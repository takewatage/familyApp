<?php

namespace App\Dtos\MyPage;

use App\Dtos\Model\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MyPageData extends Data
{
    public function __construct(
        public UserData $user,
    )
    {
    }
}
