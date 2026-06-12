<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Storie di Ganaghello')</title>
    <meta name="description" content="@yield('description', 'Il racconto della casa di Ganaghello, una storia alla volta.')">

    {{-- Open Graph --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', 'Storie di Ganaghello')">
    <meta property="og:description" content="@yield('description', 'Il racconto della casa di Ganaghello.')">
    @hasSection('og_image')
    <meta property="og:image" content="@yield('og_image')">
    @endif

    @vite(['resources/css/app.css'])
    <style>
        /* Texture carta leggera e rotazioni polaroid */
        .polaroid { transition: transform .2s ease; }
        .polaroid:hover { transform: rotate(0deg) scale(1.02) !important; }
    </style>
</head>
<body class="bg-paper text-ink font-sans antialiased">

    {{-- Testata --}}
    <header class="border-b border-paper-dark/60">
        <div class="max-w-3xl mx-auto px-5 py-6 flex items-baseline justify-between">
            <a href="{{ route('storie.index') }}" class="font-serif text-xl text-ink hover:text-salvia transition-colors">
                Ganaghello
            </a>
            @auth
            <a href="{{ route('cruscotto') }}"
               class="text-xs text-salvia hover:text-salvia-dark tracking-widest uppercase transition-colors">
                Cruscotto →
            </a>
            @else
            <span class="text-xs text-ink/40 tracking-widest uppercase">storie</span>
            @endauth
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-5 py-10">
        @yield('content')
    </main>

    <footer class="border-t border-paper-dark/60 mt-16">
        <div class="max-w-3xl mx-auto px-5 py-8 text-center text-xs text-ink/35">
            Ganaghello · una storia alla volta
        </div>
    </footer>

</body>
</html>
