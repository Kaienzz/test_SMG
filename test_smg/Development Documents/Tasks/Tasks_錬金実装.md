# Tasks_錬金実装.md - 錬金システム実装計画

## プロジェクト概要

既存のハードコードアイテムシステムの良さを保持しながら、公式武器・防具をベースとして消費し、カスタム性能のアイテムを作成できる錬金システムを実装します。錬金では耐久値が継承され、カスタムアイテムの再錬金はできません。

## 現在のシステム分析

### 既存のアイテムシステム構造
- **Item model**: `items`テーブルでハードコード定義
- **Equipment model**: 装備アイテムのリンク管理
- **Inventory model**: JSON形式のスロット管理（`slot_data`）
- **ItemCategory enum**: アイテム種別定義（スタック制限、耐久度等）
- **MaterialItem**: 素材アイテム用の既存クラス（material_type, grade, crafting_uses）

### 既存のプレイヤーシステム
- **Player model**: 1:1 User関係、ステータス・ゴールド・位置情報管理
- **Domain traits**: PlayerInventory, PlayerEquipment, PlayerSkills

### 既存のショップシステム
- **Shop model**: 位置ベースのショップ管理
- **ShopType enum**: ショップ種別定義（コントローラー・ビュー紐付け）

## 実装方針

### 1. ハイブリッドアイテム管理システム

**基本コンセプト**: 既存のハードコードシステムを維持し、カスタムアイテムを拡張として実装

#### システム設計
- **標準アイテム**: 既存の`items`テーブルで管理（変更なし）
- **カスタムアイテム**: 新規`custom_items`テーブルで管理
- **アイテム識別**: `is_custom`フラグで判別
- **互換性維持**: 既存システムとの完全互換性

#### 錬金制限仕様
- **錬金対象**: 公式アイテム（標準アイテム）のみ
- **カスタムアイテム制限**: カスタムアイテムの再錬金は不可
- **ベースアイテム消費**: 錬金に使用した武器・防具は消費される
- **耐久値継承**: ベースアイテムの現在耐久値を参照してカスタムアイテムの耐久値を計算

### 2. データベース設計

#### 2.1 新規テーブル設計

##### `custom_items`テーブル
```sql
CREATE TABLE custom_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    base_item_id BIGINT UNSIGNED NOT NULL,  -- ベースとなる標準アイテムのID
    creator_id BIGINT UNSIGNED NOT NULL,    -- 生産者（錬金実施者）
    custom_stats JSON NOT NULL,             -- カスタムステータス
    base_stats JSON NOT NULL,               -- ベースステータス（比較用）
    material_bonuses JSON NOT NULL,         -- 使用素材効果
    base_durability INT NOT NULL,           -- ベースアイテムの使用時耐久度
    durability INT NOT NULL,                -- 現在耐久度（base_durabilityを基準に計算）
    max_durability INT NOT NULL,            -- 最大耐久度（素材効果で向上可能）
    is_masterwork BOOLEAN DEFAULT FALSE,    -- 名匠品フラグ
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (base_item_id) REFERENCES items(id),
    FOREIGN KEY (creator_id) REFERENCES players(id) ON DELETE CASCADE,
    INDEX idx_creator_custom_items (creator_id),
    INDEX idx_base_item (base_item_id)
);
```

**耐久値継承仕様**:
- `base_durability`: 錬金時のベースアイテムの現在耐久値を記録
- `durability`: ベースアイテムの耐久度を基準に、素材効果を加味して計算
- `max_durability`: ベースアイテムの最大耐久度 + 素材の耐久度ボーナス
- **計算例**:
  ```
  ベース鋼の剣: 現在耐久80/100, 素材効果+10
  → カスタムアイテム: 現在耐久90/110 (80+10/100+10)
  ```

##### `alchemy_materials`テーブル
```sql
CREATE TABLE alchemy_materials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,        -- 素材アイテム名
    stat_bonuses JSON NOT NULL,             -- ステータス効果
    durability_bonus INT DEFAULT 0,         -- 耐久度ボーナス
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_material (item_name)
);
```

**注意**: `alchemy_recipes`テーブルは不要です。
既存の`ItemCategory` enumの`isEquippable()`メソッドを使用して、錬金可能なアイテムを動的に判定します。
これにより、装備品カテゴリ（weapon, body_equipment, shield, head_equipment, foot_equipment, accessory）のアイテムが自動的に錬金対象となります。

#### 2.2 既存テーブル拡張

