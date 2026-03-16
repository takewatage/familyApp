<?php

namespace App\Dtos\Model;

use App\Dtos\Model\FamilyData;
use App\Dtos\Model\FileData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $email_verified_at,
        public ?string $created_at,
        public ?string $updated_at,
        // accessor
        public FileData|Optional|null $avatar,
        // relation: BelongsToMany
        /** @var DataCollection<int, FamilyData> */
        public DataCollection|Optional $families,
        // relation: MorphMany
        /** @var DataCollection<int, FileData> */
        public DataCollection|Optional $files
    ) {}
}
