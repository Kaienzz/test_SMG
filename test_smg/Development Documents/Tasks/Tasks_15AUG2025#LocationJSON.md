# Location JSON Management タスク - 2025年8月15日

## 概要
現在ハードコードで管理されている町、道、ダンジョンの移動情報をJSON形式のデータベースとして管理し、管理画面で編集可能にするためのタスクです。

## 現在の実装状況

### 1. ハードコード配列による管理（LocationService）
- `$roadNames` - 道路名設定配列（7つの道路）
- `$townNames` - 町名設定配列（6つの町）
- `$dungeonNames` - ダンジョン名設定配列（2つのダンジョン）
- `$roadBranches` - T字路・交差点設定配列（1つの分岐点）
- `$townConnections` - 町の複数接続設定配列（6つの町の接続情報）

### 2. 3段階進化システム
- **Phase 1**: 道路命名システム（実装済み）
- **Phase 2**: T字路・交差点システム（実装済み）
- **Phase 3**: 複数接続システム（実装済み）

### 3. 既存の管理画面基盤
- AdminController基底クラス（権限管理、監査ログ）
- AdminItemControllerでのJSON管理実装例あり
- 権限システム（`locations.view`, `locations.edit`権限追加予定）

## フェーズ別実装計画

---

## Phase 1: JSON設定ファイル構造の設計

### 1.1 JSONファイル構造の設計
**目的**: ハードコード配列をJSON形式に変換

**作成するファイル**:
```
config/locations/
├── locations.json          # 統合設定ファイル（メイン）
├── roads.json              # 道路設定（個別管理用）
├── towns.json              # 町設定（個別管理用）
├── dungeons.json           # ダンジョン設定（個別管理用）
└── connections.json        # 接続設定（個別管理用）
```

**locations.json の構造**:
```json
{
  "version": "1.0.0",
  "last_updated": "2025-08-15T00:00:00Z",
  "roads": {
    "road_1": {
      "name": "プリマ街道",
      "description": "プリマの町と中央大通りを結ぶ主要街道",
      "length": 100,
      "difficulty": "easy",
      "encounter_rate": 0.1
    },
    "road_2": {
      "name": "中央大通り",
      "description": "王国の中央を貫く大通り",
      "length": 100,
      "difficulty": "normal",
      "encounter_rate": 0.15,
      "branches": {
        "50": {
          "straight": {"type": "road", "id": "road_3"},
          "right": {"type": "road", "id": "road_4"}
        }
      }
    }
  },
  "towns": {
    "town_prima": {
      "name": "プリマ",
      "description": "冒険者の拠点となる美しい町",
      "type": "starter_town",
      "services": ["shop", "inn", "blacksmith"],
      "connections": {
        "east": {"type": "road", "id": "road_1"}
      }
    },
    "town_c": {
      "name": "C町",
      "description": "三方向への分岐点となる商業都市",
      "type": "hub_town", 
      "services": ["shop", "inn", "blacksmith", "tavern"],
      "connections": {
        "east": {"type": "road", "id": "road_5"},
        "south": {"type": "road", "id": "road_6"},
        "north": {"type": "road", "id": "road_7"}
      }
    }
  },
  "dungeons": {
    "dungeon_1": {
      "name": "古の洞窟",
      "description": "古代の秘密が眠る深い洞窟",
      "type": "cave",
      "difficulty": "normal",
      "floors": 5,
      "boss": "Cave Guardian"
    }
  }
}
```

### 1.2 LocationService の JSON 対応
**ファイル**: `app/Domain/Location/LocationService.php`

**実装内容**:
- [ ] JSON設定ファイル読み込み機能追加
- [ ] `loadLocationData()` メソッド追加
- [ ] 既存ハードコード配列のJSONファイル置き換え
- [ ] キャッシュ機能追加（パフォーマンス対策）
- [ ] バックアップ機能（設定変更時）

**実装例**:
```php
class LocationService
{
    private array $locationData = [];
    private bool $dataLoaded = false;
    
    private function loadLocationData(): void
    {
        if ($this->dataLoaded) return;
        
        $configPath = config_path('locations/locations.json');
        if (file_exists($configPath)) {
            $this->locationData = json_decode(file_get_contents($configPath), true);
        } else {
            // フォールバック: 既存ハードコード配列
            $this->initializeDefaultData();
        }
        
        $this->dataLoaded = true;
    }
}
```

### 1.3 設定ファイル管理サービス
**新規ファイル**: `app/Services/Location/LocationConfigService.php`

