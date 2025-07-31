# 実装機能概要ドキュメント (Implemented Features Documentation)

## プロジェクト概要
**SMG (Simple Management Game)** - Laravelフレームワークを使用したブラウザベースのターン制RPGゲーム

### アーキテクチャ概要
- **フレームワーク**: Laravel 10.x
- **データ管理**: セッションベース + DummyDataService
- **フロントエンド**: Vanilla JavaScript (クラスベース設計)
- **UI**: Blade テンプレート + CSS Grid/Flexbox
- **通信**: AJAX (fetch API)

---

## 1. ゲーム機能システム

### 1.1 移動システム (Movement System)
**ファイル**: `app/Http/Controllers/GameController.php`, `public/js/game.js`

#### 主要機能
- **サイコロ移動**: 2〜3個のサイコロを振って移動距離決定
- **道路移動**: 左右矢印キーでプレイヤー移動
- **場所切り替え**: 町 ⇔ 道路間の移動

#### セッション変数
```php
session([
    'location_type' => 'town|road',         // 現在の場所タイプ
    'location_id' => 'town_a|road_1|...',   // 具体的な場所ID
    'game_position' => 0-100,               // 道路上の位置
    'character_sp' => 30,                   // キャラクターSP
]);
```

#### 移動ロジック
- **サイコロ効果**: 基本2個 + 装備による追加サイコロ + ボーナス値
- **移動判定**: position 0-100で道路端に到達で次の場所へ移動可能
- **エンカウント**: 移動時にモンスター出現判定

#### JavaScript連携
```javascript
// クラス構造
GameManager -> DiceManager, MovementManager, UIManager
- rollDice(): サイコロ振り処理
- move(direction): 左右移動処理  
- moveToNext(): 次の場所への移動
- updateGameDisplay(): UI状態更新
```

### 1.2 戦闘システム (Battle System)
**ファイル**: `app/Http/Controllers/BattleController.php`, `app/Services/BattleService.php`

#### 戦闘流れ
1. **エンカウント**: 道路移動時にランダム発生
2. **戦闘開始**: モンスター情報の表示
3. **行動選択**: 攻撃/防御/逃走/スキル使用
4. **ダメージ計算**: 物理/魔法攻撃、クリティカル判定
5. **勝敗判定**: HP0で敗北、モンスター撃破で勝利

#### 戦闘パラメータ
```php
// キャラクターステータス (DummyDataService)
'attack' => 15,      // 攻撃力
'magic_attack' => 12 //魔力
'defense' => 12,     // 防御力
'agility' => 18,     // 素早さ
'evasion' => 22,     // 回避力
'accuracy' => 90,    // 命中力
```

#### モンスターデータ例
```php
// BattleService::getRandomMonster()
'goblin' => [
    'name' => 'ゴブリン',
    'emoji' => '👹',
    'hp' => 25, 'max_hp' => 25,
    'attack' => 8, 'defense' => 3,
    'agility' => 12, 'accuracy' => 75,
]
```

### 1.3 採集システム (Gathering System)
**ファイル**: `app/Http/Controllers/GatheringController.php`, `app/Models/GatheringTable.php`

#### 採集データ構造
```php
// GatheringTable::getGatheringTableByRoad()
'road_1' => [
    ['item_name' => '薬草', 'required_skill_level' => 1, 'success_rate' => 80, 'quantity_min' => 1, 'quantity_max' => 2, 'rarity' => 1],
    ['item_name' => '木の枝', 'required_skill_level' => 1, 'success_rate' => 90, 'quantity_min' => 1, 'quantity_max' => 3, 'rarity' => 1],
]
```

#### レアリティシステム
```php
$rarityNames = [
    1 => 'コモン', 2 => 'アンコモン', 3 => 'レア', 
    4 => 'スーパーレア', 5 => 'ウルトラレア', 6 => 'レジェンダリー'
];
```

#### 採集処理フロー
1. **SP消費チェック**: 採集スキルのSPコストを確認
2. **場所判定**: 道路上でのみ採集可能
3. **アイテム抽選**: スキルレベルに応じた採集可能アイテムからランダム選択
4. **成功判定**: アイテム固有の成功率で判定
5. **結果返却**: 成功時はアイテム・数量・経験値を取得

---

## 2. キャラクター管理システム

### 2.1 ステータスシステム
**ファイル**: `app/Http/Controllers/CharacterController.php`, `app/Services/DummyDataService.php`

