# API設計書
# test_smg API設計仕様書

## ドキュメント情報

**プロジェクト名**: test_smg (Simple Management Game)  
**作成日**: 2025年7月25日  
**版数**: Version 1.0  
**対象**: 開発チーム、フロントエンド開発者、API利用者  

---

## 1. API設計概要

### 1.1 設計思想

test_smgのAPI設計は、以下の原則に基づいて構築されています：

#### 核となる設計原則
1. **RESTful設計**: リソース指向の明確なAPI構造
2. **一貫性**: 統一されたレスポンス形式・エラーハンドリング
3. **セキュリティ**: 認証・認可・入力検証の徹底
4. **ゲーム特化**: リアルタイム性・状態管理を考慮した設計
5. **拡張性**: 将来の機能追加を考慮したバージョニング対応

### 1.2 API構成概要

```
test_smg API Structure
├── 認証系 API (/auth/*)
├── ゲーム基本機能 API (/game/*)  
├── キャラクター管理 API (/character/*)
├── 戦闘システム API (/battle/*)
├── インベントリ API (/inventory/*)
├── 装備管理 API (/equipment/*)
├── スキル管理 API (/skills/*)
├── ショップ API (/shops/*)
├── 採集システム API (/gathering/*)
├── 同期システム API (/sync/*)
└── 分析システム API (/analytics/*)
```

### 1.3 API仕様標準

#### ベースURL
```
# 開発環境
http://localhost:8000

# 本番環境  
https://testsmg.example.com
```

#### 標準ヘッダー
```http
Content-Type: application/json
Accept: application/json
X-Requested-With: XMLHttpRequest
X-CSRF-TOKEN: {csrf_token}
Authorization: Bearer {jwt_token}  # 将来のAPI認証用
```

#### レスポンス形式標準
```json
// 成功レスポンス
{
  "success": true,
  "data": {
    // 実際のデータ
  },
  "message": "Operation completed successfully",
  "timestamp": "2025-07-25T10:30:00Z"
}

// エラーレスポンス
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input parameters",
    "details": {
      "field": ["error message"]
    }
  },
  "timestamp": "2025-07-25T10:30:00Z"
}
```

---

## 2. 認証・セッション管理API

### 2.1 認証関連エンドポイント

#### POST /register
**概要**: 新規ユーザー登録

```http
POST /register
Content-Type: application/json

{
  "name": "NewPlayer",
  "email": "player@example.com", 
  "password": "securepassword123",
  "password_confirmation": "securepassword123"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "NewPlayer",
      "email": "player@example.com",
      "created_at": "2025-07-25T10:30:00Z"
    },
    "character": {
      "id": 1,
      "name": "冒険者",
      "level": 1,
      "location_type": "town",
      "location_id": "town_a"
    }
  },
  "message": "Registration successful"
}
```

#### POST /login
**概要**: ユーザーログイン

```http
POST /login
Content-Type: application/json

{
  "email": "player@example.com",
  "password": "securepassword123",
  "remember": true
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "NewPlayer",
      "email": "player@example.com"
    },
    "redirect_url": "/game"
  },
  "message": "Login successful"
}
```

#### POST /logout
**概要**: ユーザーログアウト

```http
POST /logout
X-CSRF-TOKEN: {csrf_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {},
  "message": "Logout successful"
}
```

---

## 3. ゲーム基本機能API

### 3.1 ゲーム状態管理

#### GET /game
**概要**: ゲーム画面データ取得

```http
GET /game
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "character": {
      "id": 1,
      "name": "冒険者",
      "level": 5,
      "hp": 85,
      "max_hp": 120,
      "mp": 45,
      "max_mp": 80,
      "sp": 30,
      "max_sp": 60,
      "gold": 1250,
      "stats": {
        "attack": 25,
        "defense": 18,
        "agility": 22,
        "evasion": 28,
        "magic_attack": 15,
        "accuracy": 92
      }
    },
    "location": {
      "type": "road",
      "id": "road_1",
      "name": "森の道",
      "position": 45,
      "description": "深い森に続く静かな道"
    },
    "game_state": {
      "can_move": true,
      "can_gather": true,
      "encounter_rate": 30,
      "available_actions": ["move_left", "move_right", "gather", "next_location"]
    },
    "battle": null
  }
}
```

