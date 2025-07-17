<?php

namespace App\Http\Controllers;

use App\Enums\ShopType;
use App\Services\ItemShopService;

class ItemShopController extends BaseShopController
{
    public function __construct()
    {
        parent::__construct(ShopType::ITEM_SHOP, new ItemShopService());
    }

    protected function getValidationRules(): array
    {
        return [
            'shop_item_id' => 'nullable|exists:shop_items,id',
            'sell_slot_index' => 'nullable|integer|min:0',
            'quantity' => 'integer|min:1|max:99',
        ];
    }

    public function inventory(): \Illuminate\Http\JsonResponse
    {
        $character = $this->getCharacter();
        $inventory = $this->shopService->getCharacterInventory($character);
        
        return response()->json([
            'success' => true,
            'inventory' => $inventory,
        ]);
    }
}