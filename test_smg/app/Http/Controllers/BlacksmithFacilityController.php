<?php

namespace App\Http\Controllers;

use App\Enums\FacilityType;
use App\Services\BlacksmithFacilityService;

class BlacksmithFacilityController extends BaseFacilityController
{
    public function __construct()
    {
        parent::__construct(FacilityType::BLACKSMITH, new BlacksmithFacilityService());
    }

    protected function getValidationRules(): array
    {
        return [
            'service_type' => 'required|in:repair,enhance,dismantle',
            'item_slot' => 'required|integer|min:0',
        ];
    }
}