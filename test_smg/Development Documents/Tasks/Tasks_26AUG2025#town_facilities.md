# Town Facilities (旧Shops) システム変更タスク

**作成日**: 2025年8月26日  
**プロジェクト**: test_SMG - Shopsテーブル名称変更とシステム統一  
**目標**: shopsテーブルをtown_facilitiesに変更し、より明確な概念に統一  

---

## 🎯 変更概要

### 変更理由
- **意味の曖昧性解決**: "shops"は商店に限定されるが、実際は町の施設全般を管理
- **概念の明確化**: 酒場、鍛冶屋、錬金屋など店舗以外の施設も含む
- **将来の拡張性**: 宿屋、ギルド、図書館なども追加可能な構造へ

### 新しい概念
- **旧**: `shops` (ショップ) → **新**: `town_facilities` (町の施設)
- **旧**: `shop_type` → **新**: `facility_type`  
- **旧**: `ShopType` → **新**: `FacilityType`
- **旧**: `Shop` Model → **新**: `TownFacility` Model

---

## 📋 実装タスク詳細

### Phase 1: データベース構造変更
**期間**: 1日  
**重要度**: 🔴 最重要

#### 1.1 マイグレーション作成
- [ ] **1.1.1** 新テーブル作成マイグレーション
  ```php
  // database/migrations/2025_08_26_create_town_facilities_table.php
  Schema::create('town_facilities', function (Blueprint $table) {
      $table->id();
      $table->string('name');                      // 施設名
      $table->string('facility_type');            // 施設種別
      $table->string('location_id');              // 所在地ID
      $table->string('location_type');            // 場所種別
      $table->boolean('is_active')->default(true); // 営業状態
      $table->text('description')->nullable();     // 施設説明
      $table->json('facility_config')->nullable(); // 施設設定
      $table->timestamps();
      
      $table->unique(['location_id', 'location_type', 'facility_type']);
  });
  ```

- [ ] **1.1.2** 関連テーブル名変更マイグレーション
  ```php
  // database/migrations/2025_08_26_rename_shop_items_to_facility_items.php
  Schema::rename('shop_items', 'facility_items');
  
  // カラム名も変更
  Schema::table('facility_items', function (Blueprint $table) {
      $table->renameColumn('shop_id', 'facility_id');
  });
  ```

- [ ] **1.1.3** データ移行マイグレーション
  ```php
  // database/migrations/2025_08_26_migrate_shops_to_town_facilities.php
  // 既存のshopsテーブルからtown_facilitiesへデータ移行
  ```

- [ ] **1.1.4** 旧テーブル削除マイグレーション
  ```php
  // database/migrations/2025_08_26_drop_shops_table.php
  Schema::dropIfExists('shops');
  ```

#### 1.2 Seeder更新
- [ ] **1.2.1** TownFacilitySeeder作成
  ```php
  // database/seeders/TownFacilitySeeder.php
  // 旧ShopSeederの内容を移植・更新
  ```

- [ ] **1.2.2** AdminSystemSeeder権限更新
  ```php
  // town_facilities.* 権限に変更
  ['name' => 'town_facilities.view', 'category' => 'town_facilities', ...],
  ['name' => 'town_facilities.edit', 'category' => 'town_facilities', ...],
  ['name' => 'town_facilities.create', 'category' => 'town_facilities', ...],
  ```

---

### Phase 2: モデル・Enum変更
**期間**: 0.5日  
**重要度**: 🔴 最重要

#### 2.1 モデルクラス更新
- [ ] **2.1.1** TownFacility モデル作成
  ```php
  // app/Models/TownFacility.php
  class TownFacility extends Model
  {
      protected $table = 'town_facilities';
      protected $fillable = [
          'name', 'facility_type', 'location_id', 'location_type',
          'is_active', 'description', 'facility_config'
      ];
      protected $casts = [
          'is_active' => 'boolean',
          'facility_config' => 'array',
      ];
  }
  ```

- [ ] **2.1.2** FacilityItem モデル作成
  ```php
  // app/Models/FacilityItem.php (旧ShopItem)
  ```

- [ ] **2.1.3** 旧Shopモデル削除

#### 2.2 Enum変更
- [ ] **2.2.1** FacilityType Enum作成
  ```php
  // app/Enums/FacilityType.php
  enum FacilityType: string
  {
      case ITEM_SHOP = 'item_shop';
      case BLACKSMITH = 'blacksmith';
      case TAVERN = 'tavern';
      case ALCHEMY_SHOP = 'alchemy_shop';
      // 将来拡張用
      case INN = 'inn';
      case GUILD = 'guild';
      case LIBRARY = 'library';
  }
  ```

