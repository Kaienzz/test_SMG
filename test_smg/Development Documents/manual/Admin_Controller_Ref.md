# Admin_Controller_Ref.md - 管理画面開発リファレンスマニュアル

## 📋 概要

このマニュアルは、test_smgプロジェクトの管理画面機能を追加・修正する際の統一的な開発ガイドラインです。権限管理、ルーティング、コントローラー実装のベストプラクティスを定義し、開発の一貫性とセキュリティを確保します。

**作成日**: 2025年8月19日  
**最終更新**: 2025年8月19日  
**対象**: Laravel 11ベース管理画面システム

---

## 🏗 管理画面アーキテクチャ概要

### 基本構成要素

```
┌─────────────────────────────────────────┐
│              Frontend                   │
│          (Blade Templates)              │
├─────────────────────────────────────────┤
│              Routes                     │
│         (routes/admin.php)              │
├─────────────────────────────────────────┤
│             Middleware                  │
│    [auth] → [admin] → [admin.permission]│
├─────────────────────────────────────────┤
│            Controllers                  │
│         (AdminController基底)           │
├─────────────────────────────────────────┤
│             Services                    │
│  AdminPermissionService, AuditService   │
├─────────────────────────────────────────┤
│             Database                    │
│     Users, AdminRoles, Permissions     │
└─────────────────────────────────────────┘
```

### 核心理念

1. **セキュリティファースト**: 全機能に適切な権限チェック
2. **監査可能性**: 全管理操作のログ記録
3. **拡張性**: 新機能追加時の統一パターン
4. **保守性**: 明確なコード構造と責任分離

---

## 🛡 権限管理システム

### 🎯 **統一権限検証ベストプラクティス（必須遵守）**

#### **必須パターン (MANDATORY PATTERN)**
すべての管理画面コントローラーメソッドで以下を **必ず実行**：

```php
public function anyMethod(Request $request) 
{
    // 🔴【必須】リクエスト初期化
    $this->initializeForRequest();
    
    // 🔴【必須】権限チェック  
    $this->checkPermission('resource.action');
    
    // 🟡【推奨】ページアクセス記録
    $this->trackPageAccess('resource.method');
    
    // ✅ メインロジック
    try {
        // ビジネスロジック実行
        $result = $this->service->performAction($data);
        
        // 🟡【推奨】監査ログ記録
        $this->auditLog('resource.action.performed', [
            'details' => $result
        ], 'medium');
        
        return view('admin.resource.template', compact('result'));
        
    } catch (\Exception $e) {
        // 🔴【必須】エラーログ記録
        $this->auditLog('resource.action.failed', [
            'error' => $e->getMessage()
        ], 'high');
        
        return back()->withError('操作に失敗しました: ' . $e->getMessage());
    }
}
```

#### **権限チェック二重防御 (DUAL-LAYER PROTECTION)**

```
🛡 Route Level (Middleware)
Route::middleware(['admin.permission:resource.view'])
    ↓ 第1層防御：アクセス前チェック
🛡 Controller Level (Method)  
$this->checkPermission('resource.view')
    ↓ 第2層防御：実行前チェック
✅ Permission Granted
```

#### **権限命名規則 (STRICT NAMING)**

```
✅ 正しい命名：
users.view, users.create, users.edit, users.delete
items.view, items.create, items.edit, items.delete  
monsters.view, monsters.edit
locations.view, locations.edit, locations.export

❌ 間違った命名：
user.view (単数形)
items.update (非標準動詞)
monster.spawn (不明確なアクション)
```

#### **監査ログ記録基準 (AUDIT LOG STANDARDS)**

```php
// Severity Level Guidelines
'low'      - 表示・検索操作（記録任意）
'medium'   - 作成・更新操作（記録必須）
'high'     - 削除・重要変更（記録必須）
'critical' - システム設定・権限変更（記録必須）

// 記録必須項目
$this->auditLog('action.name', [
    'resource_id' => $id,
    'old_values' => $beforeData,    // 変更前データ
    'new_values' => $afterData,     // 変更後データ  
    'user_input' => $request->all() // ユーザー入力
], $severity);
```

### 権限レベル階層

```
Super Admin (admin_level: 'super')
    ↓ 全権限所有（ミドルウェアバイパス）
Role-based Admin (admin_role_id設定)
    ↓ ロール権限 + 個別権限
Individual Admin (admin_permissions設定)
    ↓ 個別権限のみ
Basic Admin (is_admin: true)
    ↓ 基本管理権限のみ
```

### 権限命名規則

