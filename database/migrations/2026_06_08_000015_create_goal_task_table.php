<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_task', function (Blueprint $table) {
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->primary(['goal_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_task');
    }
};
