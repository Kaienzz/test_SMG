# アイテム追加マニュアル

## 概要
このマニュアルでは、test_smgプロジェクトに新しいアイテムを追加する手順を説明します。
現在のコードベースを精査し、既存の実装パターンに基づいた手順を記載しています。

## アイテムシステムの構造

### データベース設計
- **items**: アイテムのマスターデータテーブル
- **shop_items**: ショップでの販売情報テーブル  
- **equipment**: プレイヤーの装備情報テーブル
- **inventories**: プレイヤーのインベントリ情報（JSON形式のslot_data）

### アイテムカテゴリ（ItemCategory enum）
| カテゴリ | 表示名 | スタック可能 | 耐久度 | 装備可能 | 使用可能 | 備考 |
|---------|--------|------------|------|---------|---------|------|
| MATERIAL | 素材 | ○ (99個) | × | × | × | 素材アイテム |
| POTION | ポーション | ○ (50個) | × | × | ○ | 回復アイテム |
| WEAPON | 武器 | × | ○ | ○ | × | 武器装備 |
| HEAD_EQUIPMENT | 頭装備 | × | ○ | ○ | × | 頭部装備 |
| BODY_EQUIPMENT | 胴体装備 | × | ○ | ○ | × | 胴体装備 |
| SHIELD | 盾 | × | ○ | ○ | × | 盾装備 |
| FOOT_EQUIPMENT | 靴装備 | × | ○ | ○ | × | 足部装備 |
| ACCESSORY | 装飾品 | × | ○ | ○ | × | アクセサリー |
| BAG | 鞄 | × | ○ | ○ | × | インベントリ拡張 |

### アイテムの属性
- **name**: アイテム名（必須）
- **description**: アイテム説明
- **category**: カテゴリ（ItemCategory enum）
- **effects**: アイテム効果（JSON形式）
- **rarity**: レアリティ（1-5：コモン、アンコモン、レア、エピック、レジェンダリー）
- **value**: アイテムの価値（ゴールド）
- **sell_price**: 売却価格（nullの場合value*0.5）
- **max_durability**: 最大耐久度（装備品のみ）
- **stack_limit**: スタック上限（素材・ポーションのみ）
- **weapon_type**: 武器タイプ（'physical' or 'magical'、武器のみ）
- **battle_skill_id**: バトルスキルID（武器のみ）

## アイテム追加手順

### 1. アイテムデータの設計

新しいアイテムを追加する前に、以下を決定してください：

#### 基本情報
- アイテム名
- 説明文
- カテゴリ（ItemCategory）
- レアリティ（1-5）
- 基本価値

#### カテゴリ別設定
**ポーション類**
```php
[
    'name' => 'アイテム名',
    'description' => '説明文',
    'category' => ItemCategory::POTION->value,
    'stack_limit' => 50, // スタック上限
    'effects' => ['heal_hp' => 50], // 効果
    'rarity' => 1,
    'value' => 100,
]
```

**武器類**
```php
[
    'name' => 'アイテム名',
    'description' => '説明文',
    'category' => ItemCategory::WEAPON->value,
    'max_durability' => 100, // 耐久度
    'effects' => ['attack' => 10], // 攻撃力等の効果
    'rarity' => 2,
    'value' => 200,
    'weapon_type' => 'physical', // 'physical' or 'magical'
    'battle_skill_id' => 'skill_id', // バトルスキル（任意）
]
```

**装備品類**
```php
[
    'name' => 'アイテム名',
    'description' => '説明文',
    'category' => ItemCategory::BODY_EQUIPMENT->value,
    'max_durability' => 80,
    'effects' => ['defense' => 5], // 防御力等の効果
    'rarity' => 1,
    'value' => 150,
]
```

**素材類**
```php
[
    'name' => 'アイテム名',
    'description' => '説明文',
    'category' => ItemCategory::MATERIAL->value,
    'stack_limit' => 99,
    'rarity' => 1,
    'value' => 5,
]
```

### 2. サンプルアイテムへの追加

**ファイル**: `app/Models/Item.php`  
**メソッド**: `createSampleItems()`

```php
public static function createSampleItems(): array
{
    return [
        // 既存のアイテム...
        
        // 新しいアイテムを追加
        [
            'name' => '新しいアイテム名',
            'description' => 'アイテムの説明',
            'category' => ItemCategory::WEAPON->value,
            'max_durability' => 120,
            'effects' => ['attack' => 15, 'accuracy' => 3],
            'rarity' => 3,
            'value' => 300,
            'weapon_type' => self::WEAPON_TYPE_PHYSICAL,
        ],
    ];
}
```

### 3. データベースシーダーの更新（必要に応じて）

**ファイル**: `database/seeders/ShopSeeder.php`

データベースに永続化したい場合は、ShopSeederに追加：

