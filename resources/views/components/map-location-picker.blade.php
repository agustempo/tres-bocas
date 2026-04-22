@props([
    'latitude'    => null,
    'longitude'   => null,
    'description' => null,
    'fieldPrefix' => 'location',
])

{{--
    MapLocationPicker — reusable Blade + Alpine.js component
    Loads Leaflet lazily (only when the map is first opened).
    Props:
      - latitude    / longitude   : existing coordinates (nullable floats)
      - description               : optional location label
      - fieldPrefix               : HTML field name prefix (default: "location")
    Emits hidden inputs: {prefix}[latitude], {prefix}[longitude], {prefix}[description]
--}}

<div
    x-data="mapLocationPicker({
        initLat:  {{ $latitude  !== null ? (float) $latitude  : 'null' }},
        initLng:  {{ $longitude !== null ? (float) $longitude : 'null' }},
        initDesc: @js($description ?? ''),
        strings: {
            tapHint:        @js(__('ui.location_tap_hint')),
            useGps:         @js(__('ui.location_use_gps')),
            getting:        @js(__('ui.location_getting')),
            noPin:          @js(__('ui.location_no_pin')),
            descHint:       @js(__('ui.location_description_hint')),
            gpsUnsupported: @js(__('ui.location_gps_unsupported')),
            gpsDenied:      @js(__('ui.location_gps_denied')),
            gpsUnavailable: @js(__('ui.location_gps_unavailable')),
            gpsTimeout:     @js(__('ui.location_gps_timeout')),
            gpsError:       @js(__('ui.location_gps_error')),
        },
        defaultLat: 9.3407,
        defaultLng: -82.2510,
    })"
    x-init="boot()"
