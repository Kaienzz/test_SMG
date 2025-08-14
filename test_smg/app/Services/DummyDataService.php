<?php

namespace App\Services;

class DummyDataService
{
    public static function getPlayerData(int $id = 1): array
    {
        $baseSp = 30;
        $currentSp = session('player_sp', $baseSp);
        $currentGold = session('player_gold', 500); // デフォルト500G
        
        return [
            'id' => $id,
            'name' => '冒険者',
            'level' => 5,
            'experience' => 120,
            'attack' => 15,
            'defense' => 12,
            'agility' => 18,
            'evasion' => 22,
            'hp' => 85,
            'max_hp' => 120,
            'mp' => 45,
            'max_mp' => 80,
            'sp' => $currentSp,
            'max_sp' => 60,
            'accuracy' => 90,
            'magic_attack' => 12,
            'gold' => $currentGold,
        ];
    }

    public static function getPlayerStatusSummary(int $id = 1): array
    {
        $player = self::getPlayerData($id);
        return [
            'name' => $player['name'],
            'level' => $player['level'],
            'hp' => "{$player['hp']}/{$player['max_hp']}",
            'sp' => "{$player['sp']}/{$player['max_sp']}",
            'hp_percentage' => ($player['hp'] / $player['max_hp']) * 100,
            'sp_percentage' => ($player['sp'] / $player['max_sp']) * 100,
            'is_alive' => $player['hp'] > 0,
        ];
    }

    public static function getPlayerDetailedStats(int $id = 1): array
    {
        $player = self::getPlayerData($id);
        return [
            'basic_info' => [
                'name' => $player['name'],
                'level' => $player['level'],
                'experience' => $player['experience'],
            ],
            'combat_stats' => [
                'attack' => $player['attack'],
                'magic_attack' => $player['magic_attack'],
                'defense' => $player['defense'],
                'agility' => $player['agility'],
                'evasion' => $player['evasion'],
                'accuracy' => $player['accuracy'],
            ],
            'vitals' => [
                'hp' => $player['hp'],
                'max_hp' => $player['max_hp'],
                'sp' => $player['sp'],
                'max_sp' => $player['max_sp'],
                'hp_percentage' => ($player['hp'] / $player['max_hp']) * 100,
                'sp_percentage' => ($player['sp'] / $player['max_sp']) * 100,
            ],
        ];
    }

