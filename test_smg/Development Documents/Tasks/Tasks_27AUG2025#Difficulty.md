# Road管理システム難易度機能削除計画

**作成日**: 2025年8月27日  
**対象**: Roadおよび関連システムの難易度（difficulty）機能の完全削除  
**優先度**: 中  
**影響範囲**: 広範囲（DB、管理画面、分析システム、ゲームロジック）

## 概要

現在のRoad管理システムにおいて、難易度（difficulty）機能が設定されているが、この機能を完全に削除する。難易度は現在`easy`, `normal`, `hard`の3段階で設定されているが、ゲーム設計方針の変更により不要となった。

## 影響範囲調査結果

### データベース
- **routes テーブル**: `difficulty` ENUM カラム（nullable）
- **インデックス**: `difficulty` カラムにインデックス設定済み
- **外部参照**: なし（直接的な外部キー制約はなし）

### コントローラー層
- **AdminRoadController.php**: 
  - バリデーション: `required|in:easy,normal,hard`
  - 作成・更新処理で difficulty 必須
- **AdminDungeonController.php**: 
  - ダンジョンフロア作成でも同様の difficulty 使用
- **AdminController.php**: 
  - 汎用バリデーションで difficulty 定義

### サービス層
- **AdminRouteService.php**:
  - データエクスポート時の difficulty 含有
  - フィルタリング機能で difficulty による絞り込み
  - `getAvailableDifficulties()` メソッド存在

### モデル層
- **Route.php**:
  - fillable 配列に difficulty 含有
  - `scopeByDifficulty()` スコープメソッド存在

### ビュー層
- **Road管理画面**:
  - `_form.blade.php`: 難易度選択フォーム要素
  - `index.blade.php`: 一覧での難易度表示とバッジ
  - `show.blade.php`: 詳細画面での難易度表示
  - `create.blade.php`: JavaScript バリデーションで difficulty チェック
  - `edit.blade.php`: 編集時の JavaScript バリデーション

- **ダンジョン管理画面**:
  - `create-floor.blade.php`: フロア作成時の難易度設定
  - `floors.blade.php`: フロア一覧での難易度表示
  - `index.blade.php`: ダンジョン統計での高難易度フロア数表示
  - `show.blade.php`: ダンジョン詳細での難易度分析

- **その他管理画面**:
  - `locations/modules/basic-info.blade.php`: 基本情報表示
  - `monsters/spawn_lists/`: スポーンリスト関連

### 分析・ログシステム
- **BattleLog.php**:
  - `difficulty_progression` による戦闘難易度分析
  - `calculateDifficultyRating()` メソッド
- **AnalyticsController.php**:
  - ゲームバランス分析での難易度評価
  - `assessDifficultyBalance()` メソッド
  - 推奨事項生成での難易度考慮
- **AnalyzePathwaysData.php**:
  - データ分析コマンドでの難易度分布計算

## 削除計画

### フェーズ1: 準備段階（リスク最小化）

#### 1.1 データ保全対策
- [ ] 現在の difficulty データのバックアップ作成
- [ ] 削除対象データの完全一覧化
- [ ] テストデータでの削除手順検証

#### 1.2 依存関係の詳細分析
- [ ] difficulty を参照している全メソッドの動作確認
- [ ] 分析システムでの difficulty 依存処理の特定
- [ ] 外部システム（もしあれば）での difficulty 利用状況確認

### フェーズ2: コード修正（後方互換性維持）

#### 2.1 バリデーション修正
- [ ] **AdminRoadController.php**
  - `store()`: difficulty バリデーション削除
  - `update()`: difficulty バリデーション削除
- [ ] **AdminDungeonController.php**
  - フロア作成・更新での difficulty バリデーション削除
- [ ] **AdminController.php**
  - 汎用バリデーションルールから difficulty 削除

#### 2.2 サービス層修正
- [ ] **AdminRouteService.php**
  - `getAllLocationData()`: エクスポートデータから difficulty 除外
  - フィルタリング機能から difficulty オプション削除
  - `getAvailableDifficulties()` メソッド削除
  - `getLocationWithDetails()`: difficulty データ除外

#### 2.3 モデル修正
- [ ] **Route.php**
  - fillable 配列から difficulty 削除
  - `scopeByDifficulty()` メソッド削除

### フェーズ3: ビュー修正（管理画面から削除）

#### 3.1 Road管理画面修正
- [ ] **resources/views/admin/roads/_form.blade.php**
  - 難易度選択フォーム要素削除
  - バリデーションエラー表示削除
- [ ] **resources/views/admin/roads/index.blade.php**
  - 難易度カラム削除
  - 難易度バッジ表示削除
  - 難易度色分け配列削除
- [ ] **resources/views/admin/roads/show.blade.php**
  - 詳細画面から難易度情報削除
  - 関連する PHP 配列削除
