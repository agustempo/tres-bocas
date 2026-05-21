{{-- WeatherSummaryCard — compact status tile (dashboard 2-col row) --}}
@props(['tide'])

@php
    $weather   = $tide['weather'] ?? ['available' => false];
    $weatherOk = $weather['available'] ?? false;
    if (!$weatherOk) return;

    $windSpeed = $weather['wind']['speed'] ?? 0;
    $windOk    = $weather['wind']['available'] ?? false;
    $rainPct   = $weather['rain'] ?? 0;

    [$pillCls, $pillKey] = match(true) {
        $windSpeed >= 50 => ['bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',       'wind_dangerous'],
        $windSpeed >= 30 => ['bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300','wind_caution'],
        default          => ['bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300','wind_navigable'],
    };
@endphp

<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-4 flex flex-col gap-2">

    {{-- Label row --}}
    <div class="flex items-center justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            ⛅ {{ __('ui.weather_summary') }}
        </span>
        @if($windOk)
        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $pillCls }}">
            {{ __('ui.' . $pillKey) }}
        </span>
        @endif
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
