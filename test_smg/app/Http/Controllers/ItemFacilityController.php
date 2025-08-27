<?php

namespace App\Http\Controllers;

use App\Enums\FacilityType;
use App\Services\ItemFacilityService;
use Illuminate\Support\Facades\Auth;

class ItemFacilityController extends BaseFacilityController
{
    public function __construct()
    {
        parent::__construct(FacilityType::ITEM_SHOP, new ItemFacilityService());
    }

    protected function getValidationRules(): array
    {
        return [
            'facility_item_id' => 'nullable|exists:facility_items,id',
            'sell_slot_index' => 'nullable|integer|min:0',
            'quantity' => 'integer|min:1|max:99',
        ];
    }

    public function inventory(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        $inventory = $this->facilityService->getPlayerInventory($player);
        
        return response()->json([
            'success' => true,
            'inventory' => $inventory,
        ]);
    }
}