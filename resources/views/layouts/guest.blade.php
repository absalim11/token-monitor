<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            tosca: '#20B2AA',
                            'tosca-dark': '#008080',
                            'tosca-light': '#48D1CC',
                        }
                    }
                }
            };
        </script>
        <script>
            (function () {
                const storedTheme = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const useDark = storedTheme ? storedTheme === 'dark' : prefersDark;

                document.documentElement.classList.toggle('dark', useDark);
            })();
        </script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
        @php($isLoginPage = request()->routeIs('login'))

        <div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,_rgba(32,178,170,0.24),_transparent_34%),linear-gradient(180deg,_#f7fbfb_0%,_#eef4f4_45%,_#e5ecec_100%)] dark:bg-[radial-gradient(circle_at_top_left,_rgba(72,209,204,0.16),_transparent_32%),linear-gradient(180deg,_#0f1720_0%,_#111827_48%,_#0b1220_100%)]">
            <div class="absolute inset-0 opacity-40 dark:opacity-20" style="background-image: linear-gradient(rgba(15,23,42,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(15,23,42,0.05) 1px, transparent 1px); background-size: 28px 28px;"></div>
            <div class="relative min-h-screen flex items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
                @if ($isLoginPage)
                    <div class="w-full max-w-6xl">
                        {{ $slot }}
                    </div>
                @else
                    <div class="w-full max-w-md">
                        <div class="mb-6 text-center">
                            <h1 class="text-3xl font-bold text-tosca">{{ config('app.name') }}</h1>
                        </div>

                        <div class="px-6 py-5 bg-white/95 dark:bg-gray-800/95 shadow-xl shadow-black/5 backdrop-blur-sm overflow-hidden rounded-2xl border border-white/60 dark:border-gray-700/80 transition-colors duration-200">
                            {{ $slot }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>
