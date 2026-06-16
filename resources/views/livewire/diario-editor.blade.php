<div class="min-h-screen">
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 pb-28">

    {{-- Testata --}}
    <div class="flex items-center justify-between mb-6">
        <x-back :fallback="route('diario')">Diario</x-back>
        <h1 class="text-sm font-semibold text-ink">{{ $entry ? 'Modifica voce' : 'Nuova voce del diario' }}</h1>
    </div>

    <div class="space-y-5">

        {{-- Titolo --}}
        <div>
            <label class="block text-xs text-ink/50 mb-1">Titolo</label>
            <input type="text" wire:model="title" placeholder="Titolo della voce..."
                   class="w-full text-base border border-paper-dark rounded-lg px-3 py-2
                          focus:outline-none focus:border-salvia bg-white">
            @error('title')<p class="text-xs text-terracotta mt-0.5">{{ $message }}</p>@enderror
        </div>

        {{-- Meta: data, ora, area --}}
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-ink/50 mb-1">Data</label>
                <input type="date" wire:model="date"
                       class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                              focus:outline-none focus:border-salvia bg-white">
                @error('date')<p class="text-xs text-terracotta mt-0.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs text-ink/50 mb-1">Ora (opzionale)</label>
                <input type="text" wire:model="time" placeholder="14:30" maxlength="5"
                       class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                              focus:outline-none focus:border-salvia bg-white font-mono tracking-wider">
            </div>
            <div>
                <label class="block text-xs text-ink/50 mb-1">Area</label>
                <select wire:model="areaId"
                        class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                               focus:outline-none focus:border-salvia bg-white">
                    <option value="">Nessuna</option>
                    <x-area-options />
                </select>
            </div>
        </div>

        {{-- Tipo (kind): auto o forzato --}}
        <div>
            <label class="block text-xs text-ink/50 mb-1.5">Tipo</label>
            <div class="flex flex-wrap gap-1.5">
                @foreach(['auto' => 'Automatico', 'polaroid' => 'Polaroid', 'postit' => 'Post-it', 'nota' => 'Nota'] as $val => $label)
                <button type="button" wire:click="$set('kind', '{{ $val }}')"
                        class="text-xs px-3 py-1.5 rounded-full border transition-colors
                               {{ $kind === $val ? 'bg-salvia text-white border-salvia' : 'text-ink/55 border-paper-dark hover:border-salvia' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
            <p class="text-[11px] text-ink/35 mt-1">Automatico: con foto diventa polaroid, testo lungo nota, altrimenti post-it.</p>
        </div>

        {{-- Contenuto --}}
        <div>
            <label class="block text-xs text-ink/50 mb-1.5">Testo</label>
            <textarea wire:model="content" rows="14" placeholder="Cosa è successo..."
                      class="w-full text-base font-narrative text-ink border border-paper-dark rounded-xl
                             px-4 py-3 resize-y focus:outline-none focus:border-salvia leading-relaxed
                             bg-white placeholder:text-ink/25"></textarea>
            @error('content')<p class="text-xs text-terracotta mt-0.5">{{ $message }}</p>@enderror
        </div>

        {{-- Copertina --}}
        <div>
            @php
                $coverPreview = match(true) {
                    (bool) $cover                       => $cover->temporaryUrl(),
                    (bool) $selectedLibraryUrl          => $selectedLibraryUrl,
                    ($currentCoverUrl && !$removeCover)  => $currentCoverUrl,
                    default                              => null,
                };
            @endphp

            <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs text-ink/50">Immagine di copertina</label>
                @if(!$coverPreview)
                <button wire:click="toggleLibrary" type="button"
                        class="text-xs text-salvia hover:text-salvia-dark transition-colors">
                    {{ $showLibrary ? '↑ Chiudi libreria' : '⬚ Scegli dalla libreria' }}
                </button>
                @endif
            </div>

            @if($coverPreview)
            <div class="relative rounded-xl overflow-hidden border border-paper-dark">
                <img src="{{ $coverPreview }}" alt="" class="w-full max-h-64 object-cover">
                <button wire:click="dropCover" type="button"
                        class="absolute top-2 right-2 bg-ink/60 text-white rounded-full w-7 h-7
                               flex items-center justify-center text-xs hover:bg-ink transition-colors">✕</button>
            </div>
            @else
            <x-upload-zone wire:model="cover" label="Trascina o clicca per scegliere" />
            <div wire:loading wire:target="cover" class="text-xs text-salvia mt-1">Elaborazione...</div>
            @error('cover')<p class="text-xs text-terracotta mt-0.5">{{ $message }}</p>@enderror

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
                             alt="{{ $libImg->original_filename }}" class="w-full h-full object-cover">
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
            @endif
        </div>

    </div>
</div>

{{-- Footer azione, fisso in basso --}}
<div class="fixed bottom-0 inset-x-0 bg-paper/95 backdrop-blur border-t border-paper-dark z-30">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-end gap-3">
        <x-back :fallback="route('diario')" class="px-4 py-2">Annulla</x-back>
        <button wire:click="save" wire:loading.attr="disabled"
                class="px-5 py-2 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors disabled:opacity-50">
            <span wire:loading.remove wire:target="save">{{ $entry ? 'Salva modifiche' : 'Salva voce' }}</span>
            <span wire:loading wire:target="save">Salvataggio...</span>
        </button>
    </div>
</div>
</div>
