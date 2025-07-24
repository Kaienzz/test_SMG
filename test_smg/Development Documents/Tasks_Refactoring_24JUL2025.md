# Player/Character変数 根本的リファクタリング計画 - 2025年7月24日

## 📋 プロジェクト概要
**目的**: Player変数とCharacter変数の混乱を解消し、設計品質を根本的に改善  
**ステータス**: 🔄 **計画中**  
**優先度**: 高（技術的負債解消・保守性向上のため）  
**推定工数**: 8-12時間（根本的リファクタリング）  
**戦略**: ドメイン駆動設計（DDD）アプローチによる責任分離

## 🔍 深層分析結果

### 現状の重大な設計問題

#### 1. Character クラスの責任過多（722行の巨大クラス）
```php
class Character extends Model {
    // 戦闘システム (20+ メソッド)
    // レベルシステム (15+ メソッド)  
    // スキルシステム (25+ メソッド)
    // 装備システム (10+ メソッド)
    // 位置管理 (5+ メソッド)
    // リソース管理 (10+ メソッド)
}
```

#### 2. Player オブジェクトの曖昧な役割
- GameController で動的生成される一時オブジェクト
- Character への単純なプロキシとして機能
- View層での混在使用による混乱

#### 3. データフローの複雑性
```
Request → Controller → Character(DB) → Player(Object) → View → JavaScript
                    ↘                                   ↗
                      Session → Migration → Calculation
```

#### 4. コード重複の蔓延
- **位置計算ロジック**: 3箇所に重複（GameController, GameState, Player）
- **データ変換ロジック**: GameController, BattleController で重複
- **View層のCharacter取得**: 複数のBladeテンプレートで重複

## 🎯 根本的リファクタリング戦略

### 戦略: ドメイン駆動設計（DDD）による責任分離

#### 最終的な構造
```
Domain/
├── Character/          # ドメインロジック
│   ├── Character.php          # 基本エンティティ（100行程度）
│   ├── CharacterStats.php     # ステータス計算
│   └── CharacterSkills.php    # スキルロジック
├── Location/
│   ├── Location.php           # 位置エンティティ
│   └── LocationService.php    # 位置管理統一
└── Battle/
    └── BattleService.php      # 戦闘ロジック

Application/
├── Services/           # アプリケーションサービス
│   ├── GameDisplayService.php  # View用データ変換統一
│   ├── GameStateManager.php    # ゲーム状態管理
│   └── CharacterService.php    # Character操作統一
└── DTOs/              # データ転送オブジェクト
    ├── GameViewData.php        # View用統一データ
    ├── MoveResult.php          # 移動結果
    └── BattleData.php          # 戦闘用データ

Infrastructure/         # インフラストラクチャ
└── Repositories/
    └── CharacterRepository.php
```

## 📅 段階的実装タスクリスト

### Phase 1: Service層導入（推定: 3-4時間）

#### Task 1.1: LocationService 作成
**ファイル**: `app/Domain/Location/LocationService.php`
**目的**: 位置計算ロジックの統一
- [ ] `getCurrentLocation(Character $character): Location` 実装
- [ ] `getNextLocation(Character $character): ?Location` 実装  
- [ ] `calculateMovement(Character $character, int $steps): Position` 実装
- [ ] 既存の3箇所の重複ロジックを統合
- [ ] 単体テスト作成

**影響ファイル**:
- `app/Http/Controllers/GameController.php` (340-371行削除)
- `app/Models/GameState.php` (94-123行削除)
- `app/Models/Player.php` (位置計算メソッド削除)

#### Task 1.2: GameDisplayService 作成
**ファイル**: `app/Application/Services/GameDisplayService.php`  
**目的**: View用データ変換の統一
- [ ] `prepareGameView(Character $character): GameViewData` 実装
- [ ] Character→View用データの変換ロジック統合
- [ ] Player オブジェクト生成ロジックの置き換え
- [ ] 単体テスト作成

**影響ファイル**:
- `app/Http/Controllers/GameController.php` (123-140行置き換え)
- `app/Http/Controllers/BattleController.php` (502-505行置き換え)

#### Task 1.3: CharacterStatsService 作成
**ファイル**: `app/Domain/Character/CharacterStats.php`
**目的**: ステータス計算の分離
- [ ] レベル計算ロジックの抽出
- [ ] 装備効果計算の抽出
- [ ] スキルボーナス計算の抽出
- [ ] Character クラスから該当メソッドを移行
- [ ] 単体テスト作成

