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
            ['name' => '家事'],
            ['name' => '買い物'],
            ['name' => '勉強'],
        ];

        $familyA = Family::first();
        if ($familyA) {
            foreach ($categories as $category) {
                TaskCategory::create([
                    'family_id' => $familyA->id,
                    'name' => $category['name'],
                ]);
            }
        }
    }
}