    /**
     * ゲーム標準アイテムマスターデータ
     * 管理画面での確認・バランス調整用
     */
    public static function getStandardItems(): array
    {
        return [
            // ポーション類
            [
                'id' => 'std_1',
                'name' => '薬草',
                'description' => 'HPを5回復する基本的な薬草',
                'category' => 'potion',
                'category_name' => 'ポーション',
                'effects' => ['heal_hp' => 5],
                'value' => 10,
                'sell_price' => 5,
                'stack_limit' => 99,
                'max_durability' => null,
                'rarity_name' => 'コモン',
                'rarity_color' => '#9ca3af',
                'is_equippable' => false,
                'is_usable' => true,
                'weapon_type' => null,
                'is_standard' => true,
            ],
            [
                'id' => 'std_2',
                'name' => 'ポーション',
                'description' => 'HPを15回復する標準的なポーション',
                'category' => 'potion',
                'category_name' => 'ポーション',
                'effects' => ['heal_hp' => 15],
                'value' => 50,
                'sell_price' => 25,
                'stack_limit' => 50,
                'max_durability' => null,
                'rarity_name' => 'コモン',
                'rarity_color' => '#9ca3af',
                'is_equippable' => false,
                'is_usable' => true,
                'weapon_type' => null,
                'is_standard' => true,
            ],
            [
                'id' => 'std_3',
                'name' => 'エーテル',
                'description' => 'SPを10回復する魔法薬',
                'category' => 'potion',
                'category_name' => 'ポーション',
                'effects' => ['heal_sp' => 10],
                'value' => 80,
                'sell_price' => 40,
                'stack_limit' => 50,
                'max_durability' => null,
                'rarity_name' => 'コモン',
                'rarity_color' => '#9ca3af',
                'is_equippable' => false,
                'is_usable' => true,
                'weapon_type' => null,
                'is_standard' => true,
            ],
            
            // 武器類
            [
                'id' => 'std_4',
                'name' => '鉄の剣',
                'description' => '攻撃力+5の基本的な剣',
                'category' => 'weapon',
                'category_name' => '武器',
                'effects' => ['attack' => 5],
                'value' => 200,
                'sell_price' => 100,
                'stack_limit' => 1,
                'max_durability' => 100,
                'rarity_name' => 'コモン',
                'rarity_color' => '#9ca3af',
                'is_equippable' => true,
                'is_usable' => false,
                'weapon_type' => 'physical',
                'is_standard' => true,
            ],
            [
                'id' => 'std_5',
                'name' => '鋼の剣',
                'description' => '攻撃力+8の優秀な剣',
                'category' => 'weapon',
                'category_name' => '武器',
                'effects' => ['attack' => 8],
                'value' => 450,
                'sell_price' => 225,
                'stack_limit' => 1,
                'max_durability' => 120,
                'rarity_name' => 'アンコモン',
                'rarity_color' => '#10b981',
                'is_equippable' => true,
                'is_usable' => false,
                'weapon_type' => 'physical',
                'is_standard' => true,
            ],
            [
                'id' => 'std_6',
                'name' => '魔導の杖',
                'description' => '魔法攻撃力+6の杖',
                'category' => 'weapon',
                'category_name' => '武器',
                'effects' => ['magic_attack' => 6],
                'value' => 380,
                'sell_price' => 190,
                'stack_limit' => 1,
                'max_durability' => 80,
                'rarity_name' => 'アンコモン',
                'rarity_color' => '#10b981',
                'is_equippable' => true,
                'is_usable' => false,
                'weapon_type' => 'magical',
                'is_standard' => true,
            ],
            
            // 防具類
            [
                'id' => 'std_7',
                'name' => '革の鎧',
                'description' => '防御力+3の基本的な鎧',
                'category' => 'body_equipment',
                'category_name' => '胴体装備',
                'effects' => ['defense' => 3],
                'value' => 150,
                'sell_price' => 75,
                'stack_limit' => 1,
                'max_durability' => 80,
                'rarity_name' => 'コモン',
                'rarity_color' => '#9ca3af',
                'is_equippable' => true,
                'is_usable' => false,
                'weapon_type' => null,
                'is_standard' => true,
            ],
            [
                'id' => 'std_8',
                'name' => '疾風のブーツ',
                'description' => '素早さ+8、移動サイコロ+1の風の靴',
                'category' => 'foot_equipment',
                'category_name' => '靴装備',
                'effects' => ['agility' => 8, 'extra_dice' => 1],
                'value' => 320,
                'sell_price' => 160,
                'stack_limit' => 1,
                'max_durability' => 70,
                'rarity_name' => 'レア',
                'rarity_color' => '#3b82f6',
                'is_equippable' => true,
                'is_usable' => false,
                'weapon_type' => null,
                'is_standard' => true,
            ],
            [
                'id' => 'std_9',
                'name' => '木の盾',
                'description' => '防御力+2の簡素な盾',
                'category' => 'shield',
                'category_name' => '盾',
                'effects' => ['defense' => 2],
                'value' => 80,
                'sell_price' => 40,
                'stack_limit' => 1,
                'max_durability' => 90,
                'rarity_name' => 'コモン',
                'rarity_color' => '#9ca3af',
                'is_equippable' => true,
                'is_usable' => false,
                'weapon_type' => null,
                'is_standard' => true,
            ],
            
            // 素材類
            [
                'id' => 'std_10',
                'name' => '鉄鉱石',
                'description' => '武器作成に使用する基本素材',
                'category' => 'material',
                'category_name' => '素材',
                'effects' => [],
                'value' => 25,
                'sell_price' => 12,
                'stack_limit' => 99,
                'max_durability' => null,
                'rarity_name' => 'コモン',
                'rarity_color' => '#9ca3af',
                'is_equippable' => false,
                'is_usable' => false,
                'weapon_type' => null,
                'is_standard' => true,
            ],
        ];
    }

    public static function getInventory(int $playerId = 1): array
    {
        // 標準アイテムから抜粋してインベントリ用に変換
        $standardItems = self::getStandardItems();
        $items = [
            [
                'item' => $standardItems[0], // 薬草
                'quantity' => 5,
                'slot' => 0,
            ],
            [
                'item' => $standardItems[3], // 鉄の剣
                'quantity' => 1,
                'slot' => 1,
            ],
            [
                'item' => $standardItems[7], // 疾風のブーツ
                'quantity' => 1,
                'slot' => 2,
            ],
        ];
        
        $maxSlots = 20;
        $usedSlots = count($items);
        
        // Convert items array to slots format expected by the view
        $slots = [];
        foreach ($items as $item) {
            $slots[$item['slot']] = [
                'item_info' => $item['item'],
                'item_name' => $item['item']['name'],
                'quantity' => $item['quantity'],
                'durability' => null, // Add durability support
            ];
        }
        
        // Fill empty slots
        for ($i = 0; $i < $maxSlots; $i++) {
            if (!isset($slots[$i])) {
                $slots[$i] = ['empty' => true];
            }
        }
        
        return [
            'player_id' => $playerId,
            'max_slots' => $maxSlots,
            'used_slots' => $usedSlots,
            'available_slots' => $maxSlots - $usedSlots,
            'items' => $items,
            'slots' => $slots,
        ];
    }

