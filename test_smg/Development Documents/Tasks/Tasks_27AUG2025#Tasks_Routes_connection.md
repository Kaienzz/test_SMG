# Routes_Connection 実装タスクリスト

作成日: 2025-08-27  
参照文書: `/workspaces/test_SMG/test_smg/Development Documents/Tasks/Tasks_27AUG2025#Routes_connection.md`

## 概要

Routes_Connectionデータ管理ベストプラクティス設計文書に基づく実装タスク。
主要な変更点：
- Bidirectional廃止 → 有向エッジに統一
- position分離 → source_position/target_position
- action_label ENUM化
- keyboard_shortcut機能追加

## Phase 1: データベース基盤整備

### Task 1-1: route_connections テーブル拡張マイグレーション
**優先度: 高**  
**見積もり: 2時間**

- [ ] マイグレーションファイル作成
  ```sql
  ALTER TABLE route_connections ADD COLUMN source_position TINYINT NULL;
  ALTER TABLE route_connections ADD COLUMN target_position TINYINT NULL;
  ALTER TABLE route_connections ADD COLUMN edge_type ENUM('normal','branch','portal','exit','enter') NULL;
  ALTER TABLE route_connections ADD COLUMN is_enabled BOOLEAN DEFAULT 1;
  ALTER TABLE route_connections ADD COLUMN action_label ENUM('turn_right','turn_left','move_north','move_south','move_west','move_east','enter_dungeon','exit_dungeon') NULL;
  ALTER TABLE route_connections ADD COLUMN keyboard_shortcut ENUM('up','down','left','right') NULL;
  ```
- [ ] インデックス追加
  ```sql
  CREATE INDEX idx_route_connections_source_position ON route_connections (source_location_id, source_position);
  CREATE INDEX idx_route_connections_keyboard ON route_connections (source_location_id, keyboard_shortcut);
  ```
- [ ] UNIQUE制約追加
  ```sql
  ALTER TABLE route_connections ADD CONSTRAINT unique_keyboard_shortcut_per_source 
  UNIQUE (source_location_id, keyboard_shortcut) WHERE keyboard_shortcut IS NOT NULL;
  ```

### Task 1-2: 既存データ移行スクリプト
**優先度: 高**  
**見積もり: 3時間**

- [ ] 既存direction → action_labelマッピング作成
- [ ] 既存position → source_positionデータ移行
- [ ] Bidirectional → 双方向レコード分離
- [ ] データ整合性検証スクリプト
- [ ] ロールバック用バックアップ作成

## Phase 2: モデル層更新

### Task 2-1: RouteConnection モデル更新
**優先度: 高**  
**見積もり: 2時間**

- [ ] fillable配列更新
  ```php
  protected $fillable = [
      'source_location_id',
      'target_location_id', 
      'source_position',
      'target_position',
      'edge_type',
      'is_enabled',
      'action_label',
      'keyboard_shortcut'
  ];
  ```
- [ ] casts配列追加
  ```php
  protected $casts = [
      'source_position' => 'integer',
      'target_position' => 'integer',
      'is_enabled' => 'boolean',
  ];
  ```
- [ ] 新しいスコープメソッド追加
  ```php
  public function scopeBySourcePosition($query, $position)
  public function scopeByKeyboardShortcut($query, $shortcut)
  public function scopeEnabled($query)
  ```

### Task 2-2: ActionLabel ヘルパー実装
**優先度: 中**  
**見積もり: 1時間**

- [ ] ActionLabelヘルパークラス作成
- [ ] getActionLabelText() メソッド実装
- [ ] ENUM値 ↔ 日本語テキスト対応
- [ ] ターゲット名動的挿入機能

## Phase 3: バリデーション実装

### Task 3-1: RouteConnection バリデーション強化
**優先度: 高**  
**見積もり: 3時間**

- [ ] FormRequest作成 (CreateRouteConnectionRequest, UpdateRouteConnectionRequest)
- [ ] 基本バリデーションルール
  ```php
  'source_position' => 'nullable|integer|between:0,100',
  'target_position' => 'nullable|integer|between:0,100',
  'action_label' => 'nullable|in:turn_right,turn_left,move_north,move_south,move_west,move_east,enter_dungeon,exit_dungeon',
  'keyboard_shortcut' => 'nullable|in:up,down,left,right'
  ```
- [ ] クロスバリデーション実装（source/target categoryとposition関係）
- [ ] keyboard_shortcut重複チェック
- [ ] カスタムバリデーションルール作成

### Task 3-2: 管理画面バリデーション更新
**優先度: 中**  
**見積もり: 2時間**

- [ ] AdminRouteConnectionController バリデーション更新
- [ ] connection_type削除（bidirectional廃止）
- [ ] 新しいフィールドのバリデーション追加
- [ ] エラーメッセージ日本語化

## Phase 4: 管理画面UI更新

### Task 4-1: 管理画面フォーム更新
**優先度: 中**  
**見積もり: 4時間**

- [ ] create.blade.php更新
  - source_position/target_position入力フィールド追加
  - action_labelセレクトボックス追加（日本語選択肢）
  - keyboard_shortcutセレクトボックス追加
