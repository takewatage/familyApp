<?php

namespace Database\Factories;

use App\Models\CalendarLabel;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarLabel>
 */
class CalendarLabelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'name' => fake()->word(),
            'color' => fake()->hexColor(),
            'sort' => 0,
        ];
    }
}
