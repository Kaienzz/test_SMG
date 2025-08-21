# Admin管理画面リファクタリング計画 (2025/08/20)

## 1. はじめに

本ドキュメントは、Admin管理画面の現状を分析し、保守性と拡張性を向上させるためのリファクタリング計画を定義するものである。
仕様変更の積み重ねにより、特にロケーション管理機能において、ルート定義の複雑化、不要なコードの残存、コントローラーの肥大化といった問題が確認された。

## 2. 現状分析と問題点

### 2.1. 問題点サマリー

- **ロケーション管理のルートが複雑すぎる:** `pathways`, `roads`, `dungeons`, `towns` のルートが混在し、見通しが悪い。
- **古いコントローラーが残っている:** `AdminLocationControllerOld.php` がプロジェクト内に存在するが、ルーティングからは参照されていない。
- **ルート定義にクロージャが多用されている:** 単純なビューを返すためだけにクロージャが使われており、将来的な機能追加の際に管理が煩雑になる可能性がある。
- **コントローラーの責務が肥大化している:** `AdminLocationController` がロケーションに関するほぼ全ての操作を担当しており、単一責任の原則に反している。

### 2.2. 詳細分析

#### (1) ルーティングの複雑化（特にロケーション管理）

`routes/admin.php` を調査した結果、ロケーション管理に関連するルートが後方互換性のために複数存在し、非常に複雑になっている。

- **`pathways`**: 新しい統合管理ルート
- **`roads`**: 後方互換性のための古いルート
- **`dungeons`**: 後方互換性のための古いルート
- **`towns`**: 町管理用のルート

これに伴い、対応するコントローラー (`AdminLocationController`) 内にも `pathwayForm`, `roadForm`, `dungeonForm`, `savePathway`, `saveRoad` のような類似メソッドが多数存在し、コードの重複とメンテナンスコストの増大を招いている。

#### (2) 不要なレガシーコードの残存

`app/Http/Controllers/Admin/` ディレクトリ内に `AdminLocationControllerOld.php` が存在する。
`routes/admin.php` を調査したところ、このコントローラーへのルーティングは存在しない。これはJSONベースの旧仕様の名残であり、現在は使用されていない不要なファイルである可能性が極めて高い。

#### (3) クロージャルートの乱立

以下のルートがコントローラーを介さず、クロージャで直接ビューを返している。

- `/players`
- `/shops`
- `/analytics`
- `/audit`
- `/system/config`
- `/roles`

現状は単純な表示のみだが、将来的にこれらのページでデータ操作や複雑なロジックが必要になった場合、ルートファイルが肥大化し、テストも困難になる。

## 3. リファクタリングに向けたタスクリスト

### 【フェーズ1】基盤整理と不要コードの削除

-   [ ] **Task 1-1: 不要なコントローラーの削除**
    -   `app/Http/Controllers/Admin/AdminLocationControllerOld.php` を削除する。
    -   削除前に、念のためプロジェクト全体で参照がないことを再度確認する。
    -   **具体例:**
        ```bash
        # プロジェクトルートで以下のコマンドを実行する
        rm "test_smg/app/Http/Controllers/Admin/AdminLocationControllerOld.php"
        ```

-   [ ] **Task 1-2: ロケーション管理ルートの統合**
    -   `routes/admin.php` を編集し、`roads` と `dungeons` に関連する後方互換ルートを全て削除する。
    -   `pathways` と `towns` のルートに機能を統一する。`towns` も将来的には `pathways` の一種として統合可能か検討する。
    -   **具体例:**
        -   **Before (`routes/admin.php`):**
            ```php
            // ...
            // 道路管理（後方互換性）
            Route::get('/locations/roads', [AdminLocationController::class, 'roads'])->name('locations.roads');
            Route::get('/locations/roads/create', [AdminLocationController::class, 'roadForm'])->name('locations.roads.create');
            Route::get('/locations/roads/{roadId}', [AdminLocationController::class, 'roadForm'])->name('locations.roads.edit');
            // ...
            // ダンジョン管理（後方互換性）
            Route::get('/locations/dungeons', [AdminLocationController::class, 'dungeons'])->name('locations.dungeons');
            Route::get('/locations/dungeons/create', [AdminLocationController::class, 'dungeonForm'])->name('locations.dungeons.create');
            Route::get('/locations/dungeons/{dungeonId}', [AdminLocationController::class, 'dungeonForm'])->name('locations.dungeons.edit');
            // ...
            ```
        -   **After (`routes/admin.php`):**
            ```php
            // 上記の '道路管理' と 'ダンジョン管理' のルート定義を完全に削除する。
            // 'pathways' 関連のルートに統一されていることを確認する。
            ```