>

    {{-- ── Hidden form fields passed with the listing form ── --}}
    <input type="hidden" name="{{ $fieldPrefix }}[latitude]"    x-model="lat">
    <input type="hidden" name="{{ $fieldPrefix }}[longitude]"   x-model="lng">
    <input type="hidden" name="{{ $fieldPrefix }}[description]" x-model="desc">

    {{-- ── Section header ── --}}
    <div class="flex items-center justify-between mb-2">
        <label class="block text-sm font-medium text-gray-700">
            {{ __('ui.location_label') }}
            <span class="text-xs text-gray-400 font-normal ml-1">({{ __('ui.optional') }})</span>
        </label>
        <button
            type="button"
            x-show="lat"
            @click="clearLocation()"
            class="text-xs text-red-500 hover:text-red-700 hover:underline focus:outline-none
                   focus:ring-2 focus:ring-red-400 rounded px-1">
            {{ __('ui.location_remove') }}
        </button>
    </div>

    {{-- ── No location: dashed trigger button ── --}}
    <div x-show="!lat">
        <button
            type="button"
            @click="openModal()"
            class="w-full py-3.5 px-4 border-2 border-dashed border-gray-300 rounded-xl
                   text-sm font-medium text-gray-500
                   hover:border-indigo-400 hover:text-indigo-600 hover:bg-indigo-50
                   active:bg-indigo-100
                   transition-all duration-150
                   flex items-center justify-center gap-2
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ __('ui.location_set_on_map') }}
        </button>
    </div>

    {{-- ── Location set: mini-map preview card ── --}}
    <div x-show="lat" class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        {{-- Mini map thumbnail --}}
        <div x-ref="minimap" class="h-40 bg-gray-100 w-full"></div>

        {{-- Coordinates + edit bar --}}
        <div class="px-3 py-2.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 min-w-0">
                <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                          clip-rule="evenodd"/>
                </svg>
                <span class="text-xs text-gray-500 font-mono truncate"
                      x-text="lat ? `${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}` : ''"></span>
            </div>
            <button
                type="button"
                @click="openModal()"
                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium shrink-0
                       hover:underline focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded px-1">
                {{ __('ui.location_edit') }}
            </button>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         MAP MODAL
         Full-screen on mobile · centered panel on sm+
    ═══════════════════════════════════════════════════════════════ --}}
    <div
        x-show="modalOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('ui.location_picker_title') }}"
        @keydown.escape.window="closeModal()">

        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            @click="closeModal()"
            aria-hidden="true">
        </div>

        {{-- Panel --}}
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
            x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100"
            x-transition:leave-end="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
            class="relative flex flex-col
                   w-full max-h-[95dvh] sm:max-h-[88vh]
                   sm:w-full sm:max-w-2xl
                   bg-white
                   rounded-t-3xl sm:rounded-2xl
                   shadow-2xl overflow-hidden">

            {{-- ── Modal header ── --}}
            <div class="flex items-center justify-between px-4 py-3.5 border-b border-gray-200 bg-white shrink-0">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <h2 class="text-base font-semibold text-gray-900">{{ __('ui.location_picker_title') }}</h2>
                </div>
                <button
                    type="button"
                    @click="closeModal()"
                    class="p-2 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100
                           transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    aria-label="{{ __('ui.cancel') }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- ── GPS / hint bar ── --}}
            <div class="px-4 py-2.5 bg-indigo-50 border-b border-indigo-100 shrink-0
                        flex flex-col sm:flex-row items-start sm:items-center gap-2">
                <p class="text-xs text-indigo-700 flex-1 leading-snug" x-text="strings.tapHint"></p>
                <button
                    type="button"
                    @click="useMyLocation()"
                    :disabled="gpsLoading"
                    class="flex items-center gap-2 px-3.5 py-2 text-sm font-medium
                           bg-white border border-indigo-200 text-indigo-700 rounded-xl
                           hover:bg-indigo-100 active:bg-indigo-200
                           transition-colors shrink-0
                           disabled:opacity-50 disabled:cursor-not-allowed
                           focus:outline-none focus:ring-2 focus:ring-indigo-500">

                    {{-- GPS icon --}}
                    <svg x-show="!gpsLoading" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 2a10 10 0 100 20A10 10 0 0012 2z" opacity="0.3"/>
                    </svg>

                    {{-- Spinner --}}
                    <svg x-show="gpsLoading" class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>

                    <span x-text="gpsLoading ? strings.getting : strings.useGps"></span>
                </button>
            </div>

            {{-- ── GPS / permission error ── --}}
            <div
                x-show="gpsError"
                x-transition
                class="px-4 py-2.5 bg-amber-50 border-b border-amber-200 shrink-0
                       flex items-start gap-2 text-xs text-amber-800">
                <svg class="w-4 h-4 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                </svg>
                <span x-text="gpsError"></span>
            </div>

            {{-- ── Map container ── --}}
            <div class="relative flex-1 min-h-0" style="min-height: 240px;">
                {{-- Loading overlay --}}
                <div
                    x-show="mapLoading"
                    class="absolute inset-0 flex flex-col items-center justify-center
                           bg-gray-50 z-10 gap-3">
                    <svg class="w-8 h-8 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <p class="text-xs text-gray-400">{{ __('ui.location_loading_map') }}</p>
                </div>

                {{-- Leaflet map mounts here --}}
                <div x-ref="mapEl" class="absolute inset-0"></div>
            </div>

            {{-- ── Footer: coords + description + actions ── --}}
            <div class="px-4 pt-3 pb-4 border-t border-gray-200 bg-white shrink-0 space-y-3">

                {{-- Coordinates display --}}
                <div class="text-center text-xs font-mono min-h-[1.25rem]">
                    <template x-if="tempLat">
                        <span class="text-gray-600">
                            <span class="text-green-500 font-bold mr-1">✓</span>
                            <span x-text="`${parseFloat(tempLat).toFixed(6)},  ${parseFloat(tempLng).toFixed(6)}`"></span>
                        </span>
                    </template>
                    <template x-if="!tempLat">
                        <span class="text-gray-400" x-text="strings.noPin"></span>
                    </template>
                </div>

                {{-- Optional location description --}}
                <input
                    type="text"
                    x-model="tempDesc"
                    :placeholder="strings.descHint"
                    maxlength="255"
                    class="w-full text-sm border-gray-200 rounded-xl bg-gray-50
                           focus:ring-indigo-500 focus:border-indigo-500 placeholder-gray-400
                           focus:outline-none focus:ring-2">

                {{-- Action buttons --}}
                <div class="flex gap-2.5">
                    <button
                        type="button"
                        @click="closeModal()"
                        class="flex-1 py-3 border border-gray-200 rounded-xl
                               text-sm font-medium text-gray-600
                               hover:bg-gray-50 active:bg-gray-100 transition-colors
                               focus:outline-none focus:ring-2 focus:ring-gray-400">
                        {{ __('ui.cancel') }}
                    </button>

                    <button
                        type="button"
                        @click="confirmLocation()"
                        :disabled="!tempLat"
                        :class="tempLat
                            ? 'bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white shadow-sm shadow-indigo-200'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                        class="flex-1 py-3 rounded-xl text-sm font-semibold transition-colors
                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        {{ __('ui.location_confirm') }}
                    </button>
                </div>
            </div>

        </div>{{-- /panel --}}
    </div>{{-- /modal --}}

