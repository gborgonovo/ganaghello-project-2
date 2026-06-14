<div class="max-w-3xl mx-auto space-y-6 pb-16">

    {{-- Torna indietro --}}
    <x-back />

    {{-- ===== IMMAGINE ===== --}}
    @if($coverUrl)
    <div class="rounded-xl overflow-hidden border border-paper-dark bg-white">
        <img src="{{ $coverUrl }}"
             alt="{{ $inspiration->title ?? '' }}"
             class="w-full max-h-[60vh] object-contain bg-ink/5"
             onerror="this.closest('.rounded-xl').style.display='none'">
    </div>
    @endif

    {{-- ===== CORPO ===== --}}
    <div class="bg-white rounded-xl border border-paper-dark p-6 space-y-4">

        @if($editing)
        {{-- Modalità edit --}}
        <div class="space-y-3">
            <div>
                <label class="block text-xs text-ink/50 mb-1">Titolo</label>
                <input type="text" wire:model="editTitle" placeholder="Titolo..."
                       class="w-full text-base font-semibold border border-paper-dark rounded-lg px-3 py-2
                              focus:outline-none focus:border-salvia">
            </div>
            <div>
                <label class="block text-xs text-ink/50 mb-1">Descrizione</label>
                <textarea wire:model="editDesc" rows="4" placeholder="Descrizione / note..."
                          class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                                 focus:outline-none focus:border-salvia resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-ink/50 mb-1">URL fonte</label>
                    <input type="url" wire:model="editUrl" placeholder="https://..."
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                                  focus:outline-none focus:border-salvia">
                </div>
                <div>
                    <label class="block text-xs text-ink/50 mb-1">URL immagine</label>
                    <input type="url" wire:model="editImageUrl" placeholder="https://..."
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                                  focus:outline-none focus:border-salvia">
                </div>
            </div>
            <div>
                <label class="block text-xs text-ink/50 mb-1">Area</label>
                <select wire:model="editAreaId"
                        class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                               focus:outline-none focus:border-salvia bg-white">
                    <option value="">Nessuna area</option>
                    @foreach($aree as $area)
                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 pt-1">
                <button wire:click="saveEdit"
                        class="px-5 py-2 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors">
                    Salva
                </button>
                <button wire:click="cancelEdit"
                        class="px-4 py-2 text-sm text-ink/50 hover:text-ink transition-colors">
                    Annulla
                </button>
            </div>
        </div>

        @else
        {{-- Vista normale --}}
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1 min-w-0">
                @if($inspiration->title)
                <h1 class="text-xl font-semibold text-ink leading-snug">{{ $inspiration->title }}</h1>
                @endif
                @if($inspiration->area)
                <span class="inline-flex items-center gap-1.5 text-xs text-ink/50">
                    @if($inspiration->area->color)
                    <span class="w-2 h-2 rounded-full shrink-0"
                          style="background-color: {{ $inspiration->area->color }}"></span>
                    @endif
                    {{ $inspiration->area->name }}
                </span>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button wire:click="startEdit"
                        class="text-xs text-ink/40 hover:text-ink transition-colors px-2 py-1">
                    modifica
                </button>
                <x-confirm action="delete"
                        class="text-xs text-ink/30 hover:text-terracotta transition-colors px-2 py-1">
                    elimina
                </x-confirm>
            </div>
        </div>

        @if($inspiration->description)
        <p class="text-sm text-ink/70 font-serif leading-relaxed">{{ $inspiration->description }}</p>
        @endif

        <div class="flex items-center gap-4 pt-1 flex-wrap">
            @if($inspiration->url)
            <a href="{{ $inspiration->url }}" target="_blank"
               class="text-sm text-salvia hover:underline">
                Vai alla fonte
            </a>
            @endif

            @if($convertedTaskId)
            <span class="text-sm text-salvia">
                Task creato:
                <a href="{{ route('tasks.show', $convertedTaskId) }}" wire:navigate
                   class="font-medium hover:underline" wire:navigate>
                    apri →
                </a>
            </span>
            @else
            <button wire:click="convertToTask"
                    class="text-sm text-ink/40 hover:text-salvia transition-colors">
                → Converti in task
            </button>
            @endif
        </div>
        @endif

    </div>

</div>
