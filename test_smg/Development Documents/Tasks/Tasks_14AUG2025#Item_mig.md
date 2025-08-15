# 標準アイテム移行タスクリスト

## プロジェクト概要
現在DummyDataServiceでハードコードされている標準アイテムを、段階的にJSONファイル管理経由でデータベースへ移行する。インベントリシステムとの統合も同時に実施。

## 現在の実装状況

### 標準アイテム（ハードコード）
- **場所**: `app/Services/DummyDataService.php`の`getStandardItems()`メソッド
- **数量**: 10個のアイテム（ポーション3個、武器3個、防具3個、素材1個）
- **形式**: PHP配列として直接定義
- **特徴**: 
  - `is_standard => true`フラグ付き
  - `std_*`形式のID（例：`std_1`, `std_2`）

### 錬金アイテム（データベース管理）
- **ベースアイテム**: `items`テーブル
- **カスタムアイテム**: `custom_items`テーブル（`base_item_id`で`items`を参照）
- **モデル**: `Item.php`, `CustomItem.php`

### インベントリシステム（現状）
- **テーブル**: `inventories`テーブル（player_id/character_id対応）
- **データ形式**: `slot_data`にJSON形式でアイテム情報を保存
- **アイテム参照方法**: 
  - `Item::findSampleItem($itemName)` - 標準アイテムを名前で検索
  - DummyDataServiceのハードコードデータに依存
- **依存関係**:
  - `InventoryController` → `Item::findSampleItem()` → `DummyDataService::getStandardItems()`
  - `Inventory.php` → `Item::findSampleItem()` → 同上
  - `ItemShopService` → `Item::findSampleItem()` → 同上
- **重要機能**:
  - アイテム追加・削除・使用
  - スロット管理（最大20スロット、デフォルト10）
  - スタック管理（アイテム種別により異なる）
  - アイテム効果適用（HP/MP/SP回復等）

## 移行フェーズ

### フェーズ1: JSON移行準備 ⭐ 優先度：高
**目標**: 標準アイテムをJSONファイルで管理できる仕組みを構築

#### 1-1: JSON設計・ファイル作成
- [ ] **標準アイテムJSONスキーマ設計**
  - 既存の配列構造をベースにJSON形式設計
  - バリデーションルール定義
  - ファイル配置場所決定（`storage/app/data/standard_items.json`推奨）
- [ ] **JSON形式で標準アイテムデータ変換**
  - DummyDataServiceの10個のアイテムデータをJSONに変換
  - データ整合性チェック
- [ ] **JSONスキーマバリデーション実装**
  - JSON構造検証クラス作成
  - バリデーションエラーハンドリング

#### 1-2: JSONリーダーサービス実装
- [ ] **StandardItemService作成**
  - JSONファイル読み込み機能
  - キャッシュ機能（パフォーマンス対策）
  - エラーハンドリング（ファイル不存在・破損時）
- [ ] **既存インターフェース互換性保持**
  - `DummyDataService::getStandardItems()`と同じ形式で返す機能
  - 既存コード影響ゼロを保証
- [ ] **ItemモデルのfindSampleItem更新**
  - JSONベースのアイテム検索に対応
  - 後方互換性確保（ハードコードからJSON両対応）

#### 1-3: JSON移行切り替え＋インベントリ対応
- [ ] **設定ベース切り替え機能実装**
  - 環境変数または設定ファイルでハードコード/JSON切り替え
  - デフォルトはハードコード（安全性重視）
- [ ] **DummyDataService修正**
  - JSONモードの場合にStandardItemServiceを使用
  - レガシーモード（ハードコード）も維持
- [ ] **インベントリシステム互換性確保**
  - `InventoryController`の動作確認
  - `ItemShopService`の動作確認
  - アイテム追加・削除・使用の動作確認
- [ ] **統合テスト実行**
  - JSON形式とハードコード形式の出力比較テスト
  - インベントリ機能の完全動作確認
  - ショップ機能の完全動作確認

### フェーズ2: データベース移行準備 ⭐ 優先度：中
**目標**: データベースで標準アイテムを管理する基盤を構築

#### 2-1: データベース設計拡張
- [ ] **標準アイテム判別機能追加**
  - `items`テーブルに`is_standard`カラム追加（migration作成）
  - `standard_item_id`カラム追加（既存標準アイテムとの紐づけ用）
- [ ] **標準アイテムシーダー作成**
  - JSONファイルからデータベースへのデータ投入処理
  - 既存データとの重複チェック機能
  - ロールバック機能

#### 2-2: 標準アイテムデータベースサービス＋インベントリ統合
- [ ] **StandardItemDatabaseService作成**
  - データベースから標準アイテム取得機能
  - キャッシュ機能（Redis推奨）
  - パフォーマンス最適化
