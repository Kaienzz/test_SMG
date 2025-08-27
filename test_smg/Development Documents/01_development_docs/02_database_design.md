# データベース設計書
# test_smg データベース設計仕様書

## ドキュメント情報

**プロジェクト名**: test_smg (Simple Management Game)  
**作成日**: 2025年7月25日  
**最終更新**: 2025年8月27日  
**版数**: Version 2.0  
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
│  │   (認証+管理) │  │ reset_tokens │  │   (セッション)  │  │
│  │  ┌─────────┐│  └──────────────┘  └─────────────────┘  │
│  │  │admin_*  ││                                          │
│  │  │tables   ││                                          │
│  │  └─────────┘│                                          │
│  └─────────────┘                                          │
└─────────────────────────────────────────────────────────┘
                              │ 1:1
                              ▼
┌─────────────────────────────────────────────────────────┐
│                 Character/Player System                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │   players   │  │   skills    │  │ active_effects  │  │
│  │(統合エンティティ)│  │   (1:多)  │  │   (一時効果)    │  │
│  │ ┌─────────┐ │  └─────────────┘  └─────────────────┘  │
│  │ │character│ │          │               │             │
│  │ │ (legacy)│ │          │               │             │
│  │ └─────────┘ │          │               │             │
│  └─────────────┘          │               │             │
└─────────────────────────────────────────────────────────┘
           │ 1:1              │ 1:1              │ 1:多
           ▼                  ▼                  ▼
┌─────────────────────────────────────────────────────────┐
│               Item & Equipment System                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │inventories  │  │ equipment   │  │     items       │  │
│  │  (JSON管理)  │  │  (装備状態)  │  │  (旧マスター)    │  │
│  └─────────────┘  └─────────────┘  ├─────────────────┤  │
│  ┌─────────────┐  ┌─────────────┐  │ standard_items  │  │
│  │custom_items │  │alchemy_     │  │  (新マスター)    │  │
│  │ (錬金品)    │  │ materials   │  └─────────────────┘  │
│  └─────────────┘  └─────────────┘                      │
└─────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────┐
│                 World & Location System                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │    routes   │  │route_       │  │gathering_       │  │
│  │ (場所/経路)  │  │connections  │  │mappings         │  │
│  └─────────────┘  └─────────────┘  └─────────────────┘  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │dungeons_desc│  │town_        │  │   monsters      │  │
│  │ (ダンジョン)  │  │facilities   │  │ (モンスター)     │  │
│  └─────────────┘  └─────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────┐
│                   Battle System                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │active_battles│  │battle_logs  │  │monster_spawns   │  │
│  │ (進行中戦闘) │  │  (戦闘履歴)  │  │ (出現設定)      │  │
│  └─────────────┘  └─────────────┘  └─────────────────┘  │
│  ┌─────────────┐  ┌─────────────┐                      │
│  │monster_spawn│  │ spawn_lists │                      │
│  │_lists       │  │ (出現リスト) │                      │
│  └─────────────┘  └─────────────┘                      │
└─────────────────────────────────────────────────────────┘
```

---

## 2. テーブル設計詳細

### 2.1 ユーザー管理系テーブル

#### users テーブル
**責務**: ユーザー認証・セッション管理・デバイス情報管理・管理者システム

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
    
    -- 管理者システム (2025年8月追加)
    is_admin BOOLEAN DEFAULT FALSE COMMENT '管理者フラグ',
    admin_activated_at TIMESTAMP NULL COMMENT '管理者権限有効化日時',
    admin_last_login_at TIMESTAMP NULL COMMENT '管理者最終ログイン日時',
    admin_role_id BIGINT UNSIGNED NULL COMMENT '管理者ロールID',
    admin_permissions JSON NULL COMMENT '管理者権限設定',
    admin_level VARCHAR(255) DEFAULT 'basic' COMMENT '管理者レベル',
    admin_requires_2fa BOOLEAN DEFAULT FALSE COMMENT '2要素認証必須フラグ',
    admin_ip_whitelist JSON NULL COMMENT 'IP制限設定',
    admin_permissions_updated_at TIMESTAMP NULL COMMENT '権限最終更新日時',
    admin_created_by BIGINT UNSIGNED NULL COMMENT '管理者権限付与者ID',
    admin_notes TEXT NULL COMMENT '管理者メモ',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_last_active (last_active_at),
    INDEX idx_active_admins (is_admin, admin_activated_at),
    INDEX idx_admin_role (admin_role_id),
    INDEX idx_admin_level (admin_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ユーザー認証・セッション管理・管理者システムテーブル';
```

