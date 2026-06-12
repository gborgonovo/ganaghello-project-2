<div wire:key="stage-{{ $stage->id }}">

    {{-- Header stage --}}
    <div class="flex items-center gap-2 mb-2">
        <span class="text-xs font-semibold uppercase tracking-wider px-2 py-0.5 rounded"
              style="background-color: {{ $stage->bg_color }}; color: {{ $stage->text_color }}">
            {{ $stage->label }}
        </span>
        @if($stageTasks->isNotEmpty())
        <span class="text-xs text-ink/40">{{ $stageTasks->count() }}</span>
        @endif
    </div>

    {{-- Quick add --}}
    <div class="mb-3">
        <input wire:model="quickAdd.{{ $stage->id }}"
               wire:keydown.enter.prevent="quickAddTask({{ $stage->id }})"
               type="text"
               placeholder="+ task veloce..."
               class="w-full max-w-xs text-sm border border-dashed border-paper-dark rounded-lg px-3 py-1.5
                      focus:outline-none focus:border-salvia focus:border-solid bg-transparent
                      placeholder:text-ink/25 text-ink">
    </div>

    {{-- Griglia card (masonry con CSS columns) --}}
    <div class="columns-2 md:columns-3 lg:columns-4 xl:columns-5 gap-2"
         data-sortable-stage="{{ $stage->id }}"
         wire:key="cards-{{ $stage->id }}">

        @forelse($stageTasks as $task)
        @include('livewire.kanban._card', ['task' => $task])
        @empty
        <p class="text-xs text-ink/25 italic py-1 col-span-full">Nessun task</p>
        @endforelse

    </div>
</div>
