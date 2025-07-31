# アーキテクチャ設計・責務定義書
# test_smg システム設計仕様書

## ドキュメント情報

**プロジェクト名**: test_smg (Simple Management Game)  
**作成日**: 2025年7月25日  
**版数**: Version 1.0  
**対象**: 開発チーム、保守担当者  

---

## 1. アーキテクチャ概要

### 1.1 設計思想

test_smgは**ドメイン駆動設計（DDD）**と**クリーンアーキテクチャ**の原則を採用し、Laravel フレームワークの MVC 構造と統合したレイヤードアーキテクチャを採用しています。

#### 核となる設計原則
1. **責任の分離**: 各レイヤーが明確な責任を持つ
2. **依存関係の逆転**: 上位レイヤーが下位レイヤーに依存しない
3. **テスタビリティ**: 各レイヤーが独立してテスト可能
4. **保守性**: 変更の影響範囲を最小限に抑制

### 1.2 全体アーキテクチャ図

```
┌─────────────────────────────────────────────────────────────┐
│                    🌐 Presentation Layer                     │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐  │
│  │   Blade Views   │  │  JavaScript/UI  │  │  API Routes  │  │
│  │  (resources/    │  │  (public/js/    │  │ (routes/web) │  │
│  │   views/)       │  │   game.js)      │  │              │  │
│  └─────────────────┘  └─────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
              │                    │                    │
              ▼                    ▼                    ▼
┌─────────────────────────────────────────────────────────────┐
│                   🎮 Application Layer                       │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐  │
│  │   Controllers   │  │   DTOs (Data    │  │  Services    │  │
│  │ (Http/Controllers│  │  Transfer       │  │ (Application/│  │
│  │  GameController │  │  Objects)       │  │  Services/)  │  │
│  │  BattleController│  │                 │  │              │  │
│  └─────────────────┘  └─────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
              │                    │                    │
              ▼                    ▼                    ▼
┌─────────────────────────────────────────────────────────────┐
│                     🏗️ Domain Layer                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐  │
│  │ Business Logic  │  │   Domain        │  │  Interfaces  │  │
│  │ (Domain/        │  │   Services      │  │ (Contracts/) │  │
│  │  Character/)    │  │                 │  │              │  │
│  │                 │  │                 │  │              │  │
│  └─────────────────┘  └─────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
              │                    │                    │
              ▼                    ▼                    ▼
┌─────────────────────────────────────────────────────────────┐
│                 💾 Infrastructure Layer                      │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐  │
│  │  Eloquent       │  │   Services      │  │  Factories   │  │
│  │  Models         │  │  (Services/)    │  │              │  │
│  │  (Models/)      │  │                 │  │              │  │
│  └─────────────────┘  └─────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. レイヤー別詳細設計

### 2.1 Presentation Layer（プレゼンテーション層）

#### 責務
- **ユーザーインターフェース**: HTML/CSS/JavaScriptによるUI提供
- **入力検証**: フロントエンド側の基本的な入力チェック
- **状態表示**: ゲーム状態の視覚的表現
- **ユーザー操作**: クリック・キーボード操作の受付

#### 構成要素

##### Blade Views
```
resources/views/
├── layouts/
│   ├── app.blade.php              # アプリケーション共通レイアウト
│   ├── guest.blade.php            # ゲスト用レイアウト
│   └── navigation.blade.php       # ナビゲーション
├── game/
│   ├── index.blade.php            # メインゲーム画面
│   └── partials/                  # 画面部品
│       ├── location_info.blade.php
│       ├── dice_container.blade.php
│       ├── movement_controls.blade.php
│       └── navigation.blade.php
├── battle/
│   └── index.blade.php            # 戦闘画面
├── character/
│   └── index.blade.php            # キャラクター管理画面
├── shops/
│   ├── base/
│   │   └── index.blade.php        # ショップ基底レイアウト
│   ├── item/
│   │   └── index.blade.php        # アイテムショップ
│   ├── blacksmith/
│   │   └── index.blade.php        # 鍛冶屋
│   ├── tavern/
│   │   └── index.blade.php        # 酒場
│   └── alchemy/                   # 錬金屋 (新規追加)
│       └── index.blade.php        # 錬金ショップ画面
└── [other features]/
```

##### JavaScript/UI Layer
```javascript
// public/js/game.js
class GameManager {
    constructor() {
        this.diceManager = new DiceManager();
        this.movementManager = new MovementManager();
        this.uiManager = new UIManager();
        this.battleManager = new BattleManager();
    }
}

