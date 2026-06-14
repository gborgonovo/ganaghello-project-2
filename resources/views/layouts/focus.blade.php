<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — BiGlog' : 'BiGlog' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- Layout "focus": scrittura immersiva, niente sidebar ne' topbar.
     Usato dagli editor narrativi (diario, blog). L'uscita la fornisce la vista
     stessa (link "← ..." in testa). --}}
<body class="font-sans antialiased bg-paper text-ink">

<main class="min-h-screen">
    {{ $slot }}
</main>

<x-toast />
@livewireScripts
</body>
</html>
