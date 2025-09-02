<?php

namespace App\Services\Admin;

use App\Models\CompoundingRecipe;
use App\Models\CompoundingRecipeIngredient;
use App\Models\CompoundingRecipeLocation;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

class AdminCompoundingService
{
    public function listRecipes(array $filters = [])
    {
        $query = CompoundingRecipe::query();

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('recipe_key', 'like', "%$s%");
            });
        }
        if (isset($filters['active'])) {
            $query->where('is_active', (bool)$filters['active']);
        }

        return $query->with(['ingredients', 'locations'])->orderBy('name')->paginate(20);
    }

    public function getRecipe(int $id): ?CompoundingRecipe
    {
        return CompoundingRecipe::with(['ingredients', 'locations'])->find($id);
    }

    public function getItemsForSelect(): array
    {
        return Item::orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function create(array $data): CompoundingRecipe
    {
        return DB::transaction(function () use ($data) {
            $recipe = CompoundingRecipe::create([
                'recipe_key' => $data['recipe_key'],
                'name' => $data['name'],
                'product_item_id' => (int)$data['product_item_id'],
                'product_quantity' => (int)($data['product_quantity'] ?? 1),
                'required_skill_level' => (int)($data['required_skill_level'] ?? 1),
                'success_rate' => (int)($data['success_rate'] ?? 100),
                'sp_cost' => (int)($data['sp_cost'] ?? 15),
                'base_exp' => (int)($data['base_exp'] ?? 100),
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool)($data['is_active'] ?? true),
            ]);

            $this->syncIngredients($recipe->id, $data['ingredients'] ?? []);
            $this->syncLocations($recipe->id, $data['locations'] ?? []);

            return $recipe;
        });
    }

    public function update(int $id, array $data): ?CompoundingRecipe
    {
        return DB::transaction(function () use ($id, $data) {
            $recipe = CompoundingRecipe::find($id);
            if (!$recipe) return null;

            $recipe->fill([
                'recipe_key' => $data['recipe_key'],
                'name' => $data['name'],
                'product_item_id' => (int)$data['product_item_id'],
                'product_quantity' => (int)($data['product_quantity'] ?? 1),
                'required_skill_level' => (int)($data['required_skill_level'] ?? 1),
                'success_rate' => (int)($data['success_rate'] ?? 100),
                'sp_cost' => (int)($data['sp_cost'] ?? 15),
                'base_exp' => (int)($data['base_exp'] ?? 100),
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool)($data['is_active'] ?? true),
            ])->save();

            $this->syncIngredients($recipe->id, $data['ingredients'] ?? []);
            $this->syncLocations($recipe->id, $data['locations'] ?? []);

            return $recipe;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $recipe = CompoundingRecipe::find($id);
            if (!$recipe) return false;
            // child rows cascade via FK
            return (bool)$recipe->delete();
        });
    }

    private function syncIngredients(int $recipeId, array $ingredients): void
    {
        // Expected format: [ ['item_id' => X, 'quantity' => Y], ... ]
        $keepIds = [];
        foreach ($ingredients as $ing) {
            if (empty($ing['item_id']) || empty($ing['quantity'])) continue;
            $existing = CompoundingRecipeIngredient::where('recipe_id', $recipeId)
                ->where('item_id', (int)$ing['item_id'])
                ->first();
            if ($existing) {
                $existing->quantity = (int)$ing['quantity'];
                $existing->save();
                $keepIds[] = $existing->id;
            } else {
                $new = CompoundingRecipeIngredient::create([
                    'recipe_id' => $recipeId,
                    'item_id' => (int)$ing['item_id'],
                    'quantity' => (int)$ing['quantity'],
                ]);
                $keepIds[] = $new->id;
            }
        }

        // Remove others
        if (!empty($keepIds)) {
            CompoundingRecipeIngredient::where('recipe_id', $recipeId)
                ->whereNotIn('id', $keepIds)
                ->delete();
        } else {
            CompoundingRecipeIngredient::where('recipe_id', $recipeId)->delete();
        }
    }

    private function syncLocations(int $recipeId, array $locations): void
    {
        // Expected: array of location_id strings
        CompoundingRecipeLocation::where('recipe_id', $recipeId)->delete();
        foreach ($locations as $loc) {
            if (!$loc) continue;
            CompoundingRecipeLocation::create([
                'recipe_id' => $recipeId,
                'location_id' => (string)$loc,
                'is_active' => true,
            ]);
        }
    }
}
