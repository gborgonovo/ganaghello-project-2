<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BiG-Log</title>
    <x-pwa-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-paper text-ink">

<div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
    <div class="mb-8 text-center">
        <div class="text-2xl font-semibold text-salvia-dark tracking-tight">BiG-Log</div>
        <div class="text-xs text-salvia mt-0.5">Ganaghello</div>
    </div>
    <div class="w-full max-w-sm bg-white rounded-xl shadow-sm border border-paper-dark px-8 py-7">
        {{ $slot }}
    </div>
</div>

@livewireScripts
</body>
</html>
