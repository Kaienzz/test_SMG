# JSONファイル依存の調査レポート (2025/08/20)

## 調査目的

アイテム、モンスター、ロケーションなどのマスターデータをJSONファイル管理からSQLiteデータベース管理へ移行した。
本調査では、プロジェクト内に古いJSONファイルへの参照が残っていないか、データベースへの移行が完全に完了しているかを確認する。

## 調査結果サマリー

**結論として、StandardItem（標準アイテム）のデータ取得処理が依然としてJSONファイルに依存しており、データベースへの完全な移行は完了していません。**

- **要修正箇所:** `app/Services/StandardItem/StandardItemService.php` が `storage/app/data/standard_items.json` を直接読み込んでいる。
- **関連する不要な設定:** `config/items.php` に上記JSONへのパスが定義されている。
- **潜在的な問題箇所:** `app/Http/Controllers/Admin/AdminLocationControllerOld.php` に古いJSONインポート/エクスポート機能が残っている。
- **問題なしと判断した箇所:**
    - API通信におけるJSON形式の利用 (`response()->json()` など)。
    - データベースのJSON型カラムの利用 (`$table->json(...)`)。
    - `backup/` ディレクトリ以下の古いファイル。

---

## 詳細分析

### 1. マスターデータとしてのJSONファイル読み込み（要対応）

以下のファイルで、マスターデータとしてJSONファイルを直接読み込んでいる箇所が確認された。

- **`app/Services/StandardItem/StandardItemService.php`**
  - **内容:** `config('items.json_file_path')` を通じて `storage/app/data/standard_items.json` のパスを取得し、`Storage::get()` でファイル内容を読み込み、`json_decode()` でパースしている。
  - **問題点:** アイテムデータの取得がデータベースではなく、JSONファイルに直接依存している。
  - **推奨対応:** このサービスの処理を、DBの `standard_items` テーブルを検索してデータを返すように全面的に書き換える。

- **`config/items.php`**
  - **内容:** `'json_file_path' => 'data/standard_items.json'` という設定が存在する。
  - **問題点:** 上記 `StandardItemService` から参照されている。
  - **推奨対応:** `StandardItemService` の修正完了後、この行を削除する。

- **`app/Http/Controllers/Admin/AdminLocationControllerOld.php`**
  - **内容:** ロケーション設定をJSONファイルとしてエクスポート、またはJSONファイルからインポートする管理機能が実装されている。
  - **問題点:** このコントローラーが現在も有効な場合、意図せず古いJSONベースの操作が行われる可能性がある。
  - **推奨対応:** `routes/admin.php` などを確認し、このコントローラーへのルーティングが存在するか調査する。
    - **ルーティングが存在しない場合:** この古いコントローラーは削除する。
    - **ルーティングが存在する場合:** データベースを直接操作するようにリファクタリングする。

### 2. API通信でのJSON利用（問題なし）

- **`public/js/` 以下のJSファイル**
- **`app/Http/Controllers/` 以下の多数のコントローラー**
  - **内容:** `response()->json()`, `response.json()`, `Content-Type: application/json` といった記述が多数見られる。
  - **評価:** これらはフロントエンドとバックエンドが非同期通信を行うための正常な実装であり、今回のデータベース移行の趣旨とは無関係。問題なし。

### 3. データベースのJSON型カラム（問題なし）

- **`database/migrations/` 以下の多数のマイグレーションファイル**
  - **内容:** `$table->json('effects')` のように、データベースのテーブル定義でJSON型カラムが多用されている。
  - **評価:** アイテムの効果や各種設定など、柔軟なデータ構造を持つデータを効率的に格納するための正しいアプローチ。データベースへの移行が適切に行われている証拠であり、問題なし。

---

## 結論と次のステップ

**最優先で `app/Services/StandardItem/StandardItemService.php` の改修が必要です。**

1.  **[最優先]** `StandardItemService` を、JSONファイルではなく `standard_items` テーブルからデータを取得するように修正する。
2.  上記修正後、`config/items.php` から不要な設定を削除する。
3.  `AdminLocationControllerOld.php` の要不要を判断し、適切に処理（削除またはリファクタリング）する。

以上の対応が完了すれば、マスターデータのデータベースへの完全移行が達成される。
