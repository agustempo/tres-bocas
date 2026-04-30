@props(['listing'])

<div x-data="{
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
     }"
     class="listing-card group">

    {{-- Image --}}
    <a href="{{ route('listings.show', $listing) }}" class="listing-card-image">
        @if ($listing->media->isNotEmpty())
            <img src="{{ asset('storage/' . $listing->media->first()->path) }}"
                 alt="{{ $listing->title }}"
                 loading="lazy">
        @else
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700">
                <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
        <span class="absolute top-3 left-3 text-xs font-semibold bg-white/90 backdrop-blur-sm text-gray-700 px-2.5 py-1 rounded-full shadow-sm">
            {{ $listing->category->name }}
        </span>
    </a>

    {{-- Body --}}
    <div class="listing-card-body">
        <a href="{{ route('listings.show', $listing) }}"
           class="font-semibold text-gray-900 hover:text-rose-600 text-sm leading-snug mb-1 transition-colors line-clamp-2 block dark:text-gray-100">
            {{ $listing->title }}
        </a>

        <div class="flex items-center gap-2 mb-2">
            <div class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-[10px] shrink-0">
                {{ strtoupper(substr($listing->user->name, 0, 1)) }}
            </div>
            <span class="text-xs text-gray-500 truncate dark:text-gray-400">{{ $listing->user->name }}</span>
            @if ($listing->user->reviews_count > 0)
                <span class="text-xs text-gray-400 ml-auto shrink-0 flex items-center gap-0.5 dark:text-gray-500">
                    <span class="text-yellow-400">★</span>
                    {{ number_format($listing->user->avg_rating, 1) }}
                </span>
            @endif
        </div>

        <p class="text-sm text-gray-500 line-clamp-2 leading-relaxed flex-1 dark:text-gray-400">
            {{ $listing->description }}
        </p>

        <div class="mt-4">
            @auth
                <button @click="open = true"
                        class="w-full py-2.5 bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold rounded-xl transition-colors duration-150">
                    {{ __('ui.ask_about') }}
                </button>
            @else
                <a href="{{ route('login') }}"
                   class="block w-full py-2.5 bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold rounded-xl transition-colors duration-150 text-center">
                    {{ __('ui.ask_about') }}
                </a>
            @endauth
        </div>
    </div>

    {{-- Inquiry modal --}}
    @auth
    <template x-teleport="body">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="open = false"
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
             style="display:none">

            <div @click="open = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

            <div @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md mx-auto overflow-hidden dark:bg-gray-900 dark:shadow-black/20">

                <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-rose-500 uppercase tracking-wide mb-0.5">Inquiry</p>
                            <h3 class="text-lg font-bold text-gray-900 leading-snug dark:text-gray-100">{{ $listing->title }}</h3>
                            <p class="text-sm text-gray-500 mt-0.5 dark:text-gray-400">{{ $listing->user->name }}</p>
                        </div>
                        <button @click="open = false"
                                class="shrink-0 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors dark:bg-gray-800 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div x-show="sent" class="text-center py-6">
                        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-1 dark:text-gray-100">Message sent!</h4>
                        <p class="text-sm text-gray-500 mb-6 dark:text-gray-400">The provider will get back to you soon.</p>
                        <button @click="open = false; sent = false; message = ''; requestedDate = ''"
                                class="px-6 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800">
                            Close
                        </button>
                    </div>

                    <div x-show="!sent">
                        <div x-show="error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl dark:bg-red-900/20 dark:border-red-800">
                            <span x-text="error"></span>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5 dark:text-gray-200">
                                Message <span class="text-rose-500">*</span>
                            </label>
                            <textarea x-model="message" rows="4"
                                      placeholder="Describe what you need…"
                                      class="w-full border-gray-200 bg-gray-50 rounded-xl text-sm resize-none focus:ring-rose-400 focus:border-rose-400 placeholder-gray-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500"></textarea>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5 dark:text-gray-200">
                                Preferred date <span class="text-xs text-gray-400 font-normal ml-1 dark:text-gray-500">optional</span>
                            </label>
                            <input type="date" x-model="requestedDate"
                                   class="w-full border-gray-200 bg-gray-50 rounded-xl text-sm focus:ring-rose-400 focus:border-rose-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500">
                        </div>
                        <button @click="submit()"
                                :disabled="sending || message.trim() === ''"
                                :class="(sending || message.trim() === '') ? 'opacity-50 cursor-not-allowed' : 'hover:bg-rose-600'"
                                class="w-full py-3 bg-rose-500 text-white text-sm font-semibold rounded-xl transition-colors duration-150">
                            <span x-show="!sending">{{ __('ui.send_message') }}</span>
                            <span x-show="sending" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Sending…
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
    @endauth
</div>
