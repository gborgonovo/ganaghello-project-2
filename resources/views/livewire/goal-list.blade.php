<div class="max-w-2xl mx-auto space-y-4 pb-10">

    {{-- ===== LISTA GOAL ===== --}}
    @forelse($goals as $goal)
    @php
        $directTasks = $goal->tasks;
        $subTasks    = $goal->children->flatMap->tasks;
        $allTasks    = $directTasks->merge($subTasks);
        $total       = $allTasks->count();
        $done        = $allTasks->whereNotNull('completed_at')->count();
        $pct         = $total > 0 ? round($done / $total * 100) : 0;
        $aree        = $allTasks->map->area->filter()->unique('id');
    @endphp

    <div class="rounded-xl border border-paper-dark bg-white px-6 py-5"
         wire:key="goal-{{ $goal->id }}">

        @if($editingId === $goal->id)
        {{-- ===== FORM EDIT ===== --}}
        @include('livewire.goal-list._edit-form', ['goalId' => $goal->id, 'isChild' => false])

        @else
        {{-- ===== BLOCCO GOAL ===== --}}

        {{-- Header --}}
        <div class="flex items-start justify-between gap-3 mb-2">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-salvia font-semibold text-base">★</span>
                    <h2 class="text-base font-semibold text-ink">{{ $goal->title }}</h2>

                    @php
                        $statusClass = match($goal->status) {
                            'raggiunto'   => 'text-emerald-700 bg-emerald-50',
                            'abbandonato' => 'text-ink/40 bg-paper-dark',
                            default       => 'text-salvia bg-salvia/10',
                        };
                        $statusLabel = match($goal->status) {
                            'raggiunto'   => '✓ raggiunto',
                            'abbandonato' => 'abbandonato',
                            default       => 'attivo',
                        };
                    @endphp
                    <span class="text-xs px-1.5 py-0.5 rounded font-medium {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>

                    @if($goal->deadline)
                    <span class="text-xs text-ink/40">
                        orizzonte: {{ $goal->deadline->format('Y') }}
                        @if($goal->deadline->year === now()->year) (quest'anno) @endif
                    </span>
                    @endif
                </div>

                @if($goal->description)
                <p class="text-sm text-ink/60 italic mt-1 leading-relaxed">
                    "{{ $goal->description }}"
                </p>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button wire:click="startEdit({{ $goal->id }})"
                        class="text-ink/30 hover:text-salvia text-sm transition-colors">✎</button>
                @if($confirmDeleteId === $goal->id)
                <span class="text-xs text-ink/50">Sicuro?</span>
                <button wire:click="deleteGoal({{ $goal->id }})"
                        class="text-xs text-terracotta hover:opacity-80">Elimina</button>
                <button wire:click="$set('confirmDeleteId', null)"
                        class="text-xs text-ink/40 hover:text-ink">No</button>
                @else
                <button wire:click="$set('confirmDeleteId', {{ $goal->id }})"
                        class="text-ink/20 hover:text-terracotta text-sm transition-colors">✕</button>
                @endif
            </div>
        </div>

        {{-- Barra avanzamento --}}
        <div class="mb-3">
            <div class="flex items-center gap-2 mb-1">
                <div class="flex-1 h-2 bg-paper-dark rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500
                                {{ $goal->status === 'raggiunto' ? 'bg-emerald-500' : 'bg-salvia' }}"
                         style="width: {{ $pct }}%"></div>
                </div>
                @if($total > 0)
                <button wire:click="toggleTasks({{ $goal->id }})"
                        class="text-xs text-ink/50 hover:text-salvia transition-colors shrink-0 tabular-nums">
                    {{ $done }}/{{ $total }} task
                </button>
                @else
                <span class="text-xs text-ink/30 shrink-0">nessun task</span>
                @endif
            </div>

            {{-- Aree coinvolte --}}
            @if($aree->isNotEmpty())
            <p class="text-xs text-ink/40">
                Aree: {{ $aree->pluck('name')->implode(' · ') }}
            </p>
            @endif
        </div>

        {{-- Task list espandibile --}}
        @if(in_array($goal->id, $expandedTasks) && $total > 0)
        <div class="border-t border-paper-dark pt-3 mb-3 space-y-1">
            @foreach($allTasks->sortBy(fn($t) => $t->completed_at ? 1 : 0) as $task)
            <div class="flex items-center gap-2 text-sm">
                <span class="text-xs {{ $task->completed_at ? 'text-emerald-600' : 'text-ink/30' }}">
                    {{ $task->completed_at ? '✓' : '○' }}
                </span>
                <a href="{{ route('tasks.show', $task) }}" wire:navigate
                   class="flex-1 truncate {{ $task->completed_at ? 'text-ink/40 line-through' : 'text-ink hover:text-salvia' }}">
                    {{ $task->title }}
                </a>
                @if($task->area)
                <span class="text-xs text-ink/30 shrink-0">{{ $task->area->name }}</span>
                @endif
                @if($task->stage)
                <span class="text-xs px-1.5 py-0.5 rounded shrink-0"
                      style="background-color: {{ $task->stage->bg_color }}; color: {{ $task->stage->text_color }}">
                    {{ $task->stage->label }}
                </span>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- ===== SOTTO-GOAL ===== --}}
        @if($goal->children->isNotEmpty())
        <div class="border-t border-paper-dark pt-3 space-y-2 mt-1">
            @foreach($goal->children as $sub)
            @php
                $subTotal = $sub->tasks->count();
                $subDone  = $sub->tasks->whereNotNull('completed_at')->count();
                $subPct   = $subTotal > 0 ? round($subDone / $subTotal * 100) : 0;
            @endphp

            <div wire:key="subgoal-{{ $sub->id }}">
                @if($editingId === $sub->id)
                @include('livewire.goal-list._edit-form', ['goalId' => $sub->id, 'isChild' => true])
                @else
                <div class="flex items-start gap-2">
                    <span class="text-salvia-light text-xs mt-0.5 shrink-0">
                        {{ $loop->last ? '└' : '├' }} ★
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-medium text-ink">{{ $sub->title }}</span>
                            @if($sub->deadline)
                            <span class="text-xs text-ink/35">{{ $sub->deadline->format('Y') }}</span>
                            @endif
                            <button wire:click="startEdit({{ $sub->id }})"
                                    class="text-ink/20 hover:text-salvia text-xs transition-colors">✎</button>
                            <button wire:click="$set('confirmDeleteId', {{ $sub->id }})"
                                    class="text-ink/15 hover:text-terracotta text-xs transition-colors">✕</button>
                        </div>
                        @if($subTotal > 0)
                        <div class="flex items-center gap-2 mt-1">
                            <div class="w-24 h-1.5 bg-paper-dark rounded-full overflow-hidden">
                                <div class="h-full rounded-full
                                            {{ $sub->status === 'raggiunto' ? 'bg-emerald-500' : 'bg-salvia-light' }}"
                                     style="width: {{ $subPct }}%"></div>
                            </div>
                            <button wire:click="toggleTasks({{ $sub->id }})"
                                    class="text-xs text-ink/40 hover:text-salvia tabular-nums">
                                {{ $subDone }}/{{ $subTotal }}
                                @if($sub->status === 'raggiunto') ✓ @endif
                            </button>
                        </div>
                        @endif

                        {{-- Task sub-goal espandibili --}}
                        @if(in_array($sub->id, $expandedTasks) && $subTotal > 0)
                        <div class="mt-2 space-y-0.5">
                            @foreach($sub->tasks as $task)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="{{ $task->completed_at ? 'text-emerald-600' : 'text-ink/30' }}">
                                    {{ $task->completed_at ? '✓' : '○' }}
                                </span>
                                <a href="{{ route('tasks.show', $task) }}" wire:navigate
                                   class="{{ $task->completed_at ? 'text-ink/40 line-through' : 'text-ink/70 hover:text-salvia' }} truncate">
                                    {{ $task->title }}
                                </a>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if($confirmDeleteId === $sub->id)
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-ink/50">Eliminare?</span>
                            <button wire:click="deleteGoal({{ $sub->id }})"
                                    class="text-xs text-terracotta">Sì</button>
                            <button wire:click="$set('confirmDeleteId', null)"
                                    class="text-xs text-ink/40">No</button>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @endif {{-- fine if not editing --}}
    </div>
    @empty
    <div class="rounded-xl border border-paper-dark bg-white px-6 py-8 text-center">
        <p class="text-ink/40 text-sm italic mb-3">Nessun goal ancora.</p>
        <button wire:click="$set('showCreate', true)"
                class="text-sm text-salvia hover:text-salvia-dark transition-colors">
            + Crea il primo goal
        </button>
    </div>
    @endforelse

    {{-- ===== CREA NUOVO GOAL ===== --}}
    @if(!$showCreate)
    <button wire:click="$set('showCreate', true)"
            class="w-full rounded-xl border border-dashed border-paper-dark text-ink/30
                   hover:border-salvia hover:text-salvia transition-colors py-4 text-sm">
        + Nuovo goal
    </button>
    @else
    <div class="rounded-xl border border-salvia-light bg-white px-6 py-5 space-y-3">
        <p class="text-sm font-semibold text-ink">Nuovo goal</p>

        <input wire:model="newTitle"
               wire:keydown.escape="$set('showCreate', false)"
               type="text"
               placeholder="Titolo del goal..."
               autofocus
               class="w-full text-sm border-b border-salvia bg-transparent focus:outline-none
                      pb-0.5 placeholder:text-ink/30">
        @error('newTitle') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

        <textarea wire:model="newDesc" rows="2"
                  placeholder="Perche' questo goal? (opzionale)"
                  class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                         resize-none focus:outline-none focus:border-salvia placeholder:text-ink/30"></textarea>

        <div class="flex items-center gap-3 flex-wrap">
            <div>
                <label class="text-xs text-ink/50 block mb-0.5">Orizzonte</label>
                <x-date-input wire:model="newDeadline"
                    class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5
                           focus:outline-none focus:border-salvia"></x-date-input>
            </div>
            @if($allGoals->isNotEmpty())
            <div>
                <label class="text-xs text-ink/50 block mb-0.5">Sotto-goal di</label>
                <select wire:model="newParentId"
                        class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white
                               focus:outline-none focus:border-salvia">
                    <option value="">Goal principale</option>
                    @foreach($allGoals as $id => $title)
                    <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        <div class="flex items-center gap-2 pt-1">
            <button wire:click="createGoal"
                    class="text-sm px-4 py-1.5 bg-salvia text-white rounded-lg hover:bg-salvia-dark">
                Crea
            </button>
            <button wire:click="$set('showCreate', false)"
                    class="text-sm text-ink/50 hover:text-ink transition-colors">
                Annulla
            </button>
        </div>
    </div>
    @endif

</div>
