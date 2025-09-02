# DungeonDesc 主従管理 実装タスクリスト（2025-08-30）

このドキュメントは「親=dungeons_desc、子=routes(ダンジョンフロア)」で効果的に管理するための実装タスクをまとめたものです。

---

## ゴール／要求仕様
- 親テーブル`dungeons_desc`はダンジョン名・詳細などメタ情報を管理する
- `routes`テーブルの`category='dungeon'`なレコードは、`dungeon_id`で`dungeons_desc`と紐づく（子フロア）
- 管理画面は「親（dungeons_desc）一覧/詳細」を基点に、配下フロア（routes）を子要素として閲覧・操作できる
- オーファン（親未紐づけ/親未作成）データの検出・解消導線を提供する

---

## 現状サマリ（確認済み）
- モデル/リレーション
  - Route::belongsTo(DungeonDesc, 'dungeon_id', 'dungeon_id')
  - DungeonDesc::hasMany(Route, 'dungeon_id', 'dungeon_id')->where('category','dungeon') as floors
- 管理画面
  - 親一覧(index)/詳細(show)/編集(edit)・フロア一覧(floors)/フロア作成(create-floor)は実装済み
  - 親削除は「子のdungeon_idをnullにする」＝子は残す（文言要調整）
- 課題
  - オーファン（dungeon_idがnull、または親の無いdungeon_id）の救済UIが未整備
  - 親一覧はアクティブのみ（非アクティブ表示切替なし）
  - 一覧上部の統計はページング後コレクションでの集計（全体像とズレ）

---

## タスク一覧（チェックリスト）

### A. 文言・表示ポリシー調整（小タスク）
- [ ] 親削除時の確認文言を実装に合わせて修正（「子フロアは削除されず、dungeon_idがnullになります」）
  - 対象: `resources/views/admin/dungeons/index.blade.php` の削除確認、`show.blade.php` の危険操作説明
- [ ] 親一覧に「非アクティブを含める」トグル（クエリパラメータ）を追加
  - Controller: `AdminDungeonController@index`（scopeActiveの適用を切替）
  - View: `index.blade.php` にトグルUI、現在件数の表示を全体集計に変更
- [ ] 子フロア表示のフィルタ（アクティブのみ/全件）の簡易トグルを追加（任意）

### B. オーファン（孤立）救済導線の追加（中タスク）
- [ ] 親詳細/フロア管理画面に「既存フロアをこの親にアタッチ」フォームを追加
  - GET: 検索（id/name部分一致、`category='dungeon'`）+ 絞り（`dungeon_id is null`、または他親dungeon_id）
  - POST: 選択IDの `routes.dungeon_id = <親のdungeon_id>` を一括更新
  - Controller: `AdminDungeonController` に `attachFloorsForm`(GET), `attachFloors`(POST) を追加 or 小規模なら `floors`/`storeFloor` に統合
  - View: `floors.blade.php` にモーダル/セクションでフォームを追加
  - Validation/権限/監査ログの付与
- [ ] オーファン整理ページの追加（一覧→一括処理）
  - 画面要件:
    - リスト1: `category='dungeon' AND dungeon_id is null`
    - リスト2: `routes.dungeon_id` が存在するが `dungeons_desc` に該当`dungeon_id` が無い（親不在）
    - アクション: 既存親に紐づけ / 新規親作成+一括紐づけ
  - 追加ルート例: `/admin/dungeons/orphans`（GET/POST）
  - Controller: `AdminDungeonOrphanController` 新設 or `AdminDungeonController` に集約
  - View: `resources/views/admin/dungeons/orphans.blade.php`

### C. DungeonDesc 管理画面の拡充（中タスク）
- [ ] 親の編集フォームに「非アクティブ切替」の説明文を追記（非表示基準の明確化）
- [ ] 親一覧に簡易検索（name/id 部分一致）を追加

### D. ルーティング・権限・監査（小〜中タスク）
- [ ] ルート追加: `dungeons.attach-floors`（GET/POST）、`dungeons.orphans`（GET/POST）
- [ ] 権限: `locations.edit`でアタッチ操作許可、閲覧は`locations.view`
- [ ] 監査ログ: attach/cleanup/parent-createを記録

