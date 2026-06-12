<div class="space-y-2 pb-10">

    {{-- ===== FILTRI ===== --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <input wire:model.live.debounce.300ms="search"
               type="text"
               placeholder="Cerca task..."
               class="text-sm border border-paper-dark rounded-lg px-3 py-1.5 focus:outline-none focus:border-salvia
                      flex-1 min-w-0 sm:flex-none sm:w-48">

        <select wire:model.live="filterArea"
                class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                       focus:outline-none focus:border-salvia flex-1 sm:flex-none">
            <option value="">Tutte le aree</option>
            @foreach($areas as $area)
            <option value="{{ $area->id }}">{{ $area->name }}</option>
            @endforeach
        </select>

        @if($goals->isNotEmpty())
        <select wire:model.live="filterGoal"
                class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                       focus:outline-none focus:border-salvia flex-1 sm:flex-none">
            <option value="">Tutti i goal</option>
            @foreach($goals as $goal)
            <option value="{{ $goal->id }}">{{ $goal->title }}</option>
            @endforeach
        </select>
        @endif

        @if($filterArea || $filterGoal || $search)
        <button wire:click="$set('filterArea', null); $set('filterGoal', null); $set('search', '')"
                class="text-xs text-terracotta hover:text-ink transition-colors shrink-0">
            Azzera filtri
        </button>
        @endif

        <div class="ml-auto shrink-0">
            @livewire('task-form')
        </div>
    </div>

    {{-- ===== ZONA ESECUZIONE ===== --}}
    <div class="space-y-4">
        @foreach($zones['esecuzione'] as $stage)
            @include('livewire.kanban._stage', ['stage' => $stage, 'stageTasks' => $tasks->get($stage->id, collect())])
        @endforeach
    </div>

    {{-- ===== ZONA DECISIONE (collassabile) ===== --}}
    <div class="border border-paper-dark rounded-xl overflow-hidden">
        <button wire:click="$toggle('showDecisione')"
                class="w-full flex items-center gap-2 px-4 py-3 bg-paper hover:bg-paper-dark transition-colors text-left">
            <span class="text-xs font-semibold uppercase tracking-wider text-ink/50">
                {{ $showDecisione ? '▾' : '▸' }} Decisione
            </span>
            <span class="text-xs text-ink/30 ml-1">Approvato · In discussione · Idea</span>
            @php $decCount = $zones['decisione']->sum(fn($s) => $tasks->get($s->id, collect())->count()); @endphp
            @if($decCount > 0)
            <span class="text-xs text-ink/40 ml-auto">{{ $decCount }}</span>
            @endif
        </button>

        @if($showDecisione)
        <div class="px-4 pb-4 pt-2 space-y-4 border-t border-paper-dark">
            @foreach($zones['decisione'] as $stage)
                @include('livewire.kanban._stage', ['stage' => $stage, 'stageTasks' => $tasks->get($stage->id, collect())])
            @endforeach
        </div>
        @endif
    </div>

    {{-- ===== ZONA ARCHIVIO (collassabile) ===== --}}
    <div class="border border-paper-dark rounded-xl overflow-hidden">
        <button wire:click="$toggle('showArchivio')"
                class="w-full flex items-center gap-2 px-4 py-3 bg-paper hover:bg-paper-dark transition-colors text-left">
            <span class="text-xs font-semibold uppercase tracking-wider text-ink/50">
                {{ $showArchivio ? '▾' : '▸' }} Archivio
            </span>
            @php $arcCount = $zones['archivio']->sum(fn($s) => $tasks->get($s->id, collect())->count()); @endphp
            @if($arcCount > 0)
            <span class="text-xs text-ink/40 ml-auto">{{ $arcCount }}</span>
            @endif
        </button>

        @if($showArchivio)
        <div class="px-4 pb-4 pt-2 space-y-4 border-t border-paper-dark">
            @foreach($zones['archivio'] as $stage)
                @include('livewire.kanban._stage', ['stage' => $stage, 'stageTasks' => $tasks->get($stage->id, collect())])
            @endforeach
        </div>
        @endif
    </div>

</div>
