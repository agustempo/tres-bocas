<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InaService
{
    const TZ        = 'America/Argentina/Buenos_Aires';
    const BASE      = 'https://alerta.ina.gob.ar/pub/datos/';
    const CACHE_KEY = 'ina_tide_data';
    const CACHE_TTL = 60; // minutes
    const TIMEOUT   = 8;  // seconds

    /**
     * Return cached tide data, fetching fresh on miss.
     * On API failure, falls back to the last known-good snapshot (up to 24 h old).
     * Returns null only when no data is available at all.
     */
    public function getCachedTideData(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->fetchTideData();

        if ($data !== null) {
            Cache::put(self::CACHE_KEY, $data, now()->addMinutes(self::CACHE_TTL));
            Cache::put(self::CACHE_KEY . '_stale', $data, now()->addHours(24));
            return $data;
        }

        // Both fetches failed — return stale snapshot if available
        $stale = Cache::get(self::CACHE_KEY . '_stale');
        if ($stale !== null) {
            Log::warning('InaService: returning stale cache after fetch failure');
        }
        return $stale;
    }

    // ─── Internal: fetch + merge ──────────────────────────────────────────────

    private function fetchTideData(): ?array
    {
        $obsAvailable = true;
        $frcAvailable = true;

        try {
            $observed = $this->getObserved();
        } catch (\Throwable $e) {
            Log::warning('InaService: observed fetch failed', ['error' => $e->getMessage()]);
            $observed = [];
            $obsAvailable = false;
        }

        try {
            $forecast = $this->getForecast();
        } catch (\Throwable $e) {
            Log::warning('InaService: forecast fetch failed', ['error' => $e->getMessage()]);
            $forecast = [];
            $frcAvailable = false;
        }

        if (! $obsAvailable && ! $frcAvailable) {
            return null;
        }

        // Serialize Carbon instances to ISO strings before caching
        $serialize = fn (array $point): array => array_merge($point, [
            'time' => $point['time']->format('c'),
        ]);

        $merged = array_merge(
            array_map($serialize, $observed),
            array_map($serialize, $forecast)
        );
        usort($merged, fn ($a, $b) => strcmp($a['time'], $b['time']));

        return [
            'fetched_at'           => now()->setTimezone(self::TZ)->format('c'),
            'data'                 => $merged,
            'observed_unavailable' => ! $obsAvailable,
            'forecast_unavailable' => ! $frcAvailable,
        ];
    }

    // ─── Observed ─────────────────────────────────────────────────────────────

    public function getObserved(): array
    {
        $start    = now()->setTimezone(self::TZ)->subHours(48)->format('Y-m-d\TH:i:s');
        $end      = now()->setTimezone(self::TZ)->format('Y-m-d\TH:i:s');
        $seriesId = config('services.ina.observed_series_id');

        $url = self::BASE . 'datos'
            . '&timeStart=' . $start
            . '&timeEnd=' . $end
            . '&seriesId=' . $seriesId
            . '&format=json';

        $response = Http::timeout(self::TIMEOUT)->get($url);

        if (! $response->successful()) {
            Log::warning('InaService: observed HTTP ' . $response->status());
            throw new \RuntimeException('INA observed API returned ' . $response->status());
        }

        $body  = $response->json();
        $items = $body['data'] ?? [];

        return array_values(array_map(
            fn ($item) => [
                'time'     => Carbon::parse($item['timestart'], self::TZ),
                'value'    => round((float) $item['valor'], 3),
                'type'     => 'observed',
                'error_hi' => null,
                'error_lo' => null,
            ],
            array_filter($items, fn ($item) => isset($item['timestart'], $item['valor']))
        ));
    }

    // ─── Forecast ─────────────────────────────────────────────────────────────

    public function getForecast(): array
    {
        // Fetch from 6 h ago (overlap with observed) to +96 h
        $start    = now()->setTimezone(self::TZ)->subHours(6)->format('Y-m-d\TH:i:s');
        $end      = now()->setTimezone(self::TZ)->addHours(96)->format('Y-m-d\TH:i:s');
        $seriesId = config('services.ina.forecast_series_id');
        $calId    = config('services.ina.forecast_cal_id');

        $url = self::BASE . 'datosProno'
            . '&timeStart=' . $start
            . '&timeEnd=' . $end
            . '&seriesId=' . $seriesId
            . '&calId=' . $calId
            . '&all=false'
            . '&format=json';

        $response = Http::timeout(self::TIMEOUT)->get($url);

        if (! $response->successful()) {
            Log::warning('InaService: forecast HTTP ' . $response->status());
            throw new \RuntimeException('INA forecast API returned ' . $response->status());
        }

        $body  = $response->json();
        $items = $body['data'] ?? [];

        // The API returns 5 ensemble members per timestamp.
        // Group by timestart, sort each group, take:
        //   median  → value
        //   min     → error_lo
        //   max     → error_hi
        $byTime = [];
        foreach ($items as $item) {
            if (! isset($item['timestart'], $item['valor'])) {
                continue;
            }
            $byTime[$item['timestart']][] = (float) $item['valor'];
        }

        $result = [];
        $emissionTime = now()->setTimezone(self::TZ);
        $horizonSecs  = 96 * 3600;

        foreach ($byTime as $timeStr => $vals) {
            sort($vals);
            $n      = count($vals);
            $median = $vals[(int) ($n / 2)];
            $lo     = $vals[0];
            $hi     = $vals[$n - 1];

            // If the API returned no quantile spread, synthesise a linear band
            // that grows from 0 at emission to ±0.30 m at 96 h.
            if (abs($hi - $lo) < 0.001) {
                $t       = Carbon::parse($timeStr, self::TZ);
                $elapsed = max(0, $t->diffInSeconds($emissionTime, false) * -1);
                $band    = 0.30 * min(1.0, $elapsed / $horizonSecs);
                $hi      = $median + $band;
                $lo      = $median - $band;
            }

            $result[] = [
                'time'     => Carbon::parse($timeStr, self::TZ),
                'value'    => round($median, 3),
                'type'     => 'forecast',
                'error_hi' => round($hi, 3),
                'error_lo' => round($lo, 3),
            ];
        }

        usort($result, fn ($a, $b) => $a['time'] <=> $b['time']);

        return $result;
    }
}