// 責務分離されたクラス設計
class DiceManager        // サイコロ機能専門
class MovementManager    // 移動機能専門  
class UIManager          // UI制御専門
class BattleManager      // 戦闘UI専門
```

#### Design Patterns
- **MVC Pattern**: View層としての責務特化
- **Observer Pattern**: ゲーム状態変更の動的UI更新
- **Strategy Pattern**: 場所別（町・道路）のUI切り替え

### 2.2 Application Layer（アプリケーション層）

#### 責務
- **ユースケース調整**: ビジネスロジックの組み合わせ・調整
- **トランザクション管理**: データ整合性の確保
- **認証・認可**: ユーザー権限の検証
- **入力検証**: サーバーサイドバリデーション
- **レスポンス構築**: クライアントへの応答データ作成

#### 構成要素

##### Controllers（コントローラー層）
```php
app/Http/Controllers/
├── GameController.php           # ゲーム基本機能（移動・サイコロ）
├── BattleController.php         # 戦闘システム
├── CharacterController.php      # キャラクター管理
├── InventoryController.php      # インベントリ管理
├── EquipmentController.php      # 装備管理
├── SkillController.php          # スキル管理
├── shops/
│   ├── BaseShopController.php     # ショップ基底クラス
│   ├── ItemShopController.php     # アイテムショップ
│   ├── BlacksmithController.php   # 鍛冶屋
│   ├── TavernController.php       # 酒場
│   └── AlchemyShopController.php  # 錬金屋 (新規追加)
└── Traits/
    └── HasCharacter.php         # キャラクター取得共通処理
```

**Controller設計原則**:
```php
<?php
// コントローラーの標準的な構造
class GameController extends Controller
{
    use HasCharacter;
    
    public function __construct(
        private readonly GameStateManager $gameStateManager,
        private readonly BattleStateManager $battleStateManager,
        private readonly GameDisplayService $displayService
    ) {}
    
    public function index(Request $request): View
    {
        // 1. 認証・認可チェック
        $character = $this->getCharacter();
        
        // 2. サービス層への処理委譲
        $gameData = $this->displayService->prepareGameDisplay($character);
        
        // 3. View への データ渡し
        return view('game.index', compact('gameData'));
    }
}
```

##### DTOs（Data Transfer Objects）
```php
app/Application/DTOs/
├── GameViewData.php         # ゲーム画面表示用データ
├── BattleData.php          # 戦闘データ
├── BattleResult.php        # 戦闘結果
├── LocationData.php        # 場所データ
├── DiceResult.php          # サイコロ結果
├── MoveResult.php          # 移動結果
├── AlchemyPreviewData.php  # 錬金プレビューデータ (新規追加)
└── AlchemyResultData.php   # 錬金結果データ (新規追加)
```

**DTO設計原則**:
```php
<?php
namespace App\Application\DTOs;

