<div class="space-y-2 pb-10"
     x-data
     x-on:livewire:navigated.window="$wire.$refresh()">
    {{-- Al ritorno via wire:navigate la pagina arriva dalla cache (snapshot vecchio):
         forziamo un refresh cosi' i task sono nella colonna giusta senza ricaricare. --}}

    {{-- ===== FILTRI ===== --}}
    <div class="flex flex-wrap items-center gap-2 mb-2">
        <input wire:model.live.debounce.300ms="search"
               type="text"
               placeholder="Cerca task..."
               class="text-sm border border-paper-dark rounded-lg px-3 py-1.5 focus:outline-none focus:border-salvia
                      flex-1 min-w-0 sm:flex-none sm:w-48">

        <select wire:model.live="filterArea"
                class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                       focus:outline-none focus:border-salvia flex-1 sm:flex-none">
            <option value="">Tutte le aree</option>
            <x-area-options />
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

        <select wire:model.live="filterScadenza"
                class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white text-ink
                       focus:outline-none focus:border-salvia flex-1 sm:flex-none">
            <option value="">Scadenza</option>
            <option value="scaduti">Scaduti</option>
            <option value="7gg">Entro 7 gg</option>
            <option value="30gg">Entro 30 gg</option>
        </select>

        {{-- Pill "Faccio io · weekend" (07-delta §3) --}}
        <button wire:click="$toggle('filterWeekend')"
                class="text-xs px-2.5 py-1.5 rounded-lg border transition-colors shrink-0
                       {{ $filterWeekend ? 'bg-salvia text-white border-salvia' : 'border-paper-dark text-ink/60 hover:border-salvia' }}">
            🔧 Faccio io · weekend
        </button>

        @if($filterArea || $filterGoal || $search || $filterScadenza || $filterWeekend || count($filterTags))
        <button wire:click="$set('filterArea', null); $set('filterGoal', null); $set('filterScadenza', ''); $set('filterWeekend', false); $set('filterTags', []); $set('search', '')"
                class="text-xs text-terracotta hover:text-ink transition-colors shrink-0">
            Azzera filtri
        </button>
        @endif

        <div class="ml-auto shrink-0">
            {{-- key stabile: il refresh del kanban (su navigated) non deve scollegare
                 questa "isola" annidata, altrimenti il suo "Crea" smette di funzionare. --}}
            @livewire('task-form', [], key('kanban-task-form'))
        </div>
    </div>

    {{-- Filtro tag multi-select (07-delta §1) --}}
    @if($tags->isNotEmpty())
    <div class="flex flex-wrap items-center gap-1.5 mb-4">
        <span class="text-xs text-ink/40 mr-0.5">Tag:</span>
        @foreach($tags as $tag)
        <label class="cursor-pointer">
            <input type="checkbox" wire:model.live="filterTags" value="{{ $tag->id }}" class="peer sr-only">
            <span class="text-xs px-2 py-0.5 rounded-full border border-paper-dark text-ink/50
                         peer-checked:bg-salvia peer-checked:text-white peer-checked:border-salvia transition-colors">
                {{ ucfirst($tag->name) }}
            </span>
        </label>
        @endforeach
    </div>
    @endif

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
