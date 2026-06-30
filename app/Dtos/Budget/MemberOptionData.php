<?php

namespace App\Dtos\Budget;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 支出の担当者選択肢（実ユーザー / 仮想ユーザー）。
 * key は v-select の item-value 用（"{member_type}|{member_id}"）。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class MemberOptionData extends Data
{
    public function __construct(
        public string $key,
        public string $member_type,
        public string $member_id,
        public string $name,
    ) {}
}
