{{-- HourlyWeather — collapsible card that expands inline from the header --}}
@props(['tide'])

@php
    $weather   = $tide['weather'] ?? ['available' => false];
    $weatherOk = $weather['available'] ?? false;
    $hourly    = $weather['hourly'] ?? [];
    if (!$weatherOk || empty($hourly)) return;

    $toCardinal = function (int $deg): string {
        $dirs = ['N','NE','E','SE','S','SO','O','NO'];
        return $dirs[(int) round($deg / 45) % 8];
    };

    $firstSlot   = collect($hourly)->flatMap(fn($d) => $d['hours'])->first();
    $summaryLine = $firstSlot
        ? ($firstSlot['emoji'] . ' ' . $firstSlot['temp'] . '°  💧' . ($firstSlot['rain'] ?? 0) . '%  💨' . ($firstSlot['wind_speed'] ?? 0) . 'km/h')
        : '';
@endphp

{{-- Single card — button is the header, content expands below inside the same card --}}
<div x-data="{ open: false }"
     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">

    {{-- Header / toggle --}}
    <button @click="open = !open"
            class="w-full flex items-center justify-between px-4 py-3 text-left
                   hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
        <div class="flex items-center gap-3 min-w-0">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 shrink-0">
                {{ __('ui.hourly_forecast_heading') }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400 truncate"
                  x-show="!open">{{ $summaryLine }}</span>
        </div>
        <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-200"
             :class="open ? 'rotate-180' : ''"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Expandable content — same card, separated by a border-t --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         style="display:none"
         class="border-t border-gray-100 dark:border-gray-800">

        <div class="flex overflow-x-auto pb-3 px-4 pt-4 gap-4">

            @foreach($hourly as $dayIdx => $day)
            @php
                $dayHours = array_values(array_filter(
                    $day['hours'],
                    fn($hw) => (int) explode(':', $hw['hour'])[0] % 3 === 0
                ));
            @endphp
            @if(!empty($dayHours))

            <div class="flex-none {{ $dayIdx > 0 ? 'pl-4 border-l border-gray-100 dark:border-gray-800' : '' }}">

                <p class="text-[10px] font-bold uppercase tracking-wide
                           text-gray-400 dark:text-gray-500 mb-2 whitespace-nowrap">
                    {{ $day['day_label'] }}
                </p>

                <div class="flex gap-2">
                    @foreach($dayHours as $hw)
                    @php
                        $hwRain    = $hw['rain'] ?? 0;
                        $hwWind    = $hw['wind_speed'] ?? 0;
                        $hwDir     = $toCardinal((int) ($hw['wind_dir'] ?? 0));
                        $rainColor = $hwRain >= 60 ? 'text-blue-500 dark:text-blue-400'
                                   : ($hwRain >= 30 ? 'text-blue-400 dark:text-blue-500'
                                                    : 'text-gray-400 dark:text-gray-500');
                        $windColor = $hwWind >= 50 ? 'text-red-500 dark:text-red-400'
                                   : ($hwWind >= 30 ? 'text-amber-500 dark:text-amber-400'
                                                    : 'text-gray-400 dark:text-gray-500');
                    @endphp
                    <div class="flex flex-col items-center gap-0.5
                                bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700
                                rounded-xl px-2.5 py-2" style="min-width:58px">
                        <span class="text-[10px] font-semibold tabular-nums text-gray-400 dark:text-gray-500">
                            {{ $hw['hour'] }}
                        </span>
                        <span class="text-base leading-none my-0.5" aria-hidden="true">{{ $hw['emoji'] }}</span>
                        <span class="text-xs font-bold tabular-nums text-gray-800 dark:text-gray-100">
                            {{ $hw['temp'] }}°
                        </span>
                        <span class="text-[10px] tabular-nums {{ $rainColor }}">
                            💧{{ $hwRain }}%
                        </span>
                        <span class="text-[10px] tabular-nums {{ $windColor }} whitespace-nowrap">
                            {{ $hwWind }}<span class="opacity-70"> {{ $hwDir }}</span>
                        </span>
                    </div>
                    @endforeach
                </div>

            </div>
            @endif
            @endforeach

        </div>
    </div>
</div>
