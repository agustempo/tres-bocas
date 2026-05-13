<x-app-layout>

<div class="bg-white min-h-screen dark:bg-gray-900">

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        {{-- ── ERROR STATE ── --}}
        @if($tide['has_error'])
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 flex items-start gap-3 dark:bg-amber-900/20 dark:border-amber-800">
                <span class="text-xl mt-0.5">⚠️</span>
                <div>
                    <p class="font-semibold text-amber-800 text-sm dark:text-amber-300">{{ __('ui.tide_error_title') }}</p>
                    <p class="text-amber-700 text-xs mt-1 dark:text-amber-400">
                        {{ __('ui.tide_error_body') }}
                        <a href="https://www.hidro.gov.ar/oceanografia/pronostico.asp"
                           target="_blank" rel="noopener"
                           class="underline">hidro.gov.ar</a>.
                    </p>
                </div>
            </div>
        @endif


        {{-- ── WEATHER SUMMARY ── --}}
        @php
            $weather = $tide['weather'] ?? ['available' => false, 'hourly' => []];

            $condBg = [
                'clear'         => 'bg-gradient-to-br from-amber-50/60 to-orange-50/40 dark:from-amber-900/10 dark:to-orange-900/5',
                'partly_cloudy' => 'bg-gradient-to-br from-sky-50/50 to-blue-50/30 dark:from-sky-900/10 dark:to-blue-900/5',
                'cloudy'        => 'bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-800 dark:to-gray-900',
                'fog'           => 'bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-900',
                'rain'          => 'bg-gradient-to-br from-blue-50/60 to-cyan-50/40 dark:from-blue-900/10 dark:to-cyan-900/5',
                'storm'         => 'bg-gradient-to-br from-slate-100 to-gray-100 dark:from-slate-800 dark:to-gray-900',
            ];

            $ct      = $weather['condition_type'] ?? 'cloudy';
            $bgClass = $condBg[$ct] ?? $condBg['cloudy'];

            $windSpeed  = $weather['wind']['speed'] ?? 0;
            $windClass  = match(true) {
                $windSpeed >= 50 => 'text-red-500 dark:text-red-400',
                $windSpeed >= 30 => 'text-amber-500 dark:text-amber-400',
                default          => 'text-gray-600 dark:text-gray-300',
            };
            $arrowClass = match(true) {
                $windSpeed >= 50 => 'text-red-500',
                $windSpeed >= 30 => 'text-amber-500',
                default          => 'text-blue-400',
            };
            $rainColor = ($weather['rain'] ?? 0) >= 60
                ? 'text-blue-600 dark:text-blue-400'
                : (($weather['rain'] ?? 0) >= 30
                    ? 'text-blue-500 dark:text-blue-400'
                    : 'text-gray-500 dark:text-gray-400');
        @endphp

        @if($weather['available'])
        <div x-data="{ open: false }"
             class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden
                    dark:border-gray-800 dark:shadow-black/20 {{ $bgClass }}">

            {{-- ── Part 1: Current conditions (always visible) ── --}}
            <div class="px-5 pt-5 pb-4">

                {{-- Responsive: stacked on mobile, side-by-side on sm+ --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:gap-6">

                {{-- Left: emoji + temp + condition + feels-like --}}
                <div class="flex items-start gap-4 sm:shrink-0">
                    <span class="text-5xl leading-none shrink-0 mt-1" aria-hidden="true">
                        {{ $weather['emoji'] }}
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-baseline gap-1 flex-wrap">
                            <span class="text-5xl font-black tabular-nums leading-none text-gray-900 dark:text-gray-50">
                                {{ $weather['temperature'] }}°
                            </span>
                            <span class="text-2xl font-light text-gray-400 dark:text-gray-500">C</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 mt-1">
                            {{ $weather['condition'] }}
                        </p>
                        @if(isset($weather['feels_like']))
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ __('ui.weather_feels_like') }}: {{ $weather['feels_like'] }}°C
                        </p>
                        @endif
                    </div>
                </div>

                {{-- Divider: horizontal on mobile, vertical on sm+ --}}
                <div class="border-t border-black/5 dark:border-white/5 my-3 sm:hidden"></div>
                <div class="hidden sm:block self-stretch border-l border-black/5 dark:border-white/5 mx-1"></div>

                {{-- Stats: wind | rain | humidity | clouds --}}
                <div class="flex-1 grid grid-cols-4 gap-1 text-center">

                    {{-- Wind --}}
                    @if($weather['wind']['available'])
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-6 h-6 flex items-center justify-center">
                            <svg style="transform: rotate({{ $weather['wind']['direction_deg'] }}deg)"
                                 class="{{ $arrowClass }}"
                                 viewBox="0 0 24 24" fill="currentColor" width="20" height="20"
                                 aria-hidden="true">
                                <path d="M12 2 L16 20 L12 17 L8 20 Z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold {{ $windClass }} tabular-nums leading-tight">
                            {{ $windSpeed }}<span class="font-normal text-[10px]"> km/h</span>
                        </span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 leading-tight">
                            {{ $weather['wind']['direction'] }}
                        </span>
                        @if($windSpeed >= 30)
                        <span class="text-[10px] font-semibold {{ $arrowClass }} leading-tight">
                            {{ __('ui.weather_strong_wind') }}
                        </span>
                        @endif
                    </div>
                    @else
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-xl">💨</span>
                        <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ __('ui.wind') }}</span>
                    </div>
                    @endif

                    {{-- Rain --}}
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-6 h-6 flex items-center justify-center">
                            <span class="text-xl leading-none" aria-hidden="true">💧</span>
                        </div>
                        <span class="text-xs font-bold {{ $rainColor }} tabular-nums leading-tight">
                            {{ $weather['rain'] }}%
                        </span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 leading-tight">
                            {{ __('ui.weather_rain') }}
                        </span>
                    </div>

                    {{-- Humidity --}}
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-6 h-6 flex items-center justify-center">
                            <span class="text-xl leading-none" aria-hidden="true">💦</span>
                        </div>
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300 tabular-nums leading-tight">
                            {{ isset($weather['humidity']) ? $weather['humidity'].'%' : '—' }}
                        </span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 leading-tight">
                            {{ __('ui.weather_humidity') }}
                        </span>
                    </div>

                    {{-- Clouds --}}
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-6 h-6 flex items-center justify-center">
                            <span class="text-xl leading-none" aria-hidden="true">☁️</span>
                        </div>
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300 tabular-nums leading-tight">
                            {{ isset($weather['cloud_cover']) ? $weather['cloud_cover'].'%' : '—' }}
                        </span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 leading-tight">
                            {{ __('ui.weather_clouds') }}
                        </span>
                    </div>

                </div>
                </div>{{-- end sm:flex-row --}}
            </div>

            {{-- ── Part 2: 3-day hourly forecast (collapsible) ── --}}
            @if(!empty($weather['hourly']))
            <div class="border-t border-black/5 dark:border-white/5">

                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-5 py-3 text-left
                               text-xs font-semibold text-gray-500 dark:text-gray-400
                               hover:bg-black/5 dark:hover:bg-white/5 transition-colors select-none">
                    <span>{{ __('ui.weather_next_hours') }}</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     style="display:none">

                    {{-- Single continuous horizontal scroll with inline day separators --}}
                    <div class="border-t border-black/5 dark:border-white/5 px-5 py-4">
                        <div class="flex items-start gap-2 overflow-x-auto pb-2 -mx-1 px-1 snap-x scroll-smooth">

                            @foreach($weather['hourly'] as $dayGroup)

                            {{-- Day separator --}}
                            <div class="snap-start shrink-0 flex flex-col items-center gap-1 px-1.5 py-2.5">
                                <div class="w-px grow bg-black/10 dark:bg-white/10"></div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500
                                             whitespace-nowrap"
                                      style="writing-mode: vertical-lr; text-orientation: mixed; transform: rotate(180deg);">
                                    {{ $dayGroup['day_label'] }}
                                </span>
                                <div class="w-px grow bg-black/10 dark:bg-white/10"></div>
                            </div>

                            @foreach($dayGroup['hours'] as $hw)
                            @php
                                $hwWind = $hw['wind_speed'] ?? 0;
                                $hwWindClass = match(true) {
                                    $hwWind >= 50 => 'text-red-400',
                                    $hwWind >= 30 => 'text-amber-400',
                                    default       => 'text-gray-400 dark:text-gray-500',
                                };
                                $hwRain = $hw['rain'] >= 60
                                    ? 'text-blue-500 dark:text-blue-400'
                                    : ($hw['rain'] >= 30
                                        ? 'text-blue-400 dark:text-blue-500'
                                        : 'text-gray-400 dark:text-gray-600');
                            @endphp
                            <div class="snap-start shrink-0 flex flex-col items-center gap-1
                                        bg-white/50 dark:bg-gray-800/60
                                        border border-black/5 dark:border-white/5
                                        rounded-xl px-3 py-2.5 min-w-[68px]">
                                <span class="text-xs font-semibold tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ $hw['hour'] }}
                                </span>
                                <span class="text-xl leading-none" aria-hidden="true">{{ $hw['emoji'] }}</span>
                                <span class="text-sm font-bold tabular-nums text-gray-800 dark:text-gray-100">
                                    {{ $hw['temp'] }}°
                                </span>
                                <span class="text-xs tabular-nums {{ $hwRain }}">
                                    💧{{ $hw['rain'] }}%
                                </span>
                                @if($hwWind > 0)
                                <span class="flex items-center gap-0.5 {{ $hwWindClass }}">
                                    <svg style="transform: rotate({{ $hw['wind_dir'] }}deg)"
                                         viewBox="0 0 24 24" fill="currentColor" width="10" height="10"
                                         aria-hidden="true">
                                        <path d="M12 2 L16 20 L12 17 L8 20 Z"/>
                                    </svg>
                                    <span class="text-[10px] tabular-nums">{{ $hwWind }}</span>
                                </span>
                                @endif
                            </div>
                            @endforeach

                            @endforeach

                        </div>
                    </div>

                </div>
            </div>
            @endif

        </div>

        @endif

        {{-- ── 1. STATUS CARD ── --}}
        @php
            $status  = $tide['status'];
            $current = $tide['current'];
            $trend   = $tide['trend'] ?? 'stable';
            $wind    = $tide['wind'] ?? ['available' => false];

            $colorMap = [
                'red'    => ['bg' => 'bg-red-50 dark:bg-red-900/20',       'border' => 'border-red-200 dark:border-red-800',     'badge' => 'bg-red-500 text-white',    'level' => 'text-red-600',    'icon' => '🔴', 'msg' => 'text-gray-600 dark:text-gray-400'],
                'orange' => ['bg' => 'bg-orange-50 dark:bg-orange-900/20', 'border' => 'border-orange-200 dark:border-orange-800','badge' => 'bg-orange-500 text-white', 'level' => 'text-orange-600', 'icon' => '🟠', 'msg' => 'text-gray-600 dark:text-gray-400'],
                'yellow' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'border' => 'border-yellow-200 dark:border-yellow-800','badge' => 'bg-yellow-400 text-gray-900','level' => 'text-yellow-700','icon' => '🟡', 'msg' => 'text-gray-600 dark:text-gray-400'],
                'green'  => ['bg' => 'bg-green-50 dark:bg-green-900/20',   'border' => 'border-green-200 dark:border-green-800',  'badge' => 'bg-green-500 text-white',  'level' => 'text-green-700',  'icon' => '🟢', 'msg' => 'text-gray-600 dark:text-gray-400'],
                'gray'   => ['bg' => 'bg-gray-50 dark:bg-gray-800',        'border' => 'border-gray-200 dark:border-gray-700',    'badge' => 'bg-gray-400 text-white',   'level' => 'text-gray-600',   'icon' => '⚪', 'msg' => 'text-gray-600 dark:text-gray-400'],
            ];
            $c = $colorMap[$status['color']] ?? $colorMap['gray'];

            $trendIcon = match($trend) {
                'rising'  => '↑',
                'falling' => '↓',
                default   => '→',
            };
        @endphp

        <div x-data="{ openHourly: false }"
             class="rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} overflow-hidden">

            {{-- Main content --}}
            <div class="p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4 dark:text-gray-500">{{ __('ui.tide_status_heading') }}</p>

                <div class="flex flex-row items-start justify-between gap-6">

                    {{-- LEFT: level only --}}
                    @if($current)
                    <div class="shrink-0">
                        <div class="flex items-baseline gap-2">
                            <span class="text-5xl font-black tabular-nums leading-none {{ $c['level'] }}">
                                {{ $current['level'] }}
                            </span>
                            <span class="text-lg text-gray-400 dark:text-gray-500">m</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mt-2">
                            {{ $trendIcon }} {{ __('ui.tide_trend_'.$trend) }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $current['hour'] }}</p>
                    </div>
                    @endif

                    {{-- RIGHT: badge + message stacked --}}
                    <div class="shrink-0 space-y-2 text-right">
                        @if($status['label'])
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-black tracking-wide {{ $c['badge'] }}">
                                {{ $c['icon'] }} {{ __('ui.'.$status['label']) }}
                            </span>
                        @endif
                        @if($status['message'])
                            <div class="text-sm font-medium leading-snug mt-2 {{ $c['msg'] }}">
                                @foreach(explode("\n", __('ui.'.$status['message'])) as $line)
                                    <p>{{ $line }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            {{-- Collapsible hourly heights --}}
            @if(!empty($tide['hourly']))
            <div class="border-t border-black/5 dark:border-white/5">
                <button @click="openHourly = !openHourly"
                        class="w-full flex items-center justify-between px-6 py-3 text-left
                               text-xs font-semibold text-gray-500 dark:text-gray-400
                               hover:bg-black/5 dark:hover:bg-white/5 transition-colors select-none">
                    <span>{{ __('ui.hourly_heights') }}</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200"
                         :class="openHourly ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="openHourly"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     style="display:none">
                    <div class="overflow-x-auto border-t border-black/5 dark:border-white/5">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-black/5 dark:bg-white/5 text-left">
                                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">{{ __('ui.hour_col') }}</th>
                                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">{{ __('ui.level_col') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5 dark:divide-white/5">
                                @php $currentHour = $tide['current']['hour'] ?? null; @endphp
                                @foreach($tide['hourly'] as $row)
                                    @php $isNow = ($row['hour'] === $currentHour); @endphp
                                    <tr class="{{ $isNow ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-black/5 dark:hover:bg-white/5' }} transition-colors">
                                        <td class="px-6 py-2.5 tabular-nums {{ $isNow ? 'font-bold text-blue-700 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300' }}">
                                            @if($isNow)
                                                <span class="inline-flex items-center gap-1.5">
                                                    <span class="text-blue-500">👉</span>
                                                    <span>{{ $row['hour'] }}</span>
                                                </span>
                                            @else
                                                {{ $row['hour'] }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-2.5 font-semibold tabular-nums {{ $isNow ? 'text-blue-700 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200' }}">
                                            {{ $row['level'] }}
                                            @if($isNow)
                                                <span class="ml-2 text-xs font-normal text-blue-400 dark:text-blue-500">{{ __('ui.last_reading') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- ── 2. UPCOMING EVENTS — grouped by day ── --}}
        @if(!empty($tide['forecast']))
            @php
                // Group events by day_label, preserving sort order
                $grouped = collect($tide['forecast'])->groupBy('day_label');
            @endphp

            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="font-bold text-gray-800 text-lg dark:text-gray-100">{{ __('ui.forecast') }}</h2>
                        @if($tide['updated_at'])
                        <p class="text-xs text-gray-400 mt-0.5 dark:text-gray-500">
                            {{ __('ui.last_updated') }} <span class="font-semibold text-gray-500 dark:text-gray-400">{{ $tide['updated_at'] }}</span>
                            <span class="text-gray-300 mx-1 dark:text-gray-600">·</span>
                            <a href="{{ route('marea.index') }}" class="text-blue-500 hover:underline">{{ __('ui.update_link') }}</a>
                        </p>
                        @endif
                    </div>
                    <a href="https://www.hidro.gov.ar/oceanografia/pronostico.asp"
                       target="_blank" rel="noopener"
                       class="text-xs text-gray-400 hover:text-blue-500 transition-colors shrink-0">
                        hidro.gov.ar ↗
                    </a>
                </div>

                <div class="space-y-6">
                    @foreach($grouped as $dayLabel => $events)
                        <div>
                            {{-- Day heading --}}
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2 dark:text-gray-400">
                                <span class="w-6 h-px bg-gray-200 inline-block dark:bg-gray-700"></span>
                                {{ $dayLabel }}
                                <span class="flex-1 h-px bg-gray-100 inline-block dark:bg-gray-800"></span>
                            </h3>

                            {{-- Event cards --}}
                            @php
                                $statusBorders = [
                                    'red'    => 'border-red-200    bg-red-50    dark:border-red-800    dark:bg-red-900/20',
                                    'orange' => 'border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-900/20',
                                    'yellow' => 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20',
                                    'green'  => 'border-green-200  bg-green-50  dark:border-green-800  dark:bg-green-900/20',
                                    'gray'   => 'border-gray-200   bg-gray-50   dark:border-gray-700   dark:bg-gray-800',
                                ];
                                $statusBadges = [
                                    'red'    => 'bg-red-500    text-white',
                                    'orange' => 'bg-orange-500 text-white',
                                    'yellow' => 'bg-yellow-400 text-gray-900',
                                    'green'  => 'bg-green-500  text-white',
                                    'gray'   => 'bg-gray-400   text-white',
                                ];
                                $statusLevels = [
                                    'red'    => 'text-red-700',
                                    'orange' => 'text-orange-700',
                                    'yellow' => 'text-yellow-700',
                                    'green'  => 'text-green-700',
                                    'gray'   => 'text-gray-600',
                                ];
                                $statusIcons = [
                                    'red'    => '🔴',
                                    'orange' => '🟠',
                                    'yellow' => '🟡',
                                    'green'  => '🟢',
                                    'gray'   => '⚪',
                                ];
                            @endphp
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($events as $event)
                                    @php
                                        $isPleamar = str_contains(mb_strtolower($event['type']), 'plea');
                                        $s         = $event['status'];
                                        $col       = $s['color'];
                                    @endphp
                                    <div class="flex items-center gap-4 rounded-2xl border px-5 py-4
                                        {{ $statusBorders[$col] ?? $statusBorders['gray'] }}">

                                        {{-- Direction icon --}}
                                        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-xl bg-white/60 dark:bg-gray-700/60">
                                            {{ $isPleamar ? '⬆️' : '⬇️' }}
                                        </div>

                                        {{-- Type + time --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                {{ ucfirst(mb_strtolower($event['type'])) }}
                                            </p>
                                            <p class="text-2xl font-black tabular-nums leading-tight text-gray-800 dark:text-gray-100">
                                                {{ $event['time'] }}
                                            </p>
                                        </div>

                                        {{-- Level + status badge --}}
                                        @if($event['level'])
                                            <div class="shrink-0 text-right space-y-1">
                                                <p class="text-xl font-bold tabular-nums {{ $statusLevels[$col] ?? 'text-gray-700 dark:text-gray-200' }}">
                                                    {{ $event['level'] }}<span class="text-xs font-normal text-gray-400 ml-0.5 dark:text-gray-500">m</span>
                                                </p>
                                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold
                                                    {{ $statusBadges[$col] ?? $statusBadges['gray'] }}">
                                                    {{ $statusIcons[$col] ?? '' }} {{ __('ui.'.$s['label']) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── 3. HOURLY TABLE ── --}}

        {{-- ── 4. TIDE CHART ── --}}
        <div x-data="{ openChart: false }"
             class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20">
            <button @click="openChart = !openChart"
                    class="w-full flex items-center justify-between px-6 py-4 text-left
                           hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors select-none">
                <span class="font-bold text-gray-800 dark:text-gray-100">{{ __('ui.tide_chart_title') }}</span>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ $tide['chart_source'] }}"
                       target="_blank" rel="noopener"
                       @click.stop
                       class="text-xs text-gray-400 hover:text-blue-500 transition-colors">
                        ina.gob.ar ↗
                    </a>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                         :class="openChart ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </button>

            <div x-show="openChart"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 style="display:none">
                <div class="border-t border-gray-100 dark:border-gray-800 p-4">
                    <img src="{{ $tide['chart_image'] }}"
                         alt="{{ __('ui.tide_chart_title') }}"
                         class="w-full h-auto rounded-xl"
                         loading="lazy"
                         onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden')">
                    <p class="hidden text-sm text-gray-400 text-center py-6 dark:text-gray-500">
                        {{ __('ui.chart_unavailable') }} <a href="{{ $tide['chart_source'] }}" target="_blank" class="text-blue-500 underline">{{ __('ui.view_at_ina') }}</a>
                    </p>
                    <p class="text-xs text-gray-400 text-center mt-3 dark:text-gray-500">
                        {{ __('ui.chart_source_label') }} &mdash;
                        <a href="{{ $tide['chart_source'] }}" target="_blank" rel="noopener" class="hover:underline">
                            {{ __('ui.see_original') }}
                        </a>
                    </p>
                </div>
            </div>
        </div>

        {{-- ── SOURCES ── --}}
        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-6 py-5 dark:border-gray-800 dark:bg-gray-800">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3 dark:text-gray-500">{{ __('ui.official_sources') }}</h3>
            <ul class="space-y-1.5 text-sm">
                <li>
                    <a href="https://www.hidro.gov.ar/oceanografia/pronostico.asp"
                       target="_blank" rel="noopener"
                       class="text-blue-600 hover:underline">
                        {{ __('ui.tide_forecast_source') }}
                    </a>
                </li>
                <li>
                    <a href="https://www.hidro.gov.ar/oceanografia/alturashorarias.asp"
                       target="_blank" rel="noopener"
                       class="text-blue-600 hover:underline">
                        {{ __('ui.hourly_source') }}
                    </a>
                </li>
                <li>
                    <a href="{{ $tide['chart_source'] }}"
                       target="_blank" rel="noopener"
                       class="text-blue-600 hover:underline">
                        {{ __('ui.tide_chart_source') }}
                    </a>
                </li>
                <li>
                    <a href="https://open-meteo.com"
                       target="_blank" rel="noopener"
                       class="text-blue-600 hover:underline">
                        {{ __('ui.weather_source') }}
                    </a>
                </li>
            </ul>
        </div>

    </div>{{-- /container --}}
</div>

</x-app-layout>
