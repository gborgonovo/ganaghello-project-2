<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('cost_min', 10, 2)->nullable();
            $table->decimal('cost_max', 10, 2)->nullable();
            $table->enum('cost_basis', ['stima', 'preventivo'])->nullable();
            $table->decimal('cost_real', 10, 2)->nullable();
            $table->decimal('work_estimate', 6, 2)->nullable();
            $table->enum('executor', ['una_persona', 'team', 'professionista', 'impresa'])->nullable();
            $table->enum('priority', ['bassa', 'media', 'alta'])->nullable();
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->string('mnemosyne_node_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