**重要な設計決定**:
- `session_data`: マルチデバイス同期のためのJSON データ
- `admin_*`: 包括的な管理者権限管理システム
- 段階的な管理者レベル：basic/advanced/super
- IP制限・2FA対応のセキュリティ機能

#### 管理者システム関連テーブル (2025年8月追加)

##### admin_roles テーブル
**責務**: 管理者ロール・権限テンプレート管理

```sql
CREATE TABLE admin_roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'ロール名',
    description TEXT NULL COMMENT 'ロール説明',
    permissions JSON NOT NULL COMMENT '権限設定',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'ロール有効状態',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='管理者ロール定義テーブル';
```

##### admin_permissions テーブル
**責務**: 細かな権限設定管理

```sql
CREATE TABLE admin_permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT '権限名',
    description TEXT NULL COMMENT '権限説明',
    category VARCHAR(255) NOT NULL COMMENT '権限カテゴリ',
    is_active BOOLEAN DEFAULT TRUE COMMENT '権限有効状態',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='管理者権限マスターテーブル';
```

##### admin_audit_logs テーブル
**責務**: 管理者操作ログ・監査証跡管理

```sql
CREATE TABLE admin_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admin_user_id BIGINT UNSIGNED NOT NULL COMMENT '操作者管理者ID',
    action_type VARCHAR(255) NOT NULL COMMENT '操作種別',
    target_type VARCHAR(255) NULL COMMENT '操作対象種別',
    target_id VARCHAR(255) NULL COMMENT '操作対象ID',
    old_values JSON NULL COMMENT '変更前データ',
    new_values JSON NULL COMMENT '変更後データ',
    ip_address VARCHAR(255) NULL COMMENT '操作元IPアドレス',
    user_agent TEXT NULL COMMENT 'ブラウザ情報',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_action (admin_user_id, action_type),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='管理者操作監査ログテーブル';
```

### 2.2 ワールド・場所システム (2025年8月大幅拡張)

#### routes テーブル (旧locations)
**責務**: ゲーム内場所・経路・ダンジョン情報管理

```sql
CREATE TABLE routes (
    id VARCHAR(255) NOT NULL PRIMARY KEY COMMENT '場所ID',
    name VARCHAR(255) NOT NULL COMMENT '場所名',
    description TEXT NULL COMMENT '場所説明',
    category VARCHAR(255) NOT NULL COMMENT '場所カテゴリ',
    
    -- 経路・移動関連
    length INT NULL COMMENT '経路長（移動に必要なターン数）',
    difficulty VARCHAR(255) NULL COMMENT '難易度',
    encounter_rate DECIMAL(3,2) NULL COMMENT 'エンカウント率（0.00-1.00）',
    
    -- ダンジョン関連
    type VARCHAR(255) NULL COMMENT '場所タイプ',
    floors INT NULL COMMENT '階層数',
    min_level INT NULL COMMENT '推奨最小レベル',
    max_level INT NULL COMMENT '推奨最大レベル',
    boss VARCHAR(255) NULL COMMENT 'ボスモンスター',
    
    -- 町・拠点関連
    services JSON NULL COMMENT '利用可能サービス',
    special_actions JSON NULL COMMENT '特殊アクション',
    branches JSON NULL COMMENT '分岐情報',
    
    -- モンスター・採集関連
    spawn_list_id VARCHAR(255) NULL COMMENT 'モンスター出現リストID',
    spawn_tags JSON NULL COMMENT '出現タグ',
    spawn_description TEXT NULL COMMENT '出現説明',
    
    -- ダンジョン関連外部キー
    dungeon_id VARCHAR(255) NULL COMMENT 'ダンジョンID',
    
    is_active BOOLEAN DEFAULT TRUE COMMENT '場所有効状態',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (dungeon_id) REFERENCES dungeons_desc(dungeon_id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_difficulty (difficulty),
    INDEX idx_is_active (is_active),
    INDEX idx_spawn_list_id (spawn_list_id),
    INDEX idx_dungeon_id (dungeon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ゲーム内場所・経路・ダンジョン管理テーブル';
```

