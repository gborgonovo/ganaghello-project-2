<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Durante il seed non sincronizziamo su Mnemosyne (dati base, niente coda).
        config(['services.mnemosyne.sync' => false]);

        $this->call([
            UserSeeder::class,
            StageSeeder::class,
            AreaSeeder::class,
            TagSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
