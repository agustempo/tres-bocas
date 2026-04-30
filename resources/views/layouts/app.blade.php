<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">

        {{-- ── Anti-FOUC: aplica dark class ANTES de cualquier render ── --}}
        <script>
            (function () {
                try {
                    var t = localStorage.getItem('theme') || 'system';
                    var d = t === 'dark' ||
                            (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (d) document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Isla.Ar') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">
        <link rel="icon" href="/favicon-16x16.png" type="image/png" sizes="16x16">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Google Analytics -->
        @production
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-BF5M168RX6"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-BF5M168RX6');
        </script>
        @endproduction
    </head>
    <body class="font-sans antialiased bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100">

        @include('layouts.navigation')

        <main>
            {{ $slot }}
        </main>

    </body>
</html>
