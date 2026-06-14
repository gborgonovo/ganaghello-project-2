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
            <button wire:click="openEdit"
                    class="text-xs text-salvia hover:text-salvia-dark transition-colors flex items-center gap-1">
                ✎ Modifica
            </button>
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

{{-- ===== MODALE MODIFICA ===== --}}
@if($showModal)
<div class="fixed inset-0 z-50 bg-paper overflow-y-auto">
    <div class="relative w-full max-w-2xl mx-auto min-h-screen">

        <div class="flex items-center justify-between px-6 py-4 border-b border-paper-dark">
            <h2 class="text-sm font-semibold text-ink">Modifica pagina</h2>
            <button wire:click="closeModal"
                    class="w-7 h-7 flex items-center justify-center rounded-full
                           text-ink/30 hover:text-ink hover:bg-paper-dark transition-colors text-sm">
                ✕
            </button>
        </div>

        <div class="px-6 py-5 space-y-5">

            {{-- Titolo --}}
            <div>
                <label class="block text-xs text-ink/50 mb-1">Titolo</label>
                <input type="text" wire:model="modalTitle" placeholder="Titolo della pagina..."
                       class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                              focus:outline-none focus:border-salvia bg-white">
                @error('modalTitle')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
            </div>

            {{-- Meta: data, ora, area --}}
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-ink/50 mb-1">Data</label>
                    <input type="date" wire:model="modalDate"
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                                  focus:outline-none focus:border-salvia bg-white">
                    @error('modalDate')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs text-ink/50 mb-1">Ora (opzionale)</label>
                    <input type="text" wire:model="modalTime" placeholder="14:30" maxlength="5"
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                                  focus:outline-none focus:border-salvia bg-white font-mono tracking-wider">
                </div>
                <div>
                    <label class="block text-xs text-ink/50 mb-1">Area</label>
                    <select wire:model="modalAreaId"
                            class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                                   focus:outline-none focus:border-salvia bg-white">
                        <option value="">Nessuna</option>
                        <x-area-options />
                    </select>
                </div>
            </div>

            {{-- Contenuto --}}
            <div>
                <label class="block text-xs text-ink/50 mb-1.5">Testo</label>
                <textarea wire:model="modalContent" rows="12" placeholder="Cosa è successo..."
                          class="w-full text-sm font-narrative text-ink border border-paper-dark rounded-xl
                                 px-4 py-3 resize-none focus:outline-none focus:border-salvia leading-relaxed
                                 bg-white placeholder:text-ink/25"></textarea>
                @error('modalContent')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
            </div>

            {{-- Copertina --}}
            <div>
                @php
                    $currentCoverPreview = match(true) {
                        (bool)$modalCover                   => $modalCover->temporaryUrl(),
                        (bool)$selectedLibraryUrl           => $selectedLibraryUrl,
                        ($currentCoverUrl && !$removeCover) => $currentCoverUrl,
                        default                             => null,
                    };
                @endphp
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs text-ink/50">Immagine di copertina</label>
                    @if(!$currentCoverPreview)
                    <button wire:click="toggleLibrary" type="button"
                            class="text-xs text-salvia hover:text-salvia-dark transition-colors">
                        {{ $showLibrary ? '↑ Chiudi libreria' : '⬚ Scegli dalla libreria' }}
                    </button>
                    @endif
                </div>
                @if($currentCoverPreview)
                <div class="relative rounded-xl overflow-hidden border border-paper-dark">
                    <img src="{{ $currentCoverPreview }}" alt="" class="w-full max-h-48 object-cover">
                    <button wire:click="$set('removeCover', true); $set('modalCover', null); $set('modalCoverMediaId', null); $set('selectedLibraryUrl', null)"
                            type="button"
                            class="absolute top-2 right-2 bg-ink/60 text-white rounded-full w-7 h-7
                                   flex items-center justify-center text-xs hover:bg-ink transition-colors">
                        ✕
                    </button>
                </div>
                @else
                <x-upload-zone wire:model="modalCover" label="Trascina o clicca per scegliere" />
                <div wire:loading wire:target="modalCover" class="text-xs text-salvia mt-1">Elaborazione...</div>
                @error('modalCover')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                @if($showLibrary)
                <div class="mt-2 border border-paper-dark rounded-xl overflow-hidden">
                    <div class="px-3 py-2 border-b border-paper-dark bg-paper">
                        <input type="text" wire:model.live.debounce.300ms="libSearch" placeholder="Cerca..."
                               class="w-full text-xs border border-paper-dark rounded-lg px-2.5 py-1.5
                                      focus:outline-none focus:border-salvia bg-white">
                    </div>
                    @if($libraryImages->isEmpty())
                    <p class="text-xs text-ink/30 text-center py-6">
                        {{ $libSearch ? 'Nessun risultato.' : 'Nessuna immagine in libreria.' }}
                    </p>
                    @else
                    <div class="grid grid-cols-4 gap-1 p-2 max-h-52 overflow-y-auto">
                        @foreach($libraryImages as $libImg)
                        <button wire:click="selectFromLibrary({{ $libImg->id }})" type="button"
                                class="aspect-square rounded-lg overflow-hidden border-2 border-transparent
                                       hover:border-salvia transition-colors">
                            <img src="{{ route('media.serve', [$libImg, 'thumb']) }}"
                                 alt="{{ $libImg->original_filename }}"
                                 class="w-full h-full object-cover">
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif
                @endif
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-paper-dark">
            <button wire:click="closeModal"
                    class="text-sm text-ink/50 hover:text-ink transition-colors px-4 py-2">
                Annulla
            </button>
            <button wire:click="saveModal" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-salvia text-white rounded-lg text-sm
                           hover:bg-salvia-dark transition-colors disabled:opacity-50">
                <span wire:loading.remove wire:target="saveModal">Salva</span>
                <span wire:loading wire:target="saveModal">Salvataggio...</span>
            </button>
        </div>
    </div>
</div>
@endif
</div>
