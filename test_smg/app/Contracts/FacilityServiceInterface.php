<?php

namespace App\Contracts;

use App\Models\TownFacility;
use App\Models\Player;

interface FacilityServiceInterface
{
    public function canEnterFacility(string $locationId, string $locationType): bool;
    
    public function getFacilityData(TownFacility $facility): array;
    
    public function processTransaction(TownFacility $facility, Player $player, array $data): array;
    
    public function getAvailableServices(TownFacility $facility): array;
    
    public function validateTransactionData(array $data): bool;
}