<?php

namespace App\Http\Controllers;

use App\Services\InaService;
use App\Services\TideService;
use App\Services\TideSummaryService;
use Carbon\Carbon;
use Illuminate\View\View;

class MareaController extends Controller
{
    const TZ = 'America/Argentina/Buenos_Aires';

    public function __construct(
        private TideService        $tideService,
        private InaService         $inaService,
        private TideSummaryService $summaryService,
    ) {}

    public function index(): View
    {
        $tide   = $this->tideService->getData();
        $inaRaw = $this->inaService->getCachedTideData();

        $nowIso = Carbon::now(self::TZ)->format('c');

        // ── SHN series ────────────────────────────────────────────────────────
        $shnObserved = $this->tideService->buildObservedSeries($tide['hourly'] ?? []);

        // Merge recently-passed SHN events (linger cache) so they remain
        // visible in the chart and event list for up to 50 minutes after
        // the API drops them.
        $shnForecastRaw = $tide['forecast'] ?? [];
        $lingeringRaw   = $this->tideService->getLingeringForecast();

        if (! empty($lingeringRaw)) {
            $existingKeys = array_flip(array_column($shnForecastRaw, 'sort_key'));
            foreach ($lingeringRaw as $entry) {
                $key = $entry['sort_key'] ?? '';
                if ($key !== '' && ! isset($existingKeys[$key])) {
                    $shnForecastRaw[] = $entry;
                }
            }
            usort($shnForecastRaw, fn ($a, $b) => strcmp($a['sort_key'] ?? '', $b['sort_key'] ?? ''));
        }

        $shnForecast = $this->tideService->buildForecastSeries($shnForecastRaw);

        // ── INA forecast series ───────────────────────────────────────────────
        // Include points from the last 50 min so recently-passed extremes survive
        // in detectExtremes() — mirrors the SHN linger window.
        $inaForecast = [];
        if ($inaRaw && ! empty($inaRaw['data'])) {
            $lingerCutoff = Carbon::now(self::TZ)->subMinutes(50)->format('c');
            $inaForecast  = array_values(array_filter(
                $inaRaw['data'],
                fn ($p) => $p['type'] === 'forecast' && strcmp($p['time'], $lingerCutoff) >= 0
            ));
        }

        // ── Derived data ──────────────────────────────────────────────────────
        $inaExtremes = $this->detectExtremes($inaForecast);
        $events      = $this->buildEvents($shnForecast, $inaExtremes, $nowIso);
        $alarms      = $this->generateAlarms($inaForecast, $nowIso);
        $comparison  = $this->buildComparison($events, $inaExtremes, $nowIso);
        $seWind      = $this->getSeWindForecast($tide['weather'] ?? []);
        $summary     = $this->buildSummary($tide, $comparison, $inaExtremes, $nowIso);
        $windHourly  = $this->buildWindSeries($tide['weather'] ?? []);

        // ── Chart data bundle (embedded as JSON in the page) ─────────────────
        $chartData = [
            'now'          => $nowIso,
            'shn_observed' => $shnObserved,
            'shn_forecast' => $shnForecast,
            'ina_forecast' => $inaForecast,
            'wind_hourly'  => $windHourly,
            'thresholds'   => ['alert' => 3.0, 'evacuation' => 3.5],
        ];

        // ── LLM-generated operational summary (pre-computed by the scheduled command) ──
        $llmSummary = $this->summaryService->getCached();

        return view('marea.index', [
            'tide'       => $tide,
            'chartData'  => $chartData,
            'events'     => $events,
            'alarms'     => $alarms,
            'comparison' => $comparison,
            'seWind'     => $seWind,
            'summary'    => $summary,
            'llmSummary' => $llmSummary,
        ]);
    }

    // ── Local extremes detection ───────────────────────────────────────────────