**場所カテゴリ体系**:
```php
const ROUTE_CATEGORIES = [
    'town' => '町・拠点',
    'road' => '道路・経路',
    'dungeon' => 'ダンジョン',
    'forest' => '森林',
    'mountain' => '山地',
    'cave' => '洞窟',
    'ruins' => '遺跡',
    'special' => '特殊場所'
];
```

#### route_connections テーブル
**責務**: 場所間の接続・移動経路管理

```sql
CREATE TABLE route_connections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    source_location_id VARCHAR(255) NOT NULL COMMENT '出発場所ID',
    target_location_id VARCHAR(255) NOT NULL COMMENT '到着場所ID',
    connection_type VARCHAR(255) NOT NULL COMMENT '接続種別',
    position INT NULL COMMENT '接続位置',
    direction VARCHAR(255) NULL COMMENT '方向',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (source_location_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (target_location_id) REFERENCES routes(id) ON DELETE CASCADE,
    INDEX idx_source_location (source_location_id),
    INDEX idx_target_location (target_location_id),
    INDEX idx_connection_type (connection_type),
    INDEX idx_direction (direction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='場所間接続・経路管理テーブル';
```

#### dungeons_desc テーブル
**責務**: ダンジョン詳細情報管理

```sql
CREATE TABLE dungeons_desc (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dungeon_id VARCHAR(255) NOT NULL UNIQUE COMMENT 'ダンジョンID',
    dungeon_name VARCHAR(255) NOT NULL COMMENT 'ダンジョン名',
    dungeon_desc TEXT NULL COMMENT 'ダンジョン説明',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'ダンジョン有効状態',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_dungeon_id (dungeon_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ダンジョン詳細情報テーブル';
```

#### town_facilities テーブル (新ショップシステム)
**責務**: 町の施設・ショップ管理

```sql
CREATE TABLE town_facilities (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT '施設名',
    facility_type VARCHAR(255) NOT NULL COMMENT '施設種別',
    location_id VARCHAR(255) NOT NULL COMMENT '所在場所ID',
    location_type VARCHAR(255) NOT NULL COMMENT '場所種別',
    is_active BOOLEAN DEFAULT TRUE COMMENT '施設営業状態',
    description TEXT NULL COMMENT '施設説明',
    facility_config JSON NULL COMMENT '施設固有設定',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_location_facility (location_id, location_type, facility_type),
    INDEX idx_facility_type (facility_type),
    INDEX idx_location (location_id, location_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='町施設・ショップ管理テーブル';
```

**施設種別**:
```php
const FACILITY_TYPES = [
    'ITEM_SHOP' => 'アイテムショップ',
    'BLACKSMITH' => '鍛冶屋',
    'TAVERN' => '酒場',
    'ALCHEMY_SHOP' => '錬金屋',
    'MAGIC_SHOP' => '魔法ショップ',
    'GUILD_HALL' => 'ギルドホール',
    'BANK' => '銀行',
    'WAREHOUSE' => '倉庫'
];
```

#### facility_items テーブル (旧shop_items)
**責務**: 施設商品・在庫管理

```sql
CREATE TABLE facility_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    facility_id BIGINT UNSIGNED NOT NULL COMMENT '所属施設ID',
    item_id BIGINT UNSIGNED NOT NULL COMMENT '販売アイテムID',
    
    -- 価格・在庫
    price INT NOT NULL COMMENT '販売価格',
    stock INT DEFAULT -1 COMMENT '在庫数（-1=無限在庫）',
    is_available BOOLEAN DEFAULT TRUE COMMENT '販売可能状態',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (facility_id) REFERENCES town_facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_facility_item (facility_id, item_id),
    INDEX idx_facility_available (facility_id, is_available),
    INDEX idx_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='施設商品管理テーブル';
```

