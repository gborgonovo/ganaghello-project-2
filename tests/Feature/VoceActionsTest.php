<?php

namespace Tests\Feature;

use App\Actions\TaskActions;
use App\Actions\VoceActions;
use App\Jobs\SyncEntryToMnemosyne;
use App\Models\Entry;
use App\Models\RegistroScrittura;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Le regole delle voci decise il 2026-11-01 (esecutivo, "Decisioni sulle voci dettate"). */
class VoceActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $u;
    private VoceActions $a;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-01 16:05:00', 'Europe/Rome'));
        config(['services.mnemosyne.sync' => false]);
        $this->u = User::factory()->create();
        $this->a = app(VoceActions::class);
    }

    public function test_senza_data_e_oggi_con_l_ora_attuale(): void
    {
        $v = $this->a->crea($this->u, ['content' => 'Spazzato il fienile.']);

        $this->assertSame('2026-11-01', $v->entry_date->toDateString());
        $this->assertSame('16:05', substr($v->entry_time, 0, 5));
        $this->assertSame('postit', $v->kind);
        $this->assertNull($v->title);
    }

    public function test_un_altro_giorno_senza_ora_lascia_l_ora_vuota(): void
    {
        $v = $this->a->crea($this->u, ['content' => 'Ieri abbiamo potato.', 'entry_date' => '2026-10-31']);

        $this->assertSame('2026-10-31', $v->entry_date->toDateString());
        $this->assertNull($v->entry_time);
    }

    public function test_con_la_foto_in_arrivo_nasce_polaroid_anche_con_testo_lungo(): void
    {
        $v = $this->a->crea($this->u, ['content' => str_repeat('parole ', 80)], conFoto: true);

        $this->assertSame('polaroid', $v->kind);
    }

    public function test_senza_foto_un_testo_lungo_e_una_nota(): void
    {
        $this->assertSame('nota', $this->a->crea($this->u, ['content' => str_repeat('parole ', 80)])->kind);
    }

    public function test_testo_vuoto_e_un_errore(): void
    {
        $this->expectException(ValidationException::class);
        $this->a->crea($this->u, ['content' => '   ', 'title' => 'Solo titolo']);
    }

    public function test_modifica_parziale_lascia_il_resto_e_registra(): void
    {
        $v = $this->a->crea($this->u, ['content' => 'Primo testo', 'title' => 'Titolo', 'entry_date' => '2026-10-20']);

        $this->a->modifica($this->u, $v, ['content' => 'Testo corretto']);

        $v->refresh();
        $this->assertSame('Testo corretto', $v->content);
        $this->assertSame('Titolo', $v->title);
        $this->assertSame('2026-10-20', $v->entry_date->toDateString());
        $this->assertSame(['Primo testo', 'Testo corretto'], RegistroScrittura::where('azione', 'modifica')->first()->modifiche['content']);
    }

    public function test_eliminare_e_ripristinare_la_voce(): void
    {
        $v = $this->a->crea($this->u, ['content' => 'x']);

        $this->a->elimina($this->u, $v);
        $this->assertSoftDeleted('entries', ['id' => $v->id]);

        $this->a->ripristina($this->u, Entry::withTrashed()->find($v->id));
        $this->assertNotSoftDeleted('entries', ['id' => $v->id]);
    }

    public function test_collegare_una_voce_da_un_task_risincronizza_la_voce(): void
    {
        $stage = Stage::create(['code' => 'idea', 'label' => 'Idea', 'sequence' => 1]);
        $task  = Task::create(['user_id' => $this->u->id, 'title' => 'Tetto', 'stage_id' => $stage->id]);
        $v     = $this->a->crea($this->u, ['content' => 'x']);
        config(['services.mnemosyne.sync' => true]);
        Queue::fake();

        app(TaskActions::class)->collegaVoci($this->u, $task, [$v->id]);

        Queue::assertPushed(SyncEntryToMnemosyne::class, fn ($job) => $job->entryId === $v->id);
    }
}