- [ ] **2.2.2** 旧ShopType Enum削除

---

### Phase 3: サービスクラス更新
**期間**: 1日  
**重要度**: 🔴 最重要

#### 3.1 Contract・Interface更新
- [ ] **3.1.1** FacilityServiceInterface作成
  ```php
  // app/Contracts/FacilityServiceInterface.php (旧ShopServiceInterface)
  interface FacilityServiceInterface
  {
      public function canEnterFacility(string $locationId, string $locationType): bool;
      public function getFacilityData(TownFacility $facility): array;
      public function processTransaction(TownFacility $facility, Player $player, array $data): array;
      // ...
  }
  ```

#### 3.2 具体サービスクラス更新
- [ ] **3.2.1** AbstractFacilityService作成
  ```php
  // app/Services/AbstractFacilityService.php (旧AbstractShopService)
  ```

- [ ] **3.2.2** 各施設サービス更新
  ```php
  // app/Services/ItemShopService.php → ItemFacilityService.php
  // app/Services/BlacksmithService.php → BlacksmithFacilityService.php
  // app/Services/TavernService.php → TavernFacilityService.php
  // app/Services/AlchemyShopService.php → AlchemyFacilityService.php
  ```

- [ ] **3.2.3** FacilityServiceFactory作成
  ```php
  // app/Services/FacilityServiceFactory.php (旧ShopServiceFactory)
  ```

---

### Phase 4: コントローラー更新
**期間**: 1日  
**重要度**: 🔴 最重要

#### 4.1 ゲーム側コントローラー更新
- [ ] **4.1.1** BaseFacilityController作成
  ```php
  // app/Http/Controllers/BaseFacilityController.php (旧BaseShopController)
  protected FacilityType $facilityType;
  protected FacilityServiceInterface $facilityService;
  ```

- [ ] **4.1.2** 各施設コントローラー更新
  ```php
  // app/Http/Controllers/ItemShopController.php → ItemFacilityController.php
  // app/Http/Controllers/BlacksmithController.php → BlacksmithFacilityController.php
  // app/Http/Controllers/TavernController.php → TavernFacilityController.php
  // app/Http/Controllers/AlchemyShopController.php → AlchemyFacilityController.php
  ```

#### 4.2 管理画面コントローラー更新
- [ ] **4.2.1** AdminTownFacilityController作成
  ```php
  // app/Http/Controllers/Admin/AdminTownFacilityController.php (旧AdminShopController)
  ```

---

### Phase 5: ルーティング更新
**期間**: 0.5日  
**重要度**: 🔴 最重要

#### 5.1 ゲームルート更新
- [ ] **5.1.1** routes/web.php更新
  ```php
  // 旧: Route::prefix('shops')->group(function () {
  // 新: Route::prefix('facilities')->group(function () {
  //     Route::get('/item', [ItemFacilityController::class, 'index'])->name('facilities.item.index');
  //     Route::get('/blacksmith', [BlacksmithFacilityController::class, 'index'])->name('facilities.blacksmith.index');
  //     Route::get('/tavern', [TavernFacilityController::class, 'index'])->name('facilities.tavern.index');
  //     Route::get('/alchemy', [AlchemyFacilityController::class, 'index'])->name('facilities.alchemy.index');
  // });
  ```

#### 5.2 管理画面ルート更新
- [ ] **5.2.1** routes/admin.php更新
  ```php
  // 旧: Route::middleware(['admin.permission:shops.view'])->group(function () {
  // 新: Route::middleware(['admin.permission:town_facilities.view'])->group(function () {
  //     Route::get('/town-facilities', [AdminTownFacilityController::class, 'index'])->name('town-facilities.index');
  //     Route::get('/town-facilities/{facility}', [AdminTownFacilityController::class, 'show'])->name('town-facilities.show');
  // });
  ```

---

### Phase 6: ビューファイル更新
**期間**: 1.5日  
**重要度**: 🟡 高

#### 6.1 ゲーム画面ビュー更新
- [ ] **6.1.1** 施設ディレクトリ構造変更
  ```
  旧: resources/views/shops/
  新: resources/views/facilities/
      ├── base/
      ├── item/
      ├── blacksmith/
      ├── tavern/
      └── alchemy/
  ```

- [ ] **6.1.2** 町画面での施設一覧表示更新
  ```php
  // resources/views/game-states/town-sidebar.blade.php
  // resources/views/game-states/town-left.blade.php
  // resources/views/game/partials/location_info.blade.php
  
  // 旧: $townShops = \App\Models\Shop::getShopsByLocation(...)
  // 新: $townFacilities = \App\Models\TownFacility::getFacilitiesByLocation(...)
  
  // 旧ルート名: 'shops.item.index'
  // 新ルート名: 'facilities.item.index'
  ```

