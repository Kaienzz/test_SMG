# データベース設計書
# test_smg データベース設計仕様書

## ドキュメント情報

**プロジェクト名**: test_smg (Simple Management Game)  
**作成日**: 2025年7月25日  
**版数**: Version 1.0  
**対象**: 開発チーム、DBA、保守担当者  

---

## 1. データベース設計概要

### 1.1 設計思想

test_smgのデータベース設計は、以下の設計原則に基づいて構築されています：

#### 核となる設計原則
1. **正規化**: 第3正規形を基本とし、データ冗長性を最小化
2. **1ユーザー1キャラクター**: シンプルなユーザー・キャラクター関係
3. **JSON活用**: 柔軟性が必要な箇所でJSONカラムを効果的活用
4. **拡張性**: 将来の機能追加を考慮した設計
5. **整合性**: 外部キー制約による厳密な整合性維持

### 1.2 データベース構成図

```
┌─────────────────────────────────────────────────────────┐
│                     User Management                      │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐  │
│  │    users    │  │ password_    │  │    sessions     │  │
│  │    (認証)   │  │ reset_tokens │  │   (セッション)  │  │
│  └─────────────┘  └──────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────┘
                              │ 1:1
                              ▼
┌─────────────────────────────────────────────────────────┐
│                   Character System                      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │ characters  │  │   skills    │  │ active_effects  │  │
│  │  (中心エンティティ)│  │   (1:多)  │  │   (一時効果)    │  │
│  └─────────────┘  └─────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────┘
           │ 1:1              │ 1:1              │ 1:多
           ▼                  ▼                  ▼
┌─────────────────────────────────────────────────────────┐
│               Item & Equipment System                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │inventories  │  │ equipment   │  │     items       │  │
│  │  (JSON管理)  │  │  (装備状態)  │  │  (マスターデータ)  │  │
│  └─────────────┘  └─────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────┐
│                   Battle System                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │active_battles│  │battle_logs  │  │  [monsters]     │  │
│  │ (進行中戦闘) │  │  (戦闘履歴)  │  │  (将来拡張)     │  │
│  └─────────────┘  └─────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────┐
│                     Shop System                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │    shops    │  │ shop_items  │  │    [経済]       │  │
│  │  (ショップ)  │  │  (商品管理)  │  │  (将来拡張)     │  │
│  └─────────────┘  └─────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## 2. テーブル設計詳細

### 2.1 ユーザー管理系テーブル

#### users テーブル
**責務**: ユーザー認証・セッション管理・デバイス情報管理

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'ユーザー表示名',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT 'ログイン用メールアドレス',
    email_verified_at TIMESTAMP NULL COMMENT 'メール認証完了日時',
    password VARCHAR(255) NOT NULL COMMENT 'ハッシュ化パスワード',
    remember_token VARCHAR(100) NULL COMMENT 'ログイン状態維持用トークン',
    
    -- セッション・デバイス管理
    last_active_at TIMESTAMP NULL COMMENT '最終アクティブ日時',
    last_device_type VARCHAR(255) NULL COMMENT '最終使用デバイス種別',
    last_ip_address VARCHAR(255) NULL COMMENT '最終アクセスIPアドレス',
    session_data JSON NULL COMMENT 'セッション同期データ',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_last_active (last_active_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ユーザー認証・セッション管理テーブル';
```

**重要な設計決定**:
- `session_data`: マルチデバイス同期のためのJSON データ
- `last_device_type`, `last_ip_address`: セキュリティ監視・デバイス切り替え検出
- Laravel Breeze標準に準拠した認証カラム構成

### 2.2 キャラクター管理系テーブル

#### characters テーブル  
**責務**: ゲーム内キャラクター情報・ステータス・位置情報管理

```sql
CREATE TABLE characters (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '所有者ユーザーID',
    name VARCHAR(255) DEFAULT '冒険者' COMMENT 'キャラクター名',
    
    -- レベル・経験値システム
    level INT DEFAULT 1 COMMENT 'キャラクターレベル(スキル総計ベース)',
    experience INT DEFAULT 0 COMMENT '戦闘経験値',
    
    -- 戦闘ステータス
    attack INT DEFAULT 10 COMMENT '攻撃力',
    defense INT DEFAULT 8 COMMENT '防御力', 
    agility INT DEFAULT 12 COMMENT '素早さ',
    evasion INT DEFAULT 15 COMMENT '回避力',
    magic_attack INT DEFAULT 8 COMMENT '魔法攻撃力',
    accuracy INT DEFAULT 85 COMMENT '命中力',
    
    -- リソース管理（HP/MP/SP）
    hp INT DEFAULT 100 COMMENT '現在HP',
    max_hp INT DEFAULT 100 COMMENT '最大HP',
    mp INT DEFAULT 20 COMMENT '現在MP',
    max_mp INT DEFAULT 20 COMMENT '最大MP',
    sp INT DEFAULT 30 COMMENT '現在SP（スキルポイント）',
    max_sp INT DEFAULT 30 COMMENT '最大SP',
    
    -- ゲーム進行状況
    location_type VARCHAR(255) DEFAULT 'town' COMMENT '現在地タイプ(town/road)',
    location_id VARCHAR(255) DEFAULT 'town_a' COMMENT '具体的場所ID',
    game_position INT DEFAULT 0 COMMENT '道路上位置(0-100)',
    last_visited_town VARCHAR(255) DEFAULT 'town_a' COMMENT '最後に訪れた町',
    
    -- JSON形式データ（柔軟性確保）
    location_data JSON NULL COMMENT '場所関連の詳細データ',
    player_data JSON NULL COMMENT 'プレイヤー固有データ',
    game_data JSON NULL COMMENT 'ゲーム進行データ',
    
    -- 経済
    gold INT DEFAULT 1000 COMMENT '所持金',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_character (user_id) COMMENT '1ユーザー1キャラクター制約',
    INDEX idx_location (location_type, location_id),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ゲーム内キャラクター情報管理テーブル';
```

