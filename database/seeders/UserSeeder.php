<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'gimborgo@gmail.com'],
            [
                'name'              => 'Giorgio',
                'password'          => Hash::make('changeme'),
                'role'              => 'admin',
                'identicon'         => 'GB',
                'email_verified_at' => now(),
            ]
        );
    }
}
