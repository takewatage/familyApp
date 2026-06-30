<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Family;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringExpense>
 */
class RecurringExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'member_type' => null,
            'member_id' => null,
            'category_id' => Category::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'shop_id' => null,
            'name' => fake()->word(),
            'amount' => fake()->numberBetween(1000, 100000),
            'day_of_month' => fake()->numberBetween(1, 28),
            'start_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'end_date' => null,
            'is_active' => true,
            'last_generated_date' => null,
        ];
    }
}
