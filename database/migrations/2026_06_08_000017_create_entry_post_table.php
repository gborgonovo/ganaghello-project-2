<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_post', function (Blueprint $table) {
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->primary(['entry_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_post');
    }
};
