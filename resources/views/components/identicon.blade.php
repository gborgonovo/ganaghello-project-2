@props(['user' => null, 'size' => 'w-7 h-7 text-xs'])
{{-- Rappresentazione dell'utente: l'identicon scelto (iniziali o emoji) oppure,
     in mancanza, le prime due lettere del nome. Niente avatar immagine. --}}
@php
    $label = $user?->identicon ?: mb_strtoupper(mb_substr($user?->name ?? '?', 0, 2));
@endphp
<span {{ $attributes->merge(['class' => $size . ' rounded-full bg-salvia flex items-center justify-center font-semibold text-paper shrink-0']) }}
      title="{{ $user?->name }}">{{ $label }}</span>
