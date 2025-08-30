# Tasks_28AUG2025#Movement_Service.md

## タスク概要
**方角ベース移動システム実装タスク**

**作成日**: 2025年8月28日  
**対象**: 道路移動システムの方角ベース（北南東西）への変更  
**目的**: データベースレベルでの移動軸明示化とUI文言の方角ベース変更

---

## 📋 現在のシステム分析結果

### ✅ 既存実装状況
- **RouteConnections**: 既に`action_label`で`move_north/south/east/west`、`keyboard_shortcut`で4方向対応済み
- **LocationService**: `getDirectionLabel()`メソッドで北南東西の日本語ラベル対応済み（line:552-565）
- **UI**: 現在は「左に移動」「右に移動」のみで方角表現なし
- **JavaScript**: `move('left')`、`move('right')`で固定実装

### ⚠️ 課題
1. **移動軸の非明示性**: データベースで上下移動/左右移動の区別が不明確
2. **UI表現の非統一性**: 「左右」表現のまま、方角ベースになっていない
3. **拡張性の制限**: 将来的な斜め移動や複合移動への対応が困難

---

## 🎯 実装戦略

### データベース設計方針
**ハイブリッドアプローチ**: Routes基本軸定義 + RouteConnections詳細制御

```sql
-- Routes: 基本移動軸定義
ALTER TABLE routes ADD COLUMN default_movement_axis 
    ENUM('horizontal', 'vertical', 'cross', 'mixed') DEFAULT 'horizontal';

-- RouteConnections: 既存action_labelを活用した詳細制御
-- (新カラム追加は不要、既存データ構造を最大活用)
```

---

## 📈 Phase 1: データベース拡張・データ移行

### Task 1.1: Routes テーブル拡張
**優先度**: 🔴 HIGH  
**担当**: Backend  
**期間**: 0.5日

```sql
-- Migration作成
CREATE TABLE IF NOT EXISTS routes_movement_axis_migration (
    ALTER TABLE routes ADD COLUMN default_movement_axis 
        ENUM('horizontal', 'vertical', 'cross', 'mixed') 
        DEFAULT 'horizontal' 
        COMMENT '基本移動軸：horizontal=左右、vertical=上下'
);

-- Index追加（パフォーマンス向上）
CREATE INDEX idx_routes_movement_axis ON routes(default_movement_axis);
```

**成功基準**:
- [ ] Migrationファイル作成完了
- [ ] テーブル拡張成功
- [ ] インデックス設定完了

### Task 1.2: 既存データの移動軸設定
**優先度**: 🟡 MEDIUM  
**担当**: Backend  
**期間**: 1日

```php
// Seeder作成: RouteMovementAxisSeeder
$movementAxisMapping = [
    // 水平移動の道路
    'road_prima_to_cavetown' => 'horizontal',
    'road_cavetown_to_forest' => 'horizontal',
    
    // 垂直移動の道路
    'mountain_path_north_south' => 'vertical',
    'valley_road_up_down' => 'vertical',
    
    // 十字移動
    'crossroads_center' => 'cross',
    
    // 複合移動
    'complex_pathway_mixed' => 'mixed'
];
```

**成功基準**:
- [ ] 全既存道路の移動軸分類完了
- [ ] Seederによるデータ更新実行
- [ ] データ整合性チェック完了

### Task 1.3: RouteConnection データ検証・整備
**優先度**: 🟡 MEDIUM  
**担当**: Backend  
**期間**: 0.5日

```php
// 既存action_labelとkeyboard_shortcutの整合性チェック
$validationRules = [
    'move_north' => ['keyboard_shortcut' => 'up'],
    'move_south' => ['keyboard_shortcut' => 'down'],
    'move_west' => ['keyboard_shortcut' => 'left'],
    'move_east' => ['keyboard_shortcut' => 'right'],
];
```

**成功基準**:
- [ ] action_labelとkeyboard_shortcutの整合性確認
- [ ] 不整合データの修正完了
- [ ] バリデーションルール実装

---

## 🔧 Phase 2: サービス層拡張

### Task 2.1: LocationService 移動軸判定機能拡張
**優先度**: 🔴 HIGH  
**担当**: Backend  
**期間**: 1日

