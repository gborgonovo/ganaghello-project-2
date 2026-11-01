<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Eliminazione recuperabile anche per le aree (Parte B MCP, B2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', fn (Blueprint $t) => $t->softDeletes());
    }

    public function down(): void
    {
        Schema::table('areas', fn (Blueprint $t) => $t->dropSoftDeletes());
    }
};
