{{-- Voce di solo testo corto: post-it giallo. Testo breve, niente fronzoli. --}}
@php $time = $entry->entry_time ? substr($entry->entry_time, 0, 5) : null; @endphp
<a href="{{ $url }}" wire:navigate
   class="block bg-postit hover:bg-postit-dark transition-colors shadow-lg shadow-ink/15 px-4 py-3">
    @if($entry->title)
    <div class="font-narrative font-semibold text-ink leading-snug mb-1">{{ $entry->title }}</div>
    @endif
    <p class="text-sm font-narrative text-ink/80 leading-relaxed whitespace-pre-line line-clamp-6">{{ \Illuminate\Support\Str::limit($entry->content, 240) }}</p>
    <div class="flex items-center justify-between gap-2 mt-2.5">
        <span class="font-hand text-base text-ink/55">
            {{ $entry->entry_date->isoFormat('D MMM') }}@if($time) · {{ $time }}@endif
        </span>
        @if($entry->area)<x-area-chip :area="$entry->area" />@endif
    </div>
</a>
