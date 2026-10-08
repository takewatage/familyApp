<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 予定の参加者として選べる家族メンバー（ログインユーザー・仮想ユーザー）
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class CalendarParticipantResult extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $avatar_url,
        /** 仮想ユーザー（アカウントを持たないメンバー）か */
        public bool $is_virtual,
    ) {}
}
