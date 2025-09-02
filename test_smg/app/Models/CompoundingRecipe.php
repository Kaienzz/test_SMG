<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompoundingRecipe extends Model
{
    protected $fillable = [
        'recipe_key',
        'name',
        'product_item_id',
        'product_quantity',
        'required_skill_level',
        'success_rate',
        'sp_cost',
        'base_exp',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'product_item_id' => 'integer',
        'product_quantity' => 'integer',
        'required_skill_level' => 'integer',
        'success_rate' => 'integer',
        'sp_cost' => 'integer',
        'base_exp' => 'integer',
        'is_active' => 'boolean',
    ];

    public function ingredients(): HasMany
    {
        return $this->hasMany(CompoundingRecipeIngredient::class, 'recipe_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(CompoundingRecipeLocation::class, 'recipe_id');
    }
}
