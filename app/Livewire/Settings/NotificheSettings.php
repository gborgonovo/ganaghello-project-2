<?php

namespace App\Livewire\Settings;

use App\Models\NotificationLog;
use App\Models\Setting;
use Livewire\Component;

class NotificheSettings extends Component
{
    public bool   $enabled          = true;
    public int    $hour             = 7;
    public int    $minute           = 30;
    public bool   $withMnemosyne    = true;
    public array  $thresholds       = [0, 1, 3, 7, 30];

    public ?string $savedMessage = null;

    private const ALL_THRESHOLDS = [
        0  => 'Il giorno stesso',
        1  => 'Il giorno prima',
        3  => '3 giorni prima',
        7  => '7 giorni prima',
        30 => '30 giorni prima',
    ];

    public function mount(): void
    {
        $this->enabled       = Setting::get('digest.enabled', '1') === '1';
        $this->hour          = (int) Setting::get('digest.hour', 7);
        $this->minute        = (int) Setting::get('digest.minute', 30);
        $this->withMnemosyne = Setting::get('digest.mnemosyne', '1') === '1';
        $this->thresholds    = json_decode(Setting::get('digest.thresholds', json_encode([0, 1, 3, 7, 30])), true);
    }

    public function save(): void
    {
        $this->validate([
            'hour'   => 'required|integer|between:0,23',
            'minute' => 'required|integer|in:0,30',
        ]);

        Setting::set('digest.enabled',    $this->enabled    ? '1' : '0');
        Setting::set('digest.hour',       (string) $this->hour);
        Setting::set('digest.minute',     (string) $this->minute);
        Setting::set('digest.mnemosyne',  $this->withMnemosyne ? '1' : '0');
        Setting::set('digest.thresholds', json_encode(array_values($this->thresholds)));

        $this->savedMessage = 'Preferenze salvate.';
        $this->dispatch('toast', message: 'Impostazioni salvate.');
    }

    public function toggleThreshold(int $days): void
    {
        if (in_array($days, $this->thresholds)) {
            $this->thresholds = array_values(array_filter($this->thresholds, fn($d) => $d !== $days));
        } else {
            $this->thresholds[] = $days;
            sort($this->thresholds);
        }
        $this->savedMessage = null;
    }

    public function render()
    {
        $lastLog = NotificationLog::where('type', 'digest')
            ->orderByDesc('sent_at')
            ->first();

        return view('livewire.settings.notifiche', [
            'allThresholds' => self::ALL_THRESHOLDS,
            'lastLog'       => $lastLog,
        ])->layout('layouts.app', ['title' => 'Impostazioni notifiche']);
    }
}
