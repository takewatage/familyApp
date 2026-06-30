<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Family;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuickEntry>
 */
class QuickEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'member_type' => null,
            'member_id' => null,
            'name' => fake()->word(),
            'category_id' => Category::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'shop_id' => null,
            'default_amount' => fake()->optional()->numberBetween(100, 5000),
            'sort_order' => 0,
            'usage_count' => 0,
        ];
    }
}
