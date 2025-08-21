# データ移行検討 - Admin Pathways管理システム

## 現在のデータ状況

### GameLocation テーブル（18エントリ）
- **Roads**: 8エントリ（dungeon_id = null）
- **Dungeons**: 4エントリ（dungeon_id = null）
- **Towns**: 6エントリ

### DungeonDesc テーブル（1エントリ）
- test_pyramid: "テストピラミッド"

### 現在の問題点
1. **ダンジョンGameLocationのdungeon_id未設定**: 既存のダンジョンロケーションがDungeonDescと関連付けられていない
2. **データ整合性の欠如**: ダンジョンロケーションとダンジョン定義の関係が不明確
3. **新システムとの統合**: 新しいRoad/Dungeon管理システムと既存データの整合性

## データ移行戦略

### Phase 5.1: データ監査と関係性マッピング

#### 実行すべき分析
1. **既存ダンジョンの関係性分析**
   ```sql
   -- 既存ダンジョンの詳細確認
   SELECT id, name, description, floors, boss 
   FROM game_locations 
   WHERE category = 'dungeon';
   ```

2. **DungeonDescとの潜在的関係特定**
   - `test_pyramid` DungeonDescに対応するGameLocationが存在するか確認
   - 既存ダンジョンが独立したダンジョンかフロアかの判定

3. **Road データの整合性確認**
   ```sql
   -- Road の基本情報確認
   SELECT id, name, length, difficulty, encounter_rate 
   FROM game_locations 
   WHERE category = 'road';
   ```

### Phase 5.2: データクリーンアップ計画

#### 必要な修正作業
1. **ダンジョンの分類と整理**
   - 独立ダンジョン → DungeonDesc作成
   - ダンジョンフロア → 既存DungeonDescに関連付け
   - 孤立データの特定と対処

2. **データ品質向上**
   - 必須フィールドの未入力確認
   - データ形式の統一
   - 無効なデータの修正

### Phase 5.3: 移行スクリプト作成

#### 推奨移行手順
1. **バックアップ作成**
   ```bash
   php artisan db:backup --tables=game_locations,dungeons_desc
   ```

2. **データ移行Seeder作成**
   - 既存ダンジョンの適切な分類
   - DungeonDescエントリの生成
   - GameLocationのdungeon_id更新

3. **検証スクリプト**
   - データ整合性チェック
   - リレーション検証
   - パフォーマンステスト

### Phase 5.4: 具体的な移行シナリオ

#### シナリオA: 最小限の変更
- 既存データをそのまま活用
- 欠落している関係性のみ修復
- **推奨度**: 高（リスク最小）

#### シナリオB: データ正規化
- ダンジョン構造の完全な再設計
- フロア概念の導入
- **推奨度**: 中（中期的利益あり）

#### シナリオC: 全面的な再構築
- 既存データの完全な再編成
- 新システムに最適化
- **推奨度**: 低（高リスク）

## 推奨実装プラン

### 段階的アプローチ（推奨）

#### Step 1: データ監査と分析
```php
// 既存データの詳細分析
$existingDungeons = GameLocation::where('category', 'dungeon')->get();
$existingRoads = GameLocation::where('category', 'road')->get();
$dungeonDescs = DungeonDesc::all();

// 関係性の確認とマッピング
// レポート生成
```

#### Step 2: 最小限の修正実装
```php
// 1. test_pyramid ダンジョンに対応するGameLocationの特定
// 2. 適切なdungeon_idの設定
// 3. データ整合性の確保
```

#### Step 3: 段階的な拡張
- 新しいダンジョン/ロードの追加時に新システム使用
- 既存データは段階的に新システムに移行
- 両システムの並行運用期間を設定

## 必要なツール・スクリプト

### 1. データ分析コマンド
```php
php artisan admin:analyze-pathways-data
```

### 2. データ移行コマンド
```php
php artisan admin:migrate-pathways-data [--dry-run] [--backup]
```

### 3. 整合性チェックコマンド
```php
php artisan admin:validate-pathways-data
```

## リスク評価と対策

### 高リスク
- **既存プレイヤーデータとの整合性**: プレイヤーの現在位置情報への影響
- **ゲームバランス**: encounter_rate等の変更によるゲーム性への影響

### 中リスク
- **システム停止時間**: 移行作業中のサービス停止
- **データロールバック**: 移行失敗時の復旧手順

### 低リスク
- **管理画面の表示**: 新しい管理画面での既存データ表示

## 実装タイムライン

### 準備期間（1-2日）
- データ分析
- 移行計画確定
- バックアップ戦略策定

### 実装期間（1-2日）
- 移行スクリプト作成
- テスト実行
- 本番適用

### 検証期間（1日）
- 移行結果確認
- 機能テスト
- パフォーマンス確認

## 成功の指標

1. **データ整合性**: すべてのGameLocationが適切なカテゴリとリレーションを持つ
2. **機能性**: 新しい管理画面ですべての既存データが正常に表示・編集可能
3. **パフォーマンス**: 移行後のクエリ性能が移行前と同等以上
4. **ゲーム性**: 既存のゲームバランスが維持されている

## 次のアクション

1. **データ分析の実行**: 現在のデータ構造の詳細分析
2. **関係者との調整**: データ変更の影響範囲確認
3. **移行ツールの開発**: 自動化された移行プロセスの構築
4. **テスト環境での検証**: 本番適用前の十分なテスト

---

**作成日**: 2025年8月20日  
**作成者**: Claude (Admin Pathways管理システム開発)  
**ステータス**: データ移行計画策定完了