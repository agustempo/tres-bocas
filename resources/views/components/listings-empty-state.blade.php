<div
    x-data="{ visible: false }"
    x-init="setTimeout(() => visible = true, 80)"
    x-show="visible"
    x-transition:enter="transition ease-out duration-700"
    x-transition:enter-start="opacity-0 translate-y-3"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="py-20 sm:py-32 flex flex-col items-center text-center max-w-md mx-auto px-6"
    role="status"
    aria-live="polite"
>
    {{-- Logo --}}
    <div class="w-24 h-24 flex items-center justify-center mb-7">
        <x-logo variant="icon" class="w-24 h-24" />
    </div>

    {{-- Label --}}
    <span class="text-xs font-semibold tracking-widest text-rose-400 uppercase mb-3 dark:text-rose-300">
        {{ __('ui.listings_empty_soon') }}
    </span>

    {{-- Primary message --}}
    <p class="text-gray-800 dark:text-gray-200 text-lg sm:text-xl font-medium leading-snug mb-4">
        {{ __('ui.listings_empty_primary') }}
    </p>

    {{-- Secondary message --}}
    <p class="text-gray-500 dark:text-gray-400 text-sm sm:text-base leading-relaxed mb-8">
        {{ __('ui.listings_empty_secondary') }}
    </p>

    {{-- Divider + optional note --}}
    <p class="text-gray-400 dark:text-gray-600 text-xs sm:text-sm border-t border-gray-100 dark:border-gray-800 pt-6 w-full">
        {{ __('ui.listings_empty_cta') }}
    </p>
</div>
