@props([
    'accept'   => 'image/*',
    'multiple' => false,
    'label'    => 'Trascina o clicca per scegliere',
])

<div x-data="{
        dragging: false,
        open()  { $refs.input.click() },
        drop(e) {
            this.dragging = false;
            const files = [...e.dataTransfer.files].filter(f =>
                '{{ $accept }}' === 'image/*' ? f.type.startsWith('image/') : true
            );
            if (!files.length) return;
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            $refs.input.files = dt.files;
            $refs.input.dispatchEvent(new Event('change'));
        }
    }"
     @dragover.prevent="dragging = true"
     @dragleave.prevent="dragging = false"
     @drop.prevent="drop($event)"
     @click="open()"
     :class="dragging ? 'border-salvia bg-salvia/10 scale-[1.01]' : 'border-paper-dark hover:border-salvia hover:bg-salvia/5'"
     class="rounded-xl border-2 border-dashed transition-all cursor-pointer">
    <div class="py-5 flex flex-col items-center gap-1.5 pointer-events-none">
        <span class="text-2xl" :class="dragging ? 'text-salvia' : 'text-ink/25'">⊕</span>
        <span class="text-xs" :class="dragging ? 'text-salvia font-medium' : 'text-ink/40'">
            <span x-show="!dragging">{{ $label }}</span>
            <span x-show="dragging">Rilascia per caricare</span>
        </span>
    </div>
    <input type="file"
           x-ref="input"
           {{ $attributes->wire('model') }}
           accept="{{ $accept }}"
           @if($multiple) multiple @endif
           class="sr-only">
</div>
