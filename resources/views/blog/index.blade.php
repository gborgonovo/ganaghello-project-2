@extends('blog.layout')

@section('title', 'Storie di Ganaghello')

@section('content')

    @forelse($posts as $post)
    @php
        $coverMedia = $post->attachments->first()?->media;
        $coverUrl   = $coverMedia ? route('storie.img', [$coverMedia->id, 'medium']) : ($post->cover ?: null);
    @endphp

    <article class="mb-14">
        <a href="{{ route('storie.show', $post->slug) }}" class="group block">

            @if($coverUrl)
            <div class="rounded-2xl overflow-hidden border border-paper-dark/60 mb-4 bg-white p-2 shadow-sm">
                <img src="{{ $coverUrl }}" alt="{{ $post->title }}"
                     class="w-full max-h-96 object-cover rounded-xl">
            </div>
            @endif

            <p class="text-xs text-ink/40 tracking-wide uppercase mb-1">
                {{ optional($post->published_at ?? $post->created_at)->isoFormat('D MMMM YYYY') }}
            </p>
            <h2 class="font-serif text-2xl text-ink group-hover:text-salvia transition-colors leading-tight mb-2">
                {{ $post->title }}
            </h2>
            @if($post->excerpt)
            <p class="text-ink/70 font-narrative leading-relaxed">{{ $post->excerpt }}</p>
            @endif
            <span class="inline-block mt-3 text-sm text-salvia group-hover:underline">Leggi →</span>
        </a>
    </article>

    @empty
    <p class="text-center text-ink/30 italic py-20">Ancora nessuna storia da raccontare.</p>
    @endforelse

    @if($posts->hasPages())
    <div class="mt-10">
        {{ $posts->links() }}
    </div>
    @endif

@endsection
