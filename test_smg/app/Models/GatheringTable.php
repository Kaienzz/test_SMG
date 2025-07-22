<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatheringTable extends Model
{
    protected $fillable = [
        'road_id',
        'item_name',
        'required_skill_level',
        'success_rate',
        'quantity_min',
        'quantity_max',
        'rarity',
    ];

    protected $casts = [
        'required_skill_level' => 'integer',
        'success_rate' => 'integer',
        'quantity_min' => 'integer',
        'quantity_max' => 'integer',
        'rarity' => 'integer',
    ];

    public static function getGatheringTableByRoad(string $roadId): array
    {
        $tables = [
            'road_1' => [
                ['item_name' => '薬草', 'required_skill_level' => 1, 'success_rate' => 80, 'quantity_min' => 1, 'quantity_max' => 2, 'rarity' => 1],
                ['item_name' => '木の枝', 'required_skill_level' => 1, 'success_rate' => 90, 'quantity_min' => 1, 'quantity_max' => 3, 'rarity' => 1],
                ['item_name' => '小さな石', 'required_skill_level' => 2, 'success_rate' => 70, 'quantity_min' => 1, 'quantity_max' => 2, 'rarity' => 2],
            ],
            'road_2' => [
                ['item_name' => 'ポーション', 'required_skill_level' => 3, 'success_rate' => 60, 'quantity_min' => 1, 'quantity_max' => 1, 'rarity' => 3],
                ['item_name' => 'エーテル', 'required_skill_level' => 5, 'success_rate' => 40, 'quantity_min' => 1, 'quantity_max' => 1, 'rarity' => 4],
                ['item_name' => '鉄鉱石', 'required_skill_level' => 4, 'success_rate' => 50, 'quantity_min' => 1, 'quantity_max' => 2, 'rarity' => 3],
                ['item_name' => '薬草', 'required_skill_level' => 1, 'success_rate' => 85, 'quantity_min' => 1, 'quantity_max' => 3, 'rarity' => 1],
            ],
            'road_3' => [
                ['item_name' => 'ハイポーション', 'required_skill_level' => 7, 'success_rate' => 30, 'quantity_min' => 1, 'quantity_max' => 1, 'rarity' => 5],
                ['item_name' => 'ハイエーテル', 'required_skill_level' => 8, 'success_rate' => 25, 'quantity_min' => 1, 'quantity_max' => 1, 'rarity' => 5],
                ['item_name' => '貴重な鉱石', 'required_skill_level' => 6, 'success_rate' => 35, 'quantity_min' => 1, 'quantity_max' => 1, 'rarity' => 4],
                ['item_name' => '古代の遺物', 'required_skill_level' => 10, 'success_rate' => 15, 'quantity_min' => 1, 'quantity_max' => 1, 'rarity' => 6],
            ],
        ];

        return $tables[$roadId] ?? [];
    }

    public static function getAvailableItems(string $roadId, int $skillLevel): array
    {
        $table = self::getGatheringTableByRoad($roadId);
        
        return array_filter($table, function($item) use ($skillLevel) {
            return $item['required_skill_level'] <= $skillLevel;
        });
    }

    public static function rollForItem(array $item): array
    {
        $success = mt_rand(1, 100) <= $item['success_rate'];
        
        if (!$success) {
            return ['success' => false, 'item' => null, 'quantity' => 0];
        }

        $quantity = mt_rand($item['quantity_min'], $item['quantity_max']);
        
        return [
            'success' => true,
            'item' => $item['item_name'],
            'quantity' => $quantity,
            'rarity' => $item['rarity'],
        ];
    }
}