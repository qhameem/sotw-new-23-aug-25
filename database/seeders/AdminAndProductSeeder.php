<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminAndProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);

        // Create admin user
        $adminUser = \App\Models\User::firstOrCreate(
            ['email' => 'qhameemb@gmail.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'), // You should change this
            ]
        );

        // Assign admin role to the user
        $adminUser->assignRole($adminRole);

        // Create some sample products
        if (\App\Models\Product::count() == 0) {
            \App\Models\Product::factory()->count(20)->create();
        }
    }
}
