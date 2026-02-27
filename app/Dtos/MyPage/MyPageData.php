<?php

namespace App\Dtos\MyPage;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MyPageData extends Data
{
    public function __construct(
        public string  $name,
        public string  $email,
        public ?string $avatarUrl,
        public string  $createdAt,
    ) {
    }
}
