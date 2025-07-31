# Location Management Manual - 場所管理マニュアル

## 概要
このマニュアルでは、test_smgゲームにおける場所管理システムについて説明します。
システムは3つのフェーズを経て進化し、道路命名、T字路・交差点、複数接続の機能を持ちます。

## 現在のシステム構造

### 基本マップ構成
```
                    北街道（road_7）
                        ↑
                        ↓
        プリマ ―プリマ街道→ 中央大通り ―港湾道路→ B町
                        ↓(T字路:位置50)
                        山道（road_4）
                        
        エルフの村 ←森林道路→ C町 ←商業街道→ 商業都市
                                ↑
                            北街道（road_7）
```

### システムの特徴

**Phase 1: 道路命名システム**
- カスタム道路名（プリマ街道、中央大通りなど）
- 統一的な名前管理
- フォールバック機能

**Phase 2: T字路・交差点システム**
- 道路の中間地点での分岐
- 複数方向選択（直進・左折・右折）
- 分岐専用UI

**Phase 3: 複数接続システム**
- 町からの4方向接続（北・南・東・西）
- 方向指定移動
- 拡張された町と道路ネットワーク

## LocationService の構造

### 1. 基本設定配列

#### 1.1 道路名設定配列
```php
private array $roadNames = [
    'road_1' => 'プリマ街道',
    'road_2' => '中央大通り', 
    'road_3' => '港湾道路',
    'road_4' => '山道',
    'road_5' => '森林道路',
    'road_6' => '商業街道',
    'road_7' => '北街道',
];
```

#### 1.2 町名設定配列
```php
private array $townNames = [
    'town_prima' => 'プリマ',
    'town_b' => 'B町',
    'town_c' => 'C町',
    'elven_village' => 'エルフの村',
    'merchant_city' => '商業都市',
];
```

#### 1.3 T字路・交差点設定配列
```php
private array $roadBranches = [
    'road_2' => [
        50 => [
            'straight' => ['type' => 'road', 'id' => 'road_3'],
            'right' => ['type' => 'road', 'id' => 'road_4'],
        ]
    ],
    // 将来の拡張例
];
```

#### 1.4 町の複数接続設定配列
```php
private array $townConnections = [
    'town_prima' => [
        'east' => ['type' => 'road', 'id' => 'road_1'],
    ],
    'town_b' => [
        'west' => ['type' => 'road', 'id' => 'road_3'],
    ],
    'town_c' => [
        'east' => ['type' => 'road', 'id' => 'road_5'],
        'south' => ['type' => 'road', 'id' => 'road_6'],
        'north' => ['type' => 'road', 'id' => 'road_7'],
    ],
    'elven_village' => [
        'west' => ['type' => 'road', 'id' => 'road_5'],
    ],
    'merchant_city' => [
        'north' => ['type' => 'road', 'id' => 'road_6'],
    ],
];
```

### 2. 主要メソッド

#### 2.1 名前取得メソッド
```php
// 統一的な場所名取得
public function getLocationName(string $type, string $id): string

// 道路名取得（カスタム名 + フォールバック）
private function getRoadName(string $roadId): string

// 町名取得
private function getTownName(string $townId): string

// ダンジョン名取得
private function getDungeonName(string $dungeonId): string
```

#### 2.2 分岐システムメソッド
```php
// 分岐判定
public function hasBranchAt(string $roadId, int $position): bool

// 分岐選択肢取得
public function getBranchOptions(string $roadId, int $position): ?array

// 分岐移動処理
public function getNextLocationFromBranch(string $roadId, int $position, string $direction): ?array
```

#### 2.3 複数接続システムメソッド
```php
// 複数接続判定
public function hasMultipleConnections(string $townId): bool

// 町の接続先取得
public function getTownConnections(string $townId): ?array

// 方向指定移動
public function getNextLocationFromTownDirection(string $townId, string $direction): ?array
```

## 新しい場所の追加方法

### 1. 道路名付き道路の追加

#### 1.1 道路名の定義
```php
// $roadNames 配列に追加
'road_8' => '新道路名',
'road_9' => '別の道路名',
```

#### 1.2 道路接続の定義
```php
// getNextLocationFromRoad() メソッドで接続先を定義
'road_8' => ['type' => 'town', 'id' => 'new_town'],
```

### 2. T字路・交差点の追加

#### 2.1 分岐点の定義
```php
// $roadBranches 配列に追加
'road_5' => [
    30 => [
        'straight' => ['type' => 'road', 'id' => 'road_6'],
        'left' => ['type' => 'town', 'id' => 'town_c'],
        'right' => ['type' => 'dungeon', 'id' => 'dungeon_1'],
    ]
],
```

#### 2.2 分岐専用UI
分岐地点では自動的に分岐選択UIが表示され、通常の移動ボタンは非表示になります。

### 3. 複数接続の町の追加

#### 3.1 町名の定義
```php
// $townNames 配列に追加
'new_town' => '新しい町',
```

