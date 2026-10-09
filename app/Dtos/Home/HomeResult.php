<?php

namespace App\Dtos\Home;

use App\Dtos\Family\FamilyMemberData;
use App\Dtos\Model\VirtualUserData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class HomeResult extends Data
{
    public function __construct(
        /** @var FamilyMemberData[] */
        public array $members,
        /** @var VirtualUserData[] */
        public array $virtual_users,
        /** 家族のバナー画像（ホームの一番上に表示。未設定なら null） */
        public ?string $banner_url = null,
    ) {}
}
