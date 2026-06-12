<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        // Aree di primo livello (senza genitore)
        $roots = [
            ['name' => 'Abitazione',   'color' => '#C98A6B', 'sequence' => 1],
            ['name' => 'Stalla',       'color' => '#8A9E7A', 'sequence' => 2],
            ['name' => 'Orto',         'color' => '#5C6B4F', 'sequence' => 3],
            ['name' => 'Bosco',        'color' => '#3D4A35', 'sequence' => 4],
            ['name' => 'Accesso',      'color' => '#9CA3AF', 'sequence' => 5],
            ['name' => 'Progetto B&B', 'color' => '#3B82F6', 'sequence' => 6],
            ['name' => 'Giardino',     'color' => '#7C9F6E', 'sequence' => 7],
            ['name' => 'Annessi',      'color' => '#A0845C', 'sequence' => 8],
            ['name' => 'Silos',        'color' => '#B0A070', 'sequence' => 9],
            ['name' => 'Prato',        'color' => '#6B8E5A', 'sequence' => 10],
        ];

        foreach ($roots as $area) {
            DB::table('areas')->insertOrIgnore(array_merge($area, [
                'parent_area_id' => null,
                'description'    => null,
                'status'         => null,
                'lat'            => null,
                'lng'            => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]));
        }

        // Sub-aree: richiedono che i parent esistano
        $subs = [
            // parent_name          => sub-aree
            'Annessi'    => ['Magazzino', 'Stalletta'],
            'Abitazione' => ['Camera da letto Ovest', 'Soggiorno Ovest', 'Cucina Ovest', 'Bagno Ovest'],
        ];

        foreach ($subs as $parentName => $children) {
            $parentId = DB::table('areas')->where('name', $parentName)->value('id');
            if (!$parentId) {
                continue;
            }
            foreach ($children as $i => $childName) {
                DB::table('areas')->insertOrIgnore([
                    'name'           => $childName,
                    'color'          => null,
                    'sequence'       => $i + 1,
                    'parent_area_id' => $parentId,
                    'description'    => null,
                    'status'         => null,
                    'lat'            => null,
                    'lng'            => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }
}
