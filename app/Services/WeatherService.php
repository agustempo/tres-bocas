<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches current weather + 6-hour hourly forecast from Open-Meteo.
 * Also returns wind data (replaces WindService in TideService).
 *
 * Returns shape:
 *   available     bool
 *   temperature   int (°C)
 *   rain          int (% precipitation probability)
 *   condition     string (Spanish label)
 *   emoji         string
 *   hourly        array of { hour, temp, rain, emoji, condition }
 *   wind          { available, speed, direction_deg, direction }
 */
class WeatherService
{
    const CACHE_KEY = 'weather_san_fernando';
    const CACHE_TTL = 60; // minutes

    const API_URL   = 'https://api.open-meteo.com/v1/forecast';
    const LATITUDE  = -34.44;
    const LONGITUDE = -58.56;
    const TZ        = 'America/Argentina/Buenos_Aires';

    /** WMO weather interpretation codes → Spanish label + emoji */
    private static array $WMO = [
        0  => ['label' => 'Despejado',             'emoji' => '☀️'],
        1  => ['label' => 'Mayormente despejado',  'emoji' => '🌤️'],
        2  => ['label' => 'Parcialmente nublado',  'emoji' => '⛅'],
        3  => ['label' => 'Nublado',               'emoji' => '☁️'],
        45 => ['label' => 'Niebla',                'emoji' => '🌫️'],
        48 => ['label' => 'Niebla',                'emoji' => '🌫️'],
        51 => ['label' => 'Llovizna leve',         'emoji' => '🌦️'],
        53 => ['label' => 'Llovizna',              'emoji' => '🌧️'],
        55 => ['label' => 'Llovizna intensa',      'emoji' => '🌧️'],
        61 => ['label' => 'Lluvia leve',           'emoji' => '🌧️'],
        63 => ['label' => 'Lluvia',                'emoji' => '🌧️'],
        65 => ['label' => 'Lluvia intensa',        'emoji' => '🌧️'],
        71 => ['label' => 'Nevada leve',           'emoji' => '🌨️'],
        73 => ['label' => 'Nevada',                'emoji' => '❄️'],
        75 => ['label' => 'Nevada intensa',        'emoji' => '❄️'],
        80 => ['label' => 'Chaparrones',           'emoji' => '🌦️'],
        81 => ['label' => 'Chaparrones',           'emoji' => '🌧️'],
        82 => ['label' => 'Chaparrones intensos',  'emoji' => '🌧️'],
        95 => ['label' => 'Tormenta',              'emoji' => '⛈️'],
        96 => ['label' => 'Tormenta con granizo',  'emoji' => '⛈️'],
        99 => ['label' => 'Tormenta con granizo',  'emoji' => '⛈️'],
    ];

    // ─── Public API ───────────────────────────────────────────────────────────

    public function getData(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL), fn () => $this->fetch());
    }

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
                'current'         => 'temperature_2m,precipitation_probability,weather_code,wind_speed_10m,wind_direction_10m',
                'hourly'          => 'temperature_2m,precipitation_probability,weather_code',
                'wind_speed_unit' => 'kmh',
                'timezone'        => self::TZ,
                'forecast_days'   => 2,
            ]);

            if (! $response->successful()) {
                Log::warning('WeatherService: HTTP ' . $response->status());
                return $this->errorResult();
            }

            $json = $response->json();

            // ── Current values ──────────────────────────────────────────────
            $cur   = $json['current'] ?? [];
            $temp  = $cur['temperature_2m']            ?? null;
            $rain  = $cur['precipitation_probability'] ?? null;
            $wcode = (int) ($cur['weather_code']       ?? 0);
            $wspd  = $cur['wind_speed_10m']            ?? null;
            $wdir  = $cur['wind_direction_10m']        ?? null;

            if ($temp === null) {
                Log::warning('WeatherService: unexpected response shape');
                return $this->errorResult();
            }

            $condition = $this->decodeWmo($wcode);

            // ── Next 6 hourly slots from now ────────────────────────────────
            $hourly = $json['hourly'] ?? [];
            $times  = $hourly['time']                     ?? [];
            $hTemps = $hourly['temperature_2m']           ?? [];
            $hRains = $hourly['precipitation_probability'] ?? [];
            $hCodes = $hourly['weather_code']             ?? [];

            $now   = Carbon::now(self::TZ);
            $cards = [];

            foreach ($times as $i => $timeStr) {
                $dt = Carbon::parse($timeStr, self::TZ);
                if ($dt->lte($now)) {
                    continue;
                }
                $cond    = $this->decodeWmo((int) ($hCodes[$i] ?? 0));
                $cards[] = [
                    'hour'      => $dt->format('H:i'),
                    'temp'      => (int) round($hTemps[$i] ?? 0),
                    'rain'      => (int) round($hRains[$i] ?? 0),
                    'emoji'     => $cond['emoji'],
                    'condition' => $cond['label'],
                ];
                if (count($cards) >= 6) {
                    break;
                }
            }

            return [
                'available'   => true,
                'temperature' => (int) round($temp),
                'rain'        => (int) round($rain),
                'condition'   => $condition['label'],
                'emoji'       => $condition['emoji'],
                'hourly'      => $cards,
                // Wind sub-array — same shape WindService returns for backward compat
                'wind' => [
                    'available'     => true,
                    'speed'         => (int) round($wspd ?? 0),
                    'direction_deg' => (int) ($wdir ?? 0),
                    'direction'     => $this->degreeToCardinal((int) ($wdir ?? 0)),
                ],
            ];

        } catch (\Throwable $e) {
            Log::warning('WeatherService: fetch error', ['msg' => $e->getMessage()]);
            return $this->errorResult();
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function decodeWmo(int $code): array
    {
        return self::$WMO[$code] ?? ['label' => 'Nublado', 'emoji' => '☁️'];
    }

    private function degreeToCardinal(int $deg): string
    {
        $deg = (($deg % 360) + 360) % 360;

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
            'available'   => false,
            'temperature' => null,
            'rain'        => null,
            'condition'   => null,
            'emoji'       => null,
            'hourly'      => [],
            'wind'        => [
                'available'     => false,
                'speed'         => null,
                'direction_deg' => null,
                'direction'     => null,
            ],
        ];
    }
}