final readonly class GameViewData
{
    public function __construct(
        public array $character,
        public array $location,
        public array $gameState,
        public array $availableActions,
        public ?array $battle = null
    ) {}
    
    public static function create(
        array $character,
        array $location, 
        array $gameState,
        array $availableActions,
        ?array $battle = null
    ): self {
        return new self($character, $location, $gameState, $availableActions, $battle);
    }
}
```

##### Application Services
```php
app/Application/Services/
├── GameStateManager.php       # ゲーム状態管理
├── BattleStateManager.php     # 戦闘状態管理
├── GameDisplayService.php     # 表示データ準備
└── AlchemyShopService.php     # 錬金ショップサービス (新規追加)
```

#### Design Patterns
- **Facade Pattern**: 複雑なドメインロジックを簡単なインターフェースで提供
- **Command Pattern**: ユーザー操作をコマンドオブジェクトとして表現
- **DTO Pattern**: レイヤー間のデータ転送を型安全にする

### 2.3 Domain Layer（ドメイン層）

#### 責務
- **ビジネスルール**: ゲームの中核となるルール・制約の実装
- **ドメインロジック**: キャラクター成長・戦闘計算・アイテム効果等
- **不変条件**: データの整合性・制約の維持
- **ドメインサービス**: 複数のエンティティにまたがるロジック

#### 構成要素

##### Domain Services
```php
app/Domain/
├── Character/
│   ├── CharacterStatsService.php     # キャラクター統計計算
│   ├── CharacterSkills.php           # スキル管理
│   ├── CharacterInventory.php        # インベントリ管理
│   └── CharacterEquipment.php        # 装備管理
└── Location/
    └── LocationService.php           # 場所・移動管理
```

**ドメインサービス設計例**:
```php
<?php
namespace App\Domain\Character;

use App\Models\Character;

class CharacterStatsService
{
    /**
     * スキルレベルベースのキャラクターレベル計算
     * ビジネスルール: 総スキルレベル÷10+1
     */
    public function calculateCharacterLevel(Character $character): int
    {
        $totalSkillLevel = $character->skills()->sum('level');
        return max(1, floor($totalSkillLevel / 10) + 1);
    }
    
    /**
     * 装備効果を含む実効ステータス計算
     */
    public function calculateEffectiveStats(Character $character): array
    {
        $baseStats = $character->getBaseStats();
        $equipmentBonus = $this->calculateEquipmentBonus($character);
        $skillBonus = $this->calculateSkillBonus($character);
        
        return $this->combineStatBonuses($baseStats, $equipmentBonus, $skillBonus);
    }
}
```

##### Contracts（インターフェース）
```php
app/Contracts/
├── ItemInterface.php           # アイテム基本契約
├── EquippableInterface.php     # 装備可能アイテム契約
├── WeaponInterface.php         # 武器契約
├── ConsumableInterface.php     # 消耗品契約
└── ShopServiceInterface.php    # ショップサービス契約
```

#### Design Patterns
- **Domain Service Pattern**: 複数エンティティにまたがるビジネスロジック
- **Specification Pattern**: 複雜な業務条件の表現
- **Factory Pattern**: 複雑なオブジェクト生成
- **Strategy Pattern**: アイテム種別による処理の切り替え

### 2.4 Infrastructure Layer（インフラストラクチャ層）

#### 責務
- **データ永続化**: データベースへの読み書き
- **外部システム**: 外部API・サービスとの連携
- **技術的詳細**: ファイルI/O・ネットワーク通信
- **フレームワーク連携**: Laravel機能の具体的実装

#### 構成要素

##### Models（データモデル層）
```php
app/Models/
├── User.php              # ユーザー認証
├── Character.php         # キャラクター（レガシー）
├── Player.php            # プレイヤー（Character統合版）
├── GameState.php         # ゲーム状態
├── Battle/
│   ├── ActiveBattle.php  # アクティブ戦闘
│   ├── BattleLog.php     # 戦闘ログ
│   └── BattleSkill.php   # 戦闘スキル
├── Items/
│   ├── Item.php          # アイテムメインモデル
│   ├── AbstractItem.php  # アイテム抽象基底
│   ├── WeaponItem.php    # 武器
│   ├── ArmorItem.php     # 防具
│   ├── ConsumableItem.php# 消耗品
│   └── MaterialItem.php  # 素材
├── CustomItem.php        # カスタムアイテム（錬金生成品）(新規追加)
├── AlchemyMaterial.php   # 錬金素材効果データ (新規追加)
├── Equipment.php         # 装備状態
├── Inventory.php         # インベントリ
├── Skill.php             # スキル
├── Shop.php              # ショップ
├── ShopItem.php          # ショップ商品
└── [other entities]
```

**Model設計原則**:
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Character extends Model
{
    protected $fillable = [
        'user_id', 'name', 'level', 'experience',
        'hp', 'max_hp', 'mp', 'max_mp', 'sp', 'max_sp',
        'attack', 'defense', 'agility', 'evasion', 'accuracy'
    ];
    
    protected $casts = [
        'location_data' => 'array',
        'player_data' => 'array',
        'game_data' => 'array',
    ];
    
    // リレーション定義
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function skills(): HasMany  
    {
        return $this->hasMany(Skill::class);
    }
    
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
    
    // ビジネスロジックは Domain Layer に委譲
    // Model には基本的なアクセサ・ミューテータのみ
}
```

