{{--
    Auth Modal — Login & Register
    Controlled by Alpine store: $store.auth
    Included once in layouts/app.blade.php
--}}
<div x-data
     x-show="$store.auth.open"
     x-cloak
     class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center"
     role="dialog"
     aria-modal="true"
     :aria-label="$store.auth.form === 'login' ? 'Iniciar sesión' : 'Crear cuenta'">

    {{-- ── Backdrop ─────────────────────────────────────────────────────── --}}
    <div x-show="$store.auth.open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm"
         @click="$store.auth.hide()"
         aria-hidden="true">
    </div>

    {{-- ── Panel ───────────────────────────────────────────────────────── --}}
    <div x-show="$store.auth.open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
         class="relative z-10 w-full sm:max-w-md
                bg-white dark:bg-gray-900
                rounded-t-3xl sm:rounded-3xl
                shadow-2xl dark:shadow-black/60
                max-h-[92vh] overflow-y-auto"
         @click.stop
         @keydown.tab="
             const els = $el.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select,textarea,[tabindex]:not([tabindex=\'-1\'])');
             const first = els[0]; const last = els[els.length - 1];
             if ($event.shiftKey && document.activeElement === first) { $event.preventDefault(); last.focus(); }
             else if (!$event.shiftKey && document.activeElement === last) { $event.preventDefault(); first.focus(); }
         ">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 pt-5 pb-4">
            <button @click="$store.auth.hide()"
                    class="flex items-center gap-2 hover:opacity-80 transition-opacity"
                    aria-label="Volver al sitio">
                <x-logo variant="icon" class="h-7 w-7" />
                <span class="text-sm font-bold text-[#147a72] dark:text-teal-400">
                    {{ config('app.name') }}
                </span>
            </button>
            <button @click="$store.auth.hide()"
                    class="w-8 h-8 flex items-center justify-center rounded-full
                           bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700
                           text-gray-500 dark:text-gray-400 transition-colors"
                    aria-label="Cerrar">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Tab bar --}}
        <div class="px-6 flex border-b border-gray-100 dark:border-gray-800">
            <button @click="$store.auth.switchTo('login')"
                    class="pb-3 mr-6 text-sm font-semibold border-b-2 -mb-px transition-colors duration-150"
                    :class="$store.auth.form === 'login'
                        ? 'border-[#147a72] dark:border-teal-400 text-[#147a72] dark:text-teal-400'
                        : 'border-transparent text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                {{ __('ui.login') }}
            </button>
            <button @click="$store.auth.switchTo('register')"
                    class="pb-3 text-sm font-semibold border-b-2 -mb-px transition-colors duration-150"
                    :class="$store.auth.form === 'register'
                        ? 'border-[#147a72] dark:border-teal-400 text-[#147a72] dark:text-teal-400'
                        : 'border-transparent text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                {{ __('ui.register') }}
            </button>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- LOGIN FORM                                                     --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="$store.auth.form === 'login'"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-x-2"
             x-transition:enter-end="opacity-100 translate-x-0"
             class="px-6 py-5 space-y-4">

            {{-- General / network error --}}
            <div x-show="$store.auth.errors._"
                 class="flex items-center gap-2.5 p-3 rounded-xl text-sm
                        bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                        text-red-700 dark:text-red-400">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                </svg>
                <span x-text="$store.auth.errors._?.[0]"></span>
            </div>

            {{-- Email --}}
            <div>
                <label for="modal-login-email"
                       class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Email
                </label>
                <input id="modal-login-email"
                       type="email"
                       x-model="$store.auth.loginEmail"
                       data-auth-login-first
                       autocomplete="email"
                       placeholder="tu@email.com"
                       @keydown.enter="$store.auth.submitLogin()"
                       :class="$store.auth.errors.email
                           ? 'border-red-400 dark:border-red-500'
                           : 'border-gray-200 dark:border-gray-700'"
                       class="w-full px-4 py-2.5 text-sm rounded-xl border
                              bg-gray-50 dark:bg-gray-800
                              text-gray-900 dark:text-gray-100
                              placeholder-gray-400 dark:placeholder-gray-500
                              focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                              transition-colors duration-150">
                <p x-show="$store.auth.errors.email"
                   x-text="$store.auth.errors.email?.[0]"
                   class="mt-1.5 text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Password --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="modal-login-password"
                           class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Contraseña
                    </label>
                    <a href="{{ route('password.request') }}"
                       class="text-xs text-[#147a72] dark:text-teal-400 hover:underline transition-colors">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
                <input id="modal-login-password"
                       type="password"
                       x-model="$store.auth.loginPassword"
                       autocomplete="current-password"
                       @keydown.enter="$store.auth.submitLogin()"
                       :class="$store.auth.errors.password
                           ? 'border-red-400 dark:border-red-500'
                           : 'border-gray-200 dark:border-gray-700'"
                       class="w-full px-4 py-2.5 text-sm rounded-xl border
                              bg-gray-50 dark:bg-gray-800
                              text-gray-900 dark:text-gray-100
                              focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                              transition-colors duration-150">
                <p x-show="$store.auth.errors.password"
                   x-text="$store.auth.errors.password?.[0]"
                   class="mt-1.5 text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Remember me --}}
            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                <input type="checkbox"
                       x-model="$store.auth.loginRemember"
                       class="rounded border-gray-300 dark:border-gray-600
                              text-teal-600 focus:ring-teal-400
                              dark:bg-gray-800 dark:checked:bg-teal-600">
                <span class="text-sm text-gray-600 dark:text-gray-400">Recordarme</span>
            </label>

            {{-- Submit --}}
            <button @click="$store.auth.submitLogin()"
                    :disabled="$store.auth.loading"
                    class="w-full py-3 rounded-xl text-sm font-semibold text-white
                           bg-[#147a72] hover:bg-[#0e6b63] dark:bg-teal-600 dark:hover:bg-teal-500
                           focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900
                           disabled:opacity-60 disabled:cursor-not-allowed
                           transition-all duration-150 shadow-sm">
                <span x-show="!$store.auth.loading">{{ __('ui.login') }}</span>
                <span x-show="$store.auth.loading" x-cloak
                      class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    Entrando…
                </span>
            </button>

            {{-- Switch --}}
            <p class="text-center text-sm text-gray-500 dark:text-gray-400 pb-1">
                ¿No tenés cuenta?
                <button @click="$store.auth.switchTo('register')"
                        class="ml-1 font-semibold text-[#147a72] dark:text-teal-400 hover:underline">
                    Registrate →
                </button>
            </p>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- REGISTER FORM                                                  --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="$store.auth.form === 'register'"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-x-2"
             x-transition:enter-end="opacity-100 translate-x-0"
             class="px-6 py-5 space-y-4">

            {{-- General / network error --}}
            <div x-show="$store.auth.errors._"
                 class="flex items-center gap-2.5 p-3 rounded-xl text-sm
                        bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                        text-red-700 dark:text-red-400">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                </svg>
                <span x-text="$store.auth.errors._?.[0]"></span>
            </div>

            {{-- Name --}}
            <div>
                <label for="modal-reg-name"
                       class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Nombre completo
                </label>
                <input id="modal-reg-name"
                       type="text"
                       x-model="$store.auth.regName"
                       data-auth-reg-first
                       autocomplete="name"
                       placeholder="Tu nombre"
                       :class="$store.auth.errors.name
                           ? 'border-red-400 dark:border-red-500'
                           : 'border-gray-200 dark:border-gray-700'"
                       class="w-full px-4 py-2.5 text-sm rounded-xl border
                              bg-gray-50 dark:bg-gray-800
                              text-gray-900 dark:text-gray-100
                              placeholder-gray-400 dark:placeholder-gray-500
                              focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                              transition-colors duration-150">
                <p x-show="$store.auth.errors.name"
                   x-text="$store.auth.errors.name?.[0]"
                   class="mt-1.5 text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Email --}}
            <div>
                <label for="modal-reg-email"
                       class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Email
                </label>
                <input id="modal-reg-email"
                       type="email"
                       x-model="$store.auth.regEmail"
                       autocomplete="email"
                       placeholder="tu@email.com"
                       :class="$store.auth.errors.email
                           ? 'border-red-400 dark:border-red-500'
                           : 'border-gray-200 dark:border-gray-700'"
                       class="w-full px-4 py-2.5 text-sm rounded-xl border
                              bg-gray-50 dark:bg-gray-800
                              text-gray-900 dark:text-gray-100
                              placeholder-gray-400 dark:placeholder-gray-500
                              focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                              transition-colors duration-150">
                <p x-show="$store.auth.errors.email"
                   x-text="$store.auth.errors.email?.[0]"
                   class="mt-1.5 text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Password --}}
            <div>
                <label for="modal-reg-password"
                       class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Contraseña <span class="text-gray-400 dark:text-gray-500 font-normal text-xs">(mín. 8 caracteres)</span>
                </label>
                <input id="modal-reg-password"
                       type="password"
                       x-model="$store.auth.regPassword"
                       autocomplete="new-password"
                       :class="$store.auth.errors.password
                           ? 'border-red-400 dark:border-red-500'
                           : 'border-gray-200 dark:border-gray-700'"
                       class="w-full px-4 py-2.5 text-sm rounded-xl border
                              bg-gray-50 dark:bg-gray-800
                              text-gray-900 dark:text-gray-100
                              focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                              transition-colors duration-150">
                <p x-show="$store.auth.errors.password"
                   x-text="$store.auth.errors.password?.[0]"
                   class="mt-1.5 text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Confirm password --}}
            <div>
                <label for="modal-reg-confirm"
                       class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Confirmá tu contraseña
                </label>
                <input id="modal-reg-confirm"
                       type="password"
                       x-model="$store.auth.regConfirm"
                       autocomplete="new-password"
                       @keydown.enter="$store.auth.submitRegister()"
                       :class="$store.auth.errors.password_confirmation
                           ? 'border-red-400 dark:border-red-500'
                           : 'border-gray-200 dark:border-gray-700'"
                       class="w-full px-4 py-2.5 text-sm rounded-xl border
                              bg-gray-50 dark:bg-gray-800
                              text-gray-900 dark:text-gray-100
                              focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                              transition-colors duration-150">
                <p x-show="$store.auth.errors.password_confirmation"
                   x-text="$store.auth.errors.password_confirmation?.[0]"
                   class="mt-1.5 text-xs text-red-600 dark:text-red-400"></p>
            </div>

            {{-- Submit --}}
            <button @click="$store.auth.submitRegister()"
                    :disabled="$store.auth.loading"
                    class="w-full py-3 rounded-xl text-sm font-semibold text-white
                           bg-[#147a72] hover:bg-[#0e6b63] dark:bg-teal-600 dark:hover:bg-teal-500
                           focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900
                           disabled:opacity-60 disabled:cursor-not-allowed
                           transition-all duration-150 shadow-sm">
                <span x-show="!$store.auth.loading">Crear cuenta</span>
                <span x-show="$store.auth.loading" x-cloak
                      class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    Creando cuenta…
                </span>
            </button>

            {{-- Switch --}}
            <p class="text-center text-sm text-gray-500 dark:text-gray-400 pb-1">
                ¿Ya tenés cuenta?
                <button @click="$store.auth.switchTo('login')"
                        class="ml-1 font-semibold text-[#147a72] dark:text-teal-400 hover:underline">
                    Iniciá sesión →
                </button>
            </p>
        </div>

    </div>{{-- /panel --}}
</div>{{-- /modal --}}
