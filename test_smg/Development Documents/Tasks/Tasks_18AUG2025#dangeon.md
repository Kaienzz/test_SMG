# ダンジョン管理システム ベストプラクティス & 実装詳細

## 概要

ダンジョンは道路と同様の移動機能を持ちながら、特殊なアクション（ボス戦、ワープ）とダンジョン固有の要素を管理する必要があります。
現在のロケーション管理システムと統合しつつ、管理を簡易化するためのベストプラクティスを提案します。

## 現状分析

### 現在のシステム構成

#### コアシステム
- **LocationService**: road, town, dungeon の3つのロケーションタイプを管理
- **JSON設定**: roads, towns, dungeons の3セクション
- **移動機能**: 道路のみ詳細な移動システム（position 0-100, branches, connections）
- **GameController**: LocationService、GameDisplayService、GameStateManager で構成

#### 戦闘・エンカウントシステム
- **EncounterData DTO**: モンスターエンカウントデータの統一管理
- **Monster Model**: spawn_roads配列でエンカウント可能な道路を管理
- **BattleMonsterData DTO**: 戦闘用モンスターデータ
- **AdminMonsterController**: モンスター管理機能

#### プレイヤーデータ管理
- **Player Model**: 
  - 位置情報: `location_type`, `location_id`, `game_position`
  - 拡張データ: `location_data`, `player_data`, `game_data` (JSON)
  - 最後の町: `last_visited_town`

### 要件
1. ダンジョンは道と同じ移動機能を持つ
2. 管理の簡易化のため道とダンジョンを区別
3. ダンジョン内に複数の道（dungeon_road1～10）
4. モンスターエンカウント
5. 特定位置での特殊アクション（ボス戦、ワープ）

### 重要な制約・考慮事項
1. **既存データの互換性**: 現在のプレイヤーデータとの互換性維持
2. **エンカウントシステム統合**: Monster.spawn_roads と新しいダンジョン道路の連携
3. **JSONキャッシュ**: LocationConfigService のキャッシュ機能との整合性
4. **管理画面統合**: 既存のAdminLocationController との統合

## 推奨アプローチ: ハイブリッドダンジョンシステム

### 1. データ構造設計

#### 1.1 JSON設定ファイル構造
```json
{
  "dungeons": {
    "dungeon_1": {
      "name": "古の洞窟",
      "description": "古代の秘密が眠る深い洞窟",
      "type": "cave",
      "difficulty": "normal",
      "floors": 3,
      "entrance": {
        "type": "town",
        "id": "town_prima",
        "connection_point": "north"
      },
      "dungeon_roads": {
        "dungeon_1_road_1": {
          "name": "洞窟入口通路",
          "floor": 1,
          "length": 100,
          "difficulty": "easy",
          "encounter_rate": 0.15,
          "connections": {
            "start": {"type": "dungeon_entrance", "id": "dungeon_1"},
            "end": {"type": "dungeon_road", "id": "dungeon_1_road_2"}
          },
          "special_actions": {
            "50": {
              "type": "treasure_chest",
              "name": "古い宝箱",
              "condition": "none",
              "action": "item_treasure",
              "data": {
                "items": ["healing_potion", "gold_100"]
              }
            }
          }
        },
        "dungeon_1_road_2": {
          "name": "深い通路",
          "floor": 2,
          "length": 100,
          "difficulty": "normal",
          "encounter_rate": 0.25,
          "connections": {
            "start": {"type": "dungeon_road", "id": "dungeon_1_road_1"},
            "end": {"type": "dungeon_road", "id": "dungeon_1_road_3"}
          },
          "branches": {
            "75": {
              "straight": {"type": "dungeon_road", "id": "dungeon_1_road_3"},
              "right": {"type": "dungeon_road", "id": "dungeon_1_secret_room"}
            }
          }
        },
        "dungeon_1_road_3": {
          "name": "ボスの間への道",
          "floor": 3,
          "length": 100,
          "difficulty": "hard",
          "encounter_rate": 0.3,
          "connections": {
            "start": {"type": "dungeon_road", "id": "dungeon_1_road_2"},
            "end": {"type": "dungeon_boss_room", "id": "dungeon_1_boss"}
          },
          "special_actions": {
            "90": {
              "type": "boss_gate",
              "name": "ボスの間への扉",
              "condition": "boss_key_required",
              "action": "boss_battle_entrance",
              "data": {
                "boss_id": "cave_guardian",
                "min_level": 5
              }
            }
          }
        },
        "dungeon_1_secret_room": {
          "name": "隠し部屋",
          "floor": 2,
          "length": 50,
          "difficulty": "normal",
          "encounter_rate": 0.0,
          "connections": {
            "start": {"type": "dungeon_road", "id": "dungeon_1_road_2"},
            "end": {"type": "warp_point", "destination": "town_prima"}
          },
          "special_actions": {
            "25": {
              "type": "warp_portal",
              "name": "脱出ポータル",
              "condition": "none",
              "action": "teleport",
              "data": {
                "destination_type": "town",
                "destination_id": "town_prima",
                "cost": 0
              }
            }
          }
        }
      },
      "boss_rooms": {
        "dungeon_1_boss": {
          "name": "守護者の間",
          "boss": "Cave Guardian",
          "min_level": 5,
          "max_level": 15,
          "rewards": ["ancient_artifacts", "gems", "rare_materials"],
          "exit": {
            "type": "town",
            "id": "town_prima"
          }
        }
      }
    }
  }
}
```