    public static function getEquipment(int $playerId = 1): array
    {
        return [
            'player_id' => $playerId,
            'weapon_id' => null,
            'body_armor_id' => null,
            'shield_id' => null,
            'helmet_id' => null,
            'boots_id' => null,
            'accessory_id' => null,
        ];
    }

    public static function getEquippedItems(int $playerId = 1): array
    {
        return [
            'weapon' => null,
            'body_armor' => null,
            'shield' => null,
            'helmet' => null,
            'boots' => null,
            'accessory' => null,
        ];
    }

    public static function getEquipmentTotalStats(int $playerId = 1): array
    {
        return [
            'attack' => 0,
            'defense' => 0,
            'agility' => 0,
            'evasion' => 0,
            'hp' => 0,
            'sp' => 0,
            'accuracy' => 0,
            'effects' => [],
        ];
    }

    public static function getSkills(int $playerId = 1): array
    {
        return [
            [
                'id' => 1,
                'name' => '飛脚術',
                'type' => 'movement',
                'level' => 3,
                'experience' => 45,
                'sp_cost' => 10,
                'can_use' => true,
                'effects' => ['dice_bonus' => 3, 'extra_dice' => 1],
            ],
            [
                'id' => 2,
                'name' => '採集',
                'type' => 'gathering',
                'level' => 5,
                'experience' => 120,
                'sp_cost' => 8,
                'can_use' => true,
                'effects' => ['gathering_bonus' => 5],
            ],
        ];
    }

    public static function getActiveEffects(int $playerId = 1): array
    {
        return [
            [
                'id' => 1,
                'effect_name' => '飛脚術効果',
                'source_type' => 'skill',
                'effects' => ['dice_bonus' => 3, 'extra_dice' => 1],
                'duration' => 5,
                'remaining_duration' => 3,
                'is_active' => true,
            ],
        ];
    }

    public static function getGameState(): array
    {
        return [
            'player_name' => 'Player',
            'player_id' => 1,
            'current_location_type' => session('location_type', 'road'),
            'current_location_id' => session('location_id', 'road_1'),
            'position' => session('game_position', 50),
            'game_data' => [],
        ];
    }

    public static function getPlayer(): array
    {
        $gameState = self::getGameState();
        return [
            'name' => $gameState['player_name'],
            'player_id' => $gameState['player_id'],
            'current_location_type' => $gameState['current_location_type'],
            'current_location_id' => $gameState['current_location_id'],
            'position' => $gameState['position'],
        ];
    }

    public static function getCurrentLocation(): array
    {
        $player = self::getPlayer();
        if ($player['current_location_type'] === 'town') {
            return [
                'name' => $player['current_location_id'] === 'town_a' ? 'A町' : 'B町',
                'type' => 'town',
            ];
        }
        
        $roadNames = ['道路1', '道路2', '道路3'];
        $roadIndex = (int) str_replace('road_', '', $player['current_location_id']) - 1;
        
        return [
            'name' => $roadNames[$roadIndex] ?? '道路1',
            'type' => 'road',
            'order' => $roadIndex + 1,
        ];
    }

    public static function getNextLocation(): ?array
    {
        $player = self::getPlayer();
        
        if ($player['current_location_type'] === 'town') {
            if ($player['current_location_id'] === 'town_a') {
                return ['type' => 'road', 'id' => 'road_1', 'name' => '道路1', 'direction' => 'forward'];
            } elseif ($player['current_location_id'] === 'town_b') {
                return ['type' => 'road', 'id' => 'road_3', 'name' => '道路3', 'direction' => 'backward'];
            }
        } elseif ($player['current_location_type'] === 'road') {
            $roadNumber = (int) str_replace('road_', '', $player['current_location_id']);
            
            if ($player['position'] === 0) {
                if ($roadNumber === 1) {
                    return ['type' => 'town', 'id' => 'town_a', 'name' => 'A町', 'direction' => 'backward'];
                } else {
                    return ['type' => 'road', 'id' => 'road_' . ($roadNumber - 1), 'name' => '道路' . ($roadNumber - 1), 'direction' => 'backward'];
                }
            } elseif ($player['position'] === 100) {
                if ($roadNumber === 3) {
                    return ['type' => 'town', 'id' => 'town_b', 'name' => 'B町', 'direction' => 'forward'];
                } else {
                    return ['type' => 'road', 'id' => 'road_' . ($roadNumber + 1), 'name' => '道路' . ($roadNumber + 1), 'direction' => 'forward'];
                }
            }
        }
        
        return null;
    }

