<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d');

        return [
            'family_id' => Family::factory(),
            'title' => fake()->words(2, true),
            'all_day' => true,
            'start_date' => $date,
            'end_date' => $date,
        ];
    }

    /** 時刻ありの予定 */
    public function timed(string $start = '10:00', ?string $end = '11:00'): static
    {
        return $this->state(fn () => ['all_day' => false, 'start_time' => $start, 'end_time' => $end]);
    }

    /** 繰り返し予定 */
    public function recurring(string $rrule = 'FREQ=WEEKLY'): static
    {
        return $this->state(fn () => ['rrule' => $rrule]);
    }
}