    private function detectExtremes(array $series): array
    {
        $extremes = [];
        $n = count($series);
        for ($i = 1; $i < $n - 1; $i++) {
            $prev = $series[$i - 1]['value'];
            $curr = $series[$i]['value'];
            $next = $series[$i + 1]['value'];
            if ($curr > $prev && $curr > $next) {
                $extremes[] = array_merge($series[$i], ['kind' => 'max']);
            } elseif ($curr < $prev && $curr < $next) {
                $extremes[] = array_merge($series[$i], ['kind' => 'min']);
            }
        }
        return $extremes;
    }

    // ── Events grid ───────────────────────────────────────────────────────────

    private function buildEvents(array $shnForecast, array $inaExtremes, string $nowIso): array
    {
        $tz          = self::TZ;
        $matchWindow = config('tide.event_match_window_minutes', 30);
        $nowCarbon   = Carbon::now($tz);

        $events          = [];
        $usedInaIndices  = [];

        // Start from SHN future extremes
        foreach ($shnForecast as $shn) {
            $shnTs = Carbon::parse($shn['time'], $tz);
            // Skip events more than LINGER_MINUTES in the past
            if ($shnTs->lt($nowCarbon->copy()->subMinutes(50))) {
                continue;
            }
            $paired = null;
            $pairedIdx = null;

            foreach ($inaExtremes as $i => $ina) {
                if ($ina['kind'] !== $shn['kind'] || isset($usedInaIndices[$i])) {
                    continue;
                }
                if (abs(Carbon::parse($ina['time'], $tz)->diffInMinutes($shnTs)) <= $matchWindow) {
                    $paired    = $ina;
                    $pairedIdx = $i;
                    break;
                }
            }

            if ($pairedIdx !== null) {
                $usedInaIndices[$pairedIdx] = true;
            }

            $events[] = [
                'kind'      => $shn['kind'],
                'time'      => $shn['time'],
                'value'     => $shn['value'],
                'ina_value' => $paired ? $paired['value'] : null,
                'source'    => $paired ? 'both' : 'shn',
                'status'    => $shn['status'],
                'day_label' => $shn['day_label'],
                'relative'  => $this->relativeTime($shnTs, $nowCarbon),
            ];
        }

        // Add INA-only extremes (no SHN pair)
        foreach ($inaExtremes as $i => $ina) {
            if (isset($usedInaIndices[$i])) {
                continue;
            }
            $inaTs = Carbon::parse($ina['time'], $tz);
            if ($inaTs->lte($nowCarbon)) {
                continue;
            }
            $events[] = [
                'kind'      => $ina['kind'],
                'time'      => $ina['time'],
                'value'     => $ina['value'],
                'ina_value' => null,
                'source'    => 'ina',
                'status'    => $this->tideService->classifyLevel($ina['value']),
                'day_label' => $this->dayLabelFromIso($ina['time']),
                'relative'  => $this->relativeTime($inaTs, $nowCarbon),
            ];
        }

        usort($events, fn ($a, $b) => strcmp($a['time'], $b['time']));

        // Drop INA-only events that have an SHN event of the same kind within 90 min.
        // Keeps the SHN version as the authoritative card when both sources cover the same tide cycle.
        $shnTimes = [];
        foreach ($events as $e) {
            if ($e['source'] !== 'ina') {
                $shnTimes[] = ['kind' => $e['kind'], 'ts' => Carbon::parse($e['time'], $tz)];
            }
        }
        $events = array_values(array_filter($events, function ($e) use ($shnTimes, $tz) {
            if ($e['source'] !== 'ina') {
                return true;
            }
            $inaTs = Carbon::parse($e['time'], $tz);
            foreach ($shnTimes as $shn) {
                if ($shn['kind'] === $e['kind'] && abs($shn['ts']->diffInMinutes($inaTs)) <= 90) {
                    return false;
                }
            }
            return true;
        }));

        return array_slice($events, 0, 6);
    }

    // ── Alarms ────────────────────────────────────────────────────────────────