#### 6.2 管理画面ビュー更新
- [ ] **6.2.1** 管理画面ディレクトリ変更
  ```
  旧: resources/views/admin/shops/
  新: resources/views/admin/town-facilities/
      ├── index.blade.php
      ├── show.blade.php
      ├── create.blade.php
      └── edit.blade.php
  ```

- [ ] **6.2.2** ナビゲーション更新
  ```php
  // resources/views/admin/layouts/app.blade.php
  // 旧: route('admin.shops.index')
  // 新: route('admin.town-facilities.index')
  
  // 旧: request()->routeIs('admin.shops.*')
  // 新: request()->routeIs('admin.town-facilities.*')
  ```

- [ ] **6.2.3** ダッシュボード統計表示更新
  ```php
  // resources/views/admin/dashboard/index.blade.php
  // ショップ統計 → 町施設統計に変更
  ```

---

### Phase 7: API・JavaScript更新  
**期間**: 0.5日  
**重要度**: 🟢 中

#### 7.1 API エンドポイント更新
- [ ] **7.1.1** ゲームAPIルート更新
  ```php
  // routes/web.php
  // 旧: Route::get('/api/location/shops', [GameController::class, 'getLocationShops'])
  // 新: Route::get('/api/location/facilities', [GameController::class, 'getLocationFacilities'])
  ```

- [ ] **7.1.2** GameController API メソッド更新
  ```php
  // app/Http/Controllers/GameController.php
  // 旧: getLocationShops()
  // 新: getLocationFacilities()
  ```

#### 7.2 JavaScript・Ajax更新
- [ ] **7.2.1** フロントエンドAPI呼び出し更新
  ```javascript
  // resources/views内のJavaScript
  // 旧: '/api/location/shops'
  // 新: '/api/location/facilities'
  ```

---

### Phase 8: テスト・関連サービス更新
**期間**: 1日  
**重要度**: 🟡 高

#### 8.1 Admin関連サービス更新
- [ ] **8.1.1** AdminRouteService更新
  ```php
  // app/Services/Admin/AdminRouteService.php
  // 旧: in_array('shops', $modules)
  // 新: in_array('town_facilities', $modules)
  ```

#### 8.2 設定ファイル・文書更新
- [ ] **8.2.1** 設定ファイル確認・更新
  ```php
  // config/ 内でshop関連の設定があれば更新
  ```

- [ ] **8.2.2** ドキュメント更新
  ```markdown
  // Development Documents/ 内の関連文書更新
  // - Database_Design.md
  // - API設計
  // - 画面遷移設計
  ```

---

## 🚨 重要な注意点

### データ整合性
1. **必須**: マイグレーション実行前のデータバックアップ
2. **必須**: 段階的なマイグレーション実行（一度に全てを変更しない）
3. **推奨**: ロールバック手順の事前準備

### ダウンタイム最小化
1. **推奨**: メンテナンスモード中での実行
2. **推奨**: Blue-Green デプロイメント方式の検討
3. **必須**: 変更前後の動作確認

### 後方互換性
1. **検討**: 旧ルート名へのリダイレクト処理
2. **検討**: 段階的な移行期間の設定
3. **推奨**: エラーハンドリングの強化

---

## 📊 実装優先順位

### 🔴 Phase 1-3: 最優先（必須）
- データベース構造変更
- モデル・Enum更新  
- サービスクラス更新

### 🟡 Phase 4-6: 高優先
- コントローラー更新
- ルーティング更新
- ビューファイル更新

### 🟢 Phase 7-8: 中優先
- API・JavaScript更新
- テスト・関連サービス更新

---

## 🔧 実装後の確認項目

### 機能確認
- [ ] 各町での施設一覧表示
- [ ] 各施設への入場・取引機能
- [ ] 管理画面での施設管理機能
- [ ] 権限制御の正常動作

### パフォーマンス確認  
- [ ] データベースクエリ性能
- [ ] ページロード時間
- [ ] メモリ使用量

### セキュリティ確認
- [ ] 権限チェック動作
- [ ] データバリデーション
- [ ] SQLインジェクション対策

---

**作成者**: GitHub Copilot  
**承認者**: [要承認]  
**開始予定日**: 2025年8月26日  
**完了予定日**: 2025年8月29日  

**重要**: このタスクは既存のゲームプレイに影響するため、十分なテストとバックアップを行ってから実行してください。