**影響ファイル**:
- `app/Models/Character.php` (計算メソッド150行程度を移行)

### Phase 2: DTO導入（推定: 2-3時間）

#### Task 2.1: GameViewData DTO 作成
**ファイル**: `app/Application/DTOs/GameViewData.php`
**目的**: View用データ構造の統一
- [ ] Character, Location, MovementInfo を含むDTO設計
- [ ] `toArray()` メソッド実装（Blade用）
- [ ] `toJson()` メソッド実装（JavaScript用）
- [ ] 型安全性の確保

#### Task 2.2: MoveResult DTO 作成
**ファイル**: `app/Application/DTOs/MoveResult.php`
**目的**: 移動結果の統一
- [ ] 移動成功/失敗の統一レスポンス
- [ ] エンカウント情報の包含
- [ ] 位置更新情報の包含
- [ ] Ajax レスポンス形式の統一

#### Task 2.3: BattleData DTO 作成
**ファイル**: `app/Application/DTOs/BattleData.php`
**目的**: 戦闘用データの統一
- [ ] 戦闘開始時のデータ構造統一
- [ ] 戦闘結果のデータ構造統一
- [ ] Character ステータスの戦闘用表現

### Phase 3: Controller純化（推定: 2-3時間）

#### Task 3.1: GameController リファクタリング
**ファイル**: `app/Http/Controllers/GameController.php`
**目的**: 387行 → 80行への削減
- [ ] `index()` メソッドの簡素化（GameDisplayService使用）
- [ ] `rollDice()` メソッドの簡素化（GameStateManager使用）
- [ ] `move()` メソッドの簡素化（GameStateManager使用）
- [ ] セッション管理ロジックをサービスに移管
- [ ] ビジネスロジックをサービスに移管

#### Task 3.2: GameStateManager 作成
**ファイル**: `app/Application/Services/GameStateManager.php`
**目的**: ゲーム状態管理の統一
- [ ] `rollDice(Character $character): DiceResult` 実装
- [ ] `moveCharacter(Character $character, MoveRequest $request): MoveResult` 実装
- [ ] `transitionLocation(Character $character, Location $destination): TransitionResult` 実装
- [ ] セッション→DB移行ロジックの統合

#### Task 3.3: Blade テンプレート更新
**対象ファイル**: 
- `resources/views/game/index.blade.php`
- `resources/views/game/partials/location_info.blade.php`
- `resources/views/game/partials/dice_container.blade.php`
- `resources/views/game/partials/next_location_button.blade.php`
- `resources/views/game/partials/movement_controls.blade.php`

**作業内容**:
- [ ] `$player` 変数を `$gameViewData` に統一
- [ ] Character直接参照の統一
- [ ] データアクセスパターンの統一

### Phase 4: Character分割（推定: 3-4時間）

#### Task 4.1: CharacterSkills Trait 分離
**ファイル**: `app/Domain/Character/CharacterSkills.php`
**目的**: スキルシステムの分離
- [ ] スキル関連メソッド（25個）をTraitに移行
- [ ] `learnSkill()`, `useSkill()`, `getSkillList()` 等
- [ ] Character からスキル関連コード削除
- [ ] 単体テスト作成

#### Task 4.2: CharacterEquipment Trait 分離
**ファイル**: `app/Domain/Character/CharacterEquipment.php`
**目的**: 装備システムの分離
- [ ] 装備関連メソッド（10個）をTraitに移行
- [ ] `getTotalStatsWithEquipment()`, `getOrCreateEquipment()` 等
- [ ] Character から装備関連コード削除
- [ ] 単体テスト作成

#### Task 4.3: CharacterBattle Trait 分離
**ファイル**: `app/Domain/Character/CharacterBattle.php`
**目的**: 戦闘システムの分離
- [ ] 戦闘関連メソッド（20個）をTraitに移行
- [ ] `takeDamage()`, `getBattleStats()`, `isAlive()` 等
- [ ] Character から戦闘関連コード削除
- [ ] 単体テスト作成

#### Task 4.4: Character クラス純化
**ファイル**: `app/Models/Character.php`
**目的**: 722行 → 150行への削減
- [ ] 基本属性とリレーションシップのみ保持
- [ ] Trait の適用
- [ ] 基本的なアクセサ・ミューテータのみ保持
- [ ] モデル単体テストの更新

### Phase 5: JavaScript整合性確保（推定: 1-2時間）

