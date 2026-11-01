<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Maquinas') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/" wire:navigate class="flex items-center gap-1 no-underline sm:gap-3">
                    <img
                        src="{{ asset('images/branding/ipn-escudo.png') }}"
                        alt="Escudo del Instituto Politécnico Nacional"
                        class="block h-16 w-16 object-contain"
                    >
                    <span class="h-10 border-l border-gray-300" aria-hidden="true"></span>
                    <span class="flex items-center" role="img" aria-label="Emblema de UPIIZ">
                        <x-application-logo />
                    </span>
                    <span class="flex flex-col leading-tight">
                        <span class="text-xl font-bold leading-tight text-[#103b73] sm:text-2xl">UPIIZ</span>
                        <span class="hidden text-sm leading-tight text-slate-500 sm:block">Campus Zacatecas</span>
                    </span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
