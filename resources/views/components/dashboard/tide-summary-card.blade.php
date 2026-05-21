{{-- TideSummaryCard — compact status tile (dashboard 2-col row) --}}
@props(['tide'])

@php
    $tideOk = !($tide['has_error'] ?? true) && !empty($tide['status']['color']) && !empty($tide['current']);
    if (!$tideOk) return;

    $status  = $tide['status'];
    $current = $tide['current'];
    $trend   = $tide['trend'] ?? 'stable';

    $colorMap = [
        'red'    => ['level' => 'text-red-600 dark:text-red-400',       'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'],
        'orange' => ['level' => 'text-orange-500 dark:text-orange-400', 'badge' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'],
        'yellow' => ['level' => 'text-yellow-600 dark:text-yellow-400', 'badge' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300'],
        'green'  => ['level' => 'text-green-700 dark:text-green-400',   'badge' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'],
        'gray'   => ['level' => 'text-gray-600 dark:text-gray-300',     'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'],
    ];
    $c = $colorMap[$status['color']] ?? $colorMap['gray'];

    $trendIcon = match($trend) { 'rising' => '↑', 'falling' => '↓', default => '→' };
@endphp

<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-4 flex flex-col gap-2">

    {{-- Label row --}}
    <div class="flex items-center justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            🌊 {{ __('ui.marea') }}
        </span>
        @if($status['label'])
        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $c['badge'] }}">
            {{ __('ui.' . $status['label']) }}
        </span>
        @endif
    </div>

    {{-- Big level --}}
    <div class="flex items-baseline gap-1">
        <span class="font-black tabular-nums leading-none {{ $c['level'] }}" style="font-size:2rem">
            {{ $current['level'] }}
        </span>
        <span class="text-sm text-gray-400 dark:text-gray-500">m</span>
        <span class="text-sm text-gray-400 dark:text-gray-500 ml-0.5">{{ $trendIcon }}</span>
    </div>

    {{-- Link --}}
    <a href="{{ route('marea.index') }}"
       class="mt-auto inline-flex items-center gap-1 text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
        {{ __('ui.marea') }} →
    </a>
</div>