**重要な設計決定**:
- **1ユーザー1キャラクター**: `UNIQUE KEY(user_id)`制約
- **スキルベースレベル**: レベル = 総スキルレベル÷10+1
- **JSON活用**: 拡張性が必要な箇所でJSONカラム使用
- **位置管理**: `location_type`, `location_id`, `game_position`による詳細管理

#### skills テーブル
**責務**: キャラクタースキル情報・レベル・効果管理

```sql
CREATE TABLE skills (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    character_id BIGINT UNSIGNED NOT NULL COMMENT '所有キャラクターID',
    
    -- スキル基本情報
    skill_type VARCHAR(255) DEFAULT 'combat' COMMENT 'スキル種別',
    skill_name VARCHAR(255) NOT NULL COMMENT 'スキル名',
    level INT DEFAULT 1 COMMENT 'スキルレベル',
    experience INT DEFAULT 0 COMMENT 'スキル経験値',
    
    -- スキル効果・コスト
    effects JSON NULL COMMENT 'スキル効果詳細(JSON)',
    sp_cost INT DEFAULT 10 COMMENT 'SP消費量',
    duration INT DEFAULT 5 COMMENT '効果持続時間',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'スキル有効状態',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    INDEX idx_character_skill (character_id, skill_name),
    INDEX idx_skill_type (skill_type),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='キャラクタースキル管理テーブル';
```

**スキル種別**:
- `combat`: 戦闘スキル（攻撃力・防御力向上）
- `movement`: 移動スキル（サイコロボーナス・移動距離）
- `gathering`: 採集スキル（採集成功率・レアアイテム）
- `magic`: 魔法スキル（魔法攻撃・回復）
- `utility`: ユーティリティスキル（その他補助効果）

#### active_effects テーブル
**責務**: 一時的バフ・デバフ効果管理

```sql
CREATE TABLE active_effects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    character_id BIGINT UNSIGNED NOT NULL COMMENT '対象キャラクターID',
    
    -- 効果詳細  
    effect_type VARCHAR(255) NOT NULL COMMENT '効果種別',
    effect_value JSON NULL COMMENT '効果値・詳細(JSON)',
    duration INT DEFAULT 0 COMMENT '残り持続時間',
    is_active BOOLEAN DEFAULT TRUE COMMENT '効果有効状態',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    INDEX idx_character_active (character_id, is_active),
    INDEX idx_effect_type (effect_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='アクティブ効果（バフ・デバフ）管理テーブル';
```

### 2.3 アイテム・装備系テーブル

#### items テーブル
**責務**: ゲーム内アイテムマスターデータ管理

```sql
CREATE TABLE items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    
    -- 基本情報
    name VARCHAR(255) NOT NULL COMMENT 'アイテム名',
    description TEXT NULL COMMENT 'アイテム説明',
    category VARCHAR(255) NOT NULL COMMENT 'アイテムカテゴリ',
    
    -- アイテム特性
    stack_limit INT NULL COMMENT 'スタック上限（NULL=スタック不可）',
    max_durability INT NULL COMMENT '最大耐久度（装備品用）',
    effects JSON NULL COMMENT 'アイテム効果詳細(JSON)',
    
    -- レアリティ・価値
    rarity INT DEFAULT 1 COMMENT 'レアリティ(1-6)',
    value INT DEFAULT 0 COMMENT '基本価値',
    sell_price INT NULL COMMENT '売却価格',
    
    -- ゲーム固有情報
    battle_skill_id VARCHAR(255) NULL COMMENT '関連戦闘スキルID',
    weapon_type VARCHAR(255) NULL COMMENT '武器種別',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- インデックス
    INDEX idx_category (category),
    INDEX idx_name (name),
    INDEX idx_rarity (rarity),
    FULLTEXT idx_name_description (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='アイテムマスターデータテーブル';
```

**アイテムカテゴリ体系**:
```php
// ItemCategory Enum
const CATEGORIES = [
    'weapon' => '武器',
    'body_equipment' => '体防具', 
    'head_equipment' => '頭防具',
    'foot_equipment' => '足防具',
    'shield' => '盾',
    'accessory' => 'アクセサリー',
    'potion' => 'ポーション',
    'material' => '素材',
    'tool' => '道具',
    'key_item' => '重要アイテム'
];
```

**レアリティシステム**:
```php
// 1-6段階のレアリティ
const RARITY = [
    1 => ['name' => 'コモン', 'color' => '#9ca3af'],
    2 => ['name' => 'アンコモン', 'color' => '#22c55e'],
    3 => ['name' => 'レア', 'color' => '#3b82f6'],
    4 => ['name' => 'スーパーレア', 'color' => '#a855f7'],
    5 => ['name' => 'ウルトラレア', 'color' => '#f59e0b'], 
    6 => ['name' => 'レジェンダリー', 'color' => '#ef4444']
];
```