-   [ ] **Task 1-3: `AdminLocationController` のスリム化**
    -   Task 1-2で削除したルートに対応するメソッド（`roadForm`, `dungeonForm`, `saveRoad`, `deleteRoad` など）を `AdminLocationController.php` から削除する。
    -   **具体例:**
        -   **Before (`AdminLocationController.php`):**
            ```php
            class AdminLocationController extends AdminController
            {
                // ...
                public function roads(Request $request) { /* ... */ }
                public function roadForm(Request $request, $roadId = null) { /* ... */ }
                public function saveRoad(Request $request) { /* ... */ }
                public function deleteRoad(Request $request, $roadId) { /* ... */ }
                public function dungeons(Request $request) { /* ... */ }
                public function dungeonForm(Request $request, $dungeonId = null) { /* ... */ }
                // ...
            }
            ```
        -   **After (`AdminLocationController.php`):**
            ```php
            class AdminLocationController extends AdminController
            {
                // roadXXX, dungeonXXX といったメソッドが削除されている状態
                // ...
                public function pathways(Request $request) { /* ... */ }
                public function pathwayForm(Request $request, $pathwayId = null) { /* ... */ }
                // ...
            }
            ```

### 【フェーズ2】コントローラーの責務分離

-   [ ] **Task 2-1: クロージャルートのコントローラー化**
    -   `/players`, `/shops`, `/analytics` など、クロージャで定義されているルートをそれぞれ専用のコントローラーに分離する。
    -   例: `AdminPlayerController`, `AdminShopController` を作成し、`index` メソッドでビューを返すように変更する。
    -   **具体例 (`/players` の場合):**
        1.  **コントローラーを作成:**
            ```bash
            php artisan make:controller Admin/AdminPlayerController
            ```
        2.  **`AdminPlayerController.php` を編集:**
            ```php
            <?php
            namespace App\Http\Controllers\Admin;

            use App\Http\Controllers\Controller;
            use Illuminate\Http\Request;

            class AdminPlayerController extends Controller
            {
                public function index()
                {
                    return view('admin.players.index', [
                        'breadcrumb' => [
                            ['title' => 'ダッシュボード', 'url' => route('admin.dashboard'), 'active' => false],
                            ['title' => 'プレイヤー管理', 'active' => true]
                        ]
                    ]);
                }
            }
            ```
        3.  **`routes/admin.php` を修正:**
            -   **Before:**
                ```php
                use App\Http\Controllers\Admin\AdminUserController;
                // ...
                Route::get('/players', function () {
                    return view('admin.players.index', [
                        'breadcrumb' => [
                            ['title' => 'ダッシュボード', 'url' => route('admin.dashboard'), 'active' => false],
                            ['title' => 'プレイヤー管理', 'active' => true]
                        ]
                    ]);
                })->name('players.index');
                ```
            -   **After:**
                ```php
                use App\Http\Controllers\Admin\AdminUserController;
                use App\Http\Controllers\Admin\AdminPlayerController; // 追加
                // ...
                Route::get('/players', [AdminPlayerController::class, 'index'])->name('players.index');
                ```

-   [ ] **Task 2-2: `AdminLocationController` の責務分割（推奨）**
    -   `AdminLocationController` が持つ機能（一覧表示, 作成, 編集, 接続管理, インポート/エクスポート）を、より小さなコントローラーやサービスクラスに分割することを検討する。
    -   例: `LocationConnectionController`, `LocationImportExportController` など。
    -   **具体例 (インポート/エクスポート機能の分離):**
        1.  **新しいコントローラーを作成:** `AdminLocationImportExportController`
        2.  **`AdminLocationController` からメソッドを移動:** `exportConfig`, `importConfig` などを新しいコントローラーに移動する。
        3.  **`routes/admin.php` を修正:**
            -   **Before:**
                ```php
                Route::get('/locations/export', [AdminLocationController::class, 'exportConfig'])->name('locations.export');
                Route::post('/locations/import', [AdminLocationController::class, 'importConfig'])->name('locations.import');
                ```
            -   **After:**
                ```php
                use App\Http\Controllers\Admin\AdminLocationImportExportController; // 追加

                Route::get('/locations/export', [AdminLocationImportExportController::class, 'export'])->name('locations.export');
                Route::post('/locations/import', [AdminLocationImportExportController::class, 'import'])->name('locations.import');
                ```

### 【フェーズ3】データソースの完全移行（関連タスク）

-   [ ] **Task 3-1: `StandardItemService` の改修**
    -   以前の調査で判明した `app/Services/StandardItem/StandardItemService.php` のJSONファイル依存を解消する。
    -   `standard_items` については、DBからデータを取得するように処理を全面的に書き換える。
    -   **具体例:**
        -   **Before (`StandardItemService.php`):**
            ```php
            class StandardItemService
            {
                // ...
                public function __construct()
                {
                    // ...
                    $this->jsonFilePath = config('items.json_file_path', 'data/standard_items.json');
                }

                public function getStandardItems(): array
                {
                    try {
                        $items = StandardItem::where('is_standard', true)->get()->toArray();
                        if (!empty($items)) {
                            return $items;
                        }
                    } catch (\Exception $e) {
                        // ... JSONフォールバック処理 ...
                    }
                    // ... JSONを読み込む処理 ...
                }
            }
            ```
        -   **After (`StandardItemService.php`):**
            ```php
            class StandardItemService
            {
                public function __construct()
                {
                    // JSON関連のプロパティと初期化を削除
                }

                public function getStandardItems(): array
                {
                    // 例外処理は上位の呼び出し元に任せるか、ここで適切に処理する
                    return StandardItem::where('is_standard', true)
                                       ->orderBy('category')
                                       ->orderBy('id')
                                       ->get()
                                       ->keyBy('id')
                                       ->toArray();
                    // JSONへのフォールバックロジックを完全に削除
                }
                // findByIdなども同様にJSONフォールバックを削除
            }
            ```

