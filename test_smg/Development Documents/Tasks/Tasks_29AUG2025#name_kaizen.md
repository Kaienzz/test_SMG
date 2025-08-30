# 2025-08-29 命名改善提案（UIコンテナ/データ属性）

このドキュメントは、ゲーム画面のコンテナクラス/データ属性の命名（例: `game-unified-layout`, `game-layout-noright`, `data-game-state` など）を整理し、よりスマートで一貫性のある命名へ移行するための分析と提案をまとめたものです。

---

## 1. 現状の命名インベントリ（スキャン結果）

検出箇所（主なファイルのみ）：
- HTML/Blade
  - `resources/views/game.blade.php` のルート要素: `class="game-unified-layout game-layout-noright"`、`data-game-state`、`data-location-id`、`data-location-type`
- CSS
  - `public/css/game-unified-layout.css` に `.game-unified-layout`、`.game-layout-noright` とそれらに紐づく派生セレクタ
- JS
  - `public/js/game-unified.js` に `.game-unified-layout` フック、および `data-game-state`/`data-location-*` へのアクセス

主要な現在名称:
- ルートクラス: `game-unified-layout`
- レイアウト状態クラス: `game-layout-noright`（右カラム無し2カラムの意味）
- データ属性: `data-game-state`、`data-location-id`、`data-location-type`

---

## 2. 問題点（なぜスマートではないのか）

- 用語の冗長性/曖昧さ
  - `unified` は「統合版」の文脈由来で、現在の意味合い（メインのゲームレイアウト）には過去の実装履歴が混ざっている。
  - `noright` は「無いものベース」の否定表現で、UIバリアント命名として直感的でない（「何があるか」より「何がないか」を伝えている）。
- 命名規則の混在
  - BEM風（block--modifier）ではなく、`game-layout-noright` のようなフラットな語で、増加時に表現の一貫性が崩れやすい。
- JS/スタイルのフックが分散
  - ルート要素の識別子が「クラス名」頼み（`.game-unified-layout`）であり、役割（component hook）や状態（state）が混同されやすい。
- データ属性のプレフィックス冗長
  - `data-game-state` は `#game-root` などの限定された文脈内では `data-state` でも衝突しにくく、短くできる。

---

## 3. 命名原則（提案）

- 役割（フック）・構造（ブロック）・状態（モディファイア/データ）を分離
  - 役割フック: `data-component="game-layout"` または `id="game-app"`
  - ブロック名: `.game-layout`（シンプルで恒久的なルート）
  - バリアント/状態: BEMモディファイア（`.game-layout--with-left-sidebar` など）か、意味的な data-* 属性（`data-columns="2"` 等）
- 肯定の表現を採用
  - `--with-left-sidebar` / `--with-right-sidebar` / `--with-both-sidebars`（非推奨: `noright` / `two-pane`）
- データ属性は短く一貫
  - `data-state`、`data-loc-id`、`data-loc-type`（文脈で十分に意味が通る範囲で省略）
- セレクタの安定性
  - JS は「役割フック（id または data-component）」にフックし、クラス名変更の影響を受けにくくする。

---

## 4. 新命名案（推奨セット）

ルート要素（例）:
```html
<div id="game-app"
  class="game-layout game-layout--with-left-sidebar"
     data-component="game-layout"
     data-state="road"
     data-loc-id="road_1"
     data-loc-type="road"
  data-columns="2"
  data-sidebars="left">
  ...
</div>
```

置き換えマッピング（最小セット）:
- ルートクラス
  - 旧: `.game-unified-layout` → 新: `.game-layout`
- レイアウトバリアント
  - 旧: `.game-layout-noright` → 新: `.game-layout--with-left-sidebar`（または `.game-layout--no-right-sidebar`）
  - 代替: クラスではなく `data-columns="2"` / `data-sidebars="left|right|both|none"` として記述し、CSSは属性セレクタで表現
- データ属性
  - 旧: `data-game-state` → 新: `data-state`
  - 旧: `data-location-id` → 新: `data-loc-id`
  - 旧: `data-location-type` → 新: `data-loc-type`
- JSフック
  - 旧: `document.querySelector('.game-unified-layout')`
  - 新: `document.querySelector('#game-app')` または `document.querySelector('[data-component="game-layout"]')`

CSSファイル名:
- 旧: `game-unified-layout.css` → 新: `game-layout.css`

---

## 5. 命名方式の比較（クラス vs データ属性）

- クラス（BEM）
  - Pros: ツール/慣習に馴染む。難読化に強い。デザインシステムに合致。
  - Cons: バリアントの種類が増えるほどクラスが増殖しやすい。
- data-* 属性（意味駆動）
  - Pros: JS・CSS双方で可読性の高い条件分岐が可能（例: `[data-columns="2"]`）。
  - Cons: チームに CSS 設計の合意が必要。設計不統一だと複雑化する。

推奨: 「ブロックはクラス」「状態/数値は data-*」のハイブリッド。

---

## 6. CSS/JS の移行方針（段階的）

Phase 0: 互換レイヤ追加（ブレークさせない）
- CSS で一時的に「旧/新」を束ねる複合セレクタを追加
  - 例: `.game-unified-layout, .game-layout { /* 共有ルール */ }`
  - 例: `.game-layout-noright, .game-layout--with-left-sidebar { /* 共有ルール */ }`
