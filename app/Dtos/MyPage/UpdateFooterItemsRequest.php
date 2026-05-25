<?php

namespace App\Dtos\MyPage;

use Spatie\LaravelData\Data;

class UpdateFooterItemsRequest extends Data
{
    public function __construct(
        /** @var string[] */
        public readonly array $footer_items,
    ) {}
}
