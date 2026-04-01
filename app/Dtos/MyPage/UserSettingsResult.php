<?php

namespace App\Dtos\MyPage;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserSettingsResult extends Data
{
    public function __construct(
        public readonly string $theme,
    ) {}

    public static function fromArray(array $settings): self
    {
        return new self(
            theme: $settings['theme'] ?? 'system',
        );
    }
}