**機能**:
- [ ] JSON設定ファイルの読み書き
- [ ] 設定の検証（JSON Schema）
- [ ] バックアップ・復元機能
- [ ] 設定変更履歴管理

---

## Phase 2: 管理画面の実装

### 2.1 AdminLocationController の作成
**新規ファイル**: `app/Http/Controllers/Admin/AdminLocationController.php`

**実装機能**:
- [ ] ロケーション一覧表示
- [ ] 道路・町・ダンジョンの編集
- [ ] 接続関係の管理
- [ ] JSON設定のインポート・エクスポート
- [ ] 設定変更の監査ログ記録

**主要メソッド**:
```php
class AdminLocationController extends AdminController
{
    public function index()              // ロケーション一覧
    public function roads()              // 道路管理
    public function towns()              // 町管理  
    public function dungeons()           // ダンジョン管理
    public function connections()        // 接続関係管理
    public function exportConfig()       // 設定エクスポート
    public function importConfig()       // 設定インポート
}
```

### 2.2 管理画面ビューの作成
**新規ディレクトリ**: `resources/views/admin/locations/`

**作成するビュー**:
- [ ] `index.blade.php` - ロケーション管理トップページ
- [ ] `roads/index.blade.php` - 道路一覧・編集
- [ ] `towns/index.blade.php` - 町一覧・編集
- [ ] `dungeons/index.blade.php` - ダンジョン一覧・編集
- [ ] `connections/index.blade.php` - 接続関係管理
- [ ] `partials/location-form.blade.php` - 編集フォーム部品

### 2.3 管理画面UIの設計
**機能**:
- [ ] ドラッグ&ドロップによる接続編集
- [ ] リアルタイムプレビュー機能
- [ ] JSON直接編集モード
- [ ] 設定検証・エラー表示
- [ ] 一括インポート・エクスポート

**UI例**:
```
┌─────────────────────────────────────────┐
│ ロケーション管理                          │
├─────────────────────────────────────────┤
│ [道路] [町] [ダンジョン] [接続] [設定]     │
├─────────────────────────────────────────┤
│ 道路一覧                                 │
│ ┌─────────┬─────────┬─────────┬───────┐│
│ │ ID      │ 名前      │ 説明      │ 操作   ││
│ ├─────────┼─────────┼─────────┼───────┤│
│ │ road_1  │プリマ街道│主要街道   │[編集] ││
│ │ road_2  │中央大通り│王国の大通り│[編集] ││
│ └─────────┴─────────┴─────────┴───────┘│
│ [新規追加] [一括インポート] [エクスポート]  │
└─────────────────────────────────────────┘
```

### 2.4 権限設定の追加
**ファイル**: 管理権限設定

**追加権限**:
- [ ] `locations.view` - ロケーション情報の閲覧
- [ ] `locations.edit` - ロケーション情報の編集
- [ ] `locations.import` - 設定の一括インポート
- [ ] `locations.export` - 設定のエクスポート

---

## Phase 3: データベーステーブルの追加（オプション）

### 3.1 ロケーション設定履歴テーブル
**目的**: 設定変更の履歴管理

**新規マイグレーション**: `create_location_config_history_table.php`

```sql
CREATE TABLE location_config_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    config_type ENUM('roads', 'towns', 'dungeons', 'connections') NOT NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    target_id VARCHAR(255) NOT NULL,
    old_data JSON NULL,
    new_data JSON NOT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (changed_by) REFERENCES users(id)
);
```

### 3.2 ロケーション設定バックアップテーブル
**目的**: 設定の自動バックアップ

**新規マイグレーション**: `create_location_config_backups_table.php`

