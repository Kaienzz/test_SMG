# GameLocations (Road/Dungeon) 管理機能のベストプラクティス案

## 1. 基本方針：単一の`GameLocation`モデルを活用し、UIとロジックを分離する

**現状の`game_locations`テーブルは、`category`カラムによって'road'や'dungeon'を区別できる優れた設計になっています。** この設計を最大限に活かし、データベースを変更することなく、管理機能のコントローラーとビューを分離する方針を取ります。

## 2. ベストプラクティス案

### Step 1: `GameLocation`モデルの確認とスコープの追加

まず、`game_locations`テーブルに対応する`app/Models/GameLocation.php`モデルを確認します。（もし存在しない場合は、`php artisan make:model GameLocation`で作成します）。

このモデルに、`category`カラムに基づいてデータを簡単に絞り込むための**ローカルスコープ**を定義します。

```php
// app/Models/GameLocation.php

class GameLocation extends Model
{
    // ... 既存のコード ...

    /**
     * 'road' カテゴリのみを取得するスコープ
     */
    public function scopeRoads($query)
    {
        return $query->where('category', 'road');
    }

    /**
     * 'dungeon' カテゴリのみを取得するスコープ
     */
    public function scopeDungeons($query)
    {
        return $query->where('category', 'dungeon');
    }

    /**
     * ダンジョンフロア（子ロケーション）を取得するリレーション
     * 'location_connections'テーブルを介して、親子関係を表現する
     */
    public function childLocations()
    {
        // 'location_connections'テーブルの構造に依存する。
        // 例: return $this->hasManyThrough(GameLocation::class, LocationConnection::class, 'parent_id', 'id', 'id', 'child_id');
        // 上記は仮の実装です。実際のテーブル定義に合わせる必要があります。
        return $this->hasMany(LocationConnection::class, 'location_a_id'); // 仮
    }
}
```

### Step 2: 管理機能ごとにコントローラーを分割する

管理の利便性を考え、「Road管理」と「Dungeon管理」でコントローラーを明確に分けます。

1.  **`AdminRoadController` の作成:**
    -   責務: `category`が`road`のGameLocationのCRUDを担当。
    -   `index`メソッドでは、Step 1で定義したスコープを使います。

    ```php
    // app/Http/Controllers/Admin/AdminRoadController.php
    use App\Models\GameLocation;

    class AdminRoadController extends Controller
    {
        public function index()
        {
            $roads = GameLocation::roads()->latest()->paginate(20);
            return view('admin.roads.index', compact('roads'));
        }
        // store/update時には 'category' => 'road' をセット
    }
    ```

2.  **`AdminDungeonController` の作成:**
    -   責務: `category`が`dungeon`のGameLocationの管理を担当。

    ```php
    // app/Http/Controllers/Admin/AdminDungeonController.php
    use App\Models\GameLocation;

    class AdminDungeonController extends Controller
    {
        public function index()
        {
            // 'dungeon'カテゴリの場所を、関連する子ロケーションと共に取得
            $dungeons = GameLocation::dungeons()->with('childLocations')->latest()->paginate(20);
            return view('admin.dungeons.index', compact('dungeons'));
        }
        // store/update時には 'category' => 'dungeon' をセット
    }
    ```

### Step 3. 管理画面の具体的な設計案

#### A. Road管理画面 (`/admin/roads`)

-   **URL**: `/admin/roads`
-   **目的**: `category`が`road`の`game_locations`レコードを管理する。
-   **表示項目**: ID, 名前, 説明, 長さ, 難易度など。
-   **操作**: 新規作成, 編集, 削除。

#### B. Dungeon管理画面 (`/admin/dungeons`)

-   **URL**: `/admin/dungeons`
-   **目的**: ダンジョン（`category`='dungeon'の場所）と、それに紐づくフロア（接続された他の場所）を一覧で管理する。
-   **表示項目**:
    -   テーブル形式でダンジョンを一覧表示。
    -   **トグル機能**: 各行にトグルボタン（例: `+` や `▼`）を設置。クリックすると、そのダンジョンに接続された場所（フロア）の一覧がインラインで表示されます。（JavaScript/Alpine.js等で実装）
    -   **親階層カラム**: ダンジョンID, ダンジョン名, 説明, **フロア数**（`childLocations`のカウント）。
    -   **トグルで表示される子階層**:
        -   接続先の場所ID, 名前, タイプなど。
-   **操作**:
    -   **ダンジョンの新規作成・編集・削除**
    -   **フロアの管理**: 子階層に表示された各フロアの接続を編集・解除する機能。

### Step 4. 管理メニューへの反映

管理画面のサイドバーなどに「Road管理」「Dungeon管理」のリンクを追加します。

1.  **ルート定義の確認:**
    `routes/admin.php` (または相当するファイル) に、`AdminRoadController`と`AdminDungeonController`へのルートを定義します。
    ```php
    // routes/admin.php
    Route::resource('roads', AdminRoadController::class)->names('admin.roads');
    Route::resource('dungeons', AdminDungeonController::class)->names('admin.dungeons');
    ```

2.  **レイアウトファイルの編集:**
    管理画面の共通レイアウトファイル（例: `resources/views/layouts/admin.blade.php`）のサイドバー部分に、以下のリンクを追加します。
    ```html
    <!-- 例: サイドバーのナビゲーション -->
    <nav>
        <ul>
            <!-- ... 他メニュー ... -->
            <li>
                <a href="{{ route('admin.roads.index') }}">Road管理</a>
            </li>
            <li>
                <a href="{{ route('admin.dungeons.index') }}">Dungeon管理</a>
            </li>
            <!-- ... 他メニュー ... -->
        </ul>
    </nav>
    ```
