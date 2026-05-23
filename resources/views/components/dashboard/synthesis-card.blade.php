{{--
  SynthesisCard — single-scenario operational message, evaluated in priority order.

  Priority (first match wins):
    RED   1 subestada (suspended + wind>=50)
          2 temporal  (suspended + storm)
          3 inundacion grave (suspended + forecast tide>=2.60)
          4 sin servicio  (suspended, generic)
          5 lluvia torrencial (storm + heavy rain, not suspended)
    AMBER 6 tormenta eléctrica
          7 niebla densa
          8 inundación parcial (tide>=2.20)
          9 muelle bajo el agua (tide>=2.00)
         10 bajante extrema (tide<0.55)
         11 bajante fuerte (tide<0.70)
         12 lluvia + viento juntos
         13 viento norte con ola picada
         14 viento fuerte (>=30 km/h)
         15 helada (min overnight temp < 2°C)
         16 última lancha ya salió (no proximo + muelle set + past evening)
         17 bajante extrema mañana (upcoming overnight tide < 0.55)
         18 lluvia intensa sin viento (rain>=60%)
    GREEN 19 lluvia moderada (rain 20-59%)
         20 mañana temprano ideal
         21 noche tranquila
         22 llovizna fina
         ∞  todo OK (default)
--}}
@props(['tide', 'muelle', 'proximo', 'servicio' => null, 'llmSummary' => null])

