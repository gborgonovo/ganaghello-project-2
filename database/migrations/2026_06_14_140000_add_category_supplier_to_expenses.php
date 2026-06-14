<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Blocco 7.2: categoria e fornitore sulle spese (decisi con Giorgio).
// Niente upload ricevuta: media_id resta predisposto ma inutilizzato.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('category', ['materiali', 'manodopera', 'professionisti', 'oneri', 'altro'])->nullable()->after('amount');
            $table->string('supplier')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['category', 'supplier']);
        });
    }
};
