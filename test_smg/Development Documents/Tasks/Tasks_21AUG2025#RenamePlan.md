# 2025-08-21 テーブルリネーム計画: game_locations -> routes / location_connections -> route_connections

## 目的
DBテーブル名とドメイン/管理画面用語の不一致 (game_locations vs pathways) を解消し、名称を `routes` / `route_connections` に統一して認知コストを下げる。ただし既存の管理パネル構造 (roads / dungeons セクション分離) と `dungeons_desc` テーブルは維持する。

## スコープ
- 物理テーブル: `game_locations` -> `routes`
- 物理テーブル: `location_connections` -> `route_connections`
- 参照制約/外部キー/インデックスの更新
- Eloquent Model の `$table` 指定による影響吸収（初期段階ではクラス名 GameLocation / LocationConnection は据え置き可能）
- バリデーションルール / クエリビルダ / マイグレーション / シーディング / サービス層 / コマンド / Blade内の直接テーブル参照文字列置換
- Doctrine DBAL を用いた rename migration 追加

非スコープ:
- モデルクラス名 (`GameLocation` -> `Route` など) の即時変更（後続タスク候補）
- 管理パネルのナビゲーション構造 (roads / dungeons メニュー) 変更
- `dungeons_desc` の名称・構造変更

## 前提確認
| 項目 | 現状 | 影響 | 対応 |
|------|------|------|------|
| `GameLocation` モデル | `$table` 未指定 (デフォルト `game_locations`) | 物理名変更で解決必須 | `$table = 'routes'` を追加 |
| `LocationConnection` モデル | `$table` 未指定 (デフォルト `location_connections`) | 同上 | `$table = 'route_connections'` を追加 |
| FK: `location_connections.source_location_id` -> `game_locations.id` | あり | rename後無効 | 新 FK: `route_connections.source_location_id` -> `routes.id` |
| FK: `monster_spawn_lists.location_id` -> `game_locations.id` | あり | `routes` に更新 | マイグレーションで再作成 |
| FK: `game_locations.spawn_list_id` -> `spawn_lists.id` | テーブル側 rename のみ | カラムは保持 | 変更不要 |
| Validation: `unique:game_locations,id` / `exists:game_locations,id` | 多数 | テーブル名更新必要 | 文字列置換 |
| Migration 過去ファイル | 歴史保持 | 変更しない | 新しい rename migration を追加 |
| Raw SQL (Documents内) | 設計資料 | 実行対象外 | 更新は任意 (後続) |

## 影響箇所洗い出し (grep 結果から)
### 1. 直接テーブル文字列
- Validation ルール: `AdminRoadController`, `AdminDungeonController`, `AdminMonsterSpawnController` など
- コマンド: `MigrateSpawnListsCommand` (配列 `$requiredTables`, スキーマチェック)
- サービス: `MonsterConfigService`, `AdminLocationService` (明示 SQL は現状なし / Eloquent 経由)
- マイグレーション: 過去ファイルは変更しない (再実行時差分抑止) ※新規 DB 構築時は rename 前の create -> rename のフローになるが容認 (後述代替案あり)

### 2. 外部キー参照
- `location_connections` -> `game_locations`
- `monster_spawn_lists.location_id` -> `game_locations.id`
- その他 `dungeons_desc` は `dungeon_id` のみで `game_locations` に物理 FK 未設定 (現行ファイル確認要: 2025_08_20_095115_add_dungeon_id_to_game_locations_table.php にはFK追加なし)

### 3. モデル
- `GameLocation` に `$table` 追加
- `LocationConnection` に `$table` 追加
- 将来的にクラス名統一する場合の影響領域: Factory / Seeder / 型ヒント / DocBlock / テスト

### 4. シード / ファクトリ / テスト
- 現状 `tests/` 内で `game_locations` 明示の Factory 参照があるか後で確認 (今回計画書段階: 最終計画でチェックタスク化)

## リネーム戦略
### 選択肢比較
| 戦略 | 概要 | Pros | Cons |
|------|------|------|------|
| A: 直接 RENAME TABLE (推奨) | 1 つの rename migration で rename / FK 再構築 | シンプル / データ移動不要 | Past migration 再実行時に一時的重複 (初期セットアップが複雑) |
| B: 新規作成 + データコピー | `routes` 作成 → INSERT SELECT → FK 差替え → 旧テーブル削除 | 過去 migration 履歴と自然整合 | データコピーコスト / トランザクション管理必要 |

本計画ではダウンタイム最小 & 容易さから A を採用。新規セットアップ用 README に "artisan migrate 実行後 rename migration が最後に動くので問題なし" と注記。CI がクリーン DB を作る場合、`game_locations` -> `routes` rename が常に成立 (存在チェック) するよう `if (Schema::hasTable('game_locations'))` ガードを追加。

## 手順 (実運用)
1. 事前バックアップ: `php artisan db:backup --tables=game_locations,location_connections,monster_spawn_lists`
2. 新マイグレーション追加: `rename_game_locations_and_location_connections_tables`
   - RENAME TABLE `game_locations` -> `routes`
   - RENAME TABLE `location_connections` -> `route_connections`
   - 既存 FK ドロップ / 再作成
     - 先に `route_connections` 内の FK 名取得 (SQLite のため rename 後は実際は再作成が必要: SQLite は外部キー再構築方式。Laravel の Schema builder では create temp -> copy が裏で走る可能性) 
   - `monster_spawn_lists` の外部キー (存在する場合) 再設定: SQLite では実際の FK 名を drop できないため、`Schema::table` でカラム再定義 or 物理 FK 無し運用を継続 (現在 migration 上 `monster_spawn_lists` に FK 定義あり: `foreign('location_id')->references('id')->on('game_locations')`). → rename 後に参照先自動更新されないため一旦 `foreign` 再作成
