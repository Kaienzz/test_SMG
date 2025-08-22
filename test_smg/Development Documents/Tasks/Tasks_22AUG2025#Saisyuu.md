# Tasks_22AUG2025#Saisyuu.md - 採集システムデータベース化 & 管理画面実装計画

## 📋 概要

現在ハードコードされている採集アイテムデータをデータベース化し、管理画面から柔軟に管理できるシステムに変更します。

## 🔍 現状分析

### 現在の実装状況
1. **ハードコードされた採集データ**: `app/Models/GatheringTable.php`で配列として定義
2. **採集ロジック**: `app/Http/Controllers/GatheringController.php`で実装済み
3. **Routes設定**: `routes`テーブル（旧game_locations）に`road`、`dungeon`カテゴリが存在
4. **管理画面**: 既存の管理システムが稼働中（権限管理・ナビゲーション等）

### 主要課題
- [ ] 採集データがハードコード（配列）で管理されている
- [ ] RoutesとGatheringデータの紐付けがない
- [ ] 管理画面からの採集マッピング管理機能がない
- [ ] アイテムIDによる参照ではなく、アイテム名による管理

## 🏗 実装計画

### Phase 1: データベース設計・マイグレーション

#### Task 1.1: gathering_mapping_table マイグレーション作成
```php
// 2025_08_22_create_gathering_mapping_table.php
Schema::create('gathering_mapping_table', function (Blueprint $table) {
    $table->id();
    $table->string('route_id')->comment('対象のroute ID (routes.id)');
    $table->string('item_id')->comment('採集可能アイテムID (items.id)');
    $table->integer('required_skill_level')->default(1)->comment('必要採集スキルレベル');
    $table->decimal('success_rate', 5, 2)->comment('採集成功率 (0.00-100.00)');
    $table->integer('quantity_min')->default(1)->comment('最小取得数量');
    $table->integer('quantity_max')->default(1)->comment('最大取得数量');
    $table->boolean('is_active')->default(true)->comment('有効フラグ');
    $table->timestamps();
    
    // インデックス
    $table->index(['route_id', 'is_active']);
    $table->index(['item_id']);
    $table->unique(['route_id', 'item_id']); // 同一ルート・アイテムの重複防止
    
    // 外部キー制約
    $table->foreign('route_id')->references('id')->on('routes')->onDelete('cascade');
    $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
});
```

#### Task 1.2: routesテーブル拡張
```php
// routes テーブルに採集有効フラグを追加
Schema::table('routes', function (Blueprint $table) {
    $table->boolean('gathering_enabled')->default(false)->comment('採集可能フラグ');
});
```

### Phase 2: モデル・リレーション実装

#### Task 2.1: GatheringMapping モデル作成
```php
// app/Models/GatheringMapping.php
class GatheringMapping extends Model
{
    protected $table = 'gathering_mapping_table';
    
    protected $fillable = [
        'route_id', 'item_id', 'required_skill_level', 
        'success_rate', 'quantity_min', 'quantity_max', 'is_active'
    ];
    
    protected $casts = [
        'success_rate' => 'decimal:2',
        'required_skill_level' => 'integer',
        'quantity_min' => 'integer',
        'quantity_max' => 'integer',
        'is_active' => 'boolean',
    ];
    
    // リレーション
    public function route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
    
    public function item() {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
```

#### Task 2.2: Route モデル拡張
```php
// app/Models/Route.php にリレーション追加
public function gatheringMappings()
{
    return $this->hasMany(GatheringMapping::class, 'route_id', 'id')
                ->where('is_active', true);
}

public function getGatheringItemsAttribute()
{
    return $this->gatheringMappings()->with('item')->get();
}
```

### Phase 3: 既存ゲームロジック更新

#### Task 3.1: GatheringController リファクタリング
```php
// app/Http/Controllers/GatheringController.php 更新
public function gather(Request $request): JsonResponse
{
    // 旧: GatheringTable::getAvailableItems($roadId, $gatheringSkill->level);
    // 新: DB駆動の採集処理
    $gatheringItems = GatheringMapping::where('route_id', $player->location_id)
        ->where('required_skill_level', '<=', $gatheringSkill->level)
        ->where('is_active', true)
        ->with('item')
        ->get();
}
```

#### Task 3.2: 旧GatheringTableクラス廃止準備
- [ ] 段階的な移行計画策定
- [ ] データマイグレーションSeeder作成

### Phase 4: 管理画面実装

