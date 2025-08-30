# Routes_Connection データ管理ベストプラクティス（設計提案）

作成日: 2025-08-27

## 目的と前提

- この文書は、ルート間接続（Routes_Connection）のデータモデルとゲームロジックのベストプラクティスを定義する。
- 現状の悩み: Bidirectional 定義が複雑化。進捗の閾値（0/50/100 など）で移動ボタン表示や開始位置設定が必要。町（town）は position を持たない。
- 既存: `routes` テーブル（category: road/town/dungeon）。現行 `route_connections` は `source_location_id, target_location_id, position, connection_type, direction`（モデル参照）。
- 要件: 以下の列を中核に再設計する。
  - source_location_id VARCHAR NOT NULL
  - target_location_id VARCHAR NOT NULL
  - source_position（進捗%: 0〜100、source が town の場合は NULL）
  - target_position（進捗%: 0〜100、target が town の場合は NULL）

## 結論（要点）

- データは常に「有向エッジ」で持つ（Bidirectional は廃止）。双方向にしたい場合は A→B と B→A の2レコードを明示的に登録。
- ボタン表示条件は「現在地の progress が source_position と一致したら可視化」（ただし、0は<=、100は>=、中間値は=で判定）。
- 遷移時は「次のロケーションの progress を target_position に設定」。
- town は position を持たないため、source が town なら source_position=NULL、target が town なら target_position=NULL とする。
- 0/50/100 等の閾値は source_position/target_position で柔軟に表現する。中間分岐（例: 50%で分岐）も A→B を source_position=50 として定義可能。

## データモデル提案（route_connections）

必須の最小構成:
- id (PK)
- source_location_id (string, FK routes.id, index)
- target_location_id (string, FK routes.id, index)
- source_position TINYINT NULL  — 0..100（source が town のときは NULL 固定）
- target_position TINYINT NULL  — 0..100（target が town のときは NULL 固定）

推奨の補助列（任意）:
- edge_type ENUM('normal','branch','portal','exit','enter') NULL — 管理・可視化用の分類タグ。
- is_enabled BOOLEAN DEFAULT 1 — 将来の停止/封鎖に対応。
- action_label ENUM('turn_right','turn_left','move_north','move_south','move_west','move_east','enter_dungeon','exit_dungeon') NULL — 移動アクションの種類
- keyboard_shortcut ENUM('up','down','left','right') NULL — 矢印キー操作用のキーバインド

整合性制約（SQL/アプリ層いずれかで担保）:
- 0 <= source_position <= 100（NULL 可）
- 0 <= target_position <= 100（NULL 可）
- source_category in (road,dungeon) のとき source_position NOT NULL、source_category='town' のとき source_position IS NULL。
- target_category in (road,dungeon) のとき target_position NOT NULL、target_category='town' のとき target_position IS NULL。
- 重複防止: UNIQUE (source_location_id, target_location_id, source_position)
  - 同一ペアで複数の閾値を許容する場合は UNIQUE を (source, target, source_position) に（position違いの枝を許容）。

備考:
- 既存の `position`, `connection_type` は段階的に廃止/置換を推奨。
- 既存の `direction` は `action_label` への移行を推奨。現在は方角表示のみだが、より具体的なアクション表現（「右折する」「ダンジョンに入る」等）に発展させる。
- `keyboard_shortcut` により矢印キー操作での移動を実現。

## 運用ルール（Bidirectional 廃止の方針）

- エッジは常に1方向。逆方向が必要なら別レコードを作る。
- 管理画面では「逆向きを自動作成」ボタンなどの利便機能は提供しても、保存は別エッジ（ID別）。
- 逆向き自動作成時のデフォルト:
  - 双方が road/dungeon: 逆エッジの source_position は元の target_position、target_position は元の source_position を初期値候補にする（確認可能に）。
  - town を含む: town 側の position は NULL、road/dungeon 側は 0 または 100（入口/出口の向きに合わせて選択）。

## ゲームロジック（UI/遷移）

