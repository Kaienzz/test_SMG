<?php

namespace App\Contracts;

use App\Models\Shop;
use App\Models\Player;

interface ShopServiceInterface
{
    public function canEnterShop(string $locationId, string $locationType): bool;
    
    public function getShopData(Shop $shop): array;
    
    public function processTransaction(Shop $shop, Player $player, array $data): array;
    
    public function getAvailableServices(Shop $shop): array;
    
    public function validateTransactionData(array $data): bool;
}