##### `inventories`テーブル拡張
既存の`slot_data`を拡張してカスタムアイテムに対応:
```json
{
  "item_id": 123,
  "item_name": "鋼の剣",
  "quantity": 1,
  "durability": 95,
  "is_custom": true,
  "custom_item_id": 456,
  "item_info": {
    // 既存フィールド + カスタムフィールド
    "is_masterwork": false,
    "creator_name": "プレイヤー名"
  }
}
```

### 3. 実装手順

#### Phase 1: データベース・モデル実装
**期間**: 2-3日

##### Task 1.1: マイグレーションファイル作成
- [ ] `create_custom_items_table.php`（base_durabilityカラム含む）
- [ ] `create_alchemy_materials_table.php`

##### Task 1.2: モデル実装
- [ ] `CustomItem` model
- [ ] `AlchemyMaterial` model

##### Task 1.3: リレーション定義
- [ ] Player -> CustomItems (hasMany)
- [ ] CustomItem -> Item (belongsTo)
- [ ] CustomItem -> Player (belongsTo)

#### Phase 2: 錬金ショップシステム
**期間**: 3-4日

##### Task 2.1: ショップタイプ拡張
- [ ] `ShopType`にALCHEMY_SHOP追加
- [ ] アイコン: 🧪, 表示名: '錬金所', 説明: '武器や防具を錬金してカスタム装備を作成します。'

##### Task 2.2: 錬金ショップController・Service実装
- [ ] `AlchemyShopController`
- [ ] `AlchemyShopService`
- [ ] 錬金実行ロジック

##### Task 2.3: 錬金画面実装
- [ ] `resources/views/shops/alchemy/index.blade.php`（基本デザインシステム準拠）
- [ ] ベースアイテム選択UI（カードスタイル、公式アイテムのみ選択可能）
- [ ] カスタムアイテム除外フィルタリング
- [ ] 素材選択UI（探索要素を重視し、効果説明なし）
- [ ] ベースアイテム消費確認ダイアログ
- [ ] 錬金実行UI（Modern Light Themeボタンスタイル）
- [ ] 結果表示UI（アニメーション付きカード表示、耐久値継承表示）

#### Phase 3: インベントリシステム拡張
**期間**: 2-3日

##### Task 3.1: Inventoryモデル拡張
- [ ] カスタムアイテム対応の`addCustomItem()`メソッド
- [ ] `getInventoryData()`でカスタムアイテム情報取得
- [ ] カスタムアイテムの`removeItem()`対応

##### Task 3.2: PlayerInventory trait拡張
- [ ] カスタムアイテム管理メソッド追加
- [ ] インベントリ統合表示対応

##### Task 3.3: インベントリ画面更新
- [ ] カスタムアイテム表示対応
- [ ] アイテム詳細表示（ベース+カスタム効果）
- [ ] カスタムアイテムの装備・使用対応

#### Phase 4: 装備システム拡張
**期間**: 2-3日

##### Task 4.1: Equipment model拡張
- [ ] カスタムアイテム装備対応
- [ ] `getTotalStats()`でカスタムアイテムステータス計算
- [ ] `equipCustomItem()`メソッド実装

##### Task 4.2: PlayerEquipment trait拡張
- [ ] カスタム装備管理メソッド
- [ ] 装備効果計算拡張

##### Task 4.3: 装備画面更新
- [ ] カスタム装備表示
- [ ] カスタムアイテム装備・解除UI

#### Phase 5: 錬金ロジック実装
**期間**: 3-4日

##### Task 5.1: AlchemyService核心ロジック
- [ ] 錬金材料効果計算
- [ ] 耐久値継承計算（ベースアイテムの現在耐久値基準）
- [ ] ランダム性能調整（ベース+素材効果の合計に対して適用）
- [ ] 名匠品判定ロジック
- [ ] ベースアイテム消費処理
- [ ] 公式アイテム判定処理
- [ ] 錬金実行処理（常に成功、失敗なし）

##### Task 5.2: 錬金素材データ作成
- [ ] 基本素材効果定義（石、動物の爪、等、効果説明なし）
- [ ] AlchemyMaterialsSeeder

##### Task 5.3: エラーハンドリング
- [ ] 素材不足エラー
- [ ] インベントリ満杯エラー
- [ ] カスタムアイテム選択エラー（公式アイテムのみ錬金可能）
- [ ] ベースアイテム消費確認

#### Phase 6: テスト・統合・最適化
**期間**: 2-3日

##### Task 6.1: 単体テスト
- [ ] AlchemyServiceのテスト
- [ ] CustomItemモデルのテスト
- [ ] インベントリ拡張のテスト

