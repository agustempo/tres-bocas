<x-app-layout>
<script>
    window.__editorPatrones = @json($patronesPorServicio);
</script>
<div class="bg-white min-h-screen dark:bg-gray-900"
     x-data="scheduleEditor(
         {{ $servicios->first()?->id ?? 'null' }},
         window.__editorPatrones,
         {{ $muelle->id }},
         '{{ csrf_token() }}'
     )">

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 space-y-5">

    {{-- ── Breadcrumb ── --}}
    <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
        <a href="{{ route('admin.movilidad.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
            Panel Movilidad
        </a>
        <span>/</span>
        <span class="text-gray-700 dark:text-gray-300">{{ $muelle->nombre }}</span>
    </div>

    {{-- ── Header ── --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $muelle->nombre }}</h1>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">
                {{ implode(' · ', array_filter([$muelle->rio, $muelle->zona])) }}
            </p>
        </div>
        <a href="{{ route('movilidad.muelles.show', $muelle->slug) }}"
           target="_blank"
           class="text-xs text-teal-600 dark:text-teal-400 hover:underline shrink-0">
            Ver público →
        </a>
    </div>

    {{-- ── Selector de servicio ── --}}
    @if ($servicios->count() > 1)
        <div class="flex flex-wrap gap-2">
            @foreach ($servicios as $svc)
                <button type="button"
                        @click="activeServiceId = {{ $svc->id }}"
                        :class="activeServiceId === {{ $svc->id }}
                            ? 'bg-teal-500 text-white'
                            : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold transition-colors">
                    {{ $svc->nombre }}
                </button>
            @endforeach
        </div>
    @else
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            {{ $servicios->first()?->nombre ?? 'Sin servicio' }}
        </p>
    @endif

    {{-- ── Tabs de día ── --}}
    <div class="flex items-center gap-2">
        @foreach (['lv' => 'L–V', 'sabado' => 'Sáb', 'domingo' => 'Dom'] as $dt => $label)
            <button type="button"
                    @click="diaTab = '{{ $dt }}'"
                    :class="diaTab === '{{ $dt }}'
                        ? 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="px-4 py-1.5 rounded-full text-xs font-semibold transition-colors">
                {{ $label }}
            </button>
        @endforeach

        <button type="button"
                @click="validarBulk()"
                :disabled="saving"
                class="ml-auto flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                       bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400
                       border border-green-200 dark:border-green-800
                       hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors disabled:opacity-50">
            <span x-text="saving ? '...' : '✓ Marcar todos revisados'"></span>
        </button>
    </div>

    {{-- ── Grilla de horarios ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        {{-- Columna VUELTA (← A Tigre) --}}
        <div class="rounded-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-800">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                    ← A Tigre
                </p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                <template x-for="g in patronesParaDia('vuelta')" :key="g.id">
                    <div>
                        {{-- Fila principal --}}
                        <div class="flex items-center gap-2 px-3 py-2 group cursor-pointer
                                    hover:bg-gray-900/[0.04] dark:hover:bg-white/[0.04] transition-colors"
                             @click="toggleDetail(g)">

                            {{-- Hora + emoji de sentimiento --}}
                            <div class="flex-1 min-w-0 flex items-center gap-2">
                                <template x-if="editingId !== g.id">
                                    <span class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-200"
                                          x-text="g.hora"></span>
                                </template>
                                <template x-if="editingId === g.id">
                                    <input type="time"
                                           x-model="editHora"
                                           @click.stop
                                           @keydown.enter="saveEdit(g)"
                                           @keydown.escape="cancelEdit()"
                                           @blur="saveEdit(g)"
                                           class="font-mono text-sm font-semibold w-24 px-1 py-0.5 rounded
                                                  border border-teal-300 dark:border-teal-700
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  focus:outline-none focus:ring-1 focus:ring-teal-400">
                                </template>
                                {{-- Emoji de sentimiento (pre-cargado desde avistajes) --}}
                                <span class="text-xs leading-none"
                                      :title="sentiTitle(g)"
                                      x-text="sentiEmoji(g)"></span>
                                {{-- Lápiz (editar hora) --}}
                                <button type="button"
                                        @click.stop="startEdit(g)"
                                        class="opacity-0 group-hover:opacity-70 hover:!opacity-100
                                               text-[10px] text-gray-400 dark:text-gray-500 transition-opacity"
                                        title="Editar hora">✎</button>
                            </div>

                            {{-- Fuente badge --}}
                            <button type="button"
                                    @click.stop="cycleFuente(g)"
                                    class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-semibold transition-colors"
                                    :class="{
                                        'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': g.fuente === 'oficial',
                                        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': g.fuente === 'comunidad',
                                        'bg-gray-100 dark:bg-gray-700/80 text-gray-500 dark:text-gray-400': g.fuente === 'estimado',
                                    }"
                                    x-text="g.fuente === 'oficial' ? 'Of' : g.fuente === 'comunidad' ? 'Com' : 'Est'">
                            </button>

                            {{-- Validado --}}
                            <button type="button"
                                    @click.stop="validar(g)"
                                    class="shrink-0 text-[11px] font-bold transition-colors"
                                    :class="g.necesita_revision
                                        ? 'text-amber-400 hover:text-amber-300'
                                        : 'text-green-500 dark:text-green-400 hover:text-green-400'"
                                    :title="g.necesita_revision ? 'Necesita revisión — clic para validar' : 'Validado'"
                                    x-text="g.necesita_revision ? '!' : '✓'">
                            </button>

                            {{-- Visibilidad (semáforo) --}}
                            <button type="button"
                                    @click.stop="toggleVisibilidad(g)"
                                    :title="g.visibilidad === 'publico' ? 'Visible — clic para ocultar' : 'Oculto — clic para publicar'"
                                    class="shrink-0 w-6 h-6 flex items-center justify-center rounded-full border transition-colors
                                           hover:ring-2 hover:ring-offset-1 dark:hover:ring-offset-gray-900"
                                    :class="g.visibilidad === 'publico'
                                        ? 'border-green-400 dark:border-green-700 hover:ring-green-500'
                                        : 'border-red-400 dark:border-red-700 hover:ring-red-500'">
                                <span class="w-2.5 h-2.5 rounded-full transition-colors"
                                      :class="g.visibilidad === 'publico' ? 'bg-green-500 dark:bg-green-400' : 'bg-red-500'"></span>
                            </button>

                            {{-- Eliminar --}}
                            <button type="button"
                                    @click.stop="deleteGrupo(g)"
                                    class="shrink-0 text-[11px] text-gray-300 dark:text-gray-600
                                           hover:text-red-500 dark:hover:text-red-400
                                           opacity-20 group-hover:opacity-100 transition-all"
                                    title="Eliminar horario">✕</button>
                        </div>

                        {{-- Panel de reportes comunitarios (expandible) --}}
                        <div x-show="openDetailId === g.id"
                             x-cloak
                             @click.stop
                             class="px-3 pb-3 pt-2 border-t border-gray-100 dark:border-gray-800/60
                                    bg-gray-50/50 dark:bg-gray-800/20 space-y-2">

                            <div class="flex items-center justify-between">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                    Reportes de la comunidad
                                </p>
                                <button type="button" @click="openDetailId = null"
                                        class="text-[10px] text-gray-300 dark:text-gray-600 hover:text-gray-400 transition-colors">✕</button>
                            </div>

                            <template x-if="detailLoading">
                                <p class="text-xs text-gray-400 dark:text-gray-500 animate-pulse">Cargando reportes…</p>
                            </template>

                            <template x-if="!detailLoading && detailAvistajes.length === 0">
                                <p class="text-xs text-gray-300 dark:text-gray-600 italic">Sin reportes sobre este horario</p>
                            </template>

                            <template x-if="!detailLoading && detailAvistajes.length > 0">
                                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                                    <template x-for="a in detailAvistajes" :key="a.id">
                                        <div class="flex items-start gap-2 px-2.5 py-2 rounded-lg
                                                    bg-white dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/50">
                                            <span class="shrink-0 text-sm leading-none mt-0.5"
                                                  :class="{
                                                      'text-green-500': a.tipo === 'paso' || a.tipo === 'embarco',
                                                      'text-amber-500': a.tipo === 'demorado',
                                                      'text-red-500':   a.tipo === 'no_paro' || a.tipo === 'cancelado',
                                                  }"
                                                  x-text="a.tipo_icono"></span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300" x-text="a.tipo_label"></p>
                                                <template x-if="a.notas">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 italic mt-0.5" x-text="a.notas"></p>
                                                </template>
                                                <p class="text-[10px] text-gray-300 dark:text-gray-600 mt-0.5"
                                                   x-text="a.hora_label + (a.user_name ? ' · ' + a.user_name : '') + (a.confirmaciones > 0 ? ' · ' + a.confirmaciones + ' ✓' : '')"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Sin horarios --}}
                <template x-if="patronesParaDia('vuelta').length === 0">
                    <p class="px-4 py-3 text-xs text-gray-300 dark:text-gray-600 italic">Sin horarios cargados</p>
                </template>
            </div>

            {{-- Agregar --}}
            <div class="px-3 py-2 border-t border-gray-50 dark:border-gray-800/60">
                <template x-if="addingSentido !== 'vuelta'">
                    <button type="button"
                            @click="addingSentido = 'vuelta'; $nextTick(() => $refs.addHoraVuelta?.focus())"
                            class="text-xs text-teal-600 dark:text-teal-400 hover:underline">
                        + Agregar horario
                    </button>
                </template>
                <template x-if="addingSentido === 'vuelta'">
                    <div class="flex items-center gap-2">
                        <input type="time"
                               x-ref="addHoraVuelta"
                               x-model="addHora"
                               @keydown.enter="addPatron('vuelta')"
                               @keydown.escape="addingSentido = null; addHora = ''"
                               class="font-mono text-sm w-24 px-2 py-1 rounded border border-teal-300 dark:border-teal-700
                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                      focus:outline-none focus:ring-1 focus:ring-teal-400">
                        <button type="button" @click="addPatron('vuelta')"
                                class="text-xs px-2 py-1 rounded bg-teal-500 text-white hover:bg-teal-600 transition-colors">
                            Agregar
                        </button>
                        <button type="button" @click="addingSentido = null; addHora = ''"
                                class="text-xs text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Columna IDA (→ Desde Tigre) --}}
        <div class="rounded-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-800">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                    → Desde Tigre
                </p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                <template x-for="g in patronesParaDia('ida')" :key="g.id">
                    <div>
                        <div class="flex items-center gap-2 px-3 py-2 group cursor-pointer
                                    hover:bg-gray-900/[0.04] dark:hover:bg-white/[0.04] transition-colors"
                             @click="toggleDetail(g)">

                            {{-- Hora + emoji de sentimiento --}}
                            <div class="flex-1 min-w-0 flex items-center gap-2">
                                <template x-if="editingId !== g.id">
                                    <span class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-200"
                                          x-text="g.hora"></span>
                                </template>
                                <template x-if="editingId === g.id">
                                    <input type="time"
                                           x-model="editHora"
                                           @click.stop
                                           @keydown.enter="saveEdit(g)"
                                           @keydown.escape="cancelEdit()"
                                           @blur="saveEdit(g)"
                                           class="font-mono text-sm font-semibold w-24 px-1 py-0.5 rounded
                                                  border border-teal-300 dark:border-teal-700
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  focus:outline-none focus:ring-1 focus:ring-teal-400">
                                </template>
                                {{-- Emoji de sentimiento (pre-cargado desde avistajes) --}}
                                <span class="text-xs leading-none"
                                      :title="sentiTitle(g)"
                                      x-text="sentiEmoji(g)"></span>
                                {{-- Lápiz (editar hora) --}}
                                <button type="button"
                                        @click.stop="startEdit(g)"
                                        class="opacity-0 group-hover:opacity-70 hover:!opacity-100
                                               text-[10px] text-gray-400 dark:text-gray-500 transition-opacity"
                                        title="Editar hora">✎</button>
                            </div>

                            {{-- Fuente badge --}}
                            <button type="button"
                                    @click.stop="cycleFuente(g)"
                                    class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-semibold transition-colors"
                                    :class="{
                                        'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': g.fuente === 'oficial',
                                        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': g.fuente === 'comunidad',
                                        'bg-gray-100 dark:bg-gray-700/80 text-gray-500 dark:text-gray-400': g.fuente === 'estimado',
                                    }"
                                    x-text="g.fuente === 'oficial' ? 'Of' : g.fuente === 'comunidad' ? 'Com' : 'Est'">
                            </button>

                            {{-- Validado --}}
                            <button type="button"
                                    @click.stop="validar(g)"
                                    class="shrink-0 text-[11px] font-bold transition-colors"
                                    :class="g.necesita_revision
                                        ? 'text-amber-400 hover:text-amber-300'
                                        : 'text-green-500 dark:text-green-400 hover:text-green-400'"
                                    :title="g.necesita_revision ? 'Necesita revisión — clic para validar' : 'Validado'"
                                    x-text="g.necesita_revision ? '!' : '✓'">
                            </button>

                            {{-- Visibilidad (semáforo) --}}
                            <button type="button"
                                    @click.stop="toggleVisibilidad(g)"
                                    :title="g.visibilidad === 'publico' ? 'Visible — clic para ocultar' : 'Oculto — clic para publicar'"
                                    class="shrink-0 w-6 h-6 flex items-center justify-center rounded-full border transition-colors
                                           hover:ring-2 hover:ring-offset-1 dark:hover:ring-offset-gray-900"
                                    :class="g.visibilidad === 'publico'
                                        ? 'border-green-400 dark:border-green-700 hover:ring-green-500'
                                        : 'border-red-400 dark:border-red-700 hover:ring-red-500'">
                                <span class="w-2.5 h-2.5 rounded-full transition-colors"
                                      :class="g.visibilidad === 'publico' ? 'bg-green-500 dark:bg-green-400' : 'bg-red-500'"></span>
                            </button>

                            {{-- Eliminar --}}
                            <button type="button"
                                    @click.stop="deleteGrupo(g)"
                                    class="shrink-0 text-[11px] text-gray-300 dark:text-gray-600
                                           hover:text-red-500 dark:hover:text-red-400
                                           opacity-20 group-hover:opacity-100 transition-all"
                                    title="Eliminar horario">✕</button>
                        </div>

                        <div x-show="openDetailId === g.id"
                             x-cloak
                             @click.stop
                             class="px-3 pb-3 pt-2 border-t border-gray-100 dark:border-gray-800/60
                                    bg-gray-50/50 dark:bg-gray-800/20 space-y-2">

                            <div class="flex items-center justify-between">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                    Reportes de la comunidad
                                </p>
                                <button type="button" @click="openDetailId = null"
                                        class="text-[10px] text-gray-300 dark:text-gray-600 hover:text-gray-400 transition-colors">✕</button>
                            </div>

                            <template x-if="detailLoading">
                                <p class="text-xs text-gray-400 dark:text-gray-500 animate-pulse">Cargando reportes…</p>
                            </template>

                            <template x-if="!detailLoading && detailAvistajes.length === 0">
                                <p class="text-xs text-gray-300 dark:text-gray-600 italic">Sin reportes sobre este horario</p>
                            </template>

                            <template x-if="!detailLoading && detailAvistajes.length > 0">
                                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                                    <template x-for="a in detailAvistajes" :key="a.id">
                                        <div class="flex items-start gap-2 px-2.5 py-2 rounded-lg
                                                    bg-white dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/50">
                                            <span class="shrink-0 text-sm leading-none mt-0.5"
                                                  :class="{
                                                      'text-green-500': a.tipo === 'paso' || a.tipo === 'embarco',
                                                      'text-amber-500': a.tipo === 'demorado',
                                                      'text-red-500':   a.tipo === 'no_paro' || a.tipo === 'cancelado',
                                                  }"
                                                  x-text="a.tipo_icono"></span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300" x-text="a.tipo_label"></p>
                                                <template x-if="a.notas">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 italic mt-0.5" x-text="a.notas"></p>
                                                </template>
                                                <p class="text-[10px] text-gray-300 dark:text-gray-600 mt-0.5"
                                                   x-text="a.hora_label + (a.user_name ? ' · ' + a.user_name : '') + (a.confirmaciones > 0 ? ' · ' + a.confirmaciones + ' ✓' : '')"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="patronesParaDia('ida').length === 0">
                    <p class="px-4 py-3 text-xs text-gray-300 dark:text-gray-600 italic">Sin horarios cargados</p>
                </template>
            </div>

            <div class="px-3 py-2 border-t border-gray-50 dark:border-gray-800/60">
                <template x-if="addingSentido !== 'ida'">
                    <button type="button"
                            @click="addingSentido = 'ida'; $nextTick(() => $refs.addHoraIda?.focus())"
                            class="text-xs text-teal-600 dark:text-teal-400 hover:underline">
                        + Agregar horario
                    </button>
                </template>
                <template x-if="addingSentido === 'ida'">
                    <div class="flex items-center gap-2">
                        <input type="time"
                               x-ref="addHoraIda"
                               x-model="addHora"
                               @keydown.enter="addPatron('ida')"
                               @keydown.escape="addingSentido = null; addHora = ''"
                               class="font-mono text-sm w-24 px-2 py-1 rounded border border-teal-300 dark:border-teal-700
                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                      focus:outline-none focus:ring-1 focus:ring-teal-400">
                        <button type="button" @click="addPatron('ida')"
                                class="text-xs px-2 py-1 rounded bg-teal-500 text-white hover:bg-teal-600 transition-colors">
                            Agregar
                        </button>
                        <button type="button" @click="addingSentido = null; addHora = ''"
                                class="text-xs text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ── Sección importar planilla ── --}}
    <div x-data="{ open: false }"
         class="rounded-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <button type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-3
                       text-sm font-medium text-gray-600 dark:text-gray-400
                       hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
            <span>↓ Importar planilla</span>
            <svg :class="open ? 'rotate-180' : ''"
                 class="w-4 h-4 transition-transform"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-cloak class="px-4 pb-4 space-y-4 border-t border-gray-100 dark:border-gray-800 pt-4">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                Pegá los horarios, uno por línea (formato HH:MM). Se agregarán los que no existan.
            </p>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Sentido</label>
                    <select x-model="importSentido"
                            class="w-full text-sm px-2 py-1.5 rounded border border-gray-200 dark:border-gray-700
                                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                   focus:outline-none focus:ring-1 focus:ring-teal-400">
                        <option value="vuelta">← A Tigre</option>
                        <option value="ida">→ Desde Tigre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Día</label>
                    <select x-model="importTipoDia"
                            class="w-full text-sm px-2 py-1.5 rounded border border-gray-200 dark:border-gray-700
                                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                   focus:outline-none focus:ring-1 focus:ring-teal-400">
                        <option value="lv">L–V</option>
                        <option value="sabado">Sáb</option>
                        <option value="domingo">Dom</option>
                        <option value="todos">Todos</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Fuente</label>
                    <select x-model="importFuente"
                            class="w-full text-sm px-2 py-1.5 rounded border border-gray-200 dark:border-gray-700
                                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                   focus:outline-none focus:ring-1 focus:ring-teal-400">
                        <option value="comunidad">Comunidad</option>
                        <option value="oficial">Oficial</option>
                        <option value="estimado">Estimado</option>
                    </select>
                </div>
            </div>

            <textarea x-model="importText"
                      @input="parseImport()"
                      rows="6"
                      placeholder="07:30&#10;08:15&#10;09:00&#10;..."
                      class="w-full font-mono text-sm px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700
                             bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                             placeholder-gray-300 dark:placeholder-gray-600
                             focus:outline-none focus:ring-2 focus:ring-teal-400 resize-none">
            </textarea>

            {{-- Preview --}}
            <template x-if="importPreview.length > 0">
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 space-y-1">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">
                        <span x-text="importPreview.length"></span> horarios reconocidos:
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="h in importPreview" :key="h">
                            <span class="font-mono text-xs px-2 py-0.5 rounded bg-teal-100 dark:bg-teal-900/30
                                         text-teal-700 dark:text-teal-400"
                                  x-text="h"></span>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="importInvalid.length > 0">
                <div class="rounded-lg bg-red-50 dark:bg-red-900/10 p-3">
                    <p class="text-xs text-red-500 dark:text-red-400">
                        Líneas ignoradas (formato inválido):
                        <span x-text="importInvalid.join(', ')"></span>
                    </p>
                </div>
            </template>

            <button type="button"
                    @click="confirmImport()"
                    :disabled="importPreview.length === 0 || saving"
                    :class="importPreview.length > 0 && !saving
                        ? 'bg-teal-600 hover:bg-teal-700 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed'"
                    class="w-full py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <span x-text="saving ? 'Importando...' : 'Importar ' + importPreview.length + ' horarios'"></span>
            </button>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:leave="transition ease-in duration-150"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50
                bg-gray-900 dark:bg-white text-white dark:text-gray-900
                text-sm font-medium px-5 py-3 rounded-2xl shadow-xl"
         x-text="toastMsg">
    </div>