```php
// app/Domain/Location/LocationService.php 拡張

/**
 * 道路の移動軸タイプを取得
 */
public function getRoadMovementAxis(string $routeId): string
{
    $route = Route::where('id', $routeId)->first();
    if (!$route) {
        return 'horizontal'; // デフォルト
    }
    
    return $route->default_movement_axis ?? 'horizontal';
}

/**
 * 利用可能な移動方向を動的生成
 */
public function getAvailableMovementDirections(Player $player): array
{
    $connections = $this->getAvailableConnections($player);
    $movementAxis = $this->getRoadMovementAxis($player->location_id);
    
    $directions = [];
    foreach ($connections as $connection) {
        $directions[] = [
            'action_label' => $connection->action_label,
            'keyboard_shortcut' => $connection->keyboard_shortcut,
            'display_text' => $this->getDirectionDisplayText($connection->action_label),
            'icon' => $this->getDirectionIcon($connection->keyboard_shortcut),
            'axis_type' => $movementAxis
        ];
    }
    
    return $directions;
}

/**
 * 方角ベースの表示テキスト取得
 */
private function getDirectionDisplayText(string $actionLabel): string
{
    return match($actionLabel) {
        'move_north' => '↑に移動する',
        'move_south' => '↓に移動する', 
        'move_west' => '←に移動する',
        'move_east' => '→に移動する',
        'turn_left' => '左に進む',
        'turn_right' => '右に進む',
        default => '移動'
    };
}

/**
 * 方向アイコン取得
 */
private function getDirectionIcon(string $keyboardShortcut): string
{
    return match($keyboardShortcut) {
        'up' => '⬆️',
        'down' => '⬇️',
        'left' => '⬅️',
        'right' => '➡️',
        default => '🚀'
    };
}
```

**成功基準**:
- [ ] 移動軸判定メソッド実装完了
- [ ] 動的移動方向生成機能完了
- [ ] 方角ベース表示テキスト機能完了
- [ ] ユニットテスト作成完了

### Task 2.2: GameController 移動処理更新
**優先度**: 🔴 HIGH  
**担当**: Backend  
**期間**: 0.5日

```php
// app/Http/Controllers/GameController.php 更新

public function move(Request $request)
{
    // 従来の'left'/'right'から方角ベースへの変換処理
    $direction = $request->input('direction');
    $player = Auth::user()->player;
    
    // 従来形式との互換性維持
    if (in_array($direction, ['left', 'right'])) {
        $direction = $this->convertLegacyDirection($direction, $player);
    }
    
    // 新しい方角ベース処理
    return $this->processMoveByDirection($direction, $player);
}

private function convertLegacyDirection(string $legacyDirection, Player $player): string
{
    $movementAxis = app(LocationService::class)->getRoadMovementAxis($player->location_id);
    
    return match([$legacyDirection, $movementAxis]) {
        ['left', 'horizontal'] => 'west',
        ['right', 'horizontal'] => 'east', 
        ['left', 'vertical'] => 'north',
        ['right', 'vertical'] => 'south',
        default => $legacyDirection
    };
}
```

**成功基準**:
- [ ] 方角ベース移動処理実装
- [ ] 従来形式との互換性維持
- [ ] エラーハンドリング実装

---

## 🎨 Phase 3: フロントエンド更新

### Task 3.1: Blade テンプレート更新
**優先度**: 🔴 HIGH  
**担当**: Frontend  
**期間**: 1日

#### Target Files:
- `resources/views/game-states/road-right.blade.php`
- `resources/views/game/partials/movement_controls.blade.php`

```blade
{{-- resources/views/game-states/road-right.blade.php --}}
{{-- 動的移動ボタン生成 --}}
@php
    $movementDirections = app(\App\Domain\Location\LocationService::class)
        ->getAvailableMovementDirections($player);
@endphp

<div class="movement-controls" id="movement-controls">
    @foreach($movementDirections as $direction)
        <button class="btn btn-warning movement-btn movement-{{ $direction['axis_type'] }}" 
                onclick="move('{{ $direction['action_label'] }}')"
                data-direction="{{ $direction['action_label'] }}"
                data-keyboard="{{ $direction['keyboard_shortcut'] }}">
            <span class="btn-icon">{{ $direction['icon'] }}</span>
            <span class="btn-text">{{ $direction['display_text'] }}</span>
        </button>
    @endforeach
</div>

{{-- 移動軸タイプに応じたレイアウト調整 --}}
@php
    $movementAxis = app(\App\Domain\Location\LocationService::class)
        ->getRoadMovementAxis($player->location_id);
@endphp

<style>
.movement-controls.movement-horizontal {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
}

.movement-controls.movement-vertical {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.movement-controls.movement-cross {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    grid-template-rows: 1fr 1fr 1fr;
    gap: 5px;
    width: 120px;
    height: 120px;
}
</style>

<script>
// 移動軸タイプに応じたCSSクラス適用
document.addEventListener('DOMContentLoaded', function() {
    const movementControls = document.getElementById('movement-controls');
    if (movementControls) {
        movementControls.classList.add('movement-{{ $movementAxis }}');
    }
});
</script>
```

