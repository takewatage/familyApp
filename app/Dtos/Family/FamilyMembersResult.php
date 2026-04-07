<?php

namespace App\Dtos\Family;

use App\Dtos\Model\FamilyData;
use App\Dtos\Model\VirtualUserData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FamilyMembersResult extends Data
{
    public function __construct(
        public FamilyData $family,
        /** @var DataCollection<int, FamilyMemberData> */
        public DataCollection $members,
        /** @var DataCollection<int, VirtualUserData> */
        public DataCollection $virtual_users,
        public bool $is_owner,
        /** @var array<string, string> ロール別署名付き招待URL */
        public array $invite_urls = [],
    ) {}
}
