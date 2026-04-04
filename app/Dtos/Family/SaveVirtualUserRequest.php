<?php

namespace App\Dtos\Family;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(CamelCaseMapper::class)]
class SaveVirtualUserRequest extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?UploadedFile $avatar_image,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'avatar_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif', 'max:10000'],
        ];
    }
}
