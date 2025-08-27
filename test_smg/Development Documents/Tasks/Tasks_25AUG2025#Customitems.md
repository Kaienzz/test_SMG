# カスタムアイテム管理画面実装タスク

**作成日**: 2025年8月25日  
**プロジェクト**: test_SMG - Admin管理画面機能拡張  
**目標**: 標準アイテム管理とは独立したカスタムアイテム専用管理画面の構築  

---

## 🎯 プロジェクト概要

### 現状分析結果

#### 既存システム構成
- **標準アイテム管理**: `AdminItemController` - 設定ファイルベースの標準アイテム管理
- **カスタムアイテムデータ**: `CustomItem`モデル - データベース保存されているプレイヤー生成アイテム（マスターデータ）
- **インベントリ管理**: `Inventory`モデル - プレイヤー個別のアイテム所有・耐久度管理
- **権限システム**: `items.*` 権限 - 現在は標準アイテム用に設定済み
- **ナビゲーション**: ゲームデータセクション内にアイテム管理メニュー存在

#### 実装必要理由
1. **データ構造の違い**: 標準アイテム（設定ファイル）vs カスタムアイテム（データベースマスター + インベントリ個別管理）
2. **管理対象の違い**: 静的マスターデータ vs 動的プレイヤー生成マスターデータ + 個別インスタンス
3. **操作権限の違い**: 設定変更権限 vs プレイヤーデータ管理権限
4. **UI要件の違い**: 設定編集UI vs マスターデータ管理・監査UI + インベントリ連携表示

---

## 📋 実装フェーズ計画

### Phase 1: 設計・権限基盤構築
**期間**: 1-2日  
**重要度**: 🔴 最重要

#### Phase 1.1: 権限設計・データベース更新
- [ ] **1.1.1** カスタムアイテム専用権限定義
  - `custom_items.view` - カスタムアイテム表示権限 (Level 10)
  - `custom_items.edit` - カスタムアイテム編集権限 (Level 25) 
  - `custom_items.delete` - カスタムアイテム削除権限 (Level 40, 危険)
  - `custom_items.audit` - カスタムアイテム監査権限 (Level 20)
  - `custom_items.inventory` - インベントリ連携確認権限 (Level 15)

- [ ] **1.1.2** AdminSystemSeederの更新
  - `database/seeders/AdminSystemSeeder.php` に新権限追加
  - 既存ロールへの権限追加（Super Admin, Manager, Viewer）

#### Phase 1.2: ルーティング設計
- [ ] **1.2.1** RESTfulルート設計
  ```php
  // routes/admin.php への追加
  Route::middleware(['admin.permission:custom_items.view'])->group(function () {
      Route::get('/custom-items', [AdminCustomItemController::class, 'index'])->name('custom-items.index');
      Route::get('/custom-items/{customItem}', [AdminCustomItemController::class, 'show'])->name('custom-items.show');
      Route::get('/custom-items/{customItem}/inventory', [AdminCustomItemController::class, 'inventory'])->name('custom-items.inventory');
      
      Route::middleware(['admin.permission:custom_items.edit'])->group(function () {
          Route::get('/custom-items/{customItem}/edit', [AdminCustomItemController::class, 'edit'])->name('custom-items.edit');
          Route::put('/custom-items/{customItem}', [AdminCustomItemController::class, 'update'])->name('custom-items.update');
      });
      
      Route::middleware(['admin.permission:custom_items.delete'])->group(function () {
          Route::delete('/custom-items/{customItem}', [AdminCustomItemController::class, 'destroy'])->name('custom-items.destroy');
      });
  });
  ```

#### Phase 1.3: ナビゲーション拡張
- [ ] **1.3.1** サイドバーメニュー追加
  - `resources/views/admin/layouts/app.blade.php` のゲームデータセクション拡張
  - アイテム管理にサブメニュー追加:
    - 標準アイテム管理（既存）
    - カスタムアイテム管理（新規）

---

### Phase 2: バックエンド実装
**期間**: 2-3日  
**重要度**: 🔴 最重要

