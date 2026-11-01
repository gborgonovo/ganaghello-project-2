{{-- Segnaposto di una polaroid ancora senza foto (voce dettata con la foto in arrivo).
     Con $link mostra anche il collegamento all'editor dove caricarla. Usa solo classi già compilate. --}}
@props(['entry', 'link' => false])
<div {{ $attributes->merge(['class' => 'border-2 border-dashed border-paper-dark bg-paper flex flex-col items-center justify-center gap-1 py-10']) }}>
    <span class="font-hand text-lg text-ink/40">foto da aggiungere</span>
    @if($link)
    <a href="{{ route('diario.edit', $entry->id) }}" wire:navigate
       class="text-sm font-medium text-salvia hover:text-salvia underline">Aggiungi la foto</a>
    @endif
</div>
