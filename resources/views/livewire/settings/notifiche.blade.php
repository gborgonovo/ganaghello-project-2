<div class="flex flex-col sm:flex-row gap-6 sm:gap-8 max-w-4xl mx-auto">
    <x-settings-nav />

    <div class="flex-1 max-w-lg space-y-6 py-6">
        <h2 class="text-base font-semibold text-ink">Notifiche</h2>

        {{-- Abilitazione --}}
        <div class="bg-white rounded-xl border border-paper-dark p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-ink">Digest giornaliero</p>
                    <p class="text-xs text-ink/50 mt-0.5">Email nei giorni esatti di promemoria di una scadenza, con eventuali fili che si raffreddano</p>
                </div>
                <button wire:click="$toggle('enabled')"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                               {{ $enabled ? 'bg-salvia' : 'bg-paper-dark' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                 {{ $enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
            </div>

            @if($enabled)
            <hr class="border-paper-dark">

            {{-- Ora invio --}}
            <div>
                <label class="block text-xs text-ink/50 mb-2">Ora di invio</label>
                <div class="flex items-center gap-2">
                    <select wire:model="hour"
                            class="text-sm border border-paper-dark rounded-lg px-3 py-1.5 bg-white focus:outline-none focus:border-salvia">
                        @for($h = 5; $h <= 21; $h++)
                        <option value="{{ $h }}">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                    <span class="text-sm text-ink/50">:</span>
                    <select wire:model="minute"
                            class="text-sm border border-paper-dark rounded-lg px-3 py-1.5 bg-white focus:outline-none focus:border-salvia">
                        <option value="0">00</option>
                        <option value="30">30</option>
                    </select>
                </div>
            </div>

            {{-- Soglie --}}
            <div>
                <label class="block text-xs text-ink/50 mb-2">Promemoria scadenza</label>
                <p class="text-xs text-ink/40 mb-2 leading-relaxed">
                    Un promemoria nel giorno esatto, sia prima sia dopo la scadenza
                    (es. 7 giorni prima e 7 giorni dopo). Oltre i 30 giorni di ritardo,
                    un promemoria ogni 30 giorni finché il task resta aperto.
                </p>
                <div class="space-y-1.5">
                    @foreach($allThresholds as $days => $label)
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                        <button wire:click="toggleThreshold({{ $days }})" type="button"
                                class="w-4 h-4 rounded border transition-colors flex items-center justify-center shrink-0
                                       {{ in_array($days, $thresholds) ? 'bg-salvia border-salvia' : 'border-paper-dark bg-white group-hover:border-salvia/50' }}">
                            @if(in_array($days, $thresholds))
                            <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            @endif
                        </button>
                        <span class="text-sm text-ink">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Mnemosyne --}}
            <div>
                <label class="block text-xs text-ink/50 mb-2">Fili che si raffreddano</label>
                <label class="flex items-center gap-2.5 cursor-pointer group">
                    <button wire:click="$toggle('withMnemosyne')" type="button"
                            class="w-4 h-4 rounded border transition-colors flex items-center justify-center shrink-0
                                   {{ $withMnemosyne ? 'bg-salvia border-salvia' : 'border-paper-dark bg-white group-hover:border-salvia/50' }}">
                        @if($withMnemosyne)
                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        @endif
                    </button>
                    <span class="text-sm text-ink">Includi i dormienti da Mnemosyne</span>
                </label>
            </div>
            @endif
        </div>

        {{-- Salva --}}
        <div class="flex items-center gap-3">
            <button wire:click="save"
                    class="px-5 py-2 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors">
                Salva
            </button>
            @if($savedMessage)
            <span class="text-sm text-salvia" wire:key="saved-msg">{{ $savedMessage }}</span>
            @endif
        </div>

        {{-- Ultimo invio --}}
        @if($lastLog)
        <div class="text-xs text-ink/40 pt-2">
            Ultimo invio: {{ $lastLog->sent_at->diffForHumans() }}
            ({{ $lastLog->payload['tasks_count'] ?? 0 }} scadenze,
            {{ $lastLog->payload['dormant_count'] ?? 0 }} dormienti)
        </div>
        @endif
    </div>
</div>