```
{リソース}.{アクション}

例:
- users.view     : ユーザー一覧・詳細表示
- users.edit     : ユーザー編集
- users.create   : ユーザー作成
- users.delete   : ユーザー削除
- users.suspend  : ユーザー停止

- items.view     : アイテム管理表示
- items.edit     : アイテム編集
- items.create   : アイテム作成
- items.delete   : アイテム削除

- monsters.view  : モンスター管理表示
- monsters.edit  : モンスター編集

- locations.view : ロケーション管理表示
- locations.edit : ロケーション編集
```

### ワイルドカード権限

```php
// 全権限を付与
'admin_permissions' => ["*"]

// カテゴリ単位の権限付与
'admin_permissions' => ["users.*", "items.*"]

// 特定権限の組み合わせ
'admin_permissions' => ["users.view", "users.edit", "items.view"]
```

---

## 🛤 ルーティング設定パターン

### 基本構造

```php
// routes/admin.php

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // ダッシュボード（権限チェックなし）
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // 機能別権限グループ
    Route::middleware(['admin.permission:機能名.view'])->group(function () {
        // 表示系ルート
        Route::get('/機能名', [Admin機能名Controller::class, 'index'])->name('機能名.index');
        Route::get('/機能名/{id}', [Admin機能名Controller::class, 'show'])->name('機能名.show');
        
        // 編集系ルート（追加権限チェック）
        Route::middleware(['admin.permission:機能名.edit'])->group(function () {
            Route::put('/機能名/{id}', [Admin機能名Controller::class, 'update'])->name('機能名.update');
            Route::post('/機能名', [Admin機能名Controller::class, 'store'])->name('機能名.store');
        });
        
        // 削除系ルート（特別権限）
        Route::middleware(['admin.permission:機能名.delete'])->group(function () {
            Route::delete('/機能名/{id}', [Admin機能名Controller::class, 'destroy'])->name('機能名.destroy');
        });
    });
});
```

### ✅ 正しいルーティング例

```php
// モンスター管理の正しい構造
Route::middleware(['admin.permission:monsters.view'])->group(function () {
    // モンスター基本管理
    Route::get('/monsters', [AdminMonsterController::class, 'index'])->name('monsters.index');
    Route::get('/monsters/{monster}', [AdminMonsterController::class, 'show'])->name('monsters.show');
    
    // モンスタースポーン管理（同じ権限グループ内）
    Route::get('/monsters/spawn-lists', [AdminMonsterSpawnController::class, 'index'])->name('monsters.spawn-lists.index');
    Route::get('/monsters/spawn-lists/pathway/{pathwayId}', [AdminMonsterSpawnController::class, 'pathwaySpawns'])->name('monsters.spawn-lists.pathway');
    
    // 編集系（追加権限）
    Route::middleware(['admin.permission:monsters.edit'])->group(function () {
        Route::put('/monsters/{monster}', [AdminMonsterController::class, 'update'])->name('monsters.update');
        Route::post('/monsters/spawn-lists/pathway/{pathwayId}', [AdminMonsterSpawnController::class, 'saveSpawns'])->name('monsters.spawn-lists.save');
    });
});
```

### ❌ 避けるべきルーティング構造

```php
// 間違い: 関連機能が別の権限グループに分散
Route::middleware(['admin.permission:items.view'])->group(function () {
    Route::get('/items', [AdminItemController::class, 'index']);
    // 間違い: モンスター管理がアイテム権限グループ内
    Route::get('/monsters', [AdminMonsterController::class, 'index']); // ❌
});

// 間違い: 重複するルート定義
Route::middleware(['admin.permission:monsters.view'])->group(function () {
    Route::get('/monsters/spawn-lists', [AdminMonsterSpawnController::class, 'index']);
});
Route::middleware(['admin.permission:monsters.view'])->group(function () {
    Route::get('/monsters/spawn-lists', [AdminMonsterSpawnController::class, 'index']); // ❌ 重複
});
```

---

## 🎛 コントローラー実装パターン

### AdminController基底クラス継承

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminAuditService;
use App\Services\YourFeature\YourFeatureService; // 必要に応じて

class AdminYourFeatureController extends AdminController
{
    private YourFeatureService $yourFeatureService;

    public function __construct(
        AdminAuditService $auditService,
        YourFeatureService $yourFeatureService
    ) {
        parent::__construct($auditService);
        $this->yourFeatureService = $yourFeatureService;
    }