### E. バックエンド（サービス/バリデーション）（小〜中タスク）
- [ ] サービス層（任意）: `App\Services\Admin\DungeonService` を新設し、
  - フロア検索/一括アタッチ/孤立検出を集約
- [ ] FormRequest: `AttachFloorsRequest` を作成（ID配列のバリデーション、存在性、カテゴリ=ダンジョン）

### F. キャッシュ/整合性（小タスク）
- [ ] 親（作成/更新/削除）とフロア（アタッチ/デタッチ/作成）操作後に関連キャッシュを無効化
  - 現状のキャッシュ利用箇所を確認（Townは実装あり）。ルートグラフ描画用のキャッシュがあれば同様に対応

### G. データユーティリティ（任意）
- [ ] Consoleコマンドの強化: `admin:analyze-pathways-data` の結果から、修正スクリプトを提案/半自動化
- [ ] Seeder: `DungeonDescSeeder`（サンプル）を用意、開発・検証用の親データ投入

### H. テスト（推奨）
- [ ] Feature: 親作成/表示/編集/削除（デタッチ動作のDB検証含む）
- [ ] Feature: フロア作成（子として作成）
- [ ] Feature: フロア一括アタッチ（選択→更新→結果検証）
- [ ] Feature: オーファン一覧→既存親に紐づけ/新規親作成+一括紐づけ
- [ ] AuthZ: `locations.view`/`locations.edit`/`locations.delete` のガード動作

### I. UI/UX 微調整（任意）
- [ ] 親一覧の行展開（子フロア表示）の性能改善（大量時はページング/読み込み遅延）
- [ ] 検索UIの入力補助（`dungeon_id`サフィックス、例: `_1f/_b1` などのヒント）

---

## 実装詳細メモ
- 削除文言の整合:
  - 現実装: 親削除時に `Route::where('dungeon_id', $parent->dungeon_id)->update(['dungeon_id'=>null])` → 子は残る
  - 文言修正箇所: `resources/views/admin/dungeons/index.blade.php` / `show.blade.php`
- 検索/アタッチのクエリ例:
  - 候補検索: `Route::dungeons()->when($onlyOrphan, fn($q)=>$q->whereNull('dungeon_id'))->when($q, name/id検索).paginate()`
  - 一括更新: `Route::whereIn('id', $ids)->update(['dungeon_id' => $parentDungeonId])`

---

## 変更対象ファイル（予定）
- コントローラ
  - `app/Http/Controllers/Admin/AdminDungeonController.php`（attach系を入れる場合）
  - もしくは `app/Http/Controllers/Admin/AdminDungeonOrphanController.php`（新設）
- ルート
  - `routes/admin.php`（新規ルート追加）
- ビュー
  - `resources/views/admin/dungeons/index.blade.php`
  - `resources/views/admin/dungeons/show.blade.php`
  - `resources/views/admin/dungeons/floors.blade.php`
  - `resources/views/admin/dungeons/attach-floors.blade.php`（新設・任意）
  - `resources/views/admin/dungeons/orphans.blade.php`（新設）
- サービス/リクエスト
  - `app/Services/Admin/DungeonService.php`（新設・任意）
  - `app/Http/Requests/Admin/AttachFloorsRequest.php`（新設）
- テスト
  - `tests/Feature/Admin/Dungeons/*`

---

## 受け入れ基準（Acceptance Criteria）
- 親一覧に非アクティブ表示トグルがあり、切り替えが動作する
- 親詳細/フロア管理から既存フロアを検索し、複数選択で親にアタッチできる
- オーファン一覧で、親の無いフロアを把握し、既存親への紐づけ/新規親作成+一括紐づけができる
- 親削除時、子フロアは残り、`dungeon_id`がnull化される（文言も一致）
- 一覧の統計が全体集計に基づき、ページングに左右されない
- 権限/監査ログが既存方針に沿って記録される
- 基本機能のFeatureテストがグリーン

---

## ロールアウト手順（推奨）
1. ブランチ作成（feature/admin-dungeons-parent-child）
2. A→B→C→D→E→F→H の順で小さくPRを分割
3. ステージングでデータ量の多いケースを確認（オーファン数、一覧展開性能）
4. 本番適用、バックアップ + ロールバック手順の用意

---

