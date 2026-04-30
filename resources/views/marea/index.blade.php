<x-app-layout>

<div class="bg-white min-h-screen dark:bg-gray-900">

    {{-- ── HEADER ── --}}
    <div class="bg-gradient-to-br from-blue-50 via-white to-cyan-50 border-b border-gray-100 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900 dark:border-gray-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <div class="flex items-center gap-3 mb-1">
                <span class="text-3xl leading-none">🌊</span>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight dark:text-gray-100">{{ __('ui.marea_title') }}</h1>
            </div>
            <p class="text-gray-500 text-sm ml-11 dark:text-gray-400">San Fernando &mdash; Delta del Paraná</p>

            @if($tide['updated_at'])
                <p class="text-xs text-gray-400 mt-2 ml-11 dark:text-gray-500">
                    {{ __('ui.last_updated') }} <span class="font-semibold text-gray-500 dark:text-gray-400">{{ $tide['updated_at'] }}</span>
                    <span class="text-gray-300 mx-1 dark:text-gray-600">·</span>
                    <a href="{{ route('marea.index') }}" class="text-blue-500 hover:underline">{{ __('ui.update_link') }}</a>
                </p>
            @endif
        </div>
    </div>

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

        {{-- ── 1. STATUS CARD ── --}}
        @php
            $status  = $tide['status'];
            $current = $tide['current'];
            $trend   = $tide['trend'] ?? 'Estable';
            $wind    = $tide['wind'] ?? ['available' => false];

            $colorMap = [
                'red'    => ['bg' => 'bg-red-50 dark:bg-red-900/20',       'border' => 'border-red-200 dark:border-red-800',     'badge' => 'bg-red-500 text-white',    'level' => 'text-red-600',    'icon' => '🔴'],
                'orange' => ['bg' => 'bg-orange-50 dark:bg-orange-900/20', 'border' => 'border-orange-200 dark:border-orange-800','badge' => 'bg-orange-500 text-white', 'level' => 'text-orange-600', 'icon' => '🟠'],
                'yellow' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'border' => 'border-yellow-200 dark:border-yellow-800','badge' => 'bg-yellow-400 text-gray-900','level' => 'text-yellow-700','icon' => '🟡'],
                'green'  => ['bg' => 'bg-green-50 dark:bg-green-900/20',   'border' => 'border-green-200 dark:border-green-800',  'badge' => 'bg-green-500 text-white',  'level' => 'text-green-700',  'icon' => '🟢'],
                'gray'   => ['bg' => 'bg-gray-50 dark:bg-gray-800',        'border' => 'border-gray-200 dark:border-gray-700',    'badge' => 'bg-gray-400 text-white',   'level' => 'text-gray-600',   'icon' => '⚪'],
            ];
            $c = $colorMap[$status['color']] ?? $colorMap['gray'];

            $trendIcon = match($trend) {
                'Subiendo' => '↑',
                'Bajando'  => '↓',
                default    => '→',
            };
        @endphp

        <div class="rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4 dark:text-gray-500">{{ __('ui.tide_status_heading') }}</p>

            <div class="flex flex-col sm:flex-row sm:items-start gap-6">

                {{-- LEFT: level → badge → message --}}
                <div class="flex-1 space-y-3">
                    @if($current)
                        {{-- Level --}}
                        <div class="flex items-baseline gap-2">
                            <span class="text-5xl font-black tabular-nums leading-none {{ $c['level'] }}">
                                {{ $current['level'] }}
                            </span>
                            <span class="text-lg text-gray-400 dark:text-gray-500">m</span>
                        </div>
                    @endif

                    {{-- Status badge --}}
                    @if($status['label'])
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-black tracking-wide {{ $c['badge'] }}">
                            {{ $c['icon'] }} {{ $status['label'] }}
                        </span>
                    @endif

                    {{-- Message --}}
                    @if($status['message'])
                        <div class="text-sm text-gray-700 leading-relaxed dark:text-gray-200">
                            @foreach(explode("\n", $status['message']) as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- RIGHT: trend + wind --}}
                @if($current)
                    <div class="shrink-0 sm:text-right">
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                            {{ $trendIcon }} {{ $trend }}
                        </p>
                        <p class="text-xs text-gray-400 mb-3 dark:text-gray-500">{{ $current['hour'] }}</p>

                        <div class="border-t border-black/5 pt-3">
                            @if($wind['available'])
                                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1 dark:text-gray-500">{{ __('ui.wind') }}</p>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">💨 {{ $wind['direction'] }}</p>
                                <p class="text-sm text-gray-500 tabular-nums dark:text-gray-400">{{ $wind['speed'] }} km/h</p>
                            @else
                                <p class="text-xs text-gray-400 italic dark:text-gray-500">{{ __('ui.wind_unavailable') }}</p>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>

        {{-- ── 2. UPCOMING EVENTS — grouped by day ── --}}
        @if(!empty($tide['forecast']))
            @php
                // Group events by day_label, preserving sort order
                $grouped = collect($tide['forecast'])->groupBy('day_label');
            @endphp

            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-gray-800 text-lg dark:text-gray-100">{{ __('ui.forecast') }}</h2>
                    <a href="https://www.hidro.gov.ar/oceanografia/pronostico.asp"
                       target="_blank" rel="noopener"
                       class="text-xs text-gray-400 hover:text-blue-500 transition-colors">
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
                                                    {{ $statusIcons[$col] ?? '' }} {{ $s['label'] }}
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
        @if(!empty($tide['hourly']))
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between dark:border-gray-800">
                    <h2 class="font-bold text-gray-800 dark:text-gray-100">{{ __('ui.hourly_heights') }}</h2>
                    <a href="https://www.hidro.gov.ar/oceanografia/alturashorarias.asp"
                       target="_blank" rel="noopener"
                       class="text-xs text-gray-400 hover:text-blue-500 transition-colors">
                        hidro.gov.ar ↗
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left dark:bg-gray-950">
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">{{ __('ui.hour_col') }}</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">{{ __('ui.level_col') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @php
                                // Use the same entry the service resolved as "current" — exact match, no recomputation.
                                $currentHour = $tide['current']['hour'] ?? null;
                            @endphp
                            @foreach($tide['hourly'] as $row)
                                @php $isNow = ($row['hour'] === $currentHour); @endphp
                                <tr class="{{ $isNow ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }} transition-colors">
                                    <td class="px-5 py-2.5 tabular-nums {{ $isNow ? 'font-bold text-blue-700 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300' }}">
                                        @if($isNow)
                                            <span title="{{ __('ui.last_reading_tooltip') }}" class="inline-flex items-center gap-1.5">
                                                <span class="text-blue-500">👉</span>
                                                <span>{{ $row['hour'] }}</span>
                                            </span>
                                        @else
                                            {{ $row['hour'] }}
                                        @endif
                                    </td>
                                    <td class="px-5 py-2.5 font-semibold tabular-nums {{ $isNow ? 'text-blue-700 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200' }}">
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
        @endif

        {{-- ── 4. TIDE CHART ── --}}
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between dark:border-gray-800">
                <h2 class="font-bold text-gray-800 dark:text-gray-100">{{ __('ui.tide_chart_title') }}</h2>
                <a href="{{ $tide['chart_source'] }}"
                   target="_blank" rel="noopener"
                   class="text-xs text-gray-400 hover:text-blue-500 transition-colors">
                    ina.gob.ar ↗
                </a>
            </div>
            <div class="p-4">
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
            </ul>
        </div>

    </div>{{-- /container --}}
</div>

</x-app-layout>