#### POST /game/roll-dice
**概要**: サイコロを振る

```http
POST /game/roll-dice
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "dice_count": 3
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "dice_rolls": [4, 2, 6],
    "total": 12,
    "bonus": 3,
    "final_total": 15,
    "effects": {
      "dice_bonus": 3,
      "extra_dice": 1,
      "movement_multiplier": 1.0
    }
  },
  "message": "Dice rolled successfully"
}
```

#### POST /game/move
**概要**: キャラクター移動

```http
POST /game/move
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "direction": "right",
  "distance": 15
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "old_position": 45,
    "new_position": 60,
    "distance_moved": 15,
    "sp_consumed": 2,
    "encounter": {
      "occurred": true,
      "monster": {
        "name": "ゴブリン", 
        "emoji": "👹",
        "message": "ゴブリンが現れた！"
      },
      "battle_id": "battle_12345"
    }
  },
  "message": "Movement completed"
}
```

#### POST /game/move-to-next
**概要**: 次の場所への移動

```http
POST /game/move-to-next
X-CSRF-TOKEN: {csrf_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "from": {
      "type": "road",
      "id": "road_1"
    },
    "to": {
      "type": "town", 
      "id": "town_b",
      "name": "商業の町",
      "description": "商人たちで賑わう活気ある町"
    },
    "position_reset": true
  },
  "message": "Successfully moved to town_b"
}
```

#### POST /game/reset
**概要**: ゲーム状態リセット

```http
POST /game/reset
X-CSRF-TOKEN: {csrf_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "reset_items": ["position", "hp", "mp", "sp"],
    "new_location": {
      "type": "town",
      "id": "town_a"
    }
  },
  "message": "Game state reset successfully"
}
```

---

## 4. 戦闘システムAPI

### 4.1 戦闘管理

#### GET /battle
**概要**: 戦闘画面データ取得

```http
GET /battle
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "battle_id": "battle_12345",
    "status": "active",
    "turn": 3,
    "character": {
      "name": "冒険者",
      "hp": 75,
      "max_hp": 120,
      "mp": 35,
      "max_mp": 80,
      "sp": 25,
      "max_sp": 60
    },
    "monster": {
      "name": "ゴブリン",
      "emoji": "👹", 
      "hp": 15,
      "max_hp": 25,
      "stats": {
        "attack": 8,
        "defense": 3,
        "agility": 12
      }
    },
    "available_actions": ["attack", "defend", "escape", "skill"],
    "battle_log": [
      "戦闘開始！",
      "冒険者の攻撃！ゴブリンに12のダメージ！",
      "ゴブリンの攻撃！冒険者に8のダメージ！"
    ]
  }
}
```

#### POST /battle/start
**概要**: 戦闘開始

```http
POST /battle/start
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "monster_id": "goblin_001",
  "location": "road_1"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "battle_id": "battle_12345",
    "monster": {
      "name": "ゴブリン",
      "emoji": "👹",
      "hp": 25,
      "max_hp": 25
    },
    "message": "ゴブリンとの戦闘が始まった！"
  }
}
```

#### POST /battle/attack
**概要**: 攻撃実行

```http
POST /battle/attack
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "battle_id": "battle_12345",
  "attack_type": "normal"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "player_action": {
      "type": "attack",
      "damage": 12,
      "critical": false,
      "message": "冒険者の攻撃！ゴブリンに12のダメージ！"
    },
    "monster_action": {
      "type": "attack",
      "damage": 8,
      "message": "ゴブリンの攻撃！冒険者に8のダメージ！"
    },
    "battle_state": {
      "character_hp": 75,
      "monster_hp": 13,
      "turn": 4,
      "status": "active"
    },
    "battle_result": null
  }
}
```

#### POST /battle/defend
**概要**: 防御実行