```php
private function seedItems(): void
{
    // 既存のアイテム...
    
    Item::firstOrCreate(
        ['name' => '新しいアイテム名'],
        [
            'description' => 'アイテムの説明',
            'category' => ItemCategory::WEAPON->value,
            'rarity' => 3,
            'effects' => ['attack' => 15, 'accuracy' => 3],
            'value' => 300,
            'max_durability' => 120,
            'weapon_type' => 'physical',
        ]
    );
}
```

### 4. ショップでの販売設定（任意）

新しいアイテムをショップで販売する場合は、ShopSeederのshop作成メソッドを更新：

```php
private function createItemShop(string $locationId, string $name, string $description): void
{
    // 既存のショップ作成コード...
    
    // 新しいアイテムをショップに追加
    $newItem = Item::where('name', '新しいアイテム名')->first();
    if ($newItem) {
        ShopItem::firstOrCreate(
            [
                'shop_id' => $shop->id,
                'item_id' => $newItem->id,
            ],
            [
                'price' => 300, // 販売価格
                'stock' => -1,  // -1は無限在庫
                'is_available' => true,
            ]
        );
    }
}
```

### 5. データベースの更新

シーダーを更新した場合は、データベースに反映：

```bash
# 開発環境での更新
php artisan db:seed --class=ShopSeeder

# または全シーダー実行
php artisan db:seed
```

### 6. テストとベリフィケーション

#### アイテムが正しく作成されることを確認
```php
// コンソールで確認
php artisan tinker

// アイテムの取得テスト
$item = App\Models\Item::findSampleItem('新しいアイテム名');
dd($item->getItemInfo());

// インベントリへの追加テスト
$character = App\Models\Character::first();
$inventory = $character->inventory;
$result = $inventory->addItem($item, 1);
dd($result);
```

#### ゲーム内での動作確認
1. インベントリにアイテムが表示されるか
2. 装備品の場合は装備できるか
3. ポーションの場合は使用できるか
4. ショップで販売されているか（設定した場合）

## アイテム効果の実装

### 効果システムの仕組み

アイテム効果は以下の方法で実装・適用されます：

#### 1. 装備品効果の実装
装備品のeffectsは`Equipment::getTotalStats()`で自動的に合計され、キャラクターのステータスに反映されます。

**実装場所**: `app/Domain/Character/CharacterEquipment.php:55-74`

```php
// 装備効果の合計方法
public function getTotalStatsWithEquipment(): array
{
    $baseStats = $this->getBaseStats();
    $equipment = $this->getOrCreateEquipment();
    $equipmentStats = $equipment->getTotalStats();

    return [
        'attack' => ($baseStats['attack'] ?? 0) + ($equipmentStats['attack'] ?? 0),
        'defense' => ($baseStats['defense'] ?? 0) + ($equipmentStats['defense'] ?? 0),
        // ... 他のステータス
        'equipment_effects' => $equipmentStats['effects'] ?? [], // 特殊効果
    ];
}
```

#### 2. ポーション効果の実装
ポーションのeffectsは`Inventory::useItem()`で効果タイプ別に処理されます。

**実装場所**: `app/Models/Inventory.php:298-336`

```php
// ポーション効果の処理例
foreach ($effects as $effectType => $effectValue) {
    switch ($effectType) {
        case 'heal_hp':
            $character->heal($effectValue);
            break;
        case 'heal_mp':
            $character->restoreMP($effectValue);
            break;
        case 'heal_sp':
            $character->restoreSP($effectValue);
            break;
    }
}
```

### 効果の種類と実装方法

#### 数値効果（装備品）
数値効果は自動的に加算され、キャラクターのステータスに反映されます。

| 効果キー | 説明 | 対象 | 実装例 |
|---------|------|-----|-------|
| **attack** | 攻撃力上昇 | 装備品 | `'effects' => ['attack' => 10]` |
| **defense** | 防御力上昇 | 装備品 | `'effects' => ['defense' => 5]` |
| **agility** | 素早さ上昇 | 装備品 | `'effects' => ['agility' => 8]` |
| **evasion** | 回避力上昇 | 装備品 | `'effects' => ['evasion' => 12]` |
| **accuracy** | 命中力上昇 | 装備品 | `'effects' => ['accuracy' => 3]` |
| **hp** | 最大HP上昇 | 装備品 | `'effects' => ['hp' => 20]` |
| **mp** | 最大MP上昇 | 装備品 | `'effects' => ['mp' => 15]` |
| **magic_attack** | 魔法攻撃力上昇 | 装備品 | `'effects' => ['magic_attack' => 6]` |

#### 回復効果（ポーション）
回復効果はアイテム使用時に即座に適用され、アイテムが消費されます。

