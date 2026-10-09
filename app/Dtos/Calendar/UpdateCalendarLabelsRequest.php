<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * ラベル一覧の一括更新（並び順は配列の順。追加・削除はできない）
 * ラベルが現在の家族のものか・全件そろっているかは CalendarLabelService で検証する。
 */
#[TypeScript]
class UpdateCalendarLabelsRequest extends Data
{
    public function __construct(
        /** @var CalendarLabelInputData[] */
        public array $labels,
    ) {}

    public static function rules(): array
    {
        return [
            'labels' => ['required', 'array', 'min:1'],
            'labels.*.id' => ['required', 'string', 'distinct'],
            'labels.*.name' => ['required', 'string', 'max:20'],
            'labels.*.color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'labels.*.name' => 'ラベル名',
            'labels.*.color' => 'カラー',
        ];
    }
}
