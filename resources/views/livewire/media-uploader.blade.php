<div class="space-y-3">

    {{-- ===== GRIGLIA IMMAGINI ===== --}}
    @if($attachments->isNotEmpty())
    <div class="grid grid-cols-3 gap-2" id="img-sortable-{{ $entityId }}">
        @foreach($attachments as $att)
        @php $url = route('media.serve', [$att->media, 'thumb']); @endphp
        <div class="relative group rounded-lg overflow-hidden bg-paper-dark aspect-square"
             wire:key="att-{{ $att->id }}"
             data-id="{{ $att->id }}">

            <img src="{{ $url }}" alt="{{ $att->caption ?? $att->media->original_filename }}"
                 class="w-full h-full object-cover cursor-pointer"
                 wire:click="openLightbox({{ $att->id }})">

            <div class="absolute inset-0 bg-ink/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-1.5">
                <input type="text"
                       wire:model.defer="captions.{{ $att->id }}"
                       wire:blur="saveCaption({{ $att->id }})"
                       placeholder="Didascalia..."
                       class="w-full text-[10px] bg-white/80 rounded px-1 py-0.5 text-ink placeholder-ink/40 mb-1">
                <x-confirm action="deleteAttachment({{ $att->id }})" tone="dark"
                        class="self-end text-[10px] text-white/80 hover:text-terracotta transition-colors">
                    ✕ elimina
                </x-confirm>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ===== ZONA UPLOAD (click + drag-and-drop) ===== --}}
    <div x-data="{
            dragging: false,
            sizeError: '',
            checkFiles(files) {
                this.sizeError = '';
                for (const f of [...files]) {
                    if (f.size > 25 * 1024 * 1024) {
                        this.sizeError = `«${f.name}» supera il limite di 25 MB.`;
                        return false;
                    }
                }
                return true;
            },
            handleDrop(e) {
                this.dragging = false;
                const files = [...e.dataTransfer.files].filter(f => f.type.startsWith('image/'));
                if (!files.length) return;
                if (!this.checkFiles(files)) return;
                const dt = new DataTransfer();
                files.forEach(f => dt.items.add(f));
                const input = $el.querySelector('input[type=file]');
                input.files = dt.files;
                input.dispatchEvent(new Event('change'));
            },
            handleChange(e) {
                if (!this.checkFiles(e.target.files)) {
                    e.target.value = '';
                    e.stopImmediatePropagation();
                }
            }
        }">
        <label
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave.prevent="dragging = false"
            x-on:drop.prevent="handleDrop($event)"
            :class="dragging ? 'border-salvia bg-salvia/10 scale-[1.01]' : 'border-paper-dark hover:border-salvia hover:bg-salvia/5'"
            class="flex flex-col items-center justify-center gap-1.5 border-2 border-dashed
                   rounded-lg py-5 px-3 cursor-pointer transition-all select-none">

            <span class="text-2xl" :class="dragging ? 'text-salvia' : 'text-ink/25'">⊕</span>
            <span class="text-xs" :class="dragging ? 'text-salvia font-medium' : 'text-ink/40'">
                <span x-show="!dragging">Clicca o trascina qui le immagini</span>
                <span x-show="dragging">Rilascia per caricare</span>
            </span>
            <span class="text-[10px] text-ink/30" x-show="!dragging">JPG, PNG, WEBP, GIF — max 25 MB</span>

            <input type="file" wire:model="uploads" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.heic,.heif"
                   x-on:change="handleChange($event)" class="sr-only">
            <div wire:loading wire:target="uploads" class="text-xs text-salvia mt-1">Elaborazione...</div>
        </label>

        <template x-if="sizeError">
            <p x-text="sizeError" class="text-xs text-terracotta mt-1"></p>
        </template>
        @error('uploads.*')
            <p class="text-xs text-terracotta mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- ===== LINK DALLA LIBRERIA ===== --}}
    <button wire:click="toggleLibrary"
            class="w-full text-xs text-ink/40 hover:text-salvia transition-colors py-1 text-center">
        {{ $showLibrary ? '↑ Chiudi libreria' : '⬚ Scegli dalla libreria' }}
    </button>

    @if($showLibrary)
    <div class="border border-paper-dark rounded-lg p-3 space-y-2 bg-paper/60">

        {{-- Ricerca --}}
        <input type="text"
               wire:model.live.debounce.300ms="libSearch"
               placeholder="Cerca per nome file..."
               class="w-full text-xs border border-paper-dark rounded px-2.5 py-1.5
                      focus:outline-none focus:border-salvia bg-white">

        @if($libraryImages->isEmpty())
        <p class="text-xs text-ink/30 italic text-center py-3">
            {{ $libSearch ? 'Nessun risultato.' : 'Nessuna immagine in libreria.' }}
        </p>
        @else
        <div class="grid grid-cols-4 gap-1.5 max-h-48 overflow-y-auto">
            @foreach($libraryImages as $libImg)
            @php $alreadyAttached = in_array($libImg->id, $attachedMediaIds); @endphp
            <div class="relative rounded overflow-hidden aspect-square
                        {{ $alreadyAttached ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer group' }}"
                 wire:key="lib-{{ $libImg->id }}"
                 @if(!$alreadyAttached) wire:click="attachFromLibrary({{ $libImg->id }})" @endif
                 title="{{ $alreadyAttached ? 'Già allegata' : $libImg->original_filename }}">

                <img src="{{ route('media.serve', [$libImg, 'thumb']) }}"
                     alt="{{ $libImg->original_filename }}"
                     class="w-full h-full object-cover">

                @if($alreadyAttached)
                <div class="absolute inset-0 flex items-center justify-center bg-ink/20">
                    <span class="text-white text-lg">✓</span>
                </div>
                @else
                <div class="absolute inset-0 bg-salvia/50 opacity-0 group-hover:opacity-100
                            transition-opacity flex items-center justify-center">
                    <span class="text-white text-lg">+</span>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- ===== LIGHTBOX ===== --}}
    @if($lightboxAttachment)
    <div class="fixed inset-0 z-50 bg-ink/80 flex items-center justify-center p-4"
         wire:click.self="closeLightbox">
        <div class="relative max-w-4xl w-full bg-paper rounded-xl overflow-hidden shadow-2xl">
            <button wire:click="closeLightbox"
                    class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-ink/50 text-white
                           flex items-center justify-center text-sm hover:bg-ink transition-colors">
                ✕
            </button>
            <img src="{{ route('media.serve', [$lightboxAttachment->media, 'medium']) }}"
                 alt="{{ $lightboxAttachment->caption ?? '' }}"
                 class="w-full max-h-[80vh] object-contain">
            @if($lightboxAttachment->caption)
            <p class="px-5 py-3 text-sm text-ink/70 font-serif italic">
                {{ $lightboxAttachment->caption }}
            </p>
            @endif
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('livewire:updated', () => {
    const el = document.getElementById('img-sortable-{{ $entityId }}');
    if (el && typeof Sortable !== 'undefined') {
        Sortable.create(el, {
            animation: 150,
            handle: 'img',
            onEnd(evt) {
                const ids = [...el.querySelectorAll('[data-id]')].map(n => n.dataset.id);
                @this.reorder(ids);
            }
        });
    }
});
</script>
@endpush
