<?php

namespace App\Dtos\Family;

use App\Dtos\Model\FamilyData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FamilySettingsResult extends Data
{
    public function __construct(
        public FamilyData $family,
        public bool $is_owner,
        public FamilyPwaSettingsData $pwa,
        /** バナー画像（未設定なら null） */
        public ?string $banner_url = null,
        /** 家族のアイコン（未設定なら null） */
        public ?string $icon_url = null,
    ) {}
}
