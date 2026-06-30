<?php

namespace Database\Factories;

use App\Models\BudgetAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlertNotification>
 */
class AlertNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alert_id' => BudgetAlert::factory(),
            'year_month' => now()->format('Y-m'),
            'triggered_at' => now(),
            'actual_percent' => fake()->randomFloat(2, 80, 100),
        ];
    }
}