#### gathering_mappings テーブル (採集システム)
**責務**: 場所別採集可能アイテム・成功率管理

```sql
CREATE TABLE gathering_mappings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    route_id VARCHAR(255) NOT NULL COMMENT '採集場所ID',
    item_id BIGINT UNSIGNED NOT NULL COMMENT '採集アイテムID',
    
    -- 採集設定
    required_skill_level INT DEFAULT 1 COMMENT '必要スキルレベル',
    success_rate INT NOT NULL COMMENT '基本成功率（%）',
    quantity_min INT DEFAULT 1 COMMENT '最小採集数',
    quantity_max INT DEFAULT 1 COMMENT '最大採集数',
    is_active BOOLEAN DEFAULT TRUE COMMENT '採集可能状態',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_route_item (route_id, item_id),
    INDEX idx_route_id (route_id),
    INDEX idx_item_id (item_id),
    INDEX idx_skill_level (required_skill_level),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='場所別採集アイテム管理テーブル';
```

### 2.3 モンスター・戦闘システム (2025年8月実装)

#### monsters テーブル
**責務**: モンスター基本情報・ステータス管理

```sql
CREATE TABLE monsters (
    id VARCHAR(255) NOT NULL PRIMARY KEY COMMENT 'モンスターID',
    name VARCHAR(255) NOT NULL COMMENT 'モンスター名',
    level INT NOT NULL COMMENT 'モンスターレベル',
    
    -- ステータス
    hp INT NOT NULL COMMENT '最大HP',
    max_hp INT NOT NULL COMMENT '最大HP（冗長だが明示的に）',
    attack INT NOT NULL COMMENT '攻撃力',
    defense INT NOT NULL COMMENT '防御力',
    agility INT NOT NULL COMMENT '素早さ',
    evasion INT NOT NULL COMMENT '回避力',
    accuracy INT NOT NULL COMMENT '命中力',
    
    -- 報酬・外見
    experience_reward INT NOT NULL COMMENT '撃破時経験値',
    emoji VARCHAR(10) NULL COMMENT 'モンスター絵文字',
    description TEXT NULL COMMENT 'モンスター説明',
    
    is_active BOOLEAN DEFAULT TRUE COMMENT 'モンスター有効状態',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_level (level),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='モンスター基本情報テーブル';
```

#### spawn_lists テーブル
**責務**: モンスター出現リスト・グループ管理

```sql
CREATE TABLE spawn_lists (
    id VARCHAR(255) NOT NULL PRIMARY KEY COMMENT '出現リストID',
    name VARCHAR(255) NOT NULL COMMENT '出現リスト名',
    description TEXT NULL COMMENT '出現リスト説明',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'リスト有効状態',
    tags JSON NULL COMMENT '出現タグ（検索・分類用）',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='モンスター出現リスト管理テーブル';
```

#### monster_spawns テーブル
**責務**: 出現リスト内のモンスター・確率設定

```sql
CREATE TABLE monster_spawns (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    spawn_list_id VARCHAR(255) NOT NULL COMMENT '所属出現リストID',
    monster_id VARCHAR(255) NOT NULL COMMENT 'モンスターID',
    
    -- 出現設定
    spawn_rate DECIMAL(5,2) NOT NULL COMMENT '出現確率（%）',
    priority INT DEFAULT 0 COMMENT '出現優先度',
    min_level INT NULL COMMENT '出現最小レベル',
    max_level INT NULL COMMENT '出現最大レベル',
    is_active BOOLEAN DEFAULT TRUE COMMENT '出現有効状態',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (spawn_list_id) REFERENCES spawn_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (monster_id) REFERENCES monsters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_spawn_monster (spawn_list_id, monster_id),
    INDEX idx_spawn_list_id (spawn_list_id),
    INDEX idx_monster_id (monster_id),
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='出現リスト内モンスター設定テーブル';
```

#### monster_spawn_lists テーブル
**責務**: 場所別モンスター出現設定管理

