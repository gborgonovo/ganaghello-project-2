<div class="flex flex-col sm:flex-row gap-6 sm:gap-8 max-w-4xl mx-auto">
    <x-settings-nav />

    <div class="flex-1">
        <h1 class="text-base font-semibold text-ink mb-6">Stage</h1>

        {{-- Lista --}}
        <div class="bg-white rounded-xl border border-paper-dark divide-y divide-paper-dark mb-4">

            @forelse($stages as $stage)
            <div wire:key="stage-{{ $stage->id }}" class="px-4 py-3">

                @if($editingId === $stage->id)
                {{-- Edit inline --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <input type="text" wire:model="editLabel" placeholder="Etichetta..."
                           class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia flex-1 min-w-28">
                    @error('editLabel')<span class="text-xs text-red-500">{{ $message }}</span>@enderror

                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] text-ink/40">Sfondo</span>
                        <input type="color" wire:model="editBgColor"
                               class="w-8 h-8 rounded cursor-pointer border border-paper-dark p-0.5">
                        <span class="text-xs text-ink/40 font-mono w-16">{{ $editBgColor }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] text-ink/40">Testo</span>
                        <input type="color" wire:model="editTextColor"
                               class="w-8 h-8 rounded cursor-pointer border border-paper-dark p-0.5">
                        <span class="text-xs text-ink/40 font-mono w-16">{{ $editTextColor }}</span>
                    </div>

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

                @elseif($confirmDeleteId === $stage->id)
                {{-- Conferma delete --}}
                <div class="flex items-center gap-3">
                    @if($deleteError)
                    <span class="text-xs text-red-500 flex-1">{{ $deleteError }}</span>
                    <button wire:click="cancelDelete" class="text-xs text-ink/40 hover:text-ink">OK</button>
                    @else
                    <span class="text-sm text-ink flex-1">Eliminare <strong>{{ $stage->label }}</strong>?</span>
                    <button wire:click="delete({{ $stage->id }})"
                            class="text-xs text-terracotta font-medium hover:opacity-80">Elimina</button>
                    <button wire:click="cancelDelete"
                            class="text-xs text-ink/40 hover:text-ink">Annulla</button>
                    @endif
                </div>

                @else
                {{-- Vista normale --}}
                <div class="flex items-center gap-3 group">

                    {{-- Bottoni ordine --}}
                    <div class="flex flex-col gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button title="Sposta su" wire:click="moveUp({{ $stage->id }})"
                                class="text-[9px] text-ink/30 hover:text-ink leading-none transition-colors">▲</button>
                        <button title="Sposta giù" wire:click="moveDown({{ $stage->id }})"
                                class="text-[9px] text-ink/30 hover:text-ink leading-none transition-colors">▼</button>
                    </div>

                    {{-- Badge preview --}}
                    <span class="px-2 py-0.5 rounded text-xs font-medium shrink-0"
                          style="background-color: {{ $stage->bg_color }}; color: {{ $stage->text_color }}">
                        {{ $stage->label }}
                    </span>

                    <span class="text-xs text-ink/30 font-mono flex-1">{{ $stage->code }}</span>

                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button title="Modifica" wire:click="startEdit({{ $stage->id }})"
                                class="text-xs text-ink/40 hover:text-salvia transition-colors">
                            modifica
                        </button>
                        <button title="Elimina" wire:click="confirmDelete({{ $stage->id }})"
                                class="text-xs text-ink/30 hover:text-terracotta transition-colors">
                            ✕
                        </button>
                    </div>
                </div>
                @endif

            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-ink/30 italic">Nessuno stage.</div>
            @endforelse

        </div>

        {{-- Crea nuovo --}}
        <div class="bg-white rounded-xl border border-paper-dark p-4">
            <p class="text-xs font-medium text-ink/50 mb-3">Nuovo stage</p>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-[10px] text-ink/40 mb-1">Codice (es. todo)</label>
                    <input type="text" wire:model="newCode" placeholder="codice_slug"
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia font-mono">
                    @error('newCode')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[10px] text-ink/40 mb-1">Etichetta</label>
                    <input type="text" wire:model="newLabel" placeholder="Da fare"
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia">
                    @error('newLabel')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex items-center gap-4 mb-3">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-ink/50">Sfondo</label>
                    <input type="color" wire:model="newBgColor"
                           class="w-8 h-8 rounded cursor-pointer border border-paper-dark p-0.5">
                    <span class="text-xs text-ink/40 font-mono">{{ $newBgColor }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-ink/50">Testo</label>
                    <input type="color" wire:model="newTextColor"
                           class="w-8 h-8 rounded cursor-pointer border border-paper-dark p-0.5">
                    <span class="text-xs text-ink/40 font-mono">{{ $newTextColor }}</span>
                </div>
                {{-- Preview badge --}}
                <span class="px-2 py-0.5 rounded text-xs font-medium"
                      style="background-color: {{ $newBgColor }}; color: {{ $newTextColor }}">
                    {{ $newLabel ?: 'Anteprima' }}
                </span>
            </div>
            <button wire:click="create"
                    class="px-4 py-1.5 bg-salvia text-white rounded-lg text-sm
                           hover:bg-salvia-dark transition-colors">
                + Aggiungi
            </button>
        </div>

    </div>
</div>
