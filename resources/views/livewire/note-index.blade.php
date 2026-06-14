<div class="space-y-5 pb-16">

    {{-- ===== CATTURA RAPIDA ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark p-4 space-y-3"
         x-data>
        <input type="text" wire:model="newTitle"
               placeholder="Titolo..."
               class="w-full text-sm font-semibold border border-paper-dark rounded-lg px-3 py-2
                      focus:outline-none focus:border-salvia placeholder:text-ink/30 placeholder:font-normal">
        @error('newTitle')<p class="text-xs text-red-500 -mt-1">{{ $message }}</p>@enderror
        <textarea wire:model="newContent"
                  rows="3"
                  placeholder="Testo della nota... (Ctrl+Invio per salvare)"
                  x-on:keydown.ctrl.enter="$wire.create()"
                  class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2.5
                         focus:outline-none focus:border-salvia resize-none placeholder:text-ink/30
                         font-serif"></textarea>
        @error('newContent')<p class="text-xs text-red-500 -mt-1">{{ $message }}</p>@enderror
        <div class="flex items-center justify-between gap-3">
            <select wire:model="newAreaId"
                    class="text-sm border border-paper-dark rounded-lg px-3 py-1.5 bg-white
                           focus:outline-none focus:border-salvia text-ink/70">
                <option value="">Nessuna area</option>
                @foreach($aree as $area)
                <option value="{{ $area->id }}">{{ $area->name }}</option>
                @endforeach
            </select>
            <button wire:click="create"
                    class="px-4 py-1.5 bg-salvia text-white rounded-lg text-sm
                           hover:bg-salvia-dark transition-colors">
                Annota
            </button>
        </div>
    </div>

    {{-- ===== RICERCA + FILTRO AREA ===== --}}
    <div class="flex items-center gap-3 flex-wrap">
        <input type="text"
               wire:model.live.debounce.300ms="search"
               placeholder="Cerca nelle note..."
               class="text-sm border border-paper-dark rounded-lg px-3 py-1.5 bg-white
                      focus:outline-none focus:border-salvia w-52">

        <div class="flex items-center gap-1.5 flex-wrap">
            <button wire:click="$set('filterAreaId', null)"
                    class="px-3 py-1.5 rounded-full text-xs transition-colors
                           {{ !$filterAreaId ? 'bg-salvia text-white' : 'bg-paper-dark text-ink/60 hover:bg-paper-dark/70' }}">
                Tutte
            </button>
            @foreach($aree as $area)
            <button wire:click="$set('filterAreaId', {{ $area->id }})"
                    class="px-3 py-1.5 rounded-full text-xs flex items-center gap-1 transition-colors
                           {{ $filterAreaId === $area->id ? 'bg-salvia text-white' : 'bg-paper-dark text-ink/60 hover:bg-paper-dark/70' }}">
                @if($area->color)
                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $area->color }}"></span>
                @endif
                {{ $area->name }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- ===== FLASH TASK CREATO ===== --}}
    @if($convertedTaskId)
    <div class="flex items-center justify-between bg-salvia/10 border border-salvia/30 rounded-lg px-4 py-2.5">
        <span class="text-sm text-salvia">Task creato con successo.</span>
        <div class="flex items-center gap-3">
            <a href="{{ route('tasks.show', $convertedTaskId) }}" wire:navigate
               class="text-sm font-medium text-salvia hover:underline">Apri task →</a>
            <button wire:click="$set('convertedTaskId', null)"
                    class="text-ink/30 hover:text-ink text-xs">✕</button>
        </div>
    </div>
    @endif

    {{-- ===== GRIGLIA NOTE ===== --}}
    @if($notes->isEmpty())
    <div class="bg-white rounded-xl border border-paper-dark px-6 py-14 text-center">
        <p class="text-ink/30 italic text-sm">
            {{ $search ? 'Nessuna nota trovata.' : 'Nessuna nota ancora. Scrivine una sopra!' }}
        </p>
    </div>
    @else
    <div class="columns-2 md:columns-3 lg:columns-4 gap-3">
        @foreach($notes as $note)
        @php $isEditing = $editingId === $note->id; @endphp
        <div class="break-inside-avoid mb-3 rounded-xl border border-paper-dark bg-white p-3.5 group
                    hover:border-salvia-light hover:shadow-sm transition-all"
             wire:key="note-{{ $note->id }}">

            @if($isEditing)
            {{-- ===== EDIT INLINE ===== --}}
            <input type="text" wire:model="editTitle"
                   placeholder="Titolo (opzionale)..."
                   class="w-full text-sm font-semibold border border-paper-dark rounded px-2 py-1 mb-2
                          focus:outline-none focus:border-salvia">
            <textarea wire:model="editContent" rows="4"
                      class="w-full text-sm border border-paper-dark rounded px-2 py-1 resize-none
                             focus:outline-none focus:border-salvia font-serif leading-relaxed mb-2"></textarea>
            <select wire:model="editAreaId"
                    class="w-full text-xs border border-paper-dark rounded px-2 py-1 mb-3
                           focus:outline-none focus:border-salvia bg-white">
                <option value="">Nessuna area</option>
                @foreach($aree as $area)
                <option value="{{ $area->id }}">{{ $area->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button wire:click="saveEdit"
                        class="text-xs px-3 py-1 bg-salvia text-white rounded
                               hover:bg-salvia-dark transition-colors">
                    Salva
                </button>
                <button wire:click="cancelEdit"
                        class="text-xs text-ink/40 hover:text-ink transition-colors">
                    Annulla
                </button>
            </div>

            @else
            {{-- ===== VISTA NORMALE ===== --}}
            <a href="{{ route('note.show', $note->id) }}" wire:navigate
               class="block text-sm font-semibold text-ink hover:text-salvia transition-colors mb-1.5 leading-snug">
                {{ $note->title }}
            </a>

            <p class="text-sm text-ink/75 font-serif leading-relaxed whitespace-pre-wrap">{{ Str::limit($note->content, 200) }}</p>

            {{-- Footer --}}
            <div class="flex items-center justify-between mt-3 pt-2 border-t border-paper-dark/50 gap-2">
                <div class="min-w-0">
                    @if($note->area)
                    <span class="flex items-center gap-1 text-[10px] text-ink/40 truncate">
                        @if($note->area->color)
                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                              style="background-color: {{ $note->area->color }}"></span>
                        @endif
                        {{ $note->area->name }}
                    </span>
                    @endif
                </div>

                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                    <button wire:click="convertToTask({{ $note->id }})"
                            title="Converti in task"
                            class="text-[10px] text-ink/40 hover:text-salvia transition-colors px-1">
                        → task
                    </button>
                    <button wire:click="startEdit({{ $note->id }})"
                            class="text-[10px] text-ink/40 hover:text-ink transition-colors px-1">
                        modifica
                    </button>
                    @if($confirmDeleteId === $note->id)
                    <button wire:click="delete({{ $note->id }})"
                            class="text-[10px] text-terracotta font-medium px-1">Elimina?</button>
                    <button wire:click="$set('confirmDeleteId', null)"
                            class="text-[10px] text-ink/30 px-1">no</button>
                    @else
                    <button wire:click="$set('confirmDeleteId', {{ $note->id }})"
                            class="text-[10px] text-ink/30 hover:text-terracotta transition-colors px-1">
                        ✕
                    </button>
                    @endif
                </div>
            </div>
            @endif

        </div>
        @endforeach
    </div>
    @endif

</div>