**成功基準**:
- [ ] 動的移動ボタン生成実装
- [ ] 方角ベース表示テキスト適用
- [ ] 移動軸に応じたレイアウト対応
- [ ] アイコン表示実装

### Task 3.2: JavaScript 更新
**優先度**: 🔴 HIGH  
**担当**: Frontend  
**期間**: 1日

#### Target Files:
- `public/js/game.js`
- `public/js/game-unified.js`

```javascript
// public/js/game-unified.js 更新

class RoadManager {
    // 従来のmove()関数を方角ベース対応に更新
    move(direction) {
        console.log('🧭 [MOVEMENT] Direction:', direction);
        
        // 方角ベース移動の前処理
        const processedDirection = this.preprocessDirection(direction);
        
        this.disableMovementButtons();
        this.moveInProgress = true;
        
        const requestData = {
            direction: processedDirection,
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
        
        // 既存のAPI呼び出し処理...
    }
    
    // 方角と従来形式の相互変換
    preprocessDirection(direction) {
        const directionMapping = {
            'left': this.getCurrentAxisDirection('left'),
            'right': this.getCurrentAxisDirection('right'),
            'up': 'north',
            'down': 'south'
        };
        
        return directionMapping[direction] || direction;
    }
    
    getCurrentAxisDirection(legacyDirection) {
        const movementAxis = this.getMovementAxisFromDOM();
        
        if (movementAxis === 'vertical') {
            return legacyDirection === 'left' ? 'north' : 'south';
        }
        
        return legacyDirection === 'left' ? 'west' : 'east';
    }
    
    getMovementAxisFromDOM() {
        const movementControls = document.getElementById('movement-controls');
        if (movementControls) {
            if (movementControls.classList.contains('movement-vertical')) return 'vertical';
            if (movementControls.classList.contains('movement-cross')) return 'cross';
        }
        return 'horizontal';
    }
    
    // キーボードショートカット対応更新
    initKeyboardControls() {
        document.addEventListener('keydown', (event) => {
            if (!this.canAcceptInput()) return;
            
            const keyDirectionMap = {
                'ArrowLeft': 'west',
                'ArrowRight': 'east', 
                'ArrowUp': 'north',
                'ArrowDown': 'south'
            };
            
            const direction = keyDirectionMap[event.key];
            if (direction) {
                event.preventDefault();
                this.move(direction);
            }
        });
    }
    
    // ボタン状態管理を方角ベース対応に更新
    disableMovementButtons() {
        const buttons = document.querySelectorAll('.movement-btn');
        buttons.forEach(btn => {
            btn.disabled = true;
        });
    }
    
    enableMovementButtons() {
        const buttons = document.querySelectorAll('.movement-btn');
        buttons.forEach(btn => {
            btn.disabled = false;
        });
    }
}

// グローバル関数の方角ベース対応
function move(direction) {
    if (gameManager?.roadManager) {
        gameManager.roadManager.move(direction);
    }
}
```

**成功基準**:
- [ ] 方角ベース移動処理実装
- [ ] キーボードショートカット4方向対応
- [ ] 従来形式との互換性維持
- [ ] ボタン状態管理更新

### Task 3.3: CSS スタイル更新
**優先度**: 🟡 MEDIUM  
**担当**: Frontend  
**期間**: 0.5日

```css
/* 移動軸タイプ別レイアウト */
.movement-controls.movement-horizontal {
    display: flex;
    flex-direction: row;
    justify-content: center;
    gap: 15px;
}

.movement-controls.movement-vertical {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.movement-controls.movement-cross {
    display: grid;
    grid-template-areas: 
        ". north ."
        "west . east"  
        ". south .";
    grid-template-columns: 1fr 1fr 1fr;
    grid-template-rows: 1fr 1fr 1fr;
    gap: 8px;
    width: 160px;
    height: 160px;
}

/* 方角ボタンの配置 */
.movement-btn[data-direction="move_north"] { grid-area: north; }
.movement-btn[data-direction="move_south"] { grid-area: south; }
.movement-btn[data-direction="move_west"] { grid-area: west; }
.movement-btn[data-direction="move_east"] { grid-area: east; }

/* 移動ボタンのスタイル強化 */
.movement-btn {
    min-width: 100px;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.2s ease;
    position: relative;
}

.movement-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.movement-btn .btn-icon {
    font-size: 1.2em;
    margin-right: 5px;
}

.movement-btn .btn-text {
    font-weight: 600;
}
```

