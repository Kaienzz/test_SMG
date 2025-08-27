<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Route;
use App\Models\Item;
use App\Models\GatheringMapping;
use App\Models\GatheringTable;
use App\Enums\ItemCategory;

class GatheringDataMigrationSeeder extends Seeder
{
    /**
     * 既存のGatheringTableの硬直化データをデータベースに移行
     */
    public function run(): void
    {
        $this->command->info('Starting migration of hardcoded gathering data to database...');

        // アイテムのマッピング定義（アイテム名 => [カテゴリ, 説明]）
        $itemDefinitions = [
            '薬草' => [
                'category' => ItemCategory::MATERIAL,
                'description' => '基本的な回復アイテムの材料となる薬草',
                'value' => 10,
            ],
            '木の枝' => [
                'category' => ItemCategory::MATERIAL,
                'description' => '軽くて丈夫な木の枝。杖の材料などに使われる',
                'value' => 5,
            ],
            '小さな石' => [
                'category' => ItemCategory::MATERIAL,
                'description' => '投擲武器や建築材料として使える小さな石',
                'value' => 2,
            ],
            'ポーション' => [
                'category' => ItemCategory::POTION,
                'description' => 'HPを回復するポーション',
                'value' => 50,
            ],
            'エーテル' => [
                'category' => ItemCategory::POTION,
                'description' => 'MPを回復するエーテル',
                'value' => 60,
            ],
            '鉄鉱石' => [
                'category' => ItemCategory::MATERIAL,
                'description' => '武器や防具の材料となる鉄の鉱石',
                'value' => 30,
            ],
            'ハイポーション' => [
                'category' => ItemCategory::POTION,
                'description' => '高品質なHP回復ポーション',
                'value' => 200,
            ],
            'ハイエーテル' => [
                'category' => ItemCategory::POTION,
                'description' => '高品質なMP回復エーテル',
                'value' => 250,
            ],
            '貴重な鉱石' => [
                'category' => ItemCategory::MATERIAL,
                'description' => '希少な鉱物。高級装備の材料に使われる',
                'value' => 150,
            ],
            '古代の遺物' => [
                'category' => ItemCategory::MATERIAL,
                'description' => '古代文明の遺物。非常に価値が高い',
                'value' => 500,
            ]
        ];

        // 1. 不足しているアイテムを作成
        $this->command->info('Creating missing items...');
        $createdItems = [];
        $totalItemsCreated = 0;

        foreach ($itemDefinitions as $itemName => $definition) {
            $existingItem = Item::where('name', $itemName)->first();
            
            if (!$existingItem) {
                $newItem = Item::create([
                    'name' => $itemName,
                    'description' => $definition['description'],
                    'category' => $definition['category'],
                    'value' => $definition['value'],
                    'sell_price' => (int)($definition['value'] * 0.7), // 70% of value
                    'stack_limit' => $definition['category']->getDefaultStackLimit(),
                    'max_durability' => $definition['category']->getDefaultDurability(),
                ]);
                
                $createdItems[$itemName] = $newItem;
                $totalItemsCreated++;
                $this->command->line("  Created: {$itemName} (ID: {$newItem->id})");
            } else {
                $createdItems[$itemName] = $existingItem;
                $this->command->line("  Exists: {$itemName} (ID: {$existingItem->id})");
            }
        }

        $this->command->info("Items created: {$totalItemsCreated}");

        // 2. 硬直化されたGatheringTableデータを取得
        $hardcodedData = $this->getHardcodedGatheringData();
        
        // 3. 採集マッピングを作成
        $this->command->info('Creating gathering mappings...');
        $totalMappingsCreated = 0;
        $totalMappingsSkipped = 0;

        foreach ($hardcodedData as $routeId => $items) {
            // ルートが存在するかチェック
            $route = Route::find($routeId);
            if (!$route) {
                $this->command->warn("  Route not found: {$routeId} - skipping");
                continue;
            }

            $this->command->line("  Processing route: {$route->name} ({$routeId})");

            foreach ($items as $itemData) {
                $itemName = $itemData['item_name'];
                
                // アイテムが存在するかチェック
                if (!isset($createdItems[$itemName])) {
                    $this->command->warn("    Item not found: {$itemName} - skipping");
                    continue;
                }

                $item = $createdItems[$itemName];

                // 既存のマッピングをチェック
                $existingMapping = GatheringMapping::where('route_id', $routeId)
                                                  ->where('item_id', $item->id)
                                                  ->first();

                if ($existingMapping) {
                    $this->command->line("    Mapping exists: {$itemName} - skipping");
                    $totalMappingsSkipped++;
                    continue;
                }

                // 新しい採集マッピングを作成
                $mapping = GatheringMapping::create([
                    'route_id' => $routeId,
                    'item_id' => $item->id,
                    'required_skill_level' => $itemData['required_skill_level'],
                    'success_rate' => $itemData['success_rate'],
                    'quantity_min' => $itemData['quantity_min'],
                    'quantity_max' => $itemData['quantity_max'],
                    'is_active' => true,
                ]);

                $this->command->line("    Created mapping: {$itemName} (Skill: {$itemData['required_skill_level']}, Rate: {$itemData['success_rate']}%)");
                $totalMappingsCreated++;
            }
        }

        // 4. 結果サマリー
        $this->command->info('Migration completed!');
        $this->command->table([
            'Category', 'Count'
        ], [
            ['Items Created', $totalItemsCreated],
            ['Mappings Created', $totalMappingsCreated], 
            ['Mappings Skipped', $totalMappingsSkipped],
        ]);

        // 5. 作成されたマッピングの確認
        $this->command->info('Verification - Created mappings by route:');
        
        foreach ($hardcodedData as $routeId => $items) {
            $route = Route::find($routeId);
            if (!$route) continue;

            $mappingCount = GatheringMapping::where('route_id', $routeId)->count();
            $this->command->line("  {$route->name}: {$mappingCount} mappings");
        }

        $this->command->info('Migration process completed successfully!');
    }