| 効果キー | 説明 | 対象 | 実装例 |
|---------|------|-----|-------|
| **heal_hp** | HP回復 | ポーション | `'effects' => ['heal_hp' => 50]` |
| **heal_mp** | MP回復 | ポーション | `'effects' => ['heal_mp' => 30]` |
| **heal_sp** | SP回復 | ポーション | `'effects' => ['heal_sp' => 25]` |

#### 特殊効果（装備品）
特殊効果は`Equipment::getTotalStats()`で'effects'配列に格納され、別途処理されます。

| 効果キー | 説明 | 対象 | 実装例 |
|---------|------|-----|-------|
| **status_immunity** | 状態異常無効化 | アクセサリー | `'effects' => ['status_immunity' => true]` |
| **dice_bonus** | 移動サイコロボーナス | アクセサリー | `'effects' => ['dice_bonus' => 2]` |
| **extra_dice** | 追加サイコロ | 靴・アクセサリー | `'effects' => ['extra_dice' => 1]` |
| **inventory_slots** | インベントリ拡張 | 鞄 | `'effects' => ['inventory_slots' => 2]` |

### 新しい効果の追加方法

#### 1. 数値効果を追加する場合

**ステップ1**: `Equipment::getTotalStats()`に新しい効果を追加
```php
// app/Models/Equipment.php:72-109
$stats = [
    'attack' => 0,
    'defense' => 0,
    // ... 既存の効果
    'new_stat' => 0, // 新しい効果を追加
];
```

**ステップ2**: `CharacterEquipment::getTotalStatsWithEquipment()`に反映
```php
// app/Domain/Character/CharacterEquipment.php:55-74
return [
    // ... 既存のステータス
    'new_stat' => ($baseStats['new_stat'] ?? 0) + ($equipmentStats['new_stat'] ?? 0),
];
```

#### 2. ポーション効果を追加する場合

`Inventory::useItem()`のswitch文に新しいケースを追加：
```php
// app/Models/Inventory.php:298-336
case 'new_potion_effect':
    // 新しい効果の処理を実装
    $character->applyNewEffect($effectValue);
    $effectResults[] = "新しい効果が適用されました";
    $shouldConsumeItem = true;
    break;
```

#### 3. 特殊効果を追加する場合

`Equipment::getTotalStats()`の特殊効果判定に追加：
```php
// app/Models/Equipment.php:101-103
} elseif ($effect === 'status_immunity' || $effect === 'dice_bonus' || $effect === 'extra_dice' || $effect === 'new_special_effect') {
    $stats['effects'][$effect] = $value;
}
```

### 効果実装のベストプラクティス

1. **命名規則**: 効果キーは小文字とアンダースコアを使用（`snake_case`）
2. **型の整合性**: 数値効果は整数、特殊効果は適切な型を使用
3. **バランス考慮**: 既存アイテムとの効果値バランスを考慮
4. **テスト**: 新しい効果は必ず動作確認を実施
5. **ドキュメント**: 新しい効果は本マニュアルに追記

### 効果テスト用コマンド

```php
// アイテム効果のテスト
php artisan tinker

// 装備効果のテスト
$character = App\Models\Character::first();
$equipment = $character->getEquipment();
dd($equipment->getTotalStats());

// ポーション効果のテスト
$inventory = $character->getInventory();
$potion = App\Models\Item::findSampleItem('ポーション名');
$result = $inventory->useItem(0, $character); // スロット0のアイテムを使用
dd($result);
```

## 注意事項

1. **命名規則**: アイテム名は日本語で、既存のアイテムと重複しないようにしてください
2. **バランス調整**: 新しいアイテムの効果値は既存のアイテムとのバランスを考慮してください  
3. **カテゴリ設定**: カテゴリは必ずItemCategory enumの値を使用してください
4. **効果の整合性**: カテゴリに応じた適切な効果を設定してください
5. **テスト**: 追加後は必ず動作確認を行ってください

## トラブルシューティング

### よくある問題

**Q: アイテムがインベントリに表示されない**  
A: Item::findSampleItem()でアイテムが取得できているか確認してください

**Q: 装備できない**  
A: カテゴリがisEquippable()=trueになっているか確認してください

**Q: 効果が反映されない**  
A: effectsの配列形式とキー名が正しいか確認してください

**Q: ショップに表示されない**  
A: ShopSeederでShopItemが正しく作成されているか確認してください

## 参考ファイル

- `app/Models/Item.php`: アイテムモデル
- `app/Models/Inventory.php`: インベントリ管理
- `app/Models/Equipment.php`: 装備管理
- `app/Enums/ItemCategory.php`: カテゴリ定義
- `database/seeders/ShopSeeder.php`: シーダー
- `database/migrations/2025_07_17_034143_create_items_table.php`: アイテムテーブル定義

このマニュアルに従って新しいアイテムを追加することで、既存のシステムと整合性を保ちながら拡張することができます。