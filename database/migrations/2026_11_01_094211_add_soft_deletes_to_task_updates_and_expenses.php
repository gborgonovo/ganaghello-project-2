<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Eliminazione recuperabile anche per aggiornamenti dei task e spese (Parte B MCP, passo 2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_updates', fn (Blueprint $t) => $t->softDeletes());
        Schema::table('expenses', fn (Blueprint $t) => $t->softDeletes());
    }

    public function down(): void
    {
        Schema::table('task_updates', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('expenses', fn (Blueprint $t) => $t->dropSoftDeletes());
    }
};