- ボタン表示ロジック（疑似）:
  - 現在地 L、現在 progress P。
  - 候補 = 全ての route_connections where source_location_id=L。
  - フィルタ: source が road/dungeon なら以下の条件で判定
    - source_position = 0 の場合: P <= 0
    - source_position = 100 の場合: P >= 100
    - source_position がその他（50等）の場合: P = source_position
  - source が town なら常に可視。
  - 残った候補を UI に「移動ボタン」として列挙（分岐も同時表示）。
- UI 表示内容:
  - ボタンテキスト: `action_label` が設定されていればそれに対応する日本語テキストを表示、なければデフォルト（「{target_location.name}に移動する」）
  - キーボード操作: `keyboard_shortcut` が設定されていれば対応する矢印キーで移動可能
  - 例: action_label='turn_right' → 「右折する」表示、keyboard_shortcut='right' → 右矢印キーで実行可能
- 遷移ロジック:
  - ボタン押下で (L,P) → (target_location_id, P') に更新。
  - P' = target が road/dungeon のとき target_position、target が town のとき NULL。

### 0/50/100 の扱いガイド
- 0: ルートの開始側（入口）。判定条件: current_progress <= 0
- 50: 中間分岐・中継地点。判定条件: current_progress = 50
- 100: ルートの終端側（出口）。判定条件: current_progress >= 100
- 例: TownA → RoadR(0), RoadR(100) → TownB。RoadR(50) → BranchRoadX(0) のような表現が可能。

## 町 ↔ 道／ダンジョン の管理

- town は position を持たない（常に NULL）。
- town→road: source_position=NULL, target_position=0 または 100（どちらの入口から入るかで選ぶ）。
- road→town: source_position=0 または 100（出口到達時に可視）、target_position=NULL。
- town→town は基本非推奨（必要なら portal 的な edge_type にして特例扱い）。

## バリデーション（Laravel 例）

- 共通:
  - source_location_id/target_location_id: required|string|exists:routes,id|different
  - source_position/target_position: nullable|integer|between:0,100
  - action_label: nullable|in:turn_right,turn_left,move_north,move_south,move_west,move_east,enter_dungeon,exit_dungeon — 移動アクション種類
  - keyboard_shortcut: nullable|in:up,down,left,right — 矢印キーバインド
- クロスルール（カテゴリ参照が必要）:
  - source.category in (road,dungeon) → source_position 必須
  - source.category='town' → source_position は必ず NULL
  - target.category in (road,dungeon) → target_position 必須
  - target.category='town' → target_position は必ず NULL
- 重複チェック: 同一 (source, target, source_position) の既存有無チェック。

実装メモ:
- DB の CHECK 制約は SQLite だと複雑な参照が難しいため、アプリ層バリデーション＋管理画面の検証機能で担保（`AdminRouteService::validate` を拡張）。

## グラフ取得・可視化

- ノード = routes、エッジ = route_connections（有向）。
- 表示ラベルに source_position→target_position を併記すると認知コストが下がる。
- edge_type で色分け（通常・分岐・ポータル等）。

## マイグレーション計画（段階的）

1) route_connections に列追加
- add: source_position (tinyint, nullable), target_position (tinyint, nullable)
- add: edge_type, is_enabled（任意）
- add: action_label (enum, nullable), keyboard_shortcut (enum, nullable)

2) データ移行
- 旧 position は用途に応じて source_position へ移す。
- 旧 direction は action_label へ移行（「北」→'move_north'等のようにENUM値に変換）。
- Bidirectional タイプは A↔B を A→B / B→A の2行に正規化。片方向のみ必要なら片方だけ残す。

3) コード更新
- モデル: fillable/casts 更新。`position`/`connection_type` の使用箇所は `source_position`/`target_position` に置換。
- UI: `action_label` ENUM値に対応する日本語表示ロジックと、`keyboard_shortcut` による矢印キー操作を実装。
- JavaScript: 矢印キーイベントリスナーを追加し、対応するconnectionを自動実行する仕組みを構築。
- バリデーション: 上記クロスルールを実装。
- 管理 UI: 逆向き自動作成ボタン（任意）と、source/target_position、action_label、keyboard_shortcut の入力/表示を追加。