#### inventories テーブル
**責務**: キャラクターインベントリ管理

```sql
CREATE TABLE inventories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    character_id BIGINT UNSIGNED NOT NULL COMMENT '所有キャラクターID',
    
    -- インベントリデータ（JSON管理）
    slot_data JSON DEFAULT '[]' COMMENT 'スロット別アイテムデータ',
    max_slots INT DEFAULT 20 COMMENT '最大スロット数',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス  
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_inventory (character_id) COMMENT '1キャラクター1インベントリ',
    INDEX idx_character (character_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='キャラクターインベントリ管理テーブル';
```

**slot_data JSON 構造例**:
```json
[
  {
    "slot": 0,
    "item_id": 1,
    "quantity": 5,
    "durability": 100,
    "enchantments": []
  },
  {
    "slot": 1,
    "item_id": 15,
    "quantity": 1,
    "durability": 87,
    "enchantments": [{"type": "sharpness", "level": 2}]
  }
]
```

#### equipment テーブル
**責務**: キャラクター装備状態管理

```sql
CREATE TABLE equipment (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    character_id BIGINT UNSIGNED NOT NULL COMMENT '所有キャラクターID',
    
    -- 装備スロット（各部位）
    weapon_id BIGINT UNSIGNED NULL COMMENT '武器',
    body_armor_id BIGINT UNSIGNED NULL COMMENT '体防具',
    shield_id BIGINT UNSIGNED NULL COMMENT '盾',
    helmet_id BIGINT UNSIGNED NULL COMMENT '頭防具',
    boots_id BIGINT UNSIGNED NULL COMMENT '足防具',
    accessory_id BIGINT UNSIGNED NULL COMMENT 'アクセサリー',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (weapon_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (body_armor_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (shield_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (helmet_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (boots_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (accessory_id) REFERENCES items(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_character_equipment (character_id) COMMENT '1キャラクター1装備セット',
    INDEX idx_character (character_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='キャラクター装備状態管理テーブル';
```

### 2.4 戦闘系テーブル

#### active_battles テーブル
**責務**: 進行中戦闘状態管理

```sql
CREATE TABLE active_battles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '戦闘参加ユーザーID',
    battle_id VARCHAR(255) UNIQUE NOT NULL COMMENT 'ユニークバトルID',
    
    -- 戦闘データ（JSON管理）
    character_data JSON NOT NULL COMMENT '戦闘開始時キャラクターデータ',
    monster_data JSON NOT NULL COMMENT 'モンスターデータ',
    battle_log JSON DEFAULT '[]' COMMENT '戦闘ログ',
    
    -- 戦闘状態
    turn INT DEFAULT 1 COMMENT '現在ターン数',
    location VARCHAR(255) NULL COMMENT '戦闘発生場所',
    status VARCHAR(255) DEFAULT 'active' COMMENT '戦闘状態',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_battle_id (battle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='進行中戦闘管理テーブル';
```

**status値**:
- `active`: 戦闘進行中
- `paused`: 戦闘一時停止  
- `completed`: 戦闘完了
- `aborted`: 戦闘中断

#### battle_logs テーブル
**責務**: 完了戦闘履歴管理

```sql
CREATE TABLE battle_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '戦闘参加ユーザーID',
    
    -- 戦闘基本情報
    monster_name VARCHAR(255) NOT NULL COMMENT '対戦モンスター名',
    location VARCHAR(255) NOT NULL COMMENT '戦闘発生場所',
    result ENUM('victory', 'defeat', 'escaped') NOT NULL COMMENT '戦闘結果',
    
    -- 戦闘結果
    experience_gained INT DEFAULT 0 COMMENT '獲得経験値',
    gold_lost INT DEFAULT 0 COMMENT '失った金額',
    turns INT DEFAULT 1 COMMENT '戦闘ターン数',
    
    -- 詳細データ
    battle_data JSON NULL COMMENT '詳細戦闘データ',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス  
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_result (result),
    INDEX idx_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='戦闘履歴管理テーブル';
```

### 2.5 ショップ系テーブル

#### shops テーブル
**責務**: ゲーム内ショップ情報管理

```sql
CREATE TABLE shops (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    
    -- ショップ基本情報
    name VARCHAR(255) NOT NULL COMMENT 'ショップ名',
    shop_type VARCHAR(255) NOT NULL COMMENT 'ショップ種別',
    
    -- 場所情報
    location_id VARCHAR(255) NOT NULL COMMENT '所在場所ID',
    location_type VARCHAR(255) NOT NULL COMMENT '場所種別',
    
    -- ショップ状態
    is_active BOOLEAN DEFAULT TRUE COMMENT 'ショップ営業状態',
    description TEXT NULL COMMENT 'ショップ説明',
    shop_config JSON NULL COMMENT 'ショップ固有設定',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス
    UNIQUE KEY unique_location_shop (location_id, location_type, shop_type),
    INDEX idx_location (location_type, location_id),
    INDEX idx_shop_type (shop_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ショップ情報管理テーブル';
```

