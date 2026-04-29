{{--
    x-logo component
    Props:
      variant : 'full' (icono + texto) | 'icon' (solo el icono)
      class   : clases adicionales de Tailwind
--}}
@props(['variant' => 'full', 'class' => ''])

@if($variant === 'full')
    <img
        src="{{ asset('images/logo-full.svg') }}"
        alt="{{ config('app.name') }}"
        class="h-9 w-auto {{ $class }}"
        height="36"
    >
@else
    <img
        src="{{ asset('images/logo-icon.svg') }}"
        alt="{{ config('app.name') }}"
        class="h-8 w-8 {{ $class }}"
        width="32"
        height="32"
    >
@endif