4) 旧列の廃止
- 実稼働で安定後に `position`, `connection_type`, `direction` を削除。

## API/サービス層の最小契約

- 入力: current_location_id (string), current_progress (int|null)
- 出力: available_edges[] （id, target_location_id, target_position, label 等）
- 条件: source が town → progress 不問、それ以外 → 以下の条件で判定
  - source_position = 0 の場合: current_progress <= 0
  - source_position = 100 の場合: current_progress >= 100
  - source_position がその他の場合: current_progress = source_position
- 例外: 不正な location、位置が未定義などは空配列返却で UI に依存。

## 代表的なユースケースとデータ例

- TownA → RoadR(0) 「東に移動する」:
  - source=TownA, target=RoadR, source_position=NULL, target_position=0
  - action_label='move_east', keyboard_shortcut='right'
- RoadR(100) → TownB 「セカンダ町に入る」:
  - source=RoadR, target=TownB, source_position=100, target_position=NULL
  - action_label=NULL, keyboard_shortcut=NULL（町への移動はデフォルト表示・キーボード操作なし）
- RoadR(50) → BranchX(0) 「右折する」:
  - source=RoadR, target=BranchX, source_position=50, target_position=0
  - action_label='turn_right', keyboard_shortcut='right'
- TownC → DungeonY(0) 「ダンジョンに入る」:
  - source=TownC, target=DungeonY, source_position=NULL, target_position=0
  - action_label='enter_dungeon', keyboard_shortcut=NULL
- DungeonY(0) → TownC 「ダンジョンから出る」:
  - source=DungeonY, target=TownC, source_position=0, target_position=NULL
  - action_label='exit_dungeon', keyboard_shortcut=NULL
- 逆方向を許可する場合は、それぞれ逆向きの行を別途作成。

## 品質ゲート（方針）

- ビルド/テスト: モデル/バリデーション更新時は Feature テストで以下を担保
  - 町→道/道→町の NULL ルール
  - 0/50/100 の可視化/遷移ロジック
  - 重複禁止（同一 (source, target, source_position)）

## 補足（現行コードとの差分観点）

- 現行 `RouteConnection` は `position, connection_type, direction` を持つ。今後は `source_position, target_position` に移行し、Bidirectional は廃止。
- `AdminRouteConnectionController` の create/update バリデーションから `connection_type in start,end,bidirectional` を撤廃し、新スキーマへ置換。
- `AdminRouteService::getConnections()` など表示層は `source_position/target_position` を含めたマッピングへ更新。

## 実装ガイダンス：action_label と keyboard_shortcut の活用

### action_label ENUM値と日本語表示の対応

```php
// ActionLabel エンティティまたはヘルパー関数
public static function getActionLabelText($actionLabel, $targetLocationName = null)
{
    return match($actionLabel) {
        'turn_right' => '右折する',
        'turn_left' => '左折する', 
        'move_north' => '北に移動する',
        'move_south' => '南に移動する',
        'move_west' => '西に移動する',
        'move_east' => '東に移動する',
        'enter_dungeon' => ($targetLocationName ? $targetLocationName . 'に入る' : 'ダンジョンに入る'),
        'exit_dungeon' => ($targetLocationName ? $targetLocationName . 'から出る' : 'ダンジョンから出る'),
        default => ($targetLocationName ? $targetLocationName . 'に移動する' : '移動する')
    };
}
```

### View での表示方法

```php
// 移動ボタンの表示例（Blade template）
@foreach($availableConnections as $connection)
    <button class="movement-btn" 
            data-connection-id="{{ $connection->id }}"
            data-keyboard-shortcut="{{ $connection->keyboard_shortcut }}"
            onclick="moveToLocation('{{ $connection->target_location_id }}')">
        <span class="btn-text">
            {{ ActionLabel::getActionLabelText($connection->action_label, $connection->targetLocation->name) }}
        </span>
        @if($connection->keyboard_shortcut)
            <span class="keyboard-hint">[{{ strtoupper($connection->keyboard_shortcut) }}]</span>
        @endif
    </button>
@endforeach
```