**ショップ種別**:
```php
// ShopType Enum
const SHOP_TYPES = [
    'ITEM_SHOP' => 'アイテムショップ',
    'BLACKSMITH' => '鍛冶屋',
    'TAVERN' => '酒場',
    'ALCHEMY_SHOP' => '錬金屋',        // 新規追加
    'MAGIC_SHOP' => '魔法ショップ',    // 将来拡張
    'GUILD_SHOP' => 'ギルドショップ'   // 将来拡張
];
```

#### shop_items テーブル
**責務**: ショップ商品・在庫管理

```sql
CREATE TABLE shop_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL COMMENT '所属ショップID',
    item_id BIGINT UNSIGNED NOT NULL COMMENT '販売アイテムID',
    
    -- 価格・在庫
    price INT NOT NULL COMMENT '販売価格',
    stock INT DEFAULT -1 COMMENT '在庫数（-1=無限在庫）',
    is_available BOOLEAN DEFAULT TRUE COMMENT '販売可能状態',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_shop_item (shop_id, item_id),
    INDEX idx_shop_available (shop_id, is_available),
    INDEX idx_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ショップ商品管理テーブル';
```

### 2.6 錬金システム系テーブル

#### custom_items テーブル
**責務**: プレイヤーが錬金で作成したカスタムアイテム管理

```sql
CREATE TABLE custom_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    base_item_id BIGINT UNSIGNED NOT NULL COMMENT 'ベースとなった公式アイテムID',
    creator_id BIGINT UNSIGNED NOT NULL COMMENT '作成者プレイヤーID',
    
    -- ステータス情報（JSON管理）
    custom_stats JSON NOT NULL COMMENT 'カスタム後の最終ステータス',
    base_stats JSON NOT NULL COMMENT 'ベースアイテムの元ステータス', 
    material_bonuses JSON NOT NULL COMMENT '素材による効果ボーナス',
    
    -- 耐久度管理
    base_durability INT NOT NULL COMMENT 'ベースアイテムの現在耐久度',
    durability INT NOT NULL COMMENT '現在耐久度',
    max_durability INT NOT NULL COMMENT '最大耐久度',
    
    -- 特殊属性
    is_masterwork BOOLEAN DEFAULT FALSE COMMENT '名匠品フラグ',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス
    FOREIGN KEY (base_item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES players(id) ON DELETE CASCADE,
    INDEX idx_creator (creator_id),
    INDEX idx_base_item (base_item_id),
    INDEX idx_masterwork (is_masterwork),
    INDEX idx_created_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='カスタムアイテム（錬金生成品）管理テーブル';
```

**custom_stats JSON構造例**:
```json
{
  "attack": 25,
  "defense": 12,
  "magic_attack": 8,
  "accuracy": 92,
  "durability_bonus": 15
}
```

**重要な設計決定**:
- **元データ保持**: `base_stats`, `material_bonuses`で錬金前データを記録
- **耐久度継承**: `base_durability`でベースアイテムの現在耐久度を継承
- **名匠品システム**: `is_masterwork`による特別品質管理
- **カスケード削除**: プレイヤー削除時にカスタムアイテムも削除

#### alchemy_materials テーブル
**責務**: 錬金に使用可能な素材の効果データ管理

```sql
CREATE TABLE alchemy_materials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) UNIQUE NOT NULL COMMENT '素材アイテム名',
    
    -- 効果データ
    stat_bonuses JSON NOT NULL COMMENT 'ステータスボーナス効果',
    durability_bonus INT DEFAULT 0 COMMENT '耐久度ボーナス',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- インデックス
    UNIQUE KEY unique_item_name (item_name),
    INDEX idx_item_name (item_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='錬金素材効果データテーブル';
```

**stat_bonuses JSON構造例**:
```json
{
  "attack": 3,
  "defense": 2,
  "magic_attack": 1,
  "accuracy": 5
}
```

**基本素材データ**:
- **鉄鉱石**: `{"attack":2,"defense":1}`, 耐久+10
- **動物の爪**: `{"attack":3,"defense":2}`, 耐久+5  
- **ルビー**: `{"attack":5,"magic_attack":3}`, 耐久±0
- **サファイア**: `{"defense":4,"mp":10}`, 耐久+8
- **魔法の粉**: `{"magic_attack":4,"mp":5}`, 耐久+3
- **硬い石**: `{"defense":3}`, 耐久+15
- **軽い羽根**: `{"agility":5,"evasion":3}`, 耐久-5
- **光る水晶**: `{"accuracy":8,"magic_attack":2}`, 耐久+5

#### players テーブル (Character→Player移行)
**責務**: Player統合によるゲーム内プレイヤー情報・ステータス・位置情報管理