@php
    use Carbon\Carbon;

    $now       = now();
    $hour      = (int) $now->format('H');

    // ── Weather ───────────────────────────────────────────────────────────────
    $weather    = $tide['weather'] ?? [];
    $weatherOk  = ($weather['available'] ?? false);
    $ct         = $weather['condition_type'] ?? 'cloudy';   // clear/partly_cloudy/cloudy/fog/rain/storm
    $windSpeed  = $weather['wind']['speed']     ?? 0;
    $windDir    = $weather['wind']['direction'] ?? '';       // cardinal already from WeatherService
    $rainPct    = $weather['rain']              ?? 0;
    $temp       = $weather['temperature']       ?? 20;

    // ── Tide ─────────────────────────────────────────────────────────────────
    $tideOk    = !($tide['has_error'] ?? true) && !empty($tide['current']);
    $level     = $tideOk ? (float) ($tide['current']['level'] ?? 0) : null;

    // Max upcoming tide level in next 6 forecast events
    $maxForecast = 0;
    $minForecast = 99;
    foreach (array_slice($tide['forecast'] ?? [], 0, 6) as $fe) {
        $fl = (float) $fe['level'];
        if ($fl > $maxForecast) $maxForecast = $fl;
        if ($fl < $minForecast) $minForecast = $fl;
    }

    // Overnight minimum temperature (22:00–06:00 next day)
    $minNightTemp = 20;
    foreach (array_slice($weather['hourly'] ?? [], 0, 2) as $dg) {
        foreach ($dg['hours'] as $hw) {
            $h = (int) explode(':', $hw['hour'])[0];
            if ($h >= 22 || $h <= 6) {
                $minNightTemp = min($minNightTemp, $hw['temp'] ?? 20);
            }
        }
    }

    // Overnight min tide (00:00–07:00) for bajante_manana scenario
    $minNightTide = null;
    foreach (array_slice($tide['forecast'] ?? [], 0, 6) as $fe) {
        $ft = trim($fe['time'] ?? '');
        $fh = (int) explode(':', $ft)[0];
        if (($fe['day_label'] ?? '') !== 'Hoy' && ($fh <= 7 || $fh >= 23)) {
            $fl = (float) $fe['level'];
            if ($minNightTide === null || $fl < $minNightTide) $minNightTide = $fl;
        }
    }

    // Service suspended flag
    $suspendido = $servicio?->suspendido ?? false;
    $motivo     = $servicio?->suspension_motivo ?? null;

    // Is wind from the north family (N, NNE, NE, NNO, NO)?
    $isNorthWind = in_array(strtoupper($windDir), ['N','NNE','NNO','NO','NE']);

    // ── Scenario evaluation ───────────────────────────────────────────────────
    $scenarioKey   = null;
    $level_display = 'green';  // red | amber | green

    // RED
    if ($suspendido && $windSpeed >= 50) {
        $scenarioKey   = 'r_subestada';
        $level_display = 'red';
    } elseif ($suspendido && $ct === 'storm') {
        $scenarioKey   = 'r_temporal';
        $level_display = 'red';
    } elseif ($suspendido && $maxForecast >= 2.60) {
        $scenarioKey   = 'r_inundacion';
        $level_display = 'red';
    } elseif ($suspendido) {
        $scenarioKey   = 'r_sin_servicio';
        $level_display = 'red';
    } elseif ($ct === 'storm' && $rainPct >= 80) {
        $scenarioKey   = 'r_lluvia_fuerte';
        $level_display = 'red';

    // AMBER
    } elseif ($ct === 'storm') {
        $scenarioKey   = 'a_tormenta';
        $level_display = 'amber';
    } elseif ($ct === 'fog') {
        $scenarioKey   = 'a_niebla';
        $level_display = 'amber';
    } elseif ($tideOk && $level >= 2.20) {
        $scenarioKey   = 'a_inundacion_p';
        $level_display = 'amber';
    } elseif ($tideOk && $level >= 2.00) {
        $scenarioKey   = 'a_muelle_bajo';
        $level_display = 'amber';
    } elseif ($tideOk && $level < 0.55) {
        $scenarioKey   = 'a_bajante_ext';
        $level_display = 'amber';
    } elseif ($tideOk && $level < 0.70) {
        $scenarioKey   = 'a_bajante';
        $level_display = 'amber';
    } elseif ($windSpeed >= 30 && $rainPct >= 30) {
        $scenarioKey   = 'a_lluvia_viento';
        $level_display = 'amber';
    } elseif ($windSpeed >= 20 && $isNorthWind) {
        $scenarioKey   = 'a_norte_picado';
        $level_display = 'amber';
    } elseif ($windSpeed >= 30) {
        $scenarioKey   = 'a_viento_fuerte';
        $level_display = 'amber';
    } elseif ($minNightTemp < 2) {
        $scenarioKey   = 'a_helada';
        $level_display = 'amber';
    } elseif ($muelle && !$proximo && $hour >= 20) {
        $scenarioKey   = 'a_ultima_lancha';
        $level_display = 'amber';
    } elseif ($minNightTide !== null && $minNightTide < 0.55) {
        $scenarioKey   = 'a_bajante_manana';
        $level_display = 'amber';
    } elseif ($rainPct >= 60) {
        $scenarioKey   = 'a_lluvia_vis';
        $level_display = 'amber';

    // GREEN
    } elseif ($rainPct >= 20) {
        $scenarioKey   = 'g_lluvia_mod';
        $level_display = 'green';
    } elseif ($hour >= 5 && $hour <= 8 && $ct === 'clear' && $windSpeed < 15) {
        $scenarioKey   = 'g_manana_ideal';
        $level_display = 'green';
    } elseif ($hour >= 20 && $windSpeed < 10) {
        $scenarioKey   = 'g_noche_calma';
        $level_display = 'green';
    } elseif ($rainPct > 0 && $rainPct < 20 && in_array($ct, ['rain','cloudy'])) {
        $scenarioKey   = 'g_llovizna';
        $level_display = 'green';
    } else {
        $scenarioKey   = 'g_todo_ok';
        $level_display = 'green';
    }

    // ── Card styles ───────────────────────────────────────────────────────────
    [$cardCls, $textCls, $dotCls, $subCls] = match($level_display) {
        'red'   => [
            'bg-red-50 dark:bg-red-950/60 border-red-300 dark:border-red-700',
            'text-red-900 dark:text-red-100',
            'bg-red-500',
            'text-red-700 dark:text-red-300',
        ],
        'amber' => [
            'bg-amber-50 dark:bg-amber-950/60 border-amber-300 dark:border-amber-700',
            'text-amber-900 dark:text-amber-100',
            'bg-amber-400',
            'text-amber-700 dark:text-amber-300',
        ],
        default => [
            'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-300 dark:border-emerald-700',
            'text-emerald-900 dark:text-emerald-100',
            'bg-emerald-400',
            'text-emerald-700 dark:text-emerald-300',
        ],
    };
@endphp

