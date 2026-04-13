<x-app-layout>

    <div class="bg-white min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="flex items-start gap-3 p-4 mb-6 bg-green-50 border border-green-200 text-green-800 rounded-2xl text-sm">
                    <svg class="w-5 h-5 shrink-0 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="flex items-start gap-3 p-4 mb-6 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm">
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
                                    overflow-hidden bg-gray-100">
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
                                <span class="text-xs font-semibold text-rose-500 bg-rose-50 px-2.5 py-1 rounded-full">
                                    {{ $listing->category->name }}
                                </span>
                                @if ($listing->status !== 'published')
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full capitalize
                                        {{ $listing->status === 'draft' ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $listing->status }}
                                    </span>
                                @endif
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                                {{ $listing->title }}
                            </h1>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @can('update', $listing)
                                <a href="{{ route('listings.edit', $listing) }}"
                                   class="px-3 py-1.5 bg-white border border-gray-200 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                                    Edit
                                </a>
                            @endcan
                            @can('delete', $listing)
                                <form method="POST" action="{{ route('listings.destroy', $listing) }}"
                                      onsubmit="return confirm('Delete this listing?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700">
                                        Delete
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>

                    {{-- Provider --}}
                    @php $owner = $listing->user; @endphp
                    <div class="flex items-center gap-4 py-5 border-y border-gray-100">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-400 to-rose-400
                                    flex items-center justify-center text-white font-bold text-lg shrink-0">
                            {{ strtoupper(substr($owner->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Provided by</p>
                            <p class="font-semibold text-gray-900">{{ $owner->name }}</p>
                        </div>
                        @if ($owner->reviews_count > 0)
                            <div class="ml-auto text-right">
                                <p class="text-lg font-bold text-gray-900 flex items-center gap-1 justify-end">
                                    <span class="text-yellow-400">★</span>
                                    {{ number_format($owner->avg_rating, 1) }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $owner->reviews_count }} {{ Str::plural('review', $owner->reviews_count) }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Description --}}
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">About this service</h2>
                        <p class="text-gray-600 leading-relaxed whitespace-pre-line">{{ $listing->description }}</p>
                    </div>

                    {{-- Contact --}}
                    @if ($listing->contact)
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl">
                            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm shrink-0">
                                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Contact</p>
                                <p class="text-sm text-gray-700 font-medium">{{ $listing->contact }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Upload photo (owner/admin) --}}
                    @can('update', $listing)
                        <div class="p-5 border border-dashed border-gray-200 rounded-2xl">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Add Photos</h3>
                            <form method="POST" action="{{ route('media.store', $listing) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="flex items-center gap-3">
                                    <input type="file" name="image" accept="image/*"
                                           class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3
                                                  file:border file:border-gray-200 file:rounded-lg file:text-sm
                                                  file:bg-white hover:file:bg-gray-50">
                                    <button type="submit"
                                            class="px-4 py-1.5 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">
                                        Upload
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
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            Reviews
                            @if ($owner->reviews_count > 0)
                                <span class="text-base font-normal text-gray-400 ml-1">({{ $owner->reviews_count }})</span>
                            @endif
                        </h2>

                        @forelse ($listing->reviews()->where('approved', true)->with('reviewer')->latest()->get() as $review)
                            <div class="py-5 border-b border-gray-100 last:border-0">
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-50 flex items-center justify-center
                                                text-indigo-700 font-semibold text-sm shrink-0">
                                        {{ strtoupper(substr($review->reviewer->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-gray-900">{{ $review->reviewer->name }}</p>
                                            <span class="text-xs text-gray-400 shrink-0">{{ $review->created_at->diffForHumans() }}</span>
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
                                            <p class="text-sm text-gray-600 leading-relaxed">{{ $review->comment }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 py-4">No reviews yet. Be the first after booking!</p>
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
                                <div id="review" class="p-6 bg-amber-50 border border-amber-100 rounded-3xl">
                                    <h3 class="text-base font-semibold text-gray-900 mb-4">Share your experience</h3>
                                    <form method="POST" action="{{ route('reviews.store', $listing) }}">
                                        @csrf
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
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
                                                      placeholder="What did you think of this service?"
                                                      class="w-full border-amber-200 bg-white rounded-2xl text-sm resize-none
                                                             focus:ring-amber-400 focus:border-amber-400">{{ old('comment') }}</textarea>
                                            @error('comment')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <button type="submit"
                                                class="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-xl transition-colors">
                                            Submit Review
                                        </button>
                                    </form>
                                </div>
                            @endif

                            {{-- Admin: pending reviews --}}
                            @if (auth()->user()->isAdmin())
                                @php $pending = $listing->reviews()->where('approved', false)->with('reviewer')->latest()->get(); @endphp
                                @if ($pending->isNotEmpty())
                                    <div class="p-5 bg-amber-50 border border-amber-200 rounded-2xl">
                                        <h3 class="text-sm font-semibold text-amber-800 mb-3 flex items-center gap-2">
                                            Pending Reviews
                                            <span class="bg-amber-200 text-amber-900 text-xs px-2 py-0.5 rounded-full">{{ $pending->count() }}</span>
                                        </h3>
                                        @foreach ($pending as $review)
                                            <div class="flex items-start justify-between border-b border-amber-100 py-3 last:border-0">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ $review->reviewer->name }}</p>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $review->comment }}</p>
                                                </div>
                                                <form method="POST" action="{{ route('reviews.approve', $review) }}" class="ml-3 shrink-0">
                                                    @csrf
                                                    <button type="submit"
                                                            class="px-3 py-1 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700">
                                                        Approve
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
                            <div class="bg-white border border-gray-200 rounded-3xl shadow-xl p-6">

                                {{-- Sent state --}}
                                <div x-show="sent" class="text-center py-4">
                                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 mb-1">Message sent!</h4>
                                    <p class="text-sm text-gray-500 mb-4">Reservation pending confirmation.</p>
                                    <button @click="sent = false; message = ''; requestedDate = ''"
                                            class="text-sm text-rose-500 hover:underline">Send another</button>
                                </div>

                                {{-- Form state --}}
                                <div x-show="!sent">
                                    <h3 class="text-base font-semibold text-gray-900 mb-1">Ask about this service</h3>
                                    <p class="text-sm text-gray-500 mb-5">Free to contact · No commitment</p>

                                    <div x-show="error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl">
                                        <span x-text="error"></span>
                                    </div>

                                    @auth
                                        <div class="mb-4">
                                            <textarea x-model="message"
                                                      rows="4"
                                                      placeholder="Describe what you need…"
                                                      class="w-full border-gray-200 bg-gray-50 rounded-2xl text-sm resize-none
                                                             focus:ring-rose-400 focus:border-rose-400 placeholder-gray-400"></textarea>
                                        </div>
                                        <div class="mb-5">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Preferred date (optional)</label>
                                            <input type="date" x-model="requestedDate"
                                                   class="w-full border-gray-200 bg-gray-50 rounded-xl text-sm
                                                          focus:ring-rose-400 focus:border-rose-400">
                                        </div>
                                        <button @click="submit()"
                                                :disabled="sending || message.trim() === ''"
                                                :class="(sending || message.trim() === '') ? 'opacity-50 cursor-not-allowed' : 'hover:bg-rose-600'"
                                                class="w-full py-3.5 bg-rose-500 text-white text-sm font-semibold rounded-2xl
                                                       shadow-lg shadow-rose-200 transition-colors duration-150">
                                            <span x-show="!sending">Send message</span>
                                            <span x-show="sending" class="flex items-center justify-center gap-2">
                                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                                </svg>
                                                Sending…
                                            </span>
                                        </button>
                                    @else
                                        <a href="{{ route('login') }}"
                                           class="block w-full py-3.5 bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold
                                                  rounded-2xl text-center shadow-lg shadow-rose-200 transition-colors">
                                            Log in to contact
                                        </a>
                                        <p class="text-center text-xs text-gray-400 mt-3">
                                            No account?
                                            <a href="{{ route('register') }}" class="text-rose-500 hover:underline">Sign up free</a>
                                        </p>
                                    @endauth
                                </div>

                                <p class="text-xs text-gray-400 text-center mt-4">Your inquiry is free and non-binding</p>
                            </div>

                            {{-- Reserve button --}}
                            @auth
                                @if ($listing->status === 'published' && auth()->id() !== $listing->user_id)
                                    <div x-data="{ rOpen: {{ session('reserveOpen') ? 'true' : 'false' }} }" class="mt-3">
                                        <button @click="rOpen = !rOpen"
                                                class="w-full py-3 border-2 border-gray-200 hover:border-gray-400
                                                       text-gray-700 text-sm font-semibold rounded-2xl text-center transition-colors">
                                            <span x-show="!rOpen">Or make a reservation →</span>
                                            <span x-show="rOpen" x-cloak>Hide reservation form</span>
                                        </button>
                                        <div x-show="rOpen" x-cloak
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             class="mt-3 p-4 border border-gray-200 rounded-2xl bg-gray-50">
                                            <form method="POST" action="{{ route('reservations.store', $listing) }}">
                                                @csrf
                                                <div class="mb-3">
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Date &amp; time (optional)</label>
                                                    <input type="datetime-local" name="scheduled_at"
                                                           class="w-full border-gray-200 bg-white rounded-xl text-sm focus:ring-rose-400 focus:border-rose-400">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Notes (optional)</label>
                                                    <textarea name="notes" rows="2"
                                                              class="w-full border-gray-200 bg-white rounded-xl text-sm resize-none focus:ring-rose-400 focus:border-rose-400"></textarea>
                                                </div>
                                                <button type="submit"
                                                        class="w-full py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors">
                                                    Confirm reservation
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
