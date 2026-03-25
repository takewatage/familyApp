<?php

namespace App\Dtos\MyPage;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SaveProfileData extends Data
{
    public function __construct(
        public string        $name,
        public ?string       $birthday,
        public ?UploadedFile $avatar_image,
    )
    {
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'birthday' => ['nullable', 'date_format:Y-m-d'],
            'avatar_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif', 'max:10000'],
        ];
    }
}
