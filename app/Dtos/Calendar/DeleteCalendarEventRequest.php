<?php

namespace App\Dtos\Calendar;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 予定の削除。scope（this / following / all）と occurrence_date は繰り返し予定でのみ使う。
 */
#[TypeScript]
class DeleteCalendarEventRequest extends Data
{
    public function __construct(
        #[Optional]
        public ?string $scope = null,
        #[Optional]
        public ?string $occurrence_date = null,
    ) {}

    public static function rules(): array
    {
        return [
            'scope' => ['nullable', Rule::in(CalendarEventRequest::SCOPES)],
            'occurrence_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
