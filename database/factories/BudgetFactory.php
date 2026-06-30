<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Budget>
 */
class BudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'year_month' => now()->format('Y-m'),
            'total_income' => fake()->numberBetween(200000, 600000),
            'saving_target' => fake()->numberBetween(0, 100000),
        ];
    }
}
