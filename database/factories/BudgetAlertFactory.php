<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetAlert>
 */
class BudgetAlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'category_id' => null,
            'threshold_percent' => 80,
            'is_enabled' => true,
        ];
    }
}
