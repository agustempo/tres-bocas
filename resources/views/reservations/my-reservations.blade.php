<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('ui.my_reservations') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8"
             x-data="{ tab: '{{ $asCustomer->isNotEmpty() || $asProvider->isEmpty() ? 'bookings' : 'incoming' }}' }">

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="flex items-start gap-3 p-4 mb-6 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">
                    <svg class="w-5 h-5 shrink-0 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="flex items-start gap-3 p-4 mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm">
                    <svg class="w-5 h-5 shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Tabs --}}
            <div class="flex gap-1 mb-6 bg-gray-100 p-1 rounded-xl w-fit">
                <button @click="tab = 'bookings'"
                        :class="tab === 'bookings'
                            ? 'bg-white text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-150">
                    {{ __('ui.my_bookings') }}
                    @if ($asCustomer->isNotEmpty())
                        <span class="ml-1.5 text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full"
                              :class="tab === 'bookings' ? 'bg-indigo-100 text-indigo-700' : ''">
                            {{ $asCustomer->count() }}
                        </span>
                    @endif
                </button>
                <button @click="tab = 'incoming'"
                        :class="tab === 'incoming'
                            ? 'bg-white text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-150">
                    {{ __('ui.incoming') }}
                    @if ($asProvider->isNotEmpty())
                        <span class="ml-1.5 text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full"
                              :class="tab === 'incoming' ? 'bg-indigo-100 text-indigo-700' : ''">
                            {{ $asProvider->count() }}
                        </span>
                    @endif
                </button>
            </div>

            {{-- ── MY BOOKINGS (as customer) ── --}}
            <div x-show="tab === 'bookings'" x-cloak>
                @if ($asCustomer->isEmpty())
                    <div class="text-center py-20 bg-white rounded-2xl border border-gray-100">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 text-sm">{{ __('ui.no_bookings_yet') }}</p>
                        <a href="{{ route('listings.index') }}"
                           class="inline-block mt-4 px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700">
                            {{ __('ui.browse_listings') }}
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($asCustomer as $res)
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">

                                {{-- Cover image --}}
                                <div class="relative h-44 bg-gray-100 shrink-0">
                                    @if ($res->listing->media->isNotEmpty())
                                        <img src="{{ asset('storage/' . $res->listing->media->first()->path) }}"
                                             alt="{{ $res->listing->title }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif

                                    {{-- Status badge --}}
                                    @php
                                        $badge = match($res->status) {
                                            'pending'   => 'bg-amber-400 text-white',
                                            'confirmed' => 'bg-blue-500 text-white',
                                            'completed' => 'bg-emerald-500 text-white',
                                            'cancelled' => 'bg-red-500 text-white',
                                            default     => 'bg-gray-400 text-white',
                                        };
                                        $statusLabel = match($res->status) {
                                            'pending'   => __('ui.status_pending'),
                                            'confirmed' => __('ui.status_confirmed'),
                                            'completed' => __('ui.status_completed'),
                                            'cancelled' => __('ui.status_cancelled'),
                                            default     => $res->status,
                                        };
                                    @endphp
                                    <span class="absolute top-3 right-3 text-xs font-semibold px-2.5 py-1 rounded-full {{ $badge }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                {{-- Card body --}}
                                <div class="p-4 flex flex-col flex-1">
                                    <a href="{{ route('listings.show', $res->listing) }}"
                                       class="text-base font-semibold text-gray-900 hover:text-indigo-600 leading-snug mb-1">
                                        {{ $res->listing->title }}
                                    </a>

                                    <div class="flex items-center gap-1.5 text-sm text-gray-500 mb-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $res->provider->name }}
                                    </div>

                                    @if ($res->scheduled_at)
                                        <div class="flex items-center gap-1.5 text-sm text-gray-500 mb-1">
                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ $res->scheduled_at->format('M j, Y · H:i') }}
                                        </div>
                                    @endif

                                    @if ($res->notes)
                                        <p class="text-xs text-gray-400 italic mt-1 line-clamp-2">{{ $res->notes }}</p>
                                    @endif

                                    @if ($res->status === 'completed')
                                        <div class="mt-auto pt-4">
                                            <a href="{{ route('listings.show', $res->listing) }}#review"
                                               class="block w-full text-center py-2 bg-indigo-600 hover:bg-indigo-700
                                                      text-white text-sm font-semibold rounded-xl transition-colors">
                                                {{ __('ui.leave_review') }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ── INCOMING RESERVATIONS (as provider) ── --}}
            <div x-show="tab === 'incoming'" x-cloak>
                @if ($asProvider->isEmpty())
                    <div class="text-center py-20 bg-white rounded-2xl border border-gray-100">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 text-sm">{{ __('ui.no_incoming_yet') }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($asProvider as $res)
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">

                                {{-- Cover image --}}
                                <div class="relative h-44 bg-gray-100 shrink-0">
                                    @if ($res->listing->media->isNotEmpty())
                                        <img src="{{ asset('storage/' . $res->listing->media->first()->path) }}"
                                             alt="{{ $res->listing->title }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif

                                    @php
                                        $badge = match($res->status) {
                                            'pending'   => 'bg-amber-400 text-white',
                                            'confirmed' => 'bg-blue-500 text-white',
                                            'completed' => 'bg-emerald-500 text-white',
                                            'cancelled' => 'bg-red-500 text-white',
                                            default     => 'bg-gray-400 text-white',
                                        };
                                        $statusLabel = match($res->status) {
                                            'pending'   => __('ui.status_pending'),
                                            'confirmed' => __('ui.status_confirmed'),
                                            'completed' => __('ui.status_completed'),
                                            'cancelled' => __('ui.status_cancelled'),
                                            default     => $res->status,
                                        };
                                    @endphp
                                    <span class="absolute top-3 right-3 text-xs font-semibold px-2.5 py-1 rounded-full {{ $badge }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                {{-- Card body --}}
                                <div class="p-4 flex flex-col flex-1">
                                    <a href="{{ route('listings.show', $res->listing) }}"
                                       class="text-base font-semibold text-gray-900 hover:text-indigo-600 leading-snug mb-1">
                                        {{ $res->listing->title }}
                                    </a>

                                    <div class="flex items-center gap-1.5 text-sm text-gray-500 mb-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $res->customer->name }}
                                    </div>

                                    @if ($res->scheduled_at)
                                        <div class="flex items-center gap-1.5 text-sm text-gray-500 mb-1">
                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ $res->scheduled_at->format('M j, Y · H:i') }}
                                        </div>
                                    @endif

                                    @if ($res->notes)
                                        <p class="text-xs text-gray-400 italic mt-1 line-clamp-2">{{ $res->notes }}</p>
                                    @endif

                                    {{-- Provider actions --}}
                                    @if (in_array($res->status, ['pending', 'confirmed']))
                                        <div class="mt-auto pt-4 flex gap-2">
                                            @if ($res->status === 'pending')
                                                <form method="POST" action="{{ route('reservations.confirm', $res) }}" class="flex-1">
                                                    @csrf
                                                    <button type="submit"
                                                            class="w-full py-2 bg-blue-500 hover:bg-blue-600 text-white
                                                                   text-sm font-semibold rounded-xl transition-colors">
                                                        {{ __('ui.confirm') }}
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($res->status === 'confirmed')
                                                <form method="POST" action="{{ route('reservations.complete', $res) }}" class="flex-1">
                                                    @csrf
                                                    <button type="submit"
                                                            class="w-full py-2 bg-emerald-500 hover:bg-emerald-600 text-white
                                                                   text-sm font-semibold rounded-xl transition-colors">
                                                        {{ __('ui.mark_complete') }}
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('reservations.cancel', $res) }}">
                                                @csrf
                                                <button type="submit"
                                                        onclick="return confirm('{{ __('ui.cancel_reservation_confirm') }}')"
                                                        class="px-4 py-2 bg-gray-100 hover:bg-red-50 text-gray-600
                                                               hover:text-red-600 text-sm font-semibold rounded-xl transition-colors">
                                                    {{ __('ui.cancel') }}
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
