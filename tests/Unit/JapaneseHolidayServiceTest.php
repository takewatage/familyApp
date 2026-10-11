<?php

namespace Tests\Unit;

use App\Services\JapaneseHolidayService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class JapaneseHolidayServiceTest extends TestCase
{
    private JapaneseHolidayService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new JapaneseHolidayService();
    }

    /**
     * 内閣府「国民の祝日について」の 2026 年の一覧と一致する（振替休日・国民の休日を含む）
     */
    public function test_holidays_of_2026_match_cabinet_office_list(): void
    {
        $holidays = $this->service->between(Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'));

        $this->assertSame([
            ['date' => '2026-01-01', 'name' => '元日'],
            ['date' => '2026-01-12', 'name' => '成人の日'],
            ['date' => '2026-02-11', 'name' => '建国記念の日'],
            ['date' => '2026-02-23', 'name' => '天皇誕生日'],
            ['date' => '2026-03-20', 'name' => '春分の日'],
            ['date' => '2026-04-29', 'name' => '昭和の日'],
            ['date' => '2026-05-03', 'name' => '憲法記念日'],
            ['date' => '2026-05-04', 'name' => 'みどりの日'],
            ['date' => '2026-05-05', 'name' => 'こどもの日'],
            ['date' => '2026-05-06', 'name' => '振替休日'],
            ['date' => '2026-07-20', 'name' => '海の日'],
            ['date' => '2026-08-11', 'name' => '山の日'],
            ['date' => '2026-09-21', 'name' => '敬老の日'],
            ['date' => '2026-09-22', 'name' => '国民の休日'],
            ['date' => '2026-09-23', 'name' => '秋分の日'],
            ['date' => '2026-10-12', 'name' => 'スポーツの日'],
            ['date' => '2026-11-03', 'name' => '文化の日'],
            ['date' => '2026-11-23', 'name' => '勤労感謝の日'],
        ], $holidays);
    }

    public function test_range_spanning_years_returns_holidays_of_both_years(): void
    {
        $holidays = $this->service->between(Carbon::parse('2026-11-20'), Carbon::parse('2027-01-15'));

        $this->assertSame(
            ['2026-11-23', '2027-01-01', '2027-01-11'],
            array_column($holidays, 'date'),
        );
    }

    public function test_range_boundaries_are_inclusive(): void
    {
        $this->assertSame(
            ['2026-05-03', '2026-05-04', '2026-05-05', '2026-05-06'],
            array_column($this->service->between(Carbon::parse('2026-05-03 15:00'), Carbon::parse('2026-05-06')), 'date'),
        );

        $this->assertSame([], $this->service->between(Carbon::parse('2026-05-07'), Carbon::parse('2026-07-19')));
    }

    /**
     * 名前はすべて日本語（翻訳がなく英名になっていない）
     */
    public function test_all_names_are_japanese_for_several_years(): void
    {
        $holidays = $this->service->between(Carbon::parse('2025-01-01'), Carbon::parse('2030-12-31'));

        $this->assertNotEmpty($holidays);

        foreach ($holidays as $holiday) {
            $this->assertMatchesRegularExpression('/^[\p{Han}\p{Hiragana}\p{Katakana}ー]+$/u', $holiday['name'], $holiday['date']);
        }
    }
}
