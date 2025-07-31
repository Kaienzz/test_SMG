# Location仕様変更タスク

## 概要
町や道の移動システムの仕様を拡張し、以下の機能を実装する：
- 町の複数道路接続（1-4つの道路）
- T字路・交差点システム（道中での分岐）
- 道路の命名システム
- ダンジョンシステムの準備
- 拡張性の向上

## 現在の実装状況
- LocationService: town_a ↔ road_1 ↔ road_2 ↔ road_3 ↔ town_b の線形接続
- 各町は1つの道路接続のみ
- 道路名は「道路{番号}」の自動生成
- 0-100位置システムでの移動
- ダンジョンタイプは部分対応済み

## Phase 1: 道路命名システムの実装

### 1.1 LocationServiceの道路名機能拡張
**ファイル**: `app/Domain/Location/LocationService.php`
- [ ] `getLocationName()` メソッドの拡張
  - 道路ID → カスタム道路名のマッピング追加
  - 道路名定義配列の作成（例：'road_1' => 'プリマ街道'）
- [ ] 道路名管理の設定配列作成
  - プライベートプロパティで道路名を管理
  - 将来的な外部設定ファイル化の準備

### 1.2 フロントエンド表示の更新
**ファイル**: `resources/views/game/partials/location_info.blade.php`
- [ ] 道路名表示の確認・更新（既に対応済みの可能性）
- [ ] 道路名が正しく表示されるかテスト

### 1.3 データ管理方式の検討
- [ ] コード埋め込み vs 設定ファイル vs データベースの比較
- [ ] 現段階では LocationService 内の配列で管理（拡張性を考慮した構造）

## Phase 2: T字路・交差点システムの実装

### 2.1 LocationServiceの分岐判定機能追加
**ファイル**: `app/Domain/Location/LocationService.php`
- [ ] 位置50（または他の指定位置）での分岐判定機能
  - `getMiddleRoadConnections($locationId, $position)` メソッドの追加
  - 分岐可能道路の定義（例：road_2の位置50で分岐可能）
- [ ] 分岐先データ構造の定義
  - 分岐方向（straight, left, right）と接続先のマッピング
  - 現在の `getNextLocationFromRoad()` を拡張

### 2.2 分岐選択UIの実装
**ファイル**: `resources/views/game/partials/movement_controls.blade.php`（新規作成）
- [ ] 分岐位置での選択ボタン表示
  - 「直進」「左折」「右折」ボタン
  - 各方向の行き先表示
- [ ] 現在の移動ボタンとの統合
  - 分岐位置では通常の移動ボタンを非表示
  - 分岐選択ボタンを表示

### 2.3 GameStateManagerの分岐移動処理
**ファイル**: `app/Application/Services/GameStateManager.php`
- [ ] `moveToBranch()` メソッドの追加
  - 分岐選択時の移動処理
  - バリデーションと位置更新
- [ ] `moveToNextLocation()` メソッドの拡張
  - 分岐選択パラメータの受け取り

### 2.4 JavaScriptの分岐処理実装
**ファイル**: `public/js/game.js`
- [ ] 分岐選択関数の追加
  - `selectBranch(direction)` 関数
  - 分岐UI表示・非表示制御

## Phase 3: 複数接続システムの実装

### 3.1 LocationServiceの複数接続対応
**ファイル**: `app/Domain/Location/LocationService.php`
- [ ] `getNextLocationFromTown()` メソッドの拡張
  - 単一戻り値から複数接続配列への変更
  - 方向情報付きの接続データ（north, south, east, west）
- [ ] `getLocationConnections()` メソッドの拡張
  - 現在の左右接続から4方向接続への拡張
  - 町の複数道路接続定義

### 3.2 複数接続選択UIの実装
**ファイル**: `resources/views/game/partials/next_location_button.blade.php`
- [ ] 単一ボタンから複数選択への拡張
  - 町で複数道路がある場合の選択UI
  - 方向別ボタン表示（北へ：○○街道、南へ：○○道路）
- [ ] 既存の単一接続との互換性維持

### 3.3 GameStateManagerの複数接続処理
**ファイル**: `app/Application/Services/GameStateManager.php`  
- [ ] `moveToSpecificNext()` メソッドの追加
  - 特定の方向・道路を指定した移動処理
  - 現在の `moveToNextLocation()` との分離

### 3.4 JavaScriptの複数接続処理
**ファイル**: `public/js/game.js`
- [ ] 複数選択肢対応の移動処理
  - `moveToSpecificNext(roadId)` 関数
  - 複数ボタンの表示制御

