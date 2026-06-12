<div class="flex flex-col sm:flex-row gap-6 sm:gap-8 max-w-4xl mx-auto">
    <x-settings-nav />

    <div class="flex-1 max-w-lg">
        <h1 class="text-base font-semibold text-ink mb-6">Profilo</h1>

        <div class="bg-white rounded-xl border border-paper-dark p-6 space-y-4">

            <div>
                <label class="block text-xs text-ink/50 mb-1">Nome</label>
                <input type="text" wire:model="name"
                       class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                              focus:outline-none focus:border-salvia bg-white">
                @error('name')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs text-ink/50 mb-1">Email</label>
                <input type="email" wire:model="email"
                       class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2
                              focus:outline-none focus:border-salvia bg-white">
                @error('email')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="save"
                        class="px-4 py-2 bg-salvia text-white rounded-lg text-sm
                               hover:bg-salvia-dark transition-colors">
                    Salva
                </button>
                @if($saved)
                <span class="text-xs text-salvia" wire:poll.3000ms="$set('saved', false)">Salvato.</span>
                @endif
            </div>

        </div>
    </div>
</div>
