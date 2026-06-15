<div>
<div class="max-w-5xl mx-auto pb-12">

    {{-- ===== TOOLBAR ===== --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">

        {{-- Filtro area --}}
        <div class="flex items-center gap-1.5 flex-wrap">
            <button wire:click="$set('filterAreaId', null)"
                    class="text-xs px-3 py-1 rounded-full transition-colors
                           {{ is_null($filterAreaId) ? 'bg-salvia text-white' : 'text-ink/50 hover:text-ink border border-paper-dark' }}">
                Tutto
            </button>
            @foreach($aree as $area)
            <button wire:click="$set('filterAreaId', {{ $area->id }})"
                    class="text-xs px-3 py-1 rounded-full transition-colors flex items-center gap-1.5
                           {{ $filterAreaId === $area->id ? 'bg-salvia text-white' : 'text-ink/50 hover:text-ink border border-paper-dark' }}">
                @if($area->color)
                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $area->color }}"></span>
                @endif
                {{ $area->name }}
            </button>
            @endforeach
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <input wire:model.live.debounce.300ms="search"
                   type="text"
                   placeholder="Cerca nel diario..."
                   class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                          focus:outline-none focus:border-salvia w-44 bg-white">

            @if($search || $filterAreaId)
            <button wire:click="$set('search', ''); $set('filterAreaId', null)"
                    class="text-xs text-terracotta hover:text-ink transition-colors">
                Azzera
            </button>
            @endif

            <button wire:click="toggleSelecting"
                    class="px-3 py-2 rounded-lg text-sm border transition-colors
                           {{ $selecting ? 'border-salvia text-salvia bg-salvia/5' : 'border-paper-dark text-ink/60 hover:text-ink' }}">
                ✚ Componi un post
            </button>

            <button wire:click="openNew"
                    class="flex items-center gap-2 px-4 py-2 bg-salvia text-white rounded-lg text-sm
                           hover:bg-salvia-dark transition-colors">
                + Nuova pagina
            </button>
        </div>
    </div>

    {{-- ===== BARRA AZIONE SELEZIONE ===== --}}
    @if($selecting)
    <div class="sticky top-0 z-20 flex items-center justify-between gap-3 mb-5 px-4 py-2.5
                bg-salvia text-white rounded-xl shadow-sm">
        <span class="text-sm">
            {{ count($selectedIds) }} {{ count($selectedIds) === 1 ? 'voce selezionata' : 'voci selezionate' }}
        </span>
        <div class="flex items-center gap-2">
            <button wire:click="composePost"
                    @disabled(count($selectedIds) === 0)
                    class="px-4 py-1.5 bg-white text-salvia rounded-lg text-sm font-medium
                           hover:bg-paper transition-colors disabled:opacity-50">
                Componi ({{ count($selectedIds) }})
            </button>
            <button wire:click="toggleSelecting"
                    class="px-3 py-1.5 text-white/80 hover:text-white text-sm transition-colors">
                Annulla
            </button>
        </div>
    </div>
    @endif

    {{-- ===== SCRAPBOOK (masonry per mese) ===== --}}
    @forelse($byMonth as $ym => $monthEntries)
    @php $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $ym)->startOfMonth(); @endphp

    <div class="mb-10" wire:key="month-{{ $ym }}">

        {{-- Separatore mese, scritto a mano --}}
        <div class="flex items-center gap-4 mb-5">
            <div class="flex-1 border-t border-paper-dark"></div>
            <span class="font-hand text-2xl text-ink/55 shrink-0">{{ ucfirst($monthDate->isoFormat('MMMM YYYY')) }}</span>
            <div class="flex-1 border-t border-paper-dark"></div>
        </div>

        {{-- Muro di voci: polaroid / post-it / nota, ognuna col suo giorno --}}
        <div class="columns-1 sm:columns-2 lg:columns-3 gap-3">
            @foreach($monthEntries as $entry)
            <x-diario.entry :entry="$entry"
                            :selecting="$selecting"
                            :isSelected="in_array($entry->id, $selectedIds)" />
            @endforeach
        </div>
    </div>

    @empty
    <div class="rounded-xl border border-dashed border-paper-dark px-6 py-12 text-center">
        <p class="text-ink/30 text-sm italic">Il diario è vuoto. Scrivi la prima pagina.</p>
    </div>
    @endforelse

    {{-- Carica altri --}}
    @if($hasMore)
    <div class="text-center mt-4">
        <button wire:click="loadMore"
                class="text-sm text-salvia hover:text-salvia-dark transition-colors">
            Carica altri...
        </button>
    </div>
    @endif

</div>{{-- fine max-w-2xl --}}

{{-- ===== MODALE NUOVA/MODIFICA PAGINA ===== --}}
@if($showModal)
<div class="fixed inset-0 z-50 bg-paper overflow-y-auto">

    <div class="relative w-full max-w-2xl mx-auto min-h-screen">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-paper-dark">
            <h2 class="text-sm font-semibold text-ink">
                {{ $editEntryId ? 'Modifica pagina' : 'Nuova pagina del diario' }}
            </h2>
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
                @error('modalTitle')
                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Meta: data, ora, area --}}
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-ink/50 mb-1">Data</label>
                    <input type="date" wire:model="modalDate"
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                                  focus:outline-none focus:border-salvia bg-white">
                    @error('modalDate')
                    <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs text-ink/50 mb-1">Ora (opzionale)</label>
                    <input type="text" wire:model="modalTime"
                           placeholder="14:30"
                           maxlength="5"
                           pattern="[0-9]{2}:[0-9]{2}"
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
                <textarea wire:model="modalContent"
                          rows="12"
                          placeholder="Cosa è successo..."
                          class="w-full text-sm font-narrative text-ink border border-paper-dark rounded-xl
                                 px-4 py-3 resize-none focus:outline-none focus:border-salvia leading-relaxed
                                 bg-white placeholder:text-ink/25"></textarea>
                @error('modalContent')
                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                @enderror
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
                {{-- Anteprima --}}
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
                {{-- Zona upload --}}
                <x-upload-zone wire:model="modalCover" label="Trascina o clicca per scegliere" />
                <div wire:loading wire:target="modalCover" class="text-xs text-salvia mt-1">Elaborazione...</div>
                @error('modalCover')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror

                {{-- Pannello libreria --}}
                @if($showLibrary)
                <div class="mt-2 border border-paper-dark rounded-xl overflow-hidden">
                    <div class="px-3 py-2 border-b border-paper-dark bg-paper">
                        <input type="text" wire:model.live.debounce.300ms="libSearch"
                               placeholder="Cerca..."
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

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-paper-dark">
            <button wire:click="closeModal"
                    class="text-sm text-ink/50 hover:text-ink transition-colors px-4 py-2">
                Annulla
            </button>
            <button wire:click="saveModal"
                    wire:loading.attr="disabled"
                    class="px-5 py-2 bg-salvia text-white rounded-lg text-sm
                           hover:bg-salvia-dark transition-colors disabled:opacity-50">
                <span wire:loading.remove wire:target="saveModal">Salva pagina</span>
                <span wire:loading wire:target="saveModal">Salvataggio...</span>
            </button>
        </div>

    </div>
</div>
@endif
</div>{{-- fine root wrapper --}}
