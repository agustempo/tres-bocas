<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">{{ __('ui.listings_title') }}</h2>
            @auth
                <a href="{{ route('listings.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    {{ __('ui.new_listing') }}
                </a>
            @endauth
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Flash message --}}
            @if (session('success'))
                <div class="mb-5 p-4 bg-green-100 border border-green-200 text-green-800 rounded-md dark:bg-green-900/20 dark:border-green-800 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Search & Filter --}}
            <form method="GET" action="{{ route('listings.index') }}"
                  class="mb-6 flex flex-col sm:flex-row gap-3">
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="{{ __('ui.search_listings') }}"
                       class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500">

                <select name="category"
                        class="sm:w-48 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500">
                    <option value="">{{ __('ui.all_categories') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit"
                        class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    {{ __('ui.search') }}
                </button>

                @if (request('q') || request('category'))
                    <a href="{{ route('listings.index') }}"
                       class="px-4 py-2 bg-white border border-gray-300 text-gray-600 text-sm rounded-md hover:bg-gray-50 text-center dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        {{ __('ui.clear') }}
                    </a>
                @endif
            </form>

            {{-- Results --}}
            @if ($listings->isEmpty())
                <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500 dark:bg-gray-900 dark:shadow-black/20 dark:text-gray-400">
                    {{ __('ui.no_listings_found') }}
                    @if (request('q') || request('category'))
                        <a href="{{ route('listings.index') }}" class="ml-1 text-indigo-600 hover:underline">{{ __('ui.clear_filters') }}</a>
                    @endif
                </div>
            @else
                {{-- Result count --}}
                <p class="text-sm text-gray-500 mb-4 dark:text-gray-400">
                    {{ __('ui.showing_results', ['from' => $listings->firstItem(), 'to' => $listings->lastItem(), 'total' => $listings->total()]) }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($listings as $listing)
                        <a href="{{ route('listings.show', $listing) }}"
                           class="block bg-white rounded-lg shadow hover:shadow-md transition p-5 dark:bg-gray-900 dark:shadow-black/20">

                            {{-- Category + Status badges --}}
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded dark:bg-indigo-900/20 dark:text-indigo-300">
                                    {{ $listing->category->name }}
                                </span>
                                @if ($listing->status !== 'published')
                                    @php
                                        $badge = match($listing->status) {
                                            'draft'    => 'bg-yellow-100 text-yellow-700',
                                            'archived' => 'bg-gray-100 text-gray-500',
                                            default    => 'bg-gray-100 text-gray-500',
                                        };
                                        $statusLabel = match($listing->status) {
                                            'draft'    => __('ui.status_draft'),
                                            'archived' => __('ui.status_archived'),
                                            default    => $listing->status,
                                        };
                                    @endphp
                                    <span class="text-xs font-medium px-2 py-0.5 rounded {{ $badge }}">
                                        {{ $statusLabel }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-base font-semibold text-gray-900 mb-1 leading-snug dark:text-gray-100">
                                {{ $listing->title }}
                            </h3>
                            <p class="text-sm text-gray-500 line-clamp-2 dark:text-gray-400">{{ $listing->description }}</p>

                            <div class="mt-3 flex items-center gap-3 text-sm text-gray-400 dark:text-gray-500">
                                @if ($listing->user->reviews_count > 0)
                                    <span class="text-yellow-500">&#9733;</span>
                                    <span>{{ number_format($listing->user->avg_rating, 1) }}</span>
                                    <span class="text-gray-300 dark:text-gray-600">·</span>
                                    <span>{{ $listing->user->name }}</span>
                                @else
                                    <span>{{ $listing->user->name }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-8">
                    {{ $listings->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
