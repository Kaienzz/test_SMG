<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardItem extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'category',
        'category_name',
        'effects',
        'value',
        'sell_price',
        'stack_limit',
        'max_durability',
        'is_equippable',
        'is_usable',
        'weapon_type',
        'is_standard',
    ];

    protected $casts = [
        'effects' => 'array',
        'value' => 'integer',
        'sell_price' => 'integer',
        'stack_limit' => 'integer',
        'max_durability' => 'integer',
        'is_equippable' => 'boolean',
        'is_usable' => 'boolean',
        'is_standard' => 'boolean',
    ];

    // Scopes
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeEquippable($query)
    {
        return $query->where('is_equippable', true);
    }

    public function scopeUsable($query)
    {
        return $query->where('is_usable', true);
    }

    public function scopeStandard($query)
    {
        return $query->where('is_standard', true);
    }

    public function scopeByWeaponType($query, string $weaponType)
    {
        return $query->where('weapon_type', $weaponType);
    }

    // Accessors
    public function getIsWeaponAttribute(): bool
    {
        return $this->category === 'weapon';
    }

    public function getIsArmorAttribute(): bool
    {
        return in_array($this->category, ['body_equipment', 'foot_equipment', 'shield']);
    }

    public function getIsPotionAttribute(): bool
    {
        return $this->category === 'potion';
    }

    public function getIsMaterialAttribute(): bool
    {
        return $this->category === 'material';
    }

    // Helper methods
    public function hasEffect(string $effectName): bool
    {
        return isset($this->effects[$effectName]);
    }

    public function getEffectValue(string $effectName, $default = 0)
    {
        return $this->effects[$effectName] ?? $default;
    }
}
