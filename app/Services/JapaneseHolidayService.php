<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Yasumi\Holiday;
use Yasumi\Yasumi;

/**
 * 日本の祝日・休日（国民の祝日・振替休日・国民の休日）。azuyalabs/yasumi のルール計算を使い、外部通信はしない
 */
class JapaneseHolidayService
{
    /**
     * 期間内（両端を含む）の祝日を日付順で返す
     *
     * @return array<int, array{date: string, name: string}>
     */
    public function between(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $results = [];

        for ($year = $from->year; $year <= $to->year; $year++) {
            foreach (Yasumi::create('Japan', $year, 'ja_JP') as $holiday) {
                /** @var Holiday $holiday */
                $date = $holiday->format('Y-m-d');

                if ($holiday->getType() !== Holiday::TYPE_OFFICIAL || $date < $from->toDateString() || $date > $to->toDateString()) {
                    continue;
                }

                $results[] = ['date' => $date, 'name' => $this->name($holiday)];
            }
        }

        usort($results, fn (array $a, array $b) => $a['date'] <=> $b['date']);

        return $results;
    }

    /**
     * 振替休日は「振替休日 (憲法記念日)」のように元の祝日名が付くため、マスに収まるよう「振替休日」にそろえる
     */
    private function name(Holiday $holiday): string
    {
        return preg_replace('/\s*[(（].*[)）]\s*$/u', '', $holiday->getName());
    }
}