    private function generateAlarms(array $inaForecast, string $nowIso): array
    {
        $raw = [];
        foreach ($inaForecast as $p) {
            $v = $p['value'];
            if      ($v < 0.40) $type = 'extreme_low';
            elseif  ($v < 0.70) $type = 'low';
            elseif  ($v > 3.00) $type = 'alert';
            elseif  ($v > 2.20) $type = 'very_high';
            elseif  ($v > 2.00) $type = 'high';
            else                 continue;
            $raw[] = ['type' => $type, 'time' => $p['time'], 'value' => $v];
        }

        // One alarm per type per 12-hour window
        $seen   = [];
        $alarms = [];
        $nowC   = Carbon::now(self::TZ);

        foreach ($raw as $a) {
            $window = (string) floor(strtotime($a['time']) / 43200);
            $key    = $a['type'] . '_' . $window;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $alarms[]   = array_merge($a, [
                'relative'  => $this->relativeTime(Carbon::parse($a['time'], self::TZ), $nowC),
                'day_label' => $this->dayLabelFromIso($a['time']),
            ]);
        }

        return $alarms;
    }

    // ── Comparison card ───────────────────────────────────────────────────────

    private function buildComparison(array $events, array $inaExtremes, string $nowIso): ?array
    {
        if (empty($events)) {
            return null;
        }

        $tz   = self::TZ;
        $nowC = Carbon::now($tz);

        // Use the chronologically next event (events are already sorted + filtered by buildEvents)
        $next = null;
        foreach ($events as $evt) {
            if (Carbon::parse($evt['time'], $tz)->gte($nowC->copy()->subMinutes(50))) {
                $next = $evt;
                break;
            }
        }
        if ($next === null) {
            return null;
        }

        $ts     = Carbon::parse($next['time'], $tz);
        $source = $next['source']; // 'shn', 'ina', or 'both'

        $shnTime  = in_array($source, ['shn', 'both']) ? $next['time']  : null;
        $shnValue = in_array($source, ['shn', 'both']) ? $next['value'] : null;
        $inaValue = $source === 'ina' ? $next['value'] : ($next['ina_value'] ?? null);
        $inaTime  = null;

        // Resolve INA timestamp for paired events
        if ($inaValue !== null && $source !== 'ina') {
            $matchWindow = config('tide.event_match_window_minutes', 30);
            foreach ($inaExtremes as $ina) {
                if ($ina['kind'] !== $next['kind']) {
                    continue;
                }
                if (abs(Carbon::parse($ina['time'], $tz)->diffInMinutes($ts)) <= $matchWindow) {
                    $inaTime = $ina['time'];
                    break;
                }
            }
        } elseif ($source === 'ina') {
            $inaTime = $next['time'];
        }

        // Difference only meaningful when both sources are present
        $diff   = null;
        $interp = null;
        if ($shnValue !== null && $inaValue !== null) {
            $diff   = round(abs($shnValue - $inaValue), 3);
            $interp = match (true) {
                $diff < 0.10 => 'agree',
                $diff < 0.20 => 'minor_diff',
                default      => 'notable_diff',
            };
        }

        return [
            'kind'      => $next['kind'],
            'source'    => $source,
            'shn_time'  => $shnTime,
            'shn_value' => $shnValue,
            'ina_time'  => $inaTime,
            'ina_value' => $inaValue,
            'diff'      => $diff,
            'interp'    => $interp,
            'day_label' => $next['day_label'],
            'relative'  => $this->relativeTime($ts, $nowC),
            'status'    => $next['status'],
        ];
    }

    // ── SE Wind ───────────────────────────────────────────────────────────────

