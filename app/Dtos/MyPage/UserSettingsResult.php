<?php

namespace App\Dtos\MyPage;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class UserSettingsResult extends Data
{
    public function __construct(
        #[In(['light', 'dark', 'system'])]
        public readonly string $theme,
        #[In(['pink', 'blue', 'purple', 'green', 'orange', 'teal'])]
        public readonly string $theme_color,
    ) {}

    public static function fromArray(array $settings): self
    {
        return new self(
            theme: $settings['theme'] ?? 'system',
            theme_color: $settings['theme_color'] ?? 'pink',
        );
    }
}
