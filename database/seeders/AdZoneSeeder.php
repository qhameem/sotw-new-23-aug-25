<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\AdZone::updateOrCreate(
            ['slug' => 'header-above-calendar'],
            [
                'name' => 'Header: Above Calendar',
                'description' => 'Displayed at the top of the page, above the product calendar on the homepage.'
            ]
        );

        \App\Models\AdZone::updateOrCreate(
            ['slug' => 'sidebar-top'],
            [
                'name' => 'Sidebar: Top',
                'description' => 'Displayed at the top of the sidebar on relevant pages.'
            ]
        );

        \App\Models\AdZone::updateOrCreate(
            ['slug' => 'sidebar-bottom'],
            [
                'name' => 'Sidebar: Bottom',
                'description' => 'Displayed at the bottom of the sidebar on relevant pages.'
            ]
        );

        \App\Models\AdZone::updateOrCreate(
            ['slug' => 'below-product-listing'],
            [
                'name' => 'Below Product Listing',
                'description' => 'Displayed directly below a list of products.'
            ]
        );
    }
}
