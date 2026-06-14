<?php

namespace Tests\Feature;

use App\Livewire\Settings\Log;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Blocco 10.1: la finestra Log mostra job falliti e notifiche inviate, e
 * permette di svuotare i job falliti.
 */
class SettingsLogTest extends TestCase
{
    use RefreshDatabase;

    private function fakeFailedJob(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid'       => (string) Str::uuid(),
            'connection' => 'database',
            'queue'      => 'default',
            'payload'    => '{}',
            'exception'  => 'RuntimeException: qualcosa e andato storto',
            'failed_at'  => now(),
        ]);
    }

    public function test_log_mostra_job_falliti_e_notifiche(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->fakeFailedJob();
        NotificationLog::create(['user_id' => $user->id, 'type' => 'digest', 'sent_at' => now()]);

        Livewire::test(Log::class)
            ->assertOk()
            ->assertSee('qualcosa e andato storto')
            ->assertSee('digest');
    }

    public function test_svuota_job_falliti(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->fakeFailedJob();

        Livewire::test(Log::class)
            ->call('clearFailedJobs')
            ->assertDispatched('toast');

        $this->assertSame(0, DB::table('failed_jobs')->count());
    }
}
