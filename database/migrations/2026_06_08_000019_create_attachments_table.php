<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('attachable');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sequence')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