### JavaScript実装：矢印キー操作

```javascript
// 矢印キーイベントリスナー
document.addEventListener('keydown', function(event) {
    // ゲーム画面でのみ有効（入力フォーカスがない場合）
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
        return;
    }
    
    const keyMappings = {
        'ArrowUp': 'up',
        'ArrowDown': 'down', 
        'ArrowLeft': 'left',
        'ArrowRight': 'right'
    };
    
    const shortcut = keyMappings[event.key];
    if (shortcut) {
        event.preventDefault();
        
        // 対応するconnectionを探す（表示中のもののみ）
        const targetButton = document.querySelector(`[data-keyboard-shortcut="${shortcut}"]:not(.hidden)`);
        if (targetButton && !targetButton.disabled) {
            targetButton.click();
        }
    }
});

// ボタン表示条件を判定するヘルパー関数
function shouldShowConnection(currentProgress, sourcePosition) {
    if (sourcePosition === null) return true; // town の場合
    if (sourcePosition === 0) return currentProgress <= 0;
    if (sourcePosition === 100) return currentProgress >= 100;
    return currentProgress === sourcePosition;
}
```

### 管理画面での設定ガイド

action_labelの使い分け:
- **方向系**: move_north, move_south, move_east, move_west （基本的な移動）
- **曲がり系**: turn_right, turn_left （道路での分岐・方向転換）
- **出入り系**: enter_dungeon, exit_dungeon （ダンジョンとの出入り）

keyboard_shortcutの割り当て:
- **up**: 北方向への移動、前進
- **down**: 南方向への移動、後退
- **left**: 西方向への移動、左折
- **right**: 東方向への移動、右折
- **NULL**: 町への移動など、キーボード操作を提供しない場合

---
この設計により、
- 双方向の複雑性を排除（常に有向）
- 中間分岐や入口/出口の明示（0/50/100 など）
- town の非 position 特性を一貫して表現
- 管理しやすいENUM形式での移動アクション定義（action_label）
- 矢印キーによる直感的な移動操作（keyboard_shortcut）
が可能になる。UI 側は「逆向き自動作成」などで作業効率を保ちつつ、データは正規化・簡潔に維持し、プレイヤーには直感的で魅力的な移動体験（マウス操作＋キーボード操作の両対応）を提供できる。

## 追加実装考慮事項

### keyboard_shortcut の詳細実装方針

1. **同一キーの重複処理**
   - 同じ現在地から複数のconnectionが同じkeyboard_shortcutを持つ場合は、バリデーション時にエラーとする
   - または、最初に見つかったconnectionのみを実行する（優先順位はid順など）

2. **キーボード操作の有効範囲**
   - 道路移動中のみ有効にするか、町でも有効にするかの選択
   - モーダルダイアログやフォーム入力中は無効化必須

3. **視覚的フィードバック**
   - 移動ボタンに対応するキー表示（[←] [→] など）
   - キー押下時のボタンハイライト効果

4. **管理画面での設定支援**
   - 同一location_idから出るconnectionのkeyboard_shortcut重複チェック機能
   - keyboard_shortcutの自動提案（北→up, 南→down等）

### データ整合性の追加制約

```sql
-- 同一source_location_id内でのkeyboard_shortcut重複防止（複合UNIQUE制約）
ALTER TABLE route_connections ADD CONSTRAINT unique_keyboard_shortcut_per_source 
UNIQUE (source_location_id, keyboard_shortcut) 
WHERE keyboard_shortcut IS NOT NULL;
```

### ゲームUXの向上案

- **キーボードヘルプ表示**: 「↑↓←→キーで移動」のようなガイド
- **キー操作音効**: 矢印キー押下時の効果音
- **移動方向の予告表示**: 次に押せるキーのプレビュー表示