<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdZone;

class AdZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AdZone::create([
            'name' => 'Sponsors',
            'slug' => 'sponsors',
            'description' => 'Sidebar sponsor section',
        ]);
    }
}