```http
POST /battle/defend
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "battle_id": "battle_12345"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "player_action": {
      "type": "defend",
      "defense_bonus": 10,
      "message": "冒険者は身構えた！"
    },
    "monster_action": {
      "type": "attack",
      "damage": 3,
      "message": "ゴブリンの攻撃！しかし防御により3ダメージに軽減！"
    },
    "battle_state": {
      "character_hp": 72,
      "monster_hp": 13,
      "turn": 5,
      "status": "active"
    }
  }
}
```

#### POST /battle/escape
**概要**: 逃走実行

```http
POST /battle/escape
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "battle_id": "battle_12345"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "escape_successful": true,
    "message": "冒険者は戦闘から逃げ出した！",
    "penalties": {
      "gold_lost": 50,
      "experience_lost": 0
    }
  }
}
```

#### POST /battle/skill
**概要**: スキル使用

```http
POST /battle/skill
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "battle_id": "battle_12345",
  "skill_id": "power_attack",
  "target": "monster"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "skill_used": {
      "name": "パワーアタック",
      "sp_cost": 10,
      "damage": 18,
      "effects": ["damage_boost"],
      "message": "冒険者のパワーアタック！ゴブリンに18のダメージ！"
    },
    "battle_state": {
      "character_hp": 72,
      "character_sp": 15,
      "monster_hp": 0,
      "turn": 6,
      "status": "victory"
    },
    "battle_result": {
      "result": "victory",
      "rewards": {
        "experience": 25,
        "gold": 30,
        "items": []
      }
    }
  }
}
```

#### POST /battle/end
**概要**: 戦闘終了処理

```http
POST /battle/end
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "battle_id": "battle_12345"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "battle_completed": true,
    "final_result": "victory",
    "character_updates": {
      "experience_gained": 25,
      "gold_gained": 30,
      "level_up": false
    }
  },
  "message": "Battle ended successfully"
}
```

---

## 5. キャラクター管理API

### 5.1 キャラクター情報

#### GET /character
**概要**: キャラクター詳細情報取得

```http
GET /character
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "character": {
      "id": 1,
      "name": "冒険者",
      "level": 5,
      "experience": 125,
      "stats": {
        "base": {
          "attack": 15,
          "defense": 12,
          "agility": 18,
          "evasion": 22,
          "magic_attack": 12,
          "accuracy": 90
        },
        "effective": {
          "attack": 25,
          "defense": 18,
          "agility": 22,
          "evasion": 28,
          "magic_attack": 15,
          "accuracy": 92
        },
        "bonuses": {
          "equipment": {
            "attack": 8,
            "defense": 5
          },
          "skills": {
            "agility": 2,
            "accuracy": 2
          }
        }
      },
      "resources": {
        "hp": 85,
        "max_hp": 120,
        "mp": 45,
        "max_mp": 80,
        "sp": 30,
        "max_sp": 60,
        "gold": 1250
      },
      "location": {
        "type": "town",
        "id": "town_a",
        "position": 0
      }
    }
  }
}
```

#### POST /character/heal
**概要**: HP回復

```http
POST /character/heal
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "amount": 50,
  "type": "potion"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "old_hp": 85,
    "healed_amount": 35,
    "new_hp": 120,
    "max_hp": 120,
    "overheal": 0
  },
  "message": "HP restored successfully"
}
```

#### POST /character/restore-mp
**概要**: MP回復

```http
POST /character/restore-mp
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "amount": 30
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "old_mp": 45,
    "restored_amount": 30,
    "new_mp": 75,
    "max_mp": 80
  },
  "message": "MP restored successfully"
}
```

---

## 6. インベントリ・装備API

### 6.1 インベントリ管理

#### GET /inventory
**概要**: インベントリ情報取得

```http
GET /inventory
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "inventory": {
      "character_id": 1,
      "max_slots": 20,
      "used_slots": 8,
      "available_slots": 12,
      "items": [
        {
          "slot": 0,
          "item": {
            "id": 1,
            "name": "薬草",
            "description": "HPを回復する薬草",
            "category": "potion",
            "rarity": 1,
            "rarity_name": "コモン",
            "effects": {"heal_hp": 50}
          },
          "quantity": 5,
          "durability": null
        },
        {
          "slot": 3,
          "item": {
            "id": 15,
            "name": "鉄の剣",
            "description": "頑丈な鉄製の剣",
            "category": "weapon",
            "rarity": 2,
            "rarity_name": "アンコモン",
            "effects": {"attack": 8}
          },
          "quantity": 1,
          "durability": 87
        }
      ]
    }
  }
}
```