#### 1.2 特殊アクションタイプ定義
```json
"special_action_types": {
  "treasure_chest": {
    "requires_interaction": true,
    "consumable": true,
    "rewards": "items"
  },
  "boss_gate": {
    "requires_condition": true,
    "blocks_passage": true,
    "action": "boss_battle"
  },
  "warp_portal": {
    "requires_interaction": true,
    "consumable": false,
    "action": "teleport"
  },
  "trap": {
    "requires_interaction": false,
    "automatic": true,
    "action": "damage_or_debuff"
  },
  "switch": {
    "requires_interaction": true,
    "affects_dungeon_state": true,
    "action": "state_change"
  }
}
```

### 2. LocationService拡張設計

#### 2.1 新しいメソッド追加
```php
// ダンジョン関連メソッド
public function getDungeonRoads(string $dungeonId): array
public function getDungeonRoadData(string $dungeonId, string $roadId): ?array
public function getSpecialActionsAt(string $roadId, int $position): array
public function canExecuteSpecialAction(Player $player, array $action): bool
public function executeSpecialAction(Player $player, array $action): array

// ダンジョン移動関連
public function getNextLocationFromDungeonRoad(string $roadId, int $position): ?array
public function calculateDungeonMovement(Player $player, int $steps, string $direction): array
public function getDungeonFloorInfo(string $dungeonId, int $floor): array
```

#### 2.2 既存メソッドの拡張
```php
public function getNextLocation(Player $player): ?array
{
    $locationType = $player->location_type ?? 'town';
    $locationId = $player->location_id ?? 'town_prima';
    $position = $player->game_position ?? 0;
    
    return match($locationType) {
        'town' => $this->getNextLocationFromTown($locationId),
        'road' => $this->getNextLocationFromRoad($locationId, $position),
        'dungeon_road' => $this->getNextLocationFromDungeonRoad($locationId, $position),
        'dungeon' => $this->getNextLocationFromDungeon($locationId),
        default => null
    };
}
```

### 3. 管理画面設計

#### 3.1 ダンジョン管理メニュー構成
```
admin/locations/dungeons/
├── index              # ダンジョン一覧
├── create             # 新規ダンジョン作成
├── {dungeonId}/edit   # ダンジョン基本情報編集
├── {dungeonId}/roads  # ダンジョン内道路管理
└── {dungeonId}/actions # 特殊アクション管理
```

#### 3.2 ダンジョン内道路管理
- ダンジョンごとの道路一覧表示
- フロア別での道路表示
- 道路間の接続関係の視覚化
- 特殊アクション位置の表示

#### 3.3 特殊アクション管理
- 位置ベースでのアクション配置
- アクションタイプ選択（ボス戦、宝箱、ワープ、トラップ）
- 条件設定（レベル、アイテム、フラグ）
- 結果設定（報酬、移動先、状態変化）

### 4. 必須修正ポイント詳細分析

#### 4.1 **CRITICAL**: Player Modelの拡張
**現状の問題**: `location_type` は `'town'`, `'road'`, `'dungeon'` のみ対応
**修正内容**:
```php
// location_type の新しい値
'dungeon_road'    // ダンジョン内道路
'dungeon_room'    // ダンジョン内特殊部屋
'dungeon_boss'    // ボス部屋

// location_data JSON フィールドの活用例
{
  "dungeon_id": "dungeon_1",
  "current_floor": 2,
  "visited_rooms": ["dungeon_1_road_1", "dungeon_1_road_2"],
  "completed_actions": ["treasure_chest_pos_50"],
  "boss_defeated": false,
  "entry_time": "2025-08-18T10:30:00Z"
}
```

#### 4.2 **CRITICAL**: LocationService の大幅拡張
**現状の問題**: `getNextLocation()` がダンジョン道路に対応していない