</div>{{-- /x-data --}}


{{-- ════════════════════════════════════════════════════════
     Alpine component definition
     Loaded once per page; safe to call multiple times.
════════════════════════════════════════════════════════ --}}
@once
<script>
function mapLocationPicker(opts) {
    return {
        // ── Committed (form) state ──
        lat:  opts.initLat  ?? null,
        lng:  opts.initLng  ?? null,
        desc: opts.initDesc ?? '',

        // ── Working (in-modal) state ──
        tempLat:  opts.initLat  ?? null,
        tempLng:  opts.initLng  ?? null,
        tempDesc: opts.initDesc ?? '',

        // ── UI flags ──
        modalOpen:  false,
        mapLoading: false,
        gpsLoading: false,
        gpsError:   '',
        strings:    opts.strings,

        // ── Leaflet instances ──
        _map:     null,
        _marker:  null,
        _minimap: null,

        // ─────────────────────────────────────────────────
        // Lifecycle
        // ─────────────────────────────────────────────────
        async boot() {
            if (this.lat !== null) {
                try {
                    await this._ensureLeaflet();
                    await this.$nextTick();
                    this._initMiniMap();
                } catch (e) {
                    console.warn('[MapPicker] Could not load Leaflet for mini-map preview.', e);
                }
            }
        },

        // ─────────────────────────────────────────────────
        // Modal open / close
        // ─────────────────────────────────────────────────
        async openModal() {
            // Reset working state to committed state
            this.tempLat  = this.lat;
            this.tempLng  = this.lng;
            this.tempDesc = this.desc;
            this.gpsError = '';

            this.modalOpen  = true;
            this.mapLoading = true;

            try {
                await this._ensureLeaflet();
            } catch (e) {
                console.error('[MapPicker] Failed to load Leaflet.', e);
                this.mapLoading = false;
                this.gpsError = 'Map failed to load. Check your connection and try again.';
                return;
            }

            this.mapLoading = false;
            await this.$nextTick();
            this._initMap();
        },

        closeModal() {
            this.modalOpen = false;
            this.gpsError  = '';
        },

        // ─────────────────────────────────────────────────
        // Confirm / clear
        // ─────────────────────────────────────────────────
        confirmLocation() {
            if (this.tempLat === null) return;
            this.lat  = this.tempLat;
            this.lng  = this.tempLng;
            this.desc = this.tempDesc;
            this.modalOpen = false;
            this.$nextTick(() => this._initMiniMap());
        },

        clearLocation() {
            this.lat = this.lng = this.desc = null;
            this.tempLat = this.tempLng = null;
            this.tempDesc = '';
            if (this._minimap) {
                this._minimap.remove();
                this._minimap = null;
            }
        },

        // ─────────────────────────────────────────────────
        // GPS
        // ─────────────────────────────────────────────────
        useMyLocation() {
            if (!navigator.geolocation) {
                this.gpsError = this.strings.gpsUnsupported;
                return;
            }
            this.gpsLoading = true;
            this.gpsError   = '';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.gpsLoading = false;
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    this.tempLat = this._round(lat);
                    this.tempLng = this._round(lng);
                    if (this._map) {
                        this._map.setView([lat, lng], 17, { animate: true });
                        this._placeMarker(lat, lng);
                    }
                },
                (err) => {
                    this.gpsLoading = false;
                    const map = {
                        1: this.strings.gpsDenied,
                        2: this.strings.gpsUnavailable,
                        3: this.strings.gpsTimeout,
                    };
                    this.gpsError = map[err.code] || this.strings.gpsError;
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 }
            );
        },

        // ─────────────────────────────────────────────────
        // Map initialisation
        // ─────────────────────────────────────────────────
        _initMap() {
            const el = this.$refs.mapEl;
            if (!el || !window.L) return;

            if (this._map) {
                // Already initialised — just resize to fill the (newly visible) container
                this._map.invalidateSize();
                return;
            }

            const lat = this.tempLat ?? opts.defaultLat;
            const lng = this.tempLng ?? opts.defaultLng;
            const zoom = this.tempLat ? 15 : 13;

            this._map = L.map(el, {
                zoomControl: true,
                tap: true,          // enable touch tap events
                tapTolerance: 15,   // more forgiving on mobile
            }).setView([lat, lng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(this._map);

            // Place marker if we already have coords
            if (this.tempLat !== null) {
                this._placeMarker(this.tempLat, this.tempLng);
            }

            // Tap / click to drop pin
            this._map.on('click', (e) => {
                this.tempLat = this._round(e.latlng.lat);
                this.tempLng = this._round(e.latlng.lng);
                this._placeMarker(e.latlng.lat, e.latlng.lng);
            });
        },

        _placeMarker(lat, lng) {
            if (!this._map) return;
            if (this._marker) {
                this._marker.setLatLng([lat, lng]);
            } else {
                this._marker = L.marker([lat, lng], {
                    draggable: true,
                    autoPan:   true,
                }).addTo(this._map);

                this._marker.on('dragend', () => {
                    const p = this._marker.getLatLng();
                    this.tempLat = this._round(p.lat);
                    this.tempLng = this._round(p.lng);
                });
            }
        },

        // ─────────────────────────────────────────────────
        // Mini-map (inline form preview)
        // ─────────────────────────────────────────────────
        _initMiniMap() {
            const el = this.$refs.minimap;
            if (!el || !window.L || this.lat === null) return;

            // Destroy old instance first
            if (this._minimap) {
                this._minimap.remove();
                this._minimap = null;
            }

            this._minimap = L.map(el, {
                zoomControl:        false,
                dragging:           false,
                touchZoom:          false,
                scrollWheelZoom:    false,
                doubleClickZoom:    false,
                boxZoom:            false,
                keyboard:           false,
                attributionControl: false,
            }).setView([this.lat, this.lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(this._minimap);

            L.marker([this.lat, this.lng]).addTo(this._minimap);
        },

        // ─────────────────────────────────────────────────
        // Leaflet lazy loader
        // ─────────────────────────────────────────────────
        _ensureLeaflet() {
            if (window.L) return Promise.resolve();

            // CSS — inject once
            if (!document.querySelector('link[data-leaflet-css]')) {
                const link = document.createElement('link');
                link.rel            = 'stylesheet';
                link.href           = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                link.dataset.leafletCss = '';
                document.head.appendChild(link);
            }

            // JS — resolve when loaded, reject on network/parse error
            return new Promise((resolve, reject) => {
                const script  = document.createElement('script');
                script.src    = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        // ─────────────────────────────────────────────────
        // Utilities
        // ─────────────────────────────────────────────────
        _round(v) {
            return Math.round(v * 1e7) / 1e7;
        },
    };
}
</script>
@endonce
