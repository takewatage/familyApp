<?php

namespace App\Dtos\Family;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 家族のバナー画像（ホームの一番上に表示する）
 */
#[TypeScript]
class UpdateFamilyBannerRequest extends Data
{
    public function __construct(
        public readonly UploadedFile $banner,
    ) {}

    public static function rules(): array
    {
        return [
            'banner' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'banner' => 'バナー画像',
        ];
    }
}
