# Admin Pathways管理システム改善タスク

## 20AUG2025 - Pathways管理システム改善計画（修正版）

### 概要
ベストプラクティス案に基づいて、Admin管理画面のPathways管理を改善します。
**重要な修正**: ダンジョン管理に専用の`dungeons_desc`テーブルを導入し、ダンジョン情報と個別フロア（GameLocation）を分離管理する設計に変更します。

### データベース設計修正要件

#### 🔴 新規作成が必要なテーブル
1. **`dungeons_desc`テーブル**: ダンジョンマスター情報
   ```sql
   CREATE TABLE dungeons_desc (
       id BIGINT AUTO_INCREMENT PRIMARY KEY,
       dungeon_id VARCHAR(255) UNIQUE NOT NULL,  -- 例: "pyramid", "forest1"
       dungeon_name VARCHAR(255) NOT NULL,       -- 例: "ピラミッド"
       dungeon_desc TEXT,                        -- 例: "これはピラミッドです。全部で5階あります。"
       is_active BOOLEAN DEFAULT TRUE,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   );
   ```

#### 🔧 修正が必要なテーブル
2. **`game_locations`テーブル**: dungeon_id カラム追加
   ```sql
   ALTER TABLE game_locations 
   ADD COLUMN dungeon_id VARCHAR(255) NULL,
   ADD INDEX idx_dungeon_id (dungeon_id);
   
   -- 外部キー制約（オプション）
   ALTER TABLE game_locations 
   ADD CONSTRAINT fk_game_locations_dungeon_id 
   FOREIGN KEY (dungeon_id) REFERENCES dungeons_desc(dungeon_id) 
   ON DELETE SET NULL ON UPDATE CASCADE;
   ```

#### ✅ 確認済み項目
3. **既存テーブル構造**: 正常
   - `game_locations`: category, length, difficulty等の基本フィールド存在
   - `location_connections`: 接続管理用テーブル正常
   - 適切なインデックス設定済み

4. **既存モデル状況**: 部分的に対応済み
   - `GameLocation`モデル: 基本スコープ実装済み
   - 新規`DungeonDesc`モデルの作成が必要

## 実装タスク

### Phase 0: データベース構造変更

#### Task 0-1: dungeons_descテーブル作成マイグレーション 🗄️
- **ファイル**: `database/migrations/xxxx_create_dungeons_desc_table.php`
- **実装内容**:
  ```php
  Schema::create('dungeons_desc', function (Blueprint $table) {
      $table->id();
      $table->string('dungeon_id')->unique();
      $table->string('dungeon_name');
      $table->text('dungeon_desc')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
      
      $table->index('dungeon_id');
      $table->index('is_active');
  });
  ```

#### Task 0-2: game_locationsテーブルにdungeon_id追加 🔧
- **ファイル**: `database/migrations/xxxx_add_dungeon_id_to_game_locations_table.php`
- **実装内容**:
  ```php
  Schema::table('game_locations', function (Blueprint $table) {
      $table->string('dungeon_id')->nullable()->after('category');
      $table->index('dungeon_id');
      
      // 外部キー制約（参照整合性のため）
      $table->foreign('dungeon_id')
            ->references('dungeon_id')
            ->on('dungeons_desc')
            ->onDelete('set null')
            ->onUpdate('cascade');
  });
  ```

### Phase 1: モデル作成・拡張

#### Task 1-1: DungeonDescモデルの作成 🏗️
- **ファイル**: `app/Models/DungeonDesc.php`
- **実装内容**:
  ```php
  class DungeonDesc extends Model
  {
      protected $table = 'dungeons_desc';
      protected $primaryKey = 'id';
      
      protected $fillable = [
          'dungeon_id', 'dungeon_name', 'dungeon_desc', 'is_active'
      ];
      
      protected $casts = [
          'is_active' => 'boolean',
      ];
      
      // このダンジョンに属するフロア（GameLocation）
      public function floors()
      {
          return $this->hasMany(GameLocation::class, 'dungeon_id', 'dungeon_id')
                      ->where('category', 'dungeon')
                      ->orderBy('name');
      }
      
      // アクティブなフロア
      public function activeFloors()
      {
          return $this->floors()->where('is_active', true);
      }
      
      // スコープ
      public function scopeActive($query)
      {
          return $query->where('is_active', true);
      }
  }
  ```

#### Task 1-2: GameLocationモデルの拡張 🔧
- **ファイル**: `app/Models/GameLocation.php`
- **実装内容**:
  ```php
  // fillableにdungeon_id追加
  protected $fillable = [
      // ... 既存のフィールド ...
      'dungeon_id',
  ];
  
  // DungeonDescとのリレーション
  public function dungeonDesc()
  {
      return $this->belongsTo(DungeonDesc::class, 'dungeon_id', 'dungeon_id');
  }
  
  // 同じダンジョンの他フロア
  public function siblingFloors()
  {
      return $this->where('dungeon_id', $this->dungeon_id)
                  ->where('id', '!=', $this->id)
                  ->where('category', 'dungeon');
  }
  
  // ダンジョンフロア判定
  public function isDungeonFloor(): bool
  {
      return $this->category === 'dungeon' && !empty($this->dungeon_id);
  }
  ```

