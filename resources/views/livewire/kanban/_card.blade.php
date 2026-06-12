<div class="break-inside-avoid mb-2 rounded-lg border border-paper-dark bg-white overflow-hidden
            hover:border-salvia-light hover:shadow-sm transition-all group"
     data-task-id="{{ $task->id }}"
     wire:key="task-{{ $task->id }}">

    {{-- Thumbnail prima immagine --}}
    @if($task->firstImage?->media)
    <a href="{{ route('tasks.show', $task) }}" class="block" @click.stop>
        <img src="{{ route('media.serve', [$task->firstImage->media, 'thumb']) }}"
             alt=""
             class="w-full h-32 object-cover">
    </a>
    @endif

    <div class="px-3 py-3">

        {{-- Area --}}
        @if($task->area)
        <span class="flex items-center gap-1 text-xs text-ink/50 mb-1.5 min-w-0">
            @if($task->area->color)
            <span class="w-2 h-2 rounded-full shrink-0"
                  style="background-color: {{ $task->area->color }}"></span>
            @endif
            <span class="truncate">{{ $task->area->name }}</span>
        </span>
        @endif

        {{-- Titolo --}}
        <a href="{{ route('tasks.show', $task) }}"
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
