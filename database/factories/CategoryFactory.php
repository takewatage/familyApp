<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'parent_id' => null,
            'name' => fake()->word(),
            'icon' => null,
            'color' => fake()->hexColor(),
            'sort_order' => 0,
            'is_system' => false,
            'is_active' => true,
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => ['family_id' => null, 'is_system' => true]);
    }
}