- [ ] edit.blade.php更新
- [ ] _route_connections.blade.php更新
- [ ] connection_type削除、新フィールド表示追加

### Task 4-2: 管理画面表示更新
**優先度: 低**  
**見積もり: 2時間**

- [ ] index.blade.php テーブル列追加
- [ ] show.blade.php 詳細表示更新
- [ ] フィルタリング機能更新（新フィールド対応）

### Task 4-3: 管理便利機能実装
**優先度: 低**  
**見積もり: 3時間**

- [ ] 逆向き自動作成ボタン実装
- [ ] keyboard_shortcut重複チェック機能
- [ ] action_label自動提案機能
- [ ] データ検証ページ実装

## Phase 5: ゲームロジック実装

### Task 5-1: LocationService 更新
**優先度: 高**  
**見積もり: 4時間**

- [ ] getAvailableConnections() メソッド更新
  - 新しい判定ロジック実装（0は<=、100は>=、中間値は=）
  - town対応（source_position=NULL時の処理）
- [ ] shouldShowConnection() ヘルパーメソッド作成
- [ ] 既存のBidirectional処理削除

### Task 5-2: GameController 更新
**優先度: 高**  
**見積もり: 3時間**

- [ ] moveToLocation() メソッド更新
- [ ] target_position設定ロジック実装
- [ ] 新しいconnection判定ロジック適用
- [ ] エラーハンドリング強化

## Phase 6: フロントエンド実装

### Task 6-1: 移動ボタン表示更新
**優先度: 高**  
**見積もり: 3時間**

- [ ] road-sidebar.blade.php更新
  - action_label対応ボタンテキスト表示
  - keyboard_shortcutヒント表示
- [ ] town-sidebar.blade.php更新
- [ ] next_location_button.blade.php更新

### Task 6-2: 矢印キー操作実装
**優先度: 中**  
**見積もり: 4時間**

- [ ] JavaScript キーイベントリスナー実装
  ```javascript
  document.addEventListener('keydown', function(event) {
      // キーマッピング・重複防止・disabled状態チェック
  });
  ```
- [ ] データ属性設定（data-keyboard-shortcut）
- [ ] 視覚的フィードバック実装（キー表示、ハイライト）
- [ ] フォーカス状態での無効化処理

### Task 6-3: UX改善実装
**優先度: 低**  
**見積もり: 2時間**

- [ ] キーボードヘルプ表示
- [ ] キー操作音効（オプション）
- [ ] 移動方向プレビュー表示
- [ ] アニメーション効果

## Phase 7: テスト実装

### Task 7-1: モデル・サービステスト
**優先度: 高**  
**見積もり: 4時間**

- [ ] RouteConnectionモデルテスト
  - 新フィールドの保存・取得テスト
  - スコープメソッドテスト
- [ ] LocationServiceテスト
  - shouldShowConnection() テスト
  - getAvailableConnections() テスト（各判定条件）
- [ ] バリデーションテスト

### Task 7-2: 機能テスト
**優先度: 中**  
**見積もり: 3時間**

- [ ] 管理画面機能テスト
  - CRUD操作テスト
  - バリデーションエラーテスト
- [ ] ゲーム移動機能テスト
  - 各position条件での移動テスト
  - keyboard_shortcut機能テスト

### Task 7-3: ブラウザテスト
**優先度: 低**  
**見積もり: 2時間**

- [ ] JavaScript機能テスト（各ブラウザ）
- [ ] キーボード操作テスト
- [ ] レスポンシブ表示テスト

## Phase 8: 旧システム廃止

### Task 8-1: 旧フィールド削除準備
**優先度: 低**  
**見積もり: 2時間**

- [ ] 旧フィールド使用箇所の完全置換確認
- [ ] 本番環境での動作確認
- [ ] バックアップ作成

### Task 8-2: 旧フィールド削除実行
**優先度: 低**  
**見積もり: 1時間**

- [ ] position, connection_type, direction列削除マイグレーション
- [ ] 関連コードの最終クリーンアップ

## 実装順序推奨

1. **Phase 1 → Phase 2 → Phase 3**: データ基盤とバリデーション
2. **Phase 5**: ゲームロジック（コア機能）
3. **Phase 6**: フロントエンド（UI/UX）
4. **Phase 4**: 管理画面（運用向上）
5. **Phase 7**: テスト（品質保証）
6. **Phase 8**: 旧システム廃止（最終クリーンアップ）

## リスク・注意事項

- **データ移行**: 本番環境での慎重な実行が必要
- **keyboard_shortcut重複**: 同一location内での重複防止必須
- **後方互換性**: 段階的な移行でゲーム継続性を確保
- **パフォーマンス**: 新しい判定ロジックでのクエリ性能確認
- **テスト**: 各position条件での動作確認を徹底

## 完了基準

- [ ] 全ての新機能が期待通り動作する
- [ ] 既存のゲームフローが影響を受けない  
- [ ] 管理画面で新しいconnectionを適切に管理できる
- [ ] 矢印キー操作が正常に動作する
- [ ] パフォーマンスが劣化しない
- [ ] テストカバレッジが適切に確保されている

---

**総見積もり時間: 約47時間**  
**推奨実装期間: 2-3週間**（1日4-6時間作業想定）