##### Services（技術サービス層）
```php
app/Services/
├── BattleService.php         # 戦闘処理実装
├── ItemService.php           # アイテム操作実装
├── MovementService.php       # 移動処理実装
├── DummyDataService.php      # 開発用データ提供
├── ShopServiceFactory.php    # ショップサービス生成
├── AbstractShopService.php   # ショップ基底処理
├── ItemShopService.php       # アイテムショップ実装
├── BlacksmithService.php     # 鍛冶屋実装
├── TavernService.php         # 酒場実装
└── AlchemyShopService.php    # 錬金屋実装 (新規追加)
```

##### Factories（ファクトリ層）
```php
app/Factories/
└── ItemFactory.php          # アイテム生成専門
```

#### Design Patterns
- **Repository Pattern**: データアクセスの抽象化（EloquentORMで実現）
- **Factory Pattern**: 複雑なオブジェクト生成の隠蔽
- **Abstract Factory**: ショップ種別による実装の切り替え
- **Singleton Pattern**: DI Container による単一インスタンス管理

---

## 3. データフロー・依存関係

### 3.1 リクエストフロー

#### ユーザー操作からレスポンスまでの流れ
```
1. User Action (JavaScript)
   ↓
2. HTTP Request (AJAX)
   ↓  
3. Route (web.php)
   ↓
4. Controller (Application Layer)
   ├─ Request Validation
   ├─ Authentication/Authorization  
   └─ Business Logic Delegation
   ↓
5. Application Service
   ├─ Use Case Coordination
   ├─ Transaction Management
   └─ Domain Service Calls
   ↓
6. Domain Service (Domain Layer)
   ├─ Business Rules Application
   ├─ Entity State Changes
   └─ Validation & Constraints
   ↓
7. Model/Repository (Infrastructure)
   ├─ Database Operations
   ├─ Data Persistence
   └─ External System Calls
   ↓
8. Response Construction
   ├─ DTO Creation
   ├─ View Data Preparation
   └─ JSON/View Response
   ↓
9. Client Update (JavaScript)
```

#### 具体例：サイコロ移動処理
```php
// 1. GameController::rollDice()
public function rollDice(Request $request): JsonResponse
{
    $character = $this->getCharacter();
    
    // 2. Application Service への委譲
    $diceResult = $this->gameStateManager->rollDice($character);
    
    // 3. レスポンス構築
    return response()->json([
        'success' => true,
        'data' => $diceResult->toArray()
    ]);
}

// 4. GameStateManager::rollDice()
public function rollDice(Character $character): DiceResult
{
    // 5. Domain Layer でのビジネスロジック実行
    $dice1 = rand(1, 6);
    $dice2 = rand(1, 6);
    $dice3 = rand(1, 6);
    
    // 6. CharacterStatsService での効果計算
    $effects = $this->characterStatsService->getMovementEffects($character);
    
    // 7. DTO でのデータ構造化
    return DiceResult::create($diceRolls, $bonus, $effects);
}
```

### 3.2 依存関係の原則

#### Dependency Inversion Principle
```php
// ❌ 悪い例：具象クラスに依存
class GameController extends Controller
{
    public function rollDice()
    {
        $battleService = new BattleService(); // 具象に依存
        // ...
    }
}

// ✅ 良い例：抽象に依存
class GameController extends Controller
{
    public function __construct(
        private readonly GameStateManager $gameStateManager // 抽象に依存
    ) {}
}
```