- [ ] **ItemモデルとInventoryモデルの統合**
  - `Item::findSampleItem()`をDB対応に更新
  - `Inventory`モデルのアイテム参照をDB経由に変更
  - `slot_data`内のアイテム情報とDBアイテムの整合性確保
- [ ] **移行用コマンド作成**
  - `php artisan standard-items:migrate`コマンド
  - JSONからデータベースへのマイグレーション機能
  - 既存インベントリデータのアイテム参照更新
  - 進捗表示・エラーレポート機能

#### 2-3: ハイブリッド運用機能＋インベントリ対応
- [ ] **3段階切り替えシステム実装**
  - ハードコード → JSON → データベース の段階的切り替え
  - 設定ファイル（config/items.php）での管理
- [ ] **インベントリシステムのアイテム参照統一**
  - `InventoryController`、`ItemShopService`の参照方法統一
  - アイテムID vs アイテム名の参照方法統一
  - 既存のslot_dataとの互換性保持
- [ ] **フォールバック機能実装**
  - データベース障害時にJSONへの自動フォールバック
  - JSONファイル破損時にハードコードへのフォールバック
  - インベントリ機能の継続動作保証

### フェーズ3: 本格移行・最適化 ⭐ 優先度：低
**目標**: データベース移行完了と既存システムとの統合

#### 3-1: 完全データベース移行
- [ ] **標準アイテムマスターテーブル正規化**
  - 標準アイテムと錬金アイテムの統合テーブル設計検討
  - 既存の`custom_items`テーブルとの関係性整理
- [ ] **管理画面での標準アイテム管理機能**
  - AdminItemControllerに標準アイテム管理機能追加
  - CRUD機能実装（作成・編集・削除・一覧）
  - バックアップ・復元機能

#### 3-2: パフォーマンス最適化
- [ ] **クエリ最適化**
  - N+1問題回避
  - インデックス設計見直し
  - クエリキャッシュ最適化
- [ ] **メモリ使用量最適化**
  - 大量アイテムデータの効率的読み込み
  - ページネーション実装

#### 3-3: レガシーコード整理
- [ ] **DummyDataService段階的廃止**
  - 標準アイテム関連コードの削除
  - プレイヤーデータやその他機能は維持
- [ ] **テストコード更新**
  - データベースベースのテスト体制構築
  - モックデータ作成の自動化

## 実行順序・スケジュール

### Week 1-2: フェーズ1実装
1. JSON設計・ファイル作成 (2日)
2. StandardItemService実装 (2日)
3. 切り替え機能・テスト (3日)

### Week 3-4: フェーズ2実装
1. データベース設計・マイグレーション (2日)
2. DatabaseService・コマンド実装 (3日)
3. ハイブリッド運用機能 (2日)

### Week 5-6: フェーズ3実装（optional）
1. 管理画面機能実装 (3日)
2. パフォーマンス最適化 (2日)
3. レガシーコード整理 (2日)

## リスク要因・対策

### 高リスク
- **データ整合性リスク**: 移行時のデータ破損・不整合
  - **対策**: 段階的移行、フォールバック機能、十分なテスト
- **インベントリデータ破損リスク**: 既存の`slot_data`とアイテム参照の不整合
  - **対策**: データ移行前のバックアップ、段階的なアイテム参照更新、ロールバック機能
- **パフォーマンス劣化**: ハードコードからDB読み込みへの変更による遅延
  - **対策**: キャッシュ機能、インデックス最適化、ベンチマーク実施

### 中リスク
- **既存機能への影響**: アイテムシステムの変更による他機能への影響
  - **対策**: インターフェース互換性保持、十分な統合テスト
- **インベントリ・ショップ機能の停止**: アイテム参照方法変更による機能停止
  - **対策**: 段階的切り替え、リアルタイム動作確認、即座のロールバック体制
- **運用複雑化**: 3段階の切り替えシステムによる運用複雑化
  - **対策**: 明確な運用マニュアル作成、ログ機能充実

### 低リスク
- **開発工数増加**: 予想以上の実装時間
  - **対策**: フェーズ分割による段階的実装、優先度の明確化
- **アイテム名称の不整合**: ハードコード→JSON→DBでの名称変更による混乱
  - **対策**: 名称マッピングテーブル、移行時の名称統一チェック

## 成功指標

### フェーズ1完了時
- [ ] JSON切り替えが正常動作（ゼロダウンタイム）
- [ ] 全既存機能が影響なく動作（インベントリ、ショップ含む）
- [ ] インベントリのアイテム追加・削除・使用が正常動作
- [ ] ショップでの購入・売却が正常動作
- [ ] パフォーマンス劣化なし

