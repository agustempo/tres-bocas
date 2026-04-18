<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WindService
{
    const CACHE_KEY = 'wind_san_fernando';
    const CACHE_TTL = 60; // minutes

    const API_URL   = 'https://api.open-meteo.com/v1/forecast';
    const LATITUDE  = -34.44;
    const LONGITUDE = -58.56;

    /**
     * Return wind data from cache, or fetch fresh if empty.
     */
    public function getData(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL), fn() => $this->fetch());
    }

    /**
     * Force-fetch and re-cache wind data.
     */
    public function refresh(): array
    {
        $data = $this->fetch();
        Cache::put(self::CACHE_KEY, $data, now()->addMinutes(self::CACHE_TTL));
        return $data;
    }

    // ─── Fetch ────────────────────────────────────────────────────────────────

    private function fetch(): array
    {
        try {
            $response = Http::timeout(10)->get(self::API_URL, [
                'latitude'        => self::LATITUDE,
                'longitude'       => self::LONGITUDE,
                'current'         => 'wind_speed_10m,wind_direction_10m',
                'wind_speed_unit' => 'kmh',
                'timezone'        => 'America/Argentina/Buenos_Aires',
            ]);

            if (! $response->successful()) {
                Log::warning('WindService: HTTP ' . $response->status());
                return $this->errorResult();
            }

            $json = $response->json();

            $speed     = $json['current']['wind_speed_10m']    ?? null;
            $direction = $json['current']['wind_direction_10m'] ?? null;

            if ($speed === null || $direction === null) {
                Log::warning('WindService: unexpected response shape');
                return $this->errorResult();
            }

            return [
                'speed'         => round($speed),
                'direction_deg' => (int) $direction,
                'direction'     => $this->degreeToCardinal((int) $direction),
                'available'     => true,
            ];

        } catch (\Throwable $e) {
            Log::warning('WindService: fetch error', ['msg' => $e->getMessage()]);
            return $this->errorResult();
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Convert a wind bearing (0–360°) to a Spanish cardinal/intercardinal direction.
     */
    private function degreeToCardinal(int $deg): string
    {
        $deg = (($deg % 360) + 360) % 360; // normalise to 0–359

        return match (true) {
            $deg <= 22  || $deg >= 338 => 'Norte',
            $deg <= 67                 => 'Noreste',
            $deg <= 112                => 'Este',
            $deg <= 157                => 'Sudeste',
            $deg <= 202                => 'Sur',
            $deg <= 247                => 'Sudoeste',
            $deg <= 292                => 'Oeste',
            default                    => 'Noroeste',
        };
    }

    private function errorResult(): array
    {
        return [
            'speed'         => null,
            'direction_deg' => null,
            'direction'     => null,
            'available'     => false,
        ];
    }
}
