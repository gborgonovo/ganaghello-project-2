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
           class="block font-narrative text-lg text-ink font-medium leading-snug mt-1 hover:text-salvia transition-colors">
            {{ $entry->title }}
        </a>
        @endif
        <div class="flex items-center justify-between gap-2 mt-1.5">
            @if($entry->area)<x-area-chip :area="$entry->area" />@else<span></span>@endif
            @if(trim($entry->content) !== '')
            {{-- C'è del testo: piccolo segno, si legge aprendo --}}
            <span title="Apri per leggere" class="shrink-0 text-ink/35">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" class="w-3.5 h-3.5">
                    <path d="M3 4.5h10M3 8h10M3 11.5h6" />
                </svg>
            </span>
            @endif
        </div>
    </div>
</div>