#### 基本ステータス
```php
// DummyDataService::getCharacter()
[
    'id' => 1,
    'name' => '冒険者',
    'level' => 5,
    'experience' => 120,
    'attack' => 15,     // 攻撃力
    'defense' => 12,    // 防御力
    'agility' => 18,    // 素早さ
    'evasion' => 22,    // 回避力
    'hp' => 85,         // 現在HP
    'max_hp' => 120,    // 最大HP
    'mp' => 45,         // 現在MP
    'max_mp' => 80,     // 最大MP
    'sp' => 30,         // 現在SP (session管理)
    'max_sp' => 60,     // 最大SP
    'accuracy' => 90,   // 命中力
]
```

#### HP/MP/SP回復システム
```php
// CharacterController
public function heal(Request $request)      // HP回復
public function restoreMp(Request $request) // MP回復
// SP回復はターン経過で自動回復（未実装）
```

### 2.2 スキルシステム
**ファイル**: `app/Http/Controllers/SkillController.php`, `app/Models/Skill.php`

#### 実装スキル
```php
// DummyDataService::getSkills()
[
    [
        'id' => 1,
        'name' => '飛脚術',
        'type' => 'movement',
        'level' => 3,
        'experience' => 45,
        'sp_cost' => 10,
        'effects' => ['dice_bonus' => 3, 'extra_dice' => 1], // サイコロボーナス
    ],
    [
        'id' => 2,
        'name' => '採集',
        'type' => 'gathering',
        'level' => 5,
        'experience' => 120,
        'sp_cost' => 8,
        'effects' => ['gathering_bonus' => 5], // 採集ボーナス
    ],
]
```

#### スキル効果システム
- **アクティブ効果**: SP消費でバフ効果発動（飛脚術等）
- **パッシブ効果**: 常時発動効果（採集レベル等）
- **効果持続**: 一定ターン数継続するバフ管理

---

## 3. インベントリ・装備システム

### 3.1 インベントリシステム
**ファイル**: `app/Http/Controllers/InventoryController.php`, `app/Models/Inventory.php`

#### インベントリ構造
```php
// DummyDataService::getInventory()
[
    'character_id' => 1,
    'max_slots' => 20,              // 最大スロット数
    'used_slots' => 3,              // 使用中スロット
    'available_slots' => 17,        // 利用可能スロット
    'items' => [...],               // アイテム配列
    'slots' => [...]                // スロット配列 (UI用)
]
```

#### アイテムデータ構造
```php
[
    'item' => [
        'id' => 1,
        'name' => '薬草',
        'description' => 'HPを5回復する薬草',
        'category' => 'potion',
        'effects' => ['heal_hp' => 5],
        'rarity' => 1,
        'rarity_name' => 'コモン',
        'rarity_color' => '#9ca3af',
        'is_equippable' => false,
        'is_usable' => true,
    ],
    'quantity' => 5,
    'slot' => 0,
]
```

#### インベントリ操作
- **addItem**: アイテム追加
- **removeItem**: アイテム削除
- **useItem**: アイテム使用（回復等）
- **expandSlots**: スロット拡張
- **moveItem**: アイテム移動（ドラッグ&ドロップ）

### 3.2 装備システム
**ファイル**: `app/Http/Controllers/EquipmentController.php`, `app/Models/Equipment.php`

#### 装備スロット
```php
// DummyDataService::getEquipment()
[
    'character_id' => 1,
    'weapon_id' => null,        // 武器
    'body_armor_id' => null,    // 体防具
    'shield_id' => null,        // 盾
    'helmet_id' => null,        // 頭防具  
    'boots_id' => null,         // 足防具
    'accessory_id' => null,     // アクセサリー
]
```

#### 装備効果
```php
// 装備アイテム例
'鉄の剣' => ['effects' => ['attack' => 5]],
'疾風のブーツ' => ['effects' => ['agility' => 8, 'extra_dice' => 1]],
```

---

## 4. ショップシステム

### 4.1 抽象ショップ設計
**ファイル**: `app/Http/Controllers/BaseShopController.php`

#### ショップ継承構造
```
BaseShopController (抽象クラス)
├── ItemShopController    (アイテムショップ)
├── BlacksmithController  (鍛冶屋)
├── TavernController      (酒場)
└── AlchemyShopController (錬金屋) ※2025年7月29日追加
```

#### 共通ショップ機能
- **在庫管理**: 商品リスト・在庫数・価格管理
- **売買処理**: 購入・売却・所持金管理
- **商品フィルタ**: カテゴリ・レアリティ別表示

