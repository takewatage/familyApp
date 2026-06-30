<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Family;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
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
            'amount' => fake()->numberBetween(100, 50000),
            'shop_name' => fake()->optional()->company(),
            'expense_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'memo' => fake()->optional()->sentence(),
            'is_recurring' => false,
            'recurring_expense_id' => null,
        ];
    }
}
