<?php

namespace App\Services;

use App\Dtos\Calendar\CalendarSettingsResult;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * カレンダー設定。
 *
 * - 家族で共有する項目（families.settings.calendar。家族全員が変更できる）: birthday_color
 * - ユーザーごとの項目（users.settings.calendar）: show_holidays
 */
class CalendarSettingsService
{
    /** ユーザーごとに保存する項目 */
    private const USER_KEYS = ['show_holidays'];

    public function get(Family $family, User $user): CalendarSettingsResult
    {
        $familyCalendar = $family->settings['calendar'] ?? [];

        return new CalendarSettingsResult(
            birthday_color: $familyCalendar['birthday_color'] ?? null,
            show_holidays: $this->showHolidays($user),
        );
    }

    public function showHolidays(User $user): bool
    {
        return (bool) ($user->settings['calendar']['show_holidays'] ?? true);
    }

    /**
     * 送られた項目だけを更新する（他の家族設定・ユーザー設定・カレンダー設定は消さない）
     *
     * @param  array<string, mixed>  $values
     */
    public function update(Family $family, User $user, array $values): CalendarSettingsResult
    {
        $userValues = array_intersect_key($values, array_flip(self::USER_KEYS));
        $familyValues = array_diff_key($values, $userValues);

        DB::transaction(function () use ($family, $user, $familyValues, $userValues) {
            // 同時に別の設定（PWA・テーマ等）が更新されても上書きしないよう、最新の値を読み直してから保存する
            if ($familyValues) {
                $fresh = Family::whereKey($family->id)->lockForUpdate()->firstOrFail();
                $family->settings = $this->merge($fresh->settings, $familyValues);
                $fresh->update(['settings' => $family->settings]);
            }

            if ($userValues) {
                $fresh = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $user->settings = $this->merge($fresh->settings, $userValues);
                $fresh->update(['settings' => $user->settings]);
            }
        });

        return $this->get($family, $user);
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function merge(?array $settings, array $values): array
    {
        $settings ??= [];
        $settings['calendar'] = array_merge($settings['calendar'] ?? [], $values);

        return $settings;
    }
}
