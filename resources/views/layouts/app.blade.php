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
        
         @vite(['resources/css/app.css', 'resources/css/plataforma.css', 'resources/js/app.js'])
    </head>
    <body class="body-app">
        <div class="min-h-screen body-app">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            <div class="layout">
        @auth
    @if(auth()->user()->role === 'admin')
        @include('partials.sidebar-admin')
    @else
        @include('partials.sidebar-user')
    @endif
@endauth

        <main>
           
            {{ $slot }}
        </main>
    </div>
        </div>
    </body>
</html>
