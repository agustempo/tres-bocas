@props(['category', 'seeAllRoute' => null])

<section>
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-bold text-gray-900">{{ $category->name }}</h2>
        @if ($seeAllRoute)
            <a href="{{ $seeAllRoute }}"
               class="text-sm font-medium text-rose-500 hover:text-rose-600 flex items-center gap-1">
                {{ __('ui.see_all') }}
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        @endif
    </div>
    <x-carousel>
        @foreach ($category->listings as $listing)
            <div class="carousel-item">
                <x-listing-card :listing="$listing" />
            </div>
        @endforeach
    </x-carousel>
</section>