#### Dependency Injection設定
```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // Domain Services
    $this->app->singleton(CharacterStatsService::class);
    $this->app->singleton(LocationService::class);
    
    // Application Services  
    $this->app->singleton(GameStateManager::class);
    $this->app->singleton(BattleStateManager::class);
    $this->app->singleton(GameDisplayService::class);
    
    // Infrastructure Services
    $this->app->bind(ShopServiceInterface::class, ShopServiceFactory::class);
}
```

---

## 4. アーキテクチャ判断記録

### 4.1 重要な設計決定

#### Decision 1: ドメイン駆動設計の採用
**背景**: 従来の巨大なCharacterクラス（722行）による保守困難  
**決定**: DDDによる責務分離実施  
**影響**: 
- ✅ 保守性大幅向上
- ✅ テスタビリティ改善  
- ⚠️ 初期学習コスト増加

#### Decision 2: セッション→データベース移行  
**背景**: セッション・DB混在による整合性問題  
**決定**: データベース中心設計に完全移行  
**影響**:
- ✅ データ整合性確保
- ✅ マルチデバイス対応可能
- ⚠️ パフォーマンス要チューニング

#### Decision 3: Application Layer の導入
**背景**: Controller での複雑なビジネスロジック処理  
**決定**: Application Service による責務分離  
**影響**:
- ✅ Controller の純化
- ✅ 再利用性向上
- ⚠️ レイヤー増加による複雑性

#### Decision 4: DTO Pattern の全面採用  
**背景**: レイヤー間のデータ渡しでの型安全性不足  
**決定**: 全てのレイヤー間通信でDTO使用  
**影響**:
- ✅ 型安全性向上
- ✅ API契約明確化
- ⚠️ 開発時の記述量増加

### 4.2 技術的制約・トレードオフ

#### Laravel Framework制約
- **メリット**: 豊富なエコシステム・開発効率
- **制約**: Laravel方式との調和必要
- **対応**: Laravelベストプラクティスとの両立

#### DDD実装の制約  
- **理想**: 完全なDDD実装
- **制約**: Laravel標準構造との調和
- **妥協点**: Laravel MVC + DDD要素の混合

#### パフォーマンス vs 設計品質
- **課題**: 多層設計による処理オーバーヘッド
- **対策**: 適切なキャッシュ・最適化実施
- **監視**: 継続的なパフォーマンス測定

---

## 5. 開発・保守ガイドライン

### 5.1 新機能追加時の指針

#### Step 1: ドメイン分析
1. **ビジネスルール**: 新機能の業務要件整理
2. **エンティティ**: 影響を受けるドメインオブジェクト特定
3. **制約**: データ整合性・ビジネス制約の洗い出し

#### Step 2: レイヤー設計
1. **Domain Layer**: ビジネスロジック・ルール実装
2. **Application Layer**: ユースケース・調整処理実装  
3. **Infrastructure Layer**: データ永続化・技術実装
4. **Presentation Layer**: UI・ユーザーインタラクション実装

#### Step 3: テスト戦略
1. **Unit Test**: 各レイヤーの個別テスト
2. **Integration Test**: レイヤー間連携テスト
3. **Feature Test**: エンドツーエンドテスト

#### Step 4: ドキュメント更新
1. **API仕様**: 新エンドポイント・レスポンス形式
2. **ビジネスルール**: ドメインルールの追加・変更  
3. **アーキテクチャ**: 設計変更・影響範囲

### 5.2 リファクタリング指針

#### コード品質指標
- **Cyclomatic Complexity**: 10以下維持
- **Class Lines**: 200行以下推奨
- **Method Lines**: 20行以下推奨  
- **Test Coverage**: 80%以上維持

#### リファクタリングトリガー
- **Code Smell**: 重複コード・長大メソッド発見時
- **Feature Request**: 既存機能への大幅変更要求時
- **Performance Issue**: パフォーマンス問題発生時
- **Bug Pattern**: 同種バグの繰り返し発生時

### 5.3 品質保証

