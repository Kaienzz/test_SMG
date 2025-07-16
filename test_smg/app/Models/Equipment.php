<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipment extends Model
{
    protected $fillable = [
        'character_id',
        'weapon_id',
        'body_armor_id',
        'shield_id',
        'helmet_id',
        'boots_id',
        'accessory_id',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'weapon_id' => 'integer',
        'body_armor_id' => 'integer',
        'shield_id' => 'integer',
        'helmet_id' => 'integer',
        'boots_id' => 'integer',
        'accessory_id' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'weapon_id');
    }

    public function bodyArmor(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'body_armor_id');
    }

    public function shield(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'shield_id');
    }

    public function helmet(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'helmet_id');
    }

    public function boots(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'boots_id');
    }

    public function accessory(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'accessory_id');
    }

    public function getTotalStats(): array
    {
        $stats = [
            'attack' => 0,
            'defense' => 0,
            'agility' => 0,
            'evasion' => 0,
            'hp' => 0,
            'mp' => 0,
            'accuracy' => 0,
            'effects' => [],
        ];

        $equipmentSlots = [
            'weapon',
            'bodyArmor',
            'shield',
            'helmet',
            'boots',
            'accessory',
        ];

        foreach ($equipmentSlots as $slot) {
            $item = $this->$slot;
            if ($item) {
                $effects = $item->getEffects();
                foreach ($effects as $effect => $value) {
                    if (isset($stats[$effect])) {
                        $stats[$effect] += $value;
                    } elseif ($effect === 'status_immunity' || $effect === 'dice_bonus' || $effect === 'extra_dice') {
                        $stats['effects'][$effect] = $value;
                    }
                }
            }
        }

        return $stats;
    }

    public function getAvailableBattleSkills(): array
    {
        $skills = [];
        
        $equipmentSlots = [
            'weapon',
            'bodyArmor',
            'shield',
            'helmet',
            'boots',
            'accessory',
        ];

        foreach ($equipmentSlots as $slot) {
            $item = $this->$slot;
            if ($item && $item->hasBattleSkill()) {
                $skill = $item->getBattleSkill();
                if ($skill) {
                    $skills[] = $skill->getSkillInfo();
                }
            }
        }

        return $skills;
    }

    public function getEquippedWeapon(): ?Item
    {
        return $this->weapon;
    }

    public function getWeaponType(): ?string
    {
        $weapon = $this->getEquippedWeapon();
        return $weapon?->weapon_type;
    }

    public function isMagicalWeaponEquipped(): bool
    {
        $weapon = $this->getEquippedWeapon();
        return $weapon && $weapon->isMagicalWeapon();
    }

    public function isPhysicalWeaponEquipped(): bool
    {
        $weapon = $this->getEquippedWeapon();
        return $weapon && $weapon->isPhysicalWeapon();
    }

    public function getEquippedItems(): array
    {
        return [
            'weapon' => $this->weapon?->getItemInfo(),
            'body_armor' => $this->bodyArmor?->getItemInfo(),
            'shield' => $this->shield?->getItemInfo(),
            'helmet' => $this->helmet?->getItemInfo(),
            'boots' => $this->boots?->getItemInfo(),
            'accessory' => $this->accessory?->getItemInfo(),
        ];
    }

    public function equipItem(Item $item, string $slot): bool
    {
        if (!$this->canEquipItemInSlot($item, $slot)) {
            return false;
        }

        $slotColumn = $this->getSlotColumn($slot);
        if (!$slotColumn) {
            return false;
        }

        $this->$slotColumn = $item->id;
        $this->save();

        return true;
    }

    public function unequipSlot(string $slot): bool
    {
        $slotColumn = $this->getSlotColumn($slot);
        if (!$slotColumn) {
            return false;
        }

        $this->$slotColumn = null;
        $this->save();

        return true;
    }

    private function canEquipItemInSlot(Item $item, string $slot): bool
    {
        $slotCategoryMap = [
            'weapon' => ['weapon'],
            'body_armor' => ['body_equipment'],
            'shield' => ['shield'],
            'helmet' => ['head_equipment'],
            'boots' => ['foot_equipment'],
            'accessory' => ['accessory'],
        ];

        if (!isset($slotCategoryMap[$slot])) {
            return false;
        }

        $allowedCategories = $slotCategoryMap[$slot];
        return in_array($item->category->value, $allowedCategories);
    }

    private function getSlotColumn(string $slot): ?string
    {
        $slotColumnMap = [
            'weapon' => 'weapon_id',
            'body_armor' => 'body_armor_id',
            'shield' => 'shield_id',
            'helmet' => 'helmet_id',
            'boots' => 'boots_id',
            'accessory' => 'accessory_id',
        ];

        return $slotColumnMap[$slot] ?? null;
    }

    public static function createForCharacter(int $characterId): self
    {
        return self::create([
            'character_id' => $characterId,
        ]);
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
                    'rarity' => 2,
                ],
                [
                    'name' => 'ミスリルソード',
                    'description' => '攻撃力+12、命中力+5の魔法の剣',
                    'category' => 'weapon',
                    'effects' => ['attack' => 12, 'accuracy' => 5],
                    'rarity' => 3,
                ],
                [
                    'name' => '疾風の剣',
                    'description' => '攻撃力+10、素早さ+8の風の剣',
                    'category' => 'weapon',
                    'effects' => ['attack' => 10, 'agility' => 8],
                    'rarity' => 3,
                ],
            ],
            'body_armor' => [
                [
                    'name' => '鋼の鎧',
                    'description' => '防御力+8の頑丈な鎧',
                    'category' => 'body_equipment',
                    'effects' => ['defense' => 8],
                    'rarity' => 2,
                ],
                [
                    'name' => 'ドラゴンスケイル',
                    'description' => '防御力+15、HP+20のドラゴンの鱗の鎧',
                    'category' => 'body_equipment',
                    'effects' => ['defense' => 15, 'hp' => 20],
                    'rarity' => 4,
                ],
                [
                    'name' => '影の外套',
                    'description' => '防御力+6、回避+12の闇の外套',
                    'category' => 'body_equipment',
                    'effects' => ['defense' => 6, 'evasion' => 12],
                    'rarity' => 3,
                ],
            ],
            'shields' => [
                [
                    'name' => '鉄の盾',
                    'description' => '防御力+5の基本的な盾',
                    'category' => 'shield',
                    'effects' => ['defense' => 5],
                    'rarity' => 1,
                ],
                [
                    'name' => '魔法の盾',
                    'description' => '防御力+8、MP+15の魔法の盾',
                    'category' => 'shield',
                    'effects' => ['defense' => 8, 'mp' => 15],
                    'rarity' => 3,
                ],
            ],
            'helmets' => [
                [
                    'name' => '鉄の兜',
                    'description' => '防御力+3の基本的な兜',
                    'category' => 'head_equipment',
                    'effects' => ['defense' => 3],
                    'rarity' => 1,
                ],
                [
                    'name' => '知恵の兜',
                    'description' => '防御力+4、MP+10の賢者の兜',
                    'category' => 'head_equipment',
                    'effects' => ['defense' => 4, 'mp' => 10],
                    'rarity' => 2,
                ],
            ],
            'boots' => [
                [
                    'name' => '革のブーツ',
                    'description' => '素早さ+3の軽い靴',
                    'category' => 'foot_equipment',
                    'effects' => ['agility' => 3],
                    'rarity' => 1,
                ],
                [
                    'name' => '疾風のブーツ',
                    'description' => '素早さ+8、移動サイコロ+1の風の靴',
                    'category' => 'foot_equipment',
                    'effects' => ['agility' => 8, 'extra_dice' => 1],
                    'rarity' => 3,
                ],
            ],
            'accessories' => [
                [
                    'name' => 'パワーリング',
                    'description' => '攻撃力+4を与える指輪',
                    'category' => 'accessory',
                    'effects' => ['attack' => 4],
                    'rarity' => 2,
                ],
                [
                    'name' => '状態異常耐性の指輪',
                    'description' => 'すべての状態異常を無効化する指輪',
                    'category' => 'accessory',
                    'effects' => ['status_immunity' => true],
                    'rarity' => 4,
                ],
                [
                    'name' => '幸運のお守り',
                    'description' => '移動時のサイコロの目+2のお守り',
                    'category' => 'accessory',
                    'effects' => ['dice_bonus' => 2],
                    'rarity' => 3,
                ],
            ],
        ];
    }
}