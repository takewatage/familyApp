<?php

namespace App\Dtos\Calendar;

use App\Services\CurrentFamilyService;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * カレンダー設定の更新（送った項目だけを更新する）
 */
#[TypeScript]
class UpdateCalendarSettingsRequest extends Data
{
    public function __construct(
        #[Optional]
        public ?string $birthday_label_id = null,
    ) {}

    public static function rules(): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        return [
            'birthday_label_id' => ['nullable', 'string', Rule::exists('calendar_labels', 'id')->where('family_id', $familyId)],
        ];
    }

    public static function attributes(): array
    {
        return [
            'birthday_label_id' => '誕生日のラベル',
        ];
    }
}
