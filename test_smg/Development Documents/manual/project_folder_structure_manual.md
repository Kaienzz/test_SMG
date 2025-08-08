# test_SMG プロジェクト フォルダー構造マニュアル

## 概要

本ドキュメントは、Laravel/PHPベースのブラウザRPGゲーム「test_smg」の開発フォルダー構造について詳しく解説します。このプロジェクトはDDD（Domain Driven Design）パターンとLaravelの標準的な構造を組み合わせた、モジュール化された設計になっています。

### プロジェクトの特徴
- Laravel 11.x ベースのWebアプリケーション
- ブラウザRPGゲーム（CGI風のUI）
- DDD アーキテクチャ採用
- フロントエンド・バックエンド統合型
- 包括的な開発文書体系

---

## ルートフォルダー構造

```
test_smg/
├── Development Documents/     # 開発文書・仕様書
├── README.md                  # プロジェクト基本情報
├── CLAUDE.md                  # AI開発アシスタント用指示書
├── app/                       # Laravel アプリケーションコア
├── resources/                 # フロントエンドリソース
├── public/                    # 公開ファイル（CSS/JS/画像）
├── database/                  # データベース関連
├── config/                    # Laravel設定ファイル
├── routes/                    # ルート定義
├── storage/                   # ログ・キャッシュ・一時ファイル
├── tests/                     # テストファイル
├── backup/                    # 開発中バックアップ
├── backups/                   # 正式バックアップ
├── bootstrap/                 # Laravel初期化
├── node_modules/              # Node.js依存関係
├── vendor/                    # Composer依存関係
├── composer.json              # PHP依存関係定義
├── package.json               # Node.js依存関係定義
└── 設定ファイル各種           # Laravel標準設定ファイル
```

---

## メインフォルダー詳細解説

### 📚 Development Documents/ - 開発文書

プロジェクトの全体設計と仕様を管理する重要なフォルダーです。

#### 00_project/ - プロジェクト基本方針
- **01_game_concept_requirements.md**: ゲームコンセプトと要件定義
- **02_inception_deck.md**: プロジェクト概要とビジョン

#### 01_development_docs/ - 開発技術仕様書
- **01_architecture_design_and_roles.md**: システムアーキテクチャ設計
- **02_database_design.md**: データベース設計詳細
- **03_api_design.md**: API設計仕様
- **04_screen_transition_design.md**: 画面遷移・UX設計
- **05_error_handling_design.md**: エラーハンドリング戦略
- **06_type_and_data_structure_design.md**: データ型・構造設計
- **07_development_environment_setup.md**: 開発環境構築手順
- **08_test_strategy.md**: テスト戦略・方針
- **09_frontend_design.md**: フロントエンド設計書
- **10_security_design.md**: セキュリティ設計
- **11_performance_optimization.md**: パフォーマンス最適化指針
- **12_performance_monitoring.md**: パフォーマンス監視方針

#### 02_design_system/ - デザインシステム仕様書
- **01_basic_design_system.md**: 基本デザインシステム定義
- **02_design_principles.md**: デザイン原則・思想
- **03_component_design.md**: UIコンポーネント設計
- **04_animation_system.md**: アニメーションシステム
- **05_layout_system.md**: レイアウトシステム

#### Notes/ - 実装記録・メモ
- **implemented_note.md**: 実装済み機能の詳細記録
- **Doc_List.md**: ドキュメント一覧・管理表

#### Tasks/ - 開発タスク管理
- **Tasks_[日付].md**: 日付別開発タスクリスト
- **Tasks_[機能名].md**: 機能別タスクリスト

#### manuals/ - 既存マニュアル類
- **item_addition_manual.md**: アイテム追加マニュアル
- **location_management_manual.md**: 場所管理マニュアル

### 🏗️ app/ - Laravel アプリケーションコア

DDD（Domain Driven Design）パターンを採用した、高度に構造化されたアプリケーションロジックです。