#### Phase 2.1: コントローラー実装
- [ ] **2.1.1** AdminCustomItemController作成
  - `app/Http/Controllers/Admin/AdminCustomItemController.php`
  - AdminController基底クラスを継承
  - 必須メソッド実装:
    - `index()` - 一覧表示（フィルタ・ソート・ページネーション）
    - `show()` - 詳細表示（ステータス詳細・作成履歴）
    - `inventory()` - インベントリ連携表示（所有者・個別状況）
    - `edit()` - 編集画面表示
    - `update()` - データ更新
    - `destroy()` - 削除（インベントリ連動削除）

- [ ] **2.1.2** 必須権限チェック実装
  ```php
  public function index(Request $request) {
      $this->initializeForRequest();
      $this->checkPermission('custom_items.view');
      $this->trackPageAccess('custom_items.index');
      // 実装内容
  }
  ```

#### Phase 2.2: サービスクラス実装
- [ ] **2.2.1** AdminCustomItemService作成
  - `app/Services/Admin/AdminCustomItemService.php`
  - ビジネスロジック分離:
    - フィルタリング・ソート・検索機能
    - 統計データ生成
    - バッチ操作機能
    - データ整合性チェック
    - インベントリ連携機能（所有者検索・個別状況確認）
    - マスター・インベントリ間の整合性検証

#### Phase 2.4: カスタムアイテム・インベントリ統合管理
- [ ] **2.4.1** マスターデータとインベントリ連携機能
  - CustomItemマスターからインベントリ内アイテム検索
  - 所有者プレイヤー一覧表示
  - 個別耐久度状況確認

- [ ] **2.4.2** データ整合性チェック機能  
  - 孤立CustomItemレコード検出
  - インベントリ内カスタムアイテムの整合性確認
  - 不整合データ自動修復

- [ ] **2.4.3** インベントリ連動削除機能
  - CustomItem削除時のインベントリslot_data更新
  - 安全な削除プロセス（依存関係チェック）
  - 削除前確認（所有者への影響説明）

#### Phase 2.3: バリデーション実装
- [ ] **2.3.1** Request Validation作成
  - `app/Http/Requests/Admin/UpdateCustomItemRequest.php`
  - 編集可能フィールドの制限（マスターデータのみ）
  - データ整合性ルール
  - 個別耐久度への影響チェック（max_durability変更時）

---

### Phase 3: フロントエンド実装
**期間**: 2-3日  
**重要度**: 🟡 高

#### Phase 3.1: ビューテンプレート作成
- [ ] **3.1.1** 一覧画面 (`resources/views/admin/custom-items/index.blade.php`)
  - 既存アイテム管理画面のデザインパターン踏襲
  - フィルタ機能:
    - 作成者検索
    - ベースアイテム種別
    - ステータス範囲
    - 作成日範囲
    - 名匠品フラグ
    - 所有者状況（所有者あり/なし）
  - ソート機能（作成日、価値、最大耐久度等）
  - ページネーション（50件/ページ）
  - インベントリ連携表示（所有者数、使用状況）

- [ ] **3.1.2** 詳細表示画面 (`resources/views/admin/custom-items/show.blade.php`)
  - カスタムアイテム詳細情報:
    - ベースアイテム情報
    - カスタムステータス詳細
    - 素材効果詳細
    - 作成者情報
    - マスター耐久度情報（base_durability, max_durability）
    - 作成履歴・ログ
  - インベントリ連携情報:
    - 現在の所有者一覧
    - 各所有者の個別耐久度状況
    - インベントリスロット位置

- [ ] **3.1.3** 編集画面 (`resources/views/admin/custom-items/edit.blade.php`)
  - 編集可能フィールド（マスターデータのみ）:
    - カスタムステータス調整
    - 最大耐久度調整（個別耐久度への影響警告表示）
    - 名匠品フラグ
  - 編集制限事項明示
  - 変更履歴記録
  - インベントリへの影響説明

- [ ] **3.1.4** インベントリ統合表示画面 (`resources/views/admin/custom-items/inventory.blade.php`)
  - カスタムアイテム詳細にインベントリ情報追加:
    - 所有者プレイヤー情報
    - インベントリスロット位置
    - 個別耐久度状況
    - 最終使用・アクセス日時

#### Phase 3.2: UI/UX実装
- [ ] **3.2.1** デザインシステム適用
  - 既存管理画面の統一CSSクラス使用
  - レスポンシブデザイン対応
  - ダークテーマ対応

