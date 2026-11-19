<?php

use App\Models\Category;
use App\Models\Type;
use Illuminate\Support\Facades\Blade;

test('the top software categories sidebar shows only software categories', function () {
    $software = Type::firstOrCreate(['name' => 'Software Categories']);
    $category = Category::create(['name' => 'Sidebar Writing Tools', 'slug' => 'sidebar-writing-tools']);
    $category->types()->attach($software);

    $mixed = Category::create(['name' => 'Sidebar Mixed', 'slug' => 'sidebar-mixed']);
    $mixed->types()->attach($software);

    foreach (['Platform', 'Pricing', 'Use Case', 'Best for'] as $index => $name) {
        $type = Type::firstOrCreate(['name' => $name]);
        $excluded = Category::create(['name' => "Sidebar {$name}", 'slug' => "sidebar-excluded-{$index}"]);
        $excluded->types()->attach($type);

        if ($name === 'Platform') {
            $mixed->types()->attach($type);
        }
    }

    $html = Blade::render('<x-top-categories />');

    expect($html)
        ->toContain('Top software categories', $category->name)
        ->not->toContain('Sidebar Platform', 'Sidebar Pricing', 'Sidebar Use Case', 'Sidebar Best for', $mixed->name);
});
