<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::factory()->count(10)->create();

        // Create some subcategories
        Category::factory()->count(20)->create([
            'parent_id' => function () {
                return Category::inRandomOrder()->first()->id;
            },
        ]);
    }
}