#### 3.2 複数接続の定義
```php
// $townConnections 配列に追加
'new_town' => [
    'north' => ['type' => 'road', 'id' => 'road_8'],
    'south' => ['type' => 'road', 'id' => 'road_9'],
    'east' => ['type' => 'dungeon', 'id' => 'dungeon_1'],
    'west' => ['type' => 'road', 'id' => 'road_10'],
],
```

#### 3.3 複数接続専用UI
複数接続がある町では自動的に方向選択UIが表示され、単一接続ボタンは非表示になります。

### 4. ダンジョンの追加

#### 4.1 ダンジョン名の定義
```php
// $dungeonNames 配列に追加
'dungeon_3' => '新しいダンジョン',
```

#### 4.2 ダンジョン接続の定義
町の接続設定やT字路の分岐先としてダンジョンを指定できます。

## UI表示の自動切り替え

### 1. 通常移動（単一接続の町）
- `next_location_button.blade.php` で単一の移動ボタンを表示

### 2. 複数接続の町
- `multiple_connections.blade.php` で方向選択UIを表示
- 単一接続ボタンは自動的に非表示

### 3. T字路・交差点（道路）
- `branch_selection.blade.php` で分岐選択UIを表示
- 通常の移動ボタンは自動的に非表示

## 実装例

### 例1: 新しい町と道路の追加
```php
// 1. 名前定義
$townNames['mountain_village'] = '山の村';
$roadNames['road_11'] = '山岳道路';

// 2. 接続定義
$townConnections['mountain_village'] = [
    'south' => ['type' => 'road', 'id' => 'road_11'],
];

// getNextLocationFromRoad() で road_11 の接続先を定義
'road_11' => ['type' => 'town', 'id' => 'mountain_village'],
```

### 例2: 複雑な交差点の追加
```php
// 交差点（4方向分岐）
$roadBranches['road_12'] = [
    50 => [
        'straight' => ['type' => 'road', 'id' => 'road_13'],
        'left' => ['type' => 'town', 'id' => 'west_town'],
        'right' => ['type' => 'town', 'id' => 'east_town'],
        'back' => ['type' => 'road', 'id' => 'road_11'],
    ]
];
```

## GameStateManager の連携

### 1. 分岐移動処理
```php
// GameStateManager::moveToBranch()
$result = $gameStateManager->moveToBranch($player, 'straight');
```

### 2. 方向指定移動処理
```php
// GameStateManager::moveToDirection()
$result = $gameStateManager->moveToDirection($player, 'north');
```

## APIエンドポイント

### 1. 分岐移動API
```
POST /game/move-to-branch
Body: {"direction": "straight|left|right|back"}
```

### 2. 方向指定移動API  
```
POST /game/move-to-direction
Body: {"direction": "north|south|east|west"}
```

## デバッグとテスト

### 1. 接続テスト
```php
php artisan tinker --execute="
use App\Domain\Location\LocationService;
$locationService = new LocationService();

// 複数接続テスト
$connections = $locationService->getTownConnections('town_c');
print_r($connections);

// 分岐テスト
$options = $locationService->getBranchOptions('road_2', 50);
print_r($options);
"
```

### 2. よくある問題

#### 2.1 「次の場所が見つかりません」エラー
- `getNextLocationFromRoad()` の接続定義漏れ
- `townConnections` の設定漏れ
- `roadBranches` の設定漏れ

#### 2.2 「この位置には分岐がありません」エラー
- `roadBranches` 配列での位置指定ミス
- 分岐位置での正確な位置判定が必要

#### 2.3 「この町には複数の接続がありません」エラー
- `townConnections` 配列での町ID設定漏れ
- 単一接続の町で方向指定移動を実行

#### 2.4 UI表示の問題
- 分岐UIと通常UIの重複表示
- 複数接続UIと単一接続UIの重複表示
- CSS の `display: none !important` でUIの優先制御

### 3. デバッグログ
```php
// LocationService にログ追加
\Log::info('Branch options:', $this->getBranchOptions($roadId, $position));
\Log::info('Town connections:', $this->getTownConnections($townId));
```

## ベストプラクティス

### 1. 設計原則
- **拡張性**: 新しい場所は既存システムに影響しない
- **一貫性**: 命名規則と接続パターンを統一
- **保守性**: 設定配列で管理し、ハードコードを避ける

### 2. 命名規則
- 町ID: `town_*`, `*_village`, `*_city`
- 道路ID: `road_*` (番号順)
- ダンジョンID: `dungeon_*`

### 3. 接続設計
- 方向は実際の地理に合わせる
- T字路は論理的な位置（通常50%地点）
- 複数接続は最大4方向まで

## まとめ

新しい場所管理システムは3つのフェーズで進化し、柔軟で拡張性の高いシステムになりました：

1. **Phase 1**: 道路に名前をつけられる
2. **Phase 2**: 道の途中で分岐できる  
3. **Phase 3**: 町から複数方向に向かえる

これにより、単純な線形接続から複雑なネットワーク構造まで対応できる包括的な場所管理システムが完成しています。新しい場所の追加は主に LocationService の設定配列を更新するだけで完了し、UI は自動的に適切な表示に切り替わります。