<x-app-layout>

    <div class="bg-white min-h-screen dark:bg-gray-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="flex items-start gap-3 p-4 mb-6 bg-green-50 border border-green-200 text-green-800 rounded-2xl text-sm dark:bg-green-900/20 dark:border-green-800 dark:text-green-300">
                    <svg class="w-5 h-5 shrink-0 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="flex items-start gap-3 p-4 mb-6 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
                    <svg class="w-5 h-5 shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- ── IMAGE GALLERY ── --}}
            @if ($listing->media->isNotEmpty())
                @php $imgs = $listing->media; @endphp
                <div class="mb-8 rounded-3xl overflow-hidden
                            @if($imgs->count() === 1) h-72 sm:h-96 @else grid gap-2 @endif
                            @if($imgs->count() >= 2) grid-cols-2 @endif
                            @if($imgs->count() >= 3) grid-rows-2 @endif">
                    @foreach ($imgs->take(5) as $i => $media)
                        <div class="@if($i === 0 && $imgs->count() > 1) row-span-2 @endif
                                    @if($imgs->count() === 1) h-full @else h-40 sm:h-52 @endif
                                    @if($i >= 3) hidden sm:block @endif
                                    overflow-hidden bg-gray-100 dark:bg-gray-800">
                            <img src="{{ asset('storage/' . $media->path) }}"
                                 alt="{{ $listing->title }}"
                                 class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- ── LEFT COLUMN: main content ── --}}
                <div class="lg:col-span-2 space-y-8">

                    {{-- Title + Admin controls --}}
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-semibold text-rose-500 bg-rose-50 px-2.5 py-1 rounded-full dark:bg-rose-900/20">
                                    {{ $listing->category->name }}
                                </span>
                                @if ($listing->status !== 'published')
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full capitalize
                                        {{ $listing->status === 'draft' ? 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $listing->status === 'draft' ? __('ui.status_draft') : __('ui.status_archived') }}
                                    </span>
                                @endif
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight dark:text-gray-100">
                                {{ $listing->title }}
                            </h1>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @can('update', $listing)
                                <a href="{{ route('listings.edit', $listing) }}"
                                   class="px-3 py-1.5 bg-white border border-gray-200 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">
                                    {{ __('ui.edit') }}
                                </a>
                            @endcan
                            @can('delete', $listing)
                                <form method="POST" action="{{ route('listings.destroy', $listing) }}"
                                      onsubmit="return confirm('{{ __('ui.delete_confirm') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700">
                                        {{ __('ui.delete') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>

                    {{-- Provider --}}
                    @php $owner = $listing->user; @endphp
                    <div class="flex items-center gap-4 py-5 border-y border-gray-100 dark:border-gray-800">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-400 to-rose-400
                                    flex items-center justify-center text-white font-bold text-lg shrink-0">
                            {{ strtoupper(substr($owner->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.provided_by') }}</p>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $owner->name }}</p>
                        </div>
                        @if ($owner->reviews_count > 0)
                            <div class="ml-auto text-right">
                                <p class="text-lg font-bold text-gray-900 flex items-center gap-1 justify-end dark:text-gray-100">
                                    <span class="text-yellow-400">★</span>
                                    {{ number_format($owner->avg_rating, 1) }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $owner->reviews_count }} {{ Str::plural('review', $owner->reviews_count) }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Description --}}
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-3 dark:text-gray-100">{{ __('ui.about_service') }}</h2>
                        <p class="text-gray-600 leading-relaxed whitespace-pre-line dark:text-gray-300">{{ $listing->description }}</p>
                    </div>

                    {{-- Location map --}}
                    @if ($listing->location)
                        {{-- Pass coordinates as data attributes — keeps x-data attribute free of JS and quotes --}}
                        <div
                            x-data="listingMap($el)"
                            x-init="init()"
                            data-lat="{{ (float) $listing->location->latitude }}"
                            data-lng="{{ (float) $listing->location->longitude }}">

                            <h2 class="text-lg font-semibold text-gray-900 mb-3 dark:text-gray-100">{{ __('ui.location_label') }}</h2>

                            @if ($listing->location->description)
                                <p class="text-sm text-gray-500 mb-2 flex items-center gap-1.5 dark:text-gray-400">
                                    <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                    {{ $listing->location->description }}
                                </p>
                            @endif

                            <div x-ref="map"
                                 class="w-full h-56 sm:h-64 rounded-2xl overflow-hidden border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                            </div>

                            <a href="https://www.openstreetmap.org/?mlat={{ $listing->location->latitude }}&mlon={{ $listing->location->longitude }}#map=16/{{ $listing->location->latitude }}/{{ $listing->location->longitude }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="mt-1.5 inline-flex items-center gap-1 text-xs text-gray-400 hover:text-indigo-600 transition-colors dark:text-gray-500 dark:hover:text-indigo-400">
                                {{ __('ui.location_open_map') }}
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>

                        <script>
                        function listingMap(rootEl) {
                            return {
                                _map: null,

                                init() {
                                    const lat = parseFloat(rootEl.dataset.lat);
                                    const lng = parseFloat(rootEl.dataset.lng);
                                    const el  = this.$refs.map;

                                    const loadLeaflet = () => {
                                        if (!document.querySelector('link[data-leaflet-css]')) {
                                            const link = document.createElement('link');
                                            link.rel = 'stylesheet';
                                            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                                            link.dataset.leafletCss = '';
                                            document.head.appendChild(link);
                                        }
                                        return new Promise((resolve, reject) => {
                                            if (window.L) return resolve();
                                            const s = document.createElement('script');
                                            s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                                            s.onload = resolve;
                                            s.onerror = reject;
                                            document.head.appendChild(s);
                                        });
                                    };

                                    const observer = new IntersectionObserver((entries) => {
                                        if (!entries[0].isIntersecting) return;
                                        observer.disconnect();
                                        loadLeaflet().then(() => {
                                            this._map = L.map(el, {
                                                zoomControl:     true,
                                                scrollWheelZoom: false,
                                            }).setView([lat, lng], 15);
                                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                                attribution: '\u00a9 <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                                                maxZoom: 19,
                                            }).addTo(this._map);
                                            L.marker([lat, lng]).addTo(this._map);
                                        }).catch((e) => {
                                            console.warn('[ListingMap] Failed to load Leaflet.', e);
                                        });
                                    }, { threshold: 0.1 });

                                    observer.observe(el);
                                },
                            };
                        }
                        </script>
                    @endif

                    {{-- Contact --}}
                    @if ($listing->contact)
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl dark:bg-gray-800">
                            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm shrink-0 dark:bg-gray-700">
                                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium dark:text-gray-500">{{ __('ui.contact_small') }}</p>
                                <p class="text-sm text-gray-700 font-medium dark:text-gray-200">{{ $listing->contact }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Upload photo (owner/admin) --}}
                    @can('update', $listing)
                        <div class="p-5 border border-dashed border-gray-200 rounded-2xl dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3 dark:text-gray-200">{{ __('ui.add_photos') }}</h3>
                            <form method="POST" action="{{ route('media.store', $listing) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="flex items-center gap-3">
                                    <input type="file" name="image" accept="image/*"
                                           class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3
                                                  file:border file:border-gray-200 file:rounded-lg file:text-sm
                                                  file:bg-white hover:file:bg-gray-50 dark:text-gray-400 dark:file:border-gray-600 dark:file:bg-gray-700 dark:file:text-gray-200 dark:hover:file:bg-gray-600">
                                    <button type="submit"
                                            class="px-4 py-1.5 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">
                                        {{ __('ui.upload') }}
                                    </button>
                                </div>
                                @error('image')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </form>
                        </div>
                    @endcan

                    {{-- Reviews --}}
                    <div id="reviews">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 dark:text-gray-100">
                            {{ __('ui.reviews') }}
                            @if ($owner->reviews_count > 0)
                                <span class="text-base font-normal text-gray-400 ml-1 dark:text-gray-500">({{ $owner->reviews_count }})</span>
                            @endif
                        </h2>

                        @forelse ($listing->reviews()->where('approved', true)->with('reviewer')->latest()->get() as $review)
                            <div class="py-5 border-b border-gray-100 last:border-0 dark:border-gray-800">
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-50 flex items-center justify-center
                                                text-indigo-700 font-semibold text-sm shrink-0 dark:bg-indigo-900/20 dark:text-indigo-300">
                                        {{ strtoupper(substr($review->reviewer->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $review->reviewer->name }}</p>
                                            <span class="text-xs text-gray-400 shrink-0 dark:text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="flex gap-0.5 my-1">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="w-3.5 h-3.5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}"
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                        @if ($review->comment)
                                            <p class="text-sm text-gray-600 leading-relaxed dark:text-gray-300">{{ $review->comment }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 py-4 dark:text-gray-500">{{ __('ui.no_reviews_yet') }}</p>
                        @endforelse
                    </div>

                    {{-- Leave a review (completed inquiry or reservation) --}}
                    @auth
                        @if (auth()->id() !== $listing->user_id)
                            @php
                                $canReview = auth()->user()->inquiriesAsCustomer()
                                    ->where('listing_id', $listing->id)
                                    ->where('status', 'completed')
                                    ->exists()
                                    || auth()->user()->reservationsAsCustomer()
                                        ->where('listing_id', $listing->id)
                                        ->where('status', 'completed')
                                        ->exists();
                            @endphp
                            @if ($canReview)
                                <div id="review" class="p-6 bg-amber-50 border border-amber-100 rounded-3xl dark:bg-amber-900/20 dark:border-amber-800">
                                    <h3 class="text-base font-semibold text-gray-900 mb-4 dark:text-gray-100">{{ __('ui.share_experience') }}</h3>
                                    <form method="POST" action="{{ route('reviews.store', $listing) }}">
                                        @csrf
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-200">{{ __('ui.rating_label') }}</label>
                                            <div class="flex gap-1.5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <label class="cursor-pointer group/star">
                                                        <input type="radio" name="rating" value="{{ $i }}"
                                                               class="sr-only peer" {{ old('rating') == $i ? 'checked' : '' }}>
                                                        <svg class="w-8 h-8 text-gray-300 peer-checked:text-yellow-400
                                                                    group-hover/star:text-yellow-300 transition-colors"
                                                             fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                    </label>
                                                @endfor
                                            </div>
                                            @error('rating')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="mb-5">
                                            <textarea name="comment" rows="3" required
                                                      placeholder="{{ __('ui.review_placeholder') }}"
                                                      class="w-full border-amber-200 bg-white rounded-2xl text-sm resize-none
                                                             focus:ring-amber-400 focus:border-amber-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500">{{ old('comment') }}</textarea>
                                            @error('comment')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <button type="submit"
                                                class="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-xl transition-colors">
                                            {{ __('ui.submit_review') }}
                                        </button>
                                    </form>
                                </div>
                            @endif

                            {{-- Admin: pending reviews --}}
                            @if (auth()->user()->isAdmin())
                                @php $pending = $listing->reviews()->where('approved', false)->with('reviewer')->latest()->get(); @endphp
                                @if ($pending->isNotEmpty())
                                    <div class="p-5 bg-amber-50 border border-amber-200 rounded-2xl dark:bg-amber-900/20 dark:border-amber-800">
                                        <h3 class="text-sm font-semibold text-amber-800 mb-3 flex items-center gap-2 dark:text-amber-300">
                                            {{ __('ui.pending_reviews_admin') }}
                                            <span class="bg-amber-200 text-amber-900 text-xs px-2 py-0.5 rounded-full">{{ $pending->count() }}</span>
                                        </h3>
                                        @foreach ($pending as $review)
                                            <div class="flex items-start justify-between border-b border-amber-100 py-3 last:border-0 dark:border-amber-900/40">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $review->reviewer->name }}</p>
                                                    <p class="text-xs text-gray-500 mt-0.5 dark:text-gray-400">{{ $review->comment }}</p>
                                                </div>
                                                <form method="POST" action="{{ route('reviews.approve', $review) }}" class="ml-3 shrink-0">
                                                    @csrf
                                                    <button type="submit"
                                                            class="px-3 py-1 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700">
                                                        {{ __('ui.approve') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        @endif
                    @endauth

                </div>

                {{-- ── RIGHT COLUMN: sticky inquiry card ── --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-6"
                         x-data="{
                             open: false,
                             sent: false,
                             sending: false,
                             error: '',
                             message: '',
                             requestedDate: '',
                             async submit() {
                                 this.sending = true;
                                 this.error = '';
                                 try {
                                     const res = await fetch('/inquiries', {
                                         method: 'POST',
                                         headers: {
                                             'Content-Type': 'application/json',
                                             'Accept': 'application/json',
                                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                         },
                                         body: JSON.stringify({
                                             listing_id: {{ $listing->id }},
                                             message: this.message,
                                             requested_date: this.requestedDate || null,
                                         }),
                                     });
                                     const data = await res.json();
                                     if (res.ok) {
                                         this.sent = true;
                                     } else {
                                         this.error = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Something went wrong.');
                                     }
                                 } catch {
                                     this.error = 'Network error. Please try again.';
                                 }
                                 this.sending = false;
                             }
                         }">

                        @if ($listing->status === 'published' && (!auth()->check() || auth()->id() !== $listing->user_id))
                            <div class="bg-white border border-gray-200 rounded-3xl shadow-xl p-6 dark:bg-gray-900 dark:border-gray-700 dark:shadow-black/20">

                                {{-- Sent state --}}
                                <div x-show="sent" class="text-center py-4">
                                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 dark:bg-green-900/30">
                                        <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 mb-1 dark:text-gray-100">{{ __('ui.message_sent') }}</h4>
                                    <p class="text-sm text-gray-500 mb-4 dark:text-gray-400">{{ __('ui.reservation_pending_confirmation') }}</p>
                                    <button @click="sent = false; message = ''; requestedDate = ''"
                                            class="text-sm text-rose-500 hover:underline">{{ __('ui.send_another') }}</button>
                                </div>

                                {{-- Form state --}}
                                <div x-show="!sent">
                                    <h3 class="text-base font-semibold text-gray-900 mb-1 dark:text-gray-100">{{ __('ui.ask_about') }}</h3>
                                    <p class="text-sm text-gray-500 mb-5 dark:text-gray-400">{{ __('ui.free_no_commitment') }}</p>

                                    <div x-show="error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
                                        <span x-text="error"></span>
                                    </div>

                                    @auth
                                        <div class="mb-4">
                                            <textarea x-model="message"
                                                      rows="4"
                                                      placeholder="Describe what you need…"
                                                      class="w-full border-gray-200 bg-gray-50 rounded-2xl text-sm resize-none
                                                             focus:ring-rose-400 focus:border-rose-400 placeholder-gray-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500"></textarea>
                                        </div>
                                        <div class="mb-5">
                                            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('ui.preferred_date') }} ({{ __('ui.optional') }})</label>
                                            <input type="date" x-model="requestedDate"
                                                   class="w-full border-gray-200 bg-gray-50 rounded-xl text-sm
                                                          focus:ring-rose-400 focus:border-rose-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500">
                                        </div>
                                        <button @click="submit()"
                                                :disabled="sending || message.trim() === ''"
                                                :class="(sending || message.trim() === '') ? 'opacity-50 cursor-not-allowed' : 'hover:bg-rose-600'"
                                                class="w-full py-3.5 bg-rose-500 text-white text-sm font-semibold rounded-2xl
                                                       shadow-lg shadow-rose-200 transition-colors duration-150">
                                            <span x-show="!sending">{{ __('ui.send_message') }}</span>
                                            <span x-show="sending" class="flex items-center justify-center gap-2">
                                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                                </svg>
                                                {{ __('ui.sending') }}
                                            </span>
                                        </button>
                                    @else
                                        <a href="{{ route('login') }}"
                                           class="block w-full py-3.5 bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold
                                                  rounded-2xl text-center shadow-lg shadow-rose-200 transition-colors">
                                            {{ __('ui.log_in_to_contact') }}
                                        </a>
                                        <p class="text-center text-xs text-gray-400 mt-3 dark:text-gray-500">
                                            {{ __('ui.no_account') }}
                                            <a href="{{ route('register') }}" class="text-rose-500 hover:underline">{{ __('ui.sign_up_free') }}</a>
                                        </p>
                                    @endauth
                                </div>

                                <p class="text-xs text-gray-400 text-center mt-4 dark:text-gray-500">{{ __('ui.inquiry_non_binding') }}</p>
                            </div>

                            {{-- Reserve button --}}
                            @auth
                                @if ($listing->status === 'published' && auth()->id() !== $listing->user_id)
                                    <div x-data="{ rOpen: {{ session('reserveOpen') ? 'true' : 'false' }} }" class="mt-3">
                                        <button @click="rOpen = !rOpen"
                                                class="w-full py-3 border-2 border-gray-200 hover:border-gray-400
                                                       text-gray-700 text-sm font-semibold rounded-2xl text-center transition-colors dark:border-gray-700 dark:hover:border-gray-500 dark:text-gray-200">
                                            <span x-show="!rOpen">{{ __('ui.make_reservation') }}</span>
                                            <span x-show="rOpen" x-cloak>{{ __('ui.hide_reservation_form') }}</span>
                                        </button>
                                        <div x-show="rOpen" x-cloak
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             class="mt-3 p-4 border border-gray-200 rounded-2xl bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                                            <form method="POST" action="{{ route('reservations.store', $listing) }}">
                                                @csrf
                                                <div class="mb-3">
                                                    <label class="block text-xs font-medium text-gray-600 mb-1 dark:text-gray-300">{{ __('ui.datetime_optional') }}</label>
                                                    <input type="datetime-local" name="scheduled_at"
                                                           class="w-full border-gray-200 bg-white rounded-xl text-sm focus:ring-rose-400 focus:border-rose-400 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="block text-xs font-medium text-gray-600 mb-1 dark:text-gray-300">{{ __('ui.notes_optional_label') }}</label>
                                                    <textarea name="notes" rows="2"
                                                              class="w-full border-gray-200 bg-white rounded-xl text-sm resize-none focus:ring-rose-400 focus:border-rose-400 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500"></textarea>
                                                </div>
                                                <button type="submit"
                                                        class="w-full py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors">
                                                    {{ __('ui.confirm_reservation') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>

            </div>{{-- /grid --}}
        </div>{{-- /container --}}
    </div>

</x-app-layout>
