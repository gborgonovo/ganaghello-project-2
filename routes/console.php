<?php

use App\Jobs\SendDailyDigest;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Digest giornaliero: ora configurabile via impostazioni (default 07:30).
// Usa last_sent_date invece del confronto esatto sul minuto: se il worker
// è giù al momento esatto, il digest parte al primo tick successivo.
Schedule::call(function () {
    $hour   = (int) Setting::get('digest.hour', 7);
    $minute = (int) Setting::get('digest.minute', 30);
    $today  = now()->toDateString();

    if (Setting::get('digest.last_sent_date') === $today) {
        return;
    }

    $targetTime = now()->copy()->setHour($hour)->setMinute($minute)->setSecond(0);
    if (now()->lt($targetTime)) {
        return;
    }

    // `digest.last_sent_date` lo scrive il job, e solo a invio riuscito: cosi' un
    // fallimento viene ritentato invece di bruciare la giornata. Questo lock evita
    // di riaccodare il job a ogni minuto mentre e' in coda o in esecuzione.
    $lockedAt = Setting::get('digest.dispatched_at');
    if ($lockedAt && now()->lt(Carbon::parse($lockedAt)->addMinutes(15))) {
        return;
    }
    Setting::set('digest.dispatched_at', now()->toDateTimeString());

    SendDailyDigest::dispatch();
})->everyMinute()->name('daily-digest')->withoutOverlapping();

Artisan::command('digest:send', function () {
    $this->info('Invio digest manuale...');
    (new SendDailyDigest())->handle(app(\App\Services\MnemosyneService::class));
    $this->info('Fatto.');
})->purpose('Invia il digest manualmente (test)');
