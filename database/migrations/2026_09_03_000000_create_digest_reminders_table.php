<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup dei promemoria del digest: una riga per ogni (utente, task, offset) gia'
 * inviato. offset in giorni: negativo = prima della scadenza, 0 = il giorno stesso,
 * positivo = giorni di ritardo (1/3/7/15/30, poi multipli di 30 per la coda mensile).
 *
 * Serve a non ripetere un promemoria e a recuperarlo al primo run utile se il
 * worker era fermo nel giorno esatto della soglia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digest_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->integer('offset');
            $table->date('sent_on');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'task_id', 'offset']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digest_reminders');
    }
};
