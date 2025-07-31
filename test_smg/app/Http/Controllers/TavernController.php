<?php

namespace App\Http\Controllers;

use App\Enums\ShopType;
use App\Services\TavernService;

class TavernController extends BaseShopController
{
    public function __construct()
    {
        parent::__construct(ShopType::TAVERN, new TavernService());
    }

    protected function getValidationRules(): array
    {
        return [
            'service_type' => 'required|in:heal_hp,heal_mp,heal_sp,heal_all',
            'amount' => 'nullable|integer|min:1',
        ];
    }
}