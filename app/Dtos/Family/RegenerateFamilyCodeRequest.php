<?php

namespace App\Dtos\Family;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;

#[MapInputName(CamelCaseMapper::class)]
class RegenerateFamilyCodeRequest extends Data
{
    public function __construct(
        // null = 無期限、ISO日時文字列 = 有効期限あり
        public readonly ?string $expires_at,
    ) {}

    public static function rules(): array
    {
        return [
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
