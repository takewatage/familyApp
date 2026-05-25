<?php

namespace App\Dtos\MyPage;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FooterSettingsResult extends Data
{
    public function __construct(
        /** @var string[] */
        public readonly array $footer_items,
    ) {}

    public static function fromArray(array $settings): self
    {
        return new self(
            footer_items: $settings['footer_items'] ?? ['home', 'dok', 'tasks'],
        );
    }
}
