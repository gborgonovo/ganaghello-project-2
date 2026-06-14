@props(['action', 'message' => 'Eliminare?', 'tone' => 'light'])
{{-- Conferma inline (niente dialog nativo del browser): al primo click mostra
     "messaggio Si/No". Per cancellazioni che riescono sempre. Le cancellazioni
     che possono fallire con un motivo usano invece il confirm con stato del
     componente (confirmDeleteId), che mostra l'errore. --}}
@php
    $msgClass = $tone === 'dark' ? 'text-white/70' : 'text-ink/50';
    $yesClass = $tone === 'dark' ? 'text-white hover:text-terracotta' : 'text-terracotta hover:underline';
    $noClass  = $tone === 'dark' ? 'text-white/50 hover:text-white' : 'text-ink/40 hover:underline';
@endphp
<span x-data="{ confirming: false }" class="inline-flex items-center">
    <button type="button" x-show="!confirming" @click="confirming = true" {{ $attributes }}>{{ $slot }}</button>
    <span x-show="confirming" style="display:none"
          class="inline-flex items-center gap-1.5 text-[11px] whitespace-nowrap">
        <span class="{{ $msgClass }}">{{ $message }}</span>
        <button type="button" wire:click="{{ $action }}" class="{{ $yesClass }} font-medium">Sì</button>
        <button type="button" @click="confirming = false" class="{{ $noClass }}">No</button>
    </span>
</span>