### Phase 2: コントローラー分離・作成

#### Task 2-1: AdminRoadControllerの作成 🏗️
- **ファイル**: `app/Http/Controllers/Admin/AdminRoadController.php`
- **責務**: categoryが'road'のGameLocationのCRUD管理
- **実装内容**:
  ```php
  - index(): roads()スコープを使用したroad一覧表示
  - show(): road詳細表示  
  - create/store(): road作成（category='road'固定、dungeon_id=null）
  - edit/update(): road更新
  - destroy(): road削除
  ```

#### Task 2-2: AdminDungeonControllerの作成 🏗️
- **ファイル**: `app/Http/Controllers/Admin/AdminDungeonController.php`
- **責務**: DungeonDescテーブルベースのダンジョン管理
- **実装内容**:
  ```php
  - index(): DungeonDesc一覧（with('floors')でフロア数も取得）
  - show(): 特定ダンジョンの詳細とフロア一覧
  - create/store(): DungeonDesc作成
  - edit/update(): DungeonDesc更新
  - destroy(): DungeonDesc削除（関連フロアはdungeon_id=nullにセット）
  - floors(): 特定ダンジョンのフロア（GameLocation）管理
  - createFloor/storeFloor(): 新フロア作成（category='dungeon'、dungeon_id設定）
  ```

#### Task 2-3: ルート定義の追加 🔧
- **ファイル**: `routes/admin.php`
- **実装内容**:
  ```php
  Route::resource('roads', AdminRoadController::class)->names('admin.roads');
  Route::resource('dungeons', AdminDungeonController::class)->names('admin.dungeons');
  Route::get('dungeons/{dungeon}/floors', [AdminDungeonController::class, 'floors'])
      ->name('admin.dungeons.floors');
  Route::post('dungeons/{dungeon}/floors', [AdminDungeonController::class, 'storeFloor'])
      ->name('admin.dungeons.floors.store');
  ```

### Phase 3: ビュー作成

#### Task 3-1: Road管理ビューの作成 🎨
- **ディレクトリ**: `resources/views/admin/roads/`
- **ファイル構成**:
  ```
  admin/roads/
  ├── index.blade.php      # Road一覧
  ├── show.blade.php       # Road詳細
  ├── create.blade.php     # Road作成
  ├── edit.blade.php       # Road編集
  └── _form.blade.php      # Road用フォーム部品（dungeon_id=null固定）
  ```

#### Task 3-2: Dungeon管理ビューの作成 🎨
- **ディレクトリ**: `resources/views/admin/dungeons/`
- **ファイル構成**:
  ```
  admin/dungeons/
  ├── index.blade.php      # DungeonDesc一覧（フロアトグル表示付き）
  ├── show.blade.php       # DungeonDesc詳細（フロア一覧付き）
  ├── create.blade.php     # DungeonDesc作成
  ├── edit.blade.php       # DungeonDesc編集
  ├── floors.blade.php     # フロア（GameLocation）管理画面
  ├── create-floor.blade.php # 新フロア作成
  ├── _form.blade.php      # DungeonDesc用フォーム
  └── _floor-form.blade.php # フロア（GameLocation）用フォーム
  ```

#### Task 3-3: ダンジョン一覧のトグル機能実装 🎨
- **ファイル**: `resources/views/admin/dungeons/index.blade.php`
- **実装内容**:
  ```blade
  <!-- DungeonDescテーブルベースの一覧表示 -->
  @foreach($dungeons as $dungeon)
      <tr class="cursor-pointer" x-data="{ expanded: false }">
          <td @click="expanded = !expanded">
              <i class="fas" :class="expanded ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
              {{ $dungeon->dungeon_name }}
              <span class="badge bg-info">{{ $dungeon->floors_count }}フロア</span>
          </td>
      </tr>
      <!-- トグル展開でフロア一覧表示 -->
      <tr x-show="expanded" x-collapse>
          <td colspan="4" class="bg-light">
              @foreach($dungeon->floors as $floor)
                  <div class="ps-4 py-1 border-start border-2">
                      <strong>{{ $floor->name }}</strong>
                      <small class="text-muted">({{ $floor->difficulty }})</small>
                  </div>
              @endforeach
          </td>
      </tr>
  @endforeach
  ```

#### Task 3-4: フロア管理ビューの作成 🎨
- **ファイル**: `resources/views/admin/dungeons/floors.blade.php`
- **実装内容**:
  - 特定dungeon_idに属するGameLocationの一覧
  - フロア（GameLocation）の作成・編集・削除
  - ダンジョン基本情報の表示
  - パンくずリストでナビゲーション

### Phase 4: 管理画面ナビゲーション更新

