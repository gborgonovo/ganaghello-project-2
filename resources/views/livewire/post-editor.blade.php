<div class="max-w-3xl mx-auto pb-24 px-4">

    {{-- Navigazione --}}
    <div class="flex items-center justify-between py-6">
        <a href="{{ route('blog') }}" wire:navigate
           class="text-sm text-ink/40 hover:text-ink transition-colors">← Blog</a>
        <div class="flex items-center gap-3">
            @if($post->visibility === 'public')
            <a href="{{ route('storie.show', $post->slug) }}" target="_blank"
               class="text-xs text-salvia hover:text-salvia-dark transition-colors">Vedi pubblico ↗</a>
            @endif
            <span class="text-xs px-2 py-0.5 rounded-full
                {{ $post->visibility === 'public' ? 'bg-salvia/15 text-salvia' : ($post->visibility === 'draft' ? 'bg-paper-dark text-ink/50' : 'bg-terracotta/15 text-terracotta') }}">
                {{ ['public' => 'pubblico', 'draft' => 'bozza', 'private' => 'privato'][$post->visibility] }}
            </span>
        </div>
    </div>

    {{-- Voci sorgente (se composto dal diario) --}}
    @if($sourceEntries->isNotEmpty())
    <div class="mb-5 bg-salvia/5 border border-salvia/20 rounded-xl px-4 py-3">
        <p class="text-xs text-ink/50 mb-1.5">Composto da {{ $sourceEntries->count() }} {{ $sourceEntries->count() === 1 ? 'voce' : 'voci' }} del diario:</p>
        <div class="flex flex-wrap gap-2">
            @foreach($sourceEntries as $e)
            <a href="{{ route('diario.show', $e->id) }}" wire:navigate
               class="text-xs text-salvia hover:underline">
                {{ $e->entry_date->isoFormat('D MMM') }} · {{ Str::limit($e->title ?: $e->content, 30) }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Titolo --}}
    <input type="text" wire:model.live.debounce.500ms="title" placeholder="Titolo del post..."
           class="w-full text-2xl font-semibold text-ink bg-transparent border-0 border-b border-transparent
                  focus:border-paper-dark focus:outline-none focus:ring-0 px-0 py-2 placeholder:text-ink/25 font-serif">
    @error('title')<p class="text-xs text-red-500">{{ $message }}</p>@enderror

    {{-- Slug --}}
    <div class="flex items-center gap-2 text-xs text-ink/40 mt-1 mb-5">
        <span>/storie/</span>
        <input type="text" wire:model="slug"
               class="flex-1 bg-transparent border-0 focus:outline-none focus:ring-0 px-0 py-1 font-mono text-ink/60">
    </div>

    {{-- Meta: visibilità, data, area --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div>
            <label class="block text-xs text-ink/50 mb-1">Visibilità</label>
            <select wire:model="visibility"
                    class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2 bg-white
                           focus:outline-none focus:border-salvia">
                <option value="draft">Bozza</option>
                <option value="private">Privato</option>
                <option value="public">Pubblico</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-ink/50 mb-1">Data pubblicazione</label>
            <input type="date" wire:model="publishedAt"
                   class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2 bg-white
                          focus:outline-none focus:border-salvia">
        </div>
        <div>
            <label class="block text-xs text-ink/50 mb-1">Area (opzionale)</label>
            <select wire:model="areaId"
                    class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2 bg-white
                           focus:outline-none focus:border-salvia">
                <option value="">Nessuna</option>
                <x-area-options />
            </select>
        </div>
    </div>

    {{-- Tag --}}
    <div class="mb-6">
        <label class="block text-xs text-ink/50 mb-1.5">Tag</label>
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($post->tags as $tag)
            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-paper-dark text-ink/60">
                {{ $tag->display_name }}
                <button wire:click="removeTag({{ $tag->id }})" class="text-ink/30 hover:text-terracotta">✕</button>
            </span>
            @endforeach
            <input type="text" wire:model="newTag" wire:keydown.enter.prevent="addTag"
                   placeholder="+ tag"
                   class="text-xs border border-paper-dark rounded-full px-3 py-1 w-28
                          focus:outline-none focus:border-salvia">
        </div>
    </div>

    {{-- Prosa --}}
    <div class="mb-6">
        <label class="block text-xs text-ink/50 mb-1.5">Racconto</label>
        <textarea wire:model="content" rows="16" placeholder="Il racconto del post... (markdown)"
                  class="w-full text-base font-narrative text-ink border border-paper-dark rounded-xl
                         px-4 py-3 resize-y focus:outline-none focus:border-salvia leading-relaxed
                         bg-white placeholder:text-ink/25"></textarea>
        @error('content')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
    </div>

    {{-- Estratto --}}
    <div class="mb-6">
        <label class="block text-xs text-ink/50 mb-1.5">Estratto (per l'indice)</label>
        <textarea wire:model="excerpt" rows="2" placeholder="Breve estratto mostrato nell'elenco delle storie..."
                  class="w-full text-sm text-ink/80 border border-paper-dark rounded-xl
                         px-4 py-2.5 resize-none focus:outline-none focus:border-salvia
                         bg-white placeholder:text-ink/25"></textarea>
        @error('excerpt')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
    </div>

    {{-- Set fotografico --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs text-ink/50">Foto ({{ $photos->count() }}) — la prima è la copertina</label>
            <button wire:click="toggleLibrary" type="button"
                    class="text-xs text-salvia hover:text-salvia-dark transition-colors">
                {{ $showLibrary ? '↑ Chiudi libreria' : '⬚ Dalla libreria' }}
            </button>
        </div>

        @if($photos->isNotEmpty())
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-3">
            @foreach($photos as $i => $att)
            <div wire:key="photo-{{ $att->id }}"
                 class="relative group rounded-xl overflow-hidden border-2 {{ $loop->first ? 'border-salvia' : 'border-paper-dark' }} aspect-square">
                <img src="{{ route('media.serve', [$att->media, 'thumb']) }}" alt="" class="w-full h-full object-cover">

                @if($loop->first)
                <span class="absolute top-1 left-1 bg-salvia text-white text-[9px] px-1.5 py-0.5 rounded">copertina</span>
                @endif

                <div class="absolute inset-0 bg-ink/40 opacity-0 group-hover:opacity-100 transition-opacity
                            flex items-center justify-center gap-1">
                    @unless($loop->first)
                    <button wire:click="makeCover({{ $att->id }})" title="Imposta copertina"
                            class="bg-white/90 text-ink rounded w-6 h-6 text-xs hover:bg-white">★</button>
                    <button wire:click="movePhoto({{ $att->id }}, 'up')" title="Sposta su"
                            class="bg-white/90 text-ink rounded w-6 h-6 text-xs hover:bg-white">←</button>
                    @endunless
                    @unless($loop->last)
                    <button wire:click="movePhoto({{ $att->id }}, 'down')" title="Sposta giù"
                            class="bg-white/90 text-ink rounded w-6 h-6 text-xs hover:bg-white">→</button>
                    @endunless
                    <button wire:click="removePhoto({{ $att->id }})" title="Rimuovi"
                            class="bg-white/90 text-terracotta rounded w-6 h-6 text-xs hover:bg-white">✕</button>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Upload --}}
        <label class="block border-2 border-dashed border-paper-dark rounded-xl px-4 py-6 text-center
                      cursor-pointer hover:border-salvia transition-colors">
            <input type="file" wire:model="newPhotos" multiple accept="image/*" class="hidden">
            <span class="text-sm text-ink/40">Trascina o clicca per aggiungere foto</span>
        </label>
        <div wire:loading wire:target="newPhotos" class="text-xs text-salvia mt-1">Caricamento...</div>
        @error('newPhotos.*')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror

        {{-- Libreria --}}
        @if($showLibrary)
        <div class="mt-2 border border-paper-dark rounded-xl overflow-hidden">
            <div class="px-3 py-2 border-b border-paper-dark bg-paper">
                <input type="text" wire:model.live.debounce.300ms="libSearch" placeholder="Cerca..."
                       class="w-full text-xs border border-paper-dark rounded-lg px-2.5 py-1.5
                              focus:outline-none focus:border-salvia bg-white">
            </div>
            @if($libraryImages->isEmpty())
            <p class="text-xs text-ink/30 text-center py-6">Nessuna immagine.</p>
            @else
            <div class="grid grid-cols-5 gap-1 p-2 max-h-52 overflow-y-auto">
                @foreach($libraryImages as $libImg)
                <button wire:click="addFromLibrary({{ $libImg->id }})" type="button"
                        class="aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-salvia">
                    <img src="{{ route('media.serve', [$libImg, 'thumb']) }}" alt="" class="w-full h-full object-cover">
                </button>
                @endforeach
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Barra salvataggio sticky --}}
    <div class="fixed bottom-0 left-0 right-0 bg-paper/95 backdrop-blur border-t border-paper-dark">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-end gap-3">
            @if($saved)
            <span class="text-xs text-salvia" wire:poll.3000ms="$set('saved', false)">Salvato.</span>
            @endif
            <button wire:click="save" wire:loading.attr="disabled"
                    class="px-6 py-2 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark
                           transition-colors disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Salva</span>
                <span wire:loading wire:target="save">Salvataggio...</span>
            </button>
        </div>
    </div>

</div>
