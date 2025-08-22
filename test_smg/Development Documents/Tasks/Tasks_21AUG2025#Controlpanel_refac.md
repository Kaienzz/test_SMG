# Admin管理画面リファクタリング計画

## 1. 現状の問題点 (Issues)

現在のAdmin管理画面、特にロケーション管理機能は、度重なる仕様変更により多くの技術的負債を抱えています。

- **責務の肥大化 (Bloated Controller)**
  - `AdminLocationController` が道、町、ダンジョン、接続関係、インポート/エクスポート、バックアップなど、多数の責務を負っており、単一のコントローラーとしては過度に複雑化しています。これにより、可読性やメンテナンス性が著しく低下しています。

- **新旧仕様の混在 (Mixed Specifications)**
  - **テーブル/モデル**: `game_locations` テーブル（旧仕様）から `routes` テーブル（新仕様）への移行が行われましたが、コードベースに古いモデル (`GameLocation`) への参照が残っている可能性があります。
  - **ルーティング**: `routes/admin.php` 内で、古い仕様（`locations/roads`, `locations/dungeons`）と新しい仕様（`pathways`, `resource('roads', ...)`）が混在しています。「後方互換性」のためのルートが多数存在し、混乱を招いています。

- **命名の不統一 (Inconsistent Naming)**
  - `pathway`, `road`, `dungeon`, `location` といった用語が、同じ、あるいは類似の概念に対して統一されずに使われており、コードの意図を理解するのを困難にしています。

- **一貫性の欠如 (Lack of Consistency)**
  - 新しい `Route::resource` ベースのルートと、古い手動でのルート定義 (`Route::get`, `Route::post`) が混在しており、URL設計やコントローラーの実装に一貫性がありません。

## 2. リファクタリングの提案 (Suggestions)

上記の課題を解決するため、以下のリファクタリングを提案します。

1.  **責務の分割 (Separation of Concerns)**
    - `AdminLocationController` を廃止し、責務を以下のコントローラーに分割します。
      - `AdminRoadController`: 道の管理
      - `AdminDungeonController`: ダンジョンの管理
      - `AdminTownController`: 町の管理 **(新規作成)**
      - `AdminRouteConnectionController`: ロケーション間の接続管理 **(新規作成)**

2.  **旧仕様の完全な廃止 (Deprecate Old Specifications)**
    - `game_locations` テーブルと `GameLocation` モデルへの参照をコードベースから完全に削除します。
    - `routes/admin.php` から「後方互換性」とされているルート定義をすべて削除し、新しいコントローラーベースのルートに一本化します。

3.  **ルーティングの統一 (Unify Routing)**
    - 新しく作成・整理するすべてのコントローラーに対して `Route::resource` を使用し、RESTfulなルート設計に統一します。これにより、ルート定義が簡潔になり、予測可能性が高まります。

4.  **命名規則の統一 (Unify Naming Convention)**
    - `pathway` という曖昧な表現を廃止し、`road` または `dungeon` に明確に分類します。
    - モデルは `Route` に統一し、その `category` プロパティ（`road`, `town`, `dungeon`）で種類を判別する設計を徹底します。

## 3. タスクリスト (Tasks)

### Step 1: 基盤整理

-   [ ] **Task 1.1: [DB/Model] `GameLocation` モデルと関連コードの削除**
    -   [ ] `app/Models/GameLocation.php` を削除する。
    -   [ ] プロジェクト全体から `GameLocation` への参照を検索し、すべて `Route` モデルを使用するように修正する。（例: `MonsterConfigService`）
    -   [ ] `game_locations` テーブルを削除するマイグレーションを作成し、実行する。

### Step 2: コントローラーの分割と実装

-   [ ] **Task 2.1: [Controller] `AdminTownController` の作成**
    -   [ ] `php artisan make:controller Admin/AdminTownController --resource` コマンドでコントローラーとビューの雛形を作成する。
    -   [ ] `Route::resource('towns', AdminTownController::class)` を `routes/admin.php` に追加する。
    -   [ ] `AdminLocationController` から町のCRUD（作成、表示、編集、更新、削除）機能を移植する。

-   [ ] **Task 2.2: [Controller] `AdminRouteConnectionController` の作成**
    -   [ ] `php artisan make:controller Admin/AdminRouteConnectionController` でコントローラーを作成する。
    -   [ ] ロケーション間の接続を管理するUIとロジックを実装する。（一覧表示、新規作成、削除）
    -   [ ] `routes/admin.php` に接続管理用のルートを定義する。

-   [ ] **Task 2.3: [Refactoring] `AdminRoadController` と `AdminDungeonController` のリファクタリング**
    -   [ ] `AdminLocationController` から、それぞれのコントローラーに関連するロジック（保存、更新、削除など）を完全に移植する。
    -   [ ] `Route::resource` で提供されるアクション（`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`）に沿ってメソッドを整理する。

### Step 3: クリーンアップ

-   [ ] **Task 3.1: [Routing] `routes/admin.php` のクリーンアップ**
    -   [ ] `AdminLocationController` に関連するすべてのルート（`locations`, `pathways`, 後方互換性ルート）を削除する。
    -   [ ] 新しいリソースベースのルートのみが残るように整理する。

-   [ ] **Task 3.2: [View] Bladeファイルの整理**
    -   [ ] `resources/views/admin/locations` ディレクトリを廃止する。
    -   [ ] `resources/views/admin/roads`, `resources/views/admin/towns`, `resources/views/admin/dungeons`, `resources/views/admin/routesconnections` のように、コントローラーに対応したディレクトリ構成に再編・整理する。

-   [ ] **Task 3.3: [Controller] `AdminLocationController` の廃止**
    -   [ ] すべての機能が新しいコントローラーに移管されたことを確認した後、`app/Http/Controllers/Admin/AdminLocationController.php` ファイルを削除する。
