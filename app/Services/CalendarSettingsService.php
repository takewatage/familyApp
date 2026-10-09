<?php

namespace App\Services;

use App\Dtos\Calendar\CalendarSettingsResult;
use App\Models\Family;
use Illuminate\Support\Facades\DB;

/**
 * 家族のカレンダー設定（families.settings.calendar に JSON で保存。家族全員が変更できる）
 */
class CalendarSettingsService
{
    public function get(Family $family): CalendarSettingsResult
    {
        $calendar = $family->settings['calendar'] ?? [];

        return new CalendarSettingsResult(
            birthday_label_id: $calendar['birthday_label_id'] ?? null,
        );
    }

    /**
     * 送られた項目だけを更新する（他の家族設定・カレンダー設定は消さない）
     *
     * @param  array<string, mixed>  $values
     */
    public function update(Family $family, array $values): CalendarSettingsResult
    {
        $settings = DB::transaction(function () use ($family, $values) {
            // 同時に別の設定（PWA 等）が更新されても上書きしないよう、最新の値を読み直してから保存する
            $fresh = Family::whereKey($family->id)->lockForUpdate()->firstOrFail();
            $settings = $fresh->settings ?? [];
            $settings['calendar'] = array_merge($settings['calendar'] ?? [], $values);
            $fresh->update(['settings' => $settings]);

            return $settings;
        });

        $family->settings = $settings;

        return $this->get($family);
    }
}