#### Task 4.1: AdminGatheringMappingController 作成
```php
// app/Http/Controllers/Admin/AdminGatheringMappingController.php
class AdminGatheringMappingController extends AdminController
{
    public function index() // 採集マッピング一覧
    public function create() // 新規作成
    public function store() // 保存
    public function show($id) // 詳細表示
    public function edit($id) // 編集
    public function update($id) // 更新
    public function destroy($id) // 削除
}
```

#### Task 4.2: 管理画面ビュー作成
```
resources/views/admin/gathering/
├── index.blade.php     # 一覧画面
├── create.blade.php    # 新規作成
├── edit.blade.php      # 編集
├── show.blade.php      # 詳細表示
└── partials/
    └── form.blade.php  # フォーム共通部品
```

#### Task 4.3: ルート追加
```php
// routes/admin.php に追加
Route::middleware(['admin.permission:gathering.view'])->group(function () {
    Route::resource('gathering-mappings', AdminGatheringMappingController::class);
});
```

#### Task 4.4: 権限設定追加
```php
// admin_permissions テーブルに権限追加
- gathering.view
- gathering.create  
- gathering.edit
- gathering.delete
```

#### Task 4.5: Roads/Dungeons管理画面への採集設定追加
```php
// AdminRoadController, AdminDungeonController に採集設定機能追加
- gathering_enabled チェックボックス
- 採集アイテム設定セクション
- GatheringMapping への関連付け機能
```

### Phase 5: ナビゲーション・UI統合

#### Task 5.1: 管理メニュー追加
```php
// resources/views/admin/layouts/app.blade.php
// ゲームデータセクションに「採集マッピング管理」を追加
<a href="{{ route('admin.gathering-mappings.index') }}" class="admin-nav-subitem">
    <svg class="admin-nav-icon">...</svg>
    採集マッピング管理
</a>
```

## ⚠️ 懸念点・リスク

### 1. データ整合性リスク
- **問題**: 既存ハードコードデータとDBデータの不整合
- **対策**: 段階的移行、マイグレーションSeederでデータ変換

### 2. パフォーマンス懸念
- **問題**: 採集処理でのDB参照増加
- **対策**: 適切なインデックス設定、キャッシュ戦略検討

### 3. 外部キー制約
- **問題**: items テーブルが存在しない可能性
- **対策**: items テーブル確認、存在しない場合は作成が必要

### 4. 管理画面権限
- **問題**: 新権限の既存管理者への割り当て
- **対策**: マイグレーション時に権限自動割り当て

## 🔧 改善提案

### 1. アイテム管理の統一化
```sql
-- items テーブル確認・作成が必要
CREATE TABLE items (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('consumable', 'material', 'equipment'),
    rarity INT DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. バリデーション強化
```php
// GatheringMapping バリデーションルール
'required_skill_level' => 'required|integer|min:1|max:100',
'success_rate' => 'required|numeric|min:0|max:100',
'quantity_min' => 'required|integer|min:1',
'quantity_max' => 'required|integer|min:1|gte:quantity_min',
```

### 3. 採集確率の管理改善
- 確率の合計チェック（100%を超えないように）
- スキルレベル別の難易度カーブ設定

### 4. データシーダー作成
```php
// database/seeders/GatheringMappingSeeder.php
// 既存ハードコードデータの自動移行
```

## 📅 実装スケジュール

| Phase | 期間 | 作業内容 |
|-------|------|----------|
| Phase 1 | 1日 | DB設計・マイグレーション |
| Phase 2 | 1日 | モデル・リレーション実装 |
| Phase 3 | 2日 | ゲームロジック更新・テスト |
| Phase 4 | 3日 | 管理画面実装 |
| Phase 5 | 1日 | UI統合・権限設定 |

**総実装期間**: 約8日間

## ✅ 完了基準

### 機能要件
- [ ] gathering_mapping_table テーブル作成完了
- [ ] Routes-GatheringMapping 紐付け機能実装完了
- [ ] 管理画面からの採集マッピング CRUD 操作可能
- [ ] Roads/Dungeons 管理画面での採集設定機能追加
- [ ] 既存採集ロジックのDB駆動への移行完了

### 品質要件
- [ ] 全テストケース通過
- [ ] パフォーマンス劣化なし
- [ ] 管理者権限正常動作
- [ ] データ整合性確保

---

**実装担当**: Claude Code  
**作成日**: 2025年8月22日  
**最終更新**: 2025年8月22日