**必要な新メソッド**:
```php
// ダンジョン専用メソッド
public function getDungeonContext(string $dungeonId): DungeonContext
public function getNextLocationFromDungeonRoad(string $roadId, int $position): ?array
public function calculateDungeonMovement(Player $player, int $steps, string $direction): array
public function getSpecialActionsAt(string $roadId, int $position): array
public function executeSpecialAction(Player $player, array $action): SpecialActionResult
public function canAccessDungeonFloor(Player $player, int $floor): bool
public function getDungeonMapData(string $dungeonId): array

// 既存メソッドの修正
public function getNextLocation(Player $player): ?array {
    // 'dungeon_road' case の追加が必要
}

public function calculateMovement(Player $player, int $steps, string $direction): array {
    // ダンジョン道路での特殊アクション自動停止処理
}
```

#### 4.3 **HIGH**: Monster Model の spawn_roads 拡張
**現状の問題**: spawn_roads は通常道路のみ想定
**修正内容**:
```php
// Monster Model 新フィールド
'spawn_dungeon_roads' => 'array',  // ダンジョン道路での出現設定
'spawn_dungeons' => 'array',       // 出現可能ダンジョン一覧
'boss_of_dungeon' => 'string',     // ボスモンスターの場合のダンジョンID

// 例: Monster データ
{
  "spawn_roads": ["road_1", "road_2"],
  "spawn_dungeon_roads": ["dungeon_1_road_1", "dungeon_1_road_2"],
  "spawn_dungeons": ["dungeon_1", "dungeon_2"],
  "boss_of_dungeon": "dungeon_1"  // ボス専用
}
```

#### 4.4 **HIGH**: EncounterData DTO の拡張
**修正内容**:
```php
// EncounterData に新フィールド追加
public readonly string $encounter_context;  // 'normal', 'boss', 'special_action'
public readonly ?string $special_action_id; // 特殊アクション由来の戦闘の場合
public readonly array $action_metadata;     // 特殊アクション関連データ

// encounter_type の拡張
'boss_battle'      // ボス戦
'triggered_battle' // 特殊アクション由来の戦闘
'ambush'          // 待ち伏せ（トラップ）
'scripted'        // イベント戦闘
```

#### 4.5 **MEDIUM**: LocationConfigService のキャッシュ対応
**現状の問題**: ダンジョンデータが大容量になった場合のパフォーマンス
**修正内容**:
```php
// キャッシュ戦略の見直し
public function loadDungeonConfig(string $dungeonId): array
public function cacheDungeonRoads(string $dungeonId): void
public function invalidateDungeonCache(string $dungeonId): void

// 部分的ロード機能
public function loadDungeonFloor(string $dungeonId, int $floor): array
public function loadDungeonRoad(string $roadId): array
```

### 5. 実装フェーズ（修正版）

#### **フェーズ1: 基盤拡張** (所要時間: 3-4日)
**優先度: CRITICAL**

1. **Player Model & Migration**
   ```sql
   -- 新しいマイグレーション
   ALTER TABLE players MODIFY location_type ENUM('town', 'road', 'dungeon', 'dungeon_road', 'dungeon_room', 'dungeon_boss');
   ```

2. **LocationService コア拡張**
   - `getNextLocation()` のダンジョン対応
   - 新しいダンジョン専用メソッド実装
   - 特殊アクション基盤システム

3. **JSON Schema 拡張**
   - dungeon_roads セクション
   - special_actions スキーマ
   - バリデーション機能

#### **フェーズ2: エンカウント統合** (所要時間: 2-3日)
**優先度: HIGH**

1. **Monster Model 拡張**
   - spawn_dungeon_roads フィールド追加
   - ボス設定機能
   - マイグレーション実行

2. **EncounterData DTO 拡張**
   - 新しい encounter_type 対応
   - 特殊アクション連携機能

3. **AdminMonsterController 更新**
   - ダンジョン出現設定UI
   - ボス設定機能

#### **フェーズ3: 管理画面実装** (所要時間: 4-5日)
**優先度: HIGH**

1. **AdminLocationController 拡張**
   ```php
   // 新しいルート
   admin/locations/dungeons/{dungeonId}/roads
   admin/locations/dungeons/{dungeonId}/floors
   admin/locations/dungeons/{dungeonId}/actions
   admin/locations/dungeons/{dungeonId}/preview
   ```

2. **ダンジョン専用管理画面**
   - ダンジョン内道路CRUD
   - フロア別管理機能
   - 特殊アクション配置ツール
   - ダンジョンマップのビジュアル表示

