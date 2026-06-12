<div class="space-y-4 pb-10">

    {{-- Griglia aree masonry --}}
    <div class="columns-2 md:columns-3 gap-4">

        @foreach($areas as $area)
        <div class="break-inside-avoid mb-4 rounded-xl border border-paper-dark bg-white px-5 py-4 space-y-3"
             wire:key="area-{{ $area->id }}">

            @if($editingId === $area->id)
            {{-- === FORM EDIT === --}}
            <div class="space-y-2">
                <input wire:model="editName"
                       wire:keydown.enter="saveEdit"
                       wire:keydown.escape="cancelEdit"
                       type="text"
                       class="w-full text-sm font-medium border-b border-salvia bg-transparent
                              focus:outline-none pb-0.5"
                       autofocus>
                @error('editName') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                <div class="flex items-center gap-3 flex-wrap">
                    <div class="flex items-center gap-1.5">
                        <input wire:model="editColor" type="color"
                               class="w-7 h-7 rounded cursor-pointer border border-paper-dark">
                        <span class="text-xs text-ink/50">Colore</span>
                    </div>
                    <select wire:model="editStatus"
                            class="text-xs border border-paper-dark rounded-lg px-2 py-1 bg-white
                                   focus:outline-none focus:border-salvia">
                        <option value="">Stato automatico</option>
                        <option value="cantiere">Cantiere</option>
                        <option value="attiva">Attiva</option>
                        <option value="in_valutazione">In valutazione</option>
                        <option value="dormiente">Dormiente</option>
                    </select>
                </div>

                <textarea wire:model="editDesc" rows="2" placeholder="Descrizione..."
                          class="w-full text-xs border border-paper-dark rounded-lg px-2.5 py-1.5
                                 resize-none focus:outline-none focus:border-salvia
                                 placeholder:text-ink/30"></textarea>

                <div>
                    <label class="text-xs text-ink/50 block mb-0.5">Dentro a</label>
                    <select wire:model="editParentId"
                            class="text-xs border border-paper-dark rounded-lg px-2 py-1 bg-white
                                   focus:outline-none focus:border-salvia">
                        <option value="">Area radice</option>
                        @foreach($allAreas->where('id', '!=', $editingId) as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button wire:click="saveEdit"
                            class="text-xs px-3 py-1.5 bg-salvia text-white rounded-lg
                                   hover:bg-salvia-dark transition-colors">
                        Salva
                    </button>
                    <button wire:click="cancelEdit"
                            class="text-xs text-ink/50 hover:text-ink transition-colors">
                        Annulla
                    </button>
                </div>
            </div>

            @else
            {{-- === SCHEDA AREA === --}}
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    @if($area->color)
                    <span class="w-3 h-3 rounded-full shrink-0"
                          style="background-color: {{ $area->color }}"></span>
                    @endif
                    <a href="{{ route('aree.show', $area) }}"
                       class="font-semibold text-ink hover:text-salvia transition-colors truncate">
                        {{ $area->name }}
                    </a>
                </div>
                <button wire:click="startEdit({{ $area->id }})"
                        class="shrink-0 text-ink/30 hover:text-salvia transition-colors text-sm">
                    ✎
                </button>
            </div>

            @if($area->description)
            <p class="text-xs text-ink/50 leading-relaxed">{{ $area->description }}</p>
            @endif

            <div class="flex items-center justify-between text-xs text-ink/50">
                <span>{{ $area->tasks_count }} task totali</span>
                @if($area->open_tasks_count > 0)
                <span class="text-salvia">{{ $area->open_tasks_count }} aperti</span>
                @endif
            </div>

            {{-- Sotto-aree come mini-box --}}
            @if($area->children->isNotEmpty())
            <div class="border-t border-paper-dark pt-3 grid grid-cols-2 gap-2">
                @foreach($area->children as $child)
                <a href="{{ route('aree.show', $child) }}"
                   class="block rounded-lg border border-paper-dark px-2.5 py-2
                          hover:border-salvia group transition-colors">
                    <div class="flex items-center gap-1.5 mb-1 min-w-0">
                        @if($child->color)
                        <span class="w-2 h-2 rounded-full shrink-0"
                              style="background-color: {{ $child->color }}"></span>
                        @endif
                        <span class="text-xs font-medium text-ink group-hover:text-salvia
                                     transition-colors truncate">
                            {{ $child->name }}
                        </span>
                    </div>
                    <p class="text-[10px] text-ink/40">
                        @if($child->open_tasks_count > 0)
                            {{ $child->open_tasks_count }} aperti
                        @else
                            nessun task
                        @endif
                    </p>
                </a>
                @endforeach
            </div>
            @endif

            @if($confirmDeleteId === $area->id)
            <div class="flex items-center gap-2 pt-1 border-t border-paper-dark">
                <span class="text-xs text-ink/60">Eliminare quest'area?</span>
                <button wire:click="deleteArea({{ $area->id }})"
                        class="text-xs px-2 py-1 bg-terracotta text-white rounded hover:opacity-90">
                    Elimina
                </button>
                <button wire:click="$set('confirmDeleteId', null)"
                        class="text-xs text-ink/40 hover:text-ink">
                    Annulla
                </button>
            </div>
            @else
            @if($area->tasks_count === 0 && $area->children->isEmpty())
            <button wire:click="$set('confirmDeleteId', {{ $area->id }})"
                    class="text-xs text-ink/25 hover:text-terracotta transition-colors">
                Elimina
            </button>
            @endif
            @endif

            @endif
        </div>
        @endforeach

        {{-- Tessera "Nuova area" --}}
        @if(!$showCreate)
        <div class="break-inside-avoid mb-4">
            <button wire:click="$set('showCreate', true)"
                    class="w-full rounded-xl border border-dashed border-paper-dark text-ink/30
                           hover:border-salvia hover:text-salvia transition-colors px-5 py-4
                           flex items-center justify-center gap-1.5 text-sm min-h-24">
                <span class="text-lg leading-none">+</span>
                <span>Nuova area</span>
            </button>
        </div>
        @else
        <div class="break-inside-avoid mb-4 rounded-xl border border-salvia-light bg-white px-5 py-4 space-y-3">
            <p class="text-sm font-medium text-ink">Nuova area</p>
            <input wire:model="newName"
                   wire:keydown.enter="createArea"
                   wire:keydown.escape="$set('showCreate', false)"
                   type="text"
                   placeholder="Nome area..."
                   autofocus
                   class="w-full text-sm border-b border-salvia bg-transparent focus:outline-none pb-0.5
                          placeholder:text-ink/30">
            @error('newName') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

            <div class="flex items-center gap-2">
                <input wire:model="newColor" type="color"
                       class="w-7 h-7 rounded cursor-pointer border border-paper-dark">
                <span class="text-xs text-ink/50">Colore</span>
            </div>

            <textarea wire:model="newDesc" rows="2" placeholder="Descrizione (opzionale)..."
                      class="w-full text-xs border border-paper-dark rounded-lg px-2.5 py-1.5
                             resize-none focus:outline-none focus:border-salvia
                             placeholder:text-ink/30"></textarea>

            <div>
                <label class="text-xs text-ink/50 block mb-0.5">Dentro a</label>
                <select wire:model="newParentId"
                        class="text-xs border border-paper-dark rounded-lg px-2 py-1 bg-white
                               focus:outline-none focus:border-salvia">
                    <option value="">Area radice</option>
                    @foreach($allAreas as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button wire:click="createArea"
                        class="text-xs px-3 py-1.5 bg-salvia text-white rounded-lg hover:bg-salvia-dark">
                    Crea
                </button>
                <button wire:click="$set('showCreate', false)"
                        class="text-xs text-ink/50 hover:text-ink">
                    Annulla
                </button>
            </div>
        </div>
        @endif

    </div>

</div>
