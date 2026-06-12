<div class="max-w-4xl mx-auto pb-16 px-4">

    {{-- Toolbar --}}
    <div class="flex items-center justify-between py-6 flex-wrap gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            @foreach(['' => 'Tutti', 'public' => 'Pubblici', 'draft' => 'Bozze', 'private' => 'Privati'] as $val => $label)
            <button wire:click="$set('filterVisibility', '{{ $val }}')"
                    class="text-xs px-3 py-1.5 rounded-full transition-colors
                           {{ $filterVisibility === $val ? 'bg-salvia text-white' : 'text-ink/50 hover:text-ink border border-paper-dark' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cerca nei post..."
                   class="text-sm border border-paper-dark rounded-lg px-3 py-1.5 w-44 bg-white
                          focus:outline-none focus:border-salvia">
            <a href="{{ route('blog.new') }}" wire:navigate
               class="px-4 py-2 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors">
                + Nuovo post
            </a>
        </div>
    </div>

    {{-- Lista --}}
    @forelse($posts as $post)
    @php
        $cover = $post->attachments->first()?->media;
        $coverUrl = $cover ? route('media.serve', [$cover, 'thumb']) : ($post->cover ?: null);
    @endphp
    <div wire:key="post-{{ $post->id }}"
         class="flex items-center gap-4 bg-white rounded-xl border border-paper-dark p-3 mb-3
                hover:border-salvia-light transition-colors">

        {{-- Copertina --}}
        <a href="{{ route('blog.edit', $post->id) }}" wire:navigate
           class="shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-paper-dark">
            @if($coverUrl)
            <img src="{{ $coverUrl }}" alt="" class="w-full h-full object-cover">
            @else
            <div class="w-full h-full flex items-center justify-center text-ink/20 text-xs">no foto</div>
            @endif
        </a>

        {{-- Info --}}
        <div class="flex-1 min-w-0">
            <a href="{{ route('blog.edit', $post->id) }}" wire:navigate
               class="text-sm font-semibold text-ink hover:text-salvia transition-colors block truncate font-serif">
                {{ $post->title }}
            </a>
            <p class="text-xs text-ink/40 mt-0.5 truncate">{{ $post->excerpt ?: Str::limit(strip_tags($post->content), 80) }}</p>
            <div class="flex items-center gap-3 mt-1.5 text-[11px] text-ink/40">
                <span>{{ optional($post->published_at ?? $post->created_at)->isoFormat('D MMM YYYY') }}</span>
                @if($post->attachments->count())
                <span>{{ $post->attachments->count() }} foto</span>
                @endif
            </div>
        </div>

        {{-- Visibilità + azioni --}}
        <div class="shrink-0 flex items-center gap-2">
            <select wire:change="setVisibility({{ $post->id }}, $event.target.value)"
                    class="text-xs border border-paper-dark rounded-lg px-2 py-1 bg-white
                           focus:outline-none focus:border-salvia
                           {{ $post->visibility === 'public' ? 'text-salvia' : 'text-ink/50' }}">
                <option value="draft"   @selected($post->visibility === 'draft')>Bozza</option>
                <option value="private" @selected($post->visibility === 'private')>Privato</option>
                <option value="public"  @selected($post->visibility === 'public')>Pubblico</option>
            </select>

            @if($confirmDeleteId === $post->id)
            <button wire:click="delete({{ $post->id }})" class="text-xs text-terracotta font-medium">Elimina?</button>
            <button wire:click="$set('confirmDeleteId', null)" class="text-xs text-ink/30">no</button>
            @else
            <button wire:click="$set('confirmDeleteId', {{ $post->id }})"
                    class="text-ink/25 hover:text-terracotta text-sm transition-colors">✕</button>
            @endif
        </div>
    </div>
    @empty
    <div class="rounded-xl border border-dashed border-paper-dark px-6 py-14 text-center">
        <p class="text-ink/30 text-sm italic mb-3">
            {{ $search || $filterVisibility ? 'Nessun post trovato.' : 'Nessun post ancora.' }}
        </p>
        @unless($search || $filterVisibility)
        <a href="{{ route('blog.new') }}" wire:navigate class="text-sm text-salvia hover:underline">Scrivi il primo post →</a>
        @endunless
    </div>
    @endforelse

</div>
