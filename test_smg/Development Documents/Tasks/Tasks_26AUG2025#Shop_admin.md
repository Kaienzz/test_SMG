# Tasks_26AUG2025#Shop_admin.md
# 町施設管理機能 - Create/Edit実装タスク

**作成日**: 2025年8月26日  
**対象**: /admin/town-facilities/create および /admin/town-facilities/edit 機能  
**担当**: 開発チーム  

---

## **📋 概要**

町の施設作成・編集機能の実装。各町における施設リスト（道具屋、鍛冶屋等）の作成・管理、および施設ごとの販売アイテム設定機能の開発。

### **要件まとめ**
- ✅ 各町における施設リストの作成機能
- ✅ 町ごとの施設種別選択機能
- ✅ 施設作成後の販売アイテム編集機能(/edit)
- ✅ 町によって異なる商品ラインナップの設定

---

## **🏗️ Phase 1: 町施設作成機能(/admin/town-facilities/create)の実装**

### **1.1 フロントエンド関連**

#### **1.1.1 Create画面のBladeテンプレート作成**
- [ ] **ファイル**: `resources/views/admin/town-facilities/create.blade.php`
- [ ] **実装内容**:
  ```php
  @extends('admin.layouts.app')
  @section('title', '新規施設作成')
  
  // 必要な要素:
  // - 町選択ドロップダウン
  // - 施設タイプ選択
  // - 施設名入力
  // - 説明テキストエリア
  // - 稼働状態チェックボックス
  // - バリデーションエラー表示
  ```

#### **1.1.2 町選択機能**
- [ ] **データソース**: Route model (`category = 'town'`)
- [ ] **利用可能な町**:
  - town_a (A町)
  - town_prima (プリマ)
  - town_b (B町)
  - town_c (C町)
  - elven_village (エルフの村)
  - merchant_city (商業都市)

#### **1.1.3 施設タイプ選択**
- [ ] **データソース**: `App\Enums\FacilityType`
- [ ] **利用可能な施設タイプ**:
  - 🏪 道具屋 (item_shop)
  - ⚒️ 鍛冶屋 (blacksmith)
  - ⚔️ 武器屋 (weapon_shop)
  - 🛡️ 防具屋 (armor_shop)
  - 🔮 魔法屋 (magic_shop)
  - ⚗️ 錬金屋 (alchemy_shop)

#### **1.1.4 フロントエンドバリデーション**
- [ ] **重複チェック**: 同一町に同じ施設タイプの重複防止
- [ ] **実装**: Ajax による即座重複チェック
- [ ] **ユーザビリティ**: 選択済み施設タイプの無効化

### **1.2 バックエンド関連**

#### **1.2.1 AdminTownFacilityController改善**
- [x] ✅ **確認済**: 基本的なcreate/storeメソッドは実装済み
- [ ] **改善タスク**: `getAvailableLocations()`メソッドの動的化
  ```php
  private function getAvailableLocations(): array
  {
      return Route::where('category', 'town')
                  ->where('is_active', true)
                  ->orderBy('name')
                  ->get(['id', 'name'])
                  ->map(function($town) {
                      return [
                          'id' => $town->id,
                          'name' => $town->name,
                          'type' => 'town'
                      ];
                  })
                  ->toArray();
  }
  ```

#### **1.2.2 バリデーション強化**
- [ ] **ユニーク制約チェック**: 
  ```php
  'facility_type' => [
      'required',
      'string',
      'in:' . implode(',', FacilityType::getAllTypes()),
      Rule::unique('town_facilities')->where(function ($query) use ($request) {
          return $query->where('location_id', $request->location_id)
                       ->where('location_type', $request->location_type);
      })
  ]
  ```

#### **1.2.3 成功時のリダイレクト**
- [ ] **実装**: 初期設定ウィザードの提供

---

## **🔧 Phase 2: 町施設編集機能(/admin/town-facilities/edit)の実装**

### **2.1 Edit画面のBladeテンプレート作成**

