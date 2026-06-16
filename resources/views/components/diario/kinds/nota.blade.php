{{-- Voce di testo lungo: foglietto di carta sobrio. Click -> apre la lettura in modale.
     Mostra piu' testo del post-it; una foto eventuale (caso override) compare in alto. --}}
@php $time = $entry->entry_time ? substr($entry->entry_time, 0, 5) : null; @endphp
<div wire:click="openRead({{ $entry->id }})"
     class="bg-white shadow-lg shadow-ink/15 border border-paper-dark/60 border-l-4 border-l-salvia/40 px-4 py-3.5 cursor-pointer">
    @if($cover)
    <div class="mb-2.5 -mx-4 -mt-3.5 bg-paper-dark overflow-hidden">
        <img src="{{ route('media.serve', [$cover, 'medium']) }}" alt="" class="w-full object-cover max-h-44">
    </div>
    @endif
    <span class="font-hand text-base text-ink/55">
        {{ $entry->entry_date->isoFormat('D MMM') }}@if($time) · {{ $time }}@endif
    </span>
    @if($entry->title)
    <div class="font-narrative text-lg font-semibold text-ink leading-snug mt-0.5">{{ $entry->title }}</div>
    @endif
    <div class="entry-prose text-sm font-narrative text-ink/75 leading-relaxed mt-1.5 line-clamp-[14]">
        {!! \Illuminate\Support\Str::markdown($entry->content, ['html_input' => 'escape']) !!}
    </div>
    <div class="flex items-center justify-between gap-2 mt-2">
        <span class="text-xs text-salvia">continua a leggere →</span>
        @if($entry->area)<x-area-chip :area="$entry->area" />@endif
    </div>
</div>
