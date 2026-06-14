<div class="flex flex-col sm:flex-row gap-6 sm:gap-8 max-w-4xl mx-auto">
    <x-settings-nav />

    <div class="flex-1 space-y-8">

        {{-- ===== Job falliti ===== --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h1 class="text-base font-semibold text-ink">Job falliti</h1>
                @if($failedCount > 0)
                <x-confirm action="clearFailedJobs"
                    class="text-xs text-ink/40 hover:text-terracotta transition-colors">
                    Svuota ({{ $failedCount }})
                </x-confirm>
                @endif
            </div>

            @forelse($failed as $job)
            <div wire:key="fail-{{ $job->id }}" class="bg-white rounded-xl border border-paper-dark px-4 py-3 mb-2">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <span class="text-xs font-medium text-ink">coda: {{ $job->queue }}</span>
                    <span class="text-xs text-ink/40">{{ \Illuminate\Support\Carbon::parse($job->failed_at)->format('d/m/Y H:i') }}</span>
                </div>
                <p class="text-[11px] text-terracotta/90 font-mono leading-snug line-clamp-2">
                    {{ \Illuminate\Support\Str::limit(strtok($job->exception, "\n"), 200) }}
                </p>
            </div>
            @empty
            <div class="bg-white rounded-xl border border-paper-dark px-4 py-8 text-center text-sm text-ink/30 italic">
                Nessun job fallito. Tutto liscio.
            </div>
            @endforelse
        </div>

        {{-- ===== Storico notifiche ===== --}}
        <div>
            <h2 class="text-base font-semibold text-ink mb-3">Notifiche inviate</h2>

            <div class="bg-white rounded-xl border border-paper-dark divide-y divide-paper-dark">
                @forelse($logs as $log)
                <div wire:key="log-{{ $log->id }}" class="flex items-center gap-3 px-4 py-2.5">
                    <span class="text-xs px-1.5 py-0.5 rounded bg-paper-dark text-ink/60 shrink-0">{{ $log->type }}</span>
                    <span class="text-sm text-ink/70 flex-1 min-w-0 truncate">
                        @if($log->user){{ $log->user->name }}@endif
                        @if($log->task) · {{ \Illuminate\Support\Str::limit($log->task->title, 40) }}@endif
                    </span>
                    <span class="text-xs text-ink/40 shrink-0">{{ $log->sent_at?->format('d/m/Y H:i') }}</span>
                </div>
                @empty
                <div class="px-4 py-8 text-center text-sm text-ink/30 italic">Nessuna notifica inviata ancora.</div>
                @endforelse
            </div>
            <p class="text-[11px] text-ink/30 mt-2">Ultimi 50 record. Serve per capire a colpo d'occhio se i digest partono.</p>
        </div>

    </div>
</div>