    /**
     * GatheringTableから硬直化データを取得
     */
    private function getHardcodedGatheringData(): array
    {
        return [
            'road_1' => [
                ['item_name' => '薬草', 'required_skill_level' => 1, 'success_rate' => 80, 'quantity_min' => 1, 'quantity_max' => 2],
                ['item_name' => '木の枝', 'required_skill_level' => 1, 'success_rate' => 90, 'quantity_min' => 1, 'quantity_max' => 3],
                ['item_name' => '小さな石', 'required_skill_level' => 2, 'success_rate' => 70, 'quantity_min' => 1, 'quantity_max' => 2],
            ],
            'road_2' => [
                ['item_name' => 'ポーション', 'required_skill_level' => 3, 'success_rate' => 60, 'quantity_min' => 1, 'quantity_max' => 1],
                ['item_name' => 'エーテル', 'required_skill_level' => 5, 'success_rate' => 40, 'quantity_min' => 1, 'quantity_max' => 1],
                ['item_name' => '鉄鉱石', 'required_skill_level' => 4, 'success_rate' => 50, 'quantity_min' => 1, 'quantity_max' => 2],
                ['item_name' => '薬草', 'required_skill_level' => 1, 'success_rate' => 85, 'quantity_min' => 1, 'quantity_max' => 3],
            ],
            'road_3' => [
                ['item_name' => 'ハイポーション', 'required_skill_level' => 7, 'success_rate' => 30, 'quantity_min' => 1, 'quantity_max' => 1],
                ['item_name' => 'ハイエーテル', 'required_skill_level' => 8, 'success_rate' => 25, 'quantity_min' => 1, 'quantity_max' => 1],
                ['item_name' => '貴重な鉱石', 'required_skill_level' => 6, 'success_rate' => 35, 'quantity_min' => 1, 'quantity_max' => 1],
                ['item_name' => '古代の遺物', 'required_skill_level' => 10, 'success_rate' => 15, 'quantity_min' => 1, 'quantity_max' => 1],
            ],
        ];
    }
}