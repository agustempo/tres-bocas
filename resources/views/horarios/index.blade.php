<x-app-layout>

<div class="bg-white min-h-screen dark:bg-gray-900">
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 space-y-4">

    {{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">
                @if($muelle)
                    {{ $muelle->nombre }}
                    @if($muelle->zona) · {{ $muelle->zona }} @endif
                    · {{ now()->isoFormat('dddd D MMM') }}
                @endif
            </p>
            <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                {{ __('movilidad.horarios_titulo') }}
            </h1>
        </div>
        @if($muelle)
        <a href="{{ route('movilidad.muelles.show', $muelle->slug) }}"
           class="text-xs text-gray-400 dark:text-gray-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
            {{ __('movilidad.ver_historial') }} →
        </a>
        @endif
    </div>

    {{-- ── NO MUELLE ───────────────────────────────────────────────────────── --}}
    @if(!$muelle)
        <div class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800/50 px-6 py-10 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('movilidad.sin_muelle_elegido') }}
            </p>
            <a href="{{ route('home') }}"
               class="mt-4 inline-block text-sm font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                {{ __('ui.dashboard') }} →
            </a>
        </div>
    @else

    {{-- ── BANNER AVISO ACTIVO ─────────────────────────────────────────────── --}}
    @if($avisoActivo)
    <div x-data="{ visible: true }" x-show="visible" x-cloak
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="rounded-2xl border border-amber-200 dark:border-amber-800/60
                bg-amber-50 dark:bg-amber-900/20 px-4 py-3.5">
        <div class="flex items-start gap-3">
            <span class="text-amber-500 dark:text-amber-400 text-sm mt-0.5 shrink-0">⚠</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                    {{ $avisoActivo->tipoLabel() }}
                    @if($avisoActivo->notas)
                        — <span class="font-normal">{{ $avisoActivo->notas }}</span>
                    @endif
                </p>
                <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                    {{ __('movilidad.avistaje_reportar') }}
                    {{ $avisoActivo->user?->name ?? '' }}
                    · {{ $avisoActivo->hora_evento->diffForHumans() }}
                    @if($avisoActivo->confirmaciones > 0)
                        · {{ trans_choice('movilidad.confirmaron_demora', $avisoActivo->confirmaciones, ['count' => $avisoActivo->confirmaciones]) }}
                    @endif
                </p>
            </div>
            <button @click="visible = false"
                    class="shrink-0 text-xs text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-200
                           font-medium transition-colors py-0.5">
                {{ __('movilidad.ya_lo_vi') }}
            </button>
        </div>
    </div>
    @endif

    {{-- Flash --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2500)"
         class="text-sm text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-900/20
                border border-teal-200 dark:border-teal-800 rounded-xl px-4 py-2.5">
        ✓ {{ session('success') }}
    </div>
    @endif

    @php
        $vueltaProximas  = $proximas->filter(fn($s) => $s['patron']->sentido === 'vuelta')->values();
        $idaProximas     = $proximas->filter(fn($s) => $s['patron']->sentido === 'ida')->values();
        $vueltaPasadas   = $pasadas->filter(fn($s) => $s['patron']->sentido === 'vuelta')->values();
        $idaPasadas      = $pasadas->filter(fn($s) => $s['patron']->sentido === 'ida')->values();
        $vueltaRecientes = $recientes->filter(fn($s) => $s['patron']->sentido === 'vuelta')->values();
        $idaRecientes    = $recientes->filter(fn($s) => $s['patron']->sentido === 'ida')->values();
    @endphp

    {{-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 1 — HACIA TIGRE (vuelta)  [prioridad]
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="space-y-2">
        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-1">
            {{ __('movilidad.salidas_hacia_tigre') }}
        </p>

        {{-- Pasadas vuelta (collapsible) --}}
        @if($vueltaPasadas->isNotEmpty())
        <div x-data="{ open: false }">
            <button @click="open = !open"
                    class="text-[11px] text-gray-400 dark:text-gray-500
                           hover:text-gray-600 dark:hover:text-gray-300 transition-colors px-1 py-0.5">
                <span x-show="!open">{{ trans_choice('movilidad.schedule_past_toggle_show', $vueltaPasadas->count(), ['count' => $vueltaPasadas->count()]) }}</span>
                <span x-show="open" x-cloak>{{ __('movilidad.schedule_past_toggle_hide') }}</span>
            </button>
            <div x-show="open" x-cloak class="space-y-2 mt-1">
                @foreach($vueltaPasadas as $s)
                <div class="rounded-2xl border border-gray-100 dark:border-gray-800
                            bg-white dark:bg-gray-800/30 px-4 py-3.5 opacity-50">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-baseline gap-2 min-w-0">
                            <span class="text-xl font-black font-mono tabular-nums text-gray-400 dark:text-gray-600">
                                {{ $s['hora']->format('H:i') }}
                            </span>
                            <span class="text-xs text-gray-300 dark:text-gray-600">
                                {{ __('movilidad.tolerancia_label', ['min' => $s['patron']->ventana_min]) }}
                            </span>
                        </div>
                        <div class="text-right min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-600 truncate">
                                {{ $s['patron']->servicio?->nombre }}
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-gray-300 dark:text-gray-600 font-medium">
                            {{ __('movilidad.ya_salio') }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recientes vuelta (0–20 min window) --}}
        @foreach($vueltaRecientes as $s)
            @include('horarios._salida-card', $s)
        @endforeach

        {{-- Próximas vuelta --}}
        @foreach($vueltaProximas as $s)
            @include('horarios._salida-card', $s)
        @endforeach

        {{-- Empty state --}}
        @if($vueltaProximas->isEmpty() && $vueltaRecientes->isEmpty() && $vueltaPasadas->isEmpty())
        <div class="rounded-2xl border border-gray-100 dark:border-gray-800
                    bg-white dark:bg-gray-800/30 px-5 py-4 text-center">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                {{ __('movilidad.sin_horarios_vuelta') }}
            </p>
        </div>
        @endif
    </div>

    {{-- ── HORARIOS COMUNITARIOS — VUELTA ─────────────────────────────────── --}}
    @if($comunidadVuelta->isNotEmpty())
    <div class="space-y-2">
        <div class="flex items-center gap-2 px-1">
            <p class="text-[10px] font-bold uppercase tracking-widest text-blue-400 dark:text-blue-600 whitespace-nowrap">
                {{ __('schedule.community_section_label') }}
            </p>
            <div class="flex-1 h-px bg-blue-100 dark:bg-blue-900/40"></div>
        </div>
        @foreach($comunidadVuelta as $s)
            @include('horarios._patron-comunidad-card', $s)
        @endforeach
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 2 — DESDE TIGRE (ida)
    ══════════════════════════════════════════════════════════════════════════ --}}
    @if($idaProximas->isNotEmpty() || $idaRecientes->isNotEmpty() || $idaPasadas->isNotEmpty())
    <div class="space-y-2">
        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-1">
            {{ __('movilidad.salidas_desde_tigre') }}
        </p>

        {{-- Pasadas ida (collapsible) --}}
        @if($idaPasadas->isNotEmpty())
        <div x-data="{ open: false }">
            <button @click="open = !open"
                    class="text-[11px] text-gray-400 dark:text-gray-500
                           hover:text-gray-600 dark:hover:text-gray-300 transition-colors px-1 py-0.5">
                <span x-show="!open">{{ trans_choice('movilidad.schedule_past_toggle_show', $idaPasadas->count(), ['count' => $idaPasadas->count()]) }}</span>
                <span x-show="open" x-cloak>{{ __('movilidad.schedule_past_toggle_hide') }}</span>
            </button>
            <div x-show="open" x-cloak class="space-y-2 mt-1">
                @foreach($idaPasadas as $s)
                @php $horaMostrar = $s['horaTigre'] ?? $s['hora']; @endphp
                <div class="rounded-2xl border border-gray-100 dark:border-gray-800
                            bg-white dark:bg-gray-800/30 px-4 py-3.5 opacity-50">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-baseline gap-2 min-w-0">
                            <span class="text-xl font-black font-mono tabular-nums text-gray-400 dark:text-gray-600">
                                {{ $horaMostrar->format('H:i') }}
                            </span>
                            <span class="text-xs text-gray-300 dark:text-gray-600">
                                {{ __('movilidad.tolerancia_label', ['min' => $s['patron']->ventana_min]) }}
                            </span>
                        </div>
                        <div class="text-right min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-600 truncate">
                                {{ $s['patron']->servicio?->nombre }}
                            </p>
                            @if($s['horaTigre'])
                            <p class="text-[10px] text-gray-300 dark:text-gray-700">
                                {{ __('movilidad.llega_muelle_aprox', ['hora' => $s['hora']->format('H:i')]) }}
                            </p>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs text-gray-300 dark:text-gray-600 font-medium">
                            {{ __('movilidad.ya_salio') }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recientes ida (0–20 min window) --}}
        @foreach($idaRecientes as $s)
            @include('horarios._salida-card', $s)
        @endforeach

        {{-- Próximas ida --}}
        @foreach($idaProximas as $s)
            @include('horarios._salida-card', $s)
        @endforeach
    </div>
    @endif

    {{-- ── HORARIOS COMUNITARIOS — IDA ────────────────────────────────────── --}}
    @if($comunidadIda->isNotEmpty())
    <div class="space-y-2">
        <div class="flex items-center gap-2 px-1">
            <p class="text-[10px] font-bold uppercase tracking-widest text-blue-400 dark:text-blue-600 whitespace-nowrap">
                {{ __('schedule.community_section_label') }} · {{ __('movilidad.salidas_desde_tigre') }}
            </p>
            <div class="flex-1 h-px bg-blue-100 dark:bg-blue-900/40"></div>
        </div>
        @foreach($comunidadIda as $s)
            @include('horarios._patron-comunidad-card', $s)
        @endforeach
    </div>
    @endif

    {{-- ── MAÑANA ───────────────────────────────────────────────────────────── --}}
    @if($patronesMana->isNotEmpty())
    @php
        $manaVuelta = $patronesMana->filter(fn($p) => $p->sentido === 'vuelta');
        $manaIda    = $patronesMana->filter(fn($p) => $p->sentido !== 'vuelta');
    @endphp
    <div class="space-y-2">
        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-1">
            {{ __('movilidad.manana_label') }}
        </p>
        {{-- Vuelta tomorrow --}}
        @foreach($manaVuelta as $patron)
        <div class="rounded-2xl border border-gray-100 dark:border-gray-800
                    bg-white dark:bg-gray-800/30 px-4 py-3.5 opacity-60">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-baseline gap-2 shrink-0">
                    <span class="font-black font-mono tabular-nums text-gray-600 dark:text-gray-400"
                          style="font-size:1.75rem;line-height:1">
                        {{ substr($patron->hora_referencia, 0, 5) }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-600">
                        {{ __('movilidad.tolerancia_label', ['min' => $patron->ventana_min]) }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 truncate">
                        {{ $patron->servicio?->nombre }}
                    </p>
                    <p class="text-[10px] text-gray-400 dark:text-gray-600">
                        {{ __('movilidad.sentido_a_tigre') }}
                    </p>
                </div>
                <span class="shrink-0 text-xs text-gray-400 dark:text-gray-600 font-medium">
                    {{ __('movilidad.manana_label') }}
                </span>
            </div>
        </div>
        @endforeach
        {{-- Ida tomorrow --}}
        @foreach($manaIda as $patron)
        @php
            // Look up Tigre departure for tomorrow
            $tigreList  = $tigreMana->get($patron->servicio_id) ?? collect();
            $tigreMatch = $tigreList
                ->filter(fn($t) => $t->hora_referencia <= $patron->hora_referencia)
                ->sortByDesc('hora_referencia')
                ->first();
            $horaTigreMana  = $tigreMatch
                ? \Carbon\Carbon::today()->addDay()->setTimeFromTimeString($tigreMatch->hora_referencia)
                : null;
            $horaMostrarMana = $horaTigreMana ?? \Carbon\Carbon::today()->addDay()->setTimeFromTimeString($patron->hora_referencia);
        @endphp
        <div class="rounded-2xl border border-gray-100 dark:border-gray-800
                    bg-white dark:bg-gray-800/30 px-4 py-3.5 opacity-60">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-baseline gap-2 shrink-0">
                    <span class="font-black font-mono tabular-nums text-gray-600 dark:text-gray-400"
                          style="font-size:1.75rem;line-height:1">
                        {{ $horaMostrarMana->format('H:i') }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-600">
                        {{ __('movilidad.tolerancia_label', ['min' => $patron->ventana_min]) }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 truncate">
                        {{ $patron->servicio?->nombre }}
                    </p>
                    @if($horaTigreMana)
                    <p class="text-[10px] text-gray-400 dark:text-gray-600">
                        {{ __('movilidad.llega_muelle_aprox', ['hora' => substr($patron->hora_referencia, 0, 5)]) }}
                    </p>
                    @endif
                </div>
                <span class="shrink-0 text-xs text-gray-400 dark:text-gray-600 font-medium">
                    {{ __('movilidad.manana_label') }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── AGREGAR HORARIO O MUELLE ────────────────────────────────────────── --}}
    <div x-data="{
            abierto: {{ session('community_added') ? 'false' : 'false' }},
            tab: 'horario',
            enviado: {{ session('community_added') ? 'true' : 'false' }},
            cargando: false,
            get tabHorario() { return this.tab === 'horario'; },
            get tabMuelle()  { return this.tab === 'muelle'; }
         }"
         class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700
                bg-transparent overflow-hidden">

        {{-- Toggle header --}}
        <button @click="abierto = !abierto; enviado = false"
                class="w-full flex items-center justify-between gap-3 px-4 py-3.5
                       text-left hover:bg-gray-50 dark:hover:bg-gray-800/20 transition-colors">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full
                             bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400
                             text-sm font-bold leading-none transition-transform"
                      :class="abierto ? 'rotate-45' : ''">+</span>
                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('schedule.create_toggle_label') }}
                    </p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500">
                        {{ __('schedule.create_toggle_sub') }}
                    </p>
                </div>
            </div>
        </button>

        {{-- Success flash --}}
        <div x-show="enviado" x-cloak
             class="px-4 pb-4 text-sm text-teal-700 dark:text-teal-300
                    bg-teal-50 dark:bg-teal-900/10 border-t border-teal-100 dark:border-teal-900/30
                    flex items-center gap-2 py-3">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            <span x-text="tabHorario ? '{{ __('schedule.success_schedule') }}' : '{{ __('schedule.success_dock') }}'"></span>
        </div>

        {{-- Form panel --}}
        <div x-show="abierto" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-gray-100 dark:border-gray-800">

            @auth
            {{-- Tabs --}}
            <div class="flex border-b border-gray-100 dark:border-gray-800">
                <button @click="tab = 'horario'"
                        :class="tabHorario
                            ? 'border-b-2 border-teal-500 text-teal-700 dark:text-teal-300 font-semibold'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="flex-1 py-2.5 text-sm transition-colors">
                    {{ __('schedule.create_tab_schedule') }}
                </button>
                <button @click="tab = 'muelle'"
                        :class="tabMuelle
                            ? 'border-b-2 border-teal-500 text-teal-700 dark:text-teal-300 font-semibold'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="flex-1 py-2.5 text-sm transition-colors">
                    {{ __('schedule.create_tab_dock') }}
                </button>
            </div>

            {{-- ── TAB: HORARIO ──────────────────────────────────────────── --}}
            <div x-show="tabHorario" class="px-4 py-4 space-y-3">
                <form method="POST" action="{{ route('horarios.comunidad.store') }}"
                      @submit.prevent="cargando = true; $el.submit()"
                      x-data="{ recurrencia: 'lv', sentido: 'vuelta' }">
                    @csrf

                    {{-- Hora + tolerancia --}}
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('schedule.field_hora') }}
                            </label>
                            <input type="time" name="hora_referencia" required
                                   class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700
                                          bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                          focus:outline-none focus:ring-2 focus:ring-teal-400 dark:focus:ring-teal-600">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('schedule.field_tolerancia') }}
                            </label>
                            <select name="ventana_min"
                                    class="px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700
                                           bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                           focus:outline-none focus:ring-2 focus:ring-teal-400 dark:focus:ring-teal-600">
                                <option value="15">{{ __('schedule.tolerancia_15') }}</option>
                                <option value="20" selected>{{ __('schedule.tolerancia_20') }}</option>
                                <option value="30">{{ __('schedule.tolerancia_30') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Sentido --}}
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('schedule.field_sentido') }}
                        </label>
                        <div class="flex gap-2">
                            <label class="flex-1 flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-colors"
                                   :class="sentido === 'vuelta'
                                       ? 'border-teal-300 dark:border-teal-700 bg-teal-50 dark:bg-teal-900/20'
                                       : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'">
                                <input type="radio" name="sentido" value="vuelta" x-model="sentido" class="sr-only">
                                <span class="text-sm text-gray-800 dark:text-gray-200">{{ __('schedule.sentido_vuelta') }}</span>
                            </label>
                            <label class="flex-1 flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-colors"
                                   :class="sentido === 'ida'
                                       ? 'border-teal-300 dark:border-teal-700 bg-teal-50 dark:bg-teal-900/20'
                                       : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'">
                                <input type="radio" name="sentido" value="ida" x-model="sentido" class="sr-only">
                                <span class="text-sm text-gray-800 dark:text-gray-200">{{ __('schedule.sentido_ida') }}</span>
                            </label>
                        </div>
                    </div>

                    {{-- Empresa --}}
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('schedule.field_empresa') }}
                        </label>
                        <select name="empresa"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700
                                       bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                       focus:outline-none focus:ring-2 focus:ring-teal-400 dark:focus:ring-teal-600">
                            <option value="">— {{ __('schedule.field_empresa') }} —</option>
                            <option value="Interisleña">Interisleña</option>
                            <option value="Lineas Delta">Lineas Delta</option>
                            <option value="Jilguero">Jilguero</option>
                        </select>
                    </div>

                    {{-- Días --}}
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('schedule.field_dias') }}
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach([
                                'lv'      => __('schedule.recurrencia_lv'),
                                'sabado'  => __('schedule.recurrencia_sabado'),
                                'domingo' => __('schedule.recurrencia_domingo'),
                                'unico'   => __('schedule.recurrencia_unico'),
                            ] as $val => $label)
                            <label class="flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-colors text-sm"
                                   :class="recurrencia === '{{ $val }}'
                                       ? 'border-teal-300 dark:border-teal-700 bg-teal-50 dark:bg-teal-900/20 text-gray-900 dark:text-gray-100'
                                       : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400'">
                                <input type="radio" name="recurrencia" value="{{ $val }}"
                                       x-model="recurrencia" class="sr-only">
                                {{ $label }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Fecha única (only for unico) --}}
                    <div x-show="recurrencia === 'unico'" x-cloak>
                        <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Fecha
                        </label>
                        <input type="date" name="fecha_unica"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700
                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                      focus:outline-none focus:ring-2 focus:ring-teal-400 dark:focus:ring-teal-600">
                    </div>

                    {{-- Hidden: muelle_id pre-filled --}}
                    <input type="hidden" name="muelle_id" value="{{ $muelle->id }}">

                    {{-- Validation errors --}}
                    @if($errors->has('muelle_id'))
                    <p class="text-xs text-red-500">{{ $errors->first('muelle_id') }}</p>
                    @endif

                    {{-- Submit --}}
                    <button type="submit"
                            :disabled="cargando"
                            :class="cargando ? 'opacity-60 cursor-default' : ''"
                            class="w-full py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500
                                   text-white text-sm font-semibold transition-colors">
                        <span x-show="!cargando">{{ __('schedule.btn_submit_schedule') }}</span>
                        <span x-show="cargando" x-cloak>…</span>
                    </button>
                </form>
            </div>

            {{-- ── TAB: MUELLE NUEVO ─────────────────────────────────────── --}}
            <div x-show="tabMuelle" x-cloak class="px-4 py-4 space-y-3">
                <form method="POST" action="{{ route('muelles.comunidad.store') }}"
                      @submit.prevent="cargando = true; $el.submit()">
                    @csrf

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('schedule.field_muelle_nombre') }}
                        </label>
                        <input type="text" name="nombre" required maxlength="100"
                               placeholder="{{ __('schedule.placeholder_nombre') }}"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700
                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                      placeholder-gray-400 dark:placeholder-gray-600
                                      focus:outline-none focus:ring-2 focus:ring-teal-400 dark:focus:ring-teal-600">
                    </div>

                    {{-- Zona --}}
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('schedule.field_muelle_zona') }}
                        </label>
                        <input type="text" name="zona" maxlength="100"
                               placeholder="{{ __('schedule.placeholder_zona') }}"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700
                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                      placeholder-gray-400 dark:placeholder-gray-600
                                      focus:outline-none focus:ring-2 focus:ring-teal-400 dark:focus:ring-teal-600">
                    </div>

                    {{-- Referencia --}}
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('schedule.field_muelle_referencia') }}
                        </label>
                        <input type="text" name="referencia" maxlength="255"
                               placeholder="{{ __('schedule.placeholder_referencia') }}"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700
                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                      placeholder-gray-400 dark:placeholder-gray-600
                                      focus:outline-none focus:ring-2 focus:ring-teal-400 dark:focus:ring-teal-600">
                        <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-600">
                            {{ __('schedule.field_muelle_ref_hint') }}
                        </p>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            :disabled="cargando"
                            :class="cargando ? 'opacity-60 cursor-default' : ''"
                            class="w-full py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500
                                   text-white text-sm font-semibold transition-colors">
                        <span x-show="!cargando">{{ __('schedule.btn_submit_dock') }}</span>
                        <span x-show="cargando" x-cloak>…</span>
                    </button>
                </form>
            </div>

            @else
            {{-- Guest: login prompt --}}
            <div class="px-4 py-5 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('schedule.create_login_prompt') }}
                </p>
            </div>
            @endauth

        </div>
    </div>

    {{-- Disclaimer --}}
    <p class="text-[10px] text-gray-300 dark:text-gray-600 leading-snug px-1">
        {{ __('movilidad.disclaimer_horarios') }}
    </p>

    @endif {{-- end if $muelle --}}

</div>
</div>

</x-app-layout>
