<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'notify_periods'     => '0,1,3,7',
            'digest_enabled'     => 'true',
            'digest_time'        => '08:00',
            'mnemosyne_enabled'  => 'true',
            'site_title'         => 'Ganaghello',
            'blog_description'   => 'Il progetto Ganaghello: una casa di campagna che diventa.',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'key'        => $key,
                'value'      => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