#### POST /inventory/use-item
**概要**: アイテム使用

```http
POST /inventory/use-item
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "slot": 0,
  "quantity": 1,
  "target": "self"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "item_used": {
      "name": "薬草",
      "quantity_used": 1,
      "remaining_quantity": 4
    },
    "effects_applied": {
      "hp_healed": 50,
      "new_hp": 120
    },
    "inventory_updated": true
  },
  "message": "薬草を使用してHPが50回復した"
}
```

#### POST /inventory/move-item
**概要**: アイテム移動

```http
POST /inventory/move-item
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "from_slot": 0,
  "to_slot": 5,
  "quantity": 2
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "moved_item": {
      "name": "薬草",
      "quantity_moved": 2
    },
    "from_slot": {
      "slot": 0,
      "remaining_quantity": 3
    },
    "to_slot": {
      "slot": 5,
      "new_quantity": 2
    }
  },
  "message": "Item moved successfully"
}
```

### 6.2 装備管理

#### GET /equipment
**概要**: 装備情報取得

```http
GET /equipment
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "equipment": {
      "character_id": 1,
      "equipped_items": {
        "weapon": {
          "id": 15,
          "name": "鉄の剣",
          "effects": {"attack": 8},
          "durability": 87,
          "max_durability": 100
        },
        "body_armor": {
          "id": 23,
          "name": "革の鎧",
          "effects": {"defense": 5},
          "durability": 92,
          "max_durability": 100
        },
        "shield": null,
        "helmet": null,
        "boots": null,
        "accessory": null
      },
      "total_bonuses": {
        "attack": 8,
        "defense": 5,
        "agility": 0,
        "magic_attack": 0
      }
    }
  }
}
```

#### POST /equipment/equip
**概要**: 装備着用

```http
POST /equipment/equip
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "item_id": 25,
  "slot": "helmet",
  "from_inventory_slot": 7
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "equipped_item": {
      "id": 25,
      "name": "鉄の兜",
      "slot": "helmet",
      "effects": {"defense": 3}
    },
    "previous_item": null,
    "stat_changes": {
      "defense": {
        "old": 18,
        "new": 21,
        "change": 3
      }
    },
    "inventory_updated": true
  },
  "message": "鉄の兜を装備した"
}
```

#### POST /equipment/unequip
**概要**: 装備解除

```http
POST /equipment/unequip
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "slot": "helmet",
  "to_inventory_slot": 12
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "unequipped_item": {
      "id": 25,
      "name": "鉄の兜",
      "slot": "helmet"
    },
    "stat_changes": {
      "defense": {
        "old": 21,
        "new": 18,
        "change": -3
      }
    },
    "moved_to_inventory_slot": 12
  },
  "message": "鉄の兜を装備解除した"
}
```

---

## 7. スキル管理API

### 7.1 スキル情報・使用

#### GET /skills
**概要**: スキル一覧取得

```http
GET /skills
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "skills": [
      {
        "id": 1,
        "skill_name": "飛脚術",
        "skill_type": "movement",
        "level": 3,
        "experience": 45,
        "sp_cost": 10,
        "effects": {
          "dice_bonus": 3,
          "extra_dice": 1
        },
        "is_active": true,
        "can_use": true
      },
      {
        "id": 2,
        "skill_name": "採集",
        "skill_type": "gathering",
        "level": 5,
        "experience": 120,
        "sp_cost": 8,
        "effects": {
          "gathering_bonus": 5,
          "rare_chance": 0.1
        },
        "is_active": true,
        "can_use": true
      }
    ],
    "total_skill_level": 8,
    "character_level": 1
  }
}
```

#### POST /skills/use
**概要**: スキル使用