#### 静的解析
```bash
# PHPStan - Level 8 での解析
./vendor/bin/phpstan analyse --level=8

# PHP_CodeSniffer - PSR-12 準拠チェック  
./vendor/bin/phpcs --standard=PSR12 app/

# Laravel Pint - コードスタイル自動修正
./vendor/bin/pint
```

#### 継続的インテグレーション
- **Pre-commit Hook**: コミット前品質チェック
- **Pull Request**: レビュー必須・自動テスト実行
- **Deployment**: テスト通過後の自動デプロイ

---

## 6. パフォーマンス考慮事項

### 6.1 レイヤー間通信最適化

#### N+1問題対策
```php
// ❌ N+1問題発生例
$characters = Character::all();
foreach ($characters as $character) {
    $skills = $character->skills; // N回のクエリ発生
}

// ✅ Eager Loading による最適化
$characters = Character::with(['skills', 'inventory', 'equipment'])->get();
```

#### クエリ最適化
```php
// Domain Service での効率的データ取得
class CharacterStatsService
{
    public function getCharacterWithAllData(int $characterId): Character
    {
        return Character::with([
            'skills',
            'inventory.item',
            'equipment',
            'activeBattle'
        ])->findOrFail($characterId);
    }
}
```

### 6.2 キャッシュ戦略

#### Application Layer キャッシュ
```php
class GameDisplayService  
{
    public function prepareGameDisplay(Character $character): GameViewData
    {
        // 計算コストが高いデータのキャッシュ
        $cacheKey = "game_display_{$character->id}_{$character->updated_at}";
        
        return Cache::remember($cacheKey, 300, function () use ($character) {
            return $this->buildGameViewData($character);
        });
    }
}
```

#### Domain Layer キャッシュ
```php
class CharacterStatsService
{
    public function calculateEffectiveStats(Character $character): array
    {
        $cacheKey = "character_stats_{$character->id}_{$character->equipment_updated_at}";
        
        return Cache::remember($cacheKey, 600, function () use ($character) {
            return $this->computeStats($character);
        });
    }
}
```

---

## 7. セキュリティ考慮事項

### 7.1 レイヤー別セキュリティ

#### Presentation Layer
- **入力検証**: JavaScript による基本チェック
- **XSS対策**: Blade自動エスケープ使用
- **CSRF対策**: Laravel標準CSRF トークン

#### Application Layer  
- **認証**: Laravel Breeze による認証確認
- **認可**: HasCharacter Trait による所有権確認
- **バリデーション**: FormRequest による厳密な入力検証

#### Domain Layer
- **ビジネスルール**: ドメイン制約による不正データ防止
- **不変条件**: エンティティ状態の整合性確保

#### Infrastructure Layer
- **SQLインジェクション**: Eloquent ORM使用による対策
- **データ暗号化**: 機密データの暗号化保存

### 7.2 ゲーム固有セキュリティ

#### チート対策
```php
class BattleStateManager
{
    public function processAttack(Character $character, array $attackData): BattleResult
    {
        // サーバーサイドでの戦闘計算検証
        $expectedDamage = $this->calculateDamage($character, $attackData);
        
        if ($attackData['damage'] > $expectedDamage * 1.1) { // 10%のマージン
            throw new CheatDetectedException('Damage calculation mismatch');
        }
        
        return $this->executeBattleLogic($character, $attackData);
    }
}
```

---

このアーキテクチャ設計により、test_smgは保守性・拡張性・テスタビリティを兼ね備えた堅牢なゲームシステムとして構築されています。各レイヤーの責務を明確に分離することで、将来的な機能拡張や技術変更に柔軟に対応できる設計となっています。

**2025年7月29日更新内容**:
- 錬金システム関連コンポーネント追加
  - AlchemyShopController, AlchemyShopService 
  - CustomItem, AlchemyMaterial モデル
  - AlchemyPreviewData, AlchemyResultData DTO
  - shops/alchemy/ ビューディレクトリ
- Player モデル（Character統合版）追加

**最終更新**: 2025年7月29日  
**次回レビュー**: 新機能追加時または月次アーキテクチャレビュー時