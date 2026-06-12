<?php

namespace Tests\Feature;

use App\Jobs\SyncTaskToMnemosyne;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use App\Services\MnemosyneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MnemosyneSyncJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_calls_push_task_when_enabled(): void
    {
        $stage = Stage::create(['code' => 'in_corso', 'label' => 'In corso']);
        $user  = User::factory()->create();
        $task  = Task::create(['user_id' => $user->id, 'title' => 'Test task', 'stage_id' => $stage->id]);

        $mnemosyne = $this->mock(MnemosyneService::class);
        $mnemosyne->shouldReceive('enabled')->once()->andReturn(true);
        $mnemosyne->shouldReceive('pushTask')->once()->andReturn(['name' => 'test-task']);

        (new SyncTaskToMnemosyne($task->id))->handle($mnemosyne);
    }

    public function test_sync_skips_push_when_disabled(): void
    {
        $stage = Stage::create(['code' => 'in_corso', 'label' => 'In corso']);
        $user  = User::factory()->create();
        $task  = Task::create(['user_id' => $user->id, 'title' => 'Test task', 'stage_id' => $stage->id]);

        $mnemosyne = $this->mock(MnemosyneService::class);
        $mnemosyne->shouldReceive('enabled')->once()->andReturn(false);
        $mnemosyne->shouldNotReceive('pushTask');

        (new SyncTaskToMnemosyne($task->id))->handle($mnemosyne);
    }

    public function test_sync_skips_gracefully_for_missing_task(): void
    {
        $mnemosyne = $this->mock(MnemosyneService::class);
        $mnemosyne->shouldReceive('enabled')->once()->andReturn(true);
        $mnemosyne->shouldNotReceive('pushTask');

        (new SyncTaskToMnemosyne(99999))->handle($mnemosyne);
    }

    public function test_sync_persists_canonical_node_name(): void
    {
        $stage = Stage::create(['code' => 'in_corso', 'label' => 'In corso']);
        $user  = User::factory()->create();
        $task  = Task::create(['user_id' => $user->id, 'title' => 'Test task', 'stage_id' => $stage->id]);

        $mnemosyne = $this->mock(MnemosyneService::class);
        $mnemosyne->shouldReceive('enabled')->andReturn(true);
        $mnemosyne->shouldReceive('pushTask')->andReturn(['name' => 'canonical-node-name']);

        (new SyncTaskToMnemosyne($task->id))->handle($mnemosyne);

        $this->assertSame('canonical-node-name', $task->fresh()->mnemosyne_node_name);
    }
}
