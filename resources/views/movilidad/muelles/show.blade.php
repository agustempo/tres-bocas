<x-app-layout>

<div class="bg-white min-h-screen dark:bg-gray-900">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 space-y-5">

        {{-- ── BREADCRUMB ── --}}
        <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
            <a href="{{ route('movilidad.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
                {{ __('movilidad.muelles') }}
            </a>
            <span>/</span>
            <span class="text-gray-600 dark:text-gray-300">{{ $muelle->nombre }}</span>
        </div>

        {{-- ── HEADER DEL MUELLE ── --}}
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                🛥 {{ $muelle->nombre }}
            </h1>
            @if ($muelle->rio || $muelle->zona)
                <p class="mt-0.5 text-sm text-gray-400 dark:text-gray-500">
                    {{ implode(' · ', array_filter([$muelle->rio, $muelle->zona])) }}
                </p>
            @endif
        </div>

        {{-- ── CONDICIONES AMBIENTALES ── --}}
        @include('movilidad.partials._condiciones-banner', ['condiciones' => $condicionesActuales])

        {{-- ── SERVICIOS ── --}}
        @if (count($resumen) > 0)
            <div class="space-y-4">
                @foreach ($resumen as $item)
                    @php
                        $servicio       = $item['servicio'];
                        $ultimoAvistaje = $item['ultimo_avistaje'];
                        $freshness      = $item['freshness'];
                        $alertas        = $item['alertas_activas'];
                    @endphp

                    <div x-data="{
                             open: false, tipo: '', sentido: '', horaRef: '',
                             diaTab: '{{ $item['tipo_dia_hoy'] }}', patronId: '',
                             votosOk: 0, votosMal: 0, hace: '0', mostrarCuando: false,
                             abrirPill(s, h, pid, ok, mal, hh, mm) {
                                 this.sentido = s; this.horaRef = h; this.patronId = String(pid);
                                 this.votosOk = ok; this.votosMal = mal; this.hace = '0';
                                 let p = new Date(); p.setHours(hh, mm, 0, 0);
                                 this.mostrarCuando = Math.abs(new Date() - p) / 60000 <= 60;
                                 this.open = true;
                             },
                             cerrar() {
                                 this.open = false; this.tipo = ''; this.horaRef = ''; this.sentido = '';
                                 this.patronId = ''; this.votosOk = 0; this.votosMal = 0;
                                 this.hace = '0'; this.mostrarCuando = false;
                             }
                         }"
                         @click.outside="cerrar()"
                         class="rounded-2xl border border-gray-100 dark:border-gray-800
                                bg-white dark:bg-gray-800/50 overflow-hidden">

                        {{-- Alerta activa (si existe) --}}
                        @if ($alertas->isNotEmpty())
                            @foreach ($alertas as $alerta)
                                <div class="px-4 py-2.5 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800
                                            flex items-center gap-2 text-sm text-amber-800 dark:text-amber-300">
                                    <span>⚠️</span>
                                    <span class="font-medium">{{ $alerta->tipoLabel() }}:</span>
                                    <span>{{ $alerta->descripcion }}</span>
                                </div>
                            @endforeach
                        @endif

                        {{-- Header del servicio --}}
                        <div class="px-4 pt-4 pb-3 flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-sm text-gray-900 dark:text-gray-100 uppercase tracking-wide">
                                    {{ $servicio->nombre }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ $servicio->tipoLabel() }}
                                    @if ($servicio->verificado)
                                        · <span class="text-teal-600 dark:text-teal-400">{{ __('movilidad.operador_verificado') }}</span>
                                    @endif
                                </p>
                            </div>
                            @if ($servicio->contacto)
                                <a href="tel:{{ $servicio->contacto }}"
                                   class="shrink-0 text-xs text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1">
                                    📞 {{ $servicio->contacto }}
                                </a>
                            @endif
                        </div>

                        {{-- Estado actual --}}
                        <div class="px-4 pb-3">
                            @if ($ultimoAvistaje && $freshness['es_actual'])
                                {{-- Hay avistaje fresco --}}
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 text-base leading-none">
                                        @if ($ultimoAvistaje->tipo === 'paso')     ✅
                                        @elseif ($ultimoAvistaje->tipo === 'embarco')  🛥
                                        @elseif ($ultimoAvistaje->tipo === 'no_paro')  →
                                        @elseif ($ultimoAvistaje->tipo === 'cancelado') ✗
                                        @elseif ($ultimoAvistaje->tipo === 'demorado')  ⏱
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                            {{ $ultimoAvistaje->tipoLabel() }}
                                            @if ($ultimoAvistaje->sentido)
                                                <span class="text-gray-400 font-normal">
                                                    ({{ $ultimoAvistaje->sentido === 'ida' ? __('movilidad.sentido_ida') : __('movilidad.sentido_vuelta') }})
                                                </span>
                                            @endif
                                        </p>
                                        <p class="text-xs mt-0.5
                                            @if ($freshness['color'] === 'green')  text-green-600 dark:text-green-400
                                            @elseif ($freshness['color'] === 'yellow') text-yellow-600 dark:text-yellow-400
                                            @else text-orange-500 dark:text-orange-400
                                            @endif">
                                            {{ __('movilidad.avistaje_ultimo') }}: {{ $freshness['label'] }}
                                            @if ($ultimoAvistaje->confirmaciones > 0)
                                                · {{ trans_choice('movilidad.avistaje_confirmados_por', $ultimoAvistaje->confirmaciones, ['count' => $ultimoAvistaje->confirmaciones]) }}
                                            @endif
                                        </p>
                                        @if ($ultimoAvistaje->notas)
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 italic">
                                                "{{ $ultimoAvistaje->notas }}"
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                {{-- ¿Confirmar avistaje? (solo si no es propio y no lo confirmó) --}}
                                @auth
                                    @if ($ultimoAvistaje->user_id !== auth()->id())
                                        <form method="POST"
                                              action="{{ route('movilidad.avistajes.confirmar', $ultimoAvistaje->id) }}"
                                              class="mt-2">
                                            @csrf
                                            <button type="submit"
                                                    class="text-xs text-teal-600 dark:text-teal-400 hover:underline">
                                                {{ __('movilidad.avistaje_confirmar') }}
                                            </button>
                                        </form>
                                    @endif
                                @endauth

                            @endif

                            {{-- Avistaje en muelle adyacente de la ruta --}}
                            @php
                                $avAdj       = $item['avistaje_adyacente'];
                                $ordenAct    = $item['orden_actual'];
                                $ordenesRuta = $item['ordenes_en_ruta'];
                            @endphp
                            @if ($avAdj)
                                @php
                                    $ordenAdj  = $ordenesRuta->get($avAdj->muelle_id);
                                    $minAgo    = (int) $avAdj->hora_evento->diffInMinutes(now());
                                    $vieneHaciaAca = ($avAdj->sentido === 'ida'    && $ordenAdj < $ordenAct)
                                                  || ($avAdj->sentido === 'vuelta' && $ordenAdj > $ordenAct);
                                @endphp
                                <div class="flex items-start gap-2 text-xs text-gray-400 dark:text-gray-500 mt-2
                                            @if($vieneHaciaAca) bg-teal-50/60 dark:bg-teal-900/10 rounded-lg px-2.5 py-2 @endif">
                                    <span class="text-sm leading-none shrink-0">📍</span>
                                    <span class="leading-snug">
                                        <span class="font-medium text-gray-600 dark:text-gray-300">{{ $avAdj->muelle->nombre }}</span>
                                        · {{ $avAdj->tipoLabel() }}
                                        @if ($avAdj->sentido === 'ida') · → interior
                                        @elseif ($avAdj->sentido === 'vuelta') · ← Tigre
                                        @endif
                                        · hace {{ $minAgo }} min
                                        @if ($vieneHaciaAca)
                                            <span class="text-teal-600 dark:text-teal-400 font-medium"> · viene hacia acá</span>
                                        @endif
                                    </span>
                                </div>
                            @endif

                            {{-- Grilla de horarios por día --}}
                            @php
                                $ahora        = now();
                                $corte        = $ahora->copy()->subMinutes(5);
                                $esTigre      = $item['es_tigre'];
                                $tipoDiaHoy   = $item['tipo_dia_hoy'];
                                $porTipo      = $item['patrones_por_tipo'];
                                $tigrePorTipo = $item['tigre_por_tipo'];

                                $tiposConDatos = array_filter([
                                    'lv'      => ($porTipo['lv'] ?? collect())->isNotEmpty(),
                                    'sabado'  => ($porTipo['sabado'] ?? collect())->isNotEmpty(),
                                    'domingo' => ($porTipo['domingo'] ?? collect())->isNotEmpty(),
                                ]);
                                $anySchedule     = count($tiposConDatos) > 0;
                                $hasMultipleTabs = count($tiposConDatos) > 1;

                                // Próximo para hoy
                                $idaLocalHoy = ($porTipo[$tipoDiaHoy] ?? collect())->where('sentido', 'ida')->sortBy('hora_referencia')->values();
                                $tigreHoy    = ($tigrePorTipo[$tipoDiaHoy] ?? collect())->values();
                                if ($esTigre) { $tigreHoy = $idaLocalHoy; }
                                $vueltaHoy   = ($porTipo[$tipoDiaHoy] ?? collect())->where('sentido', 'vuelta')->sortBy('hora_referencia')->values();

                                $proxVuelta   = $vueltaHoy->first(fn($p) => \Carbon\Carbon::today()->setTimeFromTimeString($p->hora_referencia)->gt($corte));
                                $proxIdaLocal = $idaLocalHoy->first(fn($p) => \Carbon\Carbon::today()->setTimeFromTimeString($p->hora_referencia)->gt($corte));
                                $proxIdaIdx   = $proxIdaLocal ? $idaLocalHoy->search(fn($p) => $p->id === $proxIdaLocal->id) : null;
                                $proxTigreId  = ($proxIdaIdx !== null && $proxIdaIdx !== false && $tigreHoy->has($proxIdaIdx))
                                                ? $tigreHoy->get($proxIdaIdx)->id : null;
                                if ($esTigre) { $proxTigreId = $proxIdaLocal?->id; }
                            @endphp

                            @if ($anySchedule)
                                <div class="mt-3">

                                    {{-- Tabs de día (solo si hay más de un tipo con datos) --}}
                                    @if ($hasMultipleTabs)
                                        <div class="flex gap-1.5 mb-3">
                                            @foreach (['lv' => 'L–V', 'sabado' => 'Sáb', 'domingo' => 'Dom'] as $dType => $dLabel)
                                                @if (!empty($tiposConDatos[$dType]))
                                                    <button type="button"
                                                            @click="diaTab = '{{ $dType }}'"
                                                            :class="diaTab === '{{ $dType }}'
                                                                ? 'bg-teal-500 text-white'
                                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                                            class="px-3 py-1 rounded-full text-xs font-semibold transition-colors">
                                                        {{ $dLabel }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Grid por tipo de día --}}
                                    @foreach (['lv' => 'L–V', 'sabado' => 'Sáb', 'domingo' => 'Dom'] as $dType => $dLabel)
                                        @if (!empty($tiposConDatos[$dType]))
                                            @php
                                                $vD      = ($porTipo[$dType] ?? collect())->where('sentido', 'vuelta')->sortBy('hora_referencia')->values();
                                                $iD      = ($porTipo[$dType] ?? collect())->where('sentido', 'ida')->sortBy('hora_referencia')->values();
                                                $tD      = ($tigrePorTipo[$dType] ?? collect())->values();
                                                if ($esTigre) { $tD = $iD; }
                                                $isToday = ($tipoDiaHoy === $dType);
                                            @endphp
                                            <div x-show="diaTab === '{{ $dType }}'" class="space-y-3">

                                                {{-- ← A TIGRE (vuelta) --}}
                                                @if ($vD->isNotEmpty() && !$esTigre)
                                                    <div>
                                                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5">
                                                            {{ __('movilidad.sentido_a_tigre') }}
                                                        </p>
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @foreach ($vD as $patron)
                                                                @php
                                                                    $hp        = \Carbon\Carbon::today()->setTimeFromTimeString($patron->hora_referencia);
                                                                    $esPasado  = $isToday && $hp->lt($corte);
                                                                    $esProximo = $isToday && ($proxVuelta?->id === $patron->id);
                                                                    $votosOk   = $patron->votos_ok ?? 0;
                                                                    $votosMal  = $patron->votos_mal ?? 0;
                                                                @endphp
                                                                @if ($esProximo)
                                                                    <button type="button"
                                                                            @click="abrirPill('vuelta', '{{ $hp->format('H:i') }}', {{ $patron->id }}, {{ $votosOk }}, {{ $votosMal }}, {{ $hp->hour }}, {{ $hp->minute }})"
                                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-mono font-semibold
                                                                                   bg-teal-500 dark:bg-teal-600 text-white hover:bg-teal-600 transition-colors">
                                                                        <x-movilidad.confidence-badge :fuente="$patron->fuente" :votosOk="$votosOk" :votosMal="$votosMal" size="xs" />
                                                                        {{ $hp->format('H:i') }}
                                                                        @if ($votosOk > 0)<span class="opacity-75 text-[9px] tabular-nums">✓{{ $votosOk }}</span>@endif
                                                                        <span class="text-base leading-none">🙋</span>
                                                                    </button>
                                                                @elseif ($esPasado)
                                                                    <button type="button"
                                                                            @click="abrirPill('vuelta', '{{ $hp->format('H:i') }}', {{ $patron->id }}, {{ $votosOk }}, {{ $votosMal }}, {{ $hp->hour }}, {{ $hp->minute }})"
                                                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-mono
                                                                                   text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 transition-colors">
                                                                        {{ $hp->format('H:i') }}
                                                                        @if ($votosOk > 0)<span class="text-[9px] tabular-nums text-green-400/60">✓{{ $votosOk }}</span>@endif
                                                                    </button>
                                                                @else
                                                                    <button type="button"
                                                                            @click="abrirPill('vuelta', '{{ $hp->format('H:i') }}', {{ $patron->id }}, {{ $votosOk }}, {{ $votosMal }}, {{ $hp->hour }}, {{ $hp->minute }})"
                                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-mono font-medium
                                                                                   bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300
                                                                                   hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                                                        <x-movilidad.confidence-badge :fuente="$patron->fuente" :votosOk="$votosOk" :votosMal="$votosMal" size="xs" />
                                                                        {{ $hp->format('H:i') }}
                                                                        @if ($votosOk > 0)<span class="text-[9px] tabular-nums text-green-500">✓{{ $votosOk }}</span>@endif
                                                                        @if ($votosMal > 0 && $votosOk === 0)<span class="text-[9px] tabular-nums text-red-400">✗{{ $votosMal }}</span>@endif
                                                                    </button>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- → DESDE TIGRE (salida desde Tigre) --}}
                                                @if ($tD->isNotEmpty())
                                                    <div>
                                                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5">
                                                            {{ $esTigre ? __('movilidad.salidas_desde_aca') : __('movilidad.desde_tigre') }}
                                                        </p>
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @foreach ($tD as $tigreIdx => $patron)
                                                                @php
                                                                    $hp            = \Carbon\Carbon::today()->setTimeFromTimeString($patron->hora_referencia);
                                                                    $localP        = $iD->get($tigreIdx);
                                                                    $esPasado      = $isToday && ($localP
                                                                        ? \Carbon\Carbon::today()->setTimeFromTimeString($localP->hora_referencia)->lt($corte)
                                                                        : $hp->lt($corte));
                                                                    $esProximo     = $isToday && ($patron->id === $proxTigreId);
                                                                    $localPatronId = $localP?->id ?? '';
                                                                    $votosOk       = $localP?->votos_ok ?? 0;
                                                                    $votosMal      = $localP?->votos_mal ?? 0;
                                                                @endphp
                                                                @php $localFuente = $localP?->fuente ?? 'estimado'; @endphp
                                                                @if ($esProximo)
                                                                    <button type="button"
                                                                            @click="abrirPill('ida', '{{ $hp->format('H:i') }}', '{{ $localPatronId }}', {{ $votosOk }}, {{ $votosMal }}, {{ $hp->hour }}, {{ $hp->minute }})"
                                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-mono font-semibold
                                                                                   bg-teal-500 dark:bg-teal-600 text-white hover:bg-teal-600 transition-colors">
                                                                        <x-movilidad.confidence-badge :fuente="$localFuente" :votosOk="$votosOk" :votosMal="$votosMal" size="xs" />
                                                                        {{ $hp->format('H:i') }}
                                                                        @if ($votosOk > 0)<span class="opacity-75 text-[9px] tabular-nums">✓{{ $votosOk }}</span>@endif
                                                                        <span class="text-base leading-none">🙋</span>
                                                                    </button>
                                                                @elseif ($esPasado)
                                                                    <button type="button"
                                                                            @click="abrirPill('ida', '{{ $hp->format('H:i') }}', '{{ $localPatronId }}', {{ $votosOk }}, {{ $votosMal }}, {{ $hp->hour }}, {{ $hp->minute }})"
                                                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-mono
                                                                                   text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 transition-colors">
                                                                        {{ $hp->format('H:i') }}
                                                                        @if ($votosOk > 0)<span class="text-[9px] tabular-nums text-green-400/60">✓{{ $votosOk }}</span>@endif
                                                                    </button>
                                                                @else
                                                                    <button type="button"
                                                                            @click="abrirPill('ida', '{{ $hp->format('H:i') }}', '{{ $localPatronId }}', {{ $votosOk }}, {{ $votosMal }}, {{ $hp->hour }}, {{ $hp->minute }})"
                                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-mono font-medium
                                                                                   bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300
                                                                                   hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                                                        <x-movilidad.confidence-badge :fuente="$localFuente" :votosOk="$votosOk" :votosMal="$votosMal" size="xs" />
                                                                        {{ $hp->format('H:i') }}
                                                                        @if ($votosOk > 0)<span class="text-[9px] tabular-nums text-green-500">✓{{ $votosOk }}</span>@endif
                                                                        @if ($votosMal > 0 && $votosOk === 0)<span class="text-[9px] tabular-nums text-red-400">✗{{ $votosMal }}</span>@endif
                                                                    </button>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                            </div>
                                        @endif
                                    @endforeach

                                </div>
                            @endif
                        </div>

                        {{-- Botones de reporte --}}
                        <div class="px-4 pb-4 pt-1 border-t border-gray-50 dark:border-gray-700/50">
                            @auth
                                <div class="space-y-2">
                                    {{-- Panel de reporte --}}
                                    <div x-show="open" x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         class="rounded-xl border border-teal-100 dark:border-teal-800/50
                                                bg-teal-50/50 dark:bg-teal-900/10 p-4 space-y-3">

                                        {{-- Contexto del horario pre-seleccionado desde el pill --}}
                                        <div x-show="horaRef"
                                             class="flex items-center gap-2 text-sm rounded-xl px-3 py-2.5
                                                    bg-teal-500/10 dark:bg-teal-500/15 text-teal-700 dark:text-teal-300 font-medium">
                                            <span x-show="sentido === 'vuelta'" class="text-lg leading-none">🛶</span>
                                            <span x-show="sentido === 'ida'" class="text-lg leading-none">🛥</span>
                                            <span x-show="!sentido" class="text-lg leading-none">🛥</span>
                                            <span>
                                                <span x-show="sentido === 'vuelta'">← A Tigre · </span>
                                                <span x-show="sentido === 'ida'">Desde Tigre · </span>
                                                <span class="font-mono" x-text="horaRef"></span>
                                            </span>
                                            <button type="button"
                                                    @click="cerrar()"
                                                    class="ml-auto text-teal-400 hover:text-teal-600 transition-colors text-base leading-none">✕</button>
                                        </div>

                                        {{-- Rating del horario --}}
                                        <div x-show="votosOk + votosMal > 0"
                                             class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400
                                                    bg-white/60 dark:bg-gray-800/40 rounded-lg px-3 py-2">
                                            <span x-text="(() => { let t=votosOk+votosMal, r=votosOk/t; return r>=0.9?'⭐⭐⭐⭐⭐':r>=0.7?'⭐⭐⭐⭐':r>=0.5?'⭐⭐⭐':r>=0.3?'⭐⭐':'⭐' })()"></span>
                                            <span x-text="(votosOk+votosMal)+' reportes'"></span>
                                        </div>

                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('movilidad.avistaje_que_observaste') }}
                                        </p>

                                        <form method="POST" action="{{ route('movilidad.avistajes.store') }}">
                                            @csrf
                                            <input type="hidden" name="muelle_id" value="{{ $muelle->id }}">
                                            <input type="hidden" name="servicio_id" value="{{ $servicio->id }}">
                                            <input type="hidden" name="patron_id" x-model="patronId">
                                            <input type="hidden" name="hora_exacta" x-model="horaRef">
                                            <input type="hidden" name="hace_minutos" x-model="hace">
                                            <input type="hidden" name="tipo" x-model="tipo">
                                            <input type="hidden" name="sentido" x-model="sentido">

                                            {{-- Tipo de avistaje --}}
                                            <div class="grid grid-cols-2 gap-2">
                                                @foreach ([
                                                    'paso'      => ['label' => 'avistaje_paso',      'emoji' => '✅'],
                                                    'embarco'   => ['label' => 'avistaje_embarco',   'emoji' => '🛥'],
                                                    'no_paro'   => ['label' => 'avistaje_no_paro',   'emoji' => '→'],
                                                    'cancelado' => ['label' => 'avistaje_cancelado', 'emoji' => '✗'],
                                                ] as $tipoVal => $tipoData)
                                                    <button type="button"
                                                            @click="tipo = '{{ $tipoVal }}'"
                                                            :class="tipo === '{{ $tipoVal }}'
                                                                ? 'border-teal-400 bg-teal-50 dark:bg-teal-800/30 text-teal-700 dark:text-teal-300'
                                                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-teal-300'"
                                                            class="flex items-center gap-2 px-3 py-2.5 rounded-lg border text-sm font-medium transition-all text-left">
                                                        <span>{{ $tipoData['emoji'] }}</span>
                                                        <span>{{ __('movilidad.' . $tipoData['label']) }}</span>
                                                    </button>
                                                @endforeach
                                            </div>

                                            {{-- Sentido (solo si no viene prefijado del pill) --}}
                                            <div class="mt-3" x-show="!horaRef">
                                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                    {{ __('movilidad.avistaje_sentido') }}
                                                </label>
                                                <select x-model="sentido"
                                                        class="w-full text-sm px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700
                                                               bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                                               focus:outline-none focus:ring-2 focus:ring-teal-400">
                                                    <option value="">—</option>
                                                    <option value="ida">{{ __('movilidad.sentido_ida') }}</option>
                                                    <option value="vuelta">{{ __('movilidad.sentido_vuelta') }}</option>
                                                </select>
                                            </div>

                                            {{-- ¿Cuándo? (solo si el horario está dentro de ±1 hora del momento actual) --}}
                                            <div class="mt-3" x-show="mostrarCuando">
                                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                    {{ __('movilidad.avistaje_cuando') }}
                                                </label>
                                                <select x-model="hace"
                                                        class="w-full text-sm px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700
                                                               bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                                               focus:outline-none focus:ring-2 focus:ring-teal-400">
                                                    <option value="0">{{ __('movilidad.avistaje_ahora') }}</option>
                                                    <option value="5">Hace 5 min</option>
                                                    <option value="10">Hace 10 min</option>
                                                    <option value="15">Hace 15 min</option>
                                                    <option value="20">Hace 20 min</option>
                                                    <option value="30">Hace 30 min</option>
                                                    <option value="45">Hace 45 min</option>
                                                    <option value="60">Hace ~1 hora</option>
                                                </select>
                                            </div>

                                            {{-- Nota opcional --}}
                                            <div class="mt-3">
                                                <input type="text"
                                                       name="notas"
                                                       placeholder="{{ __('movilidad.avistaje_nota_opcional') }}"
                                                       maxlength="500"
                                                       class="w-full text-sm px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700
                                                              bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                                              placeholder-gray-400 dark:placeholder-gray-500
                                                              focus:outline-none focus:ring-2 focus:ring-teal-400">
                                            </div>

                                            {{-- Acciones --}}
                                            <div class="flex gap-2 mt-3">
                                                <button type="submit"
                                                        :disabled="!tipo"
                                                        :class="tipo ? 'bg-teal-600 hover:bg-teal-700 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed'"
                                                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                                                    {{ __('movilidad.avistaje_reportar_accion') }}
                                                </button>
                                                <button type="button"
                                                        @click="cerrar()"
                                                        class="px-4 py-2.5 rounded-xl text-sm font-medium border border-gray-200 dark:border-gray-700
                                                               text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                    {{ __('movilidad.avistaje_cancelar') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('login') }}"
                                   class="text-sm text-gray-400 dark:text-gray-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
                                    {{ __('movilidad.login_para_reportar') }}
                                </a>
                            @endauth

                            {{-- Descargo de responsabilidad --}}
                            <p class="mt-3 text-[10px] text-gray-300 dark:text-gray-600 leading-snug">
                                {{ __('movilidad.disclaimer_horarios') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                <div class="text-3xl mb-2">🛥</div>
                <p class="text-sm">Sin servicios registrados para este muelle.</p>
            </div>
        @endif

        {{-- ── HISTORIAL RECIENTE ── --}}
        @if ($historial->isNotEmpty())
            <div x-data="{ open: false }">
                <button @click="open = !open"
                        class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    <span>📋</span>
                    {{ __('movilidad.ver_historial') }}
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="mt-3 divide-y divide-gray-50 dark:divide-gray-800 rounded-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                    @foreach ($historial as $avistaje)
                        <div class="flex items-start gap-3 px-4 py-3 bg-white dark:bg-gray-800/50">
                            <div class="text-sm leading-none mt-0.5 w-5 text-center">
                                @if ($avistaje->tipo === 'paso')     ✅
                                @elseif ($avistaje->tipo === 'embarco')  🛥
                                @elseif ($avistaje->tipo === 'no_paro')  →
                                @elseif ($avistaje->tipo === 'cancelado') ✗
                                @elseif ($avistaje->tipo === 'demorado')  ⏱
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $avistaje->servicio->nombre }} · {{ $avistaje->tipoLabel() }}
                                    @if ($avistaje->sentido)
                                        <span class="text-gray-400 text-xs">({{ $avistaje->sentido }})</span>
                                    @endif
                                </p>
                                @if ($avistaje->notas)
                                    <p class="text-xs text-gray-400 dark:text-gray-500 italic mt-0.5">"{{ $avistaje->notas }}"</p>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $avistaje->hora_evento->format('H:i') }}
                                </p>
                                @if ($avistaje->confirmaciones > 0)
                                    <p class="text-xs text-teal-500 dark:text-teal-400 mt-0.5">
                                        ✓ {{ $avistaje->confirmaciones }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50"
                 x-data="{ show: true }"
                 x-init="setTimeout(() => show = false, 3000)"
                 x-show="show"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2">
                <div class="bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium px-5 py-3 rounded-2xl shadow-xl">
                    ✓ {{ session('success') }}
                </div>
            </div>
        @endif

    </div>
</div>

</x-app-layout>
