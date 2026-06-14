<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Le aree ora hanno un chip coerente con i badge degli stage: 'color' resta lo
// sfondo, si aggiunge 'text_color' per il testo. (07-delta / audit UX 3.1)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->string('text_color', 20)->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('text_color');
        });
    }
};