**成功基準**:
- [ ] 移動軸別レイアウト実装
- [ ] 十字レイアウト実装
- [ ] ボタンデザイン強化
- [ ] レスポンシブ対応

---

## ⚙️ Phase 4: 管理画面対応

### Task 4.1: AdminRouteConnectionController 更新
**優先度**: 🟡 MEDIUM  
**担当**: Backend  
**期間**: 1日

```php
// app/Http/Controllers/Admin/AdminRouteConnectionController.php

public function create()
{
    $movementAxisOptions = [
        'horizontal' => '水平移動（東西）',
        'vertical' => '垂直移動（北南）', 
        'cross' => '十字移動（四方向）',
        'mixed' => '複合移動'
    ];
    
    $actionLabelOptions = [
        'move_north' => '↑に移動する',
        'move_south' => '↓に移動する',
        'move_west' => '←に移動する', 
        'move_east' => '→に移動する',
        'turn_left' => '左に進む',
        'turn_right' => '右に進む'
    ];
    
    return view('admin.route-connections.create', compact(
        'movementAxisOptions', 
        'actionLabelOptions'
    ));
}

public function store(CreateRouteConnectionRequest $request)
{
    // バリデーション強化：action_labelとkeyboard_shortcutの整合性チェック
    $this->validateDirectionConsistency($request);
    
    // 接続作成処理...
}

private function validateDirectionConsistency(Request $request)
{
    $actionLabel = $request->input('action_label');
    $keyboardShortcut = $request->input('keyboard_shortcut');
    
    $validCombinations = [
        'move_north' => 'up',
        'move_south' => 'down',
        'move_west' => 'left',
        'move_east' => 'right'
    ];
    
    if (isset($validCombinations[$actionLabel]) && 
        $validCombinations[$actionLabel] !== $keyboardShortcut) {
        throw ValidationException::withMessages([
            'keyboard_shortcut' => '選択されたアクションラベルとキーボードショートカットが一致しません'
        ]);
    }
}
```

**成功基準**:
- [ ] 移動軸選択UI追加
- [ ] action_labelとkeyboard_shortcut整合性バリデーション
- [ ] 管理画面での設定保存機能

### Task 4.2: 管理画面UI更新
**優先度**: 🟡 MEDIUM  
**担当**: Frontend  
**期間**: 0.5日

```blade
{{-- resources/views/admin/route-connections/create.blade.php --}}

<div class="form-group">
    <label for="movement_axis">移動軸タイプ</label>
    <select name="movement_axis" id="movement_axis" class="form-control">
        @foreach($movementAxisOptions as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    <small class="form-text text-muted">
        この道路の基本的な移動方向を選択してください
    </small>
</div>

<div class="form-group">
    <label for="action_label">移動アクション</label>
    <select name="action_label" id="action_label" class="form-control">
        @foreach($actionLabelOptions as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="keyboard_shortcut">キーボードショートカット</label>
    <select name="keyboard_shortcut" id="keyboard_shortcut" class="form-control">
        <option value="up">↑ (上)</option>
        <option value="down">↓ (下)</option> 
        <option value="left">← (左)</option>
        <option value="right">→ (右)</option>
    </select>
</div>

<script>
// action_labelとkeyboard_shortcutの自動連動
document.getElementById('action_label').addEventListener('change', function() {
    const actionLabel = this.value;
    const keyboardSelect = document.getElementById('keyboard_shortcut');
    
    const autoMapping = {
        'move_north': 'up',
        'move_south': 'down',
        'move_west': 'left',
        'move_east': 'right'
    };
    
    if (autoMapping[actionLabel]) {
        keyboardSelect.value = autoMapping[actionLabel];
    }
});
</script>
```

**成功基準**:
- [ ] 移動軸選択UI実装
- [ ] 自動連動機能実装
- [ ] バリデーション表示

---

## 🧪 Phase 5: テスト・品質保証

### Task 5.1: ユニットテスト作成
**優先度**: 🟡 MEDIUM  
**担当**: Backend  
**期間**: 1日

