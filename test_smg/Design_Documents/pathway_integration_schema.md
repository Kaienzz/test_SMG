# 道路・ダンジョン統合スキーマ設計

## 概要

ロケーション管理の簡易化のため、道路(roads)とダンジョン(dungeons)を統合し、単一の`pathways`セクションで管理する。

## 統合後のJSONスキーマ

### 基本構造

```json
{
  "version": "2.0.0",
  "last_updated": "2025-08-18T10:00:00Z",
  "description": "Location configuration for test_smg game",
  "pathways": {
    // 道路とダンジョンが統合された移動可能ロケーション
  },
  "towns": {
    // 町データ（変更なし）
  },
  "metadata": {
    // メタデータ
  }
}
```

### pathwaysセクション詳細

#### 共通フィールド（道路・ダンジョン共通）

```json
{
  "pathway_id": {
    "category": "road|dungeon",        // 必須：識別カテゴリー
    "name": "string",                  // 必須：表示名
    "description": "string",           // 説明
    "length": "integer",               // 長さ（0-100）
    "difficulty": "easy|normal|hard|extreme", // 難易度
    "encounter_rate": "float",         // エンカウント率（0.0-1.0）
    "connections": {                   // 接続情報
      "start": {"type": "string", "id": "string"},
      "end": {"type": "string", "id": "string"}
    },
    "branches": {                      // 分岐情報（オプション）
      "position": {
        "direction": {"type": "string", "id": "string"}
      }
    },
    "special_actions": {               // 特殊アクション（オプション）
      "position": {
        "type": "string",
        "name": "string", 
        "condition": "string",
        "action": "string",
        "data": "object"
      }
    }
  }
}
```

#### 道路固有フィールド（category: "road"）

道路の場合、共通フィールドのみで十分。追加フィールドは特になし。

#### ダンジョン固有フィールド（category: "dungeon"）

```json
{
  "dungeon_type": "cave|ruins|tower|underground", // ダンジョンタイプ
  "floors": "integer",               // フロア数
  "min_level": "integer",            // 最小推奨レベル
  "max_level": "integer",            // 最大推奨レベル
  "boss": "string",                  // ボス名
  "dungeon_roads": {                 // ダンジョン内道路（オプション）
    "dungeon_road_id": {
      "name": "string",
      "floor": "integer",
      "length": "integer",
      "difficulty": "string",
      "encounter_rate": "float",
      "connections": "object",
      "special_actions": "object"
    }
  },
  "boss_rooms": {                    // ボス部屋（オプション）
    "boss_room_id": {
      "name": "string",
      "boss": "string",
      "min_level": "integer",
      "max_level": "integer", 
      "rewards": "array",
      "exit": "object"
    }
  }
}
```

## 移行マッピング

### 現在のデータ → 統合データ

#### 道路移行例

```json
// 現在
"roads": {
  "road_1": {
    "name": "プリマ街道",
    "length": 100,
    "difficulty": "easy",
    "encounter_rate": 0.1
    // ...
  }
}

// 移行後
"pathways": {
  "road_1": {
    "category": "road",
    "name": "プリマ街道", 
    "length": 100,
    "difficulty": "easy",
    "encounter_rate": 0.1
    // ...
  }
}
```

#### ダンジョン移行例

```json
// 現在
"dungeons": {
  "dungeon_1": {
    "name": "古の洞窟",
    "length": 100,
    "difficulty": "normal",
    "encounter_rate": 0.25
    // ...
  }
}

// 移行後
"pathways": {
  "dungeon_1": {
    "category": "dungeon",
    "name": "古の洞窟",
    "length": 100, 
    "difficulty": "normal",
    "encounter_rate": 0.25,
    "dungeon_type": "cave",
    "floors": 1,
    "min_level": 1,
    "max_level": 10,
    "boss": "Cave Guardian"
    // ...
  }
}
```

## 管理画面での扱い

### フィルター・検索

- **カテゴリーフィルター**: "全て", "道路", "ダンジョン"
- **ダンジョンタイプフィルター**: "洞窟", "遺跡", "塔", "地下" (ダンジョンの場合のみ)
- **難易度フィルター**: "簡単", "普通", "困難", "極難"
- **ソート**: 名前、カテゴリー、難易度、フロア数等

### 表示項目

- 共通: ID, 名前, カテゴリー, 難易度, 長さ, エンカウント率
- ダンジョン固有: タイプ, フロア数, レベル制限, ボス

### 編集フォーム

- カテゴリー選択により、ダンジョン固有フィールドの表示/非表示を切り替え

## 後方互換性

- LocationService での読み込み時に自動変換
- 古いJSONフォーマットの自動検出と移行
- 移行時は自動バックアップ作成

## 実装ステップ

1. LocationConfigService の修正（新スキーマ対応）
2. データ移行機能の実装
3. AdminLocationController の統合
4. 統合管理画面の作成  
5. ルーティングの調整
6. テスト・検証

## 利点

1. **管理の簡易化**: 単一画面での道路・ダンジョン管理
2. **データ構造の統一**: 共通属性の一元管理
3. **拡張性**: 新しい移動可能ロケーションタイプの追加が容易
4. **保守性**: コードの重複削減