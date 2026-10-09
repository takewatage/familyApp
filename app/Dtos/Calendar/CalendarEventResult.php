<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * カレンダーに表示する予定 1 件（繰り返し予定は発生日ごとに 1 件）
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class CalendarEventResult extends Data
{
    public function __construct(
        /** 予定 ID（繰り返しは繰り返し元、この回だけ変更した回は上書き予定の ID。誕生日は 'birthday:{ユーザーID}:{年}'） */
        public string $id,
        /** 発生日（Y-m-d）。繰り返しの変更・削除で「どの回か」を指定するのに使う */
        public string $occurrence_date,
        public string $title,
        public ?string $memo,
        public bool $all_day,
        /** この回の開始日・終了日（Y-m-d） */
        public string $start_date,
        public string $end_date,
        /** H:i（終日なら null） */
        public ?string $start_time,
        public ?string $end_time,
        public ?string $label_id,
        /** @var string[] 参加者（User / VirtualUser）の ID */
        public array $participant_ids,
        /** 繰り返しのルール（繰り返し予定・この回だけ変更した回は繰り返し元のルール） */
        public ?string $rrule,
        /** 繰り返し予定の一部か（この回だけ変更した回を含む） */
        public bool $is_recurring,
        /** 誕生日（ユーザーの birthday から生成。編集不可） */
        public bool $is_birthday = false,
    ) {}
}
