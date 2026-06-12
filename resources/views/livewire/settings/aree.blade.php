<div class="flex flex-col sm:flex-row gap-6 sm:gap-8 max-w-4xl mx-auto">
    <x-settings-nav />

    <div class="flex-1">
        <h1 class="text-base font-semibold text-ink mb-6">Aree</h1>

        {{-- Lista --}}
        <div class="bg-white rounded-xl border border-paper-dark divide-y divide-paper-dark mb-4">

            @forelse($aree as $area)
            @php $total = $area->tasks_count + $area->notes_count + $area->entries_count + $area->inspirations_count + $area->posts_count; @endphp
            <div wire:key="area-{{ $area->id }}" class="px-4 py-3">

                @if($editingId === $area->id)
                {{-- Edit inline --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <input type="text" wire:model="editName"
                           class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia flex-1 min-w-32">
                    @error('editName')<span class="text-xs text-red-500">{{ $message }}</span>@enderror

                    <div class="flex items-center gap-1.5">
                        <input type="color" wire:model="editColor"
                               class="w-8 h-8 rounded cursor-pointer border border-paper-dark p-0.5">
                        <span class="text-xs text-ink/40 font-mono w-16">{{ $editColor }}</span>
                    </div>

                    <select wire:model="editParentId"
                            class="text-xs border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white
                                   focus:outline-none focus:border-salvia">
                        <option value="">Nessun padre</option>
                        @foreach($parents->where('id', '!=', $area->id) as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>

                    <button wire:click="saveEdit"
                            class="text-xs px-3 py-1.5 bg-salvia text-white rounded-lg
                                   hover:bg-salvia-dark transition-colors">
                        Salva
                    </button>
                    <button wire:click="cancelEdit"
                            class="text-xs text-ink/40 hover:text-ink transition-colors">
                        Annulla
                    </button>
                </div>

                @elseif($confirmDeleteId === $area->id)
                {{-- Conferma delete --}}
                <div class="flex items-center gap-3">
                    @if($deleteError)
                    <span class="text-xs text-red-500 flex-1">{{ $deleteError }}</span>
                    <button wire:click="cancelDelete" class="text-xs text-ink/40 hover:text-ink">OK</button>
                    @else
                    <span class="text-sm text-ink flex-1">Eliminare <strong>{{ $area->name }}</strong>?</span>
                    <button wire:click="delete({{ $area->id }})"
                            class="text-xs text-terracotta font-medium hover:opacity-80">Elimina</button>
                    <button wire:click="cancelDelete"
                            class="text-xs text-ink/40 hover:text-ink">Annulla</button>
                    @endif
                </div>

                @else
                {{-- Vista normale --}}
                <div class="flex items-center gap-3 group">
                    <span class="w-3 h-3 rounded-full shrink-0"
                          style="background-color: {{ $area->color ?? '#ccc' }}"></span>

                    <span class="text-sm text-ink flex-1">
                        @if($area->parent)
                        <span class="text-ink/30 text-xs">{{ $area->parent->name }} /</span>
                        @endif
                        {{ $area->name }}
                    </span>

                    @if($total > 0)
                    <span class="text-[10px] text-ink/30">{{ $total }} elem.</span>
                    @endif

                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button wire:click="startEdit({{ $area->id }})"
                                class="text-xs text-ink/40 hover:text-salvia transition-colors">
                            modifica
                        </button>
                        <button wire:click="confirmDelete({{ $area->id }})"
                                class="text-xs text-ink/30 hover:text-terracotta transition-colors">
                            ✕
                        </button>
                    </div>
                </div>
                @endif

            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-ink/30 italic">Nessuna area.</div>
            @endforelse

        </div>

        {{-- Crea nuova --}}
        <div class="bg-white rounded-xl border border-paper-dark p-4">
            <p class="text-xs font-medium text-ink/50 mb-3">Nuova area</p>
            <div class="flex items-center gap-3 flex-wrap">
                <input type="text" wire:model="newName" placeholder="Nome area..."
                       class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                              focus:outline-none focus:border-salvia flex-1 min-w-32">
                @error('newName')<span class="text-xs text-red-500">{{ $message }}</span>@enderror

                <div class="flex items-center gap-1.5">
                    <input type="color" wire:model="newColor"
                           class="w-8 h-8 rounded cursor-pointer border border-paper-dark p-0.5">
                    <span class="text-xs text-ink/40 font-mono w-16">{{ $newColor }}</span>
                </div>

                <select wire:model="newParentId"
                        class="text-xs border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white
                               focus:outline-none focus:border-salvia">
                    <option value="">Nessun padre</option>
                    @foreach($parents as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>

                <button wire:click="create"
                        class="px-4 py-1.5 bg-salvia text-white rounded-lg text-sm
                               hover:bg-salvia-dark transition-colors">
                    + Aggiungi
                </button>
            </div>
        </div>

    </div>
</div>