```sql
CREATE TABLE players (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '所有者ユーザーID',
    name VARCHAR(255) DEFAULT '冒険者' COMMENT 'プレイヤー名',
    
    -- レベル・経験値システム
    level INT DEFAULT 1 COMMENT 'プレイヤーレベル(スキル総計ベース)',
    experience INT DEFAULT 0 COMMENT '戦闘経験値',
    
    -- 戦闘ステータス
    attack INT DEFAULT 10 COMMENT '攻撃力',
    defense INT DEFAULT 8 COMMENT '防御力', 
    agility INT DEFAULT 12 COMMENT '素早さ',
    evasion INT DEFAULT 15 COMMENT '回避力',
    magic_attack INT DEFAULT 8 COMMENT '魔法攻撃力',
    accuracy INT DEFAULT 85 COMMENT '命中力',
    
    -- リソース管理（HP/MP/SP）
    hp INT DEFAULT 100 COMMENT '現在HP',
    max_hp INT DEFAULT 100 COMMENT '最大HP',
    mp INT DEFAULT 20 COMMENT '現在MP',
    max_mp INT DEFAULT 20 COMMENT '最大MP',
    sp INT DEFAULT 30 COMMENT '現在SP（スキルポイント）',
    max_sp INT DEFAULT 30 COMMENT '最大SP',
    
    -- ゲーム進行状況
    location_type VARCHAR(255) DEFAULT 'town' COMMENT '現在地タイプ(town/road)',
    location_id VARCHAR(255) DEFAULT 'town_a' COMMENT '具体的場所ID',
    game_position INT DEFAULT 0 COMMENT '道路上位置(0-100)',
    last_visited_town VARCHAR(255) DEFAULT 'town_a' COMMENT '最後に訪れた町',
    
    -- JSON形式データ（柔軟性確保）
    location_data JSON NULL COMMENT '場所関連の詳細データ',
    player_data JSON NULL COMMENT 'プレイヤー固有データ',
    game_data JSON NULL COMMENT 'ゲーム進行データ',
    
    -- 経済
    gold INT DEFAULT 1000 COMMENT '所持金',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 制約・インデックス
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_player (user_id) COMMENT '1ユーザー1プレイヤー制約',
    INDEX idx_location (location_type, location_id),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ゲーム内プレイヤー情報管理テーブル（Character統合版）';
```

---

## 3. データ関係・制約設計

### 3.1 主要リレーションシップ

#### User-Player関係 (1:1)
```php
// User Model
public function player(): HasOne
{
    return $this->hasOne(Player::class);
}

// Player Model  
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

// Legacy Character→Player 移行対応
public function character(): HasOne
{
    return $this->player(); // Alias for backward compatibility
}
```

#### Player-関連エンティティ関係
```php
// Player Model
public function skills(): HasMany
{
    return $this->hasMany(Skill::class, 'character_id'); // Legacy支援
}

public function inventory(): HasOne  
{
    return $this->hasOne(Inventory::class, 'player_id');
}

public function equipment(): HasOne
{
    return $this->hasOne(Equipment::class, 'character_id'); // Legacy支援
}

public function activeEffects(): HasMany
{
    return $this->hasMany(ActiveEffect::class, 'character_id'); // Legacy支援
}

public function battleLogs(): HasMany
{
    return $this->hasMany(BattleLog::class, 'user_id', 'user_id');
}

// 錬金システム関連
public function customItems(): HasMany
{
    return $this->hasMany(CustomItem::class, 'creator_id');
}
```

#### 錬金システム関係 (1:多, 多:1)
```php
// CustomItem Model
public function creator(): BelongsTo
{
    return $this->belongsTo(Player::class, 'creator_id');
}

public function baseItem(): BelongsTo
{
    return $this->belongsTo(Item::class, 'base_item_id');
}

// Player Model (再掲)
public function customItems(): HasMany
{
    return $this->hasMany(CustomItem::class, 'creator_id');
}

// Item Model (拡張)
public function customItems(): HasMany
{
    return $this->hasMany(CustomItem::class, 'base_item_id');
}
```

#### Equipment-Items関係 (多:1)
```php
// Equipment Model
public function weapon(): BelongsTo
{
    return $this->belongsTo(Item::class, 'weapon_id');
}

public function bodyArmor(): BelongsTo
{
    return $this->belongsTo(Item::class, 'body_armor_id');
}

public function shield(): BelongsTo
{
    return $this->belongsTo(Item::class, 'shield_id');
}

public function helmet(): BelongsTo
{
    return $this->belongsTo(Item::class, 'helmet_id');
}

public function boots(): BelongsTo
{
    return $this->belongsTo(Item::class, 'boots_id');
}

public function accessory(): BelongsTo
{
    return $this->belongsTo(Item::class, 'accessory_id');
}
```

### 3.2 制約設計

#### 外部キー制約
```sql
-- CASCADE DELETE: 親削除時に子も削除
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE

-- SET NULL: 親削除時にNULLを設定（アイテム削除時の装備解除）
FOREIGN KEY (weapon_id) REFERENCES items(id) ON DELETE SET NULL
```

#### ユニーク制約
```sql
-- 1ユーザー1キャラクター制約
UNIQUE KEY unique_user_character (user_id)

-- 1キャラクター1インベントリ制約  
UNIQUE KEY unique_character_inventory (character_id)

-- 1キャラクター1装備セット制約
UNIQUE KEY unique_character_equipment (character_id)

-- ショップ・場所・種別の一意性
UNIQUE KEY unique_location_shop (location_id, location_type, shop_type)
```

### 3.3 インデックス戦略

#### パフォーマンス重視インデックス
```sql
-- 頻繁な検索・JOIN対象
INDEX idx_character_skill (character_id, skill_name)
INDEX idx_user_date (user_id, created_at)
INDEX idx_location (location_type, location_id)

-- 範囲検索・ソート対象
INDEX idx_level (level)
INDEX idx_rarity (rarity)  
INDEX idx_last_active (last_active_at)

-- 全文検索対応
FULLTEXT idx_name_description (name, description)
```

