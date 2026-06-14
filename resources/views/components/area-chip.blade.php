@props(['area'])
{{-- Chip dell'area: sfondo = color, testo = text_color. Coerente con i badge
     degli stage. Fallback su toni neutri se i colori non sono impostati. --}}
@if($area)
<span {{ $attributes->merge(['class' => 'inline-flex items-center max-w-full text-xs px-1.5 py-0.5 rounded font-medium']) }}
      style="background-color: {{ $area->chip_bg_color }}; color: {{ $area->chip_text_color }}">
    <span class="truncate">{{ $area->name }}</span>
</span>
@endif