### フェーズ2完了時
- [ ] データベース移行が正常完了
- [ ] 既存インベントリデータの整合性保持
- [ ] 3段階切り替えシステムが安定動作
- [ ] インベントリ・ショップ機能のDB対応完了
- [ ] 管理機能の基本動作確認

### 最終完了時
- [ ] 標準アイテムの完全データベース管理
- [ ] インベントリシステムの完全DB統合
- [ ] 管理画面での運用開始
- [ ] レガシーコードの段階的削除完了
- [ ] パフォーマンス目標達成（応答時間維持）
- [ ] 全アイテム関連機能の安定動作確認

## 補足事項

### 技術選択理由
- **JSON経由移行**: 段階的移行によるリスク軽減
- **フォールバック機能**: サービス可用性確保
- **キャッシュ活用**: パフォーマンス確保
- **インベントリ統合優先**: 実際に使用される重要機能から対応

### インベントリシステムとの統合要点
- **アイテム参照の統一**: 名前ベース→IDベースへの段階的移行
- **slot_data互換性**: 既存インベントリデータを破損させない設計
- **リアルタイム動作保証**: アイテム操作中の参照先切り替えでも正常動作

### 今後の拡張性
- 将来的にはユーザー作成アイテム（錬金以外）の追加も視野
- APIベースでの外部アイテムデータ連携も可能な設計
- 多言語対応の準備（nameやdescriptionの国際化）
- インベントリの高度な機能（フィルタリング、ソート）への拡張対応

---

## 実装完了報告 (2025-08-15)

### ✅ 完了済み実装

#### フェーズ1: JSON移行準備 (完了)
- ✅ 標準アイテムJSONスキーマ設計・ファイル作成
  - `storage/app/data/standard_items.json` (10個のアイテムデータ)
  - スキーマバージョン1.0、バリデーション機能
- ✅ StandardItemService実装完了
  - JSON読み込み、キャッシュ、バリデーション機能
  - 統計情報取得、エラーハンドリング
- ✅ 3段階切り替えシステム実装
  - `config/items.php`で設定管理
  - hardcoded/json/database対応
- ✅ DummyDataService修正
  - フォールバック機能付き分岐処理
  - 既存インターフェース互換性保持

#### フェーズ2: データベース移行準備 (完了・要見直し)
- ✅ データベース設計拡張
  - migration実行済み (2025_08_14_074623_add_standard_item_fields_to_items_table)
  - `is_standard`、`standard_item_id`等のカラム追加
- ✅ StandardItemDatabaseService実装
  - データベースからの標準アイテム取得
  - キャッシュ機能、CRUD操作
- ✅ StandardItemSeeder実装
  - JSONからデータベースへの移行処理
  - 10個の標準アイテムをDBに投入済み
- ✅ Itemモデル更新
  - fillable配列に新カラム追加
  - casts設定追加

#### 統合テスト (完了)
- ✅ 3段階切り替えシステム動作確認
  - hardcoded/json/database全てで正常動作
- ✅ インベントリシステム統合テスト
  - アイテム検索・追加・使用・削除の動作確認
  - データ整合性テスト完了
- ✅ 全ソースでの統合動作確認

### 🔄 アーキテクチャ決定・整理作業 (2025-08-15)

#### 決定事項
- **標準アイテム**: JSONファイルをマスターとする
- **錬金アイテム**: データベース管理 (custom_items テーブル)
- データベースでの標準アイテム管理は廃止

#### 削除対象の実装
1. **データベース標準アイテム関連**
   - StandardItemDatabaseService.php (削除予定)
   - StandardItemSeeder.php (削除予定)
   - データベース内標準アイテムデータ (削除予定)
   - migration rollback予定

2. **テスト用コマンド**
   - TestStandardItemsCommand.php (削除予定)
   - TestInventoryIntegrationCommand.php (削除予定) 
   - StandardItemsTestCommand.php (削除予定)

3. **不要なDummy実装**
   - DummyDataServiceのハードコード標準アイテムデータ (削除予定)
   - 3段階切り替えロジック (JSON固定に変更予定)

4. **Itemモデル不要機能**
   - createSampleItems関数 (削除予定)
   - database/hardcoded分岐処理 (削除予定)

#### 整理後の構成
- **標準アイテム取得**: StandardItemService (JSON) のみ
- **設定**: config/items.php簡素化
- **インベントリ**: Item::findSampleItem() → StandardItemService経由
- **錬金システム**: 既存のCustomItemモデル継続使用

### ✅ アーキテクチャ整理完了 (2025-08-15)

#### 削除完了ファイル・機能
1. **データベース標準アイテム関連**
   - ✅ StandardItemDatabaseService.php (削除)
   - ✅ StandardItemSeeder.php (削除) 
   - ✅ データベース内標準アイテムデータ (10個削除)
   - ✅ migration rollback (2025_08_14_074623_add_standard_item_fields_to_items_table)