---

## 4. ゲームシステム特化設計

### 4.1 スキルベースレベルシステム

#### レベル計算ロジック
```php
// CharacterStatsService での実装
public function calculateCharacterLevel(Character $character): int
{
    $totalSkillLevel = $character->skills()->sum('level');
    return max(1, floor($totalSkillLevel / 10) + 1);
}
```

#### スキル効果管理
```json
// skills.effects JSON例
{
  "movement": {
    "extra_dice": 1,
    "dice_bonus": 3,
    "movement_multiplier": 1.2
  },
  "combat": {
    "attack_bonus": 5,
    "critical_rate": 0.15
  },
  "gathering": {
    "success_rate": 0.3,
    "rare_item_chance": 0.1
  }
}
```

### 4.2 インベントリシステム

#### スロット管理（JSON）
```json
// inventories.slot_data 構造
[
  {
    "slot": 0,
    "item_id": 1,
    "quantity": 5,
    "durability": 100,
    "acquired_at": "2025-07-25T10:00:00Z"
  },
  {
    "slot": 1,
    "item_id": 15,
    "quantity": 1, 
    "durability": 87,
    "enchantments": [
      {"type": "sharpness", "level": 2}
    ]
  }
]
```

#### アイテム効果システム
```json
// items.effects JSON例
{
  "heal_hp": 50,
  "heal_mp": 20,
  "stat_bonus": {
    "attack": 10,
    "defense": 5
  },
  "special_effects": [
    {"type": "regeneration", "duration": 300}
  ]
}
```

### 4.3 戦闘システム

#### 戦闘データ管理
```json
// active_battles.character_data
{
  "id": 1,
  "name": "冒険者",
  "hp": 85,
  "max_hp": 120,
  "mp": 45,
  "max_mp": 80,
  "stats": {
    "attack": 25,    // 装備込み
    "defense": 18,   // 装備込み
    "agility": 22,
    "accuracy": 90
  },
  "active_effects": [
    {"type": "attack_boost", "value": 5, "duration": 3}
  ]
}

// active_battles.monster_data  
{
  "name": "ゴブリン",
  "emoji": "👹",
  "hp": 20,
  "max_hp": 25,
  "stats": {
    "attack": 8,
    "defense": 3,
    "agility": 12,
    "accuracy": 75
  },
  "ai_pattern": "aggressive"
}
```

### 4.4 錬金システム

#### 錬金処理フロー設計
```php
// AlchemyMaterial での効果計算
public static function calculateCombinedEffects(array $materialNames): array
{
    $combinedStats = [];
    $combinedDurabilityBonus = 0;
    $totalMasterworkChance = 5.0; // 基本確率
    
    foreach ($materialNames as $materialName) {
        $material = self::where('item_name', $materialName)->first();
        
        // ステータスボーナス合計
        foreach ($material->stat_bonuses as $stat => $value) {
            $combinedStats[$stat] = ($combinedStats[$stat] ?? 0) + $value;
        }
        
        // 耐久度ボーナス合計
        $combinedDurabilityBonus += $material->durability_bonus;
        
        // 名匠品確率計算（効果値1につき+0.5%）
        $effectPower = array_sum($material->stat_bonuses);
        $totalMasterworkChance += $effectPower * 0.5;
    }
    
    return [
        'combined_stats' => $combinedStats,
        'combined_durability_bonus' => $combinedDurabilityBonus,
        'total_masterwork_chance' => min(50.0, $totalMasterworkChance)
    ];
}
```

#### カスタムアイテム生成ロジック
```php
// AlchemyShopService でのステータス計算
private function calculateCustomStats(array $baseStats, array $materialEffects, int $currentDurability): array
{
    $finalStats = [];
    $combinedStats = $materialEffects['combined_stats'];
    $durabilityBonus = $materialEffects['combined_durability_bonus'];
    
    // 基本ステータス + 素材効果を基準値とする
    foreach ($baseStats as $stat => $value) {
        $materialBonus = $combinedStats[$stat] ?? 0;
        $baseWithMaterial = $value + $materialBonus;
        $finalStats[$stat] = $baseWithMaterial;
    }
    
    // ランダム効果を適用（90-110%、名匠品なら120-150%）
    $isMasterwork = $this->determineMasterwork($materialEffects['total_masterwork_chance']);
    $multiplierRange = $isMasterwork ? [1.2, 1.5] : [0.9, 1.1];
    
    foreach ($finalStats as $stat => $value) {
        if ($value > 0) {
            $multiplier = $this->getRandomFloat($multiplierRange[0], $multiplierRange[1]);
            $finalStats[$stat] = max(1, (int)round($value * $multiplier));
        }
    }
    
    // 耐久度計算：現在耐久度 + 素材ボーナス
    $finalDurability = max(1, $currentDurability + $durabilityBonus);
    
    return [
        'final_stats' => $finalStats,
        'final_durability' => $finalDurability,
    ];
}
```

#### 錬金制約システム
```json
// 錬金可能性チェック
{
  "base_item_requirements": {
    "categories": ["weapon", "armor"],
    "is_custom": false,
    "min_durability": 1
  },
  "material_requirements": {
    "min_materials": 1,
    "max_materials": 5,
    "registered_materials_only": true
  },
  "output_constraints": {
    "prevent_re_alchemy": true,
    "inherit_base_durability": true,
    "random_variation_range": {
      "normal": [0.9, 1.1],
      "masterwork": [1.2, 1.5]
    }
  }
}
```

