@props(['fallback' => null])
{{-- Torna indietro uniforme: con wire:navigate attivo e' un back SPA istantaneo
     che riporta alla pagina di provenienza. Se non c'e' history (apertura diretta
     del link), naviga al fallback (default: cruscotto). --}}
<button type="button"
        x-data
        @click="window.history.length > 1 ? window.history.back() : Livewire.navigate(@js($fallback ?? route('cruscotto')))"
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-sm text-ink/50 hover:text-salvia transition-colors']) }}>
    ← {{ $slot->isEmpty() ? 'Indietro' : $slot }}
</button>
