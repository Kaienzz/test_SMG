# データベース設計ドキュメント

## 概要

このドキュメントは、ゲームシステムのデータベース設計を詳細に説明します。RPGスタイルのウェブゲームを前提とし、ユーザー管理、キャラクター管理、バトルシステム、インベントリシステム、ショップシステムなどの機能をサポートするよう設計されています。

## データベース構成

### ユーザー関連テーブル

#### users テーブル
ユーザーアカウント情報を管理する基本テーブル。

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    last_active_at TIMESTAMP NULL,
    last_device_type VARCHAR(255) NULL,
    last_ip_address VARCHAR(255) NULL,
    session_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**主な機能：**
- 基本的な認証機能（Laravel Breeze/Fortify準拠）
- マルチデバイスサポート（last_device_type, last_ip_address）
- セッション管理（session_data）
- クロスデバイス同期対応

#### password_reset_tokens テーブル
パスワードリセット機能用テーブル。

```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);
```

#### sessions テーブル
Laravel標準のセッション管理テーブル。

```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX(user_id),
    INDEX(last_activity)
);
```

### キャラクター関連テーブル

#### characters テーブル
ゲーム内キャラクターの基本ステータスとゲーム進行状況を管理。

```sql
CREATE TABLE characters (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) DEFAULT '冒険者',
    
    -- 基本ステータス
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    experience_to_next INT DEFAULT 100,
    
    -- 戦闘ステータス
    attack INT DEFAULT 10,
    defense INT DEFAULT 8,
    agility INT DEFAULT 12,
    evasion INT DEFAULT 15,
    magic_attack INT DEFAULT 8,
    accuracy INT DEFAULT 85,
    
    -- HP/MP/SPシステム
    hp INT DEFAULT 100,
    max_hp INT DEFAULT 100,
    sp INT DEFAULT 30,
    max_sp INT DEFAULT 30,
    mp INT DEFAULT 20,
    max_mp INT DEFAULT 20,
    
    -- ゲーム進行状況
    location_type VARCHAR(255) DEFAULT 'town',
    location_id VARCHAR(255) DEFAULT 'town_a',
    game_position INT DEFAULT 0,
    last_visited_town VARCHAR(255) DEFAULT 'town_a',
    
    -- ベースステータス（装備・スキル効果適用前）
    base_attack INT NULL,
    base_defense INT NULL,
    base_agility INT NULL,
    base_evasion INT NULL,
    base_max_hp INT NULL,
    base_max_sp INT NULL,
    base_max_mp INT NULL,
    base_magic_attack INT NULL,
    base_accuracy INT NULL,
    
    -- リソース
    gold INT DEFAULT 1000,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- 外部キー制約
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY(user_id)  -- 1ユーザー1キャラクター
);
```

**特徴：**
- **1ユーザー1キャラクター**システム
- スキルベースのレベルシステム（総スキルレベル÷10+1）
- ベースステータスとボーナスステータスの分離
- ゲーム内位置情報の詳細管理

### スキル関連テーブル

#### skills テーブル
キャラクターのスキル情報を管理。

```sql
CREATE TABLE skills (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    character_id BIGINT UNSIGNED NOT NULL,
    skill_type VARCHAR(255) DEFAULT 'combat',  -- combat, movement, gathering, magic, utility
    skill_name VARCHAR(255) NOT NULL,
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    effects JSON NULL,                         -- スキル効果の詳細
    sp_cost INT DEFAULT 10,
    duration INT DEFAULT 5,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- 外部キー制約とインデックス
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    INDEX(character_id, skill_name),
    INDEX(skill_type)
);
```

**スキルシステム設計：**
- **5つのスキルタイプ**：combat, movement, gathering, magic, utility
- スキルレベル向上によるボーナス効果
- JSON形式でのスキル効果管理

#### active_effects テーブル
一時的なバフ・デバフ効果を管理。

```sql
CREATE TABLE active_effects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    character_id BIGINT UNSIGNED NOT NULL,
    effect_type VARCHAR(255) NOT NULL,
    effect_value JSON NULL,
    duration INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);
```

### インベントリシステム

#### inventories テーブル
キャラクターのインベントリ情報を管理。

```sql
CREATE TABLE inventories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    character_id BIGINT UNSIGNED NOT NULL,
    slot_data JSON DEFAULT '[]',               -- スロット別アイテムデータ
    max_slots INT DEFAULT 10,                  -- 最大スロット数
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- 外部キー制約
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    UNIQUE KEY(character_id),                  -- 1キャラクター1インベントリ
    INDEX(character_id)
);
```

**インベントリ設計：**
- JSON形式でのスロット管理
- スタッキング対応
- 耐久度システム対応
- 拡張可能なスロット数

#### items テーブル
ゲーム内アイテムのマスターデータ。