```http
POST /skills/use
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "skill_id": 1,
  "target": "self",
  "context": "movement"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "skill_used": {
      "name": "飛脚術",
      "sp_cost": 10
    },
    "effects_applied": {
      "dice_bonus": 3,
      "extra_dice": 1,
      "duration": 1
    },
    "character_state": {
      "sp": 20,
      "max_sp": 30
    },
    "skill_experience": {
      "gained": 5,
      "new_total": 50,
      "level_up": false
    }
  },
  "message": "飛脚術を使用した！移動能力が向上した"
}
```

---

## 8. ショップAPI

### 8.1 ショップ管理

#### GET /shops
**概要**: 利用可能ショップ一覧

```http
GET /shops?location_type=town&location_id=town_a
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "location": {
      "type": "town",
      "id": "town_a",
      "name": "始まりの町"
    },
    "shops": [
      {
        "id": 1,
        "name": "アイテムショップ",
        "shop_type": "ITEM_SHOP",
        "description": "ポーションや消耗品を販売",
        "icon": "🛒"
      },
      {
        "id": 2,
        "name": "鍛冶屋",
        "shop_type": "BLACKSMITH",
        "description": "武器・防具を販売・修理",
        "icon": "⚒️"
      },
      {
        "id": 3,
        "name": "錬金屋",
        "shop_type": "ALCHEMY_SHOP",
        "description": "武器・防具を素材で強化",
        "icon": "⚗️"
      }
    ]
  }
}
```

#### GET /shops/{shopId}/items
**概要**: ショップ商品一覧

```http
GET /shops/1/items
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "shop": {
      "id": 1,
      "name": "アイテムショップ",
      "shop_type": "ITEM_SHOP"
    },
    "items": [
      {
        "id": 1,
        "name": "薬草",
        "description": "HPを50回復する",
        "category": "potion",
        "price": 50,
        "stock": -1,
        "rarity": 1,
        "effects": {"heal_hp": 50}
      },
      {
        "id": 2,
        "name": "マジックポーション",
        "description": "MPを30回復する",
        "category": "potion", 
        "price": 80,
        "stock": 15,
        "rarity": 2,
        "effects": {"heal_mp": 30}
      }
    ],
    "player_gold": 1250
  }
}
```

#### POST /shops/{shopId}/buy
**概要**: アイテム購入

```http
POST /shops/1/buy
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "item_id": 1,
  "quantity": 3
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "purchase": {
      "item_name": "薬草",
      "quantity": 3,
      "unit_price": 50,
      "total_cost": 150
    },
    "player_state": {
      "old_gold": 1250,
      "new_gold": 1100,
      "gold_spent": 150
    },
    "inventory": {
      "item_added_to_slot": 8,
      "new_quantity": 3
    }
  },
  "message": "薬草を3個購入した"
}
```

#### POST /shops/{shopId}/sell
**概要**: アイテム売却

```http
POST /shops/1/sell
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "inventory_slot": 5,
  "quantity": 2
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "sale": {
      "item_name": "鉄鉱石",
      "quantity": 2,
      "unit_price": 30,
      "total_earned": 60
    },
    "player_state": {
      "old_gold": 1100,
      "new_gold": 1160,
      "gold_earned": 60
    },
    "inventory": {
      "slot_updated": 5,
      "remaining_quantity": 3
    }
  },
  "message": "鉄鉱石を2個売却した"
}
```

### 8.2 錬金ショップAPI

#### GET /shops/alchemy
**概要**: 錬金ショップ画面データ取得

```http
GET /shops/alchemy
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "shop": {
      "id": 3,
      "name": "A町の錬金屋",
      "shop_type": "ALCHEMY_SHOP",
      "description": "古い錬金術の秘伝で武器・防具を強化いたします。"
    },
    "player": {
      "id": 1,
      "name": "冒険者",
      "gold": 1500,
      "location_id": "town_a"
    },
    "alchemizable_items": [
      {
        "slot": 0,
        "item": {
          "id": 15,
          "name": "鉄の剣",
          "category": "weapon",
          "durability": 85,
          "max_durability": 100,
          "effects": {"attack": 12, "accuracy": 85}
        },
        "quantity": 1
      }
    ],
    "material_items": [
      {
        "slot": 3,
        "item": {
          "id": 25,
          "name": "鉄鉱石",
          "category": "material"
        },
        "quantity": 5
      },
      {
        "slot": 7,
        "item": {
          "id": 26,
          "name": "動物の爪",
          "category": "material"
        },
        "quantity": 2
      }
    ],
    "available_materials": [
      {
        "item_name": "鉄鉱石",
        "stat_bonuses": {"attack": 2, "defense": 1},
        "durability_bonus": 10,
        "masterwork_chance_bonus": 1.5
      },
      {
        "item_name": "動物の爪",
        "stat_bonuses": {"attack": 3, "defense": 2},
        "durability_bonus": 5,
        "masterwork_chance_bonus": 2.5
      }
    ]
  }
}
```

