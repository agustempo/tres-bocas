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
    const CACHE_KEY       = 'weather_san_fernando';
    const CACHE_TTL       = 30;  // minutes — fresh data
    const CACHE_STALE_KEY = 'weather_san_fernando_stale';
    const CACHE_STALE_TTL = 360; // minutes — fallback si la API falla

    const API_URL   = 'https://api.open-meteo.com/v1/forecast';
    const LATITUDE  = -34.44;
    const LONGITUDE = -58.56;
    const TZ        = 'America/Argentina/Buenos_Aires';

    /** WMO weather interpretation codes → Spanish label + day emoji */
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

    /**
     * Night emoji overrides — clear/partly-cloudy codes get moon variants.
     * Rain, fog, storm emojis are the same day or night.
     */
    private static array $WMO_NIGHT = [
        0 => '🌙',
        1 => '🌙',
        2 => '🌙',  // partly cloudy night — moon still visible
        3 => '☁️',  // fully overcast: same
    ];

    // ─── Public API ───────────────────────────────────────────────────────────

    public function getData(): array
    {
        // Devolver caché fresco si existe
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return $cached;
        }

        return $this->fetchAndCache();
    }

    public function refresh(): array
    {
        return $this->fetchAndCache();
    }

    private function fetchAndCache(): array
    {
        $data = $this->fetch();

        if ($data['available']) {
            // Guardar caché fresco y actualizar el stale de respaldo
            Cache::put(self::CACHE_KEY,       $data, now()->addMinutes(self::CACHE_TTL));
            Cache::put(self::CACHE_STALE_KEY, $data, now()->addMinutes(self::CACHE_STALE_TTL));
            return $data;
        }

        // Si el fetch falló, devolver stale antes de mostrar error
        $stale = Cache::get(self::CACHE_STALE_KEY);
        if ($stale !== null) {
            Log::info('WeatherService: usando datos en caché stale');
            return $stale;
        }

        // Sin caché de ningún tipo: devolver error sin cachear
        return $data;
    }

    // ─── Fetch ────────────────────────────────────────────────────────────────

    private function fetch(): array
    {
        try {
            $response = Http::timeout(15)->retry(2, 500)->get(self::API_URL, [
                'latitude'        => self::LATITUDE,
                'longitude'       => self::LONGITUDE,
                'current'         => 'temperature_2m,apparent_temperature,relative_humidity_2m,cloud_cover,precipitation_probability,weather_code,wind_speed_10m,wind_direction_10m,wind_gusts_10m,is_day',
                'hourly'          => 'temperature_2m,precipitation_probability,weather_code,wind_speed_10m,wind_direction_10m',
                'wind_speed_unit' => 'kmh',
                'timezone'        => self::TZ,
                'forecast_days'   => 3,
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
            $isDay = (bool) ($cur['is_day']            ?? 1); // 1 = day, 0 = night

            if ($temp === null) {
                Log::warning('WeatherService: unexpected response shape');
                return $this->errorResult();
            }

            $feelsLike  = $cur['apparent_temperature']  ?? null;
            $humidity   = $cur['relative_humidity_2m']  ?? null;
            $cloudCover = $cur['cloud_cover']           ?? null;
            $gusts      = $cur['wind_gusts_10m']        ?? null;

            $condition     = $this->decodeWmo($wcode, $isDay);
            $conditionType = $this->wmoToConditionType($wcode);

            // ── Hourly forecast grouped by day (3 days) ─────────────────────
            $hourly = $json['hourly'] ?? [];
            $times  = $hourly['time']                      ?? [];
            $hTemps = $hourly['temperature_2m']            ?? [];
            $hRains = $hourly['precipitation_probability'] ?? [];
            $hCodes = $hourly['weather_code']              ?? [];
            $hWinds = $hourly['wind_speed_10m']            ?? [];
            $hDirs  = $hourly['wind_direction_10m']        ?? [];

            $now     = Carbon::now(self::TZ);
            $grouped = [];

            foreach ($times as $i => $timeStr) {
                $dt     = Carbon::parse($timeStr, self::TZ);
                $date   = $dt->toDateString();
                $hIsDay = ($dt->hour >= 7 && $dt->hour < 20); // rough day window for hourly

                if ($dt->lte($now)) {
                    continue;
                }
                if ($dt->gt($now->copy()->addDays(3)->startOfDay())) {
                    break;
                }

                $cond = $this->decodeWmo((int) ($hCodes[$i] ?? 0), $hIsDay);

                $grouped[$date][] = [
                    'hour'       => $dt->format('H:i'),
                    'temp'       => (int) round($hTemps[$i] ?? 0),
                    'rain'       => (int) round($hRains[$i] ?? 0),
                    'wind_speed' => (int) round($hWinds[$i] ?? 0),
                    'wind_dir'   => (int) ($hDirs[$i] ?? 0),
                    'emoji'      => $cond['emoji'],
                    'condition'  => $cond['label'],
                ];
            }

            $dayGroups = [];
            $dayOffset = 0;
            foreach ($grouped as $date => $hours) {
                $dt       = Carbon::parse($date, self::TZ);
                $dayLabel = match ($dayOffset) {
                    0       => 'Hoy',
                    1       => 'Mañana',
                    default => $dt->locale('es')->isoFormat('dddd D'),
                };
                $dayGroups[] = [
                    'day_label' => ucfirst($dayLabel),
                    'date'      => $date,
                    'hours'     => $hours,
                ];
                $dayOffset++;
            }

            return [
                'available'      => true,
                'temperature'    => (int) round($temp),
                'feels_like'     => $feelsLike  !== null ? (int) round($feelsLike)  : null,
                'humidity'       => $humidity   !== null ? (int) round($humidity)   : null,
                'cloud_cover'    => $cloudCover !== null ? (int) round($cloudCover) : null,
                'rain'           => (int) round($rain),
                'condition'      => $condition['label'],
                'emoji'          => $condition['emoji'],
                'is_day'         => $isDay,
                'condition_type' => $conditionType,
                'hourly'         => $dayGroups,
                'wind' => [
                    'available'     => true,
                    'speed'         => (int) round($wspd ?? 0),
                    'direction_deg' => (int) ($wdir ?? 0),
                    'direction'     => $this->degreeToCardinal((int) ($wdir ?? 0)),
                    'gusts'         => $gusts !== null ? (int) round($gusts) : null,
                ],
            ];

        } catch (\Throwable $e) {
            Log::warning('WeatherService: fetch error', ['msg' => $e->getMessage()]);
            return $this->errorResult();
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function decodeWmo(int $code, bool $isDay = true): array
    {
        $base  = self::$WMO[$code] ?? ['label' => 'Nublado', 'emoji' => '☁️'];
        if (! $isDay && isset(self::$WMO_NIGHT[$code])) {
            return array_merge($base, ['emoji' => self::$WMO_NIGHT[$code]]);
        }
        return $base;
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

    private function wmoToConditionType(int $code): string
    {
        return match (true) {
            in_array($code, [0, 1])    => 'clear',
            $code === 2                => 'partly_cloudy',
            $code === 3                => 'cloudy',
            in_array($code, [45, 48]) => 'fog',
            $code >= 51 && $code <= 82 => 'rain',
            $code >= 95 && $code <= 99 => 'storm',
            default                    => 'cloudy',
        };
    }

    private function errorResult(): array
    {
        return [
            'available'      => false,
            'temperature'    => null,
            'feels_like'     => null,
            'humidity'       => null,
            'cloud_cover'    => null,
            'rain'           => null,
            'condition'      => null,
            'emoji'          => null,
            'condition_type' => 'cloudy',
            'hourly'         => [],
            'wind'           => [
                'available'     => false,
                'speed'         => null,
                'direction_deg' => null,
                'direction'     => null,
                'gusts'         => null,
            ],
        ];
    }
}
