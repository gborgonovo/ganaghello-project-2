<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'raggiunto', 'abbandonato'])->default('active');
            $table->date('deadline')->nullable();
            $table->foreignId('parent_goal_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->string('mnemosyne_node_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