#### POST /shops/alchemy/preview
**概要**: 錬金結果プレビュー取得

```http
POST /shops/alchemy/preview
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "base_item_slot": 0,
  "material_slots": [3, 7]
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "base_item": {
      "id": 15,
      "name": "鉄の剣",
      "effects": {"attack": 12, "accuracy": 85},
      "durability": 85,
      "max_durability": 100
    },
    "material_effects": {
      "combined_stats": {"attack": 5, "defense": 3},
      "combined_durability_bonus": 15,
      "total_masterwork_chance": 9.0
    },
    "estimated_stats": {
      "normal": {
        "min": {"attack": 15, "defense": 3, "accuracy": 85},
        "max": {"attack": 19, "defense": 3, "accuracy": 85}
      },
      "masterwork": {
        "min": {"attack": 20, "defense": 4, "accuracy": 85},
        "max": {"attack": 26, "defense": 4, "accuracy": 85}
      },
      "masterwork_chance": 9.0
    }
  }
}
```

#### POST /shops/alchemy/perform
**概要**: 錬金実行

```http
POST /shops/alchemy/perform
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "base_item_slot": 0,
  "material_slots": [3, 7]
}
```

**成功レスポンス**:
```json
{
  "success": true,
  "data": {
    "custom_item": {
      "id": 101,
      "name": "強化された鉄の剣",
      "description": "素材により強化された鉄の剣。攻撃力が向上している。",
      "category": "weapon",
      "effects": {"attack": 18, "defense": 3, "accuracy": 85},
      "durability": 100,
      "max_durability": 100,
      "is_custom": true,
      "is_masterwork": false
    },
    "is_masterwork": false,
    "material_effects": {
      "combined_stats": {"attack": 5, "defense": 3},
      "combined_durability_bonus": 15,
      "total_masterwork_chance": 9.0
    },
    "final_stats": {"attack": 18, "defense": 3, "accuracy": 85}
  },
  "message": "錬金が成功しました！"
}
```

**名匠品成功レスポンス**:
```json
{
  "success": true,
  "data": {
    "custom_item": {
      "id": 102,
      "name": "【名匠品】強化された鉄の剣",
      "description": "素材により強化された鉄の剣。名匠の技により極限まで強化されている。",
      "category": "weapon",
      "effects": {"attack": 23, "defense": 4, "accuracy": 85},
      "durability": 100,
      "max_durability": 100,
      "is_custom": true,
      "is_masterwork": true
    },
    "is_masterwork": true,
    "material_effects": {
      "combined_stats": {"attack": 5, "defense": 3},
      "combined_durability_bonus": 15,
      "total_masterwork_chance": 9.0
    },
    "final_stats": {"attack": 23, "defense": 4, "accuracy": 85}
  },
  "message": "【名匠品】錬金が成功しました！"
}
```

**エラーレスポンス**:
```json
{
  "success": false,
  "error": {
    "code": "ALCHEMY_ERROR",
    "message": "カスタムアイテムは錬金できません。",
    "details": {
      "error_type": "invalid_base_item",
      "base_item_slot": 0
    }
  }
}
```

---

## 9. 採集システムAPI

### 9.1 採集管理

#### GET /gathering/info
**概要**: 採集可能情報取得