3. **バリデーション強化**
   - ダンジョン内接続の整合性チェック
   - 特殊アクションの条件検証
   - 循環参照検出

#### **フェーズ4: ゲームロジック統合** (所要時間: 3-4日)
**優先度: MEDIUM**

1. **GameController 拡張**
   - ダンジョン移動処理
   - 特殊アクション実行API
   - ダンジョン状態管理

2. **フロントエンド実装**
   - ダンジョンマップUI
   - 特殊アクションボタン
   - フロア表示機能

3. **特殊システム**
   - セーブポイント機能
   - ダンジョン脱出機能
   - プログレス管理

#### **フェーズ5: テスト・最適化** (所要時間: 2-3日)
**優先度: MEDIUM**

1. **包括的テスト**
   - ダンジョン移動の結合テスト
   - 特殊アクション動作検証
   - パフォーマンステスト

2. **キャッシュ最適化**
   - ダンジョンデータのキャッシュ戦略
   - メモリ使用量最適化

3. **エラーハンドリング**
   - ダンジョン固有のエラー処理
   - 不正な状態からの回復機能

### 5. 利点

#### 5.1 管理の簡易化
- ダンジョンごとに道路をグループ化
- 特殊アクションの一元管理
- フロア概念による階層管理

#### 5.2 既存システムとの親和性
- 道路システムの再利用
- LocationServiceの一貫した拡張
- JSON設定ファイルの統一的な管理

#### 5.3 将来の拡張性
- 新しい特殊アクションタイプの追加が容易
- 複雑なダンジョンギミックへの対応
- イベント系アクションの実装可能

### 6. 重要な注意点・制約事項

#### 6.1 **セキュリティ考慮事項** 🔒
**CRITICAL**
1. **特殊アクション実行時の検証**
   ```php
   // 必須チェック項目
   - プレイヤーが実際にその位置にいるか
   - アクション実行の前提条件を満たしているか
   - 不正なデータ送信（位置改竄等）の防止
   - JSON データインジェクション対策
   ```

2. **ダンジョン進行状態の検証**
   ```php
   // location_data の検証
   public function validateDungeonProgress(Player $player, array $requestedAction): bool {
       // 訪問済みフロアの整合性チェック
       // 実行済みアクションの重複チェック
       // 不正な飛び越しの検出
   }
   ```

3. **管理画面での権限制御**
   ```php
   // AdminLocationController
   - ダンジョン編集権限の厳密なチェック
   - 特殊アクション設定時のバリデーション強化
   - JSONデータの安全性検証
   ```

#### 6.2 **パフォーマンス最適化** ⚡
**HIGH Priority**

1. **JSON データサイズ管理**
   ```php
   // 想定データサイズ計算
   $avgDungeonSize = 10; // 道路数
   $avgActionsPerRoad = 3; // 特殊アクション数
   $estimatedJsonSize = $avgDungeonSize * $avgActionsPerRoad * 500; // bytes
   
   // 5KB程度が目安、10KB超過時は分割を検討
   ```

2. **キャッシュ戦略詳細**
   ```php
   // Redis キャッシュ設計
   'dungeon_config:{dungeonId}' => ttl: 3600,     // 基本設定
   'dungeon_roads:{dungeonId}' => ttl: 1800,      // 道路データ
   'dungeon_actions:{roadId}' => ttl: 600,        // アクションデータ
   
   // メモリ使用量制限
   $maxCachedDungeons = 20; // 同時キャッシュ可能ダンジョン数
   ```

3. **クエリ最適化**
   ```sql
   -- Player テーブル インデックス追加必要
   CREATE INDEX idx_player_dungeon_location ON players(location_type, location_id, game_position);
   CREATE INDEX idx_player_location_data ON players(location_data); -- JSON型インデックス
   ```

#### 6.3 **データ整合性・整合性管理** 🔧
**HIGH Priority**

1. **ダンジョン接続関係の検証**
   ```php
   public function validateDungeonConnections(array $dungeonData): ValidationResult {
       // 循環参照の検出
       // 到達不可能な道路の検出
       // 出口のない道路の検出
       // フロア間の整合性チェック
   }
   ```

2. **特殊アクションデータ検証**
   ```php
   $requiredActionFields = [
       'treasure_chest' => ['items', 'rarity'],
       'boss_battle' => ['boss_id', 'min_level'],
       'warp_portal' => ['destination_type', 'destination_id'],
       'trap' => ['damage_type', 'damage_amount']
   ];
   ```

