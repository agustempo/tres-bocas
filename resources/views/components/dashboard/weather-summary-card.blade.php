{{-- WeatherSummaryCard — compact status tile (dashboard 2-col row) --}}
@props(['tide'])

@php
    $weather   = $tide['weather'] ?? ['available' => false];
    $weatherOk = $weather['available'] ?? false;
    if (!$weatherOk) return;

    $windSpeed     = $weather['wind']['speed'] ?? 0;
    $windOk        = $weather['wind']['available'] ?? false;
    $rainPct       = $weather['rain'] ?? 0;
    $temp          = $weather['temperature'] ?? null;
    $feelsLike     = $weather['feels_like'] ?? $temp;
    $condType      = $weather['condition_type'] ?? 'cloudy';

    // Sensación del día — prioridad: condición extrema > viento > temperatura
    [$pillLabel, $pillCls] = match(true) {
        $condType === 'storm'
            => ['Tormentoso', 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'],
        $windSpeed >= 40
            => ['Muy ventoso', 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
        $condType === 'rain' && $rainPct >= 60
            => ['Lluvioso', 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'],
        $condType === 'fog'
            => ['Brumoso', 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'],
        $windSpeed >= 25
            => ['Ventoso', 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
        $feelsLike !== null && $feelsLike <= 8
            => ['Frío', 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'],
        $feelsLike !== null && $feelsLike <= 14
            => ['Fresquito', 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-300'],
        $feelsLike !== null && $feelsLike <= 22
            => ['Templado', 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'],
        $feelsLike !== null && $feelsLike <= 28
            => ['Calentito', 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400'],
        default
            => ['Caluroso', 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400'],
    };
@endphp

<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-4 flex flex-col gap-2">

    {{-- Label row --}}
    <div class="flex items-center justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            {{ ($weather['is_day'] ?? true) ? '⛅' : '🌙' }} {{ __('ui.weather_summary') }}
        </span>
        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $pillCls }}">
            {{ $pillLabel }}
        </span>
    </div>

    {{-- Temp + emoji --}}
    <div class="flex items-center gap-2">
        <span class="font-black tabular-nums leading-none text-gray-800 dark:text-gray-100" style="font-size:2rem">
            {{ $weather['temperature'] }}°
        </span>
        <span class="text-2xl leading-none" aria-hidden="true">{{ $weather['emoji'] }}</span>
    </div>

    {{-- Wind + rain chips --}}
    <div class="flex flex-wrap items-center gap-2">
        @if($windOk)
        <span class="text-xs text-gray-500 dark:text-gray-400">
            💨 {{ $windSpeed }} km/h{{ isset($weather['wind']['direction']) ? ' ' . $weather['wind']['direction'] : '' }}
        </span>
        @endif
        @if($rainPct > 0)
        <span class="text-xs text-gray-500 dark:text-gray-400">💧{{ $rainPct }}%</span>
        @endif
    </div>

    {{-- Link --}}
    <a href="{{ route('marea.index') }}"
       class="mt-auto inline-flex items-center gap-1 text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
        {{ __('ui.weather_summary') }} →
    </a>
</div>
