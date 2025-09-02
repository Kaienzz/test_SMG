# 調合機能 追加タスクと技術設計（2025-09-01）

## 要約
- 目的: 各町の「調合店」でレシピに基づきアイテムを作成する“調合”機能を追加する。
- スコープ: DB・ドメインサービス・施設・UI・管理画面・権限・テスト一式の初期実装（Phase1）。
- 既存連携: TownFacility（施設）基盤／Inventory（所持品）／Skill（スキル）／Player（SP・スキル）に統合。

---

## 要件チェックリスト（依頼内容の対応）
- [x] 調合には「調合スキル」が必要（スキルカテゴリは“生産”）
- [x] 調合は各町にある「調合店」で実行
- [x] 町により調合できるアイテム（レシピ）は異なる
- [x] 材料アイテムを消費して成果物アイテムを作成（例: 薬草×3 → ポーション×1）
- [x] レシピごとに必要スキルレベル（未達は実行不可）
- [x] 調合時にSPを消費（Phase1は1回あたりSP 15固定／レシピ毎に上書き可）
- [x] レシピ成功率（既定100%、一部は確率で成功）
- [x] 調合成功時、調合スキルに経験値（Phase1は+100固定）
- [x] 今後、難易度（必要レベル）とスキルレベル差で獲得経験値が逓減する仕組みを入れられる設計
- [x] DBテーブル追加の検討（レシピ・材料・町割当）
- [x] 管理画面（レシピCRUD・材料設定・町割当）
- [x] ゲームロジックの調整（施設・在庫・SP・確率・経験値）

---

## 前提／用語
- 施設基盤: `town_facilities` + `FacilityType` + `BaseFacilityController` + `FacilityServiceFactory`
- 在庫: `inventories.slot_data`（スタック可／満杯判定あり）
- スキル: `skills`（`skill_type`,`skill_name`,`level`など）。既存例として movement/gathering 等
- 町: ルートテーブル `routes` の `category = 'town'`

---

## 全体像（アーキテクチャ）
1) 新施設タイプ「調合店」を追加（FacilityType）。
2) 調合店サービス（CompoundingFacilityService）が現在地の町で利用可能なレシピを返す／調合を実行。
3) 調合はトランザクションで実行：条件チェック → SP・材料消費 → 成功判定 → 成果物付与 → スキル経験値付与。
4) レシピはDBで定義し、町ごとに有効化。成功率・必要レベル・SP・成果物・材料を柔軟に設定。
5) 管理UIでレシピと町割当を運用。

---

## DB 設計（新規）
Phase1ではアイテムIDに `items.id` を使用（標準アイテムは必要に応じて items に同期登録）。

1) compounding_recipes（調合レシピ定義）
- id: PK
- recipe_key: 文字列（ユニーク識別子）
- name: 表示名（例: ポーション調合）
- product_item_id: 作成される `items.id`
- product_quantity: 作成個数（int, default 1）
- required_skill_level: 必要スキルレベル（int, default 1）
- success_rate: 成功率（0-100, default 100）
- sp_cost: 調合SPコスト（int, default 15）
- base_exp: 基本取得EXP（int, default 100）
- notes: メモ（text, nullable）
- is_active: boolean（default true）
- timestamps

Index/制約:
- unique(recipe_key)
- index(product_item_id), index(is_active), index(required_skill_level)

2) compounding_recipe_ingredients（レシピ材料）
- id: PK
- recipe_id: FK → compounding_recipes.id
- item_id: 材料 `items.id`
- quantity: 必要数（int, >=1）
- timestamps

制約:
- unique(recipe_id, item_id)
- FK(recipe_id) ON DELETE CASCADE, FK(item_id) …

3) compounding_recipe_locations（レシピの町割当）
- id: PK
- recipe_id: FK → compounding_recipes.id
- location_id: 町の `routes.id`（category='town'）
- is_active: boolean（default true）
- timestamps

制約/Index:
- unique(recipe_id, location_id)
- index(location_id), index(is_active)

将来拡張:
- 施設単位での割当（`town_facilities.id`）が必要になれば列追加で対応可。

---

## スキル設計
- skill_type: 'production'（生産）を新設。
- skill_name: '調合' を利用。
- 利用条件: 調合実行時、プレイヤーが 'production/調合' を所持している必要。
  - 未所持なら: 調合店で初回入店時にレベル1で自動習得、またはメッセージで未習得を通知（Phase1は自動習得を推奨）。
