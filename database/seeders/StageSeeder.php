<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['code' => 'idea',        'label' => 'Idea',           'bg_color' => '#F3F4F6', 'text_color' => '#6B7280', 'sequence' => 1],
            ['code' => 'discussione', 'label' => 'In discussione', 'bg_color' => '#EFF6FF', 'text_color' => '#3B82F6', 'sequence' => 2],
            ['code' => 'approvato',   'label' => 'Approvato',      'bg_color' => '#ECFDF5', 'text_color' => '#059669', 'sequence' => 3],
            ['code' => 'todo',        'label' => 'To do',          'bg_color' => '#FEF3C7', 'text_color' => '#D97706', 'sequence' => 4],
            ['code' => 'doing',       'label' => 'Doing',          'bg_color' => '#FFF7ED', 'text_color' => '#EA580C', 'sequence' => 5],
            ['code' => 'in_attesa',   'label' => 'In attesa',      'bg_color' => '#F5F3FF', 'text_color' => '#7C3AED', 'sequence' => 6],
            ['code' => 'done',        'label' => 'Done',           'bg_color' => '#5C6B4F', 'text_color' => '#FFFFFF', 'sequence' => 7],
            ['code' => 'archiviato',  'label' => 'Archiviato',     'bg_color' => '#F9FAFB', 'text_color' => '#9CA3AF', 'sequence' => 8],
        ];

        foreach ($stages as $stage) {
            DB::table('stages')->insertOrIgnore(array_merge($stage, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