#### Task 4-1: サイドバーメニュー更新 🔧
- **ファイル**: `resources/views/admin/layouts/app.blade.php`
- **実装内容**:
  ```html
  <!-- 現在のLocationメニューを以下のように分割 -->
  <li class="nav-item">
      <a class="nav-link" href="{{ route('admin.roads.index') }}">
          <i class="fas fa-road"></i> Road管理
      </a>
  </li>
  <li class="nav-item">
      <a class="nav-link" href="{{ route('admin.dungeons.index') }}">
          <i class="fas fa-dungeon"></i> Dungeon管理
      </a>
  </li>
  <!-- 既存のTownsやConnectionsメニューは維持 -->
  ```

### Phase 5: データ移行・整理

#### Task 5-1: 既存データの移行検討 🔧
- **目的**: 既存の`game_locations`でcategory='dungeon'のデータを新構造に適応
- **実装内容**:
  - 既存ダンジョンデータの分析
  - DungeonDescレコードの作成方法検討
  - データ移行スクリプトの作成（必要に応じて）

#### Task 5-2: AdminLocationController整理 🔧
- **ファイル**: `app/Http/Controllers/Admin/AdminLocationController.php`
- **実装内容**:
  - pathways()メソッドを新システムへのリダイレクトに変更
  - towns()とconnections()メソッドは継続使用
  - 後方互換性の確保

### Phase 6: テストとドキュメント

#### Task 6-1: 機能テストの実装 ✅
- **ディレクトリ**: `tests/Feature/Admin/`
- **実装内容**:
  ```php
  - AdminRoadControllerTest.php
  - AdminDungeonControllerTest.php  
  - DungeonDescModelTest.php（新規）
  - GameLocationDungeonRelationTest.php（拡張テスト）
  ```

#### Task 6-2: データベースドキュメント更新 📚
- **ファイル**: `Development Documents/01_development_docs/02_database_design.md`
- `dungeons_desc`テーブルの追加
- GameLocationとDungeonDescのリレーション説明

#### Task 6-3: 実装完了記録 📝
- **ファイル**: `Development Documents/Notes/implemented_note.md`
- DungeonDescベース管理システムの実装記録

## 優先順位と推奨実装順序

### 🔴 最優先（データベース構造）
1. Task 0-1: dungeons_descテーブル作成
2. Task 0-2: game_locationsにdungeon_id追加

### 🟠 高優先度（モデル・コントローラー）
3. Task 1-1: DungeonDescモデル作成
4. Task 1-2: GameLocationモデル拡張
5. Task 2-1: AdminRoadController作成
6. Task 2-2: AdminDungeonController作成
7. Task 2-3: ルート定義追加

### 🟡 中優先度（ビュー）
8. Task 3-1: Road管理ビュー作成
9. Task 3-2: Dungeon管理ビュー作成
10. Task 3-3: ダンジョン一覧トグル機能
11. Task 3-4: フロア管理ビュー作成

### 🟢 低優先度（統合・保守）
12. Task 4-1: サイドバーメニュー更新
13. Task 5-1: データ移行検討
14. Task 5-2: 既存コントローラー整理
15. Task 6-1: テスト実装

## 技術仕様（修正版）

### データベース設計の変更点
- ✅ 新規`dungeons_desc`テーブルによるダンジョンマスター管理
- ✅ `game_locations.dungeon_id`による親子関係構築
- ✅ 外部キー制約による参照整合性確保

### ダンジョン管理構造
```
DungeonDesc (dungeons_desc)
├── dungeon_id: "pyramid"
├── dungeon_name: "ピラミッド"
├── dungeon_desc: "これはピラミッドです。全部で5階あります。"
└── floors (hasMany)
    ├── GameLocation { dungeon_id: "pyramid", name: "ピラミッド1階" }
    ├── GameLocation { dungeon_id: "pyramid", name: "ピラミッド2階" }
    └── GameLocation { dungeon_id: "pyramid", name: "ピラミッド3階" }
```

### セキュリティ考慮事項
- 外部キー制約による不整合データ防止
- AdminControllerベースクラスの権限チェック継承
- dungeon_id参照時のvalidation強化

### パフォーマンス考慮事項
- DungeonDesc -> floors のEager Loading
- dungeon_idインデックス活用
- ページネーション実装

---

## 実装開始前の確認事項

1. **データバックアップ**: マイグレーション前の全テーブルバックアップ
2. **既存ダンジョンデータの分析**: category='dungeon'のGameLocationの現状把握
3. **テスト環境での動作確認**: 本番適用前の十分な検証
4. **権限設定の確認**: DungeonDescアクセス権限の設定

## 完了基準

- [ ] `dungeons_desc`テーブルが正常に作成・動作している
- [ ] DungeonDescとGameLocationのリレーションが正しく動作している
- [ ] ダンジョン管理画面でトグル表示によるフロア一覧が表示される
- [ ] Road管理とDungeon管理が独立したコントローラーで動作している
- [ ] 既存機能の後方互換性が保たれている
- [ ] 全ての新機能でエラーハンドリングが適切に実装されている

この修正版タスクリストにより、DungeonDescテーブルを核とした新しいダンジョン管理システムを段階的に実装できます。