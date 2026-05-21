{{-- UpcomingTides — 3-col grid with the next tide events --}}
@props(['tide'])

@php
    $forecast = $tide['forecast'] ?? [];
    $upcoming = array_slice($forecast, 0, 3);
    if (empty($upcoming)) return;

    $colorMap = [
        'red'    => ['num' => 'text-red-600 dark:text-red-400',       'bg' => 'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-900/50'],
        'orange' => ['num' => 'text-orange-500 dark:text-orange-400', 'bg' => 'bg-orange-50 dark:bg-orange-900/20 border-orange-100 dark:border-orange-900/50'],
        'yellow' => ['num' => 'text-yellow-600 dark:text-yellow-400', 'bg' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-100 dark:border-yellow-900/50'],
        'green'  => ['num' => 'text-green-700 dark:text-green-400',   'bg' => 'bg-green-50 dark:bg-green-900/20 border-green-100 dark:border-green-900/50'],
        'gray'   => ['num' => 'text-gray-600 dark:text-gray-300',     'bg' => 'bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700'],
    ];
@endphp

<div>
    <p class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-1 mb-2">
        {{ __('ui.upcoming_tides_heading') }}
    </p>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0.5rem">
        @foreach($upcoming as $evt)
        @php
            $isPleamar = str_contains(mb_strtolower($evt['type']), 'plea');
            $c         = $colorMap[$evt['status']['color'] ?? 'gray'] ?? $colorMap['gray'];
        @endphp
        <div class="flex flex-col items-center gap-1 rounded-2xl border {{ $c['bg'] }} py-4 px-2">
            <span class="text-xl leading-none" aria-hidden="true">{{ $isPleamar ? '⬆️' : '⬇️' }}</span>
            <span class="text-lg font-black tabular-nums text-gray-800 dark:text-gray-100 leading-tight">
                {{ $evt['time'] }}
            </span>
            @if($evt['level'])
            <span class="text-sm font-bold tabular-nums {{ $c['num'] }}">{{ $evt['level'] }} m</span>
            @endif
            <span class="text-[10px] text-gray-400 dark:text-gray-500 text-center leading-tight">
                {{ $evt['day_label'] ?? '' }}
            </span>
        </div>
        @endforeach
    </div>
</div>
