<div class="max-w-3xl mx-auto space-y-5 pb-16">

    {{-- ===== BACK BUTTON ===== --}}
    <div class="flex items-center justify-between">
        <button onclick="history.back()"
                class="flex items-center gap-1.5 text-sm text-ink/50 hover:text-salvia transition-colors">
            ← Indietro
        </button>
        <p class="text-xs text-ink/30 italic">Le modifiche vengono salvate automaticamente.</p>
    </div>

    {{-- ===== STAGE SELECTOR ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark px-5 py-3">
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($stages as $s)
            <button wire:click="setStage({{ $s->id }})"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all
                       {{ $task->stage_id === $s->id ? 'ring-2 ring-offset-1 ring-salvia scale-105' : 'opacity-60 hover:opacity-90' }}"
                style="background-color: {{ $s->bg_color }}; color: {{ $s->text_color }}">
                {{ $s->label }}
            </button>
            @endforeach
        </div>

        {{-- Prompt data scadenza --}}
        @if($showDueDatePrompt)
        <div class="mt-3 border-t border-paper-dark pt-3">
            <p class="text-sm text-ink mb-2">Questo stage richiede una scadenza. Inseriscila per procedere.</p>
            <div class="flex items-center gap-2">
                <x-date-input wire:model="due_date"
                    class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-salvia"></x-date-input>
                <button wire:click="confirmDueDate"
                    class="px-3 py-1.5 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors">
                    Conferma
                </button>
                <button wire:click="cancelDueDate"
                    class="px-3 py-1.5 text-ink/50 text-sm hover:text-ink transition-colors">
                    Annulla
                </button>
            </div>
        </div>
        @endif

        {{-- Prompt data completamento --}}
        @if($showCompletedAtPrompt)
        <div class="mt-3 border-t border-paper-dark pt-3">
            <p class="text-sm text-ink mb-2">Quando è stato completato?</p>
            <div class="flex items-center gap-2">
                <x-date-input wire:model="pendingCompletedAt"
                    class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-salvia"></x-date-input>
                <button wire:click="confirmCompletedAt"
                    class="px-3 py-1.5 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors">
                    Conferma
                </button>
                <button wire:click="cancelCompletedAt"
                    class="px-3 py-1.5 text-ink/50 text-sm hover:text-ink transition-colors">
                    Annulla
                </button>
            </div>
        </div>
        @endif
    </div>

    {{-- ===== TITOLO + DESCRIZIONE ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark px-6 py-5">

        {{-- Titolo --}}
        @if($editingTitle)
        <div class="mb-3">
            <input wire:model="title"
                wire:blur="saveTitle"
                wire:keydown.enter="saveTitle"
                wire:keydown.escape="$set('editingTitle', false)"
                type="text"
                class="w-full text-xl font-semibold text-ink bg-transparent border-b border-salvia
                       focus:outline-none pb-1"
                autofocus>
            @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        @else
        <h1 class="text-xl font-semibold text-ink mb-3 cursor-pointer hover:text-salvia-dark transition-colors group"
            wire:click="$set('editingTitle', true)">
            {{ $task->title }}
            <span class="text-ink/20 text-sm font-normal ml-1.5 group-hover:text-salvia-light">✎</span>
        </h1>
        @endif

        {{-- Descrizione --}}
        @if($editingDescription)
        <div>
            <textarea wire:model="description"
                wire:blur="saveDescription"
                wire:keydown.escape="$set('editingDescription', false)"
                rows="4"
                class="w-full text-sm text-ink bg-paper rounded-lg border border-paper-dark
                       px-3 py-2 focus:outline-none focus:border-salvia resize-none"></textarea>
        </div>
        @else
        <div wire:click="$set('editingDescription', true)"
             class="cursor-pointer group">
            @if($task->description)
                <p class="text-sm text-ink/80 leading-relaxed whitespace-pre-wrap group-hover:text-ink transition-colors">
                    {{ $task->description }}
                </p>
            @else
                <p class="text-sm text-ink/30 italic group-hover:text-ink/50 transition-colors">
                    Aggiungi una descrizione...
                </p>
            @endif
        </div>
        @endif
    </div>

    {{-- ===== METADATI ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark px-4 sm:px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">

        {{-- Area --}}
        <div>
            <label class="text-xs text-ink/50 block mb-0.5">Area</label>
            <select wire:model="area_id" wire:change="saveField('area_id')"
                class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                       focus:outline-none focus:border-salvia text-sm">
                <option value="">Nessuna</option>
                @foreach($areas as $area)
                <option value="{{ $area->id }}">{{ $area->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Priorita --}}
        <div>
            <label class="text-xs text-ink/50 block mb-0.5">Priorita</label>
            <select wire:model="priority" wire:change="saveField('priority')"
                class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                       focus:outline-none focus:border-salvia text-sm">
                <option value="">Nessuna</option>
                <option value="bassa">Bassa</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
            </select>
        </div>

        {{-- Scadenza --}}
        <div>
            <label class="text-xs text-ink/50 block mb-0.5">Scadenza</label>
            <x-date-input wire:model="due_date" data-save="saveField"
                class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                       focus:outline-none focus:border-salvia"></x-date-input>
        </div>

        {{-- Inizio --}}
        <div>
            <label class="text-xs text-ink/50 block mb-0.5">Data inizio</label>
            <x-date-input wire:model="start_date" data-save="saveField"
                class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                       focus:outline-none focus:border-salvia"></x-date-input>
        </div>

        {{-- Executor --}}
        <div>
            <label class="text-xs text-ink/50 block mb-0.5">Executor</label>
            <select wire:model="executor" wire:change="saveField('executor')"
                class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                       focus:outline-none focus:border-salvia text-sm">
                <option value="">Non definito</option>
                <option value="una_persona">👤 Io</option>
                <option value="team">👥 Team</option>
                <option value="professionista">🔧 Professionista</option>
                <option value="impresa">🏗 Impresa</option>
            </select>
        </div>

        {{-- Ore/uomo --}}
        <div>
            <label class="text-xs text-ink/50 block mb-0.5">Ore/uomo stimate</label>
            <input wire:model="work_estimate" type="number" min="0" step="0.5"
                wire:blur="saveField('work_estimate')"
                placeholder="h"
                class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                       focus:outline-none focus:border-salvia">
        </div>

        {{-- Costi --}}
        <div class="col-span-2">
            <label class="text-xs text-ink/50 block mb-0.5">Costo (€)</label>
            <div class="flex items-center gap-2 flex-wrap">
                <input wire:model="cost_min" type="number" min="0" step="100"
                    wire:blur="saveField('cost_min')"
                    placeholder="min"
                    class="w-24 border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                           focus:outline-none focus:border-salvia">
                <span class="text-ink/30 text-xs">—</span>
                <input wire:model="cost_max" type="number" min="0" step="100"
                    wire:blur="saveField('cost_max')"
                    placeholder="max"
                    class="w-24 border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                           focus:outline-none focus:border-salvia">
                <select wire:model="cost_basis" wire:change="saveField('cost_basis')"
                    class="border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-sm
                           focus:outline-none focus:border-salvia">
                    <option value="">tipo...</option>
                    <option value="stima">stima</option>
                    <option value="preventivo">preventivo</option>
                    <option value="reale">reale</option>
                </select>
                <span class="text-ink/20 text-xs">|</span>
                <input wire:model="cost_real" type="number" min="0" step="100"
                    wire:blur="saveField('cost_real')"
                    placeholder="importo effettivo"
                    class="w-32 border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                           focus:outline-none focus:border-salvia">
                <span class="text-xs text-ink/40">reale</span>
            </div>
        </div>

        {{-- Data completamento (solo se done o completato) --}}
        @if($task->completed_at || in_array($task->stage?->code, ['done', 'archiviato']))
        <div>
            <label class="text-xs text-ink/50 block mb-0.5">Data completamento</label>
            <x-date-input wire:model="completed_at" data-save="saveField"
                class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                       focus:outline-none focus:border-salvia"></x-date-input>
        </div>
        @endif

        {{-- Task padre --}}
        @if($task->parent)
        <div class="col-span-2">
            <label class="text-xs text-ink/50 block mb-0.5">Task padre</label>
            <a href="{{ route('tasks.show', $task->parent) }}"
               class="text-sm text-salvia hover:text-salvia-dark transition-colors">
                {{ $task->parent->title }}
            </a>
        </div>
        @endif

    </div>

    {{-- ===== TAG + GOAL ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark px-6 py-5 space-y-4">

        {{-- Tag --}}
        <div>
            <p class="text-xs text-ink/50 mb-2">Tag</p>
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach($selectedTags as $tag)
                <span class="inline-flex items-center gap-1 text-xs bg-salvia/10 text-salvia px-2.5 py-1 rounded-full">
                    {{ $tag->display_name }}
                    <button wire:click="removeTag({{ $tag->id }})"
                        class="hover:text-terracotta transition-colors">&times;</button>
                </span>
                @endforeach
                <input wire:model="tagInput"
                    wire:keydown.enter.prevent="addTag"
                    type="text"
                    placeholder="+ tag"
                    class="text-sm border-0 focus:outline-none text-ink placeholder:text-ink/30 min-w-0 w-24 bg-transparent">
            </div>
        </div>

        {{-- Goal --}}
        @if($goals->isNotEmpty())
        <div>
            <p class="text-xs text-ink/50 mb-2">Goal collegati</p>
            <div class="flex flex-wrap gap-2">
                @foreach($goals as $goal)
                <label class="flex items-center gap-1.5 text-sm text-ink cursor-pointer">
                    <input type="checkbox"
                        wire:model="goalIds"
                        wire:change="toggleGoal({{ $goal->id }})"
                        value="{{ $goal->id }}"
                        {{ in_array($goal->id, $goalIds) ? 'checked' : '' }}
                        class="rounded border-paper-dark text-salvia focus:ring-salvia">
                    {{ $goal->title }}
                </label>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- ===== SOTTO-TASK ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark px-6 py-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-salvia mb-3">Sotto-task</p>

        @if($task->children->isNotEmpty())
        <ul class="space-y-1.5 mb-3">
            @foreach($task->children as $child)
            <li class="flex items-center gap-2.5 text-sm">
                @if($child->stage)
                <span class="shrink-0 text-xs px-1.5 py-0.5 rounded font-medium"
                      style="background-color: {{ $child->stage->bg_color }}; color: {{ $child->stage->text_color }}">
                    {{ $child->stage->label }}
                </span>
                @endif
                <a href="{{ route('tasks.show', $child) }}"
                   class="flex-1 text-ink hover:text-salvia truncate transition-colors">
                    {{ $child->title }}
                </a>
                @if($child->due_date)
                <span class="shrink-0 text-xs text-ink/40">
                    {{ $child->due_date->format('d/m') }}
                </span>
                @endif
            </li>
            @endforeach
        </ul>
        @endif

        {{-- Aggiungi sotto-task --}}
        <div class="flex items-center gap-2">
            <input wire:model="newSubtaskTitle"
                wire:keydown.enter.prevent="addSubtask"
                type="text"
                placeholder="Nuovo sotto-task..."
                class="flex-1 text-sm border border-paper-dark rounded-lg px-3 py-1.5
                       focus:outline-none focus:border-salvia placeholder:text-ink/30">
            <button wire:click="addSubtask"
                class="px-3 py-1.5 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors">
                Aggiungi
            </button>
        </div>
    </div>

    {{-- ===== AGGIORNAMENTI ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark px-6 py-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-salvia mb-4">Aggiornamenti</p>

        {{-- Lista aggiornamenti --}}
        @forelse($task->updates->sortByDesc('created_at') as $update)
        <div class="border-l-2 border-paper-dark pl-4 mb-4 group">
            @if($editingUpdateId === $update->id)
            <div>
                <textarea wire:model="editingUpdateContent"
                    rows="3"
                    class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                           focus:outline-none focus:border-salvia resize-none mb-1.5"></textarea>
                <div class="flex gap-2">
                    <button wire:click="saveUpdate"
                        class="text-xs px-3 py-1 bg-salvia text-white rounded-lg hover:bg-salvia-dark transition-colors">
                        Salva
                    </button>
                    <button wire:click="cancelEditUpdate"
                        class="text-xs px-3 py-1 text-ink/50 hover:text-ink transition-colors">
                        Annulla
                    </button>
                </div>
            </div>
            @else
            <p class="text-sm text-ink/80 whitespace-pre-wrap leading-relaxed">{{ $update->content }}</p>
            <div class="flex items-center gap-3 mt-1">
                <span class="text-xs text-ink/40">{{ $update->created_at->format('d/m/Y H:i') }}</span>
                <button wire:click="startEditUpdate({{ $update->id }})"
                    class="text-xs text-ink/30 hover:text-salvia transition-colors opacity-0 group-hover:opacity-100">
                    modifica
                </button>
                <button wire:click="deleteUpdate({{ $update->id }})"
                    wire:confirm="Eliminare questo aggiornamento?"
                    class="text-xs text-ink/30 hover:text-terracotta transition-colors opacity-0 group-hover:opacity-100">
                    elimina
                </button>
            </div>
            @endif
        </div>
        @empty
        <p class="text-sm text-ink/30 italic mb-4">Nessun aggiornamento ancora.</p>
        @endforelse

        {{-- Aggiungi aggiornamento --}}
        <div class="mt-3">
            <textarea wire:model="newUpdateContent"
                rows="2"
                placeholder="Aggiungi un aggiornamento..."
                class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                       focus:outline-none focus:border-salvia resize-none placeholder:text-ink/30 mb-2"></textarea>
            <button wire:click="addUpdate"
                class="px-4 py-1.5 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors">
                Aggiungi nota
            </button>
        </div>
    </div>

    {{-- ===== MEDIA (immagini) ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark px-6 py-4">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-ink/40 mb-3">Immagini</h3>
        @livewire('media-uploader', ['entityType' => \App\Models\Task::class, 'entityId' => $task->id], key('media-'.$task->id))
    </div>

    {{-- ===== DOCUMENTI ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark px-6 py-4">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-ink/40 mb-3">Documenti</h3>
        @livewire('doc-uploader', ['entityType' => \App\Models\Task::class, 'entityId' => $task->id], key('docs-'.$task->id))
    </div>

    {{-- ===== ZONA PERICOLO ===== --}}
    <div class="flex justify-end">
        @if(!$confirmDelete)
        <button wire:click="$set('confirmDelete', true)"
            class="text-xs text-ink/30 hover:text-terracotta transition-colors">
            Elimina task
        </button>
        @else
        <div class="flex items-center gap-2">
            <span class="text-xs text-ink/60">Eliminare questo task?</span>
            <button wire:click="deleteTask"
                class="text-xs px-3 py-1.5 bg-terracotta text-white rounded-lg hover:opacity-90 transition-colors">
                Elimina
            </button>
            <button wire:click="$set('confirmDelete', false)"
                class="text-xs text-ink/50 hover:text-ink transition-colors">
                Annulla
            </button>
        </div>
        @endif
    </div>

</div>