```php
// tests/Unit/LocationServiceTest.php

class LocationServiceTest extends TestCase
{
    public function testGetRoadMovementAxis()
    {
        $service = new LocationService();
        
        // 水平移動道路のテスト
        $axis = $service->getRoadMovementAxis('horizontal_test_road');
        $this->assertEquals('horizontal', $axis);
        
        // 垂直移動道路のテスト  
        $axis = $service->getRoadMovementAxis('vertical_test_road');
        $this->assertEquals('vertical', $axis);
    }
    
    public function testGetAvailableMovementDirections()
    {
        $player = Player::factory()->create([
            'location_id' => 'test_horizontal_road',
            'location_type' => 'road',
            'game_position' => 50
        ]);
        
        $service = new LocationService();
        $directions = $service->getAvailableMovementDirections($player);
        
        $this->assertIsArray($directions);
        $this->assertArrayHasKey('display_text', $directions[0]);
        $this->assertArrayHasKey('icon', $directions[0]);
    }
}

// tests/Feature/MovementControllerTest.php

class MovementControllerTest extends TestCase
{
    public function testMoveWithCardinalDirection()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)->post('/game/move', [
            'direction' => 'north'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message', 'data']);
    }
    
    public function testMoveLegacyDirectionConversion()
    {
        // 従来の'left'/'right'が正しく方角に変換されるかテスト
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'location_id' => 'horizontal_test_road'
        ]);
        
        $response = $this->actingAs($user)->post('/game/move', [
            'direction' => 'left'  // 水平道路では'west'に変換されるはず
        ]);
        
        $response->assertStatus(200);
        // レスポンスで'west'方向の移動が実行されたことを確認
    }
}
```

**成功基準**:
- [ ] LocationService ユニットテスト完了
- [ ] GameController フィーチャーテスト完了 
- [ ] テストカバレッジ90%以上

### Task 5.2: 統合テスト実行
**優先度**: 🔴 HIGH  
**担当**: QA  
**期間**: 1日

#### テストシナリオ:

1. **基本移動テスト**
   - [ ] 水平道路で東西移動確認
   - [ ] 垂直道路で南北移動確認  
   - [ ] 十字道路で4方向移動確認

2. **UI表示テスト**
   - [ ] 移動軸に応じたボタン配置確認
   - [ ] 方角ベース文言表示確認
   - [ ] アイコン表示確認

3. **キーボードショートカットテスト**
   - [ ] 矢印キー4方向操作確認
   - [ ] 移動軸との連動確認

4. **互換性テスト**
   - [ ] 既存セーブデータでの正常動作
   - [ ] 管理画面での設定保存・読み込み

5. **エラーハンドリングテスト**
   - [ ] 不正な方向指定時の処理
   - [ ] データベース整合性エラー処理

**成功基準**:
- [ ] 全テストシナリオ PASS
- [ ] パフォーマンス劣化なし
- [ ] 既存機能に影響なし

---

## 🔄 Phase 6: データ移行・デプロイ

### Task 6.1: 本番データ移行計画
**優先度**: 🔴 HIGH  
**担当**: DevOps + Backend  
**期間**: 0.5日

```sql
-- 移行SQL スクリプト
-- Step 1: routes テーブル拡張
ALTER TABLE routes ADD COLUMN default_movement_axis 
    ENUM('horizontal', 'vertical', 'cross', 'mixed') DEFAULT 'horizontal';

-- Step 2: 既存データの移動軸設定
UPDATE routes SET default_movement_axis = 'horizontal' 
    WHERE category = 'road' AND id LIKE '%horizontal%';
    
UPDATE routes SET default_movement_axis = 'vertical' 
    WHERE category = 'road' AND id LIKE '%vertical%';

-- Step 3: route_connections データ整合性チェック
SELECT rc.id, rc.action_label, rc.keyboard_shortcut 
FROM route_connections rc 
WHERE (rc.action_label = 'move_north' AND rc.keyboard_shortcut != 'up')
   OR (rc.action_label = 'move_south' AND rc.keyboard_shortcut != 'down')
   OR (rc.action_label = 'move_west' AND rc.keyboard_shortcut != 'left')
   OR (rc.action_label = 'move_east' AND rc.keyboard_shortcut != 'right');
```

**成功基準**:
- [ ] 移行スクリプト検証完了
- [ ] バックアップ作成完了
- [ ] ロールバック手順確認完了

### Task 6.2: 段階的デプロイ
**優先度**: 🔴 HIGH  
**担当**: DevOps  
**期間**: 1日

#### デプロイ手順:

1. **Phase 1**: データベース拡張のみ
   - [ ] routes.default_movement_axis カラム追加
   - [ ] 既存データ移行実行
   - [ ] データ整合性確認

2. **Phase 2**: バックエンド更新
   - [ ] LocationService 拡張デプロイ
   - [ ] GameController 更新デプロイ  
   - [ ] API互換性テスト

3. **Phase 3**: フロントエンド更新
   - [ ] Blade テンプレート更新
   - [ ] JavaScript 更新  
   - [ ] CSS 更新

4. **Phase 4**: 管理画面更新
   - [ ] AdminRouteConnectionController 更新
   - [ ] 管理画面UI更新

**成功基準**:
- [ ] 各フェーズでサービス停止時間最小化
- [ ] ユーザー影響なしでの段階的移行
- [ ] 全機能の正常動作確認

---

## ⚡ 緊急対応・ロールバック計画

### 問題発生時の対応手順

#### 🚨 Critical Issues:
- **移動処理の完全停止**
  - → 即座に前バージョンにロールバック
  - → データベース変更の revert 実行

#### ⚠️ Major Issues:  
- **一部UI表示不正**
  - → フロントエンドのみ前バージョン復元
  - → バックエンドは継続運用

#### 🔧 Minor Issues:
- **管理画面のみの問題**
  - → 管理画面コンポーネントのみ修正デプロイ
  - → ユーザー側は継続運用

### ロールバック SQL

```sql
-- 完全ロールバック用SQL（緊急時のみ使用）
ALTER TABLE routes DROP COLUMN default_movement_axis;

-- 部分ロールバック：デフォルト値のみリセット  
UPDATE routes SET default_movement_axis = 'horizontal';
```

---

## 📊 成功指標・KPI

### 技術指標
- [ ] **パフォーマンス**: 移動処理レスポンス時間 200ms以下維持
- [ ] **エラー率**: 移動関連エラー 0.1%以下
- [ ] **テストカバレッジ**: 90%以上
- [ ] **データベース整合性**: 100%

### ユーザー体験指標
- [ ] **移動操作の直感性**: ユーザーテスト満足度 85%以上
- [ ] **操作ミス率**: 意図しない方向への移動 5%以下
- [ ] **学習コスト**: 新システム理解時間 2分以内

### 拡張性指標
- [ ] **新方向追加コスト**: 斜め移動実装工数 2日以内
- [ ] **設定変更容易性**: 管理画面での軸変更 5分以内
- [ ] **コード保守性**: 新機能追加時の既存コード変更箇所最小化

---

## 🎯 優先順位まとめ

### 🔴 Critical Path (1週間以内)
1. **Task 1.1**: Routes テーブル拡張
2. **Task 2.1**: LocationService 移動軸判定機能  
3. **Task 3.1**: Blade テンプレート更新
4. **Task 3.2**: JavaScript 更新

### 🟡 Important (2週間以内)  
5. **Task 1.2**: 既存データ移行
6. **Task 2.2**: GameController 更新
7. **Task 5.2**: 統合テスト実行
8. **Task 6.2**: 段階的デプロイ

### 🟢 Nice to Have (3週間以内)
9. **Task 4.1**: 管理画面対応
10. **Task 3.3**: CSS スタイル強化
11. **Task 5.1**: ユニットテスト完備

---

## 📋 チェックリスト

### 開発完了チェック
- [ ] 全タスクの成功基準クリア
- [ ] コードレビュー完了
- [ ] セキュリティチェック完了  
- [ ] パフォーマンステスト完了
- [ ] ドキュメント更新完了

### リリース準備チェック  
- [ ] 本番環境テスト完了
- [ ] ロールバック手順確認完了
- [ ] 監視・アラート設定完了
- [ ] サポート体制準備完了

---

**📅 推定総工数**: 8-10人日  
**🎯 完成予定日**: 2025年9月5日  
**👥 必要リソース**: Backend×2, Frontend×1, QA×1, DevOps×1

---

## 📝 備考・注意事項

1. **データベース変更**: routes テーブルの拡張は既存データに影響を与えるため、慎重な移行計画が必要
2. **UI/UX変更**: ユーザーの操作習慣が変わるため、段階的な移行とヘルプ表示を推奨  
3. **パフォーマンス**: 移動軸判定処理の最適化により、レスポンス時間の維持が重要
4. **拡張性**: 将来的な斜め移動・3D移動への対応を考慮した設計
5. **互換性**: 既存のセーブデータとAPIとの完全な後方互換性維持

---

*このドキュメントは実装進行に合わせて随時更新します*