```sql
CREATE TABLE items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(255) NOT NULL,            -- weapon, armor, accessory, consumable, material
    stack_limit INT NULL,                      -- スタック上限（NULL=スタック不可）
    max_durability INT NULL,                   -- 最大耐久度
    effects JSON NULL,                         -- アイテム効果
    rarity INT DEFAULT 1,                      -- レアリティ（1-6）
    value INT DEFAULT 0,                       -- 基本価値
    sell_price INT NULL,                       -- 売却価格
    battle_skill_id VARCHAR(255) NULL,         -- 関連スキルID
    weapon_type VARCHAR(255) NULL,             -- 武器タイプ
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 装備システム

#### equipment テーブル
キャラクターの装備情報を管理。

```sql
CREATE TABLE equipment (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    character_id BIGINT UNSIGNED NOT NULL,
    weapon_id BIGINT UNSIGNED NULL,            -- 武器
    body_armor_id BIGINT UNSIGNED NULL,        -- 体防具
    shield_id BIGINT UNSIGNED NULL,            -- 盾
    helmet_id BIGINT UNSIGNED NULL,            -- 兜
    boots_id BIGINT UNSIGNED NULL,             -- 靴
    accessory_id BIGINT UNSIGNED NULL,         -- アクセサリー
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- 外部キー制約
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (weapon_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (body_armor_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (shield_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (helmet_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (boots_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (accessory_id) REFERENCES items(id) ON DELETE SET NULL
);
```

### バトルシステム

#### active_battles テーブル
進行中のバトル状況を管理。

```sql
CREATE TABLE active_battles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    battle_id VARCHAR(255) UNIQUE NOT NULL,    -- ユニークなバトルID
    character_data JSON NOT NULL,             -- バトル開始時のキャラクターデータ
    monster_data JSON NOT NULL,               -- モンスターデータ
    battle_log JSON DEFAULT '[]',             -- バトルログ
    turn INT DEFAULT 1,                       -- 現在のターン数
    location VARCHAR(255) NULL,               -- バトル発生場所
    status VARCHAR(255) DEFAULT 'active',     -- active, paused, completed
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- 外部キー制約とインデックス
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id)
);
```

#### battle_logs テーブル
完了したバトルの履歴を保存。

```sql
CREATE TABLE battle_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    monster_name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    result ENUM('victory', 'defeat', 'escaped') NOT NULL,
    experience_gained INT DEFAULT 0,
    gold_lost INT DEFAULT 0,
    turns INT DEFAULT 1,
    battle_data JSON NULL,                     -- 詳細な戦闘データ
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- 外部キー制約とインデックス
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id, created_at),
    INDEX(result)
);
```

### ショップシステム

#### shops テーブル
ゲーム内ショップの情報を管理。

```sql
CREATE TABLE shops (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    shop_type VARCHAR(255) NOT NULL,           -- weapon, armor, item, magic
    location_id VARCHAR(255) NOT NULL,
    location_type VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT NULL,
    shop_config JSON NULL,                     -- ショップ固有の設定
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- 複合ユニークキー
    UNIQUE KEY(location_id, location_type, shop_type)
);
```

#### shop_items テーブル
ショップで販売されるアイテムの情報。

```sql
CREATE TABLE shop_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    price INT NOT NULL,
    stock INT DEFAULT -1,                      -- -1は無限在庫
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- 外部キー制約
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    UNIQUE KEY(shop_id, item_id)
);
```

### システム管理テーブル

#### cache テーブル
Laravel標準のキャッシュシステム。

```sql
CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
);
```

#### jobs テーブル
Laravel標準のジョブキューシステム。

```sql
CREATE TABLE jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    
    INDEX(queue)
);
```

## モデル間の関係性

### 主要なリレーションシップ

#### User ↔ Character (1:1)
```php
// User.php
public function character(): HasOne
{
    return $this->hasOne(Character::class);
}

// Character.php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

#### Character → その他のエンティティ (1:多または1:1)
```php
// Character.php
public function inventory(): HasOne
{
    return $this->hasOne(Inventory::class);
}

public function equipment(): HasOne
{
    return $this->hasOne(Equipment::class);
}

public function skills(): HasMany
{
    return $this->hasMany(Skill::class);
}

public function battleLogs(): HasMany
{
    return $this->hasMany(BattleLog::class, 'user_id', 'user_id');
}
```

#### Equipment → Items (多:1)
```php
// Equipment.php
public function weapon(): BelongsTo
{
    return $this->belongsTo(Item::class, 'weapon_id');
}

public function bodyArmor(): BelongsTo
{
    return $this->belongsTo(Item::class, 'body_armor_id');
}
// 他の装備部位も同様...
```

## ゲームシステムの設計思想

### 1. スキルベースレベルシステム
- キャラクターレベル = (総スキルレベル ÷ 10) + 1
- スキル使用によりスキル経験値獲得
- スキルレベル向上がステータスボーナスに直結

### 2. 柔軟なインベントリシステム
- JSON形式でのスロットデータ管理
- スタッキング対応（アイテムの`stack_limit`に基づく）
- 耐久度システム対応
- アイテム詳細情報のキャッシュ

### 3. マルチデバイス対応
- セッション情報の詳細管理
- デバイス間でのゲーム状態同期
- クロスプラットフォーム対応

### 4. 拡張性の考慮
- JSON形式での効果・設定管理
- モジュラーなシステム設計
- 将来的な機能追加に対応

## パフォーマンス最適化

### インデックス戦略
- 頻繁にアクセスされる外部キー
- バトルログの日時検索
- ショップアイテムの検索最適化

### キャッシュ戦略
- スキルボーナス計算結果のキャッシュ
- アイテム情報のキャッシュ
- Laravelの標準キャッシュシステム活用

## セキュリティ考慮事項

### データ整合性
- 外部キー制約によるデータ整合性保証
- カスケード削除によるクリーンアップ
- ユニーク制約による重複防止

### アクセス制御
- ユーザー認証システム（Laravel Breeze/Fortify）
- キャラクターとユーザーの1:1関係による所有権管理
- セッション管理による不正アクセス防止

## 今後の拡張計画

### 想定される機能追加
1. **ギルドシステム** - 新しいテーブル群の追加
2. **クエストシステム** - quest テーブルと進行状況管理
3. **PvPシステム** - プレイヤー間バトルの仕組み
4. **イベントシステム** - 期間限定イベントの管理
5. **マーケットシステム** - プレイヤー間取引

### スケーラビリティ対応
- 大量データに対応したパーティショニング
- 読み取り専用レプリカの活用
- キャッシュ層の拡張

このデータベース設計は、現在の機能を適切にサポートしつつ、将来的な拡張に対しても柔軟に対応できるよう設計されています。