##### Task 6.2: 統合テスト
- [ ] 錬金フロー全体テスト
- [ ] 既存システムとの互換性確認
- [ ] パフォーマンステスト

##### Task 6.3: UI/UX調整
- [ ] 錬金所のレスポンシブ対応（基本デザインシステム準拠）
- [ ] アニメーション追加（ホバー効果、結果表示トランジション）
- [ ] エラーメッセージ統一（Modern Light Themeスタイル）
- [ ] CGIゲーム風親しみやすさとモダンデザインの両立

### 4. 技術仕様詳細

#### 4.1 カスタムアイテム管理システム

##### CustomItemモデル設計
```php
class CustomItem extends Model
{
    protected $fillable = [
        'base_item_id', 'creator_id', 'custom_stats', 
        'base_stats', 'material_bonuses', 'base_durability', 
        'durability', 'max_durability', 'is_masterwork'
    ];
    
    protected $casts = [
        'custom_stats' => 'array',
        'base_stats' => 'array', 
        'material_bonuses' => 'array',
        'base_durability' => 'integer',
        'durability' => 'integer',
        'max_durability' => 'integer',
        'is_masterwork' => 'boolean'
    ];
    
    // ベースアイテムの情報取得
    public function getBaseItem(): Item
    
    // 最終ステータス計算（ベース + カスタム効果）
    public function getFinalStats(): array
    
    // 耐久度継承情報取得
    public function getDurabilityInfo(): array
    
    // アイテム情報取得（既存システム互換）
    public function getItemInfo(): array
    
    // 装備可能かチェック
    public function isEquippable(): bool
    
    // 錬金可能かチェック（常にfalse：カスタムアイテムは再錬金不可）
    public function canBeAlchemized(): bool { return false; }
}
```

#### 4.2 錬金システムロジック

##### AlchemyServiceの核心メソッド
```php
class AlchemyService
{
    // 錬金実行（ベースアイテム消費含む）
    public function performAlchemy(Player $player, int $baseItemSlot, array $materials): AlchemyResult
    
    // 錬金可能性チェック（公式アイテムのみ）
    public function canAlchemyItem(array $inventorySlot): bool
    
    // 素材効果計算
    private function calculateMaterialEffects(array $materials): array
    
    // 耐久値継承計算（ベースアイテムの現在耐久値を基準）
    private function calculateInheritedDurability(int $baseDurability, int $baseMaxDurability, int $durabilityBonus): array
    
    // ランダム効果適用（ベースステータス + 素材効果の合計に対して適用）
    // 通常品：90-110%、名匠品：120-150%
    private function applyRandomVariation(array $totalEffects, bool $isMasterwork = false): array
    
    // 名匠品判定
    private function checkMasterworkCreation(array $materials): bool
    
    // ベースアイテム消費処理
    private function consumeBaseItem(Player $player, int $slotIndex): void
}
```

#### 4.3 インベントリ統合システム

##### 既存システムとの互換性維持
```php
// Inventoryモデル拡張
public function addCustomItem(CustomItem $customItem, int $quantity = 1): array
{
    // カスタムアイテムをslot_dataに統合格納
    // is_custom=true, custom_item_id追加
}

public function getInventoryData(): array 
{
    // 標準アイテムとカスタムアイテムを統合して返却
    // 既存UIとの互換性維持
}
```

### 5. 錬金素材定義例

#### 基本素材効果（探索要素を重視し、効果説明なし）
```php
$alchemyMaterials = [
    [
        'item_name' => '鉄鉱石',
        'stat_bonuses' => ['attack' => 2, 'defense' => 1],
        'durability_bonus' => 10,
    ],
    [
        'item_name' => '動物の爪',
        'stat_bonuses' => ['attack' => 3, 'defense' => 2],
        'durability_bonus' => 5,
    ],
    [
        'item_name' => 'ルビー',
        'stat_bonuses' => ['attack' => 5, 'magic_attack' => 3],
        'durability_bonus' => 0,
    ]
];
```

**ランダム性能調整仕様**:
- **対象**: ベースアイテムステータス + 素材効果の合計値
- **通常品**: 合計ステータスの90%-110%で変動
- **名匠品**: 合計ステータスの120%-150%で変動
- **計算例**: 
  ```
  ベース攻撃力10 + 素材効果+5 = 合計15
  → 通常品: 13.5〜16.5 (90%〜110%)
  → 名匠品: 18〜22.5 (120%〜150%)
  ```

### 6. 必要な修正・拡張箇所

#### 6.1 インベントリシステム修正点

##### 現在の問題点
- カスタムアイテムのスロット管理
- 装備可否判定の拡張
- アイテム情報表示の統合

