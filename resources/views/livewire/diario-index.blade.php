<div>
<div class="max-w-5xl mx-auto pb-12">

    {{-- ===== TOOLBAR ===== --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">

        {{-- Filtro area --}}
        <div class="flex items-center gap-1.5 flex-wrap">
            <button wire:click="$set('filterAreaId', null)"
                    class="text-xs px-3 py-1 rounded-full transition-colors
                           {{ is_null($filterAreaId) ? 'bg-salvia text-white' : 'text-ink/50 hover:text-ink border border-paper-dark' }}">
                Tutto
            </button>
            @foreach($aree as $area)
            <button wire:click="$set('filterAreaId', {{ $area->id }})"
                    class="text-xs px-3 py-1 rounded-full transition-colors flex items-center gap-1.5
                           {{ $filterAreaId === $area->id ? 'bg-salvia text-white' : 'text-ink/50 hover:text-ink border border-paper-dark' }}">
                @if($area->color)
                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $area->color }}"></span>
                @endif
                {{ $area->name }}
            </button>
            @endforeach
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <input wire:model.live.debounce.300ms="search"
                   type="text"
                   placeholder="Cerca nel diario..."
                   class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                          focus:outline-none focus:border-salvia w-44 bg-white">

            @if($search || $filterAreaId)
            <button wire:click="$set('search', ''); $set('filterAreaId', null)"
                    class="text-xs text-terracotta hover:text-ink transition-colors">
                Azzera
            </button>
            @endif

            <button wire:click="toggleSelecting"
                    class="px-3 py-2 rounded-lg text-sm border transition-colors
                           {{ $selecting ? 'border-salvia text-salvia bg-salvia/5' : 'border-paper-dark text-ink/60 hover:text-ink' }}">
                ✚ Componi un post
            </button>

            <a href="{{ route('diario.create') }}" wire:navigate
                    class="flex items-center gap-2 px-4 py-2 bg-salvia text-white rounded-lg text-sm
                           hover:bg-salvia-dark transition-colors">
                + Nuova pagina
            </a>
        </div>
    </div>

    {{-- ===== BARRA AZIONE SELEZIONE ===== --}}
    @if($selecting)
    <div class="sticky top-0 z-20 flex items-center justify-between gap-3 mb-5 px-4 py-2.5
                bg-salvia text-white rounded-xl shadow-sm">
        <span class="text-sm">
            {{ count($selectedIds) }} {{ count($selectedIds) === 1 ? 'voce selezionata' : 'voci selezionate' }}
        </span>
        <div class="flex items-center gap-2">
            <button wire:click="composePost"
                    @disabled(count($selectedIds) === 0)
                    class="px-4 py-1.5 bg-white text-salvia rounded-lg text-sm font-medium
                           hover:bg-paper transition-colors disabled:opacity-50">
                Componi ({{ count($selectedIds) }})
            </button>
            <button wire:click="toggleSelecting"
                    class="px-3 py-1.5 text-white/80 hover:text-white text-sm transition-colors">
                Annulla
            </button>
        </div>
    </div>
    @endif

    {{-- ===== SCRAPBOOK (masonry per mese) ===== --}}
    @forelse($byMonth as $ym => $monthEntries)
    @php $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $ym)->startOfMonth(); @endphp

    <div class="mb-10" wire:key="month-{{ $ym }}">

        {{-- Separatore mese, scritto a mano --}}
        <div class="flex items-center gap-4 mb-5">
            <div class="flex-1 border-t border-paper-dark"></div>
            <span class="font-hand text-2xl text-ink/55 shrink-0">{{ ucfirst($monthDate->isoFormat('MMMM YYYY')) }}</span>
            <div class="flex-1 border-t border-paper-dark"></div>
        </div>

        {{-- Muro di voci: polaroid / post-it / nota, ognuna col suo giorno --}}
        <div class="columns-1 sm:columns-2 lg:columns-3 gap-3">
            @foreach($monthEntries as $entry)
            <x-diario.entry :entry="$entry"
                            :selecting="$selecting"
                            :isSelected="in_array($entry->id, $selectedIds)" />
            @endforeach
        </div>
    </div>

    @empty
    <div class="rounded-xl border border-dashed border-paper-dark px-6 py-12 text-center">
        <p class="text-ink/30 text-sm italic">Il diario è vuoto. Scrivi la prima pagina.</p>
    </div>
    @endforelse

    {{-- Carica altri --}}
    @if($hasMore)
    <div class="text-center mt-4">
        <button wire:click="loadMore"
                class="text-sm text-salvia hover:text-salvia-dark transition-colors">
            Carica altri...
        </button>
    </div>
    @endif

