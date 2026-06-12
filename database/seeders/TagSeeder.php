<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'impianti',
            'struttura',
            'documenti',
            'business',
            'attrezzi',
            'materiali',
            'sicurezza',
            'permessi',
        ];

        foreach ($tags as $i => $name) {
            DB::table('tags')->insertOrIgnore([
                'name'       => $name,
                'color'      => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
