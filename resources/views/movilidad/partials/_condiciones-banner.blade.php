@php
    $estado = $condiciones['estado'] ?? 'ok';
    $advertencias = $condiciones['advertencias'] ?? [];
    $nivelMarea = $condiciones['nivel_marea'] ?? null;
    $viento = $condiciones['viento_kmh'] ?? null;
    $tideRaw = $condiciones['tide_raw'] ?? [];
    $weatherRaw = $condiciones['weather_raw'] ?? [];
@endphp

<div class="rounded-2xl border overflow-hidden
    @if ($estado === 'ok') border-gray-100 dark:border-gray-800
    @elseif ($estado === 'precaucion') border-amber-100 dark:border-amber-800/50
    @else border-red-100 dark:border-red-800/50
    @endif">

    {{-- Fila de condiciones --}}
    <div class="px-4 py-3 flex flex-wrap items-center gap-4
        @if ($estado === 'ok') bg-gray-50 dark:bg-gray-800/40
        @elseif ($estado === 'precaucion') bg-amber-50/60 dark:bg-amber-900/10
        @else bg-red-50/60 dark:bg-red-900/10
        @endif">

        {{-- Marea --}}
        @if ($nivelMarea !== null)
            <div class="flex items-center gap-1.5">
                <span class="text-base leading-none">🌊</span>
                <div>
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        {{ is_numeric($nivelMarea) ? number_format((float)$nivelMarea, 2) : $nivelMarea }} m
                    </span>
                    @if (!empty($tideRaw['trend']))
                        <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">
                            @if ($tideRaw['trend'] === 'rising') ↑
                            @elseif ($tideRaw['trend'] === 'falling') ↓
                            @else →
                            @endif
                        </span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Viento --}}
        @if ($viento !== null)
            <div class="flex items-center gap-1.5">
                <span class="text-base leading-none">💨</span>
                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    {{ $viento }} km/h
                    @if (!empty($tideRaw['wind']['direction']))
                        <span class="font-normal text-gray-400 dark:text-gray-500">{{ $tideRaw['wind']['direction'] }}</span>
                    @endif
                </span>
            </div>
        @endif

        {{-- Temperatura --}}
        @if (!empty($weatherRaw['temperature']))
            <div class="flex items-center gap-1.5">
                <span class="text-base leading-none">{{ $weatherRaw['emoji'] ?? '🌤' }}</span>
                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    {{ $weatherRaw['temperature'] }}°C
                </span>
            </div>
        @endif

        {{-- Estado de navegación --}}
        <div class="ml-auto">
            @if ($estado === 'ok')
                <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                    {{ __('movilidad.condiciones_ok') }}
                </span>
            @elseif ($estado === 'precaucion')
                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    {{ __('movilidad.condiciones_precaucion') }}
                </span>
            @else
                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                    {{ __('movilidad.condiciones_riesgo') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Advertencias detalladas --}}
    @if (!empty($advertencias))
        <div class="px-4 py-2.5 border-t border-amber-100 dark:border-amber-800/40 bg-amber-50/40 dark:bg-amber-900/5">
            @foreach ($advertencias as $advertencia)
                <p class="text-xs text-amber-700 dark:text-amber-400 flex items-start gap-1.5">
                    <span class="mt-px">⚠️</span>
                    <span>{{ $advertencia }}</span>
                </p>
            @endforeach
        </div>
    @endif
</div>