- EXP: Phase1は成功時に base_exp（既定100）を付与。
- 将来の逓減式: 例）exp = clamp(20, 100, round(100 * min(1, required_skill_level / max(1, skill_level))))
  - スキルLv >> 必要Lv の簡単レシピほど 100 → 20 へ逓減
  - 具体式は Phase2 で確定。Phase1 は100固定で実装し、関数フックを用意。

---

## 施設/サービス/コントローラ（新規）
- COMPOUNDING_SHOP = 'compounding_shop'
  - 表示名: 調合店
  - 説明: レシピに基づき材料からアイテムを調合します。
  - Icon: ⚗️
  - Controller: CompoundingFacilityController
  - ViewPrefix: facilities.compounding

2) CompoundingFacilityService（AbstractFacilityService を継承）
- getAvailableServices: 現在の町で作成可能なレシピ一覧（成功率/必要Lv/SP/必要材料/成果物）を返す
- processTransaction(TownFacility $facility, Player $player, array $data): array
  - 入力: { recipe_id:int, quantity:int>=1 }
  - 手順:
    1. 現在地が town であること確認
    2. レシピActivate・町割当の確認
    3. プレイヤーが 'production/調合' を保持（未所持ならエラー or 自動付与）
    4. スキルLv >= required_skill_level
    5. 在庫に材料が揃っているか（スタック集計）
    6. SPチェック（quantity回分、基本は 15×quantity）→ 消費
    7. DBトランザクション開始
       - 材料消費（在庫から数量分を減算）
       - 成功判定（レシピの success_rate。quantity毎に判定 or まとめて回数実施）
       - 成功分の成果物を在庫へ追加（空き枠・スタック考慮）
       - 成功回数×base_exp を 調合スキルに付与（Phase1固定100/回）
       - 失敗は材料・SPのみ消費（成果物なし）
    8. コミット
  - 出力: {success, message, crafted:{product_item, success_count, fail_count}, exp_gain, sp_spent, inventory_state（任意）}
- validateTransactionData: recipe_idの存在/quantity範囲チェック

3) CompoundingFacilityController（BaseFacilityController 継承）
- index: 調合店画面（レシピ一覧・材料所持数・成功率・SP/必要Lv）
- processTransaction: POST /facility/compounding/craft
  - バリデーション: recipe_id 必須、quantity 1..N

4) FacilityServiceFactory へ登録
 - FacilityType::COMPOUNDING_SHOP → new CompoundingFacilityService()
- Controller の factory参照 or ルート直指定

---

## プレイヤーUI（施設画面）
- レシピ一覧（町で有効なもののみ）
  - 各レシピ: アイコン/名前/成果物×個数/成功率/必要Lv/必要材料（所持数表示）/SPコスト
  - quantity 入力（初期1）
  - 実行ボタン（不足があれば disable + ツールチップ）
- 結果表示: 成功/失敗内訳、生成個数、消費SP、付与EXP、在庫更新サマリ
- プレイヤーバー: HP/MP/SP、スキル『調合』のLv/EXPバー（任意）

---

## 管理画面（新規）
権限（例）: compounding.view / compounding.edit / compounding.delete

1) レシピ管理（CRUD）
- 一覧: 検索（名前/キー/成果物）、並び替え（必要Lv/成功率 等）、有効/無効切替
- 作成/編集: 上記カラム + 材料リスト（行追加UI）
- 確率/必要Lv/SP/EXPの設定欄を含む

2) 町割当（レシピ×町）
- レシピ詳細で町チェックボックス／あるいは町側でレシピアサイン
- 一括ON/OFF、複製（他町へコピーレシピ）

3) ダッシュボード（任意）
- レシピ数/町別有効レシピ数/直近実行回数 等の簡易統計

---

## ルーティング
- 施設（ゲーム側）
  - GET /facility/compounding → 調合店トップ
  - POST /facility/compounding/craft → 調合実行