#### **2.1.1 基本編集フォーム**
- [ ] **ファイル**: `resources/views/admin/town-facilities/edit.blade.php`
- [ ] **構成**:
  ```
  ├── 基本情報セクション
  │   ├── 施設名編集
  │   ├── 説明編集
  │   ├── 稼働状態切り替え
  │   └── 施設タイプ・町（読み取り専用）
  ├── 施設タイプ別設定セクション
  │   ├── 商品系施設の場合 → 販売アイテム管理
  │   ├── サービス系施設の場合 → サービス設定
  │   └── 回復系施設の場合 → 回復サービス設定
  └── 操作ボタン
      ├── 保存
      ├── キャンセル
      └── 削除（権限がある場合）
  ```

### **2.2 販売アイテム管理機能（商品系施設）**

#### **2.2.1 現在の販売アイテム一覧**
- [ ] **実装**: FacilityItemの一覧表示
- [ ] **表示項目**:
  - アイテム名・アイコン
  - 販売価格
  - 在庫設定（無限/-1 or 数量）
  - 販売状態（有効/無効）
  - 操作ボタン（編集/削除）

#### **2.2.2 新規アイテム追加機能**
- [ ] **実装**: アイテム選択モーダル
- [ ] **機能要件**:
  ```
  ├── アイテム検索
  │   ├── 名前での部分一致検索
  │   ├── カテゴリでの絞り込み
  │   └── レアリティでの絞り込み
  ├── アイテム詳細表示
  │   ├── アイテム情報表示
  │   ├── 基本価格表示
  │   └── 推奨販売価格表示
  └── 価格・在庫設定
      ├── 販売価格入力
      ├── 在庫設定（無限 or 数量）
      └── 即座販売開始チェック
  ```

#### **2.2.3 既存アイテム編集機能**
- [ ] **実装**: インライン編集 or モーダル編集
- [ ] **編集項目**:
  - 販売価格変更
  - 在庫数変更
  - 販売状態切り替え

### **2.3 サービス系施設の設定（鍛冶屋・錬金屋）**

#### **2.3.1 サービス設定管理**
- [ ] **実装**: `facility_config` JSON設定
- [ ] **鍛冶屋設定例**:
  ```json
  {
    "services": {
      "repair": {
        "enabled": true,
        "base_cost_rate": 0.1,
        "max_cost": 1000
      },
      "enhance": {
        "enabled": true,
        "base_cost": 100,
        "success_rate": 0.8,
        "max_enhancement": 10
      },
      "dismantle": {
        "enabled": false,
        "material_return_rate": 0.5
      }
    }
  }
  ```

#### **2.3.2 錬金屋設定例**
- [ ] **実装**: 錬金術レシピ管理
- [ ] **設定内容**:
  ```json
  {
    "recipes": {
      "potion_crafting": true,
      "weapon_enhancement": true,
      "material_synthesis": false
    },
    "success_rates": {
      "basic_potion": 0.9,
      "advanced_potion": 0.7,
      "enhancement": 0.6
    }
  }
  ```

### **2.4 回復系施設の設定（酒屋）**

#### **2.4.1 酒屋設定**
- [ ] **実装内容**: 酒屋の実装に基づく

---

## **🔧 Phase 3: 高度な機能実装**

### **3.1 アイテム選択支援機能**

#### **3.1.1 推奨価格計算機能**
- [ ] **実装**: アイテムの適正価格計算
- [ ] **計算要素**:
  - アイテムの基本価格
  - 町のレベル/ランク
  - 他施設での販売価格
  - 需要と供給のバランス

#### **3.1.2 競合チェック機能**
- [ ] **実装**: 同町内での価格競合チェック
- [ ] **警告表示**: 他施設との価格差が大きい場合の警告

### **3.2 施設運営分析機能**

#### **3.2.1 売上分析ダッシュボード**
- [ ] **実装**: 施設ごとの売上統計
- [ ] **表示項目**:
  - 日次/週次/月次売上
  - 人気商品ランキング
  - 利益率分析

#### **3.2.2 在庫管理**
- [ ] **実装**: 在庫切れアラート
- [ ] **実装**: 自動発注機能

---

## **🔄 データベース・モデル関連**

### **4.1 既存テーブル確認**
- [x] ✅ **town_facilities**: 基本情報格納
- [x] ✅ **facility_items**: 販売アイテム情報
- [x] ✅ **ユニーク制約**: `(location_id, location_type, facility_type)`