### 4.2 ショップ種類
```php
// Shop::getShopsByLocation() in ShopEnums
ITEM_SHOP => [
    'name' => 'アイテムショップ',
    'icon' => '🛒',
    'description' => 'ポーションや消耗品を販売'
],
BLACKSMITH => [
    'name' => '鍛冶屋', 
    'icon' => '⚒️',
    'description' => '武器・防具を販売・修理'
],
ALCHEMY_SHOP => [
    'name' => '錬金屋',
    'icon' => '🧪', 
    'description' => 'アイテム錬金・カスタムアイテム作成'
]
```

### 4.3 錬金ショップシステム
**ファイル**: `app/Http/Controllers/AlchemyShopController.php`, `app/Services/AlchemyShopService.php`

#### 錬金システム概要
- **ベースアイテム消費**: 公式武器・防具をベースとして消費
- **素材組み合わせ**: 複数の錬金素材で効果付与
- **カスタムアイテム生成**: 耐久値継承・ランダム効果適用
- **マスターワーク確率**: 素材効果により上位品生成可能

#### 錬金処理フロー
```php
// AlchemyShopService::performAlchemy()
1. ベースアイテム検証: 錬金可能（公式アイテムのみ）
2. 素材効果計算: AlchemyMaterial::calculateCombinedEffects()
3. マスターワーク判定: 素材パワーに基づく確率計算
4. ステータス計算: ベース値 + 素材効果 + ランダム変動
5. 耐久値継承: ベースアイテムの現在耐久値を引き継ぎ
6. CustomItem生成: データベースへの永続化
7. インベントリ更新: ベース・素材消費、生成品追加
```

#### 錬金素材データ
```php
// AlchemyMaterial::getBasicMaterialsData()
'鉄鉱石' => ['attack' => 2, 'defense' => 1],
'動物の爪' => ['attack' => 3, 'agility' => 1], 
'ルビー' => ['attack' => 4, 'magic_attack' => 2],
'サファイア' => ['defense' => 3, 'magic_attack' => 2],
'エメラルド' => ['agility' => 3, 'evasion' => 2],
'ダイヤモンド' => ['attack' => 3, 'defense' => 3],
'ミスリル' => ['attack' => 5, 'agility' => 2],
'オリハルコン' => ['attack' => 6, 'defense' => 4, 'magic_attack' => 3]
```

#### カスタムアイテム構造
```php
// CustomItem Model
[
    'base_item_id' => 1,              // ベースアイテムID
    'creator_id' => 1,                // 作成者（プレイヤー）ID
    'custom_name' => '炎の鉄剣+1',     // カスタム名
    'custom_stats' => [               // JSON: カスタムステータス
        'attack' => 15,
        'durability_bonus' => 5
    ],
    'base_durability' => 45,          // ベースアイテム耐久値
    'durability' => 45,               // 現在耐久値
    'max_durability' => 50,           // 最大耐久値
    'is_masterwork' => true,          // マスターワーク品
    'materials_used' => [             // 使用素材記録
        '鉄鉱石' => 2, 'ルビー' => 1
    ]
]
```

---

## 5. UIシステム・ビュー構成

### 5.1 メインビューファイル
```
resources/views/
├── game/
│   ├── index.blade.php                 # メインゲーム画面
│   └── partials/
│       ├── navigation.blade.php        # ナビゲーションメニュー
│       ├── location_info.blade.php     # 場所情報表示
│       ├── dice_container.blade.php    # サイコロUI
│       ├── movement_controls.blade.php # 移動ボタン
│       ├── next_location_button.blade.php # 次の場所ボタン
│       └── game_controls.blade.php     # ゲーム制御ボタン
├── battle/index.blade.php              # 戦闘画面
├── character/index.blade.php           # キャラクター画面
├── inventory/index.blade.php           # インベントリ画面
├── equipment/show.blade.php            # 装備画面
├── skills/index.blade.php              # スキル画面
└── shops/
    ├── item/index.blade.php            # アイテムショップ
    ├── blacksmith/index.blade.php      # 鍛冶屋
    └── alchemy/index.blade.php         # 錬金屋 ※2025年7月29日追加
```

### 5.2 CSS設計
**ファイル**: `public/css/game.css`

#### 主要CSSクラス
```css
.game-container     /* メインコンテナ */
.location-info      /* 場所情報エリア */
.dice-container     /* サイコロエリア */
.movement-controls  /* 移動コントロール */
.progress-bar       /* 道路位置プログレスバー */
.town-menu         /* 町の施設メニュー */
.road-actions      /* 道路専用アクション */
.btn               /* 汎用ボタンスタイル */
.hidden            /* 非表示クラス */
```