##### 修正内容
```php
// Inventory::addItem()の拡張
// カスタムアイテム対応のslot_data構造
$slot = [
    'item_id' => $customItem->base_item_id,
    'custom_item_id' => $customItem->id,
    'item_name' => $customItem->getBaseItem()->name,
    'quantity' => 1,
    'durability' => $customItem->durability,
    'is_custom' => true,
    'item_info' => $customItem->getItemInfo()
];
```

#### 6.2 装備システム修正点

##### Equipment model拡張
```php
// カスタムアイテム装備対応
public function equipCustomItem(CustomItem $customItem, string $slot): bool

// ステータス計算でカスタムアイテム考慮
public function getTotalStats(): array
{
    // 標準装備 + カスタム装備のステータス合計
}
```

### 7. UI/UX設計

#### 錬金所画面フロー
1. **ベースアイテム選択**: インベントリから公式武器・防具選択（カスタムアイテムは選択不可）
2. **素材選択**: インベントリから材料選択（複数可、効果説明なし）
3. **確認画面**: ベースアイテム消費の警告表示
4. **錬金実行**: 確認後に実行（ベースアイテム消費）
5. **結果表示**: 作成されたカスタムアイテム表示

#### デザイン統一
**基本デザインシステム準拠**:
- **カラーパレット**: 
  - 背景: `linear-gradient(135deg, #fafafa 0%, #f8fafc 50%, #f1f5f9 100%)`
  - カード背景: `white`
  - プライマリテキスト: `#1e293b`
  - ボーダー: `#e2e8f0`
- **タイポグラフィ**: 
  - フォントファミリー: `'Inter', 'Hiragino Sans', system-ui, sans-serif`
  - カードタイトル: `1.25rem / 600 weight`
  - 説明文: `0.95rem / 400 weight`
- **コンポーネント**:
  - カード: `padding: 2rem`, `border-radius: 1rem`, `shadow: 0 1px 3px rgba(0,0,0,0.05)`
  - ボタン: `padding: 0.875rem 1.5rem`, `border-radius: 0.5rem`
  - ホバー効果: `translateY(-4px)` + シャドウ強化
- **錬金所専用アイコン**: 🧪
- **既存ショップとの統一感を重視**

### 8. 実装スケジュール

#### 総開発期間: 14-18日

| Phase | 期間 | 主要タスク |
|-------|------|------------|
| Phase 1 | 2-3日 | DB設計・モデル実装 |
| Phase 2 | 3-4日 | 錬金ショップシステム |
| Phase 3 | 2-3日 | インベントリ拡張 |
| Phase 4 | 2-3日 | 装備システム拡張 |
| Phase 5 | 3-4日 | 錬金ロジック実装 |
| Phase 6 | 2-3日 | テスト・最適化 |

### 9. リスク管理

#### 技術リスク
- **既存システムとの互換性**: 段階的実装で最小化
- **パフォーマンス**: JSON検索最適化、インデックス活用
- **データ整合性**: トランザクション処理、制約条件

#### 対策
- 既存機能に影響しない拡張設計
- 十分なテストカバレッジ
- ロールバック可能な実装

### 10. 完了基準

#### 機能要件
- [ ] 各町に錬金所配置完了
- [ ] ベースアイテム + 素材 = カスタムアイテム生成
- [ ] ランダム性能調整機能（通常品：90-110%、名匠品：120-150%）
- [ ] 名匠品生成機能
- [ ] インベントリ・装備システム統合
- [ ] 素材効果の探索要素実装（説明なしシステム）
- [ ] 公式アイテムのみ錬金対象（カスタムアイテム再錬金不可）
- [ ] ベースアイテム消費システム
- [ ] 耐久値継承システム（現在耐久値ベース）

#### 技術要件
- [ ] 既存システムとの完全互換性
- [ ] パフォーマンス目標達成
- [ ] テストカバレッジ80%以上
- [ ] セキュリティ要件満足

#### UI/UX要件
- [ ] 直感的な錬金操作
- [ ] エラーハンドリング完備
- [ ] レスポンシブ対応
- [ ] デザインシステム準拠

---

## 注意事項

1. **既存システムとの整合性**: 全ての変更は既存機能を破壊しない
2. **データベース設計**: 将来拡張を考慮した柔軟な設計
3. **パフォーマンス**: JSON操作の最適化に注意
4. **セキュリティ**: アイテム複製などの不正操作防止
5. **テスト**: 各フェーズでの十分なテスト実施

この実装計画により、既存システムの良さを保持しながら、魅力的な錬金システムが追加されます。