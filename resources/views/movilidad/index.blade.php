<x-app-layout>

<div class="bg-white min-h-screen dark:bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                🛥 {{ __('movilidad.titulo') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('movilidad.subtitulo') }}
            </p>
        </div>

        {{-- Buscador + lista con Alpine --}}
        <div x-data="{ q: '' }">
            <input
                x-model="q"
                type="search"
                placeholder="{{ __('movilidad.buscar_muelle') }}"
                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700
                       bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100
                       placeholder-gray-400 dark:placeholder-gray-500
                       focus:outline-none focus:ring-2 focus:ring-teal-400 focus:bg-white dark:focus:bg-gray-700
                       text-sm transition-all"
            >

            <div class="mt-6 space-y-8">
                @forelse ($muelles as $zona => $listaMuelles)
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">
                            {{ $zona ?: __('movilidad.zona') }}
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($listaMuelles as $muelle)
                                @php
                                    $haystack = strtolower($muelle->nombre . ' ' . ($muelle->rio ?? '') . ' ' . ($muelle->zona ?? ''));
                                @endphp
                                <a href="{{ route('movilidad.muelles.show', $muelle->slug) }}"
                                   x-show="!q || '{{ $haystack }}'.includes(q.toLowerCase())"
                                   class="group flex items-start gap-4 p-4 rounded-2xl border border-gray-100 dark:border-gray-800
                                          bg-white dark:bg-gray-800/50 hover:border-teal-200 dark:hover:border-teal-700
                                          hover:bg-teal-50/40 dark:hover:bg-teal-900/10 transition-all">
                                    <div class="mt-0.5 w-9 h-9 rounded-full bg-teal-50 dark:bg-teal-900/30
                                                flex items-center justify-center text-lg shrink-0">
                                        🛥
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100 text-sm group-hover:text-teal-700 dark:group-hover:text-teal-400 transition-colors">
                                            {{ $muelle->nombre }}
                                        </p>
                                        @if ($muelle->rio)
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                {{ $muelle->rio }}
                                            </p>
                                        @endif
                                        @if ($muelle->servicios->isNotEmpty())
                                            <div class="flex flex-wrap gap-1 mt-2">
                                                @foreach ($muelle->servicios->take(3) as $servicio)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                                                                 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                                        {{ $servicio->nombre }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <svg class="ml-auto w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-teal-400 shrink-0 mt-1 transition-colors"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16 text-gray-400 dark:text-gray-500">
                        <div class="text-4xl mb-3">🛥</div>
                        <p class="text-sm">{{ __('movilidad.muelle_no_encontrado') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

</x-app-layout>