    public static function calculateNewPosition(array $nextLocation, int $currentPosition): int
    {
        // 町に移動する場合は常に0
        if ($nextLocation['type'] === 'town') {
            return 0;
        }
        
        // 道路に移動する場合は移動方向に応じて位置を決定
        if ($nextLocation['type'] === 'road') {
            if ($nextLocation['direction'] === 'forward') {
                // 右方向（前進）に移動する場合、新しい道路の左端（0）からスタート
                return 0;
            } elseif ($nextLocation['direction'] === 'backward') {
                // 左方向（後退）に移動する場合、新しい道路の右端（100）からスタート
                return 100;
            }
        }
        
        // デフォルト値
        return 0;
    }

    public static function getSampleEquipmentItems(): array
    {
        return [
            'weapons' => [
                [
                    'name' => '鋼の剣',
                    'description' => '攻撃力+8の良質な剣',
                    'category' => 'weapon',
                    'effects' => ['attack' => 8],
                ],
                [
                    'name' => 'ミスリルソード',
                    'description' => '攻撃力+12、命中力+5の魔法の剣',
                    'category' => 'weapon',
                    'effects' => ['attack' => 12, 'accuracy' => 5],
                ],
                [
                    'name' => '疾風の剣',
                    'description' => '攻撃力+10、素早さ+8の風の剣',
                    'category' => 'weapon',
                    'effects' => ['attack' => 10, 'agility' => 8],
                ],
            ],
            'body_armor' => [
                [
                    'name' => '鋼の鎧',
                    'description' => '防御力+8の頑丈な鎧',
                    'category' => 'body_equipment',
                    'effects' => ['defense' => 8],
                ],
                [
                    'name' => 'ドラゴンスケイル',
                    'description' => '防御力+15、HP+20のドラゴンの鱗の鎧',
                    'category' => 'body_equipment',
                    'effects' => ['defense' => 15, 'hp' => 20],
                ],
            ],
            'shields' => [
                [
                    'name' => '鉄の盾',
                    'description' => '防御力+5の基本的な盾',
                    'category' => 'shield',
                    'effects' => ['defense' => 5],
                ],
                [
                    'name' => '魔法の盾',
                    'description' => '防御力+8、SP+15の魔法の盾',
                    'category' => 'shield',
                    'effects' => ['defense' => 8, 'sp' => 15],
                ],
            ],
            'helmets' => [
                [
                    'name' => '鉄の兜',
                    'description' => '防御力+3の基本的な兜',
                    'category' => 'head_equipment',
                    'effects' => ['defense' => 3],
                ],
                [
                    'name' => '知恵の兜',
                    'description' => '防御力+4、SP+10の賢者の兜',
                    'category' => 'head_equipment',
                    'effects' => ['defense' => 4, 'sp' => 10],
                ],
            ],
            'boots' => [
                [
                    'name' => '革のブーツ',
                    'description' => '素早さ+3の軽い靴',
                    'category' => 'foot_equipment',
                    'effects' => ['agility' => 3],
                ],
                [
                    'name' => '疾風のブーツ',
                    'description' => '素早さ+8、移動サイコロ+1の風の靴',
                    'category' => 'foot_equipment',
                    'effects' => ['agility' => 8, 'extra_dice' => 1],
                ],
            ],
            'accessories' => [
                [
                    'name' => 'パワーリング',
                    'description' => '攻撃力+4を与える指輪',
                    'category' => 'accessory',
                    'effects' => ['attack' => 4],
                ],
                [
                    'name' => '状態異常耐性の指輪',
                    'description' => 'すべての状態異常を無効化する指輪',
                    'category' => 'accessory',
                    'effects' => ['status_immunity' => true],
                ],
                [
                    'name' => '幸運のお守り',
                    'description' => '移動時のサイコロの目+2のお守り',
                    'category' => 'accessory',
                    'effects' => ['dice_bonus' => 2],
                ],
            ],
        ];
    }

    public static function getSampleSkills(): array
    {
        return [
            [
                'skill_type' => 'movement',
                'skill_name' => '飛脚術',
                'description' => 'SP消費でサイコロボーナスと追加サイコロ効果を得る',
                'effects' => ['dice_bonus' => 1],
                'sp_cost' => 12,
                'duration' => 5,
                'max_level' => 10,
            ],
            [
                'skill_type' => 'gathering',
                'skill_name' => '採集',
                'description' => '道で材料や薬草を採集する',
                'effects' => ['gathering_bonus' => 1],
                'sp_cost' => 8,
                'duration' => 0,
                'max_level' => 99,
            ],
        ];
    }

    public static function updatePlayerSp(int $playerId, int $newSp): void
    {
        // セッションベースでの更新（実装時）
        session(['player_sp' => $newSp]);
    }

    public static function updateGamePosition(int $newPosition): void
    {
        // セッションベースでの更新（実装時）
        session(['game_position' => $newPosition]);
    }

    public static function updateGameLocation(string $locationType, string $locationId): void
    {
        // セッションベースでの更新（実装時）
        session([
            'location_type' => $locationType,
            'location_id' => $locationId,
        ]);
    }
}