---

## 5. パフォーマンス・最適化

### 5.1 クエリ最適化

#### N+1問題対策
```php
// ❌ N+1問題発生例
$characters = Character::all();
foreach ($characters as $character) {
    echo $character->skills; // N回のクエリ発生
}

// ✅ Eager Loading による最適化
$characters = Character::with([
    'skills',
    'inventory', 
    'equipment.weapon',
    'equipment.bodyArmor',
    'activeEffects'
])->get();
```

#### 複雑クエリ最適化
```php
// 装備込みステータス計算用のクエリ
Character::with([
    'equipment' => function ($query) {
        $query->with(['weapon', 'bodyArmor', 'shield', 'helmet', 'boots', 'accessory']);
    },
    'skills' => function ($query) {
        $query->where('is_active', true);
    },
    'activeEffects' => function ($query) {
        $query->where('is_active', true)
              ->where('duration', '>', 0);
    }
])->find($characterId);
```

### 5.2 キャッシュ戦略

#### アプリケーションレベルキャッシュ
```php
// 装備効果キャッシュ
public function getEquipmentBonus(Character $character): array
{
    $cacheKey = "equipment_bonus_{$character->id}_{$character->equipment->updated_at}";
    
    return Cache::remember($cacheKey, 600, function () use ($character) {
        return $this->calculateEquipmentBonus($character);
    });
}

// スキルボーナスキャッシュ
public function getSkillBonus(Character $character): array
{
    $cacheKey = "skill_bonus_{$character->id}";
    
    return Cache::remember($cacheKey, 300, function () use ($character) {
        return $this->calculateSkillBonus($character);
    });
}
```

#### データベースレベル最適化
```sql
-- 計算結果マテリアライゼーション用ビュー
CREATE VIEW character_effective_stats AS
SELECT 
    c.id,
    c.user_id,
    c.attack + COALESCE(equipment_bonus.attack, 0) as effective_attack,
    c.defense + COALESCE(equipment_bonus.defense, 0) as effective_defense,
    -- 他のステータス...
FROM characters c
LEFT JOIN (
    -- 装備ボーナス計算サブクエリ
    SELECT character_id, 
           SUM(JSON_EXTRACT(effects, '$.attack')) as attack,
           SUM(JSON_EXTRACT(effects, '$.defense')) as defense
    FROM equipment e
    JOIN items i ON (e.weapon_id = i.id OR e.body_armor_id = i.id /* ... */)
    GROUP BY character_id
) equipment_bonus ON c.id = equipment_bonus.character_id;
```

### 5.3 JSONカラム最適化

#### JSON検索最適化
```sql
-- JSONカラムに対する仮想カラム・インデックス
ALTER TABLE characters 
ADD COLUMN location_type_virtual VARCHAR(255) 
GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(location_data, '$.type'))) STORED;

CREATE INDEX idx_location_type_virtual ON characters(location_type_virtual);
```

#### JSON更新最適化
```php
// 部分更新でパフォーマンス向上
DB::table('characters')
  ->where('id', $characterId)
  ->update([
      'location_data' => DB::raw("JSON_SET(location_data, '$.position', {$newPosition})")
  ]);
```

---

## 6. セキュリティ・整合性

### 6.1 データ整合性

#### 制約による整合性保証
```sql
-- キャラクターリソース制約（CHECKが使える場合）
ALTER TABLE characters 
ADD CONSTRAINT chk_hp_valid CHECK (hp >= 0 AND hp <= max_hp),
ADD CONSTRAINT chk_mp_valid CHECK (mp >= 0 AND mp <= max_mp),
ADD CONSTRAINT chk_sp_valid CHECK (sp >= 0 AND sp <= max_sp);

-- レベル制約
ALTER TABLE characters
ADD CONSTRAINT chk_level_valid CHECK (level >= 1 AND level <= 100);

-- 金額制約  
ALTER TABLE characters
ADD CONSTRAINT chk_gold_valid CHECK (gold >= 0);
```

#### アプリケーションレベル整合性
```php
// Character Model でのミューテータ
public function setHpAttribute($value): void
{
    $this->attributes['hp'] = max(0, min($value, $this->max_hp));
}

public function setGoldAttribute($value): void
{
    $this->attributes['gold'] = max(0, $value);
}

// レベル自動計算
protected static function booted(): void
{
    static::saving(function (Character $character) {
        $character->level = app(CharacterStatsService::class)
            ->calculateCharacterLevel($character);
    });
}
```

### 6.2 セキュリティ対策

#### 所有権検証
```php
// HasCharacter Trait
trait HasCharacter
{
    protected function getCharacter(): Character
    {
        $character = Auth::user()->character;
        
        if (!$character) {
            throw new ModelNotFoundException('Character not found');
        }
        
        return $character;
    }
    
    protected function verifyCharacterOwnership(int $characterId): Character
    {
        $character = Character::findOrFail($characterId);
        
        if ($character->user_id !== Auth::id()) {
            throw new AuthorizationException('Character access denied');
        }
        
        return $character;
    }
}
```

