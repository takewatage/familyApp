<?php

namespace App\Dtos\Family;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UpdateFamilyPwaSettingsRequest extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?UploadedFile $icon = null,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ];
    }
}
