<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TideService
{
    const CACHE_KEY  = 'tide_san_fernando';
    const CACHE_TTL  = 60; // minutes

    const URL_FORECAST   = 'https://www.hidro.gov.ar/oceanografia/pronostico.asp';
    const URL_HOURLY     = 'https://www.hidro.gov.ar/oceanografia/alturashorarias.asp';
    const CHART_IMAGE    = 'https://alerta.ina.gob.ar/ina/42-RIODELAPLATA/productos/Prono_SanFernando.png';
    const CHART_SOURCE   = 'https://www.ina.gob.ar/delta/index.php?seccion=12';

    const TZ = 'America/Argentina/Buenos_Aires';

    private static array $DAYS_ES = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

    /**
     * Return tide data, from cache or freshly fetched.
     */
    public function getData(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        return $this->refresh();
    }

    /**
     * Force-fetch fresh data from all sources and update the cache.
     */
    public function refresh(): array
    {
        $forecast = $this->fetchForecast();
        $hourly   = $this->fetchHourly();

        $current = $this->resolveCurrentLevel($hourly);
        $trend   = $this->resolveTrend($hourly, $current);
        $status  = $this->resolveStatus($current, $trend);

        $wind = app(WindService::class)->refresh();

        $data = [
            'forecast'     => $forecast,
            'hourly'       => $hourly,
            'current'      => $current,
            'trend'        => $trend,
            'status'       => $status,
            'wind'         => $wind,
            'chart_image'  => self::CHART_IMAGE,
            'chart_source' => self::CHART_SOURCE,
            'updated_at'   => now()->setTimezone(self::TZ)->format('H:i'),
            'has_error'    => ($forecast === null && $hourly === null),
        ];

        Cache::put(self::CACHE_KEY, $data, now()->addMinutes(self::CACHE_TTL));

        return $data;
    }

    // ─── Fetch ────────────────────────────────────────────────────────────────

    private function fetchForecast(): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; masBocas/1.0)'])
                ->get(self::URL_FORECAST);

            if (! $response->successful()) {
                Log::warning('TideService: forecast HTTP ' . $response->status());
                return null;
            }

            return $this->parseForecast($response->body());

        } catch (\Throwable $e) {
            Log::warning('TideService: forecast fetch error', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    private function fetchHourly(): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; masBocas/1.0)'])
                ->get(self::URL_HOURLY);

            if (! $response->successful()) {
                Log::warning('TideService: hourly HTTP ' . $response->status());
                return null;
            }

            return $this->parseHourly($response->body());

        } catch (\Throwable $e) {
            Log::warning('TideService: hourly fetch error', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    // ─── Parse ────────────────────────────────────────────────────────────────

    private function parseForecast(string $raw): ?array
    {
        $html = $this->toUtf8($raw);

        // The forecast table structure (columns: LUGAR | ESTADO | HORA | ALTURA | FECHA).
        // Multiple stations share one table. The first row for each station has the name
        // in col[0]; continuation rows for the same station have an empty col[0].
        // We collect all rows, track the current station, and keep only San Fernando entries.

        $allRows = $this->tableRows($html);
        $entries = [];
        $currentStation = '';

        foreach ($allRows as $row) {
            $cells = $this->cells($row);
            if (count($cells) < 4) {
                continue;
            }

            // Update current station when col[0] is non-empty and not a header
            $place = trim($cells[0]);
            $lower = mb_strtolower($place);
            if ($place !== '' && ! str_contains($lower, 'lugar')) {
                $currentStation = $place;
            }

            // Skip if not San Fernando
            if (! preg_match('/san\s+fernando/i', $currentStation)) {
                continue;
            }

            // Skip header rows
            if (str_contains(mb_strtolower($cells[1]), 'estado')) {
                continue;
            }

            // Skip rows with placeholder data
            if (trim($cells[2]) === '---' || trim($cells[3]) === '---') {
                continue;
            }

            $rawDate  = trim($cells[4] ?? '');
            $rawLevel = trim($cells[3]);
            $entries[] = [
                'type'      => trim($cells[1]),
                'time'      => trim($cells[2]),
                'level'     => $rawLevel,
                'date'      => $rawDate,
                'day_label' => $this->dayLabel($rawDate),
                'sort_key'  => $this->sortKey($rawDate, trim($cells[2])),
                'status'    => $this->classifyLevel((float) $rawLevel),
            ];
        }

        // Sort ascending by date + time
        usort($entries, fn($a, $b) => strcmp($a['sort_key'], $b['sort_key']));

        return $entries ?: null;
    }

    private function parseHourly(string $raw): ?array
    {
        $html = $this->toUtf8($raw);

        // The hourly page is a wide table:
        //   Row 0 (th): empty | "Mareógrafo" | timestamp1 | timestamp2 | ...
        //   Rows 1-N (td): icon | station-name | level1 | level2 | ...
        // We need to pair header timestamps with the San Fernando level values.

        // ── Extract header timestamps ──
        preg_match_all('/<th[^>]*>(.*?)<\/th>/is', $html, $hm);
        $rawHeaders = array_map(fn($c) => trim(strip_tags($c)), $hm[1]);

        // Headers look like "17/04/202608:45" — extract just the time portion
        $times = [];
        foreach ($rawHeaders as $h) {
            if (preg_match('/(\d{2}:\d{2})/', $h, $tm)) {
                $times[] = $tm[1];
            }
        }

        if (empty($times)) {
            Log::info('TideService: no time headers found in hourly page');
            return null;
        }

        // ── Find the San Fernando <tr> ──
        if (! preg_match('/San\s+Fernando/i', $html)) {
            Log::info('TideService: San Fernando not found in hourly page');
            return null;
        }

        $pos     = stripos($html, 'San Fernando');
        $trStart = strrpos(substr($html, 0, $pos), '<tr');
        $trEnd   = strpos($html, '</tr>', $pos);

        if ($trStart === false || $trEnd === false) {
            return null;
        }

        $sfRow = substr($html, $trStart, $trEnd - $trStart + 5);
        $cells = $this->cells($sfRow);

        // First two cells are the icon (empty) and the station name — skip them
        $levels = array_slice($cells, 2);

        if (empty($levels)) {
            return null;
        }

        // Pair reversed timestamps (page shows most-recent first) with levels
        // The timestamps are ordered newest→oldest in the header
        $entries = [];
        foreach ($times as $i => $time) {
            if (! isset($levels[$i])) {
                break;
            }
            $level = trim($levels[$i]);
            if ($level === '' || $level === '--' || $level === '-') {
                continue;
            }
            $entries[] = [
                'hour'  => $time,
                'level' => $level,
            ];
        }

        // Return in chronological order (oldest first)
        return $entries ? array_reverse($entries) : null;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Return a human day label relative to today (Buenos Aires time).
     * "17/04/2026" → "Hoy" / "Mañana" / weekday name.
     */
    private function dayLabel(string $dateStr): string
    {
        if ($dateStr === '' || $dateStr === '---') {
            return '';
        }

        try {
            $tz    = self::TZ;
            $today = Carbon::now($tz)->startOfDay();
            $date  = Carbon::createFromFormat('d/m/Y', $dateStr, $tz)->startOfDay();
            $diff  = (int) $today->diffInDays($date, false);

            return match ($diff) {
                0       => 'Hoy',
                1       => 'Mañana',
                default => self::$DAYS_ES[$date->dayOfWeek],
            };
        } catch (\Throwable) {
            return $dateStr;
        }
    }

    /**
     * Build a sortable key from "DD/MM/YYYY" + "HH:MM".
     * Returns "YYYY-MM-DD HH:MM" so lexicographic sort = chronological sort.
     */
    private function sortKey(string $dateStr, string $time): string
    {
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $dateStr, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]} {$time}";
        }
        return "{$dateStr} {$time}";
    }

    private function tableRows(string $html): array
    {
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $m);
        return $m[1] ?? [];
    }

    private function cells(string $row): array
    {
        preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $row, $m);
        return array_map(
            fn($cell) => trim(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            $m[1] ?? []
        );
    }

    /**
     * Convert the raw response body to UTF-8.
     * hidro.gov.ar historically served ISO-8859-1 pages.
     */
    private function toUtf8(string $raw): string
    {
        // Check meta charset declaration
        if (preg_match('/charset=["\']?([a-zA-Z0-9\-]+)/i', $raw, $m)) {
            $declared = strtolower(str_replace('-', '', $m[1]));
            if ($declared !== 'utf8') {
                return mb_convert_encoding($raw, 'UTF-8', $m[1]);
            }
        }

        // Heuristic fallback
        $detected = mb_detect_encoding($raw, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($detected && $detected !== 'UTF-8') {
            return mb_convert_encoding($raw, 'UTF-8', $detected);
        }

        return $raw;
    }

    /**
     * Find the hourly entry closest to the current time in Buenos Aires.
     */
    private function resolveCurrentLevel(?array $hourly): ?array
    {
        if (empty($hourly)) {
            return null;
        }

        $now        = now()->setTimezone(self::TZ);
        $nowMinutes = (int) $now->format('H') * 60 + (int) $now->format('i');

        $closest     = null;
        $closestDiff = PHP_INT_MAX;

        foreach ($hourly as $entry) {
            // hour may be "HH:MM" or just "HH"
            if (preg_match('/^(\d{1,2}):(\d{2})$/', $entry['hour'], $m)) {
                $entryMinutes = (int)$m[1] * 60 + (int)$m[2];
            } else {
                $entryMinutes = (int) $entry['hour'] * 60;
            }

            $diff = abs($entryMinutes - $nowMinutes);
            if ($diff < $closestDiff) {
                $closestDiff = $diff;
                $closest     = $entry;
            }
        }

        return $closest;
    }

    /**
     * Compare the current reading to the one before it in the hourly array.
     * Returns: 'Subiendo' | 'Bajando' | 'Estable'
     */
    private function resolveTrend(?array $hourly, ?array $current): string
    {
        if (empty($hourly) || $current === null || count($hourly) < 2) {
            return 'Estable';
        }

        // Find the index of the current entry
        $index = null;
        foreach ($hourly as $i => $entry) {
            if ($entry['hour'] === $current['hour']) {
                $index = $i;
                break;
            }
        }

        if ($index === null || $index === 0) {
            return 'Estable';
        }

        $prev    = (float) $hourly[$index - 1]['level'];
        $current = (float) $current['level'];
        $delta   = $current - $prev;

        if ($delta > 0.03) {
            return 'Subiendo';
        }
        if ($delta < -0.03) {
            return 'Bajando';
        }
        return 'Estable';
    }

    /**
     * Classify the current level into a status object with title, message, and color tokens.
     */
    private function resolveStatus(?array $current, string $trend): array
    {
        if ($current === null) {
            return ['label' => null, 'color' => 'gray', 'message' => null];
        }

        return $this->classifyLevel((float) $current['level']);
    }

    /**
     * Single source of truth for level → status classification.
     * Called for both the current reading and each forecast event.
     */
    private function classifyLevel(float $level): array
    {
        return match (true) {
            $level >= 2.20 => [
                'label'   => 'MAREA MUY ALTA',
                'color'   => 'red',
                'message' => "Hay agua sobre el terreno.\nEs probable que te mojes al caminar.",
            ],
            $level >= 2.00 => [
                'label'   => 'MAREA ALTA',
                'color'   => 'orange',
                'message' => "El nivel del agua es alto.\nCaminar puede ser incómodo.",
            ],
            $level >= 1.70 => [
                'label'   => 'ATENCIÓN',
                'color'   => 'yellow',
                'message' => "El agua está subiendo.\nConviene estar atento.",
            ],
            $level >= 0.70 => [
                'label'   => 'NORMAL',
                'color'   => 'green',
                'message' => 'Condiciones normales para caminar y navegar.',
            ],
            $level >= 0.40 => [
                'label'   => 'POCA AGUA',
                'color'   => 'yellow',
                'message' => 'La navegación puede ser limitada.',
            ],
            default => [
                'label'   => 'MUY POCA AGUA',
                'color'   => 'red',
                'message' => "La navegación es difícil.\nHay riesgo de encallar.",
            ],
        };
    }
}