- [ ] **resources/views/admin/roads/create.blade.php**
  - JavaScript バリデーションから difficulty チェック削除
  - フォーム送信時の difficulty 検証削除
- [ ] **resources/views/admin/roads/edit.blade.php**
  - JavaScript バリデーションから difficulty チェック削除

#### 3.2 ダンジョン管理画面修正
- [ ] **resources/views/admin/dungeons/create-floor.blade.php**
  - フロア作成フォームから難易度選択削除
- [ ] **resources/views/admin/dungeons/floors.blade.php**
  - フロア一覧から難易度表示削除
- [ ] **resources/views/admin/dungeons/index.blade.php**
  - 統計カードから高難易度フロア数削除
- [ ] **resources/views/admin/dungeons/show.blade.php**
  - ダンジョン詳細から難易度関連表示削除

#### 3.3 その他ビュー修正
- [ ] **resources/views/admin/locations/modules/basic-info.blade.php**
  - 基本情報から難易度表示削除
- [ ] モンスタースポーンリスト関連から難易度参照削除

### フェーズ4: 分析システム修正

#### 4.1 戦闘ログ分析修正
- [ ] **BattleLog.php**
  - `difficulty_progression` 分析機能の代替設計
  - `calculateDifficultyRating()` メソッドの代替実装
  - 戦闘統計での難易度要素削除

#### 4.2 アナリティクス修正
- [ ] **AnalyticsController.php**
  - `assessDifficultyBalance()` メソッド削除
  - 推奨事項生成から難易度関連削除
  - バランス分析の代替指標実装

#### 4.3 データ分析コマンド修正
- [ ] **AnalyzePathwaysData.php**
  - 難易度分布計算削除
  - レポート出力から難易度情報削除

### フェーズ5: データベース修正

#### 5.1 マイグレーション作成
- [ ] カラム削除マイグレーション作成
  ```php
  Schema::table('routes', function (Blueprint $table) {
      $table->dropIndex(['difficulty']);
      $table->dropColumn('difficulty');
  });
  ```

#### 5.2 既存データ処理
- [ ] 削除前データの最終バックアップ
- [ ] マイグレーション実行とロールバックテスト

### フェーズ6: 最終検証とクリーンアップ

#### 6.1 機能テスト
- [ ] Road 作成・編集・削除の正常動作確認
- [ ] ダンジョン管理機能の正常動作確認
- [ ] 分析システムの正常動作確認
- [ ] エラーログの確認

#### 6.2 パフォーマンス確認
- [ ] 削除後のクエリ性能確認
- [ ] インデックス削除によるパフォーマンス変化確認

#### 6.3 ドキュメント更新
- [ ] 開発ドキュメントの更新
- [ ] データベース設計書の更新
- [ ] API仕様書の更新（該当する場合）

## リスク分析と対策

### 高リスク要素
1. **分析システムへの影響**
   - リスク: 戦闘バランス分析機能の停止
   - 対策: 代替指標（エンカウント率、プレイヤーレベル等）の実装

2. **データ整合性**
   - リスク: 既存データの不整合
   - 対策: 段階的削除とバックアップ保持

3. **管理機能の一時停止**
   - リスク: Road/ダンジョン管理の一時利用不可
   - 対策: 各フェーズごとの動作確認

### 中リスク要素
1. **JavaScript エラー**
   - リスク: フロントエンドでの参照エラー
   - 対策: 段階的なJavaScript修正

2. **エクスポート機能**
   - リスク: データエクスポート形式の変更
   - 対策: 既存エクスポートデータとの互換性確保

## 実装順序

1. **準備段階**: データバックアップ、テスト環境での検証
2. **バックエンド修正**: コントローラー、サービス、モデルの修正
3. **フロントエンド修正**: ビューファイルの修正
4. **分析システム修正**: 代替機能の実装
5. **データベース修正**: カラム削除
6. **最終確認**: 全機能テストと性能確認

## 完了条件

- [ ] 全ての difficulty 参照が削除されている
- [ ] Road/ダンジョン管理が正常に動作する
- [ ] 分析システムが適切な代替指標で動作する
- [ ] データベースから difficulty カラムが削除されている
- [ ] 関連ドキュメントが更新されている
- [ ] パフォーマンスが維持されている

## 注意事項

1. **段階的実行**: 一度に全て削除せず、フェーズごとに動作確認
2. **バックアップ保持**: 各段階でのバックアップを確実に保持
3. **代替機能**: 分析システムでは代替指標を事前に準備
4. **テスト環境**: 本番環境への適用前に必ずテスト環境で検証

## 見積もり時間

- **準備・調査**: 4時間
- **コード修正**: 8時間
- **テスト・検証**: 6時間
- **ドキュメント更新**: 2時間
- **合計**: 約20時間（2.5日間相当）

## 担当者・承認

- **作成者**: Claude AI Assistant
- **レビュー者**: [未定]
- **承認者**: [未定]
- **実装者**: [未定]