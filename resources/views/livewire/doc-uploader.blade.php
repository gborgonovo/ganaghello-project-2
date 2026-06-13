<div class="space-y-2">

    {{-- ===== LISTA DOCUMENTI ===== --}}
    @forelse($docs as $doc)
    <div class="flex items-center gap-3 py-1.5 px-2 rounded-lg hover:bg-paper-dark/40 group"
         wire:key="doc-{{ $doc->id }}">

        <span class="text-base shrink-0 text-ink/40">
            @if($doc->media->mime_type === 'application/pdf') 📄
            @elseif(str_contains($doc->media->mime_type, 'word')) 📝
            @elseif(str_contains($doc->media->mime_type, 'spreadsheet') || str_contains($doc->media->mime_type, 'excel')) 📊
            @else 📎
            @endif
        </span>

        <div class="flex-1 min-w-0">
            <a href="{{ route('media.serve', [$doc->media, 'file']) }}"
               target="_blank"
               class="text-sm text-salvia hover:underline truncate block">
                {{ $doc->media->original_filename }}
            </a>
            <span class="text-[10px] text-ink/35">
                {{ number_format($doc->media->size / 1024, 0) }} KB
            </span>
        </div>

        <button wire:click="deleteDoc({{ $doc->id }})"
                wire:confirm="Eliminare questo documento?"
                class="opacity-0 group-hover:opacity-100 transition-opacity text-ink/30 hover:text-terracotta text-xs">
            ✕
        </button>
    </div>
    @empty
    <p class="text-xs text-ink/30 italic px-2">Nessun documento allegato.</p>
    @endforelse

    {{-- ===== ZONA UPLOAD (click + drag-and-drop) ===== --}}
    <div x-data="{
            dragging: false,
            sizeError: '',
            checkFiles(files) {
                this.sizeError = '';
                for (const f of [...files]) {
                    if (f.size > 15 * 1024 * 1024) {
                        this.sizeError = `"${f.name}" supera il limite di 15 MB.`;
                        return false;
                    }
                }
                return true;
            },
            handleDrop(e) {
                this.dragging = false;
                const allowed = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain', 'text/csv',
                    'application/vnd.oasis.opendocument.text',
                    'application/vnd.oasis.opendocument.spreadsheet'
                ];
                const files = [...e.dataTransfer.files].filter(f => allowed.includes(f.type) || f.name.match(/\.(pdf|doc|docx|xls|xlsx|odt|ods|txt|csv)$/i));
                if (!files.length) return;
                if (!this.checkFiles(files)) return;
                const dt = new DataTransfer();
                dt.items.add(files[0]);
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
            :class="dragging ? 'border-salvia bg-salvia/10 text-salvia' : 'border-paper-dark text-ink/40 hover:text-salvia hover:border-salvia'"
            class="flex items-center gap-2 cursor-pointer text-xs border-2 border-dashed
                   rounded-lg px-3 py-2.5 mt-1 transition-all select-none">

            <span class="text-base" :class="dragging ? 'text-salvia' : 'text-ink/30'">+</span>
            <span x-show="!dragging">Clicca o trascina un documento — PDF, Word, Excel, max 15 MB</span>
            <span x-show="dragging" class="font-medium">Rilascia per allegare</span>

            <input type="file"
                   wire:model="docUpload"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                   x-on:change="handleChange($event)"
                   class="sr-only">

            <div wire:loading wire:target="docUpload" class="ml-auto text-salvia">Carico...</div>
        </label>

        <template x-if="sizeError">
            <p x-text="sizeError" class="text-xs text-terracotta mt-1 px-2"></p>
        </template>
        @error('docUpload')
            <p class="text-xs text-terracotta mt-1 px-2">{{ $message }}</p>
        @enderror
    </div>

</div>