#### Task 5.1: game.js データ構造更新
**ファイル**: `public/js/game.js`
**目的**: 新しいDTO構造への対応
- [ ] `gameData.player` → `gameData.character` への変更
- [ ] Ajax レスポンス処理の更新
- [ ] UI更新ロジックの調整
- [ ] エラーハンドリングの改善

#### Task 5.2: API レスポンス統一
**対象メソッド**:
- `GameController::rollDice()`
- `GameController::move()`
- `GameController::moveToNext()`
- `BattleController::*`

**作業内容**:
- [ ] DTO を使用したレスポンス統一
- [ ] エラーレスポンスの統一
- [ ] 成功レスポンスの統一

## 📊 各フェーズの詳細工数見積もり

| Phase | タスク数 | 推定時間 | リスク度 | 優先度 |
|-------|---------|----------|----------|---------|
| Phase 1: Service層導入 | 3タスク | 3-4時間 | 中 | 最高 |
| Phase 2: DTO導入 | 3タスク | 2-3時間 | 低 | 高 |
| Phase 3: Controller純化 | 3タスク | 2-3時間 | 中 | 高 |
| Phase 4: Character分割 | 4タスク | 3-4時間 | 高 | 中 |
| Phase 5: JavaScript整合 | 2タスク | 1-2時間 | 低 | 中 |
| **合計** | **15タスク** | **11-16時間** | - | - |

## 🎯 期待される効果

### 定量的改善
- **Character**: 722行 → 150行（79%削減）
- **GameController**: 387行 → 80行（79%削減）
- **重複コード**: 3箇所 → 1箇所（統一）
- **循環的複雑度**: 大幅改善
- **保守性指数**: 大幅向上

### 定性的改善
- **単一責任原則**: 各クラス・サービスが明確な責任
- **開放閉鎖原則**: 新機能追加時の既存コード影響最小化
- **依存性逆転**: サービス層による疎結合
- **テスタビリティ**: 単体テスト・統合テストの容易化

## ⚠️ リスク管理

### 高リスク要因
- **Character分割**: 既存の複雑な依存関係
- **データ整合性**: セッション→DB移行ロジックの変更
- **パフォーマンス**: Service層追加による処理オーバーヘッド

### 緩和策
- **段階的実装**: Phase単位での部分適用・テスト
- **フィーチャーフラグ**: 段階的な機能切り替え
- **ロールバック準備**: 各Phase毎のコミット・ブランチ管理
- **テストカバレッジ**: 各Phase毎の包括的テスト

## 📋 実装チェックリスト

### Phase 1: Service層導入
- [ ] LocationService 作成・テスト
- [ ] GameDisplayService 作成・テスト  
- [ ] CharacterStatsService 作成・テスト
- [ ] 既存コードからの重複削除
- [ ] 統合テスト実行

### Phase 2: DTO導入
- [ ] GameViewData DTO作成
- [ ] MoveResult DTO作成
- [ ] BattleData DTO作成
- [ ] 型安全性確認
- [ ] シリアライゼーション確認

### Phase 3: Controller純化
- [ ] GameController リファクタリング
- [ ] GameStateManager 作成
- [ ] Blade テンプレート更新
- [ ] 機能テスト実行
- [ ] パフォーマンステスト

### Phase 4: Character分割
- [ ] CharacterSkills Trait作成
- [ ] CharacterEquipment Trait作成
- [ ] CharacterBattle Trait作成
- [ ] Character クラス純化
- [ ] 全機能テスト実行

### Phase 5: JavaScript整合性確保
- [ ] game.js データ構造更新
- [ ] API レスポンス統一
- [ ] フロントエンドテスト
- [ ] E2Eテスト実行

## 🚀 実装スケジュール

### 推奨実装順序
1. **準備**: 専用ブランチ作成 `feature/refactor-character-player-ddd`
2. **Phase 1**: Service層基盤構築（最重要）
3. **Phase 2**: データ構造統一  
4. **Phase 3**: Controller・View更新
5. **Phase 4**: Domain分割（慎重に）
6. **Phase 5**: フロントエンド調整
7. **統合**: 全体テスト・マージ

### マイルストーン
- **1週目**: Phase 1-2 完了
- **2週目**: Phase 3-4 完了  
- **3週目**: Phase 5・統合テスト・デプロイ

この根本的リファクタリングにより、**技術的負債の解消**と**持続可能な開発基盤の構築**を実現します。