```http
GET /gathering/info?location=road_1
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "location": {
      "id": "road_1",
      "name": "森の道",
      "gathering_available": true
    },
    "available_items": [
      {
        "name": "薬草",
        "required_skill_level": 1,
        "success_rate": 80,
        "quantity_range": [1, 2],
        "rarity": 1
      },
      {
        "name": "木の枝",
        "required_skill_level": 1,
        "success_rate": 90,
        "quantity_range": [1, 3],
        "rarity": 1
      },
      {
        "name": "きのこ",
        "required_skill_level": 3,
        "success_rate": 60,
        "quantity_range": [1, 1],
        "rarity": 2
      }
    ],
    "player_info": {
      "gathering_skill_level": 5,
      "sp": 30,
      "sp_cost": 8,
      "can_gather": true
    }
  }
}
```

#### POST /gathering/gather
**概要**: 採集実行

```http
POST /gathering/gather
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "location": "road_1"
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "gathering_result": {
      "success": true,
      "item_obtained": {
        "name": "きのこ",
        "quantity": 1,
        "rarity": 2,
        "rarity_name": "アンコモン"
      },
      "sp_consumed": 8,
      "experience_gained": 8
    },
    "player_state": {
      "sp": 22,
      "max_sp": 30
    },
    "skill_progress": {
      "gathering_skill_experience": 128,
      "level_up": false
    },
    "inventory": {
      "item_added_to_slot": 15,
      "slot_quantity": 1
    }
  },
  "message": "採集に成功！きのこを1個獲得した"
}
```

---

## 10. 同期・分析システムAPI

### 10.1 デバイス同期

#### GET /sync/state
**概要**: ゲーム状態同期データ取得

```http
GET /sync/state
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "sync_data": {
      "character_state": {
        "hp": 85,
        "mp": 45,
        "sp": 30,
        "location_type": "town",
        "location_id": "town_a",
        "position": 0
      },
      "last_sync": "2025-07-25T10:30:00Z",
      "device_info": {
        "last_device": "mobile",
        "last_ip": "192.168.1.100"
      }
    }
  }
}
```

#### POST /sync/device
**概要**: デバイス状態同期

```http
POST /sync/device
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "device_type": "desktop",
  "sync_data": {
    "location": "town_b",
    "last_action": "shop_visit",
    "timestamp": "2025-07-25T10:35:00Z"
  }
}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "sync_completed": true,
    "conflicts_resolved": 0,
    "updated_fields": ["device_type", "last_active_at"]
  },
  "message": "Device sync completed"
}
```

### 10.2 分析データ

#### GET /analytics/user
**概要**: ユーザー分析データ取得

```http
GET /analytics/user
Authorization: Bearer {session_token}
```

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "user_analytics": {
      "play_stats": {
        "total_play_time": 7200,
        "sessions_count": 25,
        "average_session_time": 288
      },
      "battle_stats": {
        "battles_fought": 45,
        "victories": 38,
        "defeats": 7,
        "win_rate": 0.844
      },
      "progression": {
        "character_level": 5,
        "total_experience": 125,
        "skills_learned": 3,
        "total_gold_earned": 2500
      }
    }
  }
}
```

---

## 11. エラーハンドリング

### 11.1 標準エラー形式

#### バリデーションエラー (422)
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  },
  "timestamp": "2025-07-25T10:30:00Z"
}
```

#### 認証エラー (401)
```json
{
  "success": false,
  "error": {
    "code": "AUTHENTICATION_ERROR",
    "message": "Unauthenticated",
    "details": {
      "reason": "session_expired"
    }
  },
  "timestamp": "2025-07-25T10:30:00Z"
}
```

#### 権限エラー (403)
```json
{
  "success": false,
  "error": {
    "code": "AUTHORIZATION_ERROR", 
    "message": "This action is unauthorized",
    "details": {
      "required_permission": "character_access",
      "resource": "character_1"
    }
  },
  "timestamp": "2025-07-25T10:30:00Z"
}
```

#### ゲーム状態エラー (400)
```json
{
  "success": false,
  "error": {
    "code": "GAME_STATE_ERROR",
    "message": "Invalid game state for this action",
    "details": {
      "current_state": "in_battle",
      "required_state": "in_town",
      "action": "shop_access"
    }
  },
  "timestamp": "2025-07-25T10:30:00Z"
}
```

