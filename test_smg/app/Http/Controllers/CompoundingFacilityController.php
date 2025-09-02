<?php

namespace App\Http\Controllers;

use App\Enums\FacilityType;
use App\Services\CompoundingFacilityService;

class CompoundingFacilityController extends BaseFacilityController
{
    public function __construct()
    {
        parent::__construct(FacilityType::COMPOUNDING_SHOP, new CompoundingFacilityService());
    }

    protected function getValidationRules(): array
    {
        return [
            'recipe_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1|max:999',
        ];
    }
}