```sql
CREATE TABLE location_config_backups (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    backup_name VARCHAR(255) NOT NULL,
    config_data JSON NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_auto_backup BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

---

## Phase 4: API エンドポイントの実装

### 4.1 ロケーション管理 API
**新規ルート**: `routes/admin.php`

```php
// ロケーション管理 API
Route::prefix('api/locations')->group(function () {
    Route::get('/', [AdminLocationController::class, 'apiIndex']);
    Route::get('/roads', [AdminLocationController::class, 'apiRoads']);
    Route::get('/towns', [AdminLocationController::class, 'apiTowns']);
    Route::post('/roads', [AdminLocationController::class, 'apiCreateRoad']);
    Route::put('/roads/{id}', [AdminLocationController::class, 'apiUpdateRoad']);
    Route::delete('/roads/{id}', [AdminLocationController::class, 'apiDeleteRoad']);
    
    Route::post('/import', [AdminLocationController::class, 'apiImport']);
    Route::get('/export', [AdminLocationController::class, 'apiExport']);
    Route::post('/validate', [AdminLocationController::class, 'apiValidate']);
});
```

### 4.2 リアルタイム更新機能
**実装**:
- [ ] 設定変更時の即座反映
- [ ] WebSocket（optional）またはポーリングでの更新通知
- [ ] 変更競合の検出・警告

---

## Phase 5: テスト・検証・ドキュメント

### 5.1 機能テスト
**テスト項目**:
- [ ] JSON設定ファイル読み込みテスト
- [ ] 管理画面での編集機能テスト
- [ ] 設定変更の即座反映テスト
- [ ] インポート・エクスポート機能テスト
- [ ] 権限制御テスト
- [ ] エラーハンドリングテスト

### 5.2 既存機能との互換性確認
- [ ] 既存の移動システムへの影響なし
- [ ] LocationServiceのAPIに変更なし
- [ ] ゲームプレイへの影響なし

### 5.3 パフォーマンステスト
- [ ] JSON読み込み速度
- [ ] キャッシュ効果の確認
- [ ] 大量データでの動作確認

### 5.4 ドキュメント更新
**更新ファイル**:
- [ ] `location_management_manual.md` - JSON管理方式への更新
- [ ] 新規: `location_admin_manual.md` - 管理画面操作マニュアル
- [ ] 新規: `location_json_schema.md` - JSON設定ファイル仕様書

---

## 実装サンプル・参考情報

### 1. 既存参考実装
- `AdminItemController.php` - JSON管理の実装例
- `DummyDataService.php` - 標準アイテムのJSON管理
- `StandardItemService.php` - JSON設定の読み込み処理

### 2. JSON Schema例
```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Location Configuration",
  "type": "object",
  "properties": {
    "roads": {
      "type": "object",
      "patternProperties": {
        "^road_\\d+$": {
          "type": "object",
          "properties": {
            "name": {"type": "string"},
            "description": {"type": "string"},
            "length": {"type": "integer", "minimum": 1, "maximum": 100}
          },
          "required": ["name"]
        }
      }
    }
  }
}
```

### 3. 設定移行スクリプト
**新規ファイル**: `database/seeders/LocationConfigMigrationSeeder.php`

```php
class LocationConfigMigrationSeeder extends Seeder
{
    public function run()
    {
        // 既存ハードコード配列をJSONファイルに変換
        $locationService = new LocationService();
        $configService = new LocationConfigService();
        
        $configData = [
            'roads' => $locationService->getAllRoadNames(),
            'towns' => $locationService->getAllTownNames(),
            // ... 他の設定データ
        ];
        
        $configService->saveConfig($configData);
    }
}
```

---

## 実装優先順位

### 最優先（リスク低・効果高）
1. **Phase 1**: JSON設定ファイル構造設計・LocationService対応
   - 既存システムへの影響最小
   - フォールバック機能で安全性確保

### 高優先（管理機能の実現）
2. **Phase 2**: 管理画面実装
   - ユーザー要求の直接的実現
   - 既存AdminController基盤活用

### 中優先（安定性・拡張性）
3. **Phase 3**: データベーステーブル追加
   - 履歴管理・バックアップ機能
   - 運用安定性の向上

### 低優先（最適化・保守）
4. **Phase 4-5**: API・テスト・ドキュメント
   - 品質向上・保守性強化

---

## リスク管理

### 高リスク要因
1. **既存データの互換性**: ハードコード→JSON移行時のデータ整合性
2. **パフォーマンス**: JSON読み込みの処理速度
3. **設定エラー**: 不正な設定による移動システム停止

### 対策
1. **段階的移行**: フォールバック機能付きで段階的実装
2. **キャッシュ戦略**: 設定データのメモリキャッシュ
3. **設定検証**: JSON Schema による厳密な検証
4. **バックアップ**: 設定変更前の自動バックアップ

---

## 完了条件

### Phase 1 完了条件
- [ ] JSON設定ファイルが正常に読み込まれる
- [ ] 既存の移動システムが正常動作
- [ ] LocationServiceの全メソッドが正常動作

### Phase 2 完了条件  
- [ ] 管理画面で全ロケーション情報が表示される
- [ ] 道路・町・ダンジョンの編集が可能
- [ ] 設定変更が即座に反映される
- [ ] 権限制御が正常動作

### Phase 3-5 完了条件
- [ ] 履歴管理・バックアップ機能が動作
- [ ] API経由での設定管理が可能
- [ ] 全テストケースがパス
- [ ] ドキュメントが完全更新

このタスクにより、ロケーション管理システムがハードコードから柔軟なJSON管理システムへと進化し、管理画面での直感的な編集が可能になります。