- [ ] **3.2.2** JavaScript機能実装
  - Alpine.jsを使用したインタラクション
  - Ajax検索・フィルタ
  - モーダル表示（削除確認等）

---

### Phase 4: 高度機能実装
**期間**: 1-2日  
**重要度**: 🟢 中

#### Phase 4.1: 統計・分析機能
- [ ] **4.1.1** ダッシュボード統計
  - カスタムアイテム総数
  - 作成者別統計
  - ベースアイテム別分布
  - 名匠品比率
  - 平均ステータス値
  - インベントリ所有状況統計（所有者数、未所有アイテム数）

- [ ] **4.1.2** 高度フィルタ・検索
  - 複合検索条件
  - ステータス範囲検索
  - 作成者一括管理
  - エクスポート機能

#### Phase 4.2: バッチ操作機能
- [ ] **4.2.1** 一括操作実装
  - 一括削除（危険操作・インベントリ連動削除）
  - 一括ステータス調整（マスターデータのみ）
  - 一括最大耐久度調整（インベントリへの影響確認）
  - 孤立データクリーンアップ（マスター・インベントリ不整合解決）

---

### Phase 5: テスト・セキュリティ・デバッグ
**期間**: 1-2日  
**重要度**: 🔴 最重要

#### Phase 5.1: セキュリティテスト
- [ ] **5.1.1** 権限チェックテスト
  - 各レベルでのアクセス制御確認
  - 不正アクセス試行テスト
  - CSRF対策確認

- [ ] **5.1.2** データ整合性テスト
  - 不正データ入力テスト
  - SQLインジェクション対策確認
  - 外部キー整合性確認
  - マスター・インベントリ間の整合性確認
  - 個別耐久度とマスター最大耐久度の妥当性チェック

#### Phase 5.2: 監査ログテスト
- [ ] **5.2.1** 操作ログ記録確認
  - 全CRUD操作のログ記録
  - 権限違反ログ記録
  - 重要操作の詳細ログ

#### Phase 5.3: パフォーマンステスト
- [ ] **5.3.1** 大量データテスト
  - 1000件以上のカスタムアイテムでの動作確認
  - ページネーション性能確認
  - 検索・ソート性能確認

---

## 🔧 技術仕様詳細

### データベース設計（既存・更新済み）
```sql
-- custom_items テーブル（マスターデータ用）
CREATE TABLE custom_items (
    id BIGINT PRIMARY KEY,
    base_item_id BIGINT FOREIGN KEY REFERENCES items(id),
    creator_id BIGINT FOREIGN KEY REFERENCES players(id),
    custom_stats JSON,
    base_stats JSON,
    material_bonuses JSON,
    base_durability INTEGER,
    max_durability INTEGER,
    is_masterwork BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Note: durabilityフィールドを削除（個別耐久度はinventoriesテーブルのslot_dataで管理）
```

### 新規権限定義
```php
// database/seeders/AdminSystemSeeder.php への追加
[
    'name' => 'custom_items.view',
    'category' => 'custom_items', 
    'action' => 'view',
    'display_name' => 'カスタムアイテム表示',
    'required_level' => 10
],
[
    'name' => 'custom_items.edit',
    'category' => 'custom_items',
    'action' => 'edit', 
    'display_name' => 'カスタムアイテム編集',
    'required_level' => 25
],
[
    'name' => 'custom_items.delete',
    'category' => 'custom_items',
    'action' => 'delete',
    'display_name' => 'カスタムアイテム削除', 
    'required_level' => 40,
    'is_dangerous' => true
],
[
    'name' => 'custom_items.audit',
    'category' => 'custom_items',
    'action' => 'audit',
    'display_name' => 'カスタムアイテム監査',
    'required_level' => 20
],
[
    'name' => 'custom_items.inventory',
    'category' => 'custom_items',
    'action' => 'inventory',
    'display_name' => 'カスタムアイテムインベントリ確認',
    'required_level' => 15
]
```

### コントローラー実装パターン
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\CustomItem;
use App\Services\Admin\AdminCustomItemService;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;

class AdminCustomItemController extends AdminController
{
    private AdminCustomItemService $customItemService;

    public function __construct(
        AdminAuditService $auditService,
        AdminCustomItemService $customItemService
    ) {
        parent::__construct($auditService);
        $this->customItemService = $customItemService;
    }

    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('custom_items.view');
        $this->trackPageAccess('custom_items.index');
        
