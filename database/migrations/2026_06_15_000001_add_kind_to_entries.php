<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo di voce del diario per la resa "scrapbook": polaroid (con foto),
 * postit (testo corto), nota (testo lungo). Estensibile a tipi futuri.
 * Backfill delle voci esistenti: foto -> polaroid; testo lungo -> nota; resto -> postit.
 */
return new class extends Migration
{
    private const NOTA_THRESHOLD = 280;

    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('kind')->default('postit')->after('content');
        });

        foreach (DB::table('entries')->select('id', 'content')->get() as $e) {
            $hasPhoto = DB::table('attachments')
                ->where('attachable_type', 'App\\Models\\Entry')
                ->where('attachable_id', $e->id)
                ->exists();

            $kind = match (true) {
                $hasPhoto                                          => 'polaroid',
                mb_strlen((string) $e->content) > self::NOTA_THRESHOLD => 'nota',
                default                                            => 'postit',
            };

            DB::table('entries')->where('id', $e->id)->update(['kind' => $kind]);
        }
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
