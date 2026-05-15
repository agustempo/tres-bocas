<x-app-layout>
<div class="bg-white min-h-screen dark:bg-gray-900">
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Panel · Movilidad</h1>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Gestión de horarios y muelles</p>
        </div>
        <span class="text-xs text-gray-400 dark:text-gray-600">{{ now()->format('d/m/Y') }}</span>
    </div>

    {{-- Leyenda de estados --}}
    <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-400"></span>Oficial</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span>Comunidad</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>Estimado</span>
        <span class="flex items-center gap-1.5 ml-4"><span class="text-amber-500">⚠</span>Necesita revisión</span>
    </div>

    {{-- Lista de muelles --}}
    <div class="space-y-2">
        @foreach ($muelles as $muelle)
            @php
                $pct = $muelle->patrones_total > 0
                    ? round($muelle->patrones_stale / $muelle->patrones_total * 100)
                    : 0;
            @endphp

            <div x-data="{ open: false, loading: false, svc: null, diaTab: 'lv' }"
                 class="rounded-xl border border-gray-100 dark:border-gray-800
                        bg-white dark:bg-gray-800/50 overflow-hidden transition-all">

                {{-- Fila del muelle (cabecera) --}}
                <div class="flex items-center gap-4 px-4 py-3 cursor-pointer
                            hover:bg-gray-900/[0.03] dark:hover:bg-white/[0.03] transition-colors group"
                     @click="open = !open; if (open && !svc && !loading) {
                         loading = true;
                         fetch('{{ route('admin.movilidad.muelles.preview', $muelle) }}')
                             .then(r => r.json())
                             .then(d => { svc = d; loading = false; })
                             .catch(() => { loading = false; });
                     }">

                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-900 dark:text-gray-100 group-hover:text-teal-700 dark:group-hover:text-teal-400">
                            {{ $muelle->nombre }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ implode(' · ', array_filter([$muelle->rio, $muelle->zona])) }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0 text-right">
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $muelle->patrones_total }}</span>
                            <span> horarios</span>
                            @if ($muelle->patrones_ocultos > 0)
                                <span class="text-gray-300 dark:text-gray-600"> · {{ $muelle->patrones_ocultos }} ocultos</span>
                            @endif
                        </div>

                        @if ($muelle->patrones_stale > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                         bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400
                                         border border-amber-200 dark:border-amber-800">
                                ⚠ {{ $muelle->patrones_stale }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                         bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400
                                         border border-green-200 dark:border-green-800">
                                ✓ OK
                            </span>
                        @endif

                        {{-- Flecha --}}
                        <svg :class="open ? 'rotate-90' : ''"
                             class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-teal-500 transition-all"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>

                {{-- Panel expandible con previsualización de horarios --}}
                <div x-show="open"
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="border-t border-gray-100 dark:border-gray-800">

                    {{-- Cargando --}}
                    <template x-if="loading">
                        <p class="px-4 py-4 text-xs text-gray-400 dark:text-gray-500 animate-pulse">Cargando horarios…</p>
                    </template>

                    {{-- Sin servicios --}}
                    <template x-if="!loading && svc && svc.length === 0">
                        <p class="px-4 py-4 text-xs text-gray-400 dark:text-gray-500 italic">Sin servicios vinculados</p>
                    </template>

                    {{-- Horarios --}}
                    <template x-if="svc && svc.length > 0">
                        <div class="px-4 py-3 space-y-4">

                            {{-- Por servicio --}}
                            <template x-for="s in svc" :key="s.id">
                                <div class="space-y-2">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                                       x-text="s.nombre"></p>

                                    {{-- Tabs de día --}}
                                    <div class="flex gap-1.5">
                                        <template x-for="[dt, label] in [['lv','L–V'],['sabado','Sáb'],['domingo','Dom']]" :key="dt">
                                            <template x-if="s.dias[dt] && s.dias[dt].length > 0">
                                                <button type="button"
                                                        @click.stop="diaTab = dt"
                                                        :class="diaTab === dt
                                                            ? 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900'
                                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                                                        class="px-2.5 py-1 rounded-full text-[10px] font-semibold transition-colors"
                                                        x-text="label">
                                                </button>
                                            </template>
                                        </template>
                                    </div>

                                    {{-- Grilla vuelta / ida --}}
                                    <div class="grid grid-cols-2 gap-3">

                                        {{-- ← A Tigre --}}
                                        <div>
                                            <p class="text-[9px] font-semibold uppercase tracking-widest
                                                       text-gray-300 dark:text-gray-600 mb-1.5">← A Tigre</p>
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="p in s.dias[diaTab]?.filter(p => p.sentido === 'vuelta') ?? []" :key="p.hora">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono
                                                                 bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300"
                                                          :class="p.stale ? 'opacity-60' : ''">
                                                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                              :class="p.fuente === 'oficial' ? 'bg-blue-400' : p.fuente === 'comunidad' ? 'bg-amber-400' : 'bg-gray-300 dark:bg-gray-500'">
                                                        </span>
                                                        <span x-text="p.hora"></span>
                                                        <template x-if="p.stale"><span class="text-amber-400 text-[9px]">⚠</span></template>
                                                    </span>
                                                </template>
                                                <template x-if="(s.dias[diaTab]?.filter(p => p.sentido === 'vuelta') ?? []).length === 0">
                                                    <span class="text-[10px] text-gray-300 dark:text-gray-600 italic">—</span>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- → Desde Tigre --}}
                                        <div>
                                            <p class="text-[9px] font-semibold uppercase tracking-widest
                                                       text-gray-300 dark:text-gray-600 mb-1.5">→ Desde Tigre</p>
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="p in s.dias[diaTab]?.filter(p => p.sentido === 'ida') ?? []" :key="p.hora">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono
                                                                 bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300"
                                                          :class="p.stale ? 'opacity-60' : ''">
                                                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                              :class="p.fuente === 'oficial' ? 'bg-blue-400' : p.fuente === 'comunidad' ? 'bg-amber-400' : 'bg-gray-300 dark:bg-gray-500'">
                                                        </span>
                                                        <span x-text="p.hora"></span>
                                                        <template x-if="p.stale"><span class="text-amber-400 text-[9px]">⚠</span></template>
                                                    </span>
                                                </template>
                                                <template x-if="(s.dias[diaTab]?.filter(p => p.sentido === 'ida') ?? []).length === 0">
                                                    <span class="text-[10px] text-gray-300 dark:text-gray-600 italic">—</span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Botón editar --}}
                            <div class="pt-1 border-t border-gray-50 dark:border-gray-800">
                                <a href="{{ route('admin.movilidad.muelles.editor', $muelle) }}"
                                   @click.stop
                                   class="text-xs text-teal-600 dark:text-teal-400 hover:underline font-medium">
                                    Editar horarios →
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        @endforeach
    </div>

</div>
</div>
</x-app-layout>
