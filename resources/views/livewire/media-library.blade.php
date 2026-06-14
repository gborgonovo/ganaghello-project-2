<div class="space-y-5 pb-16">

    {{-- ===== TOOLBAR ===== --}}
    <div class="flex items-center gap-4 flex-wrap">

        <div class="flex rounded-lg border border-paper-dark overflow-hidden">
            <button wire:click="switchTab('images')"
                    class="px-4 py-2 text-sm transition-colors
                           {{ $tab === 'images' ? 'bg-salvia text-white' : 'bg-white text-ink/60 hover:bg-paper-dark/40' }}">
                Immagini
                <span class="ml-1.5 text-xs {{ $tab === 'images' ? 'text-white/70' : 'text-ink/40' }}">{{ $images->count() }}</span>
            </button>
            <button wire:click="switchTab('docs')"
                    class="px-4 py-2 text-sm transition-colors border-l border-paper-dark
                           {{ $tab === 'docs' ? 'bg-salvia text-white' : 'bg-white text-ink/60 hover:bg-paper-dark/40' }}">
                Documenti
                <span class="ml-1.5 text-xs {{ $tab === 'docs' ? 'text-white/70' : 'text-ink/40' }}">{{ $docs->count() }}</span>
            </button>
        </div>

        <select wire:model.live="areaId"
                class="text-sm border border-paper-dark rounded-lg px-3 py-2 bg-white text-ink focus:outline-none focus:border-salvia">
            <option value="">Tutte le aree</option>
            @foreach($aree as $area)
            <option value="{{ $area->id }}">{{ $area->name }}</option>
            @endforeach
        </select>

        {{-- Filtro context --}}
        <div class="flex rounded-lg border border-paper-dark overflow-hidden ml-auto">
            <button wire:click="$set('filterContext', '')"
                    class="px-3 py-2 text-xs transition-colors
                           {{ $filterContext === '' ? 'bg-salvia text-white' : 'bg-white text-ink/60 hover:bg-paper-dark/40' }}">
                Generali
            </button>
            <button wire:click="$set('filterContext', 'inspiration')"
                    class="px-3 py-2 text-xs transition-colors border-l border-paper-dark
                           {{ $filterContext === 'inspiration' ? 'bg-salvia text-white' : 'bg-white text-ink/60 hover:bg-paper-dark/40' }}">
                Ispirazioni
            </button>
            <button wire:click="$set('filterContext', null)"
                    class="px-3 py-2 text-xs transition-colors border-l border-paper-dark
                           {{ $filterContext === null ? 'bg-salvia text-white' : 'bg-white text-ink/60 hover:bg-paper-dark/40' }}">
                Tutte
            </button>
        </div>
    </div>

    {{-- ===== TAB IMMAGINI ===== --}}
    @if($tab === 'images')

        {{-- Zona upload immagini --}}
        <div x-data="{
                dragging: false,
                sizeError: '',
                checkFiles(files) {
                    this.sizeError = '';
                    for (const f of [...files]) {
                        if (f.size > 25 * 1024 * 1024) {
                            this.sizeError = `"${f.name}" supera il limite di 25 MB.`;
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
                :class="dragging ? 'border-salvia bg-salvia/10' : 'border-paper-dark hover:border-salvia hover:bg-salvia/5'"
                class="flex flex-col items-center justify-center gap-1.5 border-2 border-dashed
                       rounded-xl py-6 px-3 cursor-pointer transition-all select-none">

                <span class="text-2xl" :class="dragging ? 'text-salvia' : 'text-ink/25'">⊕</span>
                <span class="text-xs" :class="dragging ? 'text-salvia font-medium' : 'text-ink/40'">
                    <span x-show="!dragging">Clicca o trascina qui le immagini</span>
                    <span x-show="dragging">Rilascia per caricare</span>
                </span>
                <span class="text-[10px] text-ink/30" x-show="!dragging">JPG, PNG, WEBP, GIF — max 25 MB</span>

                <input type="file" wire:model="imgUploads" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.heic,.heif"
                       x-on:change="handleChange($event)" class="sr-only">
                <div wire:loading wire:target="imgUploads" class="text-xs text-salvia mt-1">Elaborazione...</div>
            </label>

            <template x-if="sizeError">
                <p x-text="sizeError" class="text-xs text-terracotta mt-1"></p>
            </template>
            @error('imgUploads.*')
                <p class="text-xs text-terracotta mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Griglia --}}
        @if($images->isEmpty())
        <p class="text-center text-sm text-ink/30 italic py-6">Nessuna immagine ancora.</p>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($images as $media)
            @php
                $linkedTask  = $media->attachments->first()?->attachable;
                $isLinking   = $linkingMediaId === $media->id;
            @endphp
            <div class="relative group rounded-xl overflow-hidden bg-paper-dark aspect-square"
                 wire:key="lib-img-{{ $media->id }}">

                <img src="{{ route('media.serve', [$media, 'thumb']) }}"
                     alt="{{ $media->original_filename }}"
                     class="w-full h-full object-cover {{ $isLinking ? 'opacity-40' : 'cursor-pointer' }}"
                     @if(!$isLinking) wire:click="openLightbox({{ $media->id }})" @endif>

                {{-- Overlay collegamento task (attivo) --}}
                @if($isLinking)
                <div class="absolute inset-0 flex flex-col justify-center gap-2 p-2 bg-ink/70">
                    <p class="text-[10px] text-white/70 text-center">Collega a task:</p>
                    <select wire:model="linkTaskId"
                            class="w-full text-xs rounded px-1.5 py-1 bg-white text-ink border-0
                                   focus:outline-none focus:ring-1 focus:ring-salvia">
                        <option value="">— scegli —</option>
                        @foreach($tasks as $t)
                        <option value="{{ $t->id }}">
                            {{ $t->title }}{{ $t->area ? ' · '.$t->area->name : '' }}
                        </option>
                        @endforeach
                    </select>
                    <div class="flex gap-1.5 justify-center">
                        <button wire:click="confirmLink"
                                @if(!$linkTaskId) disabled @endif
                                class="flex-1 text-[10px] py-1 rounded
                                       {{ $linkTaskId ? 'bg-salvia text-white hover:bg-salvia-dark' : 'bg-white/20 text-white/40 cursor-not-allowed' }}
                                       transition-colors">
                            Collega
                        </button>
                        <button wire:click="cancelLink"
                                class="flex-1 text-[10px] py-1 rounded bg-white/20 text-white/80
                                       hover:bg-white/30 transition-colors">
                            Annulla
                        </button>
                    </div>
                </div>

                {{-- Overlay normale (hover) --}}
                @else
                <div class="absolute inset-0 bg-ink/50 opacity-0 group-hover:opacity-100 transition-opacity
                            flex flex-col justify-between p-2">
                    {{-- Task collegato --}}
                    @if($linkedTask)
                    <a href="{{ route('tasks.show', $linkedTask) }}" wire:navigate
                       class="text-[10px] text-white/80 hover:text-white truncate leading-tight">
                        {{ $linkedTask->title }}
                    </a>
                    @else
                    <button wire:click="startLink({{ $media->id }})"
                            class="text-[10px] text-white/60 hover:text-white text-left transition-colors">
                        + Collega a task
                    </button>
                    @endif

                    <div class="flex justify-between items-end">
                        <button wire:click="openLightbox({{ $media->id }})"
                                class="text-[10px] text-white/70 hover:text-white">⊕ apri</button>
                        <div class="flex items-center gap-1.5">
                            <button wire:click="toggleContext({{ $media->id }})"
                                    title="{{ $media->context === 'inspiration' ? 'Rimuovi da ispirazioni' : 'Segna come ispirazione' }}"
                                    class="text-[10px] transition-colors {{ $media->context === 'inspiration' ? 'text-terracotta/90 hover:text-white' : 'text-white/40 hover:text-terracotta/80' }}">
                                ✦
                            </button>
                            <x-confirm action="deleteMedia({{ $media->id }})" tone="dark"
                                    class="text-[10px] text-white/70 hover:text-terracotta transition-colors">✕</x-confirm>
                        </div>
                    </div>
                </div>
                @endif

            </div>
            @endforeach
        </div>
        @endif

    @endif

    {{-- ===== TAB DOCUMENTI ===== --}}
    @if($tab === 'docs')

        {{-- Zona upload documenti --}}
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
                    const allowed = ['application/pdf','application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain','text/csv'];
                    const files = [...e.dataTransfer.files]
                        .filter(f => allowed.includes(f.type) || f.name.match(/\.(pdf|doc|docx|xls|xlsx|odt|ods|txt|csv)$/i));
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
                :class="dragging ? 'border-salvia bg-salvia/10' : 'border-paper-dark hover:border-salvia hover:bg-salvia/5'"
                class="flex items-center justify-center gap-2 border-2 border-dashed
                       rounded-xl py-4 px-3 cursor-pointer transition-all select-none">

                <span class="text-xl" :class="dragging ? 'text-salvia' : 'text-ink/25'">+</span>
                <span class="text-sm" :class="dragging ? 'text-salvia font-medium' : 'text-ink/40'">
                    <span x-show="!dragging">Clicca o trascina un documento — PDF, Word, Excel, max 15 MB</span>
                    <span x-show="dragging">Rilascia per allegare</span>
                </span>

                <input type="file" wire:model="docUpload"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                       x-on:change="handleChange($event)" class="sr-only">
                <div wire:loading wire:target="docUpload" class="text-xs text-salvia ml-2">Carico...</div>
            </label>

            <template x-if="sizeError">
                <p x-text="sizeError" class="text-xs text-terracotta mt-1"></p>
            </template>
            @error('docUpload')
                <p class="text-xs text-terracotta mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Lista --}}
        @if($docs->isEmpty())
        <p class="text-center text-sm text-ink/30 italic py-6">Nessun documento ancora.</p>
        @else
        <div class="bg-white rounded-xl border border-paper-dark divide-y divide-paper-dark">
            @foreach($docs as $media)
            @php $task = $media->attachments->first()?->attachable; @endphp
            <div class="flex items-center gap-4 px-5 py-3 hover:bg-paper/60 group"
                 wire:key="lib-doc-{{ $media->id }}">

                <span class="text-xl shrink-0 text-ink/40">
                    @if($media->mime_type === 'application/pdf') 📄
                    @elseif(str_contains($media->mime_type, 'word')) 📝
                    @elseif(str_contains($media->mime_type, 'spreadsheet') || str_contains($media->mime_type, 'excel')) 📊
                    @else 📎
                    @endif
                </span>

                <div class="flex-1 min-w-0">
                    <a href="{{ route('media.serve', [$media, 'file']) }}"
                       target="_blank"
                       class="text-sm text-salvia hover:underline truncate block">
                        {{ $media->original_filename }}
                    </a>
                    <div class="flex items-center gap-3 mt-0.5">
                        <span class="text-[10px] text-ink/35">{{ number_format($media->size / 1024, 0) }} KB</span>
                        @if($task)
                        <a href="{{ route('tasks.show', $task) }}" wire:navigate
                           class="text-[10px] text-ink/40 hover:text-salvia truncate">
                            {{ $task->title }}
                        </a>
                        @else
                        <span class="text-[10px] text-ink/30 italic">Non collegato</span>
                        @endif
                    </div>
                </div>

                <x-confirm action="deleteMedia({{ $media->id }})"
                        class="opacity-0 group-hover:opacity-100 transition-opacity text-ink/30 hover:text-terracotta text-xs">
                    ✕ elimina
                </x-confirm>
            </div>
            @endforeach
        </div>
        @endif

    @endif

    {{-- ===== LIGHTBOX ===== --}}
    @if($lightboxMedia)
    <div class="fixed inset-0 z-50 bg-ink/80 flex items-center justify-center p-4"
         wire:click.self="closeLightbox">
        <div class="relative max-w-4xl w-full bg-paper rounded-xl overflow-hidden shadow-2xl">
            <button wire:click="closeLightbox"
                    class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-ink/50 text-white
                           flex items-center justify-center text-sm hover:bg-ink transition-colors">
                ✕
            </button>
            <img src="{{ route('media.serve', [$lightboxMedia, 'medium']) }}"
                 alt="{{ $lightboxMedia->original_filename }}"
                 class="w-full max-h-[80vh] object-contain">
            @php $task = $lightboxMedia->attachments->first()?->attachable; @endphp
            <div class="px-5 py-3 flex items-center justify-between gap-4">
                <span class="text-xs text-ink/40">{{ $lightboxMedia->original_filename }}</span>
                @if($task)
                <a href="{{ route('tasks.show', $task) }}" wire:navigate
                   class="text-xs text-salvia hover:underline shrink-0">
                    → {{ $task->title }}
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