<div class="rounded-2xl border {{ $cardCls }} px-5 py-4 flex items-start gap-3">
    <span class="mt-1.5 shrink-0 w-2 h-2 rounded-full {{ $dotCls }}"></span>
    <div class="space-y-1.5 min-w-0 flex-1">

        {{-- Main scenario message: LLM when available, template as fallback --}}
        <p class="text-sm font-medium {{ $textCls }} leading-relaxed">
            {{ $llmSummary ?? __('ui.synth_' . $scenarioKey) }}
        </p>

        {{-- Suspension motive (admin-set) --}}
        @if($suspendido && $motivo)
        <p class="text-xs {{ $subCls }}">
            {{ __('ui.synth_suspension_motivo', ['motivo' => $motivo]) }}
        </p>
        @endif

        {{-- Secondary: upcoming tide alert (non-red scenarios only) --}}
        @if($level_display !== 'red' && $tideOk && !empty($tide['forecast']))
        @php
            $tideWarn = null; $tideWarnCls = null;
            foreach (array_slice($tide['forecast'], 0, 5) as $fe) {
                $fc = $fe['status']['color'] ?? 'gray';
                if (in_array($fc, ['red','orange','yellow'])) {
                    $isPlea   = str_contains(mb_strtolower($fe['type']), 'plea');
                    $wKey     = $isPlea ? 'synth_upcoming_pleamar' : 'synth_upcoming_bajamar';
                    $tideWarn = __('ui.' . $wKey, ['level' => $fe['level'], 'day' => mb_strtolower($fe['day_label'] ?? ''), 'time' => $fe['time'] ?? '']);
                    $tideWarnCls = match($fc) {
                        'red'    => 'text-red-600 dark:text-red-400',
                        'orange' => 'text-orange-500 dark:text-orange-400',
                        default  => 'text-amber-600 dark:text-amber-400',
                    };
                    break;
                }
            }
        @endphp
        @if($tideWarn)
        <p class="text-xs font-semibold {{ $tideWarnCls }}">⚠ {{ $tideWarn }}</p>
        @endif
        @endif

        {{-- Secondary: rain warning from hourly forecast --}}
        @if($level_display === 'green' && !empty($weather['hourly']))
        @php
            $rainWarn = null;
            foreach (array_slice($weather['hourly'], 0, 2) as $dg) {
                foreach ($dg['hours'] as $hw) {
                    if (($hw['rain'] ?? 0) >= 60 && $rainWarn === null) {
                        $rainWarn = __('ui.synth_rain_warning', [
                            'pct'  => $hw['rain'],
                            'hour' => $hw['hour'],
                            'day'  => mb_strtolower($dg['day_label']),
                        ]);
                        break 2;
                    }
                }
            }
        @endphp
        @if($rainWarn)
        <p class="text-xs font-semibold text-blue-600 dark:text-blue-400">🌧 {{ $rainWarn }}</p>
        @endif
        @endif

        {{-- Secondary: wind warning from hourly forecast --}}
        @if(in_array($level_display, ['green']) && !empty($weather['hourly']))
        @php
            $windWarnHour = null;
            $toCard = fn(int $d) => ['N','NE','E','SE','S','SO','O','NO'][(int) round($d / 45) % 8];
            foreach (array_slice($weather['hourly'], 0, 2) as $dg) {
                foreach ($dg['hours'] as $hw) {
                    $spd = $hw['wind_speed'] ?? 0;
                    if ($spd >= 30 && (!$windWarnHour || $spd > ($windWarnHour['wind_speed'] ?? 0))) {
                        $windWarnHour = array_merge($hw, ['day_label' => $dg['day_label']]);
                    }
                }
            }
            $windWarn = $windWarnHour
                ? __('ui.synth_wind_warning', [
                    'speed' => $windWarnHour['wind_speed'],
                    'dir'   => $toCard((int)($windWarnHour['wind_dir'] ?? 0)),
                    'hour'  => $windWarnHour['hour'],
                    'day'   => mb_strtolower($windWarnHour['day_label']),
                  ])
                : null;
        @endphp
        @if($windWarn)
        <p class="text-xs font-semibold text-amber-600 dark:text-amber-400">💨 {{ $windWarn }}</p>
        @endif
        @endif

    </div>
</div>
