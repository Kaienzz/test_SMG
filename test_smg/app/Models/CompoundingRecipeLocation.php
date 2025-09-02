<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompoundingRecipeLocation extends Model
{
    protected $fillable = [
        'recipe_id',
        'location_id',
        'is_active',
    ];

    protected $casts = [
        'recipe_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(CompoundingRecipe::class, 'recipe_id');
    }
}
