<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">
            {{ __('ui.dashboard_title') }}
        </h2>
    </x-slot>

    <style>
        @media (min-width: 1024px) {
            .dash-outer   { max-width: 72rem; }
            .dash-grid    { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start; }
            .dash-col-env { display: flex; flex-direction: column; gap: 1rem; }
            .dash-tide-row{ display: grid; grid-template-columns: repeat(2,1fr); gap: 0.75rem; }
        }
    </style>

    <div class="py-6 lg:py-8">
        <div class="dash-outer mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 bg-green-100 border border-green-200 text-green-800 rounded-xl
                            dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ── Síntesis (full width siempre) ───────────────────────────── --}}
            <x-dashboard.synthesis-card
                :tide="$tide"
                :muelle="$muelle"
                :proximo="$proximoPaso"
                :servicio="$servicioPrincipal ?? null" />

            {{-- ── Grid desktop ─────────────────────────────────────────────── --}}
            <div class="dash-grid space-y-4 lg:space-y-0">

                {{-- Columna izquierda: lancha + listings/admin --}}
                <div class="space-y-4">

                    <x-dashboard.next-boat-card
                        :muelle="$muelle"
                        :proximo="$proximoPaso"
                        :avistaje="$avistajeProximo ?? null"
                        :confirmaciones="$confirmacionesProximo ?? 0"
                        :servicio-principal="$servicioPrincipal ?? null"
                        :mi-reaccion="$miReaccion ?? ''" />

                    {{-- Servicios / Admin --}}
                    @if (!auth()->user()->isAdmin())

                        <div class="space-y-3">
                            <div class="flex items-center justify-between px-1">
                                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                    {{ __('ui.my_listings_title') }}
                                </p>
                                <a href="{{ route('listings.create') }}"
                                   class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                                    {{ __('ui.new_listing') }}
                                </a>
                            </div>

                            @if ($myListings !== null)
                                @if ($myListings->isEmpty())
                                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 px-6 py-8 text-center shadow-sm">
                                        <p class="text-sm text-gray-400 dark:text-gray-500">
                                            {{ __('ui.no_listings_yet') }}
                                            <a href="{{ route('listings.create') }}"
                                               class="text-teal-600 dark:text-teal-400 hover:underline ml-1">
                                                {{ __('ui.create_one_now') }}
                                            </a>
                                        </p>
                                    </div>
                                @else
                                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach ($myListings as $listing)
                                            @php
                                                $badge = match($listing->status) {
                                                    'published' => 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
                                                    'draft'     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400',
                                                    'archived'  => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                                    default     => 'bg-gray-100 text-gray-500',
                                                };
                                                $statusLabel = match($listing->status) {
                                                    'published' => __('ui.status_published'),
                                                    'draft'     => __('ui.status_draft'),
                                                    'archived'  => __('ui.status_archived'),
                                                    default     => $listing->status,
                                                };
                                            @endphp
                                            <div class="flex items-center justify-between px-5 py-3.5">
                                                <div class="min-w-0 flex-1">
                                                    <a href="{{ route('listings.show', $listing) }}"
                                                       class="font-medium text-gray-800 dark:text-gray-100 hover:text-teal-600 dark:hover:text-teal-400 truncate block">
                                                        {{ $listing->title }}
                                                    </a>
                                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                        {{ $listing->category->name }}
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-3 shrink-0 ml-4">
                                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $badge }}">
                                                        {{ $statusLabel }}
                                                    </span>
                                                    <a href="{{ route('listings.edit', $listing) }}"
                                                       class="text-xs text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
                                                        {{ __('ui.edit') }}
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>

                    @endif

                </div>

                {{-- Columna derecha: datos ambientales --}}
                <div class="dash-col-env space-y-4 lg:space-y-0">

                    {{-- Marea + Tiempo --}}
                    <div class="dash-tide-row" style="display:grid; grid-template-columns:repeat(2,1fr); gap:0.75rem">
                        <x-dashboard.tide-summary-card :tide="$tide" />
                        <x-dashboard.weather-summary-card :tide="$tide" />
                    </div>

                    {{-- Próximas mareas --}}
                    <x-dashboard.upcoming-tides :tide="$tide" />

                    {{-- Pronóstico por hora --}}
                    <x-dashboard.hourly-weather :tide="$tide" />

                </div>

            </div>{{-- end dash-grid --}}

        </div>
    </div>
</x-app-layout>
