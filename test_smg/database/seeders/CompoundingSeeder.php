<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\CompoundingRecipe;
use App\Models\CompoundingRecipeIngredient;
use App\Models\CompoundingRecipeLocation;
use App\Enums\ItemCategory;

class CompoundingSeeder extends Seeder
{
    public function run(): void
    {
        // 簡易アイテムの用意（存在しなければ作成）
        $herb = Item::firstOrCreate(
            ['name' => '薬草'],
            [
                'description' => '薬の材料となる草',
                'category' => ItemCategory::MATERIAL->value,
                'effects' => [],
                'value' => 5,
                'stack_limit' => 50,
            ]
        );

        $potion = Item::firstOrCreate(
            ['name' => 'ポーション'],
            [
                'description' => 'HPを50回復する',
                'category' => ItemCategory::POTION->value,
                'effects' => ['heal_hp' => 50],
                'value' => 100,
                'stack_limit' => 50,
            ]
        );

        $recipe = CompoundingRecipe::firstOrCreate(
            ['recipe_key' => 'potion_basic'],
            [
                'name' => 'ポーション調合',
                'product_item_id' => $potion->id,
                'product_quantity' => 1,
                'required_skill_level' => 1,
                'success_rate' => 100,
                'sp_cost' => 15,
                'base_exp' => 100,
                'is_active' => true,
            ]
        );

        CompoundingRecipeIngredient::firstOrCreate(
            ['recipe_id' => $recipe->id, 'item_id' => $herb->id],
            ['quantity' => 3]
        );

        CompoundingRecipeLocation::firstOrCreate(
            ['recipe_id' => $recipe->id, 'location_id' => 'town_prima'],
            ['is_active' => true]
        );
    }
}
