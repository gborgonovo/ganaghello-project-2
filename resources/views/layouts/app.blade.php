<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' · BiG-Log' : 'BiG-Log' }}</title>
    <x-pwa-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-paper text-ink">

<div class="flex h-screen overflow-hidden"
     x-data="{ sidebarOpen: false }"
     @keydown.meta.k.window.prevent="window.dispatchEvent(new CustomEvent('open-global-search'))"
     @keydown.ctrl.k.window.prevent="window.dispatchEvent(new CustomEvent('open-global-search'))">

    {{-- Overlay mobile (dietro la sidebar, davanti al contenuto) --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/40 lg:hidden"
         style="display:none"></div>

    {{-- Sidebar --}}
    <aside class="fixed lg:relative inset-y-0 left-0 z-50 flex flex-col safe-top w-64 lg:w-56 shrink-0
                  bg-salvia-dark text-paper border-r border-salvia-dark
                  -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out"
           :class="{ 'translate-x-0': sidebarOpen }">

        {{-- Logo / titolo app --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-salvia">
            <div class="flex items-center gap-2">
                <span class="text-lg font-semibold tracking-tight text-paper">BiG-Log</span>
                <span class="text-xs text-salvia-light font-normal">Ganaghello</span>
            </div>
            {{-- Chiudi sidebar (mobile only) --}}
            <button @click="sidebarOpen = false"
                    class="lg:hidden p-1 rounded text-paper/50 hover:text-paper transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Navigazione principale --}}
        <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-0.5">
            <x-sidebar-link href="{{ route('cruscotto') }}" :active="request()->routeIs('cruscotto')" icon="home">Cruscotto</x-sidebar-link>
            <x-sidebar-link href="{{ route('kanban') }}" :active="request()->routeIs('kanban')" icon="view-columns">Kanban</x-sidebar-link>
            <x-sidebar-link href="{{ route('timeline') }}" :active="request()->routeIs('timeline')" icon="chart-bar">Timeline</x-sidebar-link>
            <x-sidebar-link href="{{ route('diario') }}" :active="request()->routeIs('diario')" icon="book-open">Diario</x-sidebar-link>
            <x-sidebar-link href="{{ route('blog') }}" :active="request()->routeIs('blog*')" icon="document-text">Blog</x-sidebar-link>
            <x-sidebar-link href="{{ route('goals') }}" :active="request()->routeIs('goals')" icon="trophy">Goals</x-sidebar-link>

            <p class="px-3 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-widest text-salvia-light/70">Raccolte</p>
            <x-sidebar-link href="{{ route('libreria') }}" :active="request()->routeIs('libreria')" icon="photo">Libreria</x-sidebar-link>
            <x-sidebar-link href="{{ route('moodboard') }}" :active="request()->routeIs('moodboard')" icon="swatch">Moodboard</x-sidebar-link>
            <x-sidebar-link href="{{ route('note') }}" :active="request()->routeIs('note')" icon="clipboard">Note</x-sidebar-link>
        </nav>

        {{-- Footer sidebar: utente + impostazioni --}}
        <div class="px-2 py-3 border-t border-salvia space-y-0.5">
            <x-sidebar-link href="{{ route('aree') }}" :active="request()->routeIs('aree')" icon="map-pin">Aree</x-sidebar-link>
            <x-sidebar-link href="{{ route('settings.index') }}" :active="request()->routeIs('settings.*')" icon="cog-6-tooth">Impostazioni</x-sidebar-link>

            {{-- Utente --}}
            <div class="mt-2 px-3 py-2 rounded-lg flex items-center gap-2 text-sm text-paper/70">
                <x-identicon :user="auth()->user()" />
                <span class="truncate">{{ auth()->user()->name ?? '' }}</span>
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button type="submit" class="text-paper/50 hover:text-paper text-xs" title="Esci">↩</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Area principale --}}
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

        {{-- Topbar: sempre visibile (serve il hamburger su mobile) --}}
        <header class="h-14 shrink-0 flex items-center px-4 lg:px-6 gap-3 border-b border-paper-dark bg-paper safe-top">

            {{-- Hamburger (mobile only) --}}
            <button @click="sidebarOpen = true"
                    class="lg:hidden p-2 -ml-1 rounded-lg text-ink/50 hover:text-ink hover:bg-paper-dark transition-colors shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            <div class="flex-1 min-w-0">
                @isset($topbar)
                    {{ $topbar }}
                @elseif(isset($title))
                    <h1 class="text-base font-semibold text-ink truncate">{{ $title }}</h1>
                @endif
            </div>

            <button x-data
                    @click="window.dispatchEvent(new CustomEvent('open-global-search'))"
                    class="flex items-center gap-1.5 text-xs text-ink/40 hover:text-ink border border-paper-dark
                           rounded-lg px-2.5 py-1.5 bg-white transition-colors shrink-0">
                <span>⌕</span>
                <span class="hidden sm:inline">Cerca</span>
                <kbd class="hidden sm:inline text-[10px] opacity-50 font-sans">⌘K</kbd>
            </button>
        </header>

        {{-- Contenuto --}}
        <main class="flex-1 overflow-y-auto px-4 lg:px-6 py-4 lg:py-5">
            {{ $slot }}
        </main>
    </div>

</div>

@livewire('global-search')
<x-toast />
@livewireScripts
</body>
</html>
