<div class="space-y-6"
     x-data
     @task-created.window="window.location.reload()">

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
                        <span class="font-medium">{{ $nodo['name'] ?? '—' }}</span>
                        @if(!empty($nodo['properties']['description']))
                            <span class="text-ink/50"> — {{ Str::limit($nodo['properties']['description'], 80) }}</span>
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
                <li class="flex items-center gap-3 text-sm">
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
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            @foreach($aree as $tessera)
            <a href="{{ route('aree.show', $tessera->area) }}"
               class="group block rounded-xl border border-paper-dark bg-white px-4 py-4 hover:border-salvia-light hover:shadow-sm transition-all">

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

            </a>
            @endforeach

            {{-- Tessera "Aggiungi area" --}}
            <a href="{{ route('aree') }}"
               class="flex items-center justify-center rounded-xl border border-dashed border-paper-dark text-ink/30 hover:border-salvia hover:text-salvia transition-colors px-4 py-4 text-sm gap-1.5">
                <span class="text-lg leading-none">+</span>
                <span>Nuova area</span>
            </a>

        </div>
    </div>

</div>
