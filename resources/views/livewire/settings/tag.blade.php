<div class="flex flex-col sm:flex-row gap-6 sm:gap-8 max-w-4xl mx-auto">
    <x-settings-nav />

    <div class="flex-1 max-w-lg">
        <h1 class="text-base font-semibold text-ink mb-6">Tag</h1>

        {{-- Lista --}}
        <div class="bg-white rounded-xl border border-paper-dark divide-y divide-paper-dark mb-4">

            @forelse($tags as $tag)
            @php $total = $tag->tasks_count + $tag->notes_count + $tag->entries_count + $tag->inspirations_count + $tag->posts_count; @endphp
            <div wire:key="tag-{{ $tag->id }}" class="px-4 py-3">

                @if($editingId === $tag->id)
                <div class="flex items-center gap-3">
                    <input type="text" wire:model="editName"
                           class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia flex-1">
                    @error('editName')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                    <input type="color" wire:model="editColor"
                           class="w-8 h-8 rounded cursor-pointer border border-paper-dark p-0.5">
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

                @elseif($confirmDeleteId === $tag->id)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-ink flex-1">
                        Eliminare <strong>{{ $tag->display_name }}</strong>?
                        @if($total > 0)<span class="text-xs text-ink/40">(rimosso da {{ $total }} elementi)</span>@endif
                    </span>
                    <button wire:click="delete({{ $tag->id }})"
                            class="text-xs text-terracotta font-medium hover:opacity-80">Elimina</button>
                    <button wire:click="$set('confirmDeleteId', null)"
                            class="text-xs text-ink/40 hover:text-ink">Annulla</button>
                </div>

                @else
                <div class="flex items-center gap-3 group">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0"
                          style="background-color: {{ $tag->color ?? '#ccc' }}"></span>
                    <span class="text-sm text-ink flex-1">{{ $tag->display_name }}</span>
                    @if($total > 0)
                    <span class="text-[10px] text-ink/30">{{ $total }} elem.</span>
                    @endif
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button title="Modifica" wire:click="startEdit({{ $tag->id }})"
                                class="text-xs text-ink/40 hover:text-salvia transition-colors">
                            modifica
                        </button>
                        <button title="Elimina" wire:click="$set('confirmDeleteId', {{ $tag->id }})"
                                class="text-xs text-ink/30 hover:text-terracotta transition-colors">
                            ✕
                        </button>
                    </div>
                </div>
                @endif

            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-ink/30 italic">Nessun tag.</div>
            @endforelse

        </div>

        {{-- Crea nuovo --}}
        <div class="bg-white rounded-xl border border-paper-dark p-4">
            <p class="text-xs font-medium text-ink/50 mb-3">Nuovo tag</p>
            <div class="flex items-center gap-3">
                <input type="text" wire:model="newName" placeholder="nome-tag..."
                       class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                              focus:outline-none focus:border-salvia flex-1"
                       wire:keydown.enter="create">
                @error('newName')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                <input type="color" wire:model="newColor"
                       class="w-8 h-8 rounded cursor-pointer border border-paper-dark p-0.5">
                <button wire:click="create"
                        class="px-4 py-1.5 bg-salvia text-white rounded-lg text-sm
                               hover:bg-salvia-dark transition-colors">
                    + Aggiungi
                </button>
            </div>
        </div>

    </div>
</div>