### 5.3 JavaScript UI管理
**ファイル**: `public/js/game.js`

#### クラス構成
```javascript
// メインクラス
GameManager          // ゲーム全体管理
├── DiceManager      // サイコロ機能
├── MovementManager  // 移動機能  
├── UIManager        // UI制御
└── BattleManager    // 戦闘管理

// UI制御メソッド
UIManager.updateGameDisplay(data)     // 画面全体更新
UIManager.showTownUI(data)           // 町UI表示
UIManager.showRoadUI(data)           // 道路UI表示
UIManager.showTownMenu()             // 町施設メニュー表示
UIManager.hideMovementControls()     // 移動ボタン非表示
```

---

## 6. データベース設計

### 6.1 マイグレーションファイル
```
database/migrations/
├── create_characters_table.php        # キャラクター
├── create_items_table.php            # アイテム
├── create_inventories_table.php      # インベントリ
├── create_equipment_table.php        # 装備
├── create_skills_table.php           # スキル  
├── create_shops_table.php            # ショップ
├── create_shop_items_table.php       # ショップ商品
├── create_active_effects_table.php   # アクティブ効果
├── create_custom_items_table.php     # カスタムアイテム ※2025年7月29日追加
└── create_alchemy_materials_table.php # 錬金素材 ※2025年7月29日追加
```

### 6.2 主要テーブル構造
```sql
-- キャラクターテーブル
characters: id, name, level, experience, hp, max_hp, mp, max_mp, sp, max_sp, 
           attack, defense, agility, evasion, accuracy

-- アイテムテーブル  
items: id, name, description, category, effects(JSON), rarity, 
       is_equippable, is_usable, base_price

-- インベントリテーブル
inventories: id, character_id, item_id, quantity, slot, durability

-- 装備テーブル
equipment: character_id, weapon_id, body_armor_id, shield_id, 
          helmet_id, boots_id, accessory_id

-- カスタムアイテムテーブル ※2025年7月29日追加
custom_items: id, base_item_id, creator_id, custom_name, custom_stats(JSON),
             base_durability, durability, max_durability, is_masterwork,
             materials_used(JSON), created_at, updated_at

-- 錬金素材テーブル ※2025年7月29日追加  
alchemy_materials: id, item_name, stat_bonuses(JSON), durability_bonus,
                  created_at, updated_at
```

---

## 7. ルーティング・API設計

### 7.1 ゲームルート
```php
// メインゲーム
GET  /game                    # ゲーム画面表示
POST /game/roll-dice          # サイコロ振り
POST /game/move               # 移動処理
POST /game/move-to-next       # 次の場所移動
POST /game/reset              # ゲームリセット

// 戦闘
GET  /battle                  # 戦闘画面
POST /battle/start            # 戦闘開始
POST /battle/attack           # 攻撃
POST /battle/defend           # 防御
POST /battle/escape           # 逃走
POST /battle/skill            # スキル使用
POST /battle/end              # 戦闘終了

// 採集
POST /gathering/gather        # 採集実行
GET  /gathering/info          # 採集情報取得
```

### 7.2 管理画面ルート
```php
// キャラクター管理
GET  /character               # キャラクター画面
POST /character/heal          # HP回復
POST /character/restore-mp    # MP回復
POST /character/reset         # ステータスリセット

// インベントリ管理  
GET  /inventory               # インベントリ画面
POST /inventory/add-item      # アイテム追加
POST /inventory/use-item      # アイテム使用
POST /inventory/move-item     # アイテム移動

// 装備管理
GET  /equipment               # 装備画面  
POST /equipment/equip         # 装備
POST /equipment/unequip       # 装備解除

// スキル管理
GET  /skills                  # スキル画面
POST /skills/use              # スキル使用
```

### 7.3 ショップ管理ルート ※2025年7月29日追加
```php
// 錬金ショップ
GET  /shops/alchemy           # 錬金ショップ画面
POST /shops/alchemy/preview   # 錬金プレビュー
POST /shops/alchemy/perform   # 錬金実行
```

---

## 8. 重要な設定・定数

### 8.1 セッションキー
```php
// GameController, DummyDataService で使用
'location_type'     // 'town' | 'road'
'location_id'       // 'town_a', 'town_b', 'road_1', 'road_2', 'road_3'  
'game_position'     // 0-100 (道路上の位置)
'character_sp'      // キャラクターSP (基本30)
```

