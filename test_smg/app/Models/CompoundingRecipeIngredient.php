<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompoundingRecipeIngredient extends Model
{
    protected $fillable = [
        'recipe_id',
        'item_id',
        'quantity',
    ];

    protected $casts = [
        'recipe_id' => 'integer',
        'item_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(CompoundingRecipe::class, 'recipe_id');
    }
}