## Phase 4: ダンジョンシステムの準備

### 4.1 LocationServiceのダンジョン対応拡張
**ファイル**: `app/Domain/Location/LocationService.php`
- [ ] `getLocationName()` メソッドのダンジョン対応
  - 'dungeon' タイプでの名前取得処理
  - ダンジョン名の管理（例：'dungeon_1' => '古の洞窟'）
- [ ] ダンジョン接続の基本構造
  - 町とダンジョン入口の接続
  - ダンジョン内の簡単な移動システム

### 4.2 ダンジョン基本データ構造の準備
- [ ] ダンジョンの基本情報管理
  - フロア数、入口・出口情報
  - 単層ダンジョンからの実装開始
- [ ] 既存の移動システムとの統合
  - `getLocationStatus()` での 'dungeon' 対応確認
  - `calculateMovement()` でのダンジョン内移動

### 4.3 UI基盤の準備
- [ ] ダンジョン表示の基本対応
  - `location_info.blade.php` でのダンジョン表示
  - 町・道路との表示切り替え
- [ ] 将来拡張のための構造整備
  - ダンジョン専用CSSクラスの準備
  - JavaScript でのダンジョンタイプ識別

## Phase 5: 拡張性とメンテナンス性の向上

### 5.1 設定の構造化
- [ ] LocationService 内での設定配列の整理
  - 道路名、接続情報、分岐情報の統一配列
  - 将来の設定ファイル化を見据えた構造
- [ ] 場所追加のためのヘルパーメソッド作成
  - `addTownConnection()` などの内部メソッド

### 5.2 拡張性の確保
- [ ] 新しい町・道路追加のガイドライン作成
  - LocationService での追加手順の明文化
  - テストケースのテンプレート作成
- [ ] エラーハンドリング強化
  - 存在しない場所IDへの対応
  - 接続不整合の検出

### 5.3 パフォーマンス考慮
- [ ] 複雑な接続計算の最適化検討
- [ ] 頻繁にアクセスされるデータのキャッシュ検討

## Phase 6: テストとドキュメント

### 6.1 機能テストの実装
- [ ] 各Phase完了後の動作確認
  - 道路名表示のテスト
  - T字路分岐のテスト
  - 複数接続選択のテスト

### 6.2 ドキュメント更新
- [ ] `location_management_manual.md` の更新
  - 新機能の追加手順
  - T字路・複数接続の設定方法
- [ ] コード内ドキュメントの充実
  - LocationService のメソッドコメント強化

### 6.3 統合確認
- [ ] 既存機能の互換性確認
- [ ] ユーザー体験の一貫性確認
- [ ] エラーケースの適切な処理確認

## 実装サンプルと段階的構築

### Phase 1完了後の道路名例
```
現在: town_a ↔ road_1 ↔ road_2 ↔ road_3 ↔ town_b
変更後: A町 ↔ プリマ街道 ↔ 中央大通り ↔ 港湾道路 ↔ B町
```

### Phase 2完了後のT字路例  
```
A町 ↔ プリマ街道（位置50でT字路）
                ├── 直進: 中央大通り → B町
                └── 右折: 山道 → 山の村
```

### Phase 3完了後の複数接続例
```
C町（3方向接続）
├── 東: プリマ街道 → A町
├── 南: 森林道路 → エルフの村
└── 北: 商業街道 → 商業都市
```

## 実装優先順位

### 最優先（即座に実装可能）
1. **Phase 1**: 道路命名システム
   - 既存構造への単純追加
   - リスク低、効果高

### 高優先（慎重な設計が必要）
2. **Phase 2**: T字路・交差点システム
   - 新規UI実装が必要
   - ゲーム体験への大きな影響

3. **Phase 3**: 複数接続システム  
   - Phase 2の知見を活用可能
   - UI/UX設計の一貫性確保

### 中優先（将来への投資）
4. **Phase 4**: ダンジョン基盤準備
   - 他機能の実装後に着手
   - 拡張機能の基盤整備

### 低優先（最適化・保守）
5. **Phase 5-6**: 拡張性・テスト・ドキュメント
   - 主要機能完成後の品質向上

## 重要な互換性方針
- **後方互換性**: 既存のプレイヤーデータは影響を受けない
- **段階的展開**: 各Phase独立して動作確認
- **フェールセーフ**: 新機能エラー時は既存動作にフォールバック
- **UI一貫性**: 既存の操作方法を保持しつつ新機能を追加