3. モデルへ `$table` プロパティ追加 (`GameLocation` = routes, `LocationConnection` = route_connections)
4. Validation / 文字列: `unique:game_locations,id` → `unique:routes,id`; `exists:game_locations,id` → `exists:routes,id`
5. コマンド / サービス内の `$requiredTables` 等: `game_locations` -> `routes`
6. (任意) ドキュメント改訂: 設計資料中の `game_locations` / `location_connections` を脚注で新旧対応表追記
7. 動作確認
   - `php artisan migrate` (新 rename migration 適用確認)
   - 主要 CRUD (Road 作成 / Dungeon Floor 作成) 
   - Monster spawn 関連: 取得/保存/ランダム選択
   - LocationConnection を利用する画面/サービス (接続一覧ロード)
8. テスト修正 & 実行: `php artisan test`
9. リリース手順書更新: Rollback 手順 (down: RENAME 戻し)

## 変更詳細タスクリスト
### Migration 追加
- `database/migrations/2025_08_21_XXXXXX_rename_game_locations_tables.php`
  - up():
    - if (Schema::hasTable('game_locations')) { Schema::rename('game_locations', 'routes'); }
    - if (Schema::hasTable('location_connections')) { Schema::rename('location_connections', 'route_connections'); }
    - 必要に応じて FK 再構築 (SQLite: Laravel 11 以降 rename サポート確認; 問題時は B 戦略 fallback コメント記載)
  - down(): 逆 rename

### Models
- `app/Models/GameLocation.php`
  - 先頭付近に `protected $table = 'routes';`
- `app/Models/LocationConnection.php`
  - 先頭付近に `protected $table = 'route_connections';`

### Controllers / Validation
- `AdminRoadController`: `unique:game_locations,id` → `unique:routes,id`
- `AdminDungeonController`: 同上 (floors 作成)
- `AdminMonsterSpawnController`: `exists:game_locations,id` → `exists:routes,id`

### Commands
- `MigrateSpawnListsCommand`:
  - `$requiredTables` 配列: `'game_locations'` → `'routes'`
  - カラム存在チェック: `Schema::hasColumn('routes', 'spawn_tags')`
  - ログ/メッセージ文字列 (ユーザ向け表示) は "旧名(game_locations)" 註釈を一時残すか検討 (段階的移行)

### Services
- `AdminLocationService` / `MonsterConfigService` / `LocationService` 等は Eloquent モデル経由のため `$table` 追加で透明化。Raw クエリがないか最終 grep: `DB::table('game_locations'` を確認 (追加チェックタスク)。

### Blade / View
- 直接テーブル名指定なし (Validation 表示メッセージ内には露出しない)。不要。

### テスト & Factories
- grep: `game_locations` を tests ディレクトリで検索して修正。(実装フェーズで実行)

## リスクと緩和策
| リスク | 内容 | 緩和策 |
|--------|------|--------|
| Migration 失敗 (SQLite rename 制約) | 外部キーが原因で rename 失敗 | 事前に `foreign_keys=off` する Raw 実行 or 戦略B fallback コメント実装 |
| 過去 migration 再実行時 | 新規環境で create → rename で2段階になる | README に記述 / 追加の guard if hasTable |
| 他レイヤーでハードコーディング | 見落とし | grep で `game_locations` / `location_connections` を CI チェックに追加 |
| 将来モデル名変更 | 段階的に二重名称期間 | 別タスク化 (`GameLocation` -> `Route`) |

## 移行後フォローアップ (別チケット)
1. モデル名リファクタ (`GameLocation` -> `Route`, `LocationConnection` -> `RouteConnection`)
2. 命名統一: pathway -> route へのサービス層/ログ/DocBlock 用語整理
3. Admin UI 表示ラベル選択 (ユーザ向け文言は日本語 "道路" / "ダンジョン" 維持)
4. ドキュメント中の旧名マッピング表作成

## ロールバック手順
- 新 rename migration を down 実行 (`php artisan migrate:rollback --step=1`) で `routes` -> `game_locations` / `route_connections` -> `location_connections`
- モデル `$table` 設定を元に戻す (または一時コメントアウト)
- Validation / コマンドのテーブル文字列を旧名へ再置換

## 実装順序ガイド (まとめ)
1. Migration 作成
2. Models `$table` 追加
3. Validation ルール置換
4. コマンド `$requiredTables` 更新
5. grep 再確認 & テスト修正
6. migrate 実行検証 (ローカル)
7. テスト実行
8. コードレビュー / ドキュメント更新

## 確認コマンド (参考)
```
# 影響確認
grep -R "game_locations" -n app/ tests/ database/
grep -R "location_connections" -n app/ tests/ database/

# マイグレーション実行
php artisan migrate

# テスト
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

## 追加メモ
- `dungeons_desc` は維持 (依存関係なし)。
- Pathways という文言は段階的に route に変更するが UI 構造 (roads/dungeons) は維持。
- ネーミング差異による混乱を最小化するため 1 リリース内で物理テーブルのみ変更しアプリ層の概念名は後続段階で整理。

---
以上。実装フェーズで不明点が出た場合は本ファイルを更新。