</div>
</div>

<script>
function scheduleEditor(initialServiceId, allPatrones, muelleId, csrfToken) {
    return {
        activeServiceId: initialServiceId,
        diaTab: 'lv',
        patrones: allPatrones,   // { serviceId: [ grupo, ... ] }
        muelleId: muelleId,
        csrf: csrfToken,

        // Editing
        editingId: null,
        editHora: '',
        saving: false,

        // Adding
        addingSentido: null,
        addHora: '',
        addFuente: 'comunidad',

        // Import
        importText: '',
        importSentido: 'vuelta',
        importTipoDia: 'lv',
        importFuente: 'comunidad',
        importPreview: [],
        importInvalid: [],

        // Detail / avistajes
        openDetailId: null,
        detailAvistajes: [],
        detailLoading: false,

        // Toast
        toast: false,
        toastMsg: '',

        // ── Computed ──────────────────────────────────────────

        patronesParaDia(sentido) {
            const all = this.patrones[this.activeServiceId] || [];
            return all
                .filter(g => g.tipo_dia === this.diaTab && g.sentido === sentido)
                .sort((a, b) => a.hora.localeCompare(b.hora));
        },

        sentiEmoji(g) {
            const ok  = g.avistajes_ok  || 0;
            const mal = g.avistajes_mal || 0;
            if (ok + mal === 0) return '';
            if (mal === 0) return '🟢';
            if (ok  === 0) return '🔴';
            return ok >= mal ? '🟡' : '🔴';
        },

        sentiTitle(g) {
            const ok  = g.avistajes_ok  || 0;
            const mal = g.avistajes_mal || 0;
            if (ok + mal === 0) return '';
            return `${ok + mal} reporte${ok + mal > 1 ? 's' : ''}: ${ok} ok, ${mal} con problemas`;
        },

        // ── Editing ───────────────────────────────────────────

        startEdit(g) {
            this.editingId = g.id;
            this.editHora  = g.hora;
        },

        cancelEdit() {
            this.editingId = null;
            this.editHora  = '';
        },

        // ── Detail / Avistajes comunitarios ──────────────────────

        toggleDetail(g) {
            if (this.openDetailId === g.id) {
                this.openDetailId    = null;
                this.detailAvistajes = [];
            } else {
                this.openDetailId    = g.id;
                this.detailAvistajes = [];
                this.loadAvistajes(g);
            }
        },

        async loadAvistajes(g) {
            this.detailLoading = true;
            try {
                const params = (g.ids || [g.id]).map(id => `ids[]=${id}`).join('&');
                const res = await fetch(`/admin/movilidad/patrones/${g.id}/avistajes?${params}`);
                this.detailAvistajes = await res.json();
            } catch (e) {
                this.detailAvistajes = [];
            } finally {
                this.detailLoading = false;
            }
        },

        async saveEdit(g) {
            if (this.editingId !== g.id) return;
            if (! this.editHora || this.editHora === g.hora) { this.cancelEdit(); return; }

            this.saving = true;
            try {
                const res = await this.patch(`/admin/movilidad/patrones/${g.id}`, {
                    ids: g.ids,
                    hora: this.editHora,
                });
                if (res.ok) { g.hora = this.editHora; this.showToast('Guardado'); }
            } finally {
                this.saving = false;
                this.cancelEdit();
            }
        },

        // ── Fuente ────────────────────────────────────────────

        async cycleFuente(g) {
            const cycle = { 'estimado': 'comunidad', 'comunidad': 'oficial', 'oficial': 'estimado' };
            const newFuente = cycle[g.fuente] ?? 'comunidad';
            const res = await this.patch(`/admin/movilidad/patrones/${g.id}`, { ids: g.ids, fuente: newFuente });
            if (res.ok) { g.fuente = newFuente; this.showToast('Fuente actualizada'); }
        },

        // ── Visibilidad ───────────────────────────────────────

        async toggleVisibilidad(g) {
            const newVal = g.visibilidad === 'publico' ? 'oculto' : 'publico';
            const res = await this.patch(`/admin/movilidad/patrones/${g.id}`, { ids: g.ids, visibilidad: newVal });
            if (res.ok) { g.visibilidad = newVal; this.showToast(newVal === 'publico' ? 'Visible' : 'Oculto'); }
        },

        // ── Validar ───────────────────────────────────────────

        async validar(g) {
            const res = await this.post(`/admin/movilidad/patrones/${g.id}/validar`, { ids: g.ids });
            if (res.ok) { g.necesita_revision = false; g.validado_at = res.validado_at; this.showToast('Marcado como revisado'); }
        },

        async validarBulk() {
            this.saving = true;
            try {
                const res = await this.post('/admin/movilidad/patrones/validar-bulk', {
                    muelle_id:   this.muelleId,
                    servicio_id: this.activeServiceId,
                    tipo_dia:    this.diaTab,
                });
                if (res.ok) {
                    const all = this.patrones[this.activeServiceId] || [];
                    all.filter(g => g.tipo_dia === this.diaTab).forEach(g => { g.necesita_revision = false; });
                    this.showToast(`${res.count} horarios marcados como revisados`);
                }
            } finally { this.saving = false; }
        },

        // ── Eliminar ──────────────────────────────────────────

        async deleteGrupo(g) {
            if (! confirm(`¿Eliminar el horario ${g.hora}?`)) return;
            const res = await this.destroy(`/admin/movilidad/patrones/${g.id}`, { ids: g.ids });
            if (res.ok) {
                const all = this.patrones[this.activeServiceId] || [];
                this.patrones[this.activeServiceId] = all.filter(p => p.id !== g.id);
                this.showToast('Horario eliminado');
            }
        },

        // ── Agregar ───────────────────────────────────────────

        async addPatron(sentido) {
            if (! this.addHora) return;
            this.saving = true;
            try {
                const res = await this.post('/admin/movilidad/patrones', {
                    muelle_id:   this.muelleId,
                    servicio_id: this.activeServiceId,
                    hora:        this.addHora,
                    sentido:     sentido,
                    tipo_dia:    this.diaTab,
                    fuente:      this.addFuente,
                });
                if (res.ok) {
                    if (! this.patrones[this.activeServiceId]) this.patrones[this.activeServiceId] = [];
                    this.patrones[this.activeServiceId].push({
                        id:                res.ids[0],
                        ids:               res.ids,
                        hora:              res.hora,
                        sentido:           sentido,
                        tipo_dia:          this.diaTab,
                        fuente:            this.addFuente,
                        visibilidad:       'publico',
                        validado_at:       new Date().toISOString(),
                        necesita_revision: false,
                        notas_admin:       '',
                        avistajes_ok:      0,
                        avistajes_mal:     0,
                    });
                    this.addHora        = '';
                    this.addingSentido  = null;
                    this.showToast('Horario agregado');
                }
            } finally { this.saving = false; }
        },

        // ── Import ────────────────────────────────────────────

        parseImport() {
            const lines  = this.importText.split('\n').map(l => l.trim()).filter(l => l);
            const valid  = /^\d{1,2}:\d{2}$/;
            const pad    = h => { const [hh, mm] = h.split(':'); return `${hh.padStart(2,'0')}:${mm}`; };
            this.importPreview = lines.filter(l => valid.test(l)).map(pad);
            this.importInvalid = lines.filter(l => l && ! valid.test(l));
        },

        async confirmImport() {
            if (this.importPreview.length === 0) return;
            this.saving = true;
            try {
                const res = await this.post('/admin/movilidad/patrones/import', {
                    muelle_id:   this.muelleId,
                    servicio_id: this.activeServiceId,
                    sentido:     this.importSentido,
                    tipo_dia:    this.importTipoDia,
                    fuente:      this.importFuente,
                    horas:       this.importPreview,
                });
                if (res.ok) {
                    this.showToast(`${res.created} importados, ${res.skipped} ya existían`);
                    this.importText    = '';
                    this.importPreview = [];
                    this.importInvalid = [];
                    // Recargar la página para mostrar los nuevos horarios
                    setTimeout(() => window.location.reload(), 800);
                }
            } finally { this.saving = false; }
        },

        // ── Toast ─────────────────────────────────────────────

        showToast(msg) {
            this.toastMsg = msg;
            this.toast    = true;
            setTimeout(() => { this.toast = false; }, 2200);
        },

        // ── HTTP helpers ──────────────────────────────────────

        async post(url, body) {
            const r = await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body:    JSON.stringify(body),
            });
            return r.json();
        },

        async patch(url, body) {
            const r = await fetch(url, {
                method:  'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body:    JSON.stringify(body),
            });
            return r.json();
        },

        async destroy(url, body) {
            const r = await fetch(url, {
                method:  'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body:    JSON.stringify(body),
            });
            return r.json();
        },
    };
}
</script>

</x-app-layout>