#### Application/ - アプリケーション層
```
Application/
├── DTOs/              # データ転送オブジェクト
│   ├── BattleData.php         # 戦闘情報DTO
│   ├── LocationData.php       # 場所情報DTO
│   ├── MoveResult.php         # 移動結果DTO
│   └── [その他DTO...]
└── Services/          # アプリケーションサービス
    ├── GameStateManager.php   # ゲーム状態管理
    ├── GameDisplayService.php # ゲーム表示サービス
    └── BattleStateManager.php # 戦闘状態管理
```

#### Domain/ - ドメイン層（ビジネスロジック）
```
Domain/
├── Character/         # キャラクター関連ドメイン
│   ├── CharacterEquipment.php
│   ├── CharacterInventory.php
│   └── CharacterSkills.php
├── Location/          # 場所・移動関連ドメイン
│   └── LocationService.php
└── Player/            # プレイヤー関連ドメイン
    ├── PlayerEquipment.php
    ├── PlayerInventory.php
    └── PlayerSkills.php
```

#### Http/ - HTTPレイヤー（Laravel標準）
```
Http/
├── Controllers/       # コントローラー
│   ├── GameController.php     # メインゲームコントローラー
│   ├── BattleController.php   # 戦闘コントローラー
│   ├── [Shop系]Controller.php # ショップ系コントローラー
│   └── Auth/                  # 認証関連
├── Requests/          # リクエストバリデーション
└── Traits/            # 共通トレイト
```

#### Models/ - Eloquentモデル
```
Models/
├── Player.php         # プレイヤーメインモデル
├── Character.php      # キャラクター情報
├── Item.php           # アイテム基本クラス
├── Items/             # アイテム種別クラス
│   ├── WeaponItem.php
│   ├── ArmorItem.php
│   └── ConsumableItem.php
├── Shop.php           # ショップモデル
├── Monster.php        # モンスターモデル
└── [その他ゲーム要素...]
```

#### Services/ - ビジネスサービス
```
Services/
├── BattleService.php          # 戦闘処理サービス
├── MovementService.php        # 移動処理サービス
├── [Shop系]Service.php        # ショップ関連サービス
└── ItemService.php            # アイテム管理サービス
```

