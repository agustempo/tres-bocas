<x-app-layout>

<div class="bg-white min-h-screen">

    {{-- ── HEADER ── --}}
    <div class="bg-gradient-to-br from-blue-50 via-white to-cyan-50 border-b border-gray-100">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <div class="flex items-center gap-3 mb-1">
                <span class="text-3xl leading-none">🌊</span>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">Marea</h1>
            </div>
            <p class="text-gray-500 text-sm ml-11">San Fernando &mdash; Delta del Paraná</p>

            @if($tide['updated_at'])
                <p class="text-xs text-gray-400 mt-2 ml-11">
                    Última actualización: <span class="font-semibold text-gray-500">{{ $tide['updated_at'] }}</span>
                    <span class="text-gray-300 mx-1">·</span>
                    <a href="{{ route('marea.index') }}" class="text-blue-500 hover:underline">actualizar</a>
                </p>
            @endif
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        {{-- ── ERROR STATE ── --}}
        @if($tide['has_error'])
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 flex items-start gap-3">
                <span class="text-xl mt-0.5">⚠️</span>
                <div>
                    <p class="font-semibold text-amber-800 text-sm">No se pudo obtener la información de marea en este momento.</p>
                    <p class="text-amber-700 text-xs mt-1">
                        Las fuentes oficiales pueden estar temporalmente inaccesibles.
                        Intentá de nuevo más tarde o consultá directamente en
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
                'red'    => ['bg' => 'bg-red-50',    'border' => 'border-red-200',   'badge' => 'bg-red-500 text-white',    'level' => 'text-red-600',    'icon' => '🔴'],
                'orange' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200','badge' => 'bg-orange-500 text-white', 'level' => 'text-orange-600', 'icon' => '🟠'],
                'yellow' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200','badge' => 'bg-yellow-400 text-gray-900','level' => 'text-yellow-700','icon' => '🟡'],
                'green'  => ['bg' => 'bg-green-50',  'border' => 'border-green-200', 'badge' => 'bg-green-500 text-white',  'level' => 'text-green-700',  'icon' => '🟢'],
                'gray'   => ['bg' => 'bg-gray-50',   'border' => 'border-gray-200',  'badge' => 'bg-gray-400 text-white',   'level' => 'text-gray-600',   'icon' => '⚪'],
            ];
            $c = $colorMap[$status['color']] ?? $colorMap['gray'];

            $trendIcon = match($trend) {
                'Subiendo' => '↑',
                'Bajando'  => '↓',
                default    => '→',
            };
        @endphp

        <div class="rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4">Estado de la marea</p>

            <div class="flex flex-col sm:flex-row sm:items-start gap-6">

                {{-- LEFT: level → badge → message --}}
                <div class="flex-1 space-y-3">
                    @if($current)
                        {{-- Level --}}
                        <div class="flex items-baseline gap-2">
                            <span class="text-5xl font-black tabular-nums leading-none {{ $c['level'] }}">
                                {{ $current['level'] }}
                            </span>
                            <span class="text-lg text-gray-400">m</span>
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
                        <div class="text-sm text-gray-700 leading-relaxed">
                            @foreach(explode("\n", $status['message']) as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- RIGHT: trend + wind --}}
                @if($current)
                    <div class="shrink-0 sm:text-right">
                        <p class="text-sm font-semibold text-gray-500">
                            {{ $trendIcon }} {{ $trend }}
                        </p>
                        <p class="text-xs text-gray-400 mb-3">{{ $current['hour'] }}</p>

                        <div class="border-t border-black/5 pt-3">
                            @if($wind['available'])
                                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Viento</p>
                                <p class="text-sm font-semibold text-gray-700">💨 {{ $wind['direction'] }}</p>
                                <p class="text-sm text-gray-500 tabular-nums">{{ $wind['speed'] }} km/h</p>
                            @else
                                <p class="text-xs text-gray-400 italic">Viento no disponible</p>
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
                    <h2 class="font-bold text-gray-800 text-lg">Pronóstico</h2>
                    <a href="{{ $tide['chart_source'] ?? 'https://www.hidro.gov.ar/oceanografia/pronostico.asp' }}"
                       target="_blank" rel="noopener"
                       class="text-xs text-gray-400 hover:text-blue-500 transition-colors">
                        hidro.gov.ar ↗
                    </a>
                </div>

                <div class="space-y-6">
                    @foreach($grouped as $dayLabel => $events)
                        <div>
                            {{-- Day heading --}}
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-6 h-px bg-gray-200 inline-block"></span>
                                {{ $dayLabel }}
                                <span class="flex-1 h-px bg-gray-100 inline-block"></span>
                            </h3>

                            {{-- Event cards --}}
                            @php
                                $statusBorders = [
                                    'red'    => 'border-red-200    bg-red-50',
                                    'orange' => 'border-orange-200 bg-orange-50',
                                    'yellow' => 'border-yellow-200 bg-yellow-50',
                                    'green'  => 'border-green-200  bg-green-50',
                                    'gray'   => 'border-gray-200   bg-gray-50',
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
                                        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-xl bg-white/60">
                                            {{ $isPleamar ? '⬆️' : '⬇️' }}
                                        </div>

                                        {{-- Type + time --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                {{ ucfirst(mb_strtolower($event['type'])) }}
                                            </p>
                                            <p class="text-2xl font-black tabular-nums leading-tight text-gray-800">
                                                {{ $event['time'] }}
                                            </p>
                                        </div>

                                        {{-- Level + status badge --}}
                                        @if($event['level'])
                                            <div class="shrink-0 text-right space-y-1">
                                                <p class="text-xl font-bold tabular-nums {{ $statusLevels[$col] ?? 'text-gray-700' }}">
                                                    {{ $event['level'] }}<span class="text-xs font-normal text-gray-400 ml-0.5">m</span>
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
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-gray-800">Alturas horarias</h2>
                    <a href="https://www.hidro.gov.ar/oceanografia/alturashorarias.asp"
                       target="_blank" rel="noopener"
                       class="text-xs text-gray-400 hover:text-blue-500 transition-colors">
                        hidro.gov.ar ↗
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Hora</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Nivel (m)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @php
                                // Use the same entry the service resolved as "current" — exact match, no recomputation.
                                $currentHour = $tide['current']['hour'] ?? null;
                            @endphp
                            @foreach($tide['hourly'] as $row)
                                @php $isNow = ($row['hour'] === $currentHour); @endphp
                                <tr class="{{ $isNow ? 'bg-blue-50' : 'hover:bg-gray-50' }} transition-colors">
                                    <td class="px-5 py-2.5 tabular-nums {{ $isNow ? 'font-bold text-blue-700' : 'text-gray-600' }}">
                                        @if($isNow)
                                            <span title="Última medición" class="inline-flex items-center gap-1.5">
                                                <span class="text-blue-500">👉</span>
                                                <span>{{ $row['hour'] }}</span>
                                            </span>
                                        @else
                                            {{ $row['hour'] }}
                                        @endif
                                    </td>
                                    <td class="px-5 py-2.5 font-semibold tabular-nums {{ $isNow ? 'text-blue-700' : 'text-gray-700' }}">
                                        {{ $row['level'] }}
                                        @if($isNow)
                                            <span class="ml-2 text-xs font-normal text-blue-400">última medición</span>
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
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">Gráfico de marea &mdash; San Fernando</h2>
                <a href="{{ $tide['chart_source'] }}"
                   target="_blank" rel="noopener"
                   class="text-xs text-gray-400 hover:text-blue-500 transition-colors">
                    ina.gob.ar ↗
                </a>
            </div>
            <div class="p-4">
                <img src="{{ $tide['chart_image'] }}"
                     alt="Pronóstico de marea San Fernando — INA"
                     class="w-full h-auto rounded-xl"
                     loading="lazy"
                     onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden')">
                <p class="hidden text-sm text-gray-400 text-center py-6">
                    El gráfico no está disponible. <a href="{{ $tide['chart_source'] }}" target="_blank" class="text-blue-500 underline">Ver en INA ↗</a>
                </p>
                <p class="text-xs text-gray-400 text-center mt-3">
                    Fuente: Instituto Nacional del Agua (INA) &mdash;
                    <a href="{{ $tide['chart_source'] }}" target="_blank" rel="noopener" class="hover:underline">
                        ver en sitio original
                    </a>
                </p>
            </div>
        </div>

        {{-- ── SOURCES ── --}}
        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-6 py-5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">Fuentes oficiales</h3>
            <ul class="space-y-1.5 text-sm">
                <li>
                    <a href="https://www.hidro.gov.ar/oceanografia/pronostico.asp"
                       target="_blank" rel="noopener"
                       class="text-blue-600 hover:underline">
                        Pronóstico de mareas — Servicio de Hidrografía Naval (SHN)
                    </a>
                </li>
                <li>
                    <a href="https://www.hidro.gov.ar/oceanografia/alturashorarias.asp"
                       target="_blank" rel="noopener"
                       class="text-blue-600 hover:underline">
                        Alturas horarias — Servicio de Hidrografía Naval (SHN)
                    </a>
                </li>
                <li>
                    <a href="{{ $tide['chart_source'] }}"
                       target="_blank" rel="noopener"
                       class="text-blue-600 hover:underline">
                        Gráfico de marea — Instituto Nacional del Agua (INA)
                    </a>
                </li>
            </ul>
        </div>

    </div>{{-- /container --}}
</div>

</x-app-layout>
