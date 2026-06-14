<div class="pb-10">
@php
    $pct      = fn($date) => min(100, max(0, $windowStart->diffInDays($date) / $totalDays * 100));
    $barWidth = fn($s, $e) => max(0.4, $pct($e) - $pct($s));
@endphp

{{-- Filtro tag multi-select (07-delta §1) --}}
@if($tags->isNotEmpty())
<div class="flex flex-wrap items-center gap-1.5 mb-3">
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
    @if(count($filterTags))
    <button wire:click="$set('filterTags', [])" class="text-xs text-terracotta hover:text-ink ml-1">azzera</button>
    @endif
</div>
@endif

<div class="overflow-x-auto rounded-xl border border-paper-dark bg-white">
<div class="min-w-[700px] relative" id="timeline-grid-wrap">

    {{-- ===== LINEA "OGGI" continua sull'intera altezza del grid ===== --}}
    <div class="absolute top-0 bottom-0 w-0.5 bg-terracotta/40 pointer-events-none z-10"
         style="left: calc(11rem + (100% - 11rem) * {{ $todayPct / 100 }})">
        <span class="absolute top-1 left-1 text-[10px] text-terracotta/80 whitespace-nowrap font-medium select-none">
            oggi
        </span>
    </div>

    <div class="grid" style="grid-template-columns: 11rem 1fr">

        {{-- ===== ASSE ANNI ===== --}}
        <div class="px-3 py-2 border-b border-r border-paper-dark">
            <span class="text-xs text-ink/30 uppercase tracking-wide">Area</span>
        </div>
        <div class="relative h-8 border-b border-paper-dark">
            @foreach($years as $yr)
            <span class="absolute top-1.5 text-xs text-ink/35 -translate-x-1/2 select-none pointer-events-none"
                  style="left: {{ $yr['pct'] }}%">{{ $yr['year'] }}</span>
            <div class="absolute inset-y-0 w-px bg-paper-dark pointer-events-none"
                 style="left: {{ $yr['pct'] }}%"></div>
            @endforeach
        </div>

        {{-- ===== GOALS ===== --}}
        @if($goals->isNotEmpty())
        <div class="px-3 py-2 border-b border-r border-paper-dark flex items-center">
            <span class="text-xs font-medium text-salvia">★ Goals</span>
        </div>
        <div class="relative h-10 border-b border-paper-dark">
            @foreach($goals as $goal)
            @php $gPct = $pct($goal->deadline); @endphp

            {{-- Rombo: centrato sulla data --}}
            <div class="absolute pointer-events-none"
                 style="left: {{ $gPct }}%; top: 50%; transform: translate(-50%, -50%)">
                <div class="w-3.5 h-3.5 rotate-45
                            {{ $goal->status === 'raggiunto' ? 'bg-emerald-500' : 'bg-salvia' }}">
                </div>
            </div>

            {{-- Etichetta: parte dall'offset del rombo, indipendente --}}
            <div class="absolute text-[10px] font-medium whitespace-nowrap
                        {{ $goal->status === 'raggiunto' ? 'text-emerald-600' : 'text-salvia' }}"
                 style="left: calc({{ $gPct }}% + 12px); top: 50%; transform: translateY(-50%)"
                 title="{{ $goal->title }}">
                {{ Str::limit($goal->title, 24) }}
            </div>
            @endforeach
        </div>
        @endif

        {{-- ===== CORSIE AREE ===== --}}
        @forelse($rows as $row)
        @php
            $area     = $row['area'];
            $indent   = $row['depth'] * 12 + 8;
            $expanded = $row['expanded'];
        @endphp

        {{-- Label --}}
        <div class="border-b border-r border-paper-dark py-2 pr-2 flex items-center"
             style="padding-left: {{ $indent }}px"
             wire:key="tl-label-{{ $area->id }}">
            <button wire:click="toggleArea({{ $area->id }})"
                    class="flex items-center gap-1.5 w-full text-left group min-w-0">
                @if($row['hasChildren'])
                <span class="text-ink/30 group-hover:text-salvia text-xs transition-colors shrink-0">
                    {{ $expanded ? '▾' : '▸' }}
                </span>
                @else
                <span class="w-3 shrink-0"></span>
                @endif
                @if($area->color)
                <span class="w-2 h-2 rounded-full shrink-0"
                      style="background-color: {{ $area->color }}"></span>
                @endif
                <span class="text-xs {{ $row['depth'] === 0 ? 'font-medium text-ink' : 'text-ink/65' }} truncate">
                    {{ $area->name }}
                </span>
            </button>
        </div>

        {{-- Barre --}}
        <div class="relative h-10 border-b border-paper-dark"
             wire:key="tl-bar-{{ $area->id }}">

            {{-- Linee anno (sfondo) --}}
            @foreach($years as $yr)
            <div class="absolute inset-y-0 w-px bg-paper-dark/60 pointer-events-none"
                 style="left: {{ $yr['pct'] }}%"></div>
            @endforeach

            {{-- Envelope --}}
            @if($row['envStart'] && $row['envEnd'])
            @php
                $eLeft  = $pct($row['envStart']);
                $eWidth = $barWidth($row['envStart'], $row['envEnd']);
                $color  = $area->color ?? '#5C6B4F';
            @endphp
            <div class="absolute rounded-full pointer-events-none"
                 style="left: {{ $eLeft }}%; width: {{ $eWidth }}%;
                        top: 50%; height: 12px; transform: translateY(-50%);
                        background-color: {{ $color }}; opacity: 0.2"></div>
            @endif

            {{-- Task --}}
            @foreach($row['tasks'] as $task)
            @if($task->start_date && $task->due_date)
            @php
                $tLeft  = $pct($task->start_date);
                $tWidth = $barWidth($task->start_date, $task->due_date);
                $past   = $task->due_date->isPast();
            @endphp
            <div class="absolute rounded-full group cursor-default"
                 style="left: {{ $tLeft }}%; width: {{ $tWidth }}%;
                        top: 50%; height: 6px; transform: translateY(-50%);
                        background-color: {{ $past ? '#C98A6B' : '#8A9E7A' }}"
                 title="{{ $task->title }}">
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1
                            bg-ink text-white text-[10px] px-2 py-0.5 rounded whitespace-nowrap
                            opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-20">
                    {{ $task->title }}
                </div>
            </div>

            @elseif($task->due_date)
            @php
                $dLeft = $pct($task->due_date);
                $done  = $task->completed_at !== null;
                $past  = $task->due_date->isPast();
                $dotColor = $done ? '#34d399' : ($past ? '#C98A6B' : '#ffffff');
                $dotBorder = $done ? '#10b981' : ($past ? '#C98A6B' : '#8A9E7A');
            @endphp
            <div class="group cursor-default"
                 style="position: absolute;
                        left: {{ $dLeft }}%; top: 50%;
                        transform: translate(-50%, -50%);
                        width: 10px; height: 10px">
                <div style="width: 10px; height: 10px; border-radius: 50%;
                            background-color: {{ $dotColor }};
                            border: 2px solid {{ $dotBorder }}; box-sizing: border-box">
                </div>
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1
                            bg-ink text-white text-[10px] px-2 py-0.5 rounded whitespace-nowrap
                            opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-20">
                    {{ $task->title }} · {{ $task->due_date->format('d/m/Y') }}
                </div>
            </div>
            @endif
            @endforeach
        </div>

        @empty
        <div class="col-span-2 px-6 py-8 text-center text-sm text-ink/30 italic">
            Nessuna area con date ancora. Aggiungi scadenze ai task.
        </div>
        @endforelse

    </div>{{-- grid --}}
</div>{{-- relative wrapper --}}
</div>{{-- overflow-x-auto --}}

{{-- Legenda --}}
<div class="flex items-center gap-5 mt-4 px-1 flex-wrap">
    <div class="flex items-center gap-1.5 text-xs text-ink/50">
        <div style="width:10px;height:10px;border-radius:50%;background:#fff;border:2px solid #8A9E7A"></div>
        <span>Scadenza futura</span>
    </div>
    <div class="flex items-center gap-1.5 text-xs text-ink/50">
        <div style="width:10px;height:10px;border-radius:50%;background:#C98A6B;border:2px solid #C98A6B"></div>
        <span>Scaduto</span>
    </div>
    <div class="flex items-center gap-1.5 text-xs text-ink/50">
        <div style="width:10px;height:10px;border-radius:50%;background:#34d399;border:2px solid #10b981"></div>
        <span>Completato</span>
    </div>
    <div class="flex items-center gap-1.5 text-xs text-ink/50">
        <div style="width:16px;height:6px;border-radius:3px;background:#8A9E7A"></div>
        <span>Intervallo start→due</span>
    </div>
    <div class="flex items-center gap-1.5 text-xs text-ink/50">
        <div style="width:12px;height:12px;background:#5C6B4F;transform:rotate(45deg)"></div>
        <span>Goal</span>
    </div>
    <div class="flex items-center gap-1.5 text-xs text-ink/50">
        <div style="width:2px;height:14px;background:#C98A6B;opacity:0.6"></div>
        <span>Oggi</span>
    </div>
</div>

</div>
