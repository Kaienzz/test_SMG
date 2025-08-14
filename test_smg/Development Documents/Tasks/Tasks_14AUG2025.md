# ロケーション管理システムDB移行＆管理画面開発タスクリスト (2025/08/14) - 洗練版

## 概要
`app/Domain/Location/LocationService.php` にハードコードされているロケーション情報をデータベース管理に移行し、管理者がWeb UIで柔軟に編集できるシステムを構築する。

---

## Phase 1: データベース基盤の構築とデータ移行

**目的:** 既存のハードコード情報を完全に格納できるDBスキーマを設計し、安全にデータを移行する。

- [ ] **DBスキーマ設計の最終化**
  - [ ] `locations` テーブル
    - `id` (PK)
    - `slug` (string, unique, index): `town_prima`, `road_1` 等。**`LocationService` 内の配列キーと完全一致させる。**
    - `name` (string): `プリマ`, `プリマ街道` 等の表示名。
    - `type` (enum: 'town', 'road', 'dungeon')): ロケーション種別。
    - `description` (text, nullable): 管理者用のメモなど。
  - [ ] `connections` テーブル
    - `id` (PK)
    - `from_location_id` (FK to locations.id, index): 出発元のロケーション。
    - `to_location_id` (FK to locations.id, index): 到着先のロケーション。
    - `direction` (string, nullable): `north`, `east`, `straight`, `right` 等。**町からの接続方向、または道路の分岐方向を示す。**
    - `branch_point` (integer, nullable): 道路上の分岐点 (例: 50)。`direction`が`straight`, `left`, `right`等の場合に使用。
    - `description` (string, nullable): `北への道` 等のUI表示用テキスト。

- [ ] **モデルとマイグレーションの実装**
  - [ ] `php artisan make:model Location -m` を実行。
  - [ ] `create_locations_table` マイグレーションファイルを編集。
  - [ ] `php artisan make:model Connection -m` を実行。
  - [ ] `create_connections_table` マイグレーションファイルを編集。
  - [ ] `Location` モデルにリレーションを定義: `hasMany(Connection::class, 'from_location_id')`
  - [ ] `Connection` モデルにリレーションを定義: `belongsTo(Location::class, 'from_location_id')`, `belongsTo(Location::class, 'to_location_id')`

- [ ] **データ移行 (Seeder) の実装**
  - [ ] `php artisan make:seeder LocationSeeder` を実行。
    - `LocationService` の `$townNames`, `$roadNames`, `$dungeonNames` をループし、`locations`テーブルにデータを挿入するロジックを記述。
  - [ ] `php artisan make:seeder ConnectionSeeder` を実行。
    - `LocationService` の `$townConnections`, `$roadBranches` をループし、`connections`テーブルにデータを挿入するロジックを記述。`slug`を元に`locations`テーブルからIDを検索して設定する。
  - [ ] `DatabaseSeeder.php` に `LocationSeeder` と `ConnectionSeeder` を登録。
  - [ ] `php artisan migrate:fresh --seed` を実行し、テーブル作成とデータ投入をテスト。

---

## Phase 2: サービス層のリファクタリングと動作保証

**目的:** `LocationService` のデータソースを配列からDBに切り替え、既存機能の完全な互換性を保証する。

- [ ] **`LocationService` のリファクタリング**
  - [ ] コンストラクタインジェクションで `Location` と `Connection` モデルを受け取るように変更。
  - [ ] ハードコードされた配列 (`$roadNames`, `$townNames`, `$dungeonNames`, `$roadBranches`, `$townConnections`) を**すべて削除**。
  - [ ] `getLocationName`, `getNextLocation`, `getBranchOptions` 等のメソッドを、Eloquentを使ったDBクエリに書き換える。
    - **注意:** パフォーマンスを考慮し、適切な `with()` を使ってEager Loadingを行う。
  - [ ] `setRoadName` や `setTownName` など、配列を直接操作していたメソッドは削除するか、DBを更新するように変更するかを決定。（管理画面に移行するため、基本的には削除を推奨）

- [ ] **動作確認とデグレッションテスト**
  - [ ] 既存のユニットテスト/フィーチャーテストがあれば、すべてパスすることを確認。
  - [ ] `LocationService` の変更に伴い、テストが失敗する場合はテストコードを修正。
  - [ ] **手動テスト:** ゲームの全ロケーションを巡り、以下の項目を重点的に確認。
    - [ ] 町から道への移動。
    - [ ] 道から町への移動。
    - [ ] T字路 (`road_2` の位置50) での分岐と移動。
    - [ ] 複数接続を持つ町 (`town_c`など) からの各方面への移動。
    - [ ] 表示されるロケーション名がすべて正しいこと。

---

## Phase 3: 管理画面 (Admin Panel) の実装

**目的:** 管理者が安全かつ直感的にロケーションと接続を管理できるUIを提供する。

- [ ] **コントローラとルーティング**
  - [ ] `php artisan make:controller Admin/LocationController --resource`
  - [ ] `php artisan make:controller Admin/ConnectionController --resource`
  - [ ] `routes/admin.php` (または `routes/web.php` の admin middleware group) に以下を追記:
    ```php
    Route::resource('locations', App\Http\Controllers\Admin\LocationController::class);
    Route.resource('connections', App\Http\Controllers\Admin\ConnectionController::class);
    ```

- [ ] **ロケーション管理画面 (CRUD)**
  - [ ] **一覧 (`index.blade.php`):** `id`, `slug`, `name`, `type` をテーブル表示。
  - [ ] **作成/編集フォーム (`form.blade.php`):** `name`, `slug`, `type`, `description` を入力可能に。`slug`はユニーク制約のバリデーションを追加。

- [ ] **接続管理画面 (CRUD)**
  - [ ] **一覧 (`index.blade.php`):** 接続情報をテーブル表示。「出発元 (from)」「到着先 (to)」「方向 (direction)」を`Location`モデルのリレーションを使って表示。
  - [ ] **作成/編集フォーム (`form.blade.php`):**
    - **出発元/到着先:** `locations`テーブルの全件を `<select>` ドロップダウンで表示。
    - **方向:** テキスト入力。バリデーションで特定のキーワード (`north`, `straight`等) を推奨。
    - **分岐点:** 数値入力。道路間の接続の場合のみ表示するなどのUI工夫を検討。

- [ ] **管理画面サイドメニューへの統合**
  - [ ] `resources/views/layouts/admin_sidebar.blade.php` (仮) などの共通パーツに、「ロケーション管理」「接続管理」へのリンクを追加。

- [ ] **管理画面のテスト**
  - [ ] `Admin/LocationControllerTest`, `Admin/ConnectionControllerTest` を作成。
  - [ ] 管理者権限がないユーザーがアクセスできないこと (403)。
  - [ ] バリデーションが機能すること（例: slugの重複、必須項目の未入力）。
  - [ ] 作成・更新・削除がDBに正しく反映されること。
