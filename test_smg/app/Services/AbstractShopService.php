<?php

namespace App\Services;

use App\Contracts\ShopServiceInterface;
use App\Models\Shop;
use App\Models\Player;
use App\Enums\ShopType;

abstract class AbstractShopService implements ShopServiceInterface
{
    protected ShopType $shopType;

    public function __construct(ShopType $shopType)
    {
        $this->shopType = $shopType;
    }

    public function canEnterShop(string $locationId, string $locationType): bool
    {
        return $locationType === 'town';
    }

    public function getShopData(Shop $shop): array
    {
        return [
            'shop' => $shop,
            'shop_type' => $this->shopType,
            'display_name' => $this->shopType->getDisplayName(),
            'icon' => $this->shopType->getIcon(),
            'services' => $this->getAvailableServices($shop),
        ];
    }

    abstract public function processTransaction(Shop $shop, Player $player, array $data): array;
    
    abstract public function getAvailableServices(Shop $shop): array;
    
    abstract public function validateTransactionData(array $data): bool;

    protected function createSuccessResponse(string $message, array $data = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message,
        ], $data);
    }

    protected function createErrorResponse(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'code' => $code,
        ];
    }

    protected function logTransaction(Shop $shop, Player $player, string $action, array $details): void
    {
        // ログ記録の実装（将来的に）
    }
}