### **4.2 FacilityItem管理機能拡張**
- [ ] **実装**: 一括アイテム追加機能
- [ ] **実装**: CSVインポート/エクスポート機能
- [ ] **実装**: テンプレート適用機能（施設タイプ別の標準商品セット）

### **4.3 audit_logs連携**
- [ ] **実装**: 施設作成・編集・削除のログ記録
- [ ] **実装**: 価格変更・商品追加のログ記録

---

## **🚨 重要な注意点・制約事項**

### **5.1 データ整合性**
- **制約**: 同一町に同じ施設タイプは1つのみ
- **対応**: 作成前の重複チェック + ユーザーフレンドリーなエラーメッセージ
- **実装**: フロントエンド即座チェック + サーバーサイドバリデーション

### **5.2 パフォーマンス**
- **課題**: 大量のアイテムリストの表示
- **対応**: ページネーション + Ajax読み込み + 検索機能
- **実装**: 仮想スクロール（大量データの場合）

### **5.3 権限管理**
- **確認必須**: `town_facilities.create`, `town_facilities.edit`, `town_facilities.delete`権限
- **実装**: ユーザーの権限レベルに応じた機能制限
- **UIの調整**: 権限がない機能のボタン非表示


### **5.4 経済システム**
- **考慮**: 町間の価格差設定
- **実装**: 町ランクによる価格倍率システム
- **バランス**: プレイヤーの移動コストと価格差の均衡

---

## **📅 実装スケジュール**

### **Week 1: Phase 1完了**
- [x] ✅ 基本的なControllerメソッドは完了済み
- [ ] Day 1-2: Create画面テンプレート作成
- [ ] Day 3-4: フロントエンドバリデーション実装
- [ ] Day 5: Controller改善（動的町取得）

### **Week 2: Phase 2-1完了**
- [ ] Day 1-2: Edit画面基本テンプレート作成
- [ ] Day 3-5: 販売アイテム管理機能実装

### **Week 3: Phase 2-2完了**
- [ ] Day 1-2: アイテム選択モーダル実装
- [ ] Day 3-4: サービス系施設設定実装
- [ ] Day 5: 回復系施設設定実装

### **Week 4: Phase 3 + テスト**
- [ ] Day 1-2: 高度な機能実装
- [ ] Day 3-4: 総合テスト・バグフィックス
- [ ] Day 5: ドキュメント整備・リリース準備

---

## **🧪 テスト項目**

### **6.1 機能テスト**
- [ ] 各施設タイプの作成テスト
- [ ] 重複制約のテスト
- [ ] 商品追加・編集・削除テスト
- [ ] 価格設定・在庫管理テスト

### **6.2 UI/UXテスト**
- [ ] レスポンシブデザインテスト
- [ ] ブラウザ互換性テスト
- [ ] アクセシビリティテスト

### **6.3 パフォーマンステスト**
- [ ] 大量データでの動作テスト
- [ ] 同時アクセステスト

### **6.4 セキュリティテスト**
- [ ] 権限チェックテスト
- [ ] CSRFトークンテスト
- [ ] SQLインジェクションテスト

---

## **📚 参考情報**

### **7.1 既存実装されているファイル**
- ✅ `app/Http/Controllers/Admin/AdminTownFacilityController.php`
- ✅ `app/Models/TownFacility.php`
- ✅ `app/Models/FacilityItem.php`
- ✅ `app/Enums/FacilityType.php`
- ✅ `routes/admin.php` (ルート定義済み)
- ✅ `resources/views/admin/town-facilities/index.blade.php`

### **7.2 未実装ファイル**
- ❌ `resources/views/admin/town-facilities/create.blade.php`
- ❌ `resources/views/admin/town-facilities/edit.blade.php`
- ❌ `resources/views/admin/town-facilities/show.blade.php` (詳細表示)

### **7.3 関連するコンポーネント**
- `resources/views/admin/shared/_item_selector.blade.php` (作成予定)
- `resources/views/admin/shared/_price_calculator.blade.php` (作成予定)
- JavaScript/Ajax 関連ファイル (作成予定)

---

**このタスクドキュメントは施設管理機能の完全な実装ガイドとして作成されています。各Phase を順次実装することで、ユーザーフレンドリーで機能豊富な町施設管理システムが構築されます。**
