<div>
<div class="max-w-2xl mx-auto pb-16">

    {{-- Navigazione + azioni --}}
    <div class="flex items-center justify-between mb-8">
        <a href="{{ route('note') }}"
           class="text-sm text-ink/40 hover:text-ink transition-colors flex items-center gap-1.5">
            ← Note
        </a>
        <button wire:click="openEdit"
                class="text-xs text-salvia hover:text-salvia-dark transition-colors">
            ✎ Modifica
        </button>
    </div>

    {{-- Titolo --}}
    @if($note->title)
    <h1 class="text-2xl font-semibold text-ink mb-4 leading-tight">{{ $note->title }}</h1>
    @endif

    {{-- Area --}}
    @if($note->area)
    <div class="flex items-center gap-1.5 mb-6 text-xs text-ink/40">
        @if($note->area->color)
        <span class="w-1.5 h-1.5 rounded-full shrink-0"
              style="background-color: {{ $note->area->color }}"></span>
        @endif
        {{ $note->area->name }}
    </div>
    @elseif($note->title)
    <div class="mb-6"></div>
    @endif

    {{-- Contenuto --}}
    <div class="prose prose-sm max-w-none font-serif text-ink leading-relaxed">
        {!! Str::markdown($note->content, ['html_input' => 'escape']) !!}
    </div>

</div>

{{-- ===== MODALE MODIFICA ===== --}}
@if($showModal)
<div class="fixed inset-0 z-50 flex items-start justify-center bg-ink/60 p-4 overflow-y-auto"
     wire:click.self="closeModal">
    <div class="relative w-full max-w-xl bg-paper rounded-2xl shadow-2xl my-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-paper-dark">
            <h2 class="text-sm font-semibold text-ink">Modifica nota</h2>
            <button wire:click="closeModal"
                    class="w-7 h-7 flex items-center justify-center rounded-full
                           text-ink/30 hover:text-ink hover:bg-paper-dark transition-colors text-sm">
                ✕
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs text-ink/50 mb-1">Titolo (opzionale)</label>
                <input type="text" wire:model="modalTitle" placeholder="Titolo..."
                       class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                              focus:outline-none focus:border-salvia bg-white">
            </div>
            <div>
                <label class="block text-xs text-ink/50 mb-1">Area</label>
                <select wire:model="modalAreaId"
                        class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                               focus:outline-none focus:border-salvia bg-white">
                    <option value="">Nessuna</option>
                    @foreach($aree as $area)
                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-ink/50 mb-1">Testo</label>
                <textarea wire:model="modalContent" rows="10"
                          class="w-full text-sm font-serif text-ink border border-paper-dark rounded-xl
                                 px-4 py-3 resize-none focus:outline-none focus:border-salvia leading-relaxed
                                 bg-white"></textarea>
                @error('modalContent')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-paper-dark">
            <button wire:click="closeModal"
                    class="text-sm text-ink/50 hover:text-ink transition-colors px-4 py-2">
                Annulla
            </button>
            <button wire:click="saveModal" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-salvia text-white rounded-lg text-sm
                           hover:bg-salvia-dark transition-colors disabled:opacity-50">
                <span wire:loading.remove wire:target="saveModal">Salva</span>
                <span wire:loading wire:target="saveModal">Salvataggio...</span>
            </button>
        </div>
    </div>
</div>
@endif
</div>
