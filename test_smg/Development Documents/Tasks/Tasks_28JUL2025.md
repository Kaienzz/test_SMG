# Tasks_28JUL2025.md - Character→Player移行の完全修正タスク

## 概要
CharacterモデルからPlayerモデルへの移行が完了していますが、コードベース全体に古いCharacter参照が残存しており、これらを完全に修正する必要があります。

## 発見された修正ポイント

### 1. 【高優先】ビューファイル内のCharacter参照修正

#### 1.1 ゲーム関連ビュー
- **`resources/views/game/partials/location_info.blade.php:71`**
  ```php
  $gatheringSkill = $character->getSkill('採集');
  ```
  → `$player->getSkill('採集');` に修正

#### 1.2 ショップ関連ビュー
- **`resources/views/shop/index.blade.php:20`**
  ```php
  {{ $character->gold ?? 1000 }}G
  ```
  → `{{ $player->gold ?? 1000 }}G` に修正

- **`resources/views/shops/base/index.blade.php:23`**
  ```php
  {{ $character->gold ?? 1000 }}G
  ```
  → `{{ $player->gold ?? 1000 }}G` に修正

- **`resources/views/shops/tavern/index.blade.php`** (複数箇所)
  - 15行目: `{{ $character->hp ?? 100 }}`
  - 19行目: `{{ ($character->hp ?? 100) / ($character->max_hp ?? 100) * 100 }}%`
  - 26行目: `{{ $character->mp ?? 50 }}`
  - 30行目: `{{ ($character->mp ?? 50) / ($character->max_mp ?? 50) * 100 }}%`
  - 37行目: `{{ $character->sp ?? 100 }}`
  - 41行目: `{{ ($character->sp ?? 100) / ($character->max_sp ?? 100) * 100 }}%`
  - 132-137行目: JavaScriptオブジェクト内の全てのcharacterプロパティ
  
  → 全て`$player`に修正

#### 1.3 キャラクター/プレイヤー表示ページ
- **`resources/views/character/index.blade.php:349`**
- **`resources/views/player/index.blade.php:349`**
  ```php
  {{ isset($player) ? $player->name : $character->name }}
  ```
  → `{{ $player->name }}` に統一（$playerが必ず存在するため）

### 2. 【高優先】コントローラー内のCharacter参照修正

#### 2.1 BaseShopController
- **`app/Http/Controllers/BaseShopController.php`** (複数箇所)
  - 28行目: `$character = $user->getOrCreateCharacter();`
  - 30-51行目: 全ての`$character`使用箇所
  - 69-85行目: 全ての`$character`使用箇所
  
  → 全て`getOrCreatePlayer()`と`$player`に修正

#### 2.2 EquipmentController
- **`app/Http/Controllers/EquipmentController.php`** (複数箇所)
  - 94-98行目, 132-133行目, 200-201行目
  
  → 全て`getOrCreatePlayer()`と`$player`に修正

#### 2.3 ShopController
- **`app/Http/Controllers/ShopController.php`** (複数箇所)
  - 33行目以降の全ての`$character`使用箇所
  
  → 全て`getOrCreatePlayer()`と`$player`に修正

#### 2.4 SkillController
- **`app/Http/Controllers/SkillController.php`** (複数箇所)
  - 53行目以降の全ての`$character`使用箇所
  
  → 全て`getOrCreatePlayer()`と`$player`に修正

#### 2.5 GatheringController
- **`app/Http/Controllers/GatheringController.php`** (複数箇所)
  - 15行目以降の全ての`$character`使用箇所
  
  → 全て`getOrCreatePlayer()`と`$player`に修正

#### 2.6 AuthenticatedSessionController
- **`app/Http/Controllers/Auth/AuthenticatedSessionController.php:47`**
  ```php
  if ($character && $character->location_type === 'battle') {
  ```
  → Playerモデルに対応する修正が必要

### 3. 【中優先】モデル関連の参照修正

#### 3.1 既存のCharacterモデル依存
以下のモデルでCharacterモデルを参照している箇所：
- `app/Models/ActiveBattle.php`
- `app/Models/Skill.php`
- `app/Models/Inventory.php`
- `app/Models/Equipment.php`
- `app/Models/User.php`
- `app/Models/GameState.php`
- `app/Models/Items/EquippableItem.php`
- `app/Models/ActiveEffect.php`

これらのモデルでPlayerモデルとの互換性確認と修正が必要。

### 4. 【中優先】サービス層の修正

#### 4.1 ショップサービス
- `app/Services/TavernService.php`
- `app/Services/ItemShopService.php`
- `app/Services/BlacksmithService.php`
- `app/Services/AbstractShopService.php`

#### 4.2 バトルサービス
- `app/Services/BattleService.php`
- `app/Application/Services/BattleStateManager.php`

### 5. 【低優先】その他のファイル

#### 5.1 DTOクラス
- `app/Application/DTOs/GameViewData.php`
- `app/Application/DTOs/BattleData.php`
- `app/Application/DTOs/BattleResult.php`

#### 5.2 ドメインサービス
- `app/Domain/Character/` 配下のサービス群
- `app/Domain/Player/` 配下のサービス群との統合

## 修正作業の優先順位

### Phase 1: 緊急修正（即座に実施）
1. ビューファイル内の`$character`を`$player`に全て置換
2. 主要コントローラーの`getOrCreateCharacter()`を`getOrCreatePlayer()`に修正

### Phase 2: 基盤修正（1-2日以内）
1. ショップ関連コントローラーの完全修正
2. スキル・装備関連コントローラーの完全修正
3. 認証系の修正

### Phase 3: システム整合性確保（3-5日以内）
1. モデル間のリレーション修正
2. サービス層の修正
3. DTO層の修正

### Phase 4: 最終検証（6-7日以内）
1. 全機能のテスト実行
2. 残存する古いCharacter参照の完全削除
3. ドキュメント更新

## 注意事項

1. **後方互換性**: `HasCharacter` Traitは既に`getOrCreateCharacter()`が`getOrCreatePlayer()`を呼ぶよう修正済み
2. **データベース**: マイグレーションは既に完了しているため、データ層の修正は不要
3. **テスト**: 各修正後に該当機能のテストを実行すること
4. **段階的修正**: 全ての修正を一度に行わず、機能単位で段階的に実施すること

## 期待される効果

- Character/Player混在による実行時エラーの完全解消
- システム全体の一貫性確保
- 保守性の向上
- 新機能開発時の混乱防止

## 完了条件

- [ ] 全ビューファイルでcharacter変数が0件
- [ ] 全コントローラーでgetOrCreateCharacter()使用が0件
- [ ] 全機能が正常動作することを確認
- [ ] Lintチェック・型チェックがすべてパス