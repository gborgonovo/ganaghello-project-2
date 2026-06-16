@props(['entry', 'selecting' => false, 'isSelected' => false])
{{-- Voce del diario nello scrapbook. Sceglie il renderer in base a `kind`
     (polaroid/postit/nota, estensibile). Rotazione fissa dedotta dall'id, cosi
     non balla a ogni render. Gestisce qui la cornice comune: selezione (modalita
     componi) e azioni hover (modifica/elimina). --}}
@php
    $kind  = in_array($entry->kind, \App\Models\Entry::KINDS, true) ? $entry->kind : 'postit';
    $rot   = round(($entry->id * 37 % 101) / 10 - 5, 1); // -5.0 .. +5.0, deterministico
    $cover = $entry->attachments->first()?->media;
@endphp
<div class="break-inside-avoid mb-5 px-1.5" wire:key="entry-{{ $entry->id }}">
    <div class="relative group" style="transform: rotate({{ $rot }}deg)">

        @if($selecting)
        {{-- Spunta di selezione --}}
        <button type="button" wire:click="toggleSelect({{ $entry->id }})"
                class="absolute -top-2 -left-2 z-20 w-7 h-7 rounded-full border-2 shadow flex items-center justify-center text-xs transition-colors
                       {{ $isSelected ? 'bg-salvia border-salvia text-white' : 'bg-white border-paper-dark text-transparent hover:border-salvia' }}">
            ✓
        </button>
        @else
        {{-- Azioni (compaiono al passaggio del mouse) --}}
        <div class="absolute -top-2 -right-2 z-20 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <a href="{{ route('diario.edit', $entry->id) }}" wire:navigate title="Modifica questa voce"
                    class="w-7 h-7 rounded-full bg-white shadow border border-paper-dark text-ink/50 hover:text-salvia text-xs flex items-center justify-center">✎</a>
            <x-confirm action="deleteEntry({{ $entry->id }})" title="Elimina questa voce"
                    class="w-7 h-7 rounded-full bg-white shadow border border-paper-dark text-ink/40 hover:text-terracotta text-xs flex items-center justify-center">✕</x-confirm>
        </div>
        @endif

        <div @class(['rounded ring-2 ring-salvia ring-offset-2 ring-offset-paper' => $selecting && $isSelected])>
            @include('components.diario.kinds.'.$kind, ['entry' => $entry, 'cover' => $cover])
        </div>
    </div>
</div>