#### その他重要フォルダー
- **Contracts/**: インターフェース定義
- **Enums/**: 列挙型定義
- **Factories/**: ファクトリークラス
- **Examples/**: 実装例・リファレンス

### 🎨 resources/ - フロントエンドリソース

ゲームUIとユーザー体験を担当するフロントエンドファイル群です。

#### views/ - Bladeテンプレート
```
views/
├── game.blade.php             # メインゲーム画面
├── game-states/               # ゲーム状態別ビュー
│   ├── town-sidebar.blade.php # 町画面サイドバー
│   ├── road-sidebar.blade.php # 道路画面サイドバー
│   └── battle-sidebar.blade.php # 戦闘画面サイドバー
├── shops/                     # ショップ系ビュー
│   ├── item/
│   ├── blacksmith/
│   ├── tavern/
│   └── alchemy/
├── auth/                      # 認証関連ビュー
├── layouts/                   # レイアウトテンプレート
└── components/                # 再利用可能コンポーネント
```

#### css/ - スタイルシート
- **game-unified-layout.css**: 統合レイアウトスタイル
- **game-design-system.css**: デザインシステム定義

#### js/ - JavaScript
- **game-unified.js**: 統合ゲームロジック
- **game.js**: 基本ゲーム機能

### 🌐 public/ - 公開ファイル

ブラウザから直接アクセス可能なファイル群です。

```
public/
├── index.php          # Laravel エントリーポイント
├── css/               # コンパイル済みCSS
│   ├── game-unified-layout.css
│   └── [その他スタイル]
├── js/                # コンパイル済みJavaScript
│   ├── game-unified.js
│   └── game.js
├── build/             # Viteビルド成果物
└── favicon.ico        # サイトアイコン
```

### 🗃️ database/ - データベース関連

ゲームデータの永続化を管理します。

```
database/
├── database.sqlite    # SQLiteデータベースファイル
├── migrations/        # データベースマイグレーション
├── seeders/           # 初期データ投入
│   ├── ShopSeeder.php
│   └── AlchemyMaterialsSeeder.php
└── factories/         # テストデータ生成
```

### 📋 config/ - 設定ファイル

Laravel標準設定ファイル群（通常は編集不要）

### 🛣️ routes/ - ルート定義

```
routes/
├── web.php            # Webルート定義
├── auth.php           # 認証ルート
└── console.php        # コンソールコマンド
```

### 🔄 backup/ & backups/ - バックアップ

#### backup/ - 開発中バックアップ
開発過程での一時的なバックアップファイル。日付付きフォルダーで管理。

#### backups/ - 正式バックアップ
重要なマイルストーン時のバックアップ。復元手順書付き。

---

## 🔧 開発時の注意点・ベストプラクティス

### 1. フォルダー構造の遵守
- 新しいファイルは適切なフォルダーに配置
- DDDパターンに従い、ドメイン層とアプリケーション層を分離
- Laravel規約に準拠した命名規則を使用

### 2. 文書の更新
- 新機能追加時は `implemented_note.md` を更新
- API変更時は `03_api_design.md` を更新
- 設計変更時は対応するDesign Documentを更新

### 3. バックアップ管理
- 重要な変更前は `backup/` にスナップショット作成
- マイルストーン達成時は `backups/` に正式バックアップ

### 4. コード品質
- 各層の責任を明確に分離
- インターフェースを活用した疎結合設計
- テストファイルの作成・維持

---

## 🚀 新機能追加ガイドライン

### 1. 計画段階
1. **要件定義**: `Development Documents/` で仕様を明文化
2. **設計検討**: 既存アーキテクチャとの整合性確認
3. **タスク管理**: `Tasks/` フォルダーでタスクリスト作成

### 2. 実装段階
1. **モデル作成**: `app/Models/` に新規モデル
2. **マイグレーション**: `database/migrations/` にDB変更
3. **サービス実装**: `app/Services/` にビジネスロジック
4. **コントローラー**: `app/Http/Controllers/` にHTTPハンドラー
5. **ビュー作成**: `resources/views/` にUI実装

### 3. テスト・文書化
1. **テスト作成**: `tests/` にテストファイル
2. **文書更新**: 対応するDesign Documentを更新
3. **実装記録**: `implemented_note.md` に詳細記録

---

## 🔍 よく使用するフォルダー・ファイル

### 日常開発で頻繁にアクセスするファイル

| ファイル・フォルダー | 用途 | 頻度 |
|---|---|---|
| `app/Http/Controllers/GameController.php` | メインゲームロジック | 非常に高 |
| `resources/views/game-states/` | ゲームUI実装 | 高 |
| `public/js/game-unified.js` | フロントエンドロジック | 高 |
| `app/Models/Player.php` | プレイヤー情報管理 | 高 |
| `Development Documents/Notes/implemented_note.md` | 実装記録確認 | 中 |
| `database/migrations/` | DB構造変更 | 中 |
| `CLAUDE.md` | プロジェクト開発指針 | 中 |

### 設計・仕様確認時にアクセスするファイル

| ファイル | 確認事項 |
|---|---|
| `01_development_docs/02_database_design.md` | DB設計詳細 |
| `01_development_docs/03_api_design.md` | API仕様 |
| `02_design_system/01_basic_design_system.md` | UI/UXガイドライン |
| `Tasks/Tasks_[最新].md` | 現在のタスク状況 |

---

## 📝 補足事項

### プロジェクト特有の構造について

1. **DDD採用**: `app/Domain/` と `app/Application/` による層分離
2. **ゲーム専用DTO**: `app/Application/DTOs/` でゲーム状態管理
3. **状態管理**: `GameStateManager` による一元的なゲーム状態制御
4. **モジュラー設計**: ショップ、戦闘、移動等の機能別分離

### 開発効率向上のためのヒント

- **CLAUDE.md**: AI開発支援のための指示書、プロジェクト方針の基準
- **implemented_note.md**: 既存機能の動作確認に便利
- **backup/**: 大きな変更前のセーフティネット
- **Development Documents/**: 設計判断に迷った時の参考資料

---

*このマニュアルは今後の開発を効率化するために作成されました。プロジェクトの成長に合わせて随時更新してください。*

**作成日**: 2025年8月8日  
**対象**: test_SMG 開発チーム  
**版数**: v1.0