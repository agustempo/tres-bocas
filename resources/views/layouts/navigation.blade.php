<header class="sticky top-0 z-50 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-black/20"
        x-data="{ mobileOpen: false }">

    {{-- ── DESKTOP ROW ── --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4 h-16">

            {{-- Left: Branding --}}
            <a href="{{ route('home') }}"
               class="shrink-0 hover:opacity-80 transition-opacity">
                <x-logo variant="icon" class="h-10 w-10" />
            </a>

            {{-- Center: Nav links --}}
            <nav class="hidden sm:flex items-center gap-1 shrink-0">
                <a href="{{ route('marea.index') }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors
                          {{ request()->routeIs('marea.*')
                              ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
                              : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100' }}">
                    <span class="text-base leading-none">🌊</span>
                    Marea
                </a>
            </nav>

            {{-- Search --}}
            <form method="GET" action="{{ route('home') }}"
                  class="hidden sm:flex flex-1 max-w-xl mx-auto">
                <div class="relative w-full">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 pointer-events-none"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="{{ __('ui.search_placeholder') }}"
                           class="w-full pl-10 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                                  text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500
                                  rounded-full focus:outline-none focus:ring-2 focus:ring-rose-400
                                  focus:border-transparent focus:bg-white dark:focus:bg-gray-700 transition-all">
                </div>
            </form>

            {{-- Right: Theme + Language + User --}}
            <div class="shrink-0 flex items-center gap-2 ml-auto sm:ml-0">

                {{-- ── Theme selector ── --}}
                <div x-data="{ themeOpen: false }" class="relative">
                    <button @click="themeOpen = !themeOpen"
                            @keydown.escape="themeOpen = false"
                            title="Apariencia"
                            class="flex items-center justify-center w-8 h-8 rounded-full border border-gray-200 dark:border-gray-700
                                   bg-white dark:bg-gray-800 hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        {{-- Sol (modo claro activo o sistem en light) --}}
                        <svg x-show="!$store.theme.isDark" x-cloak
                             class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707
                                     M17.657 17.657l.707.707M6.343 6.343l.707.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                        </svg>
                        {{-- Luna (modo oscuro activo) --}}
                        <svg x-show="$store.theme.isDark"
                             class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>

                    <div x-show="themeOpen"
                         x-cloak
                         @click.outside="themeOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-800 rounded-2xl shadow-lg
                                border border-gray-100 dark:border-gray-700 overflow-hidden z-50">

                        {{-- Light --}}
                        <button @click="$store.theme.set('light'); themeOpen = false"
                                class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm transition-colors
                                       hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="$store.theme.current === 'light'
                                    ? 'text-amber-600 dark:text-amber-400 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300'">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707
                                         M17.657 17.657l.707.707M6.343 6.343l.707.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                            </svg>
                            Claro
                        </button>

                        {{-- System --}}
                        <button @click="$store.theme.set('system'); themeOpen = false"
                                class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm transition-colors
                                       hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="$store.theme.current === 'system'
                                    ? 'text-teal-600 dark:text-teal-400 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300'">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Sistema
                        </button>

                        {{-- Dark --}}
                        <button @click="$store.theme.set('dark'); themeOpen = false"
                                class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm transition-colors
                                       hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="$store.theme.current === 'dark'
                                    ? 'text-indigo-600 dark:text-indigo-400 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300'">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            Oscuro
                        </button>
                    </div>
                </div>

                {{-- ── Language selector ── --}}
                <div x-data="{ langOpen: false }" class="relative">
                    <button @click="langOpen = !langOpen"
                            @keydown.escape="langOpen = false"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium
                                   text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100
                                   border border-gray-200 dark:border-gray-700 rounded-full
                                   hover:border-gray-400 dark:hover:border-gray-500 transition-colors
                                   bg-white dark:bg-gray-800">
                        <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <span class="uppercase font-semibold text-xs">{{ app()->getLocale() }}</span>
                        <svg class="w-3 h-3 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="langOpen"
                         x-cloak
                         @click.outside="langOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 rounded-2xl shadow-lg
                                border border-gray-100 dark:border-gray-700 overflow-hidden z-50">
                        @foreach (['es' => 'Español', 'en' => 'English', 'pt' => 'Português', 'fr' => 'Français'] as $code => $label)
                            <a href="{{ route('lang.switch', $code) }}"
                               class="flex items-center justify-between px-4 py-2.5 text-sm transition-colors
                                      {{ app()->getLocale() === $code
                                          ? 'text-rose-600 dark:text-rose-400 font-semibold bg-rose-50 dark:bg-rose-900/20'
                                          : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                <span>{{ $label }}</span>
                                <span class="text-xs uppercase font-bold
                                             {{ app()->getLocale() === $code ? 'text-rose-500 dark:text-rose-400' : 'text-gray-400 dark:text-gray-500' }}">
                                    {{ $code }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- ── Auth user or login ── --}}
                @auth
                    <div x-data="{ userOpen: false }" class="relative hidden sm:block">
                        <button @click="userOpen = !userOpen"
                                @keydown.escape="userOpen = false"
                                class="flex items-center gap-2 pl-2 pr-3 py-1.5 border border-gray-200 dark:border-gray-700
                                       rounded-full hover:border-gray-400 dark:hover:border-gray-500 transition-colors
                                       text-sm font-medium text-gray-700 dark:text-gray-200
                                       bg-white dark:bg-gray-800">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-400 to-rose-400
                                        flex items-center justify-center text-white text-[10px] font-bold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            {{ Str::limit(Auth::user()->name, 14) }}
                            <svg class="w-3 h-3 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="userOpen"
                             x-cloak
                             @click.outside="userOpen = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 rounded-2xl shadow-lg
                                    border border-gray-100 dark:border-gray-700 overflow-hidden z-50">
                            <a href="{{ route('dashboard') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300
                                      hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                {{ __('ui.dashboard') }}
                            </a>
                            <a href="{{ route('reservations.index') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300
                                      hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                {{ __('ui.reservations') }}
                            </a>
                            <a href="{{ route('profile.edit') }}"
                               class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300
                                      hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                {{ __('ui.profile') }}
                            </a>
                            <div class="border-t border-gray-100 dark:border-gray-700"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full text-left px-4 py-2.5 text-sm text-red-600 dark:text-red-400
                                               hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
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

                {{-- Mobile hamburger --}}
                <button @click="mobileOpen = !mobileOpen"
                        class="sm:hidden p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ── MOBILE SEARCH BAR ── --}}
    <div class="sm:hidden px-4 pb-3">
        <form method="GET" action="{{ route('home') }}">
            <div class="relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="{{ __('ui.search_placeholder') }}"
                       class="w-full pl-10 pr-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                              text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500
                              rounded-full focus:outline-none focus:ring-2 focus:ring-rose-400
                              focus:border-transparent focus:bg-white dark:focus:bg-gray-700 transition-all">
            </div>
        </form>
    </div>

    {{-- ── MOBILE MENU ── --}}
    <div x-show="mobileOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="sm:hidden border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-1">

        <a href="{{ route('home') }}"
           class="block px-3 py-2 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
            {{ __('ui.home') }}
        </a>
        <a href="{{ route('listings.index') }}"
           class="block px-3 py-2 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
            {{ __('ui.listings') }}
        </a>
        <a href="{{ route('marea.index') }}"
           class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium
                  {{ request()->routeIs('marea.*')
                      ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
            <span>🌊</span> Marea
        </a>

        {{-- Mobile theme selector --}}
        <div class="px-3 py-2">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Apariencia</p>
            <div class="flex gap-2">
                <button @click="$store.theme.set('light')"
                        :class="$store.theme.current === 'light' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700'"
                        class="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707
                                 M17.657 17.657l.707.707M6.343 6.343l.707.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                    </svg>
                    Claro
                </button>
                <button @click="$store.theme.set('system')"
                        :class="$store.theme.current === 'system' ? 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 border-teal-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700'"
                        class="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Sistema
                </button>
                <button @click="$store.theme.set('dark')"
                        :class="$store.theme.current === 'dark' ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border-indigo-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700'"
                        class="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    Oscuro
                </button>
            </div>
        </div>

        @auth
            <a href="{{ route('dashboard') }}"
               class="block px-3 py-2 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                {{ __('ui.dashboard') }}
            </a>
            <a href="{{ route('reservations.index') }}"
               class="block px-3 py-2 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                {{ __('ui.reservations') }}
            </a>
            <a href="{{ route('profile.edit') }}"
               class="block px-3 py-2 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                {{ __('ui.profile') }}
            </a>
            <div class="pt-1 border-t border-gray-100 dark:border-gray-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-3 py-2 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                        {{ __('ui.logout') }}
                    </button>
                </form>
            </div>
        @else
            <div class="pt-1 border-t border-gray-100 dark:border-gray-800 flex gap-3 px-3 py-2">
                <a href="{{ route('login') }}"
                   @click.prevent="$store.auth.show('login'); mobileOpen = false"
                   class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                    {{ __('ui.login') }}
                </a>
                <a href="{{ route('register') }}"
                   @click.prevent="$store.auth.show('register'); mobileOpen = false"
                   class="text-sm font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300">
                    {{ __('ui.register') }}
                </a>
            </div>
        @endauth
    </div>

</header>
