<x-app-layout>

    <div class="bg-white min-h-screen overflow-x-hidden dark:bg-gray-900">

        <!-- {{-- ── HERO ── --}}
        <div class="bg-gradient-to-br from-rose-50 via-white to-indigo-50 border-b border-gray-100 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900 dark:border-gray-800">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 text-center">
                <h1 class="text-3xl sm:text-5xl font-black text-gray-900 tracking-tight mb-2 dark:text-gray-100">
                    🏝️ Isla.Ar
                </h1>
                <p class="text-gray-500 text-base sm:text-lg dark:text-gray-400">
                    {{ __('ui.find_services_sub') }}
                </p>
            </div>
        </div> -->

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- ── CATEGORY CAROUSEL ── --}}
            <div class="py-5 border-b border-gray-100 dark:border-gray-800">
                <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
                    <a href="{{ route('home') }}"
                       class="shrink-0 px-4 py-2 rounded-full text-sm font-medium border transition-colors duration-150
                              {{ !$catSlug && !$search ? 'bg-gray-900 text-white border-gray-900 dark:bg-gray-100 dark:text-gray-900' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:border-gray-500' }}">
                        {{ __('ui.categories') }} &mdash; {{ __('ui.home') }}
                    </a>
                    @foreach ($categories as $cat)
                        <a href="{{ route('home', ['category' => $cat->slug]) }}"
                           class="shrink-0 px-4 py-2 rounded-full text-sm font-medium border transition-colors duration-150
                                  {{ $catSlug === $cat->slug ? 'bg-gray-900 text-white border-gray-900 dark:bg-gray-100 dark:text-gray-900' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:border-gray-500' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ── FILTERED GRID (when search or category active) ── --}}
            @if ($listings !== null)
                <div class="py-8">
                    @if ($listings->isEmpty())
                        <div class="text-center py-20">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 dark:bg-gray-800">
                                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 text-lg font-medium dark:text-gray-400">{{ __('ui.no_results') }}</p>
                            <p class="text-gray-400 text-sm mt-1">
                                <a href="{{ route('home') }}" class="text-rose-500 hover:underline">
                                    {{ __('ui.browse_all') }}
                                </a>
                            </p>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 mb-6 dark:text-gray-500">
                            {{ $listings->total() }} {{ Str::plural('resultado', $listings->total()) }}
                            @if ($search) — "<span class="text-gray-700 font-medium dark:text-gray-200">{{ $search }}</span>" @endif
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach ($listings as $listing)
                                <x-listing-card :listing="$listing" />
                            @endforeach
                        </div>
                        <div class="mt-10">
                            {{ $listings->links() }}
                        </div>
                    @endif
                </div>

            {{-- ── SECTIONS BY CATEGORY (default view) ── --}}
            @else
                @if ($categories->isEmpty())
                    <x-listings-empty-state />
                @else
                    <div class="py-8 space-y-12">
                        @foreach ($categories as $category)
                            <x-category-section
                                :category="$category"
                                :see-all-route="route('home', ['category' => $category->slug])"
                            />
                        @endforeach
                    </div>
                @endif
            @endif

        </div>
    </div>

</x-app-layout>
