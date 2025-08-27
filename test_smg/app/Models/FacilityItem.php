<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityItem extends Model
{
    protected $table = 'facility_items';

    protected $fillable = [
        'facility_id',
        'item_id',
        'price',
        'stock',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(TownFacility::class, 'facility_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function isInStock(): bool
    {
        return $this->stock === -1 || $this->stock > 0;
    }

    public function decreaseStock(int $quantity = 1): bool
    {
        if ($this->stock === -1) {
            return true;
        }

        if ($this->stock >= $quantity) {
            $this->stock -= $quantity;
            $this->save();
            return true;
        }

        return false;
    }
}