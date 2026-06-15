{{-- Voce con foto: polaroid. Cornice bianca, foto in alto, didascalia sotto
     (data a mano, titolo, breve testo). Le foto verticali respirano (niente crop quadrato). --}}
@php $time = $entry->entry_time ? substr($entry->entry_time, 0, 5) : null; @endphp
<div class="bg-white p-2 pb-1 shadow-lg shadow-ink/20 border border-black/5">
    @if($cover)
    <a href="{{ $url }}" wire:navigate class="block bg-paper-dark overflow-hidden">
        <img src="{{ route('media.serve', [$cover, 'medium']) }}" alt="" class="w-full object-cover max-h-80">
    </a>
    @endif
    <div class="px-1.5 pt-2 pb-1">
        <span class="font-hand text-lg leading-none text-ink/55">
            {{ $entry->entry_date->isoFormat('D MMM') }}@if($time) · {{ $time }}@endif
        </span>
        @if($entry->title)
        <a href="{{ $url }}" wire:navigate
           class="block font-narrative text-ink font-medium leading-snug mt-1 hover:text-salvia transition-colors">
            {{ $entry->title }}
        </a>
        @endif
        @if(trim($entry->content) !== '')
        <p class="text-sm font-narrative text-ink/70 leading-snug mt-0.5 line-clamp-3">
            {{ \Illuminate\Support\Str::limit(strip_tags(\Illuminate\Support\Str::markdown($entry->content)), 140) }}
        </p>
        @endif
        @if($entry->area)
        <div class="mt-1.5"><x-area-chip :area="$entry->area" /></div>
        @endif
    </div>
</div>