### 8.2 ゲーム定数
```php
// 移動システム
DICE_COUNT_BASE = 2           // 基本サイコロ数
DICE_BONUS_BASE = 0           // 基本ボーナス値
POSITION_MIN = 0              // 道路最小位置  
POSITION_MAX = 100            // 道路最大位置

// 戦闘システム  
ENCOUNTER_RATE = 30           // エンカウント率(%)
CRITICAL_RATE = 10            // クリティカル率(%)
ESCAPE_SUCCESS_RATE = 70      // 逃走成功率(%)

// インベントリシステム
DEFAULT_INVENTORY_SLOTS = 20  // デフォルトスロット数
```

### 8.3 アイテムカテゴリ・レアリティ
```php
// アイテムカテゴリ
'potion'           // ポーション
'weapon'           // 武器
'body_equipment'   // 体防具
'head_equipment'   // 頭防具
'foot_equipment'   // 足防具
'shield'           // 盾
'accessory'        // アクセサリー

// レアリティ (1-6)
1 => 'コモン'         (#9ca3af)
2 => 'アンコモン'     (#22c55e)  
3 => 'レア'          (#3b82f6)
4 => 'スーパーレア'   (#a855f7)
5 => 'ウルトラレア'   (#f59e0b)
6 => 'レジェンダリー' (#ef4444)

// 錬金システム ※2025年7月29日追加
MASTERWORK_BASE_CHANCE = 20     // マスターワーク基本確率(%)
RANDOM_VARIATION_MIN = 90       // 通常品ランダム変動最小(%)
RANDOM_VARIATION_MAX = 110      // 通常品ランダム変動最大(%)
MASTERWORK_VARIATION_MIN = 120  // マスターワーク品変動最小(%)
MASTERWORK_VARIATION_MAX = 150  // マスターワーク品変動最大(%)
```

---

## 9. 開発時の注意点・制約

### 9.1 データ管理の特徴
- **セッション中心**: ゲーム状態はセッションで管理（ブラウザ閉じるとリセット）
- **ダミーデータ**: DummyDataServiceで固定データ提供
- **DBモデル**: 設計済みだが実際のデータ永続化は未実装
- **SP管理**: SPのみセッション管理、他ステータスは固定値

### 9.2 UI/UX制約
- **動的UI切り替え**: JavaScript で町⇔道路UI を動的変更
- **リアルタイム更新**: AJAX でページリロードなしで状態更新
- **レスポンシブ**: 基本的なモバイル対応済み
- **ブラウザ依存**: セッションストレージ使用のため

### 9.3 拡張時の考慮点
```php
// 新機能追加時の参照先
DummyDataService::getXxx()        // ダミーデータ追加
GameController::index()           // メインデータ渡し
public/js/game.js                 // フロントエンド機能
resources/views/game/index.blade.php // UI追加
routes/web.php                    // ルート追加

// 錬金システム拡張時 ※2025年7月29日追加
AlchemyMaterial::getBasicMaterialsData() // 錬金素材データ
AlchemyShopService::performAlchemy()     // 錬金処理ロジック
CustomItem::getFinalStats()             // カスタムアイテム統計
```

### 9.4 デバッグ・トラブルシューティング
```javascript
// JavaScript コンソールでのデバッグ
console.log('gameData:', gameData);              // ゲームデータ確認
console.log('session location_type:', '...');    // セッション状態確認

// PHP でのデバッグ  
dd(session()->all());                            // 全セッション表示
Log::info('Player data:', $playerData);          // ログ出力
```

---

## 10. 今後の開発方針

### 10.1 データベース移行
- セッション → データベース永続化
- ユーザー認証システム導入
- マルチプレイヤー対応準備

### 10.2 機能拡張
- **錬金システム**: ✅ 完了（2025年7月29日）- カスタムアイテム作成機能
- **新スキル**: 戦闘スキル、魔法スキル追加
- **新エリア**: ダンジョン、ボス戦エリア
- **装備強化**: 装備合成・強化システム（錬金システムと統合検討）
- **ギルドシステム**: プレイヤー協力機能

### 10.3 パフォーマンス改善
- **キャッシュ**: Redis/Memcached 導入
- **フロントエンド**: Vue.js/React 移行検討
- **API設計**: RESTful API化
- **リアルタイム**: WebSocket 導入

---

このドキュメントは開発の参考資料として使用し、新機能追加時や既存機能の修正時に参照してください。

---

## 更新履歴

**2025年7月29日**: 錬金システム実装完了
- CustomItem・AlchemyMaterialモデル追加
- AlchemyShopController・AlchemyShopService実装
- 錬金ショップUI・API追加
- カスタムアイテム作成機能実装
- データベース設計・ルーティング拡張