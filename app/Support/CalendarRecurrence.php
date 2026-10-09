<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RRule\RRule;
use RRule\RSet;
use Throwable;

/**
 * 予定の繰り返しルール（RFC 5545 の RRULE）の検証・展開・変更。
 *
 * - DTSTART は予定の開始日（00:00）。日付だけで展開し、時刻は予定の start_time を使う
 * - タイムゾーンの影響を受けないよう UTC で計算する（保存している日付は家族の現地日付）
 * - UNTIL は日付（YYYYMMDD）で持ち、その日を含む
 */
class CalendarRecurrence
{
    /** 画面で扱う繰り返しの頻度 */
    public const FREQUENCIES = ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'];

    /** 1 回の取得で展開する上限（無限の繰り返しに対する保険） */
    private const MAX_OCCURRENCES = 1000;

    /**
     * RRULE として解釈でき、画面で扱う頻度か
     */
    public static function isValid(string $rrule): bool
    {
        try {
            $parts = self::parse($rrule);
            new RRule([...$parts, 'DTSTART' => self::dtstart('2000-01-01')]);

            return in_array($parts['FREQ'] ?? '', self::FREQUENCIES, true);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * 期間内（両端を含む）の発生日（Y-m-d）を返す
     *
     * @param  string[]  $exdates  除外する発生日（Y-m-d）
     * @return string[]
     */
    public static function occurrenceDates(string $rrule, Carbon $dtstart, Carbon $from, Carbon $to, array $exdates = []): array
    {
        $set = new RSet();
        $set->addRRule(new RRule([...self::parse($rrule), 'DTSTART' => self::dtstart($dtstart->toDateString())]));

        foreach ($exdates as $date) {
            $set->addExDate(self::dtstart($date));
        }

        $dates = $set->getOccurrencesBetween(
            self::dtstart($from->toDateString()),
            self::dtstart($to->toDateString()),
            self::MAX_OCCURRENCES,
        );

        return array_map(fn ($d) => $d->format('Y-m-d'), $dates);
    }

    /**
     * 繰り返しを指定日（その日を含む）で終わらせたルールを返す（COUNT は外す）
     */
    public static function withUntil(string $rrule, Carbon $until): string
    {
        $parts = self::parse($rrule);
        unset($parts['COUNT']);
        $parts['UNTIL'] = $until->format('Ymd');

        return self::build($parts);
    }

    /**
     * 意味が同じルールを同じ文字列にする（INTERVAL=1・WKST=MO の省略、項目の並び順）
     */
    public static function normalize(?string $rrule): ?string
    {
        if ($rrule === null) {
            return null;
        }

        $parts = self::parse($rrule);

        if (($parts['INTERVAL'] ?? null) === '1') {
            unset($parts['INTERVAL']);
        }

        if (($parts['WKST'] ?? null) === 'MO') {
            unset($parts['WKST']);
        }

        ksort($parts);

        return self::build($parts);
    }

    /** COUNT（回数指定）。なければ null */
    public static function count(string $rrule): ?int
    {
        $count = self::parse($rrule)['COUNT'] ?? null;

        return $count === null ? null : (int) $count;
    }

    /** COUNT を指定した値に置き換えたルール */
    public static function withCount(string $rrule, int $count): string
    {
        $parts = self::parse($rrule);
        unset($parts['UNTIL']);
        $parts['COUNT'] = (string) $count;

        return self::build($parts);
    }

    /**
     * 最後の発生日（UNTIL / COUNT で終わるルールのみ。終わりがなければ null）
     */
    public static function lastDate(string $rrule, Carbon $dtstart): ?Carbon
    {
        $parts = self::parse($rrule);

        if (!isset($parts['UNTIL']) && !isset($parts['COUNT'])) {
            return null;
        }

        $rule = new RRule([...$parts, 'DTSTART' => self::dtstart($dtstart->toDateString())]);
        $last = null;

        foreach ($rule as $occurrence) {
            $last = $occurrence;
        }

        return $last ? Carbon::parse($last->format('Y-m-d')) : $dtstart->copy();
    }

    /** 指定日より前（その日を含まない）の発生回数 */
    public static function countBefore(string $rrule, Carbon $dtstart, Carbon $date): int
    {
        if ($date->lte($dtstart)) {
            return 0;
        }

        return count(self::occurrenceDates($rrule, $dtstart, $dtstart, $date->copy()->subDay()));
    }

    /**
     * 'FREQ=WEEKLY;BYDAY=MO' 形式を連想配列にする（先頭の 'RRULE:' は許容）
     *
     * @return array<string, string>
     */
    private static function parse(string $rrule): array
    {
        $rrule = preg_replace('/^RRULE:/i', '', trim($rrule));

        if ($rrule === '' || str_contains($rrule, "\n")) {
            throw new InvalidArgumentException('RRULE が不正です');
        }

        $parts = [];

        foreach (explode(';', $rrule) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);

            if (!$key || $value === null || $value === '') {
                throw new InvalidArgumentException('RRULE が不正です');
            }

            $key = strtoupper($key);

            // DTSTART は予定の開始日を使うため、ルール側では受け付けない
            if ($key === 'DTSTART') {
                throw new InvalidArgumentException('RRULE に DTSTART は指定できません');
            }

            $parts[$key] = strtoupper($value);
        }

        return $parts;
    }

    /**
     * @param  array<string, string>  $parts
     */
    private static function build(array $parts): string
    {
        // FREQ を先頭にする（RFC 5545 では順不同だが読みやすさのため）
        $freq = ['FREQ' => $parts['FREQ'] ?? 'DAILY'];
        unset($parts['FREQ']);

        return implode(';', array_map(
            fn ($key, $value) => "{$key}={$value}",
            array_keys($freq + $parts),
            $freq + $parts,
        ));
    }

    private static function dtstart(string $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date.' 00:00:00', new DateTimeZone('UTC'));
    }
}
