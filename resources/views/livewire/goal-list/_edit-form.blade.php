<div class="space-y-2 {{ $isChild ? 'ml-5 pl-3 border-l border-paper-dark' : '' }}">
    <input wire:model="editTitle"
           wire:keydown.enter="saveEdit"
           wire:keydown.escape="cancelEdit"
           type="text"
           autofocus
           class="w-full text-sm font-semibold border-b border-salvia bg-transparent
                  focus:outline-none pb-0.5 placeholder:text-ink/30">
    @error('editTitle') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

    <textarea wire:model="editDesc" rows="2"
              placeholder="Perche' questo goal?"
              class="w-full text-sm border border-paper-dark rounded-lg px-3 py-1.5
                     resize-none focus:outline-none focus:border-salvia placeholder:text-ink/30"></textarea>

    <div class="flex items-center gap-3 flex-wrap">
        <div>
            <label class="text-xs text-ink/50 block mb-0.5">Orizzonte</label>
            <x-date-input wire:model="editDeadline"
                class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5
                       focus:outline-none focus:border-salvia"></x-date-input>
        </div>
        <div>
            <label class="text-xs text-ink/50 block mb-0.5">Stato</label>
            <select wire:model="editStatus"
                    class="text-sm border border-paper-dark rounded-lg px-2.5 py-1.5 bg-white
                           focus:outline-none focus:border-salvia">
                <option value="active">Attivo</option>
                <option value="raggiunto">Raggiunto</option>
                <option value="abbandonato">Abbandonato</option>
            </select>
        </div>
    </div>

    <div class="flex items-center gap-2 pt-1">
        <button wire:click="saveEdit"
                class="text-xs px-3 py-1.5 bg-salvia text-white rounded-lg hover:bg-salvia-dark">
            Salva
        </button>
        <button wire:click="cancelEdit"
                class="text-xs text-ink/50 hover:text-ink transition-colors">
            Annulla
        </button>
    </div>
</div>
