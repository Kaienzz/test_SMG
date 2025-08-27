<?php

namespace App\Services;

use App\Enums\FacilityType;
use App\Contracts\FacilityServiceInterface;

class FacilityServiceFactory
{
    public static function create(FacilityType $facilityType): FacilityServiceInterface
    {
        return match($facilityType) {
            FacilityType::ITEM_SHOP => new ItemFacilityService(),
            FacilityType::BLACKSMITH => new BlacksmithFacilityService(),
            FacilityType::TAVERN => new TavernFacilityService(),
            FacilityType::ALCHEMY_SHOP => new AlchemyFacilityService(),
            // 他の施設タイプは将来実装
            default => throw new \InvalidArgumentException("Unsupported facility type: {$facilityType->value}")
        };
    }

    public static function createController(FacilityType $facilityType): string
    {
        return match($facilityType) {
            FacilityType::ITEM_SHOP => ItemFacilityController::class,
            FacilityType::BLACKSMITH => BlacksmithFacilityController::class,
            FacilityType::TAVERN => TavernFacilityController::class,
            FacilityType::ALCHEMY_SHOP => AlchemyFacilityController::class,
            // 他の施設タイプは将来実装
            default => throw new \InvalidArgumentException("Unsupported facility type: {$facilityType->value}")
        };
    }
}