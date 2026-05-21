<header class="sticky top-0 z-50 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-black/20">

    {{-- ── DESKTOP ROW ── --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 h-16">

            {{-- Logo --}}
            <a href="{{ route('home') }}"
               class="shrink-0 hover:opacity-80 transition-opacity">
                <x-logo variant="icon" class="h-10 w-10" />
            </a>

            {{-- ── Live search — desktop ── --}}
            <div class="hidden sm:flex flex-1 max-w-xl mx-auto relative"
                 x-data="{
                     q: '',
                     results: { docks: [], listings: [] },
                     loading: false,
                     focused: false,
                     activeIdx: -1,
                     _timer: null,
                     get allItems()        { return [...(this.results.docks||[]), ...(this.results.listings||[])]; },
                     get showDropdown()    { return this.focused; },
                     get showQuickAccess() { return this.focused && this.q.length < 2; },
                     get showResults()     { return this.q.length >= 2 && this.allItems.length > 0; },
                     get showEmpty()       { return this.q.length >= 2 && !this.loading && this.allItems.length === 0; },
                     get quickMuelles()    { return (window.deltaMuelles    || []).slice(0, 2); },
                     get quickCategories() { return (window.deltaCategories || []).slice(0, 2); },
                     onInput() {
                         this.activeIdx = -1;
                         clearTimeout(this._timer);
                         if (this.q.length < 2) { this.results = {docks:[],listings:[]}; this.loading = false; return; }
                         this.loading = true;
                         this._timer = setTimeout(() => this.doFetch(), 250);
                     },
                     async doFetch() {
                         try {
                             const r = await fetch('/search?q=' + encodeURIComponent(this.q));
                             this.results = await r.json();
                         } catch(e) { this.results = {docks:[],listings:[]}; }
                         this.loading = false;
                     },
                     onKeydown(e) {
                         const items = this.allItems;
                         if (e.key === 'Escape') { this.focused = false; return; }
                         if (!items.length) return;
                         if (e.key === 'ArrowDown') { e.preventDefault(); this.activeIdx = Math.min(this.activeIdx+1, items.length-1); return; }
                         if (e.key === 'ArrowUp')   { e.preventDefault(); this.activeIdx = Math.max(this.activeIdx-1, -1); return; }
                         if (e.key === 'Enter') {
                             e.preventDefault();
                             const t = this.activeIdx >= 0 ? items[this.activeIdx] : items[0];
                             if (t?.url) { window.location.href = t.url; this.clear(); }
                         }
                     },
                     go(item) { if (item.url) window.location.href = item.url; this.clear(); },
                     clear() { this.q=''; this.results={docks:[],listings:[]}; this.focused=false; this.activeIdx=-1; clearTimeout(this._timer); },
                     highlight(text) {
                         if (!this.q || !text) return ('' + text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                         const safe = ('' + text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                         const idx = safe.toLowerCase().indexOf(this.q.toLowerCase());
                         if (idx < 0) return safe;
                         return safe.slice(0,idx) + '<strong class=\'font-semibold text-gray-900 dark:text-white\'>' + safe.slice(idx, idx+this.q.length) + '</strong>' + safe.slice(idx+this.q.length);
                     },
                     catColor(slug) {
                         const m = { food:'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300', accommodation:'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300', activities:'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300', transport:'bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300', services:'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300' };
                         return m[slug] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400';
                     }
                 }"
                 @click.outside="focused = false">

                {{-- Input --}}
                <div class="relative w-full">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 pointer-events-none"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           role="combobox"
                           aria-expanded="focused"
                           aria-autocomplete="list"
                           x-model="q"
                           @input="onInput()"
                           @focus="focused = true"
                           @keydown="onKeydown($event)"
                           placeholder="{{ __('search.placeholder') }}"
                           class="w-full pl-10 pr-9 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                                  text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500
                                  rounded-full focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                                  focus:bg-white dark:focus:bg-gray-700 transition-all">
                    {{-- Spinner --}}
                    <svg x-show="loading" x-cloak
                         class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 animate-spin"
                         fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    {{-- Clear --}}
                    <button x-show="q.length > 0 && !loading" x-cloak
                            @click="clear()"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors text-base leading-none">
                        ×
                    </button>
                </div>

                {{-- Dropdown --}}
                <div x-show="showDropdown" x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     role="listbox"
                     class="absolute top-full left-0 right-0 mt-1.5 bg-white dark:bg-gray-800 rounded-2xl shadow-xl
                            border border-gray-100 dark:border-gray-700 overflow-hidden z-50">

                    {{-- Quick access --}}
                    <template x-if="showQuickAccess">
                        <div class="p-2">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-3 py-1.5">
                                {{ __('search.quick_access_label') }}
                            </p>
                            <template x-for="m in quickMuelles" :key="'qm' + m.id">
                                <a :href="'/movilidad/muelles/' + m.slug"
                                   @click="clear()"
                                   class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl
                                          hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-gray-400 text-sm">⛵</span>
                                        <span class="text-sm text-gray-800 dark:text-gray-200 truncate" x-text="m.nombre"></span>
                                        <span x-show="m.zona" class="text-xs text-gray-400 truncate" x-text="m.zona"></span>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full
                                                 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                                        {{ __('search.chip_dock') }}
                                    </span>
                                </a>
                            </template>
                            <template x-for="c in quickCategories" :key="'qc' + c.id">
                                <a :href="'/servicios?category=' + c.slug"
                                   @click="clear()"
                                   class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl
                                          hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer">
                                    <span class="text-sm text-gray-800 dark:text-gray-200" x-text="c.name"></span>
                                    <span class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full
                                                 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400"
                                          x-text="c.name"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    {{-- Results --}}
                    <template x-if="showResults">
                        <div class="p-2">
                            {{-- Docks --}}
                            <template x-if="results.docks.length > 0">
                                <div class="mb-1">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-3 py-1.5"
                                       aria-label="{{ __('search.section_docks') }}">
                                        {{ __('search.section_docks') }}
                                    </p>
                                    <template x-for="(item, i) in results.docks" :key="'d' + item.id">
                                        <button type="button"
                                                @click="go(item)"
                                                :class="activeIdx === i ? 'bg-gray-50 dark:bg-gray-700/50' : ''"
                                                class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl
                                                       hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm text-gray-800 dark:text-gray-200 truncate"
                                                   x-html="highlight(item.name)"></p>
                                                <p x-show="item.detail || item.community"
                                                   class="text-xs text-gray-400 dark:text-gray-500 truncate flex items-center gap-1">
                                                    <span x-show="item.detail" x-text="item.detail"></span>
                                                    <span x-show="item.community"
                                                          class="text-[10px] text-blue-400 dark:text-blue-500">
                                                        · {{ __('search.chip_community_dock') }}
                                                    </span>
                                                </p>
                                            </div>
                                            <span class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full
                                                         bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                                                {{ __('search.chip_dock') }}
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                            {{-- Listings --}}
                            <template x-if="results.listings.length > 0">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-3 py-1.5"
                                       aria-label="{{ __('search.section_listings') }}">
                                        {{ __('search.section_listings') }}
                                    </p>
                                    <template x-for="(item, i) in results.listings" :key="'l' + item.id">
                                        <button type="button"
                                                @click="go(item)"
                                                :class="activeIdx === (results.docks.length + i) ? 'bg-gray-50 dark:bg-gray-700/50' : ''"
                                                class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl
                                                       hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm text-gray-800 dark:text-gray-200 truncate"
                                                   x-html="highlight(item.title)"></p>
                                                <p x-show="item.detail"
                                                   class="text-xs text-gray-400 dark:text-gray-500 truncate"
                                                   x-text="item.detail"></p>
                                            </div>
                                            <span class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full"
                                                  :class="catColor(item.category_slug)"
                                                  x-text="item.category"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="showEmpty">
                        <div class="px-4 py-6 text-center">
                            <p class="text-2xl mb-2">🔍</p>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300"
                               x-text="`{{ __('search.empty_title') }}`.replace(':query', q)"></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('search.empty_hint') }}</p>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ── Right side ── --}}
            <div class="shrink-0 flex items-center gap-1.5 ml-auto sm:ml-0">

                {{-- Primary icon: Condiciones --}}
                <a href="{{ route('marea.index') }}"
                   title="{{ __('ui.nav_condiciones') }}"
                   class="flex items-center justify-center w-8 h-8 rounded-full border transition-colors
                          {{ request()->routeIs('marea.*')
                              ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400'
                              : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5c1.5-2.5 3-3.5 4.5-3.5S10.5 11 12 11s3-1 4.5-1 3 1 4.5 1M3 8c1.5-2.5 3-3.5 4.5-3.5S10.5 5.5 12 5.5s3-1 4.5-1 3 1 4.5 1M3 19c1.5-2.5 3-3.5 4.5-3.5S10.5 16.5 12 16.5s3-1 4.5-1 3 1 4.5 1"/>
                    </svg>
                </a>

                {{-- Primary icon: Horarios --}}
                <a href="{{ route('horarios.index') }}"
                   title="{{ __('ui.nav_horarios') }}"
                   class="flex items-center justify-center w-8 h-8 rounded-full border transition-colors
                          {{ request()->routeIs('horarios.*')
                              ? 'border-teal-300 dark:border-teal-700 bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400'
                              : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <path stroke-linecap="round" d="M12 7v5l3 2"/>
                    </svg>
                </a>

                {{-- ── Overflow hamburger menu ── --}}
                <div x-data="{ menuOpen: false }" class="relative">
                    <button @click="menuOpen = !menuOpen"
                            @keydown.escape.window="menuOpen = false"
                            title="Menú"
                            class="flex items-center justify-center w-8 h-8 rounded-full border border-gray-200 dark:border-gray-700
                                   bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400
                                   hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                        <svg x-show="!menuOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="menuOpen" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div x-show="menuOpen"
                         x-cloak
                         @click.outside="menuOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-2xl shadow-xl
                                border border-gray-100 dark:border-gray-700 overflow-hidden z-50 py-1.5">

                        {{-- Inicio --}}
                        <a href="{{ route('home') }}"
                           @click="menuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors
                                  {{ request()->routeIs('home')
                                      ? 'bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 font-medium'
                                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 21V12h6v9"/>
                            </svg>
                            {{ __('ui.home') }}
                        </a>

                        {{-- Horarios --}}
                        <a href="{{ route('horarios.index') }}"
                           @click="menuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors
                                  {{ request()->routeIs('horarios.*')
                                      ? 'bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 font-medium'
                                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" d="M12 7v5l3 2"/>
                            </svg>
                            {{ __('ui.nav_horarios') }}
                        </a>

                        {{-- Condiciones --}}
                        <a href="{{ route('marea.index') }}"
                           @click="menuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors
                                  {{ request()->routeIs('marea.*')
                                      ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium'
                                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5c1.5-2.5 3-3.5 4.5-3.5S10.5 11 12 11s3-1 4.5-1 3 1 4.5 1M3 8c1.5-2.5 3-3.5 4.5-3.5S10.5 5.5 12 5.5s3-1 4.5-1 3 1 4.5 1"/>
                            </svg>
                            {{ __('ui.nav_condiciones') }}
                        </a>

                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                        {{-- Servicios (beta) --}}
                        <a href="{{ route('servicios.index') }}"
                           @click="menuOpen = false"
                           class="block px-4 py-2.5 text-sm transition-colors
                                  {{ request()->routeIs('servicios.*') || request()->routeIs('listings.*')
                                      ? 'bg-gray-50 dark:bg-gray-700/50'
                                      : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="{{ request()->routeIs('servicios.*') || request()->routeIs('listings.*')
                                    ? 'text-gray-900 dark:text-gray-100 font-medium'
                                    : 'text-gray-700 dark:text-gray-300' }}">
                                    {{ __('ui.nav_servicios') }}
                                </span>
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full
                                             bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500">
                                    {{ __('ui.nav_services_badge') }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 leading-snug">
                                {{ __('ui.nav_services_beta_description') }}
                            </p>
                        </a>

                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                        {{-- Perfil (auth only) --}}
                        @auth
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('admin.movilidad.index') }}"
                                   @click="menuOpen = false"
                                   class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors
                                          {{ request()->routeIs('admin.*')
                                              ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 font-medium'
                                              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Admin
                                </a>
                            @endif
                            <a href="{{ route('profile.edit') }}"
                               @click="menuOpen = false"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors
                                      {{ request()->routeIs('profile.*')
                                          ? 'bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 font-medium'
                                          : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ __('ui.profile') }}
                            </a>
                        @endauth

                        @guest
                            <a href="{{ route('login') }}"
                               @click.prevent="$store.auth.show('login'); menuOpen = false"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300
                                      hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                {{ __('ui.login') }}
                            </a>
                            <a href="{{ route('register') }}"
                               @click.prevent="$store.auth.show('register'); menuOpen = false"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-teal-700 dark:text-teal-400
                                      hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-colors">
                                {{ __('ui.register') }}
                            </a>
                        @endguest

                    </div>
                </div>

                {{-- ── Theme selector ── --}}
                <div x-data="{ themeOpen: false }" class="relative">
                    <button @click="themeOpen = !themeOpen"
                            @keydown.escape="themeOpen = false"
                            title="Apariencia"
                            class="flex items-center justify-center w-8 h-8 rounded-full border border-gray-200 dark:border-gray-700
                                   bg-white dark:bg-gray-800 hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        <svg x-show="!$store.theme.isDark" x-cloak
                             class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707
                                     M17.657 17.657l.707.707M6.343 6.343l.707.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                        </svg>
                        <svg x-show="$store.theme.isDark"
                             class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    <div x-show="themeOpen" x-cloak @click.outside="themeOpen = false"
                         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden z-50">
                        <button @click="$store.theme.set('light'); themeOpen = false"
                                class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="$store.theme.current === 'light' ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-gray-700 dark:text-gray-300'">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707 M17.657 17.657l.707.707M6.343 6.343l.707.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                            </svg>
                            Claro
                        </button>
                        <button @click="$store.theme.set('system'); themeOpen = false"
                                class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="$store.theme.current === 'system' ? 'text-teal-600 dark:text-teal-400 font-semibold' : 'text-gray-700 dark:text-gray-300'">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Sistema
                        </button>
                        <button @click="$store.theme.set('dark'); themeOpen = false"
                                class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="$store.theme.current === 'dark' ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-700 dark:text-gray-300'">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            Oscuro
                        </button>
                    </div>
                </div>

                {{-- ── Language selector ── --}}
                <div x-data="{ langOpen: false }" class="relative">
                    <button @click="langOpen = !langOpen" @keydown.escape="langOpen = false"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium
                                   text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100
                                   border border-gray-200 dark:border-gray-700 rounded-full
                                   hover:border-gray-400 dark:hover:border-gray-500 transition-colors bg-white dark:bg-gray-800">
                        <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <span class="uppercase font-semibold text-xs">{{ app()->getLocale() }}</span>
                    </button>
                    <div x-show="langOpen" x-cloak @click.outside="langOpen = false"
                         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden z-50">
                        @foreach (['es' => 'Español', 'en' => 'English', 'pt' => 'Português', 'fr' => 'Français'] as $code => $label)
                            <a href="{{ route('lang.switch', $code) }}"
                               class="flex items-center justify-between px-4 py-2.5 text-sm transition-colors
                                      {{ app()->getLocale() === $code
                                          ? 'text-rose-600 dark:text-rose-400 font-semibold bg-rose-50 dark:bg-rose-900/20'
                                          : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                <span>{{ $label }}</span>
                                <span class="text-xs uppercase font-bold {{ app()->getLocale() === $code ? 'text-rose-500 dark:text-rose-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $code }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- ── Auth user ── --}}
                @auth
                    <div x-data="{ userOpen: false }" class="relative hidden sm:block">
                        <button @click="userOpen = !userOpen" @keydown.escape="userOpen = false"
                                class="flex items-center gap-2 pl-2 pr-3 py-1.5 border border-gray-200 dark:border-gray-700
                                       rounded-full hover:border-gray-400 dark:hover:border-gray-500 transition-colors
                                       text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-400 to-rose-400
                                        flex items-center justify-center text-white text-[10px] font-bold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            {{ Str::limit(Auth::user()->name, 14) }}
                            <svg class="w-3 h-3 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="userOpen" x-cloak @click.outside="userOpen = false"
                             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden z-50">
                            <a href="{{ route('reservations.index') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                {{ __('ui.reservations') }}
                            </a>
                            <div class="border-t border-gray-100 dark:border-gray-700"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full text-left px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    {{ __('ui.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="hidden sm:flex items-center gap-2">
                        <a href="{{ route('login') }}"
                           @click.prevent="$store.auth.show('login')"
                           class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                            {{ __('ui.login') }}
                        </a>
                        <a href="{{ route('register') }}"
                           @click.prevent="$store.auth.show('register')"
                           class="px-4 py-1.5 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900
                                  text-sm font-medium rounded-full hover:bg-gray-700 dark:hover:bg-gray-300 transition-colors">
                            {{ __('ui.register') }}
                        </a>
                    </div>
                @endauth

            </div>
        </div>
    </div>

    {{-- ── MOBILE SEARCH BAR ── --}}
    <div class="sm:hidden px-4 pb-3 relative"
         x-data="{
             q: '',
             results: { docks: [], listings: [] },
             loading: false,
             focused: false,
             activeIdx: -1,
             _timer: null,
             get allItems()        { return [...(this.results.docks||[]), ...(this.results.listings||[])]; },
             get showDropdown()    { return this.focused; },
             get showQuickAccess() { return this.focused && this.q.length < 2; },
             get showResults()     { return this.q.length >= 2 && this.allItems.length > 0; },
             get showEmpty()       { return this.q.length >= 2 && !this.loading && this.allItems.length === 0; },
             get quickMuelles()    { return (window.deltaMuelles    || []).slice(0, 2); },
             get quickCategories() { return (window.deltaCategories || []).slice(0, 2); },
             onInput() {
                 this.activeIdx = -1;
                 clearTimeout(this._timer);
                 if (this.q.length < 2) { this.results = {docks:[],listings:[]}; this.loading = false; return; }
                 this.loading = true;
                 this._timer = setTimeout(() => this.doFetch(), 250);
             },
             async doFetch() {
                 try {
                     const r = await fetch('/search?q=' + encodeURIComponent(this.q));
                     this.results = await r.json();
                 } catch(e) { this.results = {docks:[],listings:[]}; }
                 this.loading = false;
             },
             onKeydown(e) {
                 const items = this.allItems;
                 if (e.key === 'Escape') { this.focused = false; return; }
                 if (!items.length) return;
                 if (e.key === 'ArrowDown') { e.preventDefault(); this.activeIdx = Math.min(this.activeIdx+1, items.length-1); return; }
                 if (e.key === 'ArrowUp')   { e.preventDefault(); this.activeIdx = Math.max(this.activeIdx-1, -1); return; }
                 if (e.key === 'Enter') {
                     e.preventDefault();
                     const t = this.activeIdx >= 0 ? items[this.activeIdx] : items[0];
                     if (t?.url) { window.location.href = t.url; this.clear(); }
                 }
             },
             go(item) { if (item.url) window.location.href = item.url; this.clear(); },
             clear() { this.q=''; this.results={docks:[],listings:[]}; this.focused=false; this.activeIdx=-1; clearTimeout(this._timer); },
             highlight(text) {
                 if (!this.q || !text) return ('' + text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                 const safe = ('' + text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                 const idx = safe.toLowerCase().indexOf(this.q.toLowerCase());
                 if (idx < 0) return safe;
                 return safe.slice(0,idx) + '<strong class=\'font-semibold text-gray-900 dark:text-white\'>' + safe.slice(idx, idx+this.q.length) + '</strong>' + safe.slice(idx+this.q.length);
             },
             catColor(slug) {
                 const m = { food:'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300', accommodation:'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300', activities:'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300', transport:'bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300', services:'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300' };
                 return m[slug] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400';
             }
         }"
         @click.outside="focused = false">

        <div class="relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 pointer-events-none"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" role="combobox"
                   x-model="q"
                   @input="onInput()"
                   @focus="focused = true"
                   @keydown="onKeydown($event)"
                   placeholder="{{ __('search.placeholder') }}"
                   class="w-full pl-10 pr-9 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                          text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500
                          rounded-full focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                          focus:bg-white dark:focus:bg-gray-700 transition-all">
            <svg x-show="loading" x-cloak class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 animate-spin"
                 fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <button x-show="q.length > 0 && !loading" x-cloak @click="clear()"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors text-base leading-none">×</button>
        </div>

        {{-- Mobile dropdown --}}
        <div x-show="showDropdown" x-cloak
             role="listbox"
             class="absolute left-4 right-4 top-full bg-white dark:bg-gray-800 rounded-2xl shadow-xl
                    border border-gray-100 dark:border-gray-700 overflow-hidden z-50">
            <template x-if="showQuickAccess">
                <div class="p-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-3 py-1.5">{{ __('search.quick_access_label') }}</p>
                    <template x-for="m in quickMuelles" :key="'mqm' + m.id">
                        <a :href="'/movilidad/muelles/' + m.slug" @click="clear()"
                           class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="text-gray-400 text-sm">⛵</span>
                                <span class="text-sm text-gray-800 dark:text-gray-200 truncate" x-text="m.nombre"></span>
                            </div>
                            <span class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">{{ __('search.chip_dock') }}</span>
                        </a>
                    </template>
                    <template x-for="c in quickCategories" :key="'mqc' + c.id">
                        <a :href="'/servicios?category=' + c.slug" @click="clear()"
                           class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="text-sm text-gray-800 dark:text-gray-200" x-text="c.name"></span>
                            <span class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400" x-text="c.name"></span>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="showResults">
                <div class="p-2">
                    <template x-if="results.docks.length > 0">
                        <div class="mb-1">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-3 py-1.5">{{ __('search.section_docks') }}</p>
                            <template x-for="(item, i) in results.docks" :key="'md' + item.id">
                                <button type="button" @click="go(item)" :class="activeIdx === i ? 'bg-gray-50 dark:bg-gray-700/50' : ''"
                                        class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-800 dark:text-gray-200 truncate" x-html="highlight(item.name)"></p>
                                        <p x-show="item.detail" class="text-xs text-gray-400 truncate" x-text="item.detail"></p>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">{{ __('search.chip_dock') }}</span>
                                </button>
                            </template>
                        </div>
                    </template>
                    <template x-if="results.listings.length > 0">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-3 py-1.5">{{ __('search.section_listings') }}</p>
                            <template x-for="(item, i) in results.listings" :key="'ml' + item.id">
                                <button type="button" @click="go(item)" :class="activeIdx === (results.docks.length + i) ? 'bg-gray-50 dark:bg-gray-700/50' : ''"
                                        class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-800 dark:text-gray-200 truncate" x-html="highlight(item.title)"></p>
                                        <p x-show="item.detail" class="text-xs text-gray-400 truncate" x-text="item.detail"></p>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full" :class="catColor(item.category_slug)" x-text="item.category"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="showEmpty">
                <div class="px-4 py-6 text-center">
                    <p class="text-2xl mb-2">🔍</p>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300"
                       x-text="`{{ __('search.empty_title') }}`.replace(':query', q)"></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('search.empty_hint') }}</p>
                </div>
            </template>
        </div>
    </div>

</header>
