<x-app-layout>

{{-- Embed chart data as JS global — no extra HTTP request on page load --}}
<script>window.__tideData = @json($chartData);</script>

<div class="bg-gray-100 min-h-screen dark:bg-gray-900">
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

{{-- ══ ESTADO ACTUAL ════════════════════════════════════════════════════════ --}}
@php
    $current = $tide['current'];
    $trend   = $tide['trend'] ?? 'stable';
    $status  = $tide['status'];
    $trendIcon = match($trend) { 'rising' => '↑', 'falling' => '↓', default => '→' };
    $levelColor = match($status['color'] ?? 'gray') {
        'red'    => 'text-red-500 dark:text-red-400',
        'orange' => 'text-orange-500 dark:text-orange-400',
        'yellow' => 'text-yellow-500 dark:text-yellow-400',
        'green'  => 'text-emerald-400 dark:text-emerald-400',
        default  => 'text-gray-600 dark:text-gray-300',
    };
    $badgeColor = match($status['color'] ?? 'gray') {
        'red'    => 'bg-red-500 text-white',
        'orange' => 'bg-orange-500 text-white',
        'yellow' => 'bg-yellow-400 text-gray-900',
        'green'  => 'bg-emerald-500 text-white',
        default  => 'bg-gray-400 text-white',
    };
@endphp

