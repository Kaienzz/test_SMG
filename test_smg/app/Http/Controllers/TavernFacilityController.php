<?php

namespace App\Http\Controllers;

use App\Enums\FacilityType;
use App\Services\TavernFacilityService;

class TavernFacilityController extends BaseFacilityController
{
    public function __construct()
    {
        parent::__construct(FacilityType::TAVERN, new TavernFacilityService());
    }

    protected function getValidationRules(): array
    {
        return [
            'service_type' => 'required|in:heal_hp,heal_mp,heal_sp,heal_all',
            'amount' => 'nullable|integer|min:1',
        ];
    }
}