<?php

namespace App\Dtos\Family;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 家族のアイコン画像（サイドメニューの家族名の横に表示する）
 */
#[TypeScript]
class UpdateFamilyIconRequest extends Data
{
    public function __construct(
        public readonly UploadedFile $icon,
    ) {}

    public static function rules(): array
    {
        return [
            'icon' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'icon' => 'アイコン画像',
        ];
    }
}