</div>{{-- fine max-w-2xl --}}

{{-- ===== MODALE DI LETTURA (la modifica avviene nella pagina dedicata) ===== --}}
@if($readEntry)
@php $rt = $readEntry->entry_time ? substr($readEntry->entry_time, 0, 5) : null; @endphp
<div class="fixed inset-0 z-50 bg-ink/70 flex items-start sm:items-center justify-center p-0 sm:p-6 overflow-y-auto"
     wire:click.self="closeRead"
     x-data="{
        modalW: null,
        fit(img) {
            // Larghezza che l'immagine assume rispettando il tetto d'altezza (85vh):
            // ci adattiamo a quella, cosi' la foto riempie la modale senza bande.
            const capH = window.innerHeight * 0.85;
            const w = Math.min(img.naturalWidth, capH * img.naturalWidth / img.naturalHeight);
            this.modalW = Math.min(672, Math.round(w)); // 672 = max-w-2xl, tetto per le orizzontali
        }
     }"
     x-on:keydown.escape.window="$wire.closeRead()">

    {{-- Larghezza adattiva: la modale combacia con la larghezza reale della foto
         (verticale o orizzontale); senza foto resta larga (max-w-2xl). --}}
    <div class="relative w-full bg-paper sm:rounded-2xl shadow-2xl my-0 sm:my-auto overflow-hidden
                transition-[max-width] duration-200"
         :class="modalW ? '' : 'max-w-2xl'"
         :style="modalW ? `max-width:${modalW}px` : ''">

        {{-- Chiudi --}}
        <button wire:click="closeRead"
                class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-ink/40 text-white
                       flex items-center justify-center text-sm hover:bg-ink transition-colors">✕</button>

        {{-- Foto --}}
        @php $cover = $readEntry->attachments->first()?->media; @endphp
        @if($cover)
        <img src="{{ route('media.serve', [$cover, 'medium']) }}" alt=""
             x-on:load="fit($event.target)"
             class="block w-full h-auto max-h-[86vh] object-contain bg-paper-dark">
        @endif

        <div class="px-6 py-5">

            {{-- Meta: data a mano + ora --}}
            <div class="flex items-center gap-2 text-ink/50 mb-1">
                <span class="font-hand text-xl">{{ $readEntry->entry_date->isoFormat('D MMMM YYYY') }}</span>
                @if($rt)<span class="text-xs font-mono">· {{ $rt }}</span>@endif
            </div>

            {{-- Titolo --}}
            @if($readEntry->title)
            <h2 class="font-narrative text-2xl font-semibold text-ink leading-snug mb-2">{{ $readEntry->title }}</h2>
            @endif

            {{-- Area + tag --}}
            <div class="flex flex-wrap items-center gap-1.5 mb-4">
                @if($readEntry->area)<x-area-chip :area="$readEntry->area" />@endif
                @foreach($readEntry->tags as $tag)
                <span class="text-[11px] text-ink/45 bg-paper-dark rounded-full px-2 py-0.5">#{{ $tag->name }}</span>
                @endforeach
            </div>

            {{-- Testo completo --}}
            <div class="entry-prose font-narrative text-ink/80 leading-relaxed">
                {!! \Illuminate\Support\Str::markdown($readEntry->content, ['html_input' => 'escape']) !!}
            </div>
        </div>

        {{-- Azioni --}}
        <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-paper-dark bg-paper/60">
            <x-confirm action="deleteEntry({{ $readEntry->id }})" title="Elimina questa voce"
                    class="text-sm text-ink/45 hover:text-terracotta transition-colors">
                Elimina
            </x-confirm>
            <a href="{{ route('diario.edit', $readEntry->id) }}" wire:navigate
               class="px-5 py-2 bg-salvia text-white rounded-lg text-sm hover:bg-salvia-dark transition-colors">
                ✎ Modifica
            </a>
        </div>
    </div>
</div>
@endif
</div>{{-- fine root wrapper --}}
