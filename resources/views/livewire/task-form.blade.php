<div>
{{-- Trigger: bottone "Nuovo task" --}}
<button wire:click="openModal"
    class="px-3 py-1.5 rounded-lg bg-salvia text-white text-sm font-medium hover:bg-salvia-dark transition-colors">
    + Nuovo task
</button>

{{-- Overlay + modale --}}
@if($open)
<div class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4"
     x-data
     @keydown.escape.window="$wire.closeModal()">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-ink/30 backdrop-blur-sm"
         wire:click="closeModal"></div>

    {{-- Modale --}}
    <div class="relative w-full max-w-xl bg-white rounded-xl shadow-xl border border-paper-dark z-10
                transition-all duration-200"
         @click.stop>

        <div class="px-6 py-5 space-y-4">

            {{-- Titolo --}}
            <div>
                <input wire:model="title"
                    type="text"
                    placeholder="Titolo del task..."
                    autofocus
                    class="w-full text-base font-medium text-ink bg-transparent border-0 border-b border-paper-dark
                           focus:outline-none focus:border-salvia pb-1.5 placeholder:text-ink/30">
                @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Descrizione --}}
            <textarea wire:model="description"
                rows="2"
                placeholder="Descrizione (opzionale)..."
                class="w-full text-sm text-ink bg-transparent border-0 resize-none
                       focus:outline-none placeholder:text-ink/30 leading-relaxed"></textarea>

            {{-- Riga: Area + Stage --}}
            <div class="flex items-center gap-2 flex-wrap">
                <select wire:model="area_id"
                    class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                           focus:outline-none focus:border-salvia">
                    <option value="">Area...</option>
                    <x-area-options />
                </select>

                <select wire:model="stage_id"
                    class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                           focus:outline-none focus:border-salvia">
                    @foreach($stages as $stage)
                    <option value="{{ $stage->id }}">{{ $stage->label }}</option>
                    @endforeach
                </select>

                @if($expanded)
                <select wire:model="priority"
                    class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                           focus:outline-none focus:border-salvia">
                    <option value="">Priorita'...</option>
                    <option value="bassa">Bassa</option>
                    <option value="media">Media</option>
                    <option value="alta">Alta</option>
                </select>
                @endif
            </div>

            @if($expanded)
            {{-- Campi espansi --}}
            <div class="space-y-3 border-t border-paper-dark pt-3">

                {{-- Date + Executor + Ore --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <div>
                        <label class="text-xs text-ink/50 block mb-0.5">Scadenza</label>
                        <x-date-input wire:model="due_date"
                            class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-salvia"></x-date-input>
                        @error('due_date') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs text-ink/50 block mb-0.5">Executor</label>
                        <select wire:model="executor"
                            class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white focus:outline-none focus:border-salvia">
                            <option value="">...</option>
                            <option value="una_persona">👤 Io</option>
                            <option value="team">👥 Team</option>
                            <option value="professionista">🔧 Professionista</option>
                            <option value="impresa">🏗 Impresa</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-ink/50 block mb-0.5">Ore/uomo</label>
                        <input wire:model="work_estimate" type="number" min="0" step="0.5"
                            placeholder="h"
                            class="w-20 text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-salvia">
                    </div>
                </div>

                {{-- Costi --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs text-ink/50 shrink-0">Costo €</span>
                    <input wire:model="cost_min" type="number" min="0" step="100"
                        placeholder="min"
                        class="w-24 text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-salvia">
                    <span class="text-ink/30 text-xs">—</span>
                    <input wire:model="cost_max" type="number" min="0" step="100"
                        placeholder="max"
                        class="w-24 text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-salvia">
                    <select wire:model="cost_basis"
                        class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white focus:outline-none focus:border-salvia">
                        <option value="">tipo...</option>
                        <option value="stima">stima</option>
                        <option value="preventivo">preventivo</option>
                    </select>
                </div>

                {{-- Tag --}}
                <div>
                    <label class="text-xs text-ink/50 block mb-1">Tag</label>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        @foreach($selectedTags as $tag)
                        <span class="inline-flex items-center gap-1 text-xs bg-salvia/10 text-salvia px-2 py-0.5 rounded-full">
                            {{ ucfirst($tag->name) }}
                            <button wire:click="removeTag({{ $tag->id }})" class="hover:text-terracotta">&times;</button>
                        </span>
                        @endforeach
                        <input wire:model="tagInput"
                            wire:keydown.enter.prevent="addTag"
                            type="text"
                            placeholder="Aggiungi tag..."
                            class="text-sm border-0 focus:outline-none text-ink placeholder:text-ink/30 min-w-0 w-28">
                    </div>
                </div>

                {{-- Goal --}}
                <div>
                    <label class="text-xs text-ink/50 block mb-1">Goal collegati</label>
                    <div class="space-y-1">
                        @foreach($goals as $goal)
                        <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                            <input type="checkbox" wire:model="goalIds" value="{{ $goal->id }}"
                                class="rounded border-paper-dark text-salvia focus:ring-salvia">
                            {{ $goal->title }}
                        </label>
                        @endforeach
                    </div>
                </div>

            </div>
            @endif

            {{-- Footer --}}
            <div class="flex items-center justify-between pt-1">
                <button wire:click="toggleExpanded"
                    class="text-xs text-salvia hover:text-salvia-dark transition-colors">
                    {{ $expanded ? '- Comprimi dettagli' : '+ Aggiungi dettagli' }}
                </button>
                <div class="flex items-center gap-2">
                    <button wire:click="closeModal"
                        class="text-sm text-ink/50 hover:text-ink px-3 py-1.5 transition-colors">
                        Annulla
                    </button>
                    <button wire:click="save"
                        class="px-4 py-1.5 rounded-lg bg-salvia text-white text-sm font-medium hover:bg-salvia-dark transition-colors">
                        Crea
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
@endif
</div>
