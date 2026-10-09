<?php

namespace App\Dtos\Calendar;

use App\Services\CurrentFamilyService;
use App\Support\CalendarRecurrence;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 予定の作成・更新。
 * 更新時の scope（this / following / all）と occurrence_date は繰り返し予定でのみ使う。
 * 参加者が現在の家族のメンバーかどうかは CalendarEventService で検証する（User / VirtualUser の両方を指すため）。
 */
#[TypeScript]
class CalendarEventRequest extends Data
{
    public const SCOPES = ['this', 'following', 'all'];

    public function __construct(
        public string $title,
        public bool $all_day,
        public string $start_date,
        public string $end_date,
        #[Optional]
        public ?string $memo = null,
        #[Optional]
        public ?string $start_time = null,
        #[Optional]
        public ?string $end_time = null,
        #[Optional]
        public ?string $label_id = null,
        /** @var string[] */
        #[Optional]
        public array $participant_ids = [],
        #[Optional]
        public ?string $rrule = null,
        #[Optional]
        public ?string $scope = null,
        #[Optional]
        public ?string $occurrence_date = null,
    ) {}

    public static function rules(): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        return [
            'title' => ['required', 'string', 'max:100'],
            'memo' => ['nullable', 'string', 'max:2000'],
            'all_day' => ['required', 'boolean'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            // 終日でないときの必須チェックは withValidator で行う（未送信の nullable 項目のルールは laravel-data が外すため）
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'label_id' => ['nullable', 'string', Rule::exists('calendar_labels', 'id')->where('family_id', $familyId)],
            'participant_ids' => ['array'],
            'participant_ids.*' => ['string', 'distinct'],
            'rrule' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value !== null && !CalendarRecurrence::isValid($value)) {
                        $fail('繰り返しの設定が正しくありません。');
                    }
                },
            ],
            'scope' => ['nullable', Rule::in(self::SCOPES)],
            'occurrence_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();

            if (filter_var($data['all_day'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
                return;
            }

            if (empty($data['start_time'])) {
                $validator->errors()->add('start_time', '開始時刻を入力してください。');

                return;
            }

            // 同じ日の予定で、終了時刻が開始時刻以前
            if (
                !empty($data['end_time'])
                && ($data['start_date'] ?? null) === ($data['end_date'] ?? null)
                && $data['end_time'] <= $data['start_time']
            ) {
                $validator->errors()->add('end_time', '終了時刻は開始時刻より後にしてください。');
            }
        });
    }

    public static function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'memo' => 'メモ',
            'start_date' => '開始日',
            'end_date' => '終了日',
            'start_time' => '開始時刻',
            'end_time' => '終了時刻',
            'label_id' => 'ラベル',
            'participant_ids' => '参加者',
            'rrule' => '繰り返し',
        ];
    }
}
