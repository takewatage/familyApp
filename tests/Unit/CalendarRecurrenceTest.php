<?php

namespace Tests\Unit;

use App\Support\CalendarRecurrence;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CalendarRecurrenceTest extends TestCase
{
    public function test_is_valid_accepts_supported_frequencies_only(): void
    {
        $this->assertTrue(CalendarRecurrence::isValid('FREQ=WEEKLY;BYDAY=MO,WE'));
        $this->assertTrue(CalendarRecurrence::isValid('RRULE:FREQ=MONTHLY;BYDAY=-1FR'));
        $this->assertFalse(CalendarRecurrence::isValid('FREQ=HOURLY'));
        $this->assertFalse(CalendarRecurrence::isValid('FREQ=WEEKLY;DTSTART=20260101'));
        $this->assertFalse(CalendarRecurrence::isValid('garbage'));
        $this->assertFalse(CalendarRecurrence::isValid(''));
    }

    public function test_with_until_replaces_count(): void
    {
        $this->assertSame(
            'FREQ=DAILY;INTERVAL=2;UNTIL=20261031',
            CalendarRecurrence::withUntil('FREQ=DAILY;INTERVAL=2;COUNT=10', Carbon::parse('2026-10-31')),
        );
    }

    public function test_occurrence_dates_excludes_exdates(): void
    {
        $this->assertSame(
            ['2026-10-01', '2026-10-03'],
            CalendarRecurrence::occurrenceDates('FREQ=DAILY', Carbon::parse('2026-10-01'), Carbon::parse('2026-10-01'), Carbon::parse('2026-10-03'), ['2026-10-02']),
        );
    }
}
