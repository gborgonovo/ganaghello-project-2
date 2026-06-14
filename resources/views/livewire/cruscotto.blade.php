<div class="space-y-6" x-data>

    {{-- ===== HEADER: bentornato + dormienti + scadenze ===== --}}
    <div class="rounded-xl border border-paper-dark bg-white px-4 sm:px-6 py-5 space-y-5">

        {{-- Bentornato --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-base font-medium text-ink">
                    @if($giorniAssenza <= 0)
                        Benvenuto, {{ auth()->user()->name }}.
                    @elseif($giorniAssenza === 1)
                        Bentornato. Eri qui ieri.
                    @else
                        Bentornato. Sono passati {{ $giorniAssenza }} giorni.
                    @endif
                </p>
                @if(!$mnemoOk)
                    <p class="text-xs text-salvia-light mt-0.5">Mnemosyne non raggiungibile — i dormienti non sono disponibili.</p>
                @endif
            </div>
            @livewire('task-form')
        </div>

        {{-- Dormienti Mnemosyne --}}
        @if($dormienti->isNotEmpty())
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-salvia mb-2">Cosa si sta raffreddando</p>
            <ul class="space-y-1">
                @foreach($dormienti as $nodo)
                <li class="flex items-start gap-2 text-sm text-ink/80">
                    <span class="mt-0.5 text-terracotta">•</span>
                    <span>
                        @if($nodo['url'] ?? null)
                            <a href="{{ $nodo['url'] }}" wire:navigate class="font-medium hover:text-salvia transition-colors">{{ $nodo['label'] }}</a>
                        @else
                            <span class="font-medium">{{ $nodo['label'] ?: '—' }}</span>
                        @endif
                        @if(!empty($nodo['properties']['description']))
                            <span class="text-ink/50"> · {{ Str::limit($nodo['properties']['description'], 80) }}</span>
                        @endif
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Scadenze imminenti --}}
        @if($scadenze->isNotEmpty())
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-salvia mb-2">In arrivo</p>
            <ul class="space-y-1">
                @foreach($scadenze as $task)
                <li>
                    <a href="{{ route('tasks.show', $task) }}" wire:navigate
                       class="flex items-center gap-3 text-sm -mx-2 px-2 py-1 rounded hover:bg-paper-dark/40 transition-colors">
                        <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded"
                              style="background-color: {{ $task->stage->bg_color }}; color: {{ $task->stage->text_color }}">
                            {{ $task->stage->label }}
                        </span>
                        <span class="text-ink flex-1 truncate">{{ $task->title }}</span>
                        <span class="shrink-0 text-ink/50 text-xs">
                            @php $gg = (int) now()->diffInDays($task->due_date, false); @endphp
                            @if($gg === 0) oggi
                            @elseif($gg === 1) domani
                            @elseif($gg > 1) tra {{ $gg }} gg
                            @else <span class="text-terracotta">{{ abs($gg) }} gg fa</span>
                            @endif
                        </span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

    </div>

    {{-- ===== TESSERE AREA ===== --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-salvia">Le tue aree</p>
            <div class="flex items-center gap-1 text-xs">
                <button wire:click="$set('sortAree','calde')"
                    class="px-2.5 py-1 rounded {{ $sortAree === 'calde' ? 'bg-salvia text-white' : 'text-ink/50 hover:text-ink' }} transition-colors">
                    Piu' calde
                </button>
                <button wire:click="$set('sortAree','trascurate')"
                    class="px-2.5 py-1 rounded {{ $sortAree === 'trascurate' ? 'bg-salvia text-white' : 'text-ink/50 hover:text-ink' }} transition-colors">
                    Piu' trascurate
                </button>
            </div>
        </div>

        {{-- Griglia tessere --}}
        <div class="columns-2 md:columns-3 gap-3">
            @foreach($aree as $tessera)
            <a href="{{ route('aree.show', $tessera->area) }}" wire:navigate
               class="break-inside-avoid mb-3 group block rounded-xl border border-paper-dark bg-white px-4 py-4 hover:border-salvia-light hover:shadow-sm transition-all">

                {{-- Nome area + colore --}}
                <div class="flex items-center gap-2 mb-2">
                    @if($tessera->area->color)
                    <span class="w-2.5 h-2.5 rounded-full shrink-0"
                          style="background-color: {{ $tessera->area->color }}"></span>
                    @endif
                    <span class="font-medium text-sm text-ink truncate">{{ $tessera->area->name }}</span>
                </div>

                {{-- Stato di sintesi --}}
                <div class="mb-3">
                    @php
                        $badgeClass = match($tessera->stato) {
                            'cantiere'       => 'text-orange-600 bg-orange-50',
                            'attiva'         => 'text-green-700 bg-green-50',
                            'in_valutazione' => 'text-blue-600 bg-blue-50',
                            'dormiente'      => 'text-gray-400 bg-gray-50',
                            default          => 'text-gray-300 bg-gray-50',
                        };
                        $badgeLabel = match($tessera->stato) {
                            'cantiere'       => '● cantiere',
                            'attiva'         => '● attiva',
                            'in_valutazione' => '● in valutazione',
                            'dormiente'      => '○ dormiente',
                            default          => '○ vuota',
                        };
                    @endphp
                    <span class="text-xs px-1.5 py-0.5 rounded font-medium {{ $badgeClass }}">
                        {{ $badgeLabel }}
                    </span>
                </div>

                {{-- Ultimo movimento --}}
                <p class="text-xs text-ink/50 mb-1 truncate">
                    @if($tessera->ultimoMovimento)
                        {{ $tessera->ultimoMovimento->diffForHumans() }}
                    @else
                        Nessun movimento
                    @endif
                </p>

                {{-- Task aperti --}}
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-ink/40">
                        {{ $tessera->taskAperti }}
                        {{ $tessera->taskAperti === 1 ? 'task aperto' : 'task aperti' }}
                    </span>
                    @if($tessera->prossima)
                    <span class="text-xs text-terracotta font-medium">
                        @php $gg = (int) now()->diffInDays($tessera->prossima->due_date, false); @endphp
                        @if($gg >= 0) scade tra {{ $gg }} gg
                        @else <span>scaduto {{ abs($gg) }} gg fa</span>
                        @endif
                    </span>
                    @endif
                </div>

                {{-- Sotto-aree --}}
                @if($tessera->area->children->isNotEmpty())
                <div class="border-t border-paper-dark mt-3 pt-3 grid grid-cols-2 gap-1.5">
                    @foreach($tessera->area->children as $child)
                    <span class="block rounded-lg border border-paper-dark px-2 py-1.5
                                 hover:border-salvia transition-colors cursor-pointer"
                          @click.stop="Livewire.navigate('{{ route('aree.show', $child) }}')">
                        <div class="flex items-center gap-1.5 min-w-0 mb-0.5">
                            @if($child->color)
                            <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                  style="background-color: {{ $child->color }}"></span>
                            @endif
                            <span class="text-[11px] font-medium text-ink truncate">{{ $child->name }}</span>
                        </div>
                        <p class="text-[10px] text-ink/40">
                            {{ $child->open_tasks_count > 0 ? $child->open_tasks_count . ' aperti' : 'nessun task' }}
                        </p>
                    </span>
                    @endforeach
                </div>
                @endif

            </a>
            @endforeach

            {{-- Tessera "Aggiungi area" --}}
            <a href="{{ route('aree') }}" wire:navigate
               class="break-inside-avoid mb-3 flex items-center justify-center rounded-xl border border-dashed border-paper-dark text-ink/30 hover:border-salvia hover:text-salvia transition-colors px-4 py-4 text-sm gap-1.5">
                <span class="text-lg leading-none">+</span>
                <span>Nuova area</span>
            </a>

        </div>
    </div>

</div>