#### チート対策
```php
// BattleStateManager での検証
public function verifyBattleIntegrity(array $battleData): bool
{
    // ダメージ計算検証
    $expectedDamage = $this->calculateExpectedDamage($battleData);
    $actualDamage = $battleData['damage'];
    
    if ($actualDamage > $expectedDamage * 1.1) { // 10%マージン
        Log::warning('Battle integrity violation', [
            'user_id' => Auth::id(),
            'expected' => $expectedDamage,
            'actual' => $actualDamage
        ]);
        return false;
    }
    
    return true;
}
```

---

## 7. 拡張性・将来計画

### 7.1 想定機能拡張

#### ギルドシステム
```sql
-- 将来追加予定テーブル
CREATE TABLE guilds (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    leader_id BIGINT UNSIGNED NOT NULL,
    max_members INT DEFAULT 20,
    guild_level INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE guild_members (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    guild_id BIGINT UNSIGNED NOT NULL,
    character_id BIGINT UNSIGNED NOT NULL,
    role ENUM('leader', 'officer', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (guild_id) REFERENCES guilds(id) ON DELETE CASCADE,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    UNIQUE KEY (character_id) -- 1キャラクター1ギルド
);
```

#### クエストシステム
```sql
CREATE TABLE quests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quest_type VARCHAR(255) NOT NULL,
    requirements JSON,
    rewards JSON,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE character_quests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    character_id BIGINT UNSIGNED NOT NULL,
    quest_id BIGINT UNSIGNED NOT NULL,
    status ENUM('available', 'in_progress', 'completed', 'failed') DEFAULT 'available',
    progress JSON DEFAULT '{}',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE
);
```

### 7.2 スケーラビリティ対応

#### パーティショニング戦略
```sql
-- 大量データテーブルの月次パーティショニング
CREATE TABLE battle_logs (
    -- 既存カラム...
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) PARTITION BY RANGE (MONTH(created_at)) (
    PARTITION p2025_01 VALUES LESS THAN (2),
    PARTITION p2025_02 VALUES LESS THAN (3),
    PARTITION p2025_03 VALUES LESS THAN (4),
    -- ...
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

#### 読み書き分離対応
```php
// 読み取り専用クエリの明示的指定
$characters = Character::on('read_replica')
    ->with(['skills', 'equipment'])
    ->where('level', '>=', 10)
    ->get();

// 書き込みは通常のコネクション
$character = Character::find($id);
$character->update(['hp' => $newHp]);
```

---

## 8. マイグレーション管理

### 8.1 マイグレーション履歴

#### 実装済みマイグレーション
```
2025_07_15_041506_create_equipment_table.php
2025_07_16_045341_create_shops_table.php  
2025_07_16_045401_create_shop_items_table.php
2025_07_17_033710_create_characters_table.php
2025_07_17_034052_add_mp_and_magic_attack_to_characters_table.php
2025_07_17_034143_create_items_table.php
2025_07_23_043133_add_user_and_game_data_to_characters_table.php
2025_07_23_043149_create_battle_logs_table.php
2025_07_23_044753_create_skills_table.php
2025_07_23_044805_create_active_effects_table.php
2025_07_23_044815_add_level_and_experience_to_characters_table.php
2025_07_23_051003_create_inventories_table.php
2025_07_23_061416_create_active_battles_table.php
2025_07_23_063521_add_session_tracking_to_users_table.php

-- Character→Player移行関連
2025_07_26_121937_create_players_table.php
2025_07_26_122032_migrate_characters_to_players_data.php
2025_07_26_122143_update_foreign_keys_to_players.php
2025_07_27_111752_add_gold_to_players.php
2025_07_27_111952_fix_player_default_gold.php

-- 錬金システム関連 (2025年7月29日実装)
2025_07_29_043244_remove_rarity_from_items_table.php
2025_07_29_081617_create_custom_items_table.php      -- カスタムアイテム管理
2025_07_29_081645_create_alchemy_materials_table.php -- 錬金素材効果データ
```

### 8.2 マイグレーション運用方針

#### 本番環境変更手順
1. **バックアップ**: 変更前の完全バックアップ取得
2. **検証**: ステージング環境での事前検証
3. **実行**: メンテナンス時間での実行
4. **確認**: データ整合性・機能動作確認
5. **監視**: パフォーマンス・エラー監視

#### ロールバック戦略
```php
// 各マイグレーションでのロールバック処理
public function down(): void
{
    // 安全なロールバック処理
    Schema::table('characters', function (Blueprint $table) {
        $table->dropColumn(['new_column']);
    });
    
    // データ移行のロールバック
    // 必要に応じてデータ復元処理
}
```

---

このデータベース設計により、test_smgは現在の機能要件を満たしつつ、将来的な拡張にも対応できる堅牢で柔軟なデータ基盤を提供しています。1ユーザー1プレイヤーのシンプルな構造を基本としながら、JSONカラムやモジュラー設計により拡張性を確保し、適切な制約とインデックスによりパフォーマンスと整合性を両立しています。

**2025年7月29日更新内容**:
- 錬金システム関連テーブル追加（custom_items, alchemy_materials）
- Character→Player移行に伴うリレーション更新
- 錬金システム特化設計の詳細追加
- ShopType ALCHEMY_SHOP の追加

**最終更新**: 2025年7月29日  
**次回レビュー**: データベース構造変更時または四半期レビュー時