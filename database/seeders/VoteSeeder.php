<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vote;
use App\Models\User;
use App\Models\Product;

class VoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $productIds = Product::pluck('id')->toArray();
        foreach ($users as $user) {
            $voted = [];
            for ($i = 0; $i < 5; $i++) {
                do {
                    $productId = $productIds[array_rand($productIds)];
                } while (in_array($productId, $voted));
                $voted[] = $productId;
                Vote::firstOrCreate([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ]);
            }
        }
    }
}
