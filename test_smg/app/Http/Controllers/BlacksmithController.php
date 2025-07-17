<?php

namespace App\Http\Controllers;

use App\Enums\ShopType;
use App\Services\BlacksmithService;

class BlacksmithController extends BaseShopController
{
    public function __construct()
    {
        parent::__construct(ShopType::BLACKSMITH, new BlacksmithService());
    }

    protected function getValidationRules(): array
    {
        return [
            'item_id' => 'required|exists:shop_items,id',
            'quantity' => 'required|integer|min:1',
        ];
    }
}