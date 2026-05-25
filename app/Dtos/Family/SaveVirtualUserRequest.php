<?php

namespace App\Dtos\Family;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SaveVirtualUserRequest extends Data
{
    public function __construct(
        public readonly string        $name,
        public readonly ?UploadedFile $avatar_image,
    )
    {
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'avatar_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif', 'max:10000'],
        ];
    }
}
