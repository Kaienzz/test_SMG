<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'shop_type',
        'location_id',
        'location_type',
        'is_active',
        'description',
        'shop_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'shop_config' => 'array',
    ];

    public function shopItems(): HasMany
    {
        return $this->hasMany(ShopItem::class);
    }

    public function availableItems(): HasMany
    {
        return $this->shopItems()->where('is_available', true);
    }

    public static function findByLocation(string $locationId, string $locationType): ?self
    {
        return self::where('location_id', $locationId)
                   ->where('location_type', $locationType)
                   ->where('is_active', true)
                   ->first();
    }

    public static function findByLocationAndType(string $locationId, string $locationType, string $shopType): ?self
    {
        return self::where('location_id', $locationId)
                   ->where('location_type', $locationType)
                   ->where('shop_type', $shopType)
                   ->where('is_active', true)
                   ->first();
    }

    public static function getShopsByLocation(string $locationId, string $locationType): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('location_id', $locationId)
                   ->where('location_type', $locationType)
                   ->where('is_active', true)
                   ->get();
    }
}
