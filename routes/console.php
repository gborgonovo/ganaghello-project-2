<?php

use App\Jobs\SendDailyDigest;
use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Digest giornaliero: ora configurabile via impostazioni (default 07:30)
Schedule::call(function () {
    $hour   = (int) Setting::get('digest.hour', 7);
    $minute = (int) Setting::get('digest.minute', 30);
    if (now()->hour === $hour && now()->minute === $minute) {
        SendDailyDigest::dispatch();
    }
})->everyMinute()->name('daily-digest')->withoutOverlapping();

Artisan::command('digest:send', function () {
    $this->info('Invio digest manuale...');
    (new SendDailyDigest())->handle(app(\App\Services\MnemosyneService::class));
    $this->info('Fatto.');
})->purpose('Invia il digest manualmente (test)');