2. **テスト用コマンド**
   - ✅ TestStandardItemsCommand.php (削除)
   - ✅ TestInventoryIntegrationCommand.php (削除)
   - ✅ StandardItemsTestCommand.php (削除)

3. **不要なDummy実装**
   - ✅ DummyDataServiceのハードコード標準アイテムデータ (削除)
   - ✅ 3段階切り替えロジック (JSON固定に変更)

4. **Itemモデル不要機能**
   - ✅ createSampleItems関数 (削除)
   - ✅ database/hardcoded分岐処理 (削除)
   - ✅ fillable/castsから標準アイテム関連フィールド削除

5. **その他**
   - ✅ ItemSystemUsage.php (関係ない実装例削除)

#### 最終動作確認
- ✅ 標準アイテム取得: 10個正常取得
- ✅ Item::findSampleItem(): 正常動作
- ✅ インベントリ統合: アイテム追加・使用・削除正常動作
- ✅ StandardItemService統計情報: 正常動作

#### 最終アーキテクチャ
- **標準アイテム**: JSONファイル (`storage/app/data/standard_items.json`) - 10個
- **錬金アイテム**: データベース (`custom_items` テーブル)
- **設定管理**: `config/items.php` (JSON パス・キャッシュ設定のみ)
- **アイテム取得**: `StandardItemService` → `DummyDataService::getStandardItems()`
- **インベントリ統合**: `Item::findSampleItem()` → `StandardItemService::findByName()`

### ✅ 管理パネル実装完了 (2025-08-15 後半)

#### 標準アイテム管理機能実装
- ✅ AdminItemController 標準アイテム管理機能追加
  - standardItems(): 標準アイテム一覧表示・検索・フィルタリング機能
  - createStandardItem(): 新規標準アイテム作成機能
  - updateStandardItem(): 標準アイテム編集機能
  - showStandardItem(): 標準アイテム詳細表示機能
  - editStandardItem(): 標準アイテム編集フォーム機能

#### ビューファイル実装
- ✅ resources/views/admin/items/standard.blade.php
  - 標準アイテム一覧・検索・フィルタリング画面
  - カテゴリ別表示、エフェクト表示、価格表示機能
- ✅ resources/views/admin/items/standard-edit.blade.php
  - 標準アイテム編集フォーム（全項目対応）
  - バリデーション、プレビュー機能
- ✅ resources/views/admin/items/standard-show.blade.php
  - 標準アイテム詳細表示画面
- ✅ resources/views/admin/items/standard-create.blade.php
  - 標準アイテム新規作成フォーム

#### ルーティング実装
- ✅ routes/admin.php 標準アイテム管理ルート追加
  - GET /admin/items/standard - 一覧表示
  - GET /admin/items/standard/create - 新規作成
  - POST /admin/items/standard - 新規作成処理
  - GET /admin/items/standard/{id} - 詳細表示  
  - GET /admin/items/standard/{id}/edit - 編集フォーム
  - PUT /admin/items/standard/{id} - 編集処理

#### StandardItemService 機能拡張
- ✅ findById(): IDベースの標準アイテム検索機能
- ✅ getFullData(): JSONファイル全データ取得機能  
- ✅ getCachedData(): キャッシュされたデータ取得機能
- ✅ clearCache(): キャッシュクリア機能

#### バグ修正完了
- ✅ Collection::total() メソッドエラー修正
  - 修正場所: resources/views/admin/items/index.blade.php:130
  - 原因: Collection にない total() メソッドを呼び出し
  - 対処: pagination データまたは count() を使用に変更

- ✅ 配列プロパティアクセスエラー修正
  - 修正場所: resources/views/admin/items/index.blade.php (複数箇所)
  - 原因: コントローラーから配列で返されるデータをオブジェクトプロパティ記法でアクセス
  - 対処: `$item->name` → `$item['name']` に全て変更 (15箇所修正)

#### アーキテクチャ整理
- ✅ index.blade.php: 標準アイテム・カスタムアイテム混在表示対応
- ✅ 配列アクセス統一: 混在データの統一的なアクセス方法実装
- ✅ ルート分離: 標準アイテム管理と通常アイテム管理の明確な分離

#### 動作確認状況
- ✅ 標準アイテムJSONファイル: 正常読み込み (10個)
- ✅ 管理画面アクセス: エラー解消済み
- ✅ データ表示形式: 配列アクセス統一完了
- ⚠️ ログイン認証: 未テスト (サーバー起動中・認証必要)

---

**作成日**: 2025-08-14  
**更新日**: 2025-08-15  
**担当**: Claude Code  
**ステータス**: 移行・整理・管理機能実装完了 ✅