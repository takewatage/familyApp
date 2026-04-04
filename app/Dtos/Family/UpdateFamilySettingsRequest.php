<?php

namespace App\Dtos\Family;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;

#[MapInputName(CamelCaseMapper::class)]
class UpdateFamilySettingsRequest extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly int $max_members,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'max_members' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
