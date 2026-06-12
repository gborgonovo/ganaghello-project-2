@extends('blog.layout')

@section('title', $post->title . ' · Ganaghello')
@if($post->excerpt)
@section('description', $post->excerpt)
@endif

@php
    $photos     = $post->attachments;
    $coverMedia = $photos->first()?->media;
    $coverUrl   = $coverMedia ? route('storie.img', [$coverMedia->id, 'medium']) : ($post->cover ?: null);
    $gallery    = $photos->slice(1)->values();
@endphp

@if($coverUrl)
@section('og_type', 'article')
@section('og_image', url($coverUrl))
@endif

@section('content')

    {{-- Torna all'indice --}}
    <a href="{{ route('storie.index') }}" class="text-xs text-ink/40 hover:text-ink transition-colors">← tutte le storie</a>

    {{-- Copertina (contenuta, non full-bleed) --}}
    @if($coverUrl)
    <div class="rounded-2xl overflow-hidden border border-paper-dark/60 mt-5 mb-7 bg-white p-2 shadow-sm">
        <img src="{{ $coverUrl }}" alt="{{ $post->title }}" class="w-full max-h-[28rem] object-cover rounded-xl">
    </div>
    @else
    <div class="mt-8"></div>
    @endif

    {{-- Data + titolo --}}
    <p class="text-xs text-ink/40 tracking-widest uppercase mb-2">
        {{ optional($post->published_at ?? $post->created_at)->isoFormat('D MMMM YYYY') }}
    </p>
    <h1 class="font-serif text-3xl sm:text-4xl text-ink leading-tight mb-8">{{ $post->title }}</h1>

    {{-- Racconto --}}
    <div class="prose prose-stone max-w-none font-narrative text-lg text-ink leading-relaxed">
        {!! Str::markdown($post->content, ['html_input' => 'escape']) !!}
    </div>

    {{-- Galleria polaroid --}}
    @if($gallery->isNotEmpty())
    <div class="mt-12 flex flex-wrap justify-center gap-6">
        @foreach($gallery as $i => $att)
        @php $rot = [-3, 2, -1, 3, -2, 1][$i % 6]; @endphp
        <figure class="polaroid bg-white p-3 pb-6 shadow-md rounded-sm border border-paper-dark/40"
                style="transform: rotate({{ $rot }}deg);">
            <img src="{{ route('storie.img', [$att->media->id, 'medium']) }}" alt=""
                 class="w-56 h-56 object-cover">
            @if($att->caption)
            <figcaption class="text-center text-xs text-ink/50 mt-2 font-narrative">{{ $att->caption }}</figcaption>
            @endif
        </figure>
        @endforeach
    </div>
    @endif

    {{-- Tag --}}
    @if($post->tags->isNotEmpty())
    <div class="mt-12 pt-5 border-t border-paper-dark/60 flex flex-wrap gap-2">
        @foreach($post->tags as $tag)
        <span class="text-xs px-2.5 py-1 rounded-full bg-paper-dark text-ink/50">{{ $tag->display_name }}</span>
        @endforeach
    </div>
    @endif

@endsection
