<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            User::firstOrCreate(
                ['email' => 'user' . $i . '@example.com'],
                [
                    'name' => 'User ' . $i,
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
