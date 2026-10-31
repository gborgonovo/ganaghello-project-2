<?php

namespace App\Livewire\Settings;

use App\Models\NotificationLog;
use App\Models\RegistroScrittura;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Blocco 10.1: finestra di amministrazione/log.
 * Mostra i job falliti (failed_jobs) e lo storico delle notifiche inviate
 * (notification_logs). Sola lettura, più la pulizia dei job falliti.
 *
 * Accesso: come tutte le impostazioni, dietro il solo middleware auth. Oggi il
 * team è fidato (scelta registrata nel piano); quando i ruoli admin saranno
 * attivi questa sezione è la prima da riservare a User::isAdmin().
 */
class Log extends Component
{
    public string $portaScritture  = '';
    public string $entitaScritture = '';

    public function clearFailedJobs(): void
    {
        DB::table('failed_jobs')->delete();
        $this->dispatch('toast', message: 'Job falliti eliminati.');
    }

    public function render()
    {
        $failed = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(50)
            ->get();

        $logs = NotificationLog::with(['user', 'task'])
            ->orderByDesc('sent_at')
            ->limit(50)
            ->get();

        $scritture = RegistroScrittura::with('user')
            ->when($this->portaScritture, fn ($q) => $q->where('porta', $this->portaScritture))
            ->when($this->entitaScritture, fn ($q) => $q->where('entita', $this->entitaScritture))
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('livewire.settings.log', [
            'scritture'      => $scritture,
            'entitaScritte'  => RegistroScrittura::distinct()->orderBy('entita')->pluck('entita'),
            'failed'      => $failed,
            'failedCount' => DB::table('failed_jobs')->count(),
            'logs'        => $logs,
        ])->layout('layouts.app', ['title' => 'Log']);
    }
}
