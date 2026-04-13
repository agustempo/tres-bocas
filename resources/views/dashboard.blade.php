<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Flash message --}}
            @if (session('success'))
                <div class="p-4 bg-green-100 border border-green-200 text-green-800 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm font-medium text-gray-500 mb-1">
                        {{ auth()->user()->isAdmin() ? 'Total Listings' : 'My Listings' }}
                    </p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalListings }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm font-medium text-gray-500 mb-1">Published</p>
                    <p class="text-3xl font-bold text-green-600">{{ $publishedListings }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm font-medium text-gray-500 mb-1">Pending Reviews</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $pendingReviews }}</p>
                </div>
            </div>

            {{-- My Listings table (regular users only) --}}
            @if ($myListings !== null)
                <div class="bg-white rounded-lg shadow">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-800">My Listings</h3>
                        <a href="{{ route('listings.create') }}"
                           class="px-4 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            + New Listing
                        </a>
                    </div>

                    @if ($myListings->isEmpty())
                        <div class="px-6 py-8 text-center text-gray-400 text-sm">
                            You haven't created any listings yet.
                            <a href="{{ route('listings.create') }}" class="text-indigo-600 hover:underline">Create one now.</a>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach ($myListings as $listing)
                                <div class="flex items-center justify-between px-6 py-4">
                                    <div>
                                        <a href="{{ route('listings.show', $listing) }}"
                                           class="font-medium text-gray-900 hover:text-indigo-600">
                                            {{ $listing->title }}
                                        </a>
                                        <p class="text-sm text-gray-400 mt-0.5">{{ $listing->category->name }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        @php
                                            $badge = match($listing->status) {
                                                'published' => 'bg-green-100 text-green-700',
                                                'draft'     => 'bg-yellow-100 text-yellow-700',
                                                'archived'  => 'bg-gray-100 text-gray-500',
                                                default     => 'bg-gray-100 text-gray-500',
                                            };
                                        @endphp
                                        <span class="text-xs font-medium px-2 py-0.5 rounded capitalize {{ $badge }}">
                                            {{ $listing->status }}
                                        </span>
                                        <a href="{{ route('listings.edit', $listing) }}"
                                           class="text-sm text-gray-500 hover:text-indigo-600">Edit</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            {{-- Admin quick actions --}}
            @else
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('listings.index') }}"
                           class="px-4 py-2 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-100">
                            View All Listings
                        </a>
                        <a href="{{ route('listings.create') }}"
                           class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            + New Listing
                        </a>
                    </div>
                    @if ($pendingReviews > 0)
                        <p class="mt-4 text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-md px-4 py-3">
                            &#9888; <strong>{{ $pendingReviews }}</strong> {{ Str::plural('review', $pendingReviews) }} pending approval.
                            Visit individual listings to approve them.
                        </p>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
