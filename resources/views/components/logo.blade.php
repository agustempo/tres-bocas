{{--
    x-logo component
    Props:
      variant : 'icon' (solo el icono, default) | 'full' (icono + texto)
      class   : clases adicionales de Tailwind
--}}
@props(['variant' => 'icon', 'class' => ''])

@if($variant === 'full')
    {{-- Full: icono + texto "Isla.Ar" --}}
    <span class="flex items-center gap-2 {{ $class }}">
        {{-- Icono: versión light (oculto en dark) --}}
        <img src="{{ asset('images/logo-icon.svg') }}"
             alt="{{ config('app.name') }}"
             class="h-9 w-9 shrink-0 dark:hidden"
             width="36" height="36">
        {{-- Icono: versión dark (oculto en light) --}}
        <img src="{{ asset('images/logo-icon-dark.svg') }}"
             alt="{{ config('app.name') }}"
             class="h-9 w-9 shrink-0 hidden dark:block"
             width="36" height="36">
        <span class="text-lg font-bold tracking-wide text-[#147a72] dark:text-teal-400">
            {{ config('app.name') }}
        </span>
    </span>
@else
    {{-- Solo ícono --}}
    <img src="{{ asset('images/logo-icon.svg') }}"
         alt="{{ config('app.name') }}"
         class="dark:hidden {{ $class }}"
         width="40" height="40">
    <img src="{{ asset('images/logo-icon-dark.svg') }}"
         alt="{{ config('app.name') }}"
         class="hidden dark:block {{ $class }}"
         width="40" height="40">
@endif
