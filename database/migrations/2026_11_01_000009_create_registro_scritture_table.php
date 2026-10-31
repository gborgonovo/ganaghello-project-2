<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_scritture', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('token_id')->nullable();
            $table->string('porta', 20);
            $table->string('azione', 40);
            $table->string('entita', 30);
            $table->unsignedBigInteger('entita_id')->nullable();
            $table->string('riassunto');
            $table->json('modifiche')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entita', 'entita_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_scritture');
    }
};
