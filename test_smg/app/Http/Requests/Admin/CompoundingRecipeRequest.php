<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CompoundingRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'recipe_key' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'product_item_id' => 'required|integer|exists:items,id',
            'product_quantity' => 'required|integer|min:1|max:999',
            'required_skill_level' => 'required|integer|min:1|max:99',
            'success_rate' => 'required|integer|min:1|max:100',
            'sp_cost' => 'required|integer|min:0|max:9999',
            'base_exp' => 'required|integer|min:0|max:999999',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'ingredients' => 'nullable|array',
            'ingredients.*.item_id' => 'required|integer|exists:items,id',
            'ingredients.*.quantity' => 'required|integer|min:1|max:999',
            'locations' => 'nullable|array',
            'locations.*' => 'required|string|max:100',
        ];
    }
}
