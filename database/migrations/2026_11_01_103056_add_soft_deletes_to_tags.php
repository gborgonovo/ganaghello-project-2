<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Eliminazione recuperabile anche per i tag (Parte B MCP, B2): i collegamenti restano e tornano col ripristino.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', fn (Blueprint $t) => $t->softDeletes());
    }

    public function down(): void
    {
        Schema::table('tags', fn (Blueprint $t) => $t->dropSoftDeletes());
    }
};
