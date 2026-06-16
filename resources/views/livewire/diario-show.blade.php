<div>
<div class="max-w-2xl mx-auto pb-16">

    {{-- Navigazione + azioni --}}
    <div class="flex items-center justify-between mb-8">
        <x-back />
        <div class="flex items-center gap-4">
            <button wire:click="composePost"
                    class="text-xs text-salvia hover:text-salvia-dark transition-colors flex items-center gap-1">
                ✚ Componi un post
            </button>
            <a href="{{ route('diario.edit', $entry->id) }}" wire:navigate
                    class="text-xs text-salvia hover:text-salvia-dark transition-colors flex items-center gap-1">
                ✎ Modifica
            </a>
            <x-confirm action="deleteEntry"
                    class="text-xs text-ink/40 hover:text-terracotta transition-colors flex items-center gap-1">
                ✕ Elimina
            </x-confirm>
        </div>
    </div>

    {{-- Copertina --}}
    @php $cover = $entry->attachments->first()?->media; @endphp
    @if($cover)
    <div class="rounded-2xl overflow-hidden mb-8 border border-paper-dark">
        <img src="{{ route('media.serve', [$cover, 'medium']) }}"
             alt="" class="w-full max-h-80 object-cover">
    </div>
    @endif

    {{-- Titolo --}}
    @if($entry->title)
    <h1 class="text-2xl font-semibold text-ink mb-4 leading-tight">{{ $entry->title }}</h1>
    @endif

    {{-- Meta --}}
    <div class="flex items-center gap-4 mb-8">
        <span class="text-sm text-ink/35 font-light tracking-wider">
            ~ {{ $entry->entry_date->isoFormat('D MMM') }} '{{ $entry->entry_date->format('y') }} ~
        </span>
        @if($entry->entry_time)
        <span class="text-xs text-ink/25 font-mono">{{ substr($entry->entry_time, 0, 5) }}</span>
        @endif
        @if($entry->area)
        <x-area-chip :area="$entry->area" />
        @endif
    </div>

    {{-- Contenuto --}}
    <div class="prose prose-sm max-w-none font-narrative text-ink leading-relaxed">
        {!! Str::markdown($entry->content, ['html_input' => 'escape']) !!}
    </div>

    {{-- Tracciabilità: post nati da questa voce --}}
    @if($entry->posts->isNotEmpty())
    <div class="mt-10 pt-5 border-t border-paper-dark">
        <p class="text-xs text-ink/40 mb-2">Questa voce è stata usata in {{ $entry->posts->count() === 1 ? 'un post' : 'più post' }}:</p>
        <div class="flex flex-wrap gap-2">
            @foreach($entry->posts as $post)
            <a href="{{ route('blog.edit', $post->id) }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full
                      bg-paper-dark text-ink/60 hover:text-salvia transition-colors">
                ✎ {{ $post->title }}
                @if($post->visibility === 'public')
                <span class="text-[9px] text-salvia">pubblico</span>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
</div>