<div class="rounded-2xl border border-gray-200/60 dark:border-gray-700/50 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">

    <div class="px-6 pt-5 pb-4">
        {{-- Section label --}}
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">{{ __('ui.tide_status_heading') }}</p>
            <div class="flex items-center gap-2">
                <button
                    onclick="this.style.animation='spin 0.6s linear'; window.location.reload()"
                    class="flex items-center justify-center h-7 w-7 rounded-full text-gray-500 hover:text-gray-300 hover:bg-white/10 active:scale-90 transition-all"
                    title="Actualizar" aria-label="Actualizar datos"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/>
                        <path d="M21 3v5h-5"/>
                    </svg>
                </button>
                <span class="src-badge shn-obs">{{ __('ui.tide_src_shn_obs') }}</span>
            </div>
        </div>

        <div class="flex items-start justify-between gap-4">
            {{-- Level --}}
            @if($current)
            @php
                $windInfo   = $tide['weather']['wind'] ?? [];
                $wAvail     = $windInfo['available'] ?? false;
                $wSpeed     = $windInfo['speed'] ?? 0;
                $wDirDeg    = $windInfo['direction_deg'] ?? 0;
                $wDirStr    = $windInfo['direction'] ?? '';
                $isSEWarning = $wAvail && $wDirDeg >= 100 && $wDirDeg <= 170 && $wSpeed > 12;
            @endphp
            <div>
                <div class="flex items-baseline gap-2">
                    <span class="text-6xl font-black tabular-nums leading-none {{ $levelColor }}">{{ $current['level'] }}</span>
                    <span class="text-xl text-gray-500">m</span>
                </div>
                <p class="text-sm font-semibold text-gray-400 mt-2">
                    {{ $trendIcon }} {{ __('ui.tide_trend_'.$trend) }}
                </p>
                <p class="text-xs text-gray-500">{{ $current['hour'] }}</p>
                @if($wAvail)
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <span class="text-xs text-gray-400">💨 {{ $wSpeed }} km/h {{ $wDirStr }}</span>
                    @if($isSEWarning)
                    <span class="text-xs text-amber-400 font-semibold">⚠ {{ __('ui.tide_wind_se_warning') }}</span>
                    @endif
                </div>
                @endif
            </div>
            @endif

            {{-- Badge + message --}}
            <div class="shrink-0 text-right space-y-2">
                @if($status['label'])
                <span class="inline-block px-3 py-1.5 rounded-full text-sm font-black tracking-wide {{ $badgeColor }}">
                    {{ __('ui.'.$status['label']) }}
                </span>
                @endif
            </div>
        </div>

        {{-- Operational summary: LLM-generated if available, template strings as fallback --}}
        @if($llmSummary)
        <div class="mt-4 pt-4 border-t border-gray-200/60 dark:border-white/5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
            {{ $llmSummary }}
        </div>
        @else
        @php
            $s = $summary;
            $trendPhrase = __('ui.tide_summary_'.$s['trend']);
            $nextPhrases = [];
            if ($s['next_ina']) {
                $ni = $s['next_ina'];
                $key = $ni['kind'] === 'max' ? 'tide_summary_next_max' : 'tide_summary_next_min';
                $nextPhrases[] = __('ui.'.$key, ['day' => strtolower($ni['day']), 'time' => $ni['time_str'], 'value' => number_format($ni['value'], 2)]);
                if ($ni['value'] < 0.70) $nextPhrases[] = __('ui.tide_summary_low_warn');
                if ($ni['value'] > 2.20) $nextPhrases[] = __('ui.tide_summary_high_warn');
            }
            if ($s['comparison']) $nextPhrases[] = __('ui.tide_summary_agree');
        @endphp
        @if(!empty($nextPhrases))
        <div class="mt-4 pt-4 border-t border-gray-200/60 dark:border-white/5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
            {{ $trendPhrase }}
            @foreach($nextPhrases as $i => $phrase)
                @if($i > 0 && $i === count($nextPhrases) - 1 && $s['comparison']) —
                @elseif($i > 0) —
                @endif
                {!! $i === 1 && $s['next_ina'] && $s['next_ina']['value'] < 0.70
                    ? '<strong class="text-amber-400">'.$phrase.'</strong>'
                    : e($phrase) !!}
            @endforeach
        </div>
        @endif
        @endif
    </div>

    {{-- Hourly heights table (collapsed by default) --}}
    @if(!empty($tide['hourly']))
    <div x-data="{ openHourly: false }" class="border-t border-gray-200/60 dark:border-white/5">
        <button @click="openHourly = !openHourly"
                class="w-full flex items-center justify-between px-6 py-3 text-left text-xs font-semibold text-gray-500 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors select-none">
            <span>{{ __('ui.tide_observed_section') }}</span>
            <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="openHourly ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="openHourly" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display:none">
            <div class="overflow-x-auto border-t border-gray-200/40 dark:border-white/5">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-100/80 dark:bg-white/5">
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-left">{{ __('ui.hour_col') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-left">{{ __('ui.level_col') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @php $currentHour = $tide['current']['hour'] ?? null; @endphp
                        @foreach($tide['hourly'] as $row)
                        @php $isNow = ($row['hour'] === $currentHour); @endphp
                        <tr class="{{ $isNow ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'hover:bg-gray-50 dark:hover:bg-white/5' }} transition-colors">
                            <td class="px-6 py-2.5 tabular-nums {{ $isNow ? 'font-bold text-emerald-400' : 'text-gray-400' }}">
                                @if($isNow)<span class="inline-flex items-center gap-1.5"><span class="text-emerald-400">👉</span> {{ $row['hour'] }}</span>@else{{ $row['hour'] }}@endif
                            </td>
                            <td class="px-6 py-2.5 font-semibold tabular-nums {{ $isNow ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-700 dark:text-gray-200' }}">
                                {{ $row['level'] }}
                                @if($isNow)<span class="ml-2 text-xs font-normal text-gray-500">{{ __('ui.last_reading') }}</span>@endif
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


{{-- ══ COMPARISON CARD ══════════════════════════════════════════════════════ --}}
@if($comparison)
@php
    $cmp     = $comparison;
    $isMin   = $cmp['kind'] === 'min';
    $kindLabel = $isMin ? __('ui.tide_event_min') : __('ui.tide_event_max');
    $titleKey  = __('ui.tide_comparison_title', ['kind' => strtolower($kindLabel)]);
    $interpMsg = __('ui.tide_comparison_'.$cmp['interp']);
    $interpBg  = $cmp['interp'] === 'notable_diff'
        ? 'bg-amber-50 text-amber-600 border-amber-200/60 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/40'
        : 'bg-gray-100 text-gray-500 border-gray-200/60 dark:bg-gray-800/60 dark:text-gray-400 dark:border-gray-700/40';
    $shnLevelColor = match($cmp['status']['color'] ?? 'gray') {
        'red','orange','yellow' => 'text-amber-400',
        default                  => 'text-blue-300',
    };
@endphp

<div class="rounded-2xl border border-gray-200/60 dark:border-gray-700/50 bg-white dark:bg-gray-900 overflow-hidden">

    <div class="px-5 pt-4 pb-1">
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">
            {{ $titleKey }}
        </p>
        <p class="text-xs text-gray-600">
            {{ __('ui.tide_comparison_subtitle') }}
        </p>
    </div>

    {{-- Side-by-side cards --}}
    <div class="grid grid-cols-2 gap-0 divide-x divide-gray-200/60 dark:divide-gray-700/50 px-0">

        {{-- SHN side --}}
        <div class="px-5 py-4 space-y-1">
            <span class="src-badge shn-pro">{{ __('ui.tide_src_shn_pro') }}</span>
            <p class="text-xs text-gray-500 uppercase tracking-wide mt-2">{{ $isMin ? __('ui.tide_event_min') : __('ui.tide_event_max') }}</p>
            <p class="text-3xl font-black tabular-nums {{ $shnLevelColor }}">
                {{ number_format($cmp['shn_value'], 2) }} <span class="text-base font-normal text-gray-500">m</span>
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-300 font-semibold">
                {{ \Carbon\Carbon::parse($cmp['shn_time'], 'America/Argentina/Buenos_Aires')->format('H:i') }}
                <span class="text-xs font-normal text-gray-500 ml-1">{{ strtolower($cmp['day_label']) }}</span>
            </p>
            <p class="text-[10px] text-gray-600 leading-snug mt-2">
                {{ __('ui.tide_comparison_shn_method') }}
            </p>
        </div>

        {{-- INA side --}}
        <div class="px-5 py-4 space-y-1">
            <span class="src-badge ina">{{ __('ui.tide_src_ina') }}</span>
            <p class="text-xs text-gray-500 uppercase tracking-wide mt-2">{{ $isMin ? __('ui.tide_event_min') : __('ui.tide_event_max') }}</p>
            <p class="text-3xl font-black tabular-nums text-purple-300">
                {{ number_format($cmp['ina_value'], 2) }} <span class="text-base font-normal text-gray-500">m</span>
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-300 font-semibold">
                {{ \Carbon\Carbon::parse($cmp['ina_time'], 'America/Argentina/Buenos_Aires')->format('H:i') }}
                <span class="text-xs font-normal text-gray-500 ml-1">{{ strtolower($cmp['day_label']) }}</span>
            </p>
            <p class="text-[10px] text-gray-600 leading-snug mt-2">
                {{ __('ui.tide_comparison_ina_method') }}
            </p>
        </div>
    </div>

    {{-- Difference bar --}}
    <div class="px-5 py-3 border-t border-gray-200/60 dark:border-gray-700/50">
        <div class="rounded-lg border {{ $interpBg }} px-3 py-2 text-xs">
            <span class="font-semibold">{{ __('ui.tide_comparison_diff_label', ['diff' => number_format($cmp['diff'], 2)]) }}</span>
            <span class="mx-1.5 text-gray-600">—</span>
            {{ $interpMsg }}
        </div>
    </div>
</div>
@endif


{{-- ══ EVENTO CRÍTICO PRÓXIMO ════════════════════════════════════════════════ --}}
@php
    $limit48h = \Carbon\Carbon::now('America/Argentina/Buenos_Aires')->addHours(48)->format('c');
    $criticalEvent = null;
    foreach ($events as $evt) {
        if ($evt['time'] > $limit48h) continue;
        if ($evt['value'] < 0.70 || $evt['value'] > 2.00) {
            $criticalEvent = $evt;
            break;
        }
    }
@endphp
@if($criticalEvent)
@php
    $cEvt      = $criticalEvent;
    $cIsMin    = $cEvt['kind'] === 'min';
    $cVal      = $cEvt['value'];
    $cInaVal   = $cEvt['ina_value'];
    // Separate SHN and INA display values based on source
    $cShnVal   = ($cEvt['source'] !== 'ina') ? $cVal : null;
    $cInaDisp  = ($cEvt['source'] === 'ina')  ? $cVal : $cInaVal;
    $checkVal  = $cShnVal ?? $cInaDisp ?? $cVal;
    $cImplication = match(true) {
        $checkVal > 2.20 => __('ui.tide_critical_very_high'),
        $checkVal > 2.00 => __('ui.tide_critical_high'),
        default          => __('ui.tide_critical_low'),
    };
    $cBorder   = ($checkVal < 0.70) ? 'border-amber-400/50 dark:border-amber-700/50'  : 'border-orange-400/50 dark:border-orange-700/50';
    $cBg       = ($checkVal < 0.70) ? 'bg-amber-50 dark:bg-amber-900/10'               : 'bg-orange-50 dark:bg-orange-900/10';
    $cIconTxt  = ($checkVal < 0.70) ? 'text-amber-600 dark:text-amber-400'             : 'text-orange-600 dark:text-orange-400';
    $cShnColor = ($checkVal < 0.70) ? 'text-amber-700 dark:text-amber-300'             : 'text-orange-700 dark:text-orange-300';
@endphp
<div class="rounded-2xl border {{ $cBorder }} {{ $cBg }} overflow-hidden">

    {{-- Header --}}
    <div class="px-5 pt-4 pb-2 flex items-center justify-between">
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-500">{{ __('ui.tide_critical_heading') }}</p>
        <span class="{{ $cIconTxt }} font-bold text-sm">⚠</span>
    </div>

    {{-- SHN / INA side-by-side --}}
    <div class="grid {{ ($cShnVal !== null && $cInaDisp !== null) ? 'grid-cols-2 divide-x divide-gray-300/40 dark:divide-gray-700/40' : 'grid-cols-1' }}">
        @if($cShnVal !== null)
        <div class="px-5 py-3 space-y-0.5">
            <span class="src-badge shn-pro">{{ __('ui.tide_src_shn_pro') }}</span>
            <p class="text-xs text-gray-500 uppercase tracking-wide mt-2">{{ $cIsMin ? __('ui.tide_event_min') : __('ui.tide_event_max') }}</p>
            <p class="text-2xl font-black tabular-nums {{ $cShnColor }}">
                {{ number_format($cShnVal, 2) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">m</span>
            </p>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ \Carbon\Carbon::parse($cEvt['time'], 'America/Argentina/Buenos_Aires')->format('H:i') }}
                <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">{{ strtolower($cEvt['day_label']) }}</span>
            </p>
        </div>
        @endif

        @if($cInaDisp !== null)
        <div class="px-5 py-3 space-y-0.5">
            <span class="src-badge ina">{{ __('ui.tide_src_ina') }}</span>
            <p class="text-xs text-gray-500 uppercase tracking-wide mt-2">{{ $cIsMin ? __('ui.tide_event_min') : __('ui.tide_event_max') }}</p>
            <p class="text-2xl font-black tabular-nums text-purple-600 dark:text-purple-300">
                {{ number_format($cInaDisp, 2) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">m</span>
            </p>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ \Carbon\Carbon::parse($cEvt['time'], 'America/Argentina/Buenos_Aires')->format('H:i') }}
                <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">{{ strtolower($cEvt['day_label']) }}</span>
            </p>
        </div>
        @endif
    </div>

    {{-- Implication --}}
    <div class="px-5 py-3 border-t border-gray-200/60 dark:border-gray-700/30">
        <p class="text-xs {{ $cIconTxt }} font-medium">{{ $cImplication }}</p>
    </div>
</div>
@endif


{{-- ══ EVENTS GRID ══════════════════════════════════════════════════════════ --}}
@if(!empty($events))
<div>
    <p class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-1 mb-3">
        {{ __('ui.tide_events_title') }}
    </p>

    <div class="flex gap-3 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-hide">
        @foreach($events as $evt)
        @php
            $evtIsMin   = $evt['kind'] === 'min';
            $evtColor   = $evt['status']['color'] ?? 'gray';
            $isAlarm    = in_array($evtColor, ['red', 'orange']) || $evt['value'] < 0.70;
            $cardBorder = $isAlarm
                ? 'border-amber-400/60 bg-amber-50 dark:border-amber-700/60 dark:bg-amber-900/20'
                : 'border-gray-200/80 bg-white dark:border-gray-700/40 dark:bg-gray-800/60';
            $levelTxt   = match($evtColor) {
                'red'    => 'text-red-600 dark:text-red-400',
                'orange' => 'text-amber-600 dark:text-amber-400',
                'yellow' => 'text-yellow-600 dark:text-yellow-400',
                'green'  => 'text-emerald-600 dark:text-emerald-400',
                default  => 'text-gray-700 dark:text-gray-200',
            };
        @endphp
        <div class="shrink-0 flex flex-col gap-1.5 rounded-2xl border {{ $cardBorder }} py-3 px-3 min-w-[90px]">

            {{-- Source badge + alarm icon --}}
            <div class="flex items-center justify-between gap-1">
                @if($evt['source'] !== 'ina')
                    <span class="src-badge shn-pro">{{ __('ui.tide_src_shn_pro') }}</span>
                @else
                    <span class="src-badge ina">{{ __('ui.tide_src_ina') }}</span>
                @endif
                @if($isAlarm)<span class="text-amber-400 text-xs">⚠</span>@endif
            </div>

            {{-- Direction + label --}}
            <div class="flex items-center gap-1">
                <span class="text-sm leading-none">{{ $evtIsMin ? '⬇️' : '⬆️' }}</span>
                <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    {{ $evtIsMin ? __('ui.tide_event_min') : __('ui.tide_event_max') }}
                </span>
            </div>

            {{-- Time --}}
            <p class="text-xl font-black tabular-nums text-gray-800 dark:text-gray-100 leading-tight">
                {{ \Carbon\Carbon::parse($evt['time'], 'America/Argentina/Buenos_Aires')->format('H:i') }}
            </p>

            {{-- Level --}}
            <p class="text-base font-bold tabular-nums {{ $levelTxt }}">
                {{ number_format($evt['value'], 2) }} <span class="text-[10px] font-normal text-gray-500">m</span>
            </p>

            {{-- Relative time + day --}}
            <div class="space-y-0.5 mt-auto">
                @if($evt['relative'])
                <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400">{{ $evt['relative'] }}</p>
                @endif
                <p class="text-[10px] text-gray-500 dark:text-gray-500">{{ $evt['day_label'] }}</p>
            </div>

            {{-- Status badge --}}
            @if($evt['status']['label'])
            <span class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded-full
                {{ match($evtColor) {
                    'red' => 'bg-red-900/40 text-red-400',
                    'orange' => 'bg-orange-900/40 text-amber-400',
                    'yellow' => 'bg-yellow-900/40 text-yellow-400',
                    'green' => 'bg-emerald-900/40 text-emerald-400',
                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                } }}">
                {{ __('ui.'.$evt['status']['label']) }}
            </span>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif


{{-- ══ ALARM BANNERS (disabled for now) ═════════════════════════════════════ --}}


{{-- ══ GRÁFICO DE NIVEL ═════════════════════════════════════════════════════ --}}
<div class="rounded-2xl border border-gray-200/60 bg-white shadow-sm overflow-hidden
            dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20">
    <x-marea-chart />
</div>


{{-- ══ VIENTO SE (sudestada card) ══════════════════════════════════════════ --}}
@if($seWind['has_se'])
<div class="rounded-2xl border border-blue-200/40 bg-blue-50/30 dark:bg-blue-900/10 dark:border-blue-800/30 overflow-hidden">
    <div class="px-5 py-4">
        <div class="flex items-start gap-3">
            <span class="text-2xl shrink-0">🌬️</span>
            <div>
                <p class="font-bold text-sm text-gray-800 dark:text-gray-100">{{ __('ui.tide_sudestada_title') }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">{{ __('ui.tide_sudestada_body') }}</p>
                @if($seWind['sustained'])
                <p class="text-xs text-blue-600 dark:text-blue-300 font-semibold mt-2">
                    ⚠ {{ __('ui.tide_sudestada_warning') }}
                </p>
                @endif
            </div>
        </div>
    </div>
    @if(!empty($seWind['slots']))
    <div class="border-t border-blue-200/20 dark:border-blue-800/20 px-5 py-3">
        <div class="flex gap-2 overflow-x-auto scrollbar-hide">
            @foreach($seWind['slots'] as $slot)
            <div class="shrink-0 flex flex-col items-center gap-0.5 rounded-xl px-2.5 py-2 min-w-[52px]
                        {{ $slot['highlighted'] ? 'bg-blue-600/20 border border-blue-500/30' : 'bg-gray-100/50 dark:bg-gray-800/40 border border-transparent' }}">
                <span class="text-[10px] font-semibold tabular-nums text-gray-500 dark:text-gray-400">{{ $slot['hour'] }}</span>
                <span class="text-xs font-bold tabular-nums {{ $slot['highlighted'] ? 'text-blue-400' : 'text-gray-600 dark:text-gray-300' }}">
                    {{ $slot['speed'] }}<span class="text-[9px] font-normal">km/h</span>
                </span>
                @if($slot['is_se'])
                <span class="text-[9px] font-bold text-blue-400">SE</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif


{{-- ══ FUENTES Y METODOLOGÍA ════════════════════════════════════════════════ --}}
<div x-data="{ open: false }"
     class="rounded-2xl border border-gray-200/60 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50 overflow-hidden">

    <button @click="open = !open"
            class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-black/5 dark:hover:bg-white/5 transition-colors select-none">
        <span class="text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ __('ui.tide_fuentes_title') }}</span>
        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display:none">
        <div class="border-t border-gray-200/60 dark:border-gray-700/50 px-6 py-5 space-y-5">

            <p class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                {{ __('ui.tide_three_sources_title') }}
            </p>

            {{-- Three source rows --}}
            <div class="space-y-4">
                <div class="flex gap-4">
                    <div class="shrink-0 pt-0.5"><span class="src-badge shn-obs">{{ __('ui.tide_src_shn_obs') }}</span></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ __('ui.tide_src_shn_obs_desc') }}</p>
                </div>
                <div class="flex gap-4">
                    <div class="shrink-0 pt-0.5"><span class="src-badge shn-pro">{{ __('ui.tide_src_shn_pro') }}</span></div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ __('ui.tide_src_shn_pro_desc') }}</p>
                        <a href="https://www.hidro.gov.ar/oceanografia" target="_blank" rel="noopener"
                           class="text-xs text-blue-500 hover:underline mt-1 inline-block">hidro.gob.ar/oceanografia ↗</a>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="shrink-0 pt-0.5"><span class="src-badge ina">{{ __('ui.tide_src_ina') }}</span></div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ __('ui.tide_src_ina_desc') }}</p>
                        <a href="https://alerta.ina.gob.ar" target="_blank" rel="noopener"
                           class="text-xs text-blue-500 hover:underline mt-1 inline-block">alerta.ina.gob.ar ↗</a>
                    </div>
                </div>
            </div>

            {{-- When to trust each one --}}
            <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-4 space-y-2">
                <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ __('ui.tide_when_to_trust_title') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.tide_when_to_trust_normal') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.tide_when_to_trust_se') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.tide_when_to_trust_diff') }}</p>
            </div>

        </div>
    </div>
</div>


</div>{{-- /container --}}
</div>{{-- /page --}}

</x-app-layout>
