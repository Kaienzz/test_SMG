<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TownFacility extends Model
{
    protected $table = 'town_facilities';

    protected $fillable = [
        'name',
        'facility_type',
        'location_id',
        'location_type',
        'is_active',
        'description',
        'facility_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'facility_config' => 'array',
    ];

    public function facilityItems(): HasMany
    {
        return $this->hasMany(FacilityItem::class, 'facility_id');
    }

    public function availableItems(): HasMany
    {
        return $this->facilityItems()->where('is_available', true);
    }

    public static function findByLocation(string $locationId, string $locationType): ?self
    {
        return self::where('location_id', $locationId)
                   ->where('location_type', $locationType)
                   ->where('is_active', true)
                   ->first();
    }

    public static function findByLocationAndType(string $locationId, string $locationType, string $facilityType): ?self
    {
        return self::where('location_id', $locationId)
                   ->where('location_type', $locationType)
                   ->where('facility_type', $facilityType)
                   ->where('is_active', true)
                   ->first();
    }

    public static function getFacilitiesByLocation(string $locationId, string $locationType): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('location_id', $locationId)
                   ->where('location_type', $locationType)
                   ->where('is_active', true)
                   ->get();
    }
}