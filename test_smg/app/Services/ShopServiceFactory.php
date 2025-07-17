<?php

namespace App\Services;

use App\Enums\ShopType;
use App\Contracts\ShopServiceInterface;

class ShopServiceFactory
{
    public static function create(ShopType $shopType): ShopServiceInterface
    {
        return match($shopType) {
            ShopType::ITEM_SHOP => new ItemShopService(),
            ShopType::BLACKSMITH => new BlacksmithService(),
            // 他のショップタイプは将来実装
            default => throw new \InvalidArgumentException("Unsupported shop type: {$shopType->value}")
        };
    }

    public static function createController(ShopType $shopType): string
    {
        return match($shopType) {
            ShopType::ITEM_SHOP => ItemShopController::class,
            ShopType::BLACKSMITH => BlacksmithController::class,
            // 他のショップタイプは将来実装
            default => throw new \InvalidArgumentException("Unsupported shop type: {$shopType->value}")
        };
    }
}