- 管理（例）
  - GET /admin/compounding/recipes
  - GET /admin/compounding/recipes/create
  - POST /admin/compounding/recipes
  - GET /admin/compounding/recipes/{id}/edit
  - PUT /admin/compounding/recipes/{id}
  - DELETE /admin/compounding/recipes/{id}
  - GET /admin/compounding/recipes/{id}/locations
  - POST /admin/compounding/recipes/{id}/locations (町割当更新)

---

## ゲームロジック詳細
- 成功判定: `mt_rand(1,100) <= success_rate`（quantity回繰り返し）
- 材料消費: quantity 倍の必要数を全体から差し引き（スロット跨ぎ対応）
- 在庫満杯: 作成前に空き/スタック余地を試算。足りなければ実行不可（Phase1）
- SP消費: `recipe.sp_cost * quantity`（既定15）。不足で実行不可
- スキルEXP: 成功回数 × recipe.base_exp（Phase1=100）。Phase2で逓減式に差し替え
- ロールバック: 失敗時は該当ループ分の材料/SPのみ消費（商品なし）
- トランザクション: 全体実行を1トランザクション。大量quantity時は適宜バッチ化検討

---

## マイグレーション＆シーディング（Phase1）
1) 3テーブル（recipes/ingredients/locations）作成
2) サンプル投入
- itemsに『薬草』『ポーション』が存在しない場合は作成
- compounding_recipes: `potion_basic` → product: ポーション×1, success_rate:100, required_skill_level:1, sp_cost:15, base_exp:100
- compounding_recipe_ingredients: （potion_basic, 薬草, 3）
- compounding_recipe_locations: （potion_basic, 'town_prima' など）

---

## 実装ステップ（作業手順）
- Step 1: DBマイグレーション3本追加 + サンプルシーダ
- Step 2: FacilityType へ COMPOUNDING_SHOP 追加（表示名/説明/アイコン/ビュー）
- Step 3: FacilityServiceFactory に CompoundingFacilityService を登録
- Step 4: CompoundingFacilityService 実装（条件チェック/在庫/確率/SP/EXP/Tx）
- Step 5: CompoundingFacilityController 実装 + ルート追加
- Step 6: ビュー（`resources/views/facilities/compounding/index.blade.php`）作成
- Step 7: スキル 'production/調合' 自動習得導線（初回入店時）
- Step 8: 管理UI（レシピCRUD・材料UI・町割当）と権限
- Step 9: 結合テスト/Featureテスト
- Step 10: ドキュメント更新（README/管理手順）

---

## テスト計画（最小）
- Service: 材料不足/在庫満杯/SP不足/必要Lv未達/成功・失敗の内訳/複数回quantity
- Controller: バリデーション/成功レスポンス/エラーレスポンス
- 在庫: スタック増減・複数スロット跨ぎ消費
- スキル: EXP付与・レベルアップ境界（将来式用のフック）
- 施設: 町割当が無い場合の非表示

---

## エッジケース/リスク
- 在庫満杯時の一部成功扱い（Phase1は実行不可に統一）
- 並行実行で材料競合 → Tx + 再読込で整合
- 標準アイテム定義（standard_items）とitemsテーブルの多重定義の整合性
- ALCHEMY_SHOP（錬金:装備強化）との名称混同 → 調合店は COMPOUNDING_SHOP として明確化

---

## 将来拡張
- 経験値逓減の本実装（必要LvとスキルLv比で係数）
- レシピ発見/解放クエスト

---

## 変更が必要な主なファイル（予定）
- app/Enums/FacilityType.php（COMPOUNDING_SHOP 追加）
- app/Services/FacilityServiceFactory.php（COMPOUNDING登録）
- app/Services/CompoundingFacilityService.php（新規）
- app/Http/Controllers/CompoundingFacilityController.php（新規）
- resources/views/facilities/compounding/index.blade.php（新規）
- database/migrations/***（3テーブル）
- database/seeders/***（サンプルレシピ/アイテム）
- app/Models（必要ならCompoundingRecipe系モデル）
- 管理画面一式（/resources/views/admin/compounding/**、/app/Http/Controllers/Admin/**）

---

## メモ（実装方針のポイント）
- Phase1は「固定EXP=100」「固定SP=15（レシピで上書き可）」でシンプルに成立させ、式を差し替え可能な関数境界を用意
- 町割当は routes.id ベース。施設IDベースの要望が出たら列追加で対応
- 権限は locations.* に倣い compounding.* を用意（view/edit/delete）
