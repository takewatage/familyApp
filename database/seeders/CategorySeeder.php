<?php

namespace Database\Seeders;

use App\Models\TaskCategory;
use App\Models\Family;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => '家事', 'color' => '#FF5733'],
            ['name' => '買い物', 'color' => '#33FF57'],
            ['name' => '勉強', 'color' => '#3357FF'],
        ];

        $familyA = Family::first();
        if ($familyA) {
            foreach ($categories as $category) {
                TaskCategory::create([
                    'family_id' => $familyA->id,
                    'name' => $category['name'],
                    'color' => $category['color'],
                ]);
            }
        }
    }
}
