<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\Character;
use App\Enums\ShopType;

class BlacksmithService extends AbstractShopService
{
    public function __construct()
    {
        parent::__construct(ShopType::BLACKSMITH);
    }

    protected function getItemTypeFilter(): array
    {
        return ['weapon', 'armor'];
    }
}