-   [ ] **Task 3-2: 不要なコンフィグの削除**
    -   Task 3-1の完了後、`config/items.php` に残っている `json_file_path` の設定を削除する。
    -   **具体例:**
        -   **Before (`config/items.php`):**
            ```php
            <?php
            return [
                // ...
                'json_file_path' => 'data/standard_items.json',
                // ...
            ];
            ```
        -   **After (`config/items.php`):**
            ```php
            <?php
            return [
                // 'json_file_path' の行を削除
                // ...
            ];
            ```

### 【フェーズ4】命名規則の統一と明確化（可読性向上）

ロケーション管理機能において、`Pathway` という抽象的な英語と、「道路」「ダンジョン」といった具体的な日本語が混在し、直感的な理解を妨げている。本フェーズでは、命名規則を統一し、コードの可読性と保守性を向上させる。

-   [ ] **Task 4-1: `Pathway` から `Location` への概念統一**
    -   **課題:** `Pathway` という用語が抽象的で、`Road` や `Dungeon` との関係性がコード上から読み取りにくい。
    -   **改善策:** より広範で理解しやすい `Location` に用語を統一する。「Location = 場所（道路、ダンジョン、町など）」として扱うことで、直感的な理解を促進する。

-   [ ] **Task 4-2: ルート定義の明確化**
    -   **課題:** `locations.pathways` のようなルート名は、`pathways` が何を指すのか不明確。
    -   **改善策:** `locations.index` や `locations.form` のように、RESTfulな命名規則に沿って、そのルートが実行する「アクション」を明確にする。
    -   **具体例 (`routes/admin.php`):**
        -   **Before:**
            ```php
            // 道路・ダンジョン統合管理
            Route::get('/locations/pathways', [AdminLocationController::class, 'pathways'])->name('locations.pathways');
            Route::get('/locations/pathways/create', [AdminLocationController::class, 'pathwayForm'])->name('locations.pathways.create');
            Route::get('/locations/pathways/{pathwayId}/edit', [AdminLocationController::class, 'pathwayForm'])->name('locations.pathways.edit');
            ```
        -   **After:**
            ```php
            // ロケーション統合管理 (用語をLocationに統一)
            // Route::resourceを使い、よりシンプルに定義することを推奨
            Route::get('/locations', [AdminLocationController::class, 'index'])->name('locations.index');
            Route::get('/locations/create', [AdminLocationController::class, 'form'])->name('locations.create');
            Route::get('/locations/{location}/edit', [AdminLocationController::class, 'form'])->name('locations.edit');
            Route::post('/locations', [AdminLocationController::class, 'store'])->name('locations.store');
            Route::put('/locations/{location}', [AdminLocationController::class, 'update'])->name('locations.update');
            ```

-   [ ] **Task 4-3: コントローラーのメソッド名と変数名の改善**
    -   **課題:** `pathwayForm`, `savePathway`, `$pathwayId` といった名前は、`Pathway` という曖昧な概念に依存している。
    -   **改善策:** メソッド名は `index`, `form`, `store`, `update` のようなアクションベースの名前に変更。変数は `$pathwayId` から `$location` (モデルバインディングを利用) や `$locationId` に変更する。
    -   **具体例 (`AdminLocationController.php`):**
        -   **Before:**
            ```php
            class AdminLocationController extends AdminController
            {
                public function pathways(Request $request) { /* ... */ }
                public function pathwayForm(Request $request, $pathwayId = null) { /* ... */ }
                public function savePathway(Request $request, $pathwayId = null) { /* ... */ }
            }
            ```
        -   **After (RESTfulな設計):**
            ```php
            // App\Models\Location モデルが存在すると仮定
            use App\Models\Location;

            class AdminLocationController extends AdminController
            {
                // pathways -> index
                public function index(Request $request) { /* 一覧表示ロジック */ }

                // pathwayForm -> form (作成・編集フォーム)
                // Eloquentのモデルバインディングを活用
                public function form(Request $request, Location $location = null) { /* ... */ }

                // savePathway -> store (新規保存)
                public function store(Request $request) { /* ... */ }

                // savePathway -> update (更新)
                public function update(Request $request, Location $location) { /* ... */ }
            }
            ```

以上のタスクを完了することで、Admin管理画面はよりクリーンで保守しやすい構造になる。