        $filters = $request->only([
            'search', 'creator_id', 'base_item_id', 
            'is_masterwork', 'min_max_durability', 'max_max_durability',
            'created_from', 'created_to', 'has_owners'
        ]);
        
        $customItems = $this->customItemService->getFilteredCustomItems($filters);
        $stats = $this->customItemService->getCustomItemStats();
        $inventoryStats = $this->customItemService->getInventoryStats();
        
        $this->auditLog('custom_items.index.accessed', [
            'filters' => $filters,
            'result_count' => $customItems->total()
        ], 'low');
        
        return view('admin.custom-items.index', compact('customItems', 'stats', 'inventoryStats', 'filters'));
    }
}
```

---

## 🚨 重要な実装注意事項

### セキュリティ要件
1. **必須**: 全メソッドでの権限チェック実行
2. **必須**: センシティブ操作の監査ログ記録
3. **必須**: プレイヤーデータ変更時の詳細ログ
4. **必須**: マスターデータ変更時のインベントリ影響範囲確認
5. **推奨**: カスタムアイテム削除時のインベントリ連動削除
6. **推奨**: 最大耐久度変更時の個別耐久度妥当性チェック

### データ整合性要件
1. **必須**: 外部キー制約の維持
2. **必須**: JSONデータの構造バリデーション
3. **必須**: CustomItemマスターとインベントリslot_dataの整合性確保
4. **推奨**: 統計データの定期的な整合性チェック
5. **推奨**: 個別耐久度とマスター最大耐久度の妥当性チェック

### ユーザビリティ要件  
1. **必須**: 標準アイテム管理との明確な区別
2. **必須**: カスタムアイテム特有の情報の分かりやすい表示
3. **必須**: マスターデータとインベントリ個別データの区別明示
4. **推奨**: 作成者への影響説明（編集・削除時）
5. **推奨**: インベントリ所有者への影響説明（最大耐久度変更時）
6. **推奨**: 整合性問題の分かりやすい表示・解決手順提示

---

## 📊 成功指標

### 機能指標
- [ ] 全CRUD操作の正常動作
- [ ] 権限レベル別のアクセス制御
- [ ] 1000件以上のデータでの安定動作
- [ ] 検索・フィルタ機能の高速応答（<2秒）
- [ ] マスター・インベントリ間の整合性維持
- [ ] インベントリ連携機能の正常動作

### セキュリティ指標
- [ ] 権限外アクセスの完全ブロック
- [ ] 全操作の監査ログ記録
- [ ] データ改竄防止機能
- [ ] マスターデータ変更時の影響範囲確認

### 運用指標
- [ ] 管理者による日常的な利用開始
- [ ] 既存標準アイテム管理との併用運用
- [ ] 問題発生時の迅速な原因特定
- [ ] 整合性問題の早期発見・解決

---

## 🔄 今後の拡張予定

### Phase 6以降の検討事項
1. **カスタムアイテム作成機能**: 管理者による直接作成
2. **テンプレート機能**: よく使用される構成の保存
3. **インポート/エクスポート**: CSVでの一括管理
4. **レポート機能**: 詳細な分析レポート出力
5. **API連携**: 外部ツールからのデータ管理
6. **自動整合性チェック**: 定期的なマスター・インベントリ整合性確認
7. **個別耐久度一括調整**: インベントリ内の個別耐久度を一括操作
8. **アイテム使用履歴追跡**: インベントリでの使用・移動履歴管理

---

**作成者**: GitHub Copilot  
**承認者**: [要承認]  
**開始予定日**: 2025年8月25日  
**完了予定日**: 2025年8月31日  

**注意**: このタスクは管理画面作成ガイドライン (`AdminControlPanel_Ref.md`) に完全準拠して実装してください。

**重要**: カスタムアイテムはマスターデータとして管理され、個別の耐久度・所有状況はインベントリシステムで管理されます。管理画面ではこの二重構造を適切に表示・管理する必要があります。

**データ構造変更履歴**: 
- 2025年8月25日: `custom_items.durability`フィールド削除（マスターデータ化）
- マイグレーション: `2025_08_25_074151_remove_durability_from_custom_items_table.php`
