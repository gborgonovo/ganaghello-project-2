<div class="pb-10" x-data @task-created.window="$wire.$refresh()">

    {{-- ===== HEADER ===== --}}
    <div class="rounded-xl border border-paper-dark bg-white px-4 sm:px-6 py-5 mb-5">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">

                {{-- Breadcrumb --}}
                <div class="flex items-center gap-1 mb-1.5 text-xs text-ink/40">
                    <a href="{{ route('aree') }}" class="hover:text-salvia transition-colors">Aree</a>
                    @if($area->parent)
                    <span>/</span>
                    <a href="{{ route('aree.show', $area->parent) }}"
                       class="inline-flex items-center gap-1 hover:text-salvia transition-colors">
                        @if($area->parent->color)
                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                              style="background-color: {{ $area->parent->color }}"></span>
                        @endif
                        {{ $area->parent->name }}
                    </a>
                    @endif
                    <span>/</span>
                    <span class="text-ink/60">{{ $area->name }}</span>
                </div>

                {{-- Nome area --}}
                @if($editingName)
                <div class="mb-1">
                    <input wire:model="areaName"
                           wire:blur="saveName"
                           wire:keydown.enter="saveName"
                           wire:keydown.escape="$set('editingName', false)"
                           type="text"
                           class="text-xl font-semibold text-ink bg-transparent border-b border-salvia
                                  focus:outline-none pb-0.5 w-full"
                           autofocus>
                </div>
                @else
                <h1 class="text-xl font-semibold text-ink mb-1 inline-flex items-center gap-2">
                    <input type="color"
                           wire:model="areaColor"
                           wire:change="saveColor"
                           class="w-4 h-4 rounded-full cursor-pointer border-0 p-0 shrink-0
                                  [&::-webkit-color-swatch-wrapper]:p-0
                                  [&::-webkit-color-swatch]:rounded-full
                                  [&::-webkit-color-swatch]:border-0"
                           title="Cambia colore">
                    <span class="cursor-pointer group inline-flex items-center gap-1"
                          wire:click="$set('editingName', true)">
                        {{ $area->name }}
                        <span class="text-ink/20 text-sm font-normal group-hover:text-salvia-light">✎</span>
                    </span>
                </h1>
                @endif

                {{-- Status --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <select wire:model.live="areaStatus" wire:change="saveStatus"
                            class="text-xs border border-paper-dark rounded-lg px-2 py-0.5 bg-white
                                   focus:outline-none focus:border-salvia text-ink/70">
                        <option value="">Stato automatico</option>
                        <option value="cantiere">● Cantiere</option>
                        <option value="attiva">● Attiva</option>
                        <option value="in_valutazione">● In valutazione</option>
                        <option value="dormiente">○ Dormiente</option>
                    </select>

                    @if($costMin > 0 || $costReal > 0)
                    <span class="text-xs text-ink/50">
                        @if($costMin > 0)prev. €{{ number_format($costMin, 0, ',', '.') }}@endif
                        @if($costReal > 0) / reale €{{ number_format($costReal, 0, ',', '.') }}@endif
                    </span>
                    @endif
                </div>
            </div>

            {{-- Azioni header --}}
            <div class="flex items-center gap-2 shrink-0">
                <button wire:click="$set('addingEntry', true)"
                        class="text-sm px-3 py-1.5 border border-paper-dark rounded-lg text-ink/70
                               hover:border-salvia hover:text-salvia transition-colors">
                    + Annota
                </button>
                @livewire('task-form', ['areaId' => $area->id])
            </div>
        </div>

        {{-- Quick annota --}}
        @if($addingEntry)
        <div class="mt-4 border-t border-paper-dark pt-4 space-y-2">
            <textarea wire:model="entryContent"
                      rows="3"
                      placeholder="Annota qui cosa sta succedendo, cosa hai fatto, idee..."
                      class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                             resize-none focus:outline-none focus:border-salvia
                             placeholder:text-ink/30"></textarea>
            <div class="flex items-center gap-2">
                <button wire:click="saveEntry"
                        class="text-xs px-3 py-1.5 bg-salvia text-white rounded-lg hover:bg-salvia-dark">
                    Salva nota
                </button>
                <button wire:click="$set('addingEntry', false)"
                        class="text-xs text-ink/50 hover:text-ink">
                    Annulla
                </button>
            </div>
        </div>
        @endif
    </div>

    {{-- ===== SOTTO-AREE (masonry, solo se presenti) ===== --}}
    @if($area->children->isNotEmpty())
    <div class="columns-2 md:columns-3 gap-4 mb-5">
        @foreach($area->children as $child)
        <div class="break-inside-avoid mb-4 rounded-xl border border-paper-dark bg-white px-5 py-4 space-y-3"
             wire:key="child-area-{{ $child->id }}">

            <div class="flex items-center gap-2">
                @if($child->color)
                <span class="w-3 h-3 rounded-full shrink-0"
                      style="background-color: {{ $child->color }}"></span>
                @endif
                <a href="{{ route('aree.show', $child) }}"
                   class="font-semibold text-sm text-ink hover:text-salvia transition-colors truncate">
                    {{ $child->name }}
                </a>
            </div>

            @if($child->description)
            <p class="text-xs text-ink/50 leading-relaxed">{{ $child->description }}</p>
            @endif

            <div class="flex items-center justify-between text-xs text-ink/50">
                <span>{{ $child->tasks_count }} task totali</span>
                @if($child->open_tasks_count > 0)
                <span class="text-salvia">{{ $child->open_tasks_count }} aperti</span>
                @endif
            </div>

            {{-- Sotto-sotto-aree come mini-box --}}
            @if($child->children->isNotEmpty())
            <div class="border-t border-paper-dark pt-3 grid grid-cols-2 gap-2">
                @foreach($child->children as $grandchild)
                <a href="{{ route('aree.show', $grandchild) }}"
                   class="block rounded-lg border border-paper-dark px-2.5 py-2
                          hover:border-salvia group transition-colors">
                    <div class="flex items-center gap-1.5 min-w-0">
                        @if($grandchild->color)
                        <span class="w-2 h-2 rounded-full shrink-0"
                              style="background-color: {{ $grandchild->color }}"></span>
                        @endif
                        <span class="text-xs font-medium text-ink group-hover:text-salvia
                                     transition-colors truncate">
                            {{ $grandchild->name }}
                        </span>
                    </div>
                </a>
                @endforeach
            </div>
            @endif

        </div>
        @endforeach
    </div>
    @endif

    {{-- ===== DUE COLONNE ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- ========== DA FARE ========== --}}
        <div class="space-y-4">

            {{-- Scadenze --}}
            @if($scadenze->isNotEmpty())
            <div class="rounded-xl border border-paper-dark bg-white px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-salvia mb-3">Scadenze</p>
                <ul class="space-y-1.5">
                    @foreach($scadenze as $task)
                    <li class="flex items-center gap-2 text-sm">
                        @php $gg = (int) now()->diffInDays($task->due_date, false); @endphp
                        <span class="shrink-0 text-xs font-medium w-16
                                     {{ $gg < 0 ? 'text-terracotta' : ($gg <= 3 ? 'text-terracotta/80' : 'text-ink/40') }}">
                            @if($gg === 0) oggi
                            @elseif($gg === 1) domani
                            @elseif($gg > 1) tra {{ $gg }} gg
                            @else {{ abs($gg) }} gg fa
                            @endif
                        </span>
                        <a href="{{ route('tasks.show', $task) }}"
                           class="flex-1 text-ink hover:text-salvia truncate">
                            {{ $task->title }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Task tree --}}
            <div class="rounded-xl border border-paper-dark bg-white px-5 py-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-salvia">Da fare</p>
                    <div class="flex items-center gap-1.5">
                        <input wire:model="quickTaskTitle"
                               wire:keydown.enter.prevent="quickAddTask"
                               type="text"
                               placeholder="+ task veloce..."
                               class="text-xs border border-dashed border-paper-dark rounded-lg px-2.5 py-1
                                      focus:outline-none focus:border-salvia focus:border-solid
                                      placeholder:text-ink/25 w-36">
                    </div>
                </div>

                @forelse($taskTree as $task)
                <div class="mb-2" wire:key="tree-{{ $task->id }}">
                    {{-- Riga task padre --}}
                    <div class="flex items-center gap-2 group py-1">
                        @if($task->children->isNotEmpty())
                        <button wire:click="toggleExpand({{ $task->id }})"
                                class="text-ink/30 hover:text-ink text-xs w-4 shrink-0 transition-colors">
                            {{ in_array($task->id, $expanded) ? '▾' : '▸' }}
                        </button>
                        @else
                        <span class="w-4 shrink-0"></span>
                        @endif

                        <a href="{{ route('tasks.show', $task) }}"
                           class="flex-1 text-sm text-ink hover:text-salvia truncate transition-colors">
                            {{ $task->title }}
                        </a>

                        @if($task->stage)
                        <span class="shrink-0 text-xs px-1.5 py-0.5 rounded font-medium"
                              style="background-color: {{ $task->stage->bg_color }}; color: {{ $task->stage->text_color }}">
                            {{ $task->stage->label }}
                        </span>
                        @endif

                        @if($task->due_date)
                        @php $over = $task->due_date->isPast(); @endphp
                        <span class="shrink-0 text-xs {{ $over ? 'text-terracotta font-medium' : 'text-ink/35' }}">
                            {{ $task->due_date->format('d/m') }}
                        </span>
                        @endif
                    </div>

                    {{-- Figli (visibili se espanso) --}}
                    @if(in_array($task->id, $expanded) && $task->children->isNotEmpty())
                    <div class="ml-6 border-l border-paper-dark pl-3 space-y-0.5 mb-1">
                        @foreach($task->children as $child)
                        <div class="flex items-center gap-2 py-0.5" wire:key="child-{{ $child->id }}">
                            <span class="text-ink/30 text-xs">•</span>
                            <a href="{{ route('tasks.show', $child) }}"
                               class="flex-1 text-sm text-ink/80 hover:text-salvia truncate">
                                {{ $child->title }}
                            </a>
                            @if($child->stage)
                            <span class="shrink-0 text-xs px-1.5 py-0.5 rounded"
                                  style="background-color: {{ $child->stage->bg_color }}; color: {{ $child->stage->text_color }}">
                                {{ $child->stage->label }}
                            </span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @empty
                <p class="text-sm text-ink/30 italic">Nessun task aperto in quest'area.</p>
                @endforelse
            </div>

        </div>

        {{-- ========== STORIA ========== --}}
        <div class="rounded-xl border border-paper-dark bg-white px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-salvia">Storia</p>
                {{-- Filtri tipo --}}
                <div class="flex items-center gap-1 text-xs">
                    @foreach(['tutto' => 'Tutto', 'diario' => 'Diario', 'aggiornamenti' => 'Note', 'fatti' => 'Fatti'] as $code => $label)
                    <button wire:click="$set('storiaFilter', '{{ $code }}')"
                            class="px-2 py-0.5 rounded transition-colors
                                   {{ $storiaFilter === $code ? 'bg-salvia text-white' : 'text-ink/50 hover:text-ink' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Timeline --}}
            @forelse($storia as $item)
            <div class="border-l-2 border-paper-dark pl-4 mb-4 group"
                 wire:key="storia-{{ $item['tipo'] }}-{{ $item['obj']->id }}">

                {{-- Data + tipo --}}
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs text-ink/40">
                        {{ $item['data'] instanceof \Carbon\Carbon
                            ? $item['data']->format('d/m/Y')
                            : \Carbon\Carbon::parse($item['data'])->format('d/m/Y') }}
                    </span>
                    @php
                        $badgeClass = match($item['tipo']) {
                            'diario'       => 'bg-salvia/10 text-salvia',
                            'aggiornamento'=> 'bg-paper-dark text-ink/50',
                            'fatto'        => 'bg-green-50 text-green-700',
                            default        => 'bg-paper-dark text-ink/50',
                        };
                        $badgeLabel = match($item['tipo']) {
                            'diario'       => 'diario',
                            'aggiornamento'=> 'nota',
                            'fatto'        => '✓ fatto',
                            default        => $item['tipo'],
                        };
                    @endphp
                    <span class="text-xs px-1.5 py-0.5 rounded {{ $badgeClass }}">{{ $badgeLabel }}</span>

                    {{-- Pill sotto-area (solo se diversa dall'area corrente) --}}
                    @if(!empty($item['area']) && $item['area']->id !== $area->id)
                    <span class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded border border-paper-dark text-ink/40">
                        @if($item['area']->color)
                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                              style="background-color: {{ $item['area']->color }}"></span>
                        @endif
                        {{ $item['area']->name }}
                    </span>
                    @endif

                    @if($item['tipo'] === 'aggiornamento' && $item['obj']->task)
                    <a href="{{ route('tasks.show', $item['obj']->task) }}"
                       class="text-xs text-ink/40 hover:text-salvia truncate max-w-[150px]">
                        {{ $item['obj']->task->title }}
                    </a>
                    @endif
                </div>

                {{-- Contenuto --}}
                @if($item['tipo'] === 'diario')
                <p class="text-sm text-ink/80 leading-relaxed whitespace-pre-wrap">
                    {{ \Illuminate\Support\Str::limit($item['obj']->content, 200) }}
                </p>
                @elseif($item['tipo'] === 'aggiornamento')
                <p class="text-sm text-ink/70 leading-relaxed">
                    {{ \Illuminate\Support\Str::limit($item['obj']->content, 150) }}
                </p>
                @elseif($item['tipo'] === 'fatto')
                <p class="text-sm font-medium text-ink/80">
                    <a href="{{ route('tasks.show', $item['obj']) }}" class="hover:text-salvia">
                        {{ $item['obj']->title }}
                    </a>
                </p>
                @endif

            </div>
            @empty
            <p class="text-sm text-ink/30 italic">Nessuna storia per quest'area ancora.</p>
            @endforelse

            @if($storiaHasMore)
            <button wire:click="loadMore"
                    class="text-xs text-salvia hover:text-salvia-dark transition-colors mt-2">
                Carica altri...
            </button>
            @endif
        </div>

    </div>

</div>
