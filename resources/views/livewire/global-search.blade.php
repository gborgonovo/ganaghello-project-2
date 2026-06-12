<div x-data @open-global-search.window="$wire.openSearch()">
    @if($isOpen)
    <div class="fixed inset-0 z-[100] flex items-start justify-center pt-[15vh] px-4 bg-ink/70"
         wire:click.self="closeSearch"
         @keydown.escape.window="$wire.closeSearch()">

        <div class="w-full max-w-xl bg-paper rounded-2xl shadow-2xl overflow-hidden"
             x-data x-init="$nextTick(() => $el.querySelector('input[type=text]')?.focus())">

            {{-- Input --}}
            <div class="flex items-center gap-3 px-4 py-3.5 border-b border-paper-dark">
                <span class="text-ink/25 text-base shrink-0">⌕</span>
                <input type="text"
                       wire:model.live.debounce.200ms="query"
                       placeholder="Cerca task, diario, note..."
                       class="flex-1 bg-transparent text-sm text-ink placeholder:text-ink/30 focus:outline-none">
                <kbd class="text-[10px] text-ink/30 border border-paper-dark rounded px-1.5 py-0.5 shrink-0 font-sans">ESC</kbd>
            </div>

            {{-- Risultati --}}
            @if(strlen(trim($query)) >= 2)
            <div class="max-h-[55vh] overflow-y-auto">
                @forelse($results as $group => $items)
                <div>
                    <p class="px-4 pt-3 pb-1 text-[10px] font-semibold text-ink/30 uppercase tracking-widest">{{ $group }}</p>
                    @foreach($items as $item)
                    @if($item['url'])
                    <a href="{{ $item['url'] }}"
                       class="flex items-center gap-3 px-4 py-2.5 hover:bg-paper-dark transition-colors group">
                        <span class="text-sm text-ink/25 shrink-0 w-5 text-center group-hover:text-salvia transition-colors">{{ $item['icon'] }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-ink truncate">{{ $item['label'] }}</p>
                            @if($item['sub'])
                            <p class="text-xs text-ink/40">{{ $item['sub'] }}</p>
                            @endif
                        </div>
                        <span class="text-xs text-ink/20 group-hover:text-salvia transition-colors shrink-0">→</span>
                    </a>
                    @else
                    <div class="flex items-center gap-3 px-4 py-2.5 opacity-40 cursor-default">
                        <span class="text-sm text-ink/25 shrink-0 w-5 text-center">{{ $item['icon'] }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-ink truncate">{{ $item['label'] }}</p>
                            @if($item['sub'])
                            <p class="text-xs text-ink/40">{{ $item['sub'] }}</p>
                            @endif
                        </div>
                        <span class="text-[10px] text-ink/30 italic shrink-0">blog non attivo</span>
                    </div>
                    @endif
                    @endforeach
                </div>
                @empty
                <div class="px-4 py-10 text-center">
                    <p class="text-sm text-ink/30">Nessun risultato per "<span class="text-ink/50">{{ $query }}</span>"</p>
                </div>
                @endforelse
            </div>
            @else
            <div class="px-4 py-6 text-center text-xs text-ink/25">Digita almeno 2 caratteri</div>
            @endif

        </div>
    </div>
    @endif
</div>
