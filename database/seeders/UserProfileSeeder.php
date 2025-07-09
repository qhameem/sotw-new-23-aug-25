<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserProfile;
use App\Models\User;

class UserProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        foreach ($users as $user) {
            UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'profile_photo_path' => null,
                    'twitter_url' => 'https://twitter.com/user' . $user->id,
                    'github_url' => 'https://github.com/user' . $user->id,
                    'website_url' => 'https://user' . $user->id . '.example.com',
                    'bio' => 'This is the bio for User ' . $user->id,
                ]
            );
        }
    }
}
