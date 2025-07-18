<?php

namespace App\Models;

use App\Enums\ItemCategory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'character_id',
        'slot_data',
        'max_slots',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'slot_data' => 'array',
        'max_slots' => 'integer',
    ];

    public const DEFAULT_MAX_SLOTS = 10;

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function getMaxSlots(): int
    {
        return $this->max_slots ?? self::DEFAULT_MAX_SLOTS;
    }

    public function getSlotData(): array
    {
        return $this->slot_data ?? [];
    }

    public function setSlotData(array $data): void
    {
        $this->slot_data = $data;
    }

    public function getUsedSlots(): int
    {
        $slots = $this->getSlotData();
        return count(array_filter($slots, fn($slot) => !empty($slot)));
    }

    public function getAvailableSlots(): int
    {
        return $this->getMaxSlots() - $this->getUsedSlots();
    }

    public function isFull(): bool
    {
        return $this->getAvailableSlots() <= 0;
    }

    public function findItemSlot(int $itemId): ?int
    {
        $slots = $this->getSlotData();
        
        for ($i = 0; $i < $this->getMaxSlots(); $i++) {
            if (isset($slots[$i]) && $slots[$i]['item_id'] === $itemId) {
                return $i;
            }
        }
        
        return null;
    }

    public function findStackableSlot(Item $item): ?int
    {
        if (!(method_exists($item, 'hasStackLimit') && $item->hasStackLimit())) {
            return null;
        }

        $slots = $this->getSlotData();
        $stackLimit = method_exists($item, 'getStackLimit') ? $item->getStackLimit() : 50;
        
        for ($i = 0; $i < $this->getMaxSlots(); $i++) {
            if (isset($slots[$i]) && 
                $slots[$i]['item_id'] === $item->id && 
                $slots[$i]['quantity'] < $stackLimit) {
                return $i;
            }
        }
        
        return null;
    }

    public function findEmptySlot(): ?int
    {
        $slots = $this->getSlotData();
        
        for ($i = 0; $i < $this->getMaxSlots(); $i++) {
            if (!isset($slots[$i]) || empty($slots[$i])) {
                return $i;
            }
        }
        
        return null;
    }

    public function addItem(Item $item, int $quantity = 1, ?int $durability = null): array
    {
        $slots = $this->getSlotData();
        $addedQuantity = 0;
        $remainingQuantity = $quantity;

        // スタック可能アイテムの場合、既存スロットに追加を試みる
        if (method_exists($item, 'hasStackLimit') && $item->hasStackLimit()) {
            $stackableSlot = $this->findStackableSlot($item);
            
            if ($stackableSlot !== null) {
                $currentQuantity = $slots[$stackableSlot]['quantity'];
                $maxAddable = (method_exists($item, 'getStackLimit') ? $item->getStackLimit() : 50) - $currentQuantity;
                $toAdd = min($maxAddable, $remainingQuantity);
                
                $slots[$stackableSlot]['quantity'] += $toAdd;
                $addedQuantity += $toAdd;
                $remainingQuantity -= $toAdd;
            }
        }

        // 残りの数量を新しいスロットに追加
        while ($remainingQuantity > 0) {
            $emptySlot = $this->findEmptySlot();
            
            if ($emptySlot === null) {
                break; // インベントリが満杯
            }

            $toAdd = (method_exists($item, 'hasStackLimit') && $item->hasStackLimit()) ? 
                min((method_exists($item, 'getStackLimit') ? $item->getStackLimit() : 50), $remainingQuantity) : 
                1;

            $slots[$emptySlot] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'quantity' => $toAdd,
                'durability' => $durability ?? (method_exists($item, 'hasDurability') && $item->hasDurability() ? 
                    (method_exists($item, 'getMaxDurability') ? $item->getMaxDurability() : 100) : null),
                'category' => $item->category ? $item->category->value : '',
                'item_info' => [
                    'id' => $item->id ?? 0,
                    'name' => $item->name ?? '',
                    'description' => $item->description ?? '',
                    'category' => $item->category ? $item->category->value : '',
                    'category_name' => $item->category ? $item->category->getDisplayName() : '',
                    'effects' => $item->effects ?? [],
                    'rarity' => $item->rarity ?? 1,
                    'rarity_name' => $this->getRarityName($item->rarity ?? 1),
                    'rarity_color' => $this->getRarityColor($item->rarity ?? 1),
                    'is_equippable' => $item->isEquippable() ?? false,
                    'is_usable' => $item->isUsable() ?? false,
                    'max_durability' => $item->max_durability ?? (method_exists($item, 'getMaxDurability') ? $item->getMaxDurability() : 100),
                ],
            ];

            $addedQuantity += $toAdd;
            $remainingQuantity -= $toAdd;

            if (!(method_exists($item, 'hasStackLimit') && $item->hasStackLimit())) {
                $remainingQuantity--; // 装備品は1個ずつしか追加できない
            }
        }

        $this->setSlotData($slots);

        return [
            'success' => $addedQuantity > 0,
            'added_quantity' => $addedQuantity,
            'remaining_quantity' => $remainingQuantity,
            'message' => $this->getAddItemMessage($item, $addedQuantity, $remainingQuantity),
        ];
    }

    public function removeItem(int $slotIndex, int $quantity = 1): array
    {
        $slots = $this->getSlotData();
        
        if (!isset($slots[$slotIndex]) || empty($slots[$slotIndex])) {
            return [
                'success' => false,
                'message' => 'そのスロットにはアイテムがありません',
            ];
        }

        $slot = $slots[$slotIndex];
        $currentQuantity = $slot['quantity'];
        
        if ($quantity >= $currentQuantity) {
            // アイテムを完全に削除
            unset($slots[$slotIndex]);
            $removedQuantity = $currentQuantity;
        } else {
            // 数量を減らす
            $slots[$slotIndex]['quantity'] -= $quantity;
            $removedQuantity = $quantity;
        }

        $this->setSlotData($slots);

        return [
            'success' => true,
            'removed_quantity' => $removedQuantity,
            'message' => "{$slot['item_name']}を{$removedQuantity}個削除しました",
        ];
    }

    public function useItem(int $slotIndex, Character $character): array
    {
        $slots = $this->getSlotData();
        
        if (!isset($slots[$slotIndex]) || empty($slots[$slotIndex])) {
            return [
                'success' => false,
                'message' => 'そのスロットにはアイテムがありません',
            ];
        }

        $slot = $slots[$slotIndex];
        $item = Item::findSampleItem($slot['item_name']);
        
        if (!$item) {
            return [
                'success' => false,
                'message' => "アイテム '{$slot['item_name']}' が見つかりません",
            ];
        }
        
        if (!$item->isUsable()) {
            return [
                'success' => false,
                'message' => 'このアイテムは使用できません',
            ];
        }

        // アイテム効果を適用
        $effects = $item->getEffects();
        $effectResults = [];
        $shouldConsumeItem = false;

        foreach ($effects as $effectType => $effectValue) {
            switch ($effectType) {
                case 'heal_hp':
                    if ($character->hp >= $character->max_hp) {
                        $effectResults[] = "HPは満タンです。";
                    } else {
                        $oldHp = $character->hp;
                        $character->heal($effectValue);
                        $actualHealing = $character->hp - $oldHp;
                        $effectResults[] = "HPが{$actualHealing}回復";
                        $shouldConsumeItem = true;
                    }
                    break;
                    
                case 'heal_mp':
                    if ($character->mp >= $character->max_mp) {
                        $effectResults[] = "MPは満タンです。";
                    } else {
                        $oldMp = $character->mp;
                        $character->restoreMP($effectValue);
                        $actualRestore = $character->mp - $oldMp;
                        $effectResults[] = "MPが{$actualRestore}回復";
                        $shouldConsumeItem = true;
                    }
                    break;
                    
                case 'heal_sp':
                    if ($character->sp >= $character->max_sp) {
                        $effectResults[] = "SPは満タンです。";
                    } else {
                        $oldSp = $character->sp;
                        $character->restoreSP($effectValue);
                        $actualRestore = $character->sp - $oldSp;
                        $effectResults[] = "SPが{$actualRestore}回復";
                        $shouldConsumeItem = true;
                    }
                    break;
            }
        }

        // アイテムを1個減らす（効果があった場合のみ）
        if ($shouldConsumeItem) {
            $removeResult = $this->removeItem($slotIndex, 1);
        }

        $message = $shouldConsumeItem ? 
            "{$item->name}を使用しました。" . implode('、', $effectResults) :
            implode('、', $effectResults);

        return [
            'success' => true,
            'message' => $message,
            'effects' => $effectResults,
            'character' => $character->getStatusSummary(),
        ];
    }

    public function getInventoryData(): array
    {
        $slots = $this->getSlotData();
        $inventorySlots = [];

        for ($i = 0; $i < $this->getMaxSlots(); $i++) {
            if (isset($slots[$i]) && !empty($slots[$i])) {
                $slot = $slots[$i];
                $item = Item::findSampleItem($slot['item_name']);
                
                // item_info が存在しない場合は、アイテムから取得して max_durability を追加
                $itemInfo = $slot['item_info'] ?? null;
                if (!$itemInfo && $item) {
                    $itemInfo = $item->getItemInfo();
                    $itemInfo['max_durability'] = $item->max_durability ?? ($item->getMaxDurability() ?? 100);
                    
                    // スロットデータを更新
                    $slots[$i]['item_info'] = $itemInfo;
                } elseif ($itemInfo && $item && !isset($itemInfo['max_durability'])) {
                    // item_info は存在するが max_durability がない場合
                    $itemInfo['max_durability'] = $item->max_durability ?? ($item->getMaxDurability() ?? 100);
                    $slots[$i]['item_info'] = $itemInfo;
                }
                
                $inventorySlots[$i] = [
                    'slot_index' => $i,
                    'item_name' => $slot['item_name'],
                    'quantity' => $slot['quantity'],
                    'durability' => $slot['durability'] ?? null,
                    'item_info' => $itemInfo,
                ];
            } else {
                $inventorySlots[$i] = [
                    'slot_index' => $i,
                    'empty' => true,
                ];
            }
        }
        
        // 更新されたスロットデータを保存
        $this->setSlotData($slots);

        return [
            'slots' => $inventorySlots,
            'max_slots' => $this->getMaxSlots(),
            'used_slots' => $this->getUsedSlots(),
            'available_slots' => $this->getAvailableSlots(),
            'is_full' => $this->isFull(),
        ];
    }

    public function expandSlots(int $additionalSlots): void
    {
        $this->max_slots = $this->getMaxSlots() + $additionalSlots;
    }

    private function getAddItemMessage(Item $item, int $addedQuantity, int $remainingQuantity): string
    {
        $message = "{$item->name}を{$addedQuantity}個追加しました";
        
        if ($remainingQuantity > 0) {
            $message .= "（{$remainingQuantity}個は追加できませんでした）";
        }
        
        return $message;
    }

    public static function createForCharacter(int $characterId): self
    {
        return new self([
            'character_id' => $characterId,
            'slot_data' => [],
            'max_slots' => self::DEFAULT_MAX_SLOTS,
        ]);
    }

    public function addSampleItems(): void
    {
        $sampleItems = [
            ['name' => '薬草', 'quantity' => 5],
            ['name' => 'マナポーション', 'quantity' => 3],
            ['name' => '鉄の剣', 'quantity' => 1],
            ['name' => '革の鎧', 'quantity' => 1],
            ['name' => '鉄鉱石', 'quantity' => 10],
        ];

        foreach ($sampleItems as $itemData) {
            $item = Item::findSampleItem($itemData['name']);
            if ($item) {
                $this->addItem($item, $itemData['quantity']);
            }
        }
    }
    
    private function getRarityName(int $rarity): string
    {
        return match($rarity) {
            1 => 'コモン',
            2 => 'アンコモン', 
            3 => 'レア',
            4 => 'エピック',
            5 => 'レジェンダリー',
            6 => 'ミシック',
            default => 'コモン',
        };
    }
    
    private function getRarityColor(int $rarity): string
    {
        return match($rarity) {
            1 => '#9ca3af', // gray
            2 => '#22c55e', // green
            3 => '#3b82f6', // blue
            4 => '#a855f7', // purple
            5 => '#f59e0b', // yellow
            6 => '#ef4444', // red
            default => '#9ca3af',
        };
    }
}