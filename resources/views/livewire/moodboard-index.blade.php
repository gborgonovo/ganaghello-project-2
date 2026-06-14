<div class="space-y-5 pb-16">

    {{-- ===== TOOLBAR ===== --}}
    <div class="flex items-center gap-3 flex-wrap">

        {{-- Pulsante Aggiungi --}}
        <button wire:click="toggleForm"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors
                       {{ $showForm ? 'bg-paper-dark text-ink' : 'bg-salvia text-white hover:bg-salvia-dark' }}">
            {{ $showForm ? '✕ Annulla' : '+ Ispirazione' }}
        </button>

        {{-- Filtro area --}}
        <div class="flex items-center gap-1.5 flex-wrap">
            <button wire:click="$set('filterAreaId', null)"
                    class="px-3 py-1.5 rounded-full text-xs transition-colors
                           {{ !$filterAreaId ? 'bg-salvia text-white' : 'bg-paper-dark text-ink/60 hover:bg-paper-dark/70' }}">
                Tutte
            </button>
            @foreach($aree as $area)
            <button wire:click="$set('filterAreaId', {{ $area->id }})"
                    class="px-3 py-1.5 rounded-full text-xs transition-colors flex items-center gap-1
                           {{ $filterAreaId === $area->id ? 'bg-salvia text-white' : 'bg-paper-dark text-ink/60 hover:bg-paper-dark/70' }}">
                @if($area->color)
                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $area->color }}"></span>
                @endif
                {{ $area->name }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- ===== FORM NUOVA ISPIRAZIONE ===== --}}
    @if($showForm)
    <div class="bg-white rounded-xl border border-paper-dark p-5 space-y-4">
        <h3 class="text-sm font-semibold text-ink">Nuova ispirazione</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-ink/50 mb-1">Titolo</label>
                <input type="text" wire:model="newTitle" placeholder="es. Pavimento in cotto..."
                       class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                              focus:outline-none focus:border-salvia">
            </div>
            <div>
                <label class="block text-xs text-ink/50 mb-1">URL fonte</label>
                <input type="url" wire:model.blur="newUrl" placeholder="https://..."
                       class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                              focus:outline-none focus:border-salvia">
            </div>
        </div>

        <div>
            <label class="block text-xs text-ink/50 mb-1">Descrizione / note</label>
            <textarea wire:model="newDescription" rows="2" placeholder="Perché ti ha ispirato..."
                      class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                             focus:outline-none focus:border-salvia resize-none"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4 items-start">
            <div>
                <label class="block text-xs text-ink/50 mb-1">Area</label>
                <select wire:model="newAreaId"
                        class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                               focus:outline-none focus:border-salvia bg-white">
                    <option value="">Nessuna area</option>
                    <x-area-options />
                </select>
            </div>
            <div class="col-span-2">
                <div class="flex items-center justify-between mb-1">
                    <label class="text-xs text-ink/50">Immagine</label>
                    @php $imgPreview = $newImage ? $newImage->temporaryUrl() : $newImagePreviewUrl; @endphp
                    @if(!$imgPreview)
                    <button wire:click="toggleImageLibrary" type="button"
                            class="text-xs text-salvia hover:text-salvia-dark transition-colors">
                        {{ $showImageLibrary ? '↑ Chiudi libreria' : '⬚ Dalla libreria' }}
                    </button>
                    @endif
                </div>

                @if($imgPreview)
                <div class="relative rounded-xl overflow-hidden border border-paper-dark">
                    <img src="{{ $imgPreview }}" alt="" class="w-full max-h-36 object-cover">
                    <button wire:click="$set('newImage', null); $set('newImageMediaId', null); $set('newImagePreviewUrl', null)"
                            type="button"
                            class="absolute top-2 right-2 bg-ink/60 text-white rounded-full w-6 h-6
                                   flex items-center justify-center text-xs hover:bg-ink transition-colors">
                        ✕
                    </button>
                </div>
                @elseif($newImageUrl)
                <div class="relative rounded-xl overflow-hidden border border-paper-dark">
                    <img src="{{ $newImageUrl }}" alt="" class="w-full max-h-36 object-cover">
                    <button wire:click="$set('newImageUrl', '')" type="button"
                            class="absolute top-2 right-2 bg-ink/60 text-white rounded-full w-6 h-6
                                   flex items-center justify-center text-xs hover:bg-ink transition-colors">
                        ✕
                    </button>
                </div>
                @else
                <x-upload-zone wire:model="newImage" label="Trascina o clicca per caricare dal tuo device" />
                <div class="mt-2">
                    <input type="url" wire:model.blur="newImageUrl" placeholder="oppure incolla URL immagine..."
                           class="w-full text-xs border border-paper-dark rounded-lg px-3 py-2
                                  focus:outline-none focus:border-salvia">
                </div>
                <div wire:loading wire:target="newImage" class="text-xs text-salvia mt-1">Elaborazione...</div>

                @if($showImageLibrary)
                <div class="mt-2 border border-paper-dark rounded-xl overflow-hidden">
                    <div class="px-3 py-2 border-b border-paper-dark bg-paper">
                        <input type="text" wire:model.live.debounce.300ms="imageLibSearch"
                               placeholder="Cerca..."
                               class="w-full text-xs border border-paper-dark rounded-lg px-2.5 py-1.5
                                      focus:outline-none focus:border-salvia bg-white">
                    </div>
                    @if($imageLibraryImages->isEmpty())
                    <p class="text-xs text-ink/30 text-center py-4">
                        {{ $imageLibSearch ? 'Nessun risultato.' : 'Nessuna immagine in libreria.' }}
                    </p>
                    @else
                    <div class="grid grid-cols-4 gap-1 p-2 max-h-44 overflow-y-auto">
                        @foreach($imageLibraryImages as $libImg)
                        <button wire:click="selectImageFromLibrary({{ $libImg->id }})" type="button"
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

        <div class="flex gap-2">
            <button wire:click="create"
                    class="px-5 py-2 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors">
                Salva
            </button>
            <button wire:click="toggleForm"
                    class="px-4 py-2 text-sm text-ink/50 hover:text-ink transition-colors">
                Annulla
            </button>
        </div>
    </div>
    @endif


    {{-- ===== GRIGLIA MASONRY ===== --}}
    @if($inspirations->isEmpty())
    <div class="bg-white rounded-xl border border-paper-dark px-6 py-16 text-center">
        <p class="text-ink/30 italic text-sm">Nessuna ispirazione ancora. Aggiungi la prima!</p>
    </div>
    @else
    <div class="columns-2 md:columns-3 lg:columns-4 gap-3">
        @foreach($inspirations as $insp)
        @php
            $coverMedia = $insp->attachments->first()?->media;
            $coverUrl   = $coverMedia ? route('media.serve', [$coverMedia, 'medium']) : $insp->cover;
        @endphp
        <div class="break-inside-avoid mb-3 rounded-xl border border-paper-dark bg-white overflow-hidden group"
             wire:key="insp-{{ $insp->id }}">

            {{-- Immagine --}}
            @if($coverUrl)
            <a href="{{ route('moodboard.show', $insp) }}" wire:navigate>
                <img src="{{ $coverUrl }}"
                     alt="{{ $insp->title ?? '' }}"
                     class="w-full object-cover"
                     onerror="this.closest('a').style.display='none'">
            </a>
            @endif

            {{-- Corpo --}}
            <div class="p-3 space-y-2">

                {{-- Vista normale (le card non hanno edit inline: si va alla pagina) --}}
                @if($insp->title)
                <a href="{{ route('moodboard.show', $insp) }}" wire:navigate
                   class="block text-sm font-medium text-ink leading-snug hover:text-salvia transition-colors">
                    {{ $insp->title }}
                </a>
                @endif

                @if($insp->description)
                <p class="text-xs text-ink/60 leading-relaxed line-clamp-2">{{ $insp->description }}</p>
                @endif

                {{-- Footer card --}}
                <div class="flex items-center justify-between pt-1 gap-2">
                    <div class="flex items-center gap-1.5 min-w-0">
                        @if($insp->area)
                        <span class="flex items-center gap-1 text-[10px] text-ink/50 truncate">
                            @if($insp->area->color)
                            <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                  style="background-color: {{ $insp->area->color }}"></span>
                            @endif
                            {{ $insp->area->name }}
                        </span>
                        @endif
                        @if($insp->url)
                        <a href="{{ $insp->url }}" target="_blank"
                           class="text-[10px] text-salvia hover:underline shrink-0" @click.stop>
                            fonte
                        </a>
                        @endif
                    </div>

                    {{-- Azioni hover --}}
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                        @if($confirmDeleteId === $insp->id)
                        <button wire:click="delete({{ $insp->id }})"
                                class="text-[10px] text-terracotta font-medium px-1">
                            Elimina?
                        </button>
                        <button wire:click="$set('confirmDeleteId', null)"
                                class="text-[10px] text-ink/30 px-1">no</button>
                        @else
                        <button wire:click="$set('confirmDeleteId', {{ $insp->id }})"
                                class="text-[10px] text-ink/30 hover:text-terracotta transition-colors px-1">
                            ✕
                        </button>
                        @endif
                    </div>
                </div>

            </div>
        </div>
        @endforeach
    </div>
    @endif


</div>
