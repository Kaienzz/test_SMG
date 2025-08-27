<?php

namespace App\Services;

use App\Contracts\FacilityServiceInterface;
use App\Models\TownFacility;
use App\Models\Player;
use App\Enums\FacilityType;

abstract class AbstractFacilityService implements FacilityServiceInterface
{
    protected FacilityType $facilityType;

    public function __construct(FacilityType $facilityType)
    {
        $this->facilityType = $facilityType;
    }

    public function canEnterFacility(string $locationId, string $locationType): bool
    {
        return $locationType === 'town';
    }

    public function getFacilityData(TownFacility $facility): array
    {
        return [
            'facility' => $facility,
            'facility_type' => $this->facilityType,
            'display_name' => $this->facilityType->getDisplayName(),
            'icon' => $this->facilityType->getIcon(),
            'services' => $this->getAvailableServices($facility),
        ];
    }

    abstract public function processTransaction(TownFacility $facility, Player $player, array $data): array;
    
    abstract public function getAvailableServices(TownFacility $facility): array;
    
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

    protected function logTransaction(TownFacility $facility, Player $player, string $action, array $details): void
    {
        // ログ記録の実装（将来的に）
    }
}