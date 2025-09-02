<?php

namespace App\Services;

use App\Models\TownFacility;
use App\Models\Player;
use App\Models\Item;
use App\Models\CompoundingRecipe;
use App\Models\CompoundingRecipeLocation;
use App\Enums\FacilityType;

class CompoundingFacilityService extends AbstractFacilityService
{
    public function __construct()
    {
        parent::__construct(FacilityType::COMPOUNDING_SHOP);
    }

    public function getAvailableServices(TownFacility $facility): array
    {
        $recipes = $this->getAvailableRecipesAtLocation($facility->location_id);

        return [
            'compounding' => [
                'name' => '調合',
                'description' => '材料から消耗品などを調合します',
                'recipes' => $recipes,
            ],
        ];
    }

    public function validateTransactionData(array $data): bool
    {
        return isset($data['recipe_id']) && is_numeric($data['recipe_id']) &&
               isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] >= 1;
    }

    public function processTransaction(TownFacility $facility, Player $player, array $data): array
    {
        if (!$this->validateTransactionData($data)) {
            return $this->createErrorResponse('無効なリクエストです。');
        }

        $recipeId = (int)$data['recipe_id'];
        $quantity = (int)$data['quantity'];

        $recipe = CompoundingRecipe::where('id', $recipeId)
            ->where('is_active', true)
            ->first();
        if (!$recipe) {
            return $this->createErrorResponse('レシピが見つかりません。', 404);
        }

        $assigned = CompoundingRecipeLocation::where('recipe_id', $recipe->id)
            ->where('location_id', $facility->location_id)
            ->where('is_active', true)
            ->exists();
        if (!$assigned) {
            return $this->createErrorResponse('この町では利用できないレシピです。');
        }

        // スキルチェック（Phase1: 所持していなければ自動習得）
        $skill = $player->getSkill('調合');
        if (!$skill) {
            $skill = $player->learnSkill('production', '調合', [], 15, 0);
        }

        if ($skill->level < $recipe->required_skill_level) {
            return $this->createErrorResponse('スキルレベルが不足しています。');
        }

        // SPチェック
        $totalSp = ($recipe->sp_cost ?? 15) * $quantity;
        if ($player->sp < $totalSp) {
            return $this->createErrorResponse('SPが不足しています。');
        }

        // 材料チェック
        $inventory = $player->getInventory();
        $inventoryData = $inventory->getInventoryData();
        $ingredients = $recipe->ingredients()->get();

        // 集計: アイテム名ベースでスロットと数量を把握
        $slotByName = [];
        foreach ($inventoryData as $index => $slot) {
            if (!empty($slot['empty'])) continue;
            $name = $slot['item_info']['name'] ?? $slot['item_name'] ?? null;
            if ($name) {
                $slotByName[$name][] = ['index' => $index, 'quantity' => $slot['quantity']];
            }
        }

        $needMap = [];
        foreach ($ingredients as $ing) {
            $item = Item::find($ing->item_id);
            if (!$item) {
                return $this->createErrorResponse('材料アイテム定義が見つかりません。');
            }
            $itemName = $item->name;
            $required = $ing->quantity * $quantity;
            $owned = 0;
            foreach ($slotByName[$itemName] ?? [] as $s) { $owned += $s['quantity']; }
            if ($owned < $required) {
                return $this->createErrorResponse("材料が不足しています: {$itemName}");
            }
            $needMap[$itemName] = $required;
        }

        // 在庫空き確認（Phase1: 全量作成できる空きがないと実行不可）
        $productItem = Item::find($recipe->product_item_id);
        if (!$productItem) {
            return $this->createErrorResponse('成果物アイテムが見つかりません。');
        }
        $totalProducts = $recipe->product_quantity * $quantity;

        // 試算: 既存スタック余地 + 空スロット
        $slots = $inventory->getSlotData();
        $stackLimit = $productItem->hasStackLimit() ? $productItem->getStackLimit() : 1;
        $existingSpace = 0;
        for ($i=0; $i<$inventory->getMaxSlots(); $i++) {
            if (isset($slots[$i]) && !empty($slots[$i]) && (($slots[$i]['item_name'] ?? '') === $productItem->name)) {
                $existingSpace += max(0, $stackLimit - ($slots[$i]['quantity'] ?? 0));
            }
        }
        $emptySlots = 0;
        for ($i=0; $i<$inventory->getMaxSlots(); $i++) {
            if (!isset($slots[$i]) || empty($slots[$i])) { $emptySlots++; }
        }
        $capacity = $existingSpace + ($productItem->hasStackLimit() ? ($emptySlots * $stackLimit) : $emptySlots);
        if ($capacity < $totalProducts) {
            return $this->createErrorResponse('インベントリの空きが足りません。');
        }

        // 実行
        try {
            \DB::beginTransaction();

            // SP 消費
            $player->consumeSP($totalSp);
            $player->save();

            // 材料消費
            foreach ($needMap as $itemName => $req) {
                foreach ($slotByName[$itemName] as $entry) {
                    if ($req <= 0) break;
                    $use = min($entry['quantity'], $req);
                    $inventory->removeItem($entry['index'], $use);
                    $req -= $use;
                }
            }

            // 成功判定と成果物追加
            $success = 0; $fail = 0;
            for ($i=0; $i<$quantity; $i++) {
                $roll = mt_rand(1, 100);
                if ($roll <= ($recipe->success_rate ?? 100)) {
                    $success++;
                } else {
                    $fail++;
                }
            }

            $added = 0;
            if ($success > 0) {
                $total = $success * $recipe->product_quantity;
                $addResult = $inventory->addItem($productItem, $total);
                $inventory->save();
                $added = $addResult['added_quantity'] ?? 0;
            }

            // スキルEXP
            $exp = ($recipe->base_exp ?? 100) * $success;
            if ($exp > 0) {
                $skill->gainExperience($exp);
            }

            \DB::commit();

            return $this->createSuccessResponse('調合を実行しました。', [
                'crafted' => [
                    'product_item' => $productItem->getItemInfo(),
                    'success_count' => $success,
                    'fail_count' => $fail,
                    'added_quantity' => $added,
                ],
                'exp_gain' => $exp,
                'sp_spent' => $totalSp,
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->createErrorResponse('調合に失敗しました: '.$e->getMessage());
        }
    }

    private function getAvailableRecipesAtLocation(string $locationId): array
    {
        $recipeIds = CompoundingRecipeLocation::where('location_id', $locationId)
            ->where('is_active', true)
            ->pluck('recipe_id');

        return CompoundingRecipe::whereIn('id', $recipeIds)
            ->where('is_active', true)
            ->with('ingredients')
            ->get()
            ->map(function ($r) {
                $product = Item::find($r->product_item_id);
                return [
                    'id' => $r->id,
                    'recipe_key' => $r->recipe_key,
                    'name' => $r->name,
                    'product' => $product?->getItemInfo(),
                    'product_quantity' => $r->product_quantity,
                    'required_skill_level' => $r->required_skill_level,
                    'success_rate' => $r->success_rate,
                    'sp_cost' => $r->sp_cost,
                    'base_exp' => $r->base_exp,
                    'ingredients' => $r->ingredients->map(function ($ing) {
                        $item = Item::find($ing->item_id);
                        return [
                            'item' => $item?->getItemInfo(),
                            'quantity' => $ing->quantity,
                        ];
                    })->toArray(),
                ];
            })->toArray();
    }
}