```sql
CREATE TABLE monster_spawn_lists (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    location_id VARCHAR(255) NOT NULL COMMENT '場所ID',
    monster_id VARCHAR(255) NOT NULL COMMENT 'モンスターID',
    
    -- 出現設定
    spawn_rate DECIMAL(5,2) NOT NULL COMMENT '出現確率（%）',
    priority INT DEFAULT 0 COMMENT '出現優先度',
    min_level INT NULL COMMENT '出現最小レベル',
    max_level INT NULL COMMENT '出現最大レベル',
    is_active BOOLEAN DEFAULT TRUE COMMENT '出現有効状態',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (location_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (monster_id) REFERENCES monsters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_location_monster (location_id, monster_id),
    INDEX idx_location_id (location_id),
    INDEX idx_monster_id (monster_id),
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='場所別モンスター出現設定テーブル';
```

### 2.4 キャラクター管理系テーブル

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

### 2.5 アイテム・装備系テーブル

#### items テーブル (レガシー)
**責務**: 従来のアイテムマスターデータ管理（後方互換性維持）

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
    
    -- 価値
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
    FULLTEXT idx_name_description (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='アイテムマスターデータテーブル（レガシー）';
```

#### standard_items テーブル (新標準システム)
**責務**: 新しい標準アイテムシステム・拡張アイテム管理

```sql
CREATE TABLE standard_items (
    id VARCHAR(255) NOT NULL PRIMARY KEY COMMENT '標準アイテムID',
    name VARCHAR(255) NOT NULL COMMENT 'アイテム名',
    description TEXT NULL COMMENT 'アイテム説明',
    category VARCHAR(255) NOT NULL COMMENT 'アイテムカテゴリ',
    category_name VARCHAR(255) NOT NULL COMMENT 'カテゴリ表示名',
    
    -- アイテム効果・特性
    effects JSON NOT NULL COMMENT 'アイテム効果詳細(JSON)',
    value INT NOT NULL COMMENT '基本価値',
    sell_price INT NULL COMMENT '売却価格',
    stack_limit INT DEFAULT 1 COMMENT 'スタック上限',
    max_durability INT NULL COMMENT '最大耐久度',
    
    -- 装備・使用可能性
    is_equippable BOOLEAN DEFAULT FALSE COMMENT '装備可能フラグ',
    is_usable BOOLEAN DEFAULT FALSE COMMENT '使用可能フラグ',
    weapon_type VARCHAR(255) NULL COMMENT '武器種別',
    is_standard BOOLEAN DEFAULT TRUE COMMENT '標準アイテムフラグ',
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_is_equippable (is_equippable),
    INDEX idx_is_usable (is_usable),
    INDEX idx_weapon_type (weapon_type),
    INDEX idx_is_standard (is_standard)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='標準アイテムマスターデータテーブル';
```

**標準アイテム効果JSON例**:
```json
{
  "stat_bonus": {
    "attack": 15,
    "defense": 8,
    "accuracy": 5
  },
  "special_effects": [
    {"type": "fire_damage", "value": 3}
  ],
  "usage_effects": {
    "heal_hp": 50,
    "heal_mp": 20
  }
}
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

#### custom_items テーブル (錬金システム)
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
    
    -- 耐久度管理 (2025年8月25日にdurabilityカラム削除)
    base_durability INT NOT NULL COMMENT 'ベースアイテムの現在耐久度',
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

**重要な設計変更 (2025年8月25日)**:
- `durability` カラムを削除：耐久度はインベントリ側で管理
- カスタムアイテムは生成時の設計図として機能
- 実際の使用・耐久度管理はインベントリシステムが担当

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

#### 基本システム (2025年7月)
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
```

#### Character→Player移行 (2025年7月26-27日)
```
2025_07_26_121937_create_players_table.php
2025_07_26_122032_migrate_characters_to_players_data.php
2025_07_26_122143_update_foreign_keys_to_players.php
2025_07_27_111752_add_gold_to_players.php
2025_07_27_111952_fix_player_default_gold.php
```

#### 錬金システム (2025年7月29日)
```
2025_07_29_043244_remove_rarity_from_items_table.php
2025_07_29_081617_create_custom_items_table.php      -- カスタムアイテム管理
2025_07_29_081645_create_alchemy_materials_table.php -- 錬金素材効果データ
```

#### 管理者システム (2025年8月13日)
```
2025_08_13_035226_add_admin_system_to_users_table.php    -- ユーザーテーブルに管理者機能追加
2025_08_13_035226_create_admin_permissions_table.php    -- 管理者権限マスター
2025_08_13_035227_create_admin_audit_logs_table.php     -- 管理者操作ログ
2025_08_13_035227_create_admin_roles_table.php          -- 管理者ロール
```

#### モンスター・戦闘システム (2025年8月19-20日)
```
2025_08_19_044721_create_monsters_table.php             -- モンスター基本情報
2025_08_19_044726_create_location_connections_table.php -- 場所間接続
2025_08_19_044726_create_locations_table.php            -- 場所・経路情報
2025_08_19_044726_create_monster_spawns_table.php       -- モンスター出現設定
2025_08_19_044726_create_spawn_lists_table.php          -- 出現リスト
2025_08_19_044727_create_standard_items_table.php       -- 新標準アイテムシステム
2025_08_19_045330_update_location_connections_enum.php  -- 接続タイプ更新

2025_08_20_024009_create_monster_spawn_lists_table.php  -- 場所別モンスター出現
2025_08_20_024426_add_spawn_fields_to_game_locations_table.php -- 場所にスポーン情報追加
2025_08_20_095043_create_dungeons_desc_table.php        -- ダンジョン説明
2025_08_20_095115_add_dungeon_id_to_game_locations_table.php   -- ダンジョンID追加
```

#### 場所システム再構築 (2025年8月21日)
```
2025_08_21_022026_rename_game_locations_tables.php      -- locations → routes へリネーム
```

#### 採集・施設システム (2025年8月25-26日)
```
2025_08_25_024902_create_gathering_mappings_table.php   -- 採集アイテム管理
2025_08_25_074151_remove_durability_from_custom_items_table.php -- カスタムアイテム耐久度削除

2025_08_26_012740_create_town_facilities_table.php      -- 町施設システム
2025_08_26_012929_rename_shop_items_to_facility_items.php -- ショップ→施設アイテムへ移行
2025_08_26_013225_migrate_shops_to_town_facilities.php   -- ショップデータ移行
2025_08_26_013429_drop_shops_table.php                  -- 旧ショップテーブル削除
```

#### 実装済みテーブル一覧 (2025年8月27日現在)
**総テーブル数**: 36テーブル

**ユーザー・認証系**:
- users, password_reset_tokens, sessions
- admin_roles, admin_permissions, admin_audit_logs

**キャラクター・プレイヤー系**:
- characters (レガシー), players, skills, active_effects

**アイテム・装備系**:
- items (レガシー), standard_items, custom_items, alchemy_materials
- inventories, equipment

**場所・世界系**:
- routes (旧locations), route_connections, dungeons_desc
- gathering_mappings

**施設・経済系**:
- town_facilities, facility_items (旧shop_items)

**モンスター・戦闘系**:
- monsters, spawn_lists, monster_spawns, monster_spawn_lists
- active_battles, battle_logs

**システム・技術系**:
- cache, cache_locks, jobs, job_batches, failed_jobs
- migrations, connections

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

**2025年8月27日 大幅更新内容**:
- 管理者システム完全実装（users拡張 + admin_* テーブル群）
- ワールドシステム大幅拡張（routes, route_connections, dungeons_desc）
- モンスター・戦闘システム実装（monsters, spawn_lists, monster_spawns）
- 新アイテムシステム実装（standard_items）
- 採集システム実装（gathering_mappings）
- 施設システム実装（town_facilities, facility_items）
- ショップシステムから施設システムへの移行完了
- カスタムアイテムシステム改良（耐久度管理方式変更）
- 総テーブル数：36テーブル

**Version 2.0 主要変更点**:
1. **システム規模**: 基本システム(13テーブル) → 包括的ゲームシステム(36テーブル)
2. **機能範囲**: 認証+基本ゲーム → 管理者+ワールド+戦闘+採集+施設
3. **設計方針**: レガシー互換性維持 + 新機能モジュラー設計
4. **スケーラビリティ**: 小規模対応 → 中規模MMO対応基盤

**最終更新**: 2025年8月27日  
**次回レビュー**: データベース構造変更時または四半期レビュー時