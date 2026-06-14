@php
    // Striscia priorità (07-delta §4): solo alta/media, bassa nessuna.
    $stripe = match($task->priority) {
        'alta'  => '#C25E3A',
        'media' => '#D4A017',
        default => null,
    };
@endphp
<div class="break-inside-avoid mb-2 rounded-lg border border-paper-dark bg-white overflow-hidden
            hover:border-salvia-light hover:shadow-sm transition-all group"
     @if($stripe) style="border-left: 3px solid {{ $stripe }}" @endif
     data-task-id="{{ $task->id }}"
     wire:key="task-{{ $task->id }}">

    {{-- Thumbnail prima immagine --}}
    @if($task->firstImage?->media)
    <a href="{{ route('tasks.show', $task) }}" wire:navigate class="block" @click.stop>
        <img src="{{ route('media.serve', [$task->firstImage->media, 'thumb']) }}"
             alt=""
             class="w-full h-32 object-cover">
    </a>
    @endif

    <div class="px-3 py-3">

        {{-- Area --}}
        @if($task->area)
        <div class="mb-1.5">
            <x-area-chip :area="$task->area" />
        </div>
        @endif

        {{-- Titolo --}}
        <a href="{{ route('tasks.show', $task) }}" wire:navigate
           class="block text-sm font-medium text-ink hover:text-salvia transition-colors leading-snug mb-1.5"
           @click.stop>
            {{ $task->title }}
        </a>

        {{-- Badge padre --}}
        @if($task->parent)
        <p class="text-xs text-ink/40 mb-1.5 truncate">
            ↑ {{ Str::limit($task->parent->title, 35) }}
        </p>
        @endif

        {{-- Anteprima ultimo aggiornamento --}}
        @if($task->latestUpdate)
        <p class="text-xs text-ink/50 leading-relaxed line-clamp-2 mb-2">
            {{ $task->latestUpdate->content }}
        </p>
        @endif

        {{-- Executor + stima di lavoro (07-delta §3): solo se valorizzati --}}
        @if($task->executor || $task->work_estimate)
        <p class="flex items-center gap-1 text-xs text-ink/45 mb-1.5">
            @if($task->executor)
            <span>{{ ['una_persona' => '👤', 'team' => '👥', 'professionista' => '🔧', 'impresa' => '🏗'][$task->executor] ?? '' }}</span>
            <span>{{ ['una_persona' => 'una persona', 'team' => 'team', 'professionista' => 'professionista', 'impresa' => 'impresa'][$task->executor] ?? '' }}</span>
            @endif
            @if($task->work_estimate)
            <span class="text-ink/25">·</span>
            <span>{{ rtrim(rtrim(number_format($task->work_estimate, 2, ',', ''), '0'), ',') }}h</span>
            @endif
        </p>
        @endif

        {{-- Footer: n figli + data + frecce stage --}}
        <div class="flex items-center justify-between mt-1 pt-1 border-t border-paper-dark/50">
            <div class="flex items-center gap-2">
                @if($task->children_count > 0)
                <span class="text-xs text-salvia-light">{{ $task->children_count }} sub</span>
                @endif

                @if($task->due_date)
                @php $overdue = $task->due_date->isPast() && !$task->isDone(); @endphp
                <span class="text-xs font-medium {{ $overdue ? 'text-terracotta' : 'text-ink/35' }}">
                    {{ $task->due_date->format('d/m') }}@if($overdue) !@endif
                </span>
                @endif
            </div>

            <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                 @click.stop>
                <button wire:click.stop="stepBackward({{ $task->id }})"
                        title="Stage precedente"
                        class="px-1.5 py-0.5 text-ink/30 hover:text-ink hover:bg-paper-dark rounded text-xs transition-all">
                    ←
                </button>
                <button wire:click.stop="stepForward({{ $task->id }})"
                        title="Stage successivo"
                        class="px-1.5 py-0.5 text-ink/30 hover:text-salvia hover:bg-salvia/10 rounded text-xs transition-all">
                    →
                </button>
            </div>
        </div>

    </div>
</div>
