<?php

namespace App\Dtos\Family;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 家族ごとの PWA 外観（アプリ名・アプリ画像）。未設定項目は config/pwa.php の既定値で補完済み。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FamilyPwaSettingsData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $icon_apple,
        public readonly string $icon_192,
        public readonly string $icon_512,
        public readonly string $icon_maskable,
        public readonly bool $is_custom_icon,
        public readonly string $manifest_hash,
    ) {}
}
