<div class="max-w-3xl mx-auto space-y-5 pb-16">

    {{-- ===== BACK BUTTON ===== --}}
    <div class="flex items-center justify-between">
        <x-back />
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

        {{-- Responsabile --}}
        <div>
            <label class="text-xs text-ink/50 flex items-center gap-1.5 mb-0.5">
                Responsabile
                @if($task->assignedUser)<x-identicon :user="$task->assignedUser" size="w-4 h-4 text-[8px]" />@endif
            </label>
            <select wire:model="assigned_to" wire:change="saveAssignee"
                class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                       focus:outline-none focus:border-salvia text-sm">
                <option value="">Nessuno</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
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

        {{-- Collaboratori --}}
        <div class="col-span-2">
            <label class="text-xs text-ink/50 block mb-1">Collaboratori</label>
            <div class="flex flex-wrap items-center gap-1.5">
                @forelse($users as $u)
                @php $active = in_array($u->id, $collaboratorIds); @endphp
                <button type="button" wire:click="toggleCollaborator({{ $u->id }})"
                        class="inline-flex items-center gap-1.5 pl-1 pr-2 py-0.5 rounded-full border text-xs transition-colors
                               {{ $active ? 'border-salvia bg-salvia/10 text-ink' : 'border-paper-dark text-ink/45 hover:border-salvia' }}">
                    <x-identicon :user="$u" size="w-5 h-5 text-[9px]" />
                    {{ $u->name }}
                </button>
                @empty
                <span class="text-xs text-ink/30 italic">Nessun altro utente.</span>
                @endforelse
            </div>
        </div>

        {{-- Date: scadenza | inizio | completamento --}}
        <div class="col-span-2">
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="text-xs text-ink/50 block mb-0.5">Scadenza</label>
                    <x-date-input wire:model="due_date" data-save="saveField"
                        class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                               focus:outline-none focus:border-salvia"></x-date-input>
                </div>
                <div>
                    <label class="text-xs text-ink/50 block mb-0.5">Inizio</label>
                    <x-date-input wire:model="start_date" data-save="saveField"
                        class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                               focus:outline-none focus:border-salvia"></x-date-input>
                </div>
                <div>
                    <label class="text-xs text-ink/50 block mb-0.5">Completamento</label>
                    <x-date-input wire:model="completed_at" data-save="saveField"
                        class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm
                               focus:outline-none focus:border-salvia"></x-date-input>
                </div>
            </div>
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

        {{-- Spese (inserimento manuale, niente upload) --}}
        @php $expSum = $task->expenses->sum('amount'); @endphp
        <div class="col-span-2 border-t border-paper-dark pt-4 mt-1">
            <div class="flex items-center justify-between mb-2">
                <label class="text-xs text-ink/50">
                    Spese
                    @if($task->expenses->isNotEmpty())
                    <span class="text-ink/40">· somma €{{ number_format($expSum, 2, ',', '.') }}</span>
                    @endif
                </label>
                @unless($showExpenseForm)
                <button wire:click="newExpense" class="text-xs text-salvia hover:text-salvia-dark transition-colors">+ Aggiungi spesa</button>
                @endunless
            </div>

            @if($task->cost_real && $expSum > 0 && abs($task->cost_real - $expSum) / $task->cost_real > 0.1)
            <p class="text-[11px] text-ink/40 mb-2">Consuntivo €{{ number_format($task->cost_real, 2, ',', '.') }}, spese registrate €{{ number_format($expSum, 2, ',', '.') }}.</p>
            @endif

            @if($task->expenses->isNotEmpty())
            <div class="rounded-lg border border-paper-dark divide-y divide-paper-dark mb-2">
                @foreach($task->expenses as $e)
                <div wire:key="exp-{{ $e->id }}" class="flex items-start gap-3 px-3 py-2 group">
                    <span class="text-xs text-ink/40 w-12 shrink-0 pt-0.5">{{ $e->expense_date?->format('d/m/y') ?? '—' }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm text-ink">{{ $e->description }}</div>
                        @if($e->category || $e->supplier)
                        <div class="text-[11px] text-ink/45">{{ $e->category }}@if($e->category && $e->supplier) · @endif{{ $e->supplier }}</div>
                        @endif
                        @if($e->notes)
                        <div class="text-[11px] text-ink/35 italic">{{ $e->notes }}</div>
                        @endif
                    </div>
                    <span class="text-sm text-ink font-medium shrink-0 pt-0.5">€{{ number_format($e->amount, 2, ',', '.') }}</span>
                    <div class="flex items-center gap-2 shrink-0 pt-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button wire:click="editExpense({{ $e->id }})" class="text-xs text-ink/40 hover:text-salvia" title="Modifica">✎</button>
                        <x-confirm action="deleteExpense({{ $e->id }})" class="text-xs text-ink/30 hover:text-terracotta">✕</x-confirm>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if($showExpenseForm)
            <div class="rounded-lg border border-salvia/40 bg-salvia/5 p-3 space-y-2">
                <div class="flex flex-wrap gap-2">
                    <input wire:model="expDescription" type="text" placeholder="Descrizione"
                        class="flex-1 min-w-40 border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-salvia">
                    <input wire:model="expAmount" type="number" min="0" step="0.01" placeholder="€"
                        class="w-28 border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-salvia">
                </div>
                @error('expDescription')<p class="text-xs text-terracotta">{{ $message }}</p>@enderror
                @error('expAmount')<p class="text-xs text-terracotta">{{ $message }}</p>@enderror
                <div class="flex flex-wrap gap-2">
                    <select wire:model="expCategory"
                        class="border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-sm focus:outline-none focus:border-salvia">
                        <option value="">categoria...</option>
                        <option value="materiali">materiali</option>
                        <option value="manodopera">manodopera</option>
                        <option value="professionisti">professionisti</option>
                        <option value="oneri">oneri</option>
                        <option value="altro">altro</option>
                    </select>
                    <input wire:model="expSupplier" type="text" placeholder="Fornitore" list="exp-suppliers"
                        class="flex-1 min-w-32 border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-salvia">
                    <datalist id="exp-suppliers">
                        @foreach($suppliers as $s)<option value="{{ $s }}">@endforeach
                    </datalist>
                    <x-date-input wire:model="expDate"
                        class="w-36 border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-salvia"></x-date-input>
                </div>
                <input wire:model="expNotes" type="text" placeholder="Note (opzionale)"
                    class="w-full border border-paper-dark rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-salvia">
                <div class="flex items-center gap-2">
                    <button wire:click="saveExpense" class="text-xs px-3 py-1.5 bg-salvia text-white rounded-lg hover:bg-salvia-dark transition-colors">
                        {{ $editingExpenseId ? 'Salva' : 'Aggiungi' }}
                    </button>
                    <button wire:click="cancelExpense" class="text-xs text-ink/40 hover:text-ink transition-colors">Annulla</button>
                </div>
            </div>
            @endif
        </div>

        {{-- Task padre --}}
        @if($task->parent)
        <div class="col-span-2">
            <label class="text-xs text-ink/50 block mb-0.5">Task padre</label>
            <a href="{{ route('tasks.show', $task->parent) }}" wire:navigate
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
                <a href="{{ route('tasks.show', $child) }}" wire:navigate
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
                <x-confirm action="deleteUpdate({{ $update->id }})"
                    class="text-xs text-ink/30 hover:text-terracotta transition-colors opacity-0 group-hover:opacity-100">
                    elimina
                </x-confirm>
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