#### リソース不足エラー (400)
```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_RESOURCES",
    "message": "Not enough resources to perform this action",
    "details": {
      "resource_type": "sp",
      "required": 10,
      "available": 5
    }
  },
  "timestamp": "2025-07-25T10:30:00Z"
}
```

### 11.2 エラーコード一覧

| コード | 説明 | HTTPステータス |
|--------|------|---------------|
| `VALIDATION_ERROR` | 入力検証エラー | 422 |
| `AUTHENTICATION_ERROR` | 認証エラー | 401 |
| `AUTHORIZATION_ERROR` | 権限エラー | 403 |
| `GAME_STATE_ERROR` | ゲーム状態エラー | 400 |
| `INSUFFICIENT_RESOURCES` | リソース不足 | 400 |
| `BATTLE_ERROR` | 戦闘関連エラー | 400 |
| `INVENTORY_FULL` | インベントリ満杯 | 400 |
| `ITEM_NOT_FOUND` | アイテム未発見 | 404 |
| `SHOP_UNAVAILABLE` | ショップ利用不可 | 400 |
| `RATE_LIMIT_EXCEEDED` | レート制限超過 | 429 |
| `SERVER_ERROR` | サーバー内部エラー | 500 |

---

## 12. レート制限・セキュリティ

### 12.1 レート制限

#### API別制限
```
認証API: 5 requests/minute
ゲーム操作API: 60 requests/minute  
戦闘API: 10 requests/minute
ショップAPI: 30 requests/minute
分析API: 20 requests/minute
```

#### 制限超過レスポンス
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests",
    "details": {
      "limit": 60,
      "remaining": 0,
      "reset_time": "2025-07-25T10:31:00Z"
    }
  },
  "timestamp": "2025-07-25T10:30:00Z"
}
```

### 12.2 セキュリティヘッダー

#### 必須ヘッダー
```http
X-CSRF-TOKEN: {csrf_token}  # CSRF保護
X-Requested-With: XMLHttpRequest  # AJAX識別
```

#### 推奨ヘッダー
```http
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
```

---

## 13. API使用例

### 13.1 ゲームプレイフロー

#### 基本的なゲームセッション
```javascript
// 1. ゲーム画面データ取得
const gameData = await fetch('/game', {
    headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
    }
});

// 2. サイコロを振る
const diceResult = await fetch('/game/roll-dice', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({dice_count: 3})
});

// 3. 移動実行
const moveResult = await fetch('/game/move', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        direction: 'right',
        distance: diceResult.data.final_total
    })
});

// 4. 戦闘発生時の処理
if (moveResult.data.encounter?.occurred) {
    const battleResult = await fetch('/battle/attack', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            battle_id: moveResult.data.encounter.battle_id,
            attack_type: 'normal'
        })
    });
}
```

### 13.2 エラーハンドリング例

```javascript
async function makeApiCall(url, options) {
    try {
        const response = await fetch(url, options);
        const data = await response.json();
        
        if (!data.success) {
            switch (data.error.code) {
                case 'INSUFFICIENT_RESOURCES':
                    showMessage(`SP不足: ${data.error.details.required}必要`);
                    break;
                case 'GAME_STATE_ERROR':
                    showMessage('この状態では実行できません');
                    break;
                case 'AUTHENTICATION_ERROR':
                    window.location.href = '/login';
                    break;
                default:
                    showMessage(data.error.message);
            }
            return null;
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        showMessage('通信エラーが発生しました');
        return null;
    }
}
```

---

このAPI設計により、test_smgは一貫性のある、拡張可能で、安全なゲームAPIを提供し、フロントエンドとの効率的な連携を実現しています。RESTful原則に従いつつ、ゲーム特有の要件（リアルタイム性、状態管理、リソース管理）に対応した設計となっています。

**2025年7月29日更新内容**:
- 錬金ショップAPI追加（GET /shops/alchemy, POST /shops/alchemy/preview, POST /shops/alchemy/perform）
- ショップ一覧にALCHEMY_SHOP追加
- 錬金システム専用レスポンス形式定義

**最終更新**: 2025年7月29日  
**次回レビュー**: API仕様変更時または新機能追加時