- JS でフックを data-component / id に変更し、旧クラスでも fallback 可能に

Phase 1: 出力更新（Blade）
- ルート要素の出力を新命名へ切り替え（旧クラスは併記してもよい）
- データ属性は新旧の二重付与 → ログで旧属性参照が無いことを確認後、旧属性を削除

Phase 2: CSS ファイル名切替
- `game-layout.css` をビルドに組み込み、`game-unified-layout.css` は import/alias で当面温存
- 実運用で問題がなければ旧ファイル参照を削除

Phase 3: 旧名の撤去
- JS・CSS・Blade 全てから旧名を削除

---

## 7. 具体的な置換ターゲット（代表例）

HTML/Blade（`resources/views/game.blade.php`）
- `class="game-unified-layout game-layout-noright"`
  - → `class="game-layout game-layout--with-left-sidebar"`（暫定は両方併記でも可）
- `data-game-state` → `data-state`
- `data-location-id` → `data-loc-id`
- `data-location-type` → `data-loc-type`

CSS（`public/css/game-unified-layout.css` → `public/css/game-layout.css`）
- `.game-unified-layout` → `.game-layout`
- `.game-layout-noright` → `.game-layout--with-left-sidebar`（または属性版 `[data-sidebars="left"]` / `[data-columns="2"]`）

JS（`public/js/game-unified.js`）
- ルート要素の取得を `#game-app` / `[data-component="game-layout"]` に変更
- データ読み取り: `dataset.state`, `dataset.locId`, `dataset.locType`

---

## 8. 命名ガイドライン（今後のルール）

- ベースブロック: `.game-layout`
- モディファイア: `.game-layout--<variant>`（例: `--with-left-sidebar`, `--with-right-sidebar`, `--with-both-sidebars`）
- 状態は `is-*` を許容（例: `.is-transitioning`）
- 数値/可変条件は data-*（`data-columns`, `data-state`）
- フックは id or `data-component`（JS のみが参照）
- kebab-case を徹底、否定形は極力回避（`noright` → `with-left-sidebar` / `no-right-sidebar`。`two-pane` はサイドバー位置が不明瞭なため非推奨）

---

## 9. 作業計画（所要 ~0.5〜1.5日）

- 0) 影響範囲把握（完了）
  - HTML/Blade, CSS, JS の命名出現箇所を特定済み
- 1) 互換レイヤ追加（0.5h）
  - CSS 複合セレクタ、JS フォールバック
- 2) Blade 出力切替（0.5h）
  - 新旧併記 → 検証 → 旧属性・旧クラス削減
- 3) JS フック切替（0.5h）
  - `#game-app` / `data-component` へ移行
- 4) CSS ファイル名切替（0.5h）
  - `game-layout.css` へ
- 5) 旧名撤去と最終整理（0.5h）

リスク/備考:
- ビルドキャッシュ/Bladeキャッシュ差に注意（キャッシュクリアを運用手順に追加）
- 外部ツールや E2E テストが旧クラス名に依存していないか確認

---

## 10. 採用案（結論）

- ルート: `.game-layout`（id=`game-app` or `data-component="game-layout"` を併用）
- バリアント: `.game-layout--with-left-sidebar` を基本（右寄りの場合は `--with-right-sidebar`、両側は `--with-both-sidebars`）。必要に応じ `data-sidebars="left|right|both|none"` や `data-columns="2|3"` を併用
- データ属性: `data-state`, `data-loc-id`, `data-loc-type`
- CSS: `game-layout.css` に改名。移行期間は旧名と併記（複合セレクタ）で互換維持
- JS: クラス依存から `id` / `data-component` 依存へ

---

### 付録：サンプル差分（概念）

HTML（移行期の併記例）
```html
<div id="game-app"
  class="game-unified-layout game-layout game-layout--with-left-sidebar"
     data-component="game-layout"
     data-game-state="road" data-state="road"
     data-location-id="road_1" data-loc-id="road_1"
  data-location-type="road" data-loc-type="road"
  data-sidebars="left">
</div>
```

CSS（移行期の複合セレクタ例）
```css
.game-unified-layout, .game-layout { /* 共通ルール */ }
.game-layout-noright, .game-layout--with-left-sidebar { /* 共通ルール */ }
```

JS（新フックの例）
```js
const root = document.querySelector('#game-app')
           ?? document.querySelector('[data-component="game-layout"]')
           ?? document.querySelector('.game-layout')
           ?? document.querySelector('.game-unified-layout');
```

---

補足：サイドバーセマンティクスの整理
- `game-layout-noright` は「右カラム（右サイドバー）が無い」構成を意味している可能性が高く、実体は「左サイドバー＋メイン」の2カラムです。よって `.game-layout--two-pane` よりも `.game-layout--with-left-sidebar` / `.game-layout--no-right-sidebar` の方が意味的に適切です。
- `--two-pane` は列数のみを表しサイドバーの有無や位置を伝えないため、クラス名としては非推奨（必要なら `data-columns="2"` で補助情報として保持）。

以上。次ステップとして「互換レイヤの追加」と「Blade 出力の二重化（新旧併記）」を短時間で実施し、E2E の確認後に段階的に旧名を撤去することを推奨します。