    /**
     * 一覧表示
     */
    public function index(Request $request)
    {
        // 1. リクエスト初期化（必須）
        $this->initializeForRequest();
        
        // 2. 権限チェック（必須）
        $this->checkPermission('your_feature.view');
        
        // 3. ページアクセス記録（推奨）
        $this->trackPageAccess('your_feature.index');

        // 4. フィルタリング処理
        $filters = $request->only(['search', 'category', 'sort_by', 'sort_direction']);
        
        try {
            // 5. データ取得
            $data = $this->yourFeatureService->getData($filters);
            
            // 6. 監査ログ記録
            $this->auditLog('your_feature.index.viewed', [
                'filters' => $filters,
                'result_count' => count($data)
            ]);
            
            // 7. ビュー返却
            return view('admin.your_feature.index', compact('data', 'filters'));
            
        } catch (\Exception $e) {
            // 8. エラーハンドリング
            return redirect()->back()
                ->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 更新処理
     */
    public function update(Request $request, $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('your_feature.edit');

        // バリデーション
        $validator = $this->validateData($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $oldData = $this->yourFeatureService->find($id);
            $newData = $this->yourFeatureService->update($id, $request->validated());
            
            // 詳細な監査ログ
            $this->auditLog('your_feature.updated', [
                'id' => $id,
                'old_values' => $oldData,
                'new_values' => $newData
            ], 'high');
            
            return redirect()->route('admin.your_feature.show', $id)
                ->with('success', '更新が完了しました。');
                
        } catch (\Exception $e) {
            $this->auditLog('your_feature.update.failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()
                ->withError('更新に失敗しました: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * データバリデーション
     */
    private function validateData(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            // 追加のバリデーションルール
        ]);
    }
}
```

### 必須実装項目チェックリスト

- [ ] `AdminController`を継承
- [ ] コンストラクタで`AdminAuditService`をインジェクション
- [ ] 各メソッドで`$this->initializeForRequest()`を実行
- [ ] 各メソッドで`$this->checkPermission()`を実行
- [ ] 重要な操作で`$this->auditLog()`を記録
- [ ] 適切なエラーハンドリングを実装
- [ ] バリデーションルールを定義

---

## 🔧 ミドルウェア設定

### ミドルウェア登録（bootstrap/app.php）

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'admin' => \App\Http\Middleware\IsAdmin::class,
        'admin.permission' => \App\Http\Middleware\AdminPermission::class,
    ]);
})
```

### ミドルウェアスタック順序

```
1. auth          - ユーザー認証確認
2. admin         - 管理者権限確認 (is_admin = true)
3. admin.permission - 特定権限確認
```

### 権限チェックの段階的実行

```
Route Level (Middleware)
    ↓
admin.permission:feature.view
    ↓ super adminはここでバイパス
Controller Level (Method)
    ↓  
$this->checkPermission('feature.view')
    ↓ AdminPermissionServiceで詳細チェック
Permission Granted ✅
```

---

## 🚨 よくある問題と解決方法

### 1. 403権限エラー

#### 症状
```
403 この機能にアクセスする権限がありません。
```

#### 原因と解決策

**原因A: ルーティングの権限グループ間違い**
```php
// 問題のコード
Route::middleware(['admin.permission:items.view'])->group(function () {
    Route::get('/monsters/spawn-lists', [AdminMonsterSpawnController::class, 'index']); // ❌
});

// 修正コード
Route::middleware(['admin.permission:monsters.view'])->group(function () {
    Route::get('/monsters/spawn-lists', [AdminMonsterSpawnController::class, 'index']); // ✅
});
```

**原因B: 権限データベース設定不備**
```sql
-- 権限の確認
SELECT * FROM admin_permissions WHERE name = 'monsters.view';

-- 権限の追加
INSERT INTO admin_permissions (name, category, action, display_name, required_level, is_active) 
VALUES ('monsters.view', 'monsters', 'view', 'モンスター管理表示', 1, 1);
```

**原因C: ユーザー権限設定問題**
```php
// ユーザー権限の確認
$user = User::find($userId);
echo $user->admin_level; // 'super' 期待
echo $user->admin_permissions; // '["*"]' 期待
```

### 2. ルート重複エラー

#### 症状
```
Route [admin.monsters.spawn-lists.index] is already defined.
```

#### 解決策
1. ルート定義の重複を確認
2. `php artisan route:list` でルート一覧確認
3. `php artisan route:clear` でキャッシュクリア

### 3. サービス注入エラー

#### 症状
```
Class 'App\Services\YourFeature\YourFeatureService' not found
```

#### 解決策
1. サービスクラスの存在確認
2. 名前空間の確認
3. `composer dump-autoload` 実行

---

## 📝 新機能追加時の開発手順

### Step 1: 権限設定

```sql
-- 1. admin_permissions テーブルに権限追加
INSERT INTO admin_permissions (name, category, action, display_name, required_level, is_active) VALUES
('new_feature.view', 'new_feature', 'view', '新機能表示', 1, 1),
('new_feature.edit', 'new_feature', 'edit', '新機能編集', 2, 1),
('new_feature.create', 'new_feature', 'create', '新機能作成', 2, 1),
('new_feature.delete', 'new_feature', 'delete', '新機能削除', 3, 1);
```

### Step 2: ルーティング設定

```php
// routes/admin.php に追加
Route::middleware(['admin.permission:new_feature.view'])->group(function () {
    Route::get('/new-feature', [AdminNewFeatureController::class, 'index'])->name('new_feature.index');
    Route::get('/new-feature/{id}', [AdminNewFeatureController::class, 'show'])->name('new_feature.show');
    Route::get('/new-feature/create', [AdminNewFeatureController::class, 'create'])->name('new_feature.create');
    Route::get('/new-feature/{id}/edit', [AdminNewFeatureController::class, 'edit'])->name('new_feature.edit');
    
    Route::middleware(['admin.permission:new_feature.edit'])->group(function () {
        Route::post('/new-feature', [AdminNewFeatureController::class, 'store'])->name('new_feature.store');
        Route::put('/new-feature/{id}', [AdminNewFeatureController::class, 'update'])->name('new_feature.update');
    });
    
    Route::middleware(['admin.permission:new_feature.delete'])->group(function () {
        Route::delete('/new-feature/{id}', [AdminNewFeatureController::class, 'destroy'])->name('new_feature.destroy');
    });
});
```

### Step 3: コントローラー作成

```bash
php artisan make:controller Admin/AdminNewFeatureController
```

### Step 4: サービス作成（必要に応じて）

```bash
php artisan make:class Services/NewFeature/NewFeatureService
```

### Step 5: ビューテンプレート作成

```
resources/views/admin/new_feature/
├── index.blade.php
├── show.blade.php
├── create.blade.php
└── edit.blade.php
```

### Step 6: ナビゲーション追加

```php
// resources/views/admin/layouts/app.blade.php
@if($canManageNewFeature)
<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.new_feature.index') }}">
        <i class="nav-icon fas fa-new-icon"></i>
        <p>新機能管理</p>
    </a>
</li>
@endif
```

### Step 7: 権限チェック追加

```php
// app/Http/Controllers/Admin/AdminController.php の initializeView() に追加
$canManageNewFeature = $this->hasPermission('new_feature.view');

View::share([
    // 既存の変数
    'canManageNewFeature' => $canManageNewFeature,
]);
```

---

## 🚀 **サブメニュー管理システム設計**

### 🎯 **サブメニュー権限設定ベストプラクティス**

複数のサブ機能を持つ管理画面メニューの統一的な権限管理方法を定義します。

#### **基本原則**

```
✅ 統一権限ベース（UNIFIED PERMISSION BASE）
- 親メニュー権限でサブメニュー群全体を制御
- 例: items.view → 標準アイテム、カスタムアイテムすべて表示可能

✅ 階層的権限構造（HIERARCHICAL PERMISSIONS）
- 親権限: category.view (表示権限)
- 子権限: category.edit (編集権限)
- 特殊権限: category.delete (削除権限)
```

### **実装パターン**

#### **1. ルーティング構造（統一パターン）**

```php
// routes/admin.php - サブメニュー統一設計

// 【例1】アイテム管理 - 標準アイテム + カスタムアイテム
Route::middleware(['admin.permission:items.view'])->group(function () {
    // 🏠 親メニュー
    Route::get('/items', [AdminItemController::class, 'index'])->name('items.index');
    
    // 📦 サブメニュー1: 標準アイテム管理
    Route::get('/items/standard', [AdminItemController::class, 'standardItems'])->name('items.standard');
    Route::get('/items/standard/{id}', [AdminItemController::class, 'showStandardItem'])->name('items.standard.show');
    Route::get('/items/standard/create', [AdminItemController::class, 'createStandardItem'])->name('items.standard.create');
    Route::get('/items/standard/{id}/edit', [AdminItemController::class, 'editStandardItem'])->name('items.standard.edit');
    
    // 🛠 サブメニュー2: カスタムアイテム管理  
    Route::get('/items/{item}', [AdminItemController::class, 'show'])->name('items.show');
    Route::get('/items/create', [AdminItemController::class, 'create'])->name('items.create');
    Route::get('/items/{item}/edit', [AdminItemController::class, 'edit'])->name('items.edit');
    
    // ✏️ 編集系（追加権限チェック）
    Route::middleware(['admin.permission:items.edit'])->group(function () {
        Route::post('/items', [AdminItemController::class, 'store'])->name('items.store');
        Route::post('/items/standard', [AdminItemController::class, 'storeStandardItem'])->name('items.standard.store');
        Route::put('/items/{item}', [AdminItemController::class, 'update'])->name('items.update');
        Route::put('/items/standard/{id}', [AdminItemController::class, 'updateStandardItem'])->name('items.standard.update');
    });
    
    // 🗑 削除系（特殊権限）
    Route::middleware(['admin.permission:items.delete'])->group(function () {
        Route::delete('/items/{item}', [AdminItemController::class, 'destroy'])->name('items.destroy');
        Route::delete('/items/standard/{id}', [AdminItemController::class, 'deleteStandardItem'])->name('items.standard.delete');
    });
});

// 【例2】モンスター管理 - 基本管理 + スポーン管理
Route::middleware(['admin.permission:monsters.view'])->group(function () {
    // 🏠 親メニュー
    Route::get('/monsters', [AdminMonsterController::class, 'index'])->name('monsters.index');
    
    // 👹 サブメニュー1: モンスター基本管理
    Route::get('/monsters/{monster}', [AdminMonsterController::class, 'show'])->name('monsters.show');
    Route::get('/monsters/{monster}/edit', [AdminMonsterController::class, 'edit'])->name('monsters.edit');
    
    // 🎯 サブメニュー2: スポーン管理
    Route::get('/monsters/spawn-lists', [AdminMonsterSpawnController::class, 'index'])->name('monsters.spawn-lists.index');
    Route::get('/monsters/spawn-lists/pathway/{pathwayId}', [AdminMonsterSpawnController::class, 'pathwaySpawns'])->name('monsters.spawn-lists.pathway');
    
    // ✏️ 編集系（共通権限）
    Route::middleware(['admin.permission:monsters.edit'])->group(function () {
        Route::put('/monsters/{monster}', [AdminMonsterController::class, 'update'])->name('monsters.update');
        Route::post('/monsters/spawn-lists/pathway/{pathwayId}', [AdminMonsterSpawnController::class, 'saveSpawns'])->name('monsters.spawn-lists.save');
    });
});

// 【例3】マップ管理 - 統合型複数サブメニュー
Route::middleware(['admin.permission:locations.view'])->group(function () {
    // 🏠 親メニュー
    Route::get('/locations', [AdminLocationController::class, 'index'])->name('locations.index');
    
    // 🛣 サブメニュー1: 道・ダンジョン統合管理
    Route::get('/locations/pathways', [AdminLocationController::class, 'pathways'])->name('locations.pathways');
    Route::get('/locations/pathways/{pathwayId}', [AdminLocationController::class, 'pathwayForm'])->name('locations.pathways.edit');
    
    // 🏘 サブメニュー2: 町管理
    Route::get('/locations/towns', [AdminLocationController::class, 'towns'])->name('locations.towns');
    Route::get('/locations/towns/{townId}', [AdminLocationController::class, 'townForm'])->name('locations.towns.edit');
    
    // 🔗 サブメニュー3: マップ接続管理
    Route::get('/locations/connections', [AdminLocationController::class, 'connections'])->name('locations.connections');
    
    // 📜 サブメニュー4: 後方互換（旧システム）
    Route::get('/locations/roads', [AdminLocationController::class, 'roads'])->name('locations.roads');
    Route::get('/locations/dungeons', [AdminLocationController::class, 'dungeons'])->name('locations.dungeons');
});
```

#### **2. ナビゲーションビュー統合（権限連動）**

```blade
{{-- resources/views/admin/layouts/app.blade.php --}}

@if((isset($canManageGameData) && $canManageGameData) || (isset($adminUser) && $adminUser->admin_level === 'super'))
<div class="admin-nav-section">
    <div class="admin-nav-title">ゲームデータ</div>
    
    {{-- 🏠 アイテム管理（親メニュー） --}}
    <a href="{{ route('admin.items.index') }}" class="admin-nav-item {{ request()->routeIs('admin.items.index') ? 'active' : '' }}">
        <svg class="admin-nav-icon">...</svg>
        アイテム管理
    </a>
    {{-- 📦 サブメニュー群 --}}
    <div class="admin-nav-submenu">
        <a href="{{ route('admin.items.standard') }}" class="admin-nav-subitem {{ request()->routeIs('admin.items.standard*') ? 'active' : '' }}">
            <svg class="admin-nav-icon">...</svg>
            標準アイテム管理
        </a>
        <a href="{{ route('admin.items.standard.create') }}" class="admin-nav-subitem {{ request()->routeIs('admin.items.standard.create') ? 'active' : '' }}">
            <svg class="admin-nav-icon">...</svg>
            標準アイテム追加
        </a>
    </div>
    
    {{-- 🏠 モンスター管理（親メニュー） --}}  
    <a href="{{ route('admin.monsters.index') }}" class="admin-nav-item {{ request()->routeIs('admin.monsters.index') ? 'active' : '' }}">
        <svg class="admin-nav-icon">...</svg>
        モンスター管理
    </a>
    {{-- 👹 サブメニュー群 --}}
    <div class="admin-nav-submenu">
        <a href="{{ route('admin.monsters.spawn-lists.index') }}" class="admin-nav-subitem {{ request()->routeIs('admin.monsters.spawn-lists.*') ? 'active' : '' }}">
            <svg class="admin-nav-icon">...</svg>
            モンスタースポーン管理
        </a>
    </div>
    
    {{-- 🏠 マップ管理（親メニュー） --}}
    <a href="{{ route('admin.locations.index') }}" class="admin-nav-item {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}">
        <svg class="admin-nav-icon">...</svg>
        マップ管理
    </a>
    {{-- 🗺 サブメニュー群（階層構造） --}}
    <div class="admin-nav-submenu">
        <a href="{{ route('admin.locations.pathways') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.pathways*') ? 'active' : '' }}">
            <svg class="admin-nav-icon">...</svg>
            道・ダンジョン管理
        </a>
        <a href="{{ route('admin.locations.towns') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.towns*') ? 'active' : '' }}">
            <svg class="admin-nav-icon">...</svg>
            町管理
        </a>
        <a href="{{ route('admin.locations.connections') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.connections*') ? 'active' : '' }}">
            <svg class="admin-nav-icon">...</svg>
            マップ接続管理
        </a>
        <hr class="border-top my-2 mx-3">
        <a href="{{ route('admin.locations.roads') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.roads*') ? 'active' : '' }}">
            <svg class="admin-nav-icon">...</svg>
            道管理（旧）
        </a>
        <a href="{{ route('admin.locations.dungeons') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.dungeons*') ? 'active' : '' }}">
            <svg class="admin-nav-icon">...</svg>
            ダンジョン管理（旧）
        </a>
    </div>
</div>
@endif
```

#### **3. コントローラー実装統一パターン**

```php
<?php

namespace App\Http\Controllers\Admin;

/**
 * サブメニュー統合管理コントローラーの実装例
 */
class AdminItemController extends AdminController
{
    /**
     * 🏠 親メニュー: アイテム管理トップ
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.view');
        $this->trackPageAccess('items.index');
        
        // 統合ダッシュボード表示
        // - 標準アイテム統計
        // - カスタムアイテム統計
        // - 最近の編集履歴
        
        return view('admin.items.index', compact('stats'));
    }
    
    /**
     * 📦 サブメニュー1: 標準アイテム一覧
     */
    public function standardItems(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.view');  // 🔴 同じ親権限を使用
        $this->trackPageAccess('items.standard.index');
        
        // サブメニュー専用ロジック
        $standardItems = $this->standardItemService->getStandardItems();
        
        return view('admin.items.standard', compact('standardItems'));
    }
    
    /**
     * 📦 サブメニュー1: 標準アイテム作成
     */
    public function createStandardItem()
    {
        $this->initializeForRequest();
        $this->checkPermission('items.create');  // 🟡 作成は別権限
        $this->trackPageAccess('items.standard.create');
        
        return view('admin.items.standard-create');
    }
    
    /**
     * 🛠 サブメニュー2: カスタムアイテム表示
     */
    public function show(Item $item)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.view');  // 🔴 同じ親権限を使用
        
        return view('admin.items.show', compact('item'));
    }
}
```

### **権限設計ガイドライン**

#### **必須権限パターン**

```sql
-- サブメニューを持つ管理機能の標準権限設計
INSERT INTO admin_permissions (name, category, action, display_name, required_level, is_active) VALUES

-- 🏠 親権限（サブメニュー群全体を制御）
('items.view', 'items', 'view', 'アイテム管理表示', 1, 1),
('monsters.view', 'monsters', 'view', 'モンスター管理表示', 1, 1),  
('locations.view', 'locations', 'view', 'マップ管理表示', 1, 1),
('shops.view', 'shops', 'view', 'ショップ管理表示', 1, 1),

-- ✏️ 編集権限（サブメニュー共通）
('items.edit', 'items', 'edit', 'アイテム管理編集', 2, 1),
('monsters.edit', 'monsters', 'edit', 'モンスター管理編集', 2, 1),
('locations.edit', 'locations', 'edit', 'マップ管理編集', 2, 1),

-- ➕ 作成権限（必要に応じて）
('items.create', 'items', 'create', 'アイテム管理作成', 2, 1),
('locations.create', 'locations', 'create', 'マップ管理作成', 2, 1),

-- 🗑 削除権限（特別権限）
('items.delete', 'items', 'delete', 'アイテム管理削除', 3, 1),
('locations.delete', 'locations', 'delete', 'マップ管理削除', 3, 1),

-- 📊 特殊権限（機能別）
('locations.export', 'locations', 'export', 'マップデータエクスポート', 2, 1),
('locations.import', 'locations', 'import', 'マップデータインポート', 3, 1);
```

### **開発チェックリスト**

#### **サブメニュー実装時の必須確認項目**

- [ ] **ルーティング統一性**
  - [ ] 親権限ミドルウェアでサブメニュー群全体をラップ
  - [ ] サブメニューごとに個別の名前空間を使用
  - [ ] 編集系は追加権限チェックを実装

- [ ] **コントローラー統一性**  
  - [ ] 全メソッドで`initializeForRequest()`実行
  - [ ] 全メソッドで適切な権限チェック実行
  - [ ] サブメニューでも`trackPageAccess()`実行

- [ ] **ナビゲーション連動性**
  - [ ] 親権限で親メニュー表示制御
  - [ ] サブメニュー項目の適切なアクティブ状態制御
  - [ ] ルート名パターンマッチングの統一

- [ ] **権限データベース整合性**
  - [ ] 必要な権限がadmin_permissionsテーブルに登録済み
  - [ ] Super adminで全サブメニューアクセス可能
  - [ ] 権限のないユーザーで403エラー確認

---

## 🔍 デバッグ・トラブルシューティング

### 権限問題のデバッグ手順

#### 1. ユーザー情報確認
```php
php artisan tinker

$user = User::where('email', 'user@example.com')->first();
echo "Admin Level: " . $user->admin_level;
echo "Admin Permissions: " . $user->admin_permissions;
echo "Admin Role ID: " . $user->admin_role_id;
echo "Is Admin: " . ($user->is_admin ? 'true' : 'false');
```

#### 2. 権限サービステスト
```php
$permissionService = app(App\Services\Admin\AdminPermissionService::class);
$hasPermission = $permissionService->hasPermission($user, 'target.permission');
echo "Has Permission: " . ($hasPermission ? 'true' : 'false');
```

#### 3. ルート確認
```bash
php artisan route:list --name=target.route
```

#### 4. ログ確認
```bash
tail -f storage/logs/laravel.log
```

### パフォーマンス監視

#### 権限チェックのキャッシュ確認
```php
// キャッシュクリア
$permissionService->clearPermissionCache($userId);

// キャッシュ状況確認
Cache::get("admin_permissions_{$userId}");
```

---

## ⚡ パフォーマンス最適化

### 権限キャッシュ戦略

```php
// 1時間キャッシュ（デフォルト）
Cache::remember("admin_permissions_{$userId}", now()->addHours(1), function() {
    // 権限取得処理
});

// 権限変更時のキャッシュクリア
$this->permissionService->clearPermissionCache($userId);
```

### バッチ権限チェック

```php
// 複数権限の一括チェック
$permissions = ['users.view', 'items.view', 'monsters.view'];
$userPermissions = $this->permissionService->getUserPermissions($user);

foreach ($permissions as $permission) {
    $results[$permission] = $this->permissionService->checkPermissionInList($permission, $userPermissions);
}
```

---

## 🛡 セキュリティベストプラクティス

### 1. 権限チェックの多層防御

```
Route Middleware (第1層)
    ↓
Controller Permission Check (第2層)
    ↓
Service Level Validation (第3層)
    ↓
Database Constraints (第4層)
```

### 2. 監査ログの必須記録

```php
// 必須監査ログ
$this->auditLog('action.performed', [
    'resource_id' => $id,
    'old_values' => $oldData,
    'new_values' => $newData,
    'user_ip' => $request->ip(),
    'user_agent' => $request->userAgent()
], 'high');
```

### 3. 危険操作の追加確認

```php
// 削除操作など危険な操作
Route::middleware(['admin.permission:resource.delete', 'confirm.dangerous'])->group(function () {
    Route::delete('/resource/{id}', [AdminResourceController::class, 'destroy']);
});
```

---

## 📊 監査・ログ記録

### ログレベル定義

```
low      - 一般的な表示・参照操作
medium   - データ更新操作
high     - 重要データ変更・権限変更
critical - 削除・システム設定変更
```

### 記録必須項目

```php
$this->auditLog($action, [
    'resource_type' => 'ModelName',
    'resource_id' => $id,
    'old_values' => $before,
    'new_values' => $after,
    'request_data' => $request->all(),
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent()
], $severity);
```

---

## ✅ リリース前チェックリスト

### 権限設定確認
- [ ] 必要な権限がadmin_permissionsテーブルに登録済み
- [ ] テストユーザーに適切な権限が付与済み
- [ ] Super adminでアクセス可能
- [ ] 権限のないユーザーで403エラー確認

### ルーティング確認
- [ ] 関連ルートが適切な権限グループに配置
- [ ] ルート名の命名規則遵守
- [ ] 重複ルート定義なし
- [ ] `php artisan route:list`で確認

### コントローラー確認
- [ ] AdminController継承
- [ ] initializeForRequest()実行
- [ ] checkPermission()実行
- [ ] 監査ログ記録
- [ ] 適切なエラーハンドリング

### ナビゲーション確認
- [ ] 管理画面メニューに追加
- [ ] 権限に応じた表示制御
- [ ] アクティブ状態の制御

### テスト確認
- [ ] 機能テスト実行
- [ ] 権限テスト実行
- [ ] エラーケーステスト
- [ ] パフォーマンステスト

---

## 🔧 開発環境設定

### 必要コマンド

```bash
# ルートキャッシュクリア
php artisan route:clear

# 設定キャッシュクリア
php artisan config:clear

# オートローダー更新
composer dump-autoload

# 権限キャッシュクリア（手動）
php artisan tinker
Cache::flush();
```

### デバッグ設定

```php
// .env
APP_DEBUG=true
LOG_LEVEL=debug

// config/logging.php - 管理画面専用ログチャンネル追加
'admin' => [
    'driver' => 'single',
    'path' => storage_path('logs/admin.log'),
    'level' => 'debug',
],
```

---

## 🚨 トラブルシューティング

### 404エラー → 403エラー → 権限問題診断

#### **問題: `/admin/items/standard` で404 Not Foundエラー**

**症状**:
```bash
# アクセス時
GET /admin/items/standard
→ 404 Not Found (実際は権限不足による403)
```

**根本原因**: `admin_permissions`テーブルに必要な権限が存在しない

#### **解決手順**:

**1. 権限存在確認**
```bash
php artisan tinker --execute="
DB::table('admin_permissions')->where('name', 'items.view')->first();
"
```

**2. 不足権限の追加**
```bash
php artisan tinker --execute="
\$itemsPermissions = [
    ['name' => 'items.view', 'category' => 'items', 'action' => 'view', 'display_name' => 'アイテム閲覧', 'required_level' => 1, 'is_dangerous' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'items.create', 'category' => 'items', 'action' => 'create', 'display_name' => 'アイテム作成', 'required_level' => 1, 'is_dangerous' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'items.edit', 'category' => 'items', 'action' => 'edit', 'display_name' => 'アイテム編集', 'required_level' => 1, 'is_dangerous' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'items.delete', 'category' => 'items', 'action' => 'delete', 'display_name' => 'アイテム削除', 'required_level' => 2, 'is_dangerous' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
];
foreach (\$itemsPermissions as \$permission) {
    DB::table('admin_permissions')->insert(\$permission);
}
"
```

**3. 管理者への権限割り当て**
```bash
php artisan tinker --execute="
\$adminUsers = DB::table('users')->where('is_admin', true)->get();
foreach (\$adminUsers as \$user) {
    \$currentPermissions = json_decode(\$user->admin_permissions ?: '[]', true);
    \$newPermissions = ['items.view', 'items.create', 'items.edit', 'items.delete'];
    foreach (\$newPermissions as \$perm) {
        if (!in_array(\$perm, \$currentPermissions)) {
            \$currentPermissions[] = \$perm;
        }
    }
    DB::table('users')->where('id', \$user->id)->update([
        'admin_permissions' => json_encode(\$currentPermissions),
        'updated_at' => now()
    ]);
}
"
```

**4. アクセステスト**
```bash
curl -I http://localhost:8000/admin/items/standard
# Expected: 302 Found (redirect to login) instead of 404
```

#### **予防策**: 新機能開発チェックリスト

✅ **権限設計段階**
- [ ] `admin_permissions`テーブルに必要権限を事前追加
- [ ] 権限階層（view < edit < delete）の設計
- [ ] 必要最小権限の原則に基づく`required_level`設定

✅ **ルーティング段階**  
- [ ] `routes/admin.php`でのミドルウェア設定確認
- [ ] 権限名の一貫性確保（例: `items.*`, `monsters.*`）

✅ **テスト段階**
- [ ] 未認証アクセステスト（302 redirect確認）
- [ ] 権限不足ユーザーでの403エラー確認  
- [ ] 正常権限ユーザーでの200 OK確認

---

## 📚 関連ドキュメント

- [Laravel 11 Authorization](https://laravel.com/docs/11.x/authorization)
- [Laravel 11 Middleware](https://laravel.com/docs/11.x/middleware)
- [セキュリティ設計書](../01_development_docs/10_security_design.md)
- [API設計書](../01_development_docs/03_api_design.md)
- [エラーハンドリング設計書](../01_development_docs/05_error_handling_design.md)

---

## 📝 変更履歴

| 日付 | 変更者 | 変更内容 |
|------|--------|----------|
| 2025-08-19 | Claude | 初版作成、403エラー対応のベストプラクティス統合 |
| 2025-08-19 | Claude | サブメニュー管理システム設計追加、統一権限検証パターン更新 |
| 2025-08-19 | Claude | トラブルシューティング章追加：404エラー→権限問題の診断・解決手順 |

---

**最終更新**: 2025年8月19日  
**次回レビュー予定**: 2025年9月19日