    private function getSeWindForecast(array $weather): array
    {
        if (! ($weather['available'] ?? false)) {
            return ['has_se' => false, 'sustained' => false, 'slots' => []];
        }

        $minDeg    = config('tide.se_wind_min_degrees', 100);
        $maxDeg    = config('tide.se_wind_max_degrees', 170);
        $threshold = config('tide.se_wind_threshold_kmh', 15);
        $minSlots  = config('tide.se_wind_sustained_slots', 3);

        $slots = [];
        foreach ($weather['hourly'] ?? [] as $dayGroup) {
            foreach ($dayGroup['hours'] ?? [] as $h) {
                $dir  = $h['wind_dir'] ?? 0;
                $spd  = $h['wind_speed'] ?? 0;
                $isSE = $dir >= $minDeg && $dir <= $maxDeg;
                $slots[] = [
                    'hour'        => $h['hour'],
                    'speed'       => $spd,
                    'dir_deg'     => $dir,
                    'is_se'       => $isSE,
                    'highlighted' => $isSE && $spd >= $threshold,
                ];
            }
        }

        // Detect sustained SE wind
        $consecutive = 0;
        $sustained   = false;
        foreach ($slots as $s) {
            if ($s['highlighted']) {
                $consecutive++;
                if ($consecutive >= $minSlots) {
                    $sustained = true;
                    break;
                }
            } else {
                $consecutive = 0;
            }
        }

        $hasSE = collect($slots)->where('highlighted', true)->count() > 0;

        return ['has_se' => $hasSE, 'sustained' => $sustained, 'slots' => $slots];
    }

    // ── Wind series for chart ─────────────────────────────────────────────────

    private function buildWindSeries(array $weather): array
    {
        if (! ($weather['available'] ?? false)) {
            return [];
        }

        $minDeg = config('tide.se_wind_min_degrees', 100);
        $maxDeg = config('tide.se_wind_max_degrees', 170);
        $tz     = self::TZ;
        $result = [];

        foreach ($weather['hourly'] ?? [] as $dayGroup) {
            $date = $dayGroup['date'] ?? null;
            if (! $date) {
                continue;
            }
            foreach ($dayGroup['hours'] ?? [] as $h) {
                $dir    = (int) ($h['wind_dir'] ?? 0);
                $spd    = (int) ($h['wind_speed'] ?? 0);
                $isSE   = $dir >= $minDeg && $dir <= $maxDeg;
                $isoStr = $date . 'T' . $h['hour'] . ':00';

                try {
                    $ts = Carbon::parse($isoStr, $tz)->format('c');
                } catch (\Throwable) {
                    continue;
                }

                $result[] = [
                    'time'    => $ts,
                    'speed'   => $spd,
                    'dir_deg' => $dir,
                    'is_se'   => $isSE,
                ];
            }
        }

        return $result;
    }

    // ── Operational summary ───────────────────────────────────────────────────

    private function buildSummary(array $tide, ?array $comparison, array $inaExtremes, string $nowIso): array
    {
        $nowC = Carbon::now(self::TZ);

        // Next INA extreme in the future
        $nextIna = null;
        foreach ($inaExtremes as $e) {
            if (strcmp($e['time'], $nowIso) > 0) {
                $nextIna = $e;
                break;
            }
        }

        return [
            'trend'      => $tide['trend'] ?? 'stable',
            'comparison' => $comparison,
            'next_ina'   => $nextIna ? [
                'kind'     => $nextIna['kind'],
                'value'    => $nextIna['value'],
                'time_str' => Carbon::parse($nextIna['time'], self::TZ)->format('H:i'),
                'day'      => $this->dayLabelFromIso($nextIna['time']),
                'relative' => $this->relativeTime(Carbon::parse($nextIna['time'], self::TZ), $nowC),
            ] : null,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function relativeTime(Carbon $target, Carbon $now): string
    {
        // positive = target is in the future, negative = target already passed
        $mins = (int) $now->diffInMinutes($target, false);

        if ($mins < -50) {
            return ''; // outside linger window, hide
        }
        if ($mins < 0) {
            return 'hace ' . abs($mins) . 'm';
        }
        if ($mins === 0) {
            return '';
        }
        if ($mins < 60) {
            return "en {$mins}m";
        }
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return $m > 0 ? "en {$h}h {$m}m" : "en {$h}h";
    }

    private function dayLabelFromIso(string $iso): string
    {
        static $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        try {
            $tz    = self::TZ;
            $date  = Carbon::parse($iso, $tz)->startOfDay();
            $today = Carbon::now($tz)->startOfDay();
            $diff  = (int) $today->diffInDays($date, false);
            return match ($diff) {
                0       => 'Hoy',
                1       => 'Mañana',
                default => $days[$date->dayOfWeek],
            };
        } catch (\Throwable) {
            return '';
        }
    }
}
