<?php

namespace App\Dtos\MyPage;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

class UpdateSettingsRequest extends Data
{
    public function __construct(
        #[In(['light', 'dark', 'system'])]
        public readonly string $theme,
    ) {}
}