3. **マイグレーション・互換性**
   ```php
   // 既存プレイヤーデータとの互換性
   $legacyLocationTypes = ['town', 'road', 'dungeon'];
   $newLocationTypes = ['dungeon_road', 'dungeon_room', 'dungeon_boss'];
   
   // 段階的移行戦略
   public function migrateLegacyDungeonData(): void
   ```

#### 6.4 **UI/UX 制約事項** 🎨
**MEDIUM Priority**

1. **ダンジョンマップの表示制限**
   ```javascript
   // フロントエンド考慮事項
   const MAX_DISPLAYED_ROADS = 15;     // 1画面での最大表示道路数
   const MAX_FLOOR_LEVELS = 10;        // 最大フロア数
   const MOBILE_RESPONSIVE_BREAKPOINT = 768; // モバイル対応
   ```

2. **特殊アクションUI**
   ```php
   // アクションボタンのレスポンス時間制限
   $maxActionResponseTime = 3; // seconds
   
   // 同時実行制限
   $maxConcurrentActions = 1; // プレイヤーあたり
   ```

#### 6.5 **スケーラビリティ制約** 📈
**MEDIUM Priority**

1. **システム限界値の設定**
   ```php
   const MAX_DUNGEONS_PER_GAME = 50;
   const MAX_ROADS_PER_DUNGEON = 20;
   const MAX_ACTIONS_PER_ROAD = 5;
   const MAX_FLOORS_PER_DUNGEON = 15;
   const MAX_CONCURRENT_PLAYERS_IN_DUNGEON = 100;
   ```

2. **負荷分散考慮**
   ```php
   // ダンジョンデータの分割保存
   // 大容量ダンジョンの場合、フロア別ファイル分割を検討
   $splitThresholdSize = 50; // KB
   ```

### 7. テスト戦略詳細

#### 7.1 **単体テスト** 
```php
// 必須テストケース
DungeonLocationServiceTest:
  - testGetNextLocationFromDungeonRoad()
  - testCalculateDungeonMovement()
  - testSpecialActionExecution()
  - testDungeonFloorAccess()

DungeonValidationTest:
  - testConnectionIntegrity()
  - testActionDataValidation()
  - testPlayerProgressValidation()
```

#### 7.2 **統合テスト**
```php
DungeonIntegrationTest:
  - testFullDungeonExploration()
  - testBossDefeatSequence()
  - testWarpPortalFunction()
  - testTreasureChestCollection()
```

#### 7.3 **パフォーマンステスト**
```php
DungeonPerformanceTest:
  - testLargeDungeonLoading()      // >10KB JSON
  - testConcurrentPlayerAccess()   // 50+ players
  - testCacheEfficiency()
```

### 8. 段階的リリース戦略

#### 8.1 **アルファ版** (フェーズ1-2完了)
- 基本的なダンジョン道路移動機能
- 簡単な特殊アクション（宝箱のみ）
- 内部テスト専用

#### 8.2 **ベータ版** (フェーズ3完了)
- 管理画面でのダンジョン作成・編集
- ボス戦機能
- 限定ユーザーでのテスト

#### 8.3 **正式版** (フェーズ4-5完了)
- 全特殊アクション機能
- UI/UX完成版
- 一般ユーザーリリース

### 9. リスク管理・コンティンジェンシープラン

#### 9.1 **技術的リスク** ⚠️
1. **JSONサイズ肥大化**: フロア別分割方式への移行
2. **パフォーマンス劣化**: キャッシュ戦略の見直し
3. **データ破損**: 自動バックアップ・復旧機能

#### 9.2 **スケジュールリスク** ⏰
1. **開発遅延**: 機能の段階的リリース
2. **統合問題**: 既存システムへの影響最小化
3. **テスト不足**: 自動テストの強化

### 10. 成功指標・KPI

#### 10.1 **技術指標**
- ダンジョン読み込み時間: <500ms
- 特殊アクション実行時間: <200ms
- JSON解析時間: <50ms
- キャッシュヒット率: >90%

#### 10.2 **機能指標**
- ダンジョン作成成功率: >95%
- プレイヤー進行データ整合性: >99.9%
- 特殊アクション動作成功率: >98%

## 結論

この詳細化されたベストプラクティスドキュメントは、ダンジョンシステムの包括的な実装指針を提供します。

**重要なポイント**:
1. **段階的実装**: 既存システムへの影響を最小化
2. **セキュリティファースト**: データ改竄・不正アクセス対策を重視
3. **パフォーマンス考慮**: スケーラブルな設計
4. **徹底的なテスト**: 品質保証の確保

この設計により、道路の機能を継承しつつダンジョン特有の要素を効率的に管理できる、堅牢で拡張可能なシステムが構築できます。