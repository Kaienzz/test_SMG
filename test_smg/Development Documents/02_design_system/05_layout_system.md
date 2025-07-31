# レイアウトシステム書

## プロジェクト情報
- **プロジェクト名**: test_smg（Simple Management Game）
- **作成日**: 2025年7月26日
- **バージョン**: 1.0
- **作成者**: Claude（AI開発アシスタント）
- **想定開発時間**: 4時間

## 目次
1. [レイアウトシステム概要](#レイアウトシステム概要)
2. [グリッドシステム](#グリッドシステム)
3. [レスポンシブデザイン](#レスポンシブデザイン)
4. [コンテナシステム](#コンテナシステム)
5. [フレックスボックスユーティリティ](#フレックスボックスユーティリティ)
6. [スペーシングシステム](#スペーシングシステム)
7. [ゲーム専用レイアウト](#ゲーム専用レイアウト)
8. [モバイル最適化](#モバイル最適化)
9. [アクセシビリティ配慮](#アクセシビリティ配慮)
10. [実装ガイドライン](#実装ガイドライン)

## レイアウトシステム概要

### 設計哲学
test_smgのレイアウトシステムは、CGI時代のシンプルな情報整理と現代的なレスポンシブデザインを融合させたものです。情報の階層構造を明確にし、どのデバイスでも直感的に操作できるインターフェースを提供します。

### システム構成
```typescript
interface LayoutSystem {
  foundation: {
    grid: GridSystem;
    containers: ContainerSystem;
    spacing: SpacingSystem;
    breakpoints: BreakpointSystem;
  };
  components: {
    header: HeaderLayout;
    sidebar: SidebarLayout;
    main: MainContentLayout;
    footer: FooterLayout;
  };
  utilities: {
    flex: FlexboxUtilities;
    position: PositionUtilities;
    alignment: AlignmentUtilities;
    spacing: SpacingUtilities;
  };
  responsive: {
    mobile: MobileOptimizations;
    tablet: TabletOptimizations;
    desktop: DesktopOptimizations;
  };
}
```

### 技術スタック
- **CSS Grid**: 主要レイアウト構造
- **Flexbox**: コンポーネント内配置
- **CSS Custom Properties**: 動的値制御
- **Container Queries**: コンテキスト依存レスポンシブ
- **CSS Logical Properties**: 国際化対応

## グリッドシステム

### 1. 基本グリッド設計
```css
:root {
  /* グリッド基本設定 */
  --grid-columns: 12;
  --grid-gap: 1rem;
  --grid-margin: 1rem;
  
  /* ブレークポイント */
  --breakpoint-xs: 0;
  --breakpoint-sm: 576px;
  --breakpoint-md: 768px;
  --breakpoint-lg: 992px;
  --breakpoint-xl: 1200px;
  --breakpoint-xxl: 1400px;
  
  /* コンテナ最大幅 */
  --container-sm: 540px;
  --container-md: 720px;
  --container-lg: 960px;
  --container-xl: 1140px;
  --container-xxl: 1320px;
}

/* 基本グリッドコンテナ */
.grid {
  display: grid;
  grid-template-columns: repeat(var(--grid-columns), 1fr);
  gap: var(--grid-gap);
  margin: 0 var(--grid-margin);
}

/* グリッドアイテム */
.grid-item {
  grid-column: span 1;
}

/* カラム数指定 */
.col-1 { grid-column: span 1; }
.col-2 { grid-column: span 2; }
.col-3 { grid-column: span 3; }
.col-4 { grid-column: span 4; }
.col-5 { grid-column: span 5; }
.col-6 { grid-column: span 6; }
.col-7 { grid-column: span 7; }
.col-8 { grid-column: span 8; }
.col-9 { grid-column: span 9; }
.col-10 { grid-column: span 10; }
.col-11 { grid-column: span 11; }
.col-12 { grid-column: span 12; }

/* 自動配置 */
.col-auto { grid-column: auto; }
.col-fill { grid-column: 1 / -1; }
```

### 2. レスポンシブグリッド
```css
/* スモールデバイス (576px以上) */
@media (min-width: 576px) {
  .col-sm-1 { grid-column: span 1; }
  .col-sm-2 { grid-column: span 2; }
  .col-sm-3 { grid-column: span 3; }
  .col-sm-4 { grid-column: span 4; }
  .col-sm-5 { grid-column: span 5; }
  .col-sm-6 { grid-column: span 6; }
  .col-sm-7 { grid-column: span 7; }
  .col-sm-8 { grid-column: span 8; }
  .col-sm-9 { grid-column: span 9; }
  .col-sm-10 { grid-column: span 10; }
  .col-sm-11 { grid-column: span 11; }
  .col-sm-12 { grid-column: span 12; }
  .col-sm-auto { grid-column: auto; }
}

/* ミディアムデバイス (768px以上) */
@media (min-width: 768px) {
  .col-md-1 { grid-column: span 1; }
  .col-md-2 { grid-column: span 2; }
  .col-md-3 { grid-column: span 3; }
  .col-md-4 { grid-column: span 4; }
  .col-md-5 { grid-column: span 5; }
  .col-md-6 { grid-column: span 6; }
  .col-md-7 { grid-column: span 7; }
  .col-md-8 { grid-column: span 8; }
  .col-md-9 { grid-column: span 9; }
  .col-md-10 { grid-column: span 10; }
  .col-md-11 { grid-column: span 11; }
  .col-md-12 { grid-column: span 12; }
  .col-md-auto { grid-column: auto; }
}

/* ラージデバイス (992px以上) */
@media (min-width: 992px) {
  .col-lg-1 { grid-column: span 1; }
  .col-lg-2 { grid-column: span 2; }
  .col-lg-3 { grid-column: span 3; }
  .col-lg-4 { grid-column: span 4; }
  .col-lg-5 { grid-column: span 5; }
  .col-lg-6 { grid-column: span 6; }
  .col-lg-7 { grid-column: span 7; }
  .col-lg-8 { grid-column: span 8; }
  .col-lg-9 { grid-column: span 9; }
  .col-lg-10 { grid-column: span 10; }
  .col-lg-11 { grid-column: span 11; }
  .col-lg-12 { grid-column: span 12; }
  .col-lg-auto { grid-column: auto; }
}

/* エクストララージデバイス (1200px以上) */
@media (min-width: 1200px) {
  .col-xl-1 { grid-column: span 1; }
  .col-xl-2 { grid-column: span 2; }
  .col-xl-3 { grid-column: span 3; }
  .col-xl-4 { grid-column: span 4; }
  .col-xl-5 { grid-column: span 5; }
  .col-xl-6 { grid-column: span 6; }
  .col-xl-7 { grid-column: span 7; }
  .col-xl-8 { grid-column: span 8; }
  .col-xl-9 { grid-column: span 9; }
  .col-xl-10 { grid-column: span 10; }
  .col-xl-11 { grid-column: span 11; }
  .col-xl-12 { grid-column: span 12; }
  .col-xl-auto { grid-column: auto; }
}
```

### 3. 特殊グリッドレイアウト
```css
/* サブグリッド (対応ブラウザ用) */
.subgrid {
  display: subgrid;
  grid-template-columns: subgrid;
  grid-template-rows: subgrid;
}

/* 密集グリッド (カードレイアウト等) */
.grid-dense {
  grid-auto-flow: dense;
}

/* 等幅カラム */
.grid-equal {
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

/* 可変カラム */
.grid-auto {
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
}

/* マソンリーレイアウト風 */
.grid-masonry {
  columns: 3;
  column-gap: var(--grid-gap);
}

@media (max-width: 768px) {
  .grid-masonry {
    columns: 1;
  }
}

@media (min-width: 769px) and (max-width: 1024px) {
  .grid-masonry {
    columns: 2;
  }
}
```

## レスポンシブデザイン

### 1. ブレークポイント戦略
```css
/* Mobile First アプローチ */
:root {
  /* デフォルト: モバイル (320px以上) */
  --layout-padding: 1rem;
  --layout-gap: 0.5rem;
  --content-max-width: 100%;
  --sidebar-width: 100%;
  --header-height: 60px;
}

/* Small tablets (576px以上) */
@media (min-width: 576px) {
  :root {
    --layout-padding: 1.5rem;
    --layout-gap: 1rem;
    --content-max-width: 540px;
    --header-height: 64px;
  }
}

/* Large tablets (768px以上) */
@media (min-width: 768px) {
  :root {
    --layout-padding: 2rem;
    --layout-gap: 1.5rem;
    --content-max-width: 720px;
    --sidebar-width: 280px;
    --header-height: 72px;
  }
}

/* Small desktops (992px以上) */
@media (min-width: 992px) {
  :root {
    --layout-padding: 2.5rem;
    --layout-gap: 2rem;
    --content-max-width: 960px;
    --sidebar-width: 320px;
    --header-height: 80px;
  }
}

/* Large desktops (1200px以上) */
@media (min-width: 1200px) {
  :root {
    --layout-padding: 3rem;
    --layout-gap: 2.5rem;
    --content-max-width: 1140px;
    --sidebar-width: 360px;
  }
}

/* Extra large desktops (1400px以上) */
@media (min-width: 1400px) {
  :root {
    --content-max-width: 1320px;
  }
}
```

### 2. アダプティブレイアウト
```css
/* ゲームメインレイアウト */
.game-layout {
  display: grid;
  min-height: 100vh;
  grid-template-areas: 
    "header"
    "sidebar"
    "main"
    "footer";
  grid-template-rows: var(--header-height) auto 1fr auto;
  grid-template-columns: 1fr;
}

/* タブレット以上でサイドバーを横に */
@media (min-width: 768px) {
  .game-layout {
    grid-template-areas: 
      "header header"
      "sidebar main"
      "footer footer";
    grid-template-rows: var(--header-height) 1fr auto;
    grid-template-columns: var(--sidebar-width) 1fr;
  }
}

/* デスクトップでより複雑なレイアウト */
@media (min-width: 1200px) {
  .game-layout {
    grid-template-areas: 
      "header header header"
      "sidebar main aside"
      "footer footer footer";
    grid-template-columns: var(--sidebar-width) 1fr 280px;
  }
}

/* 各エリアの基本スタイル */
.game-header {
  grid-area: header;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  padding: 0 var(--layout-padding);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.game-sidebar {
  grid-area: sidebar;
  background: var(--color-surface-secondary);
  border-right: 1px solid var(--color-border);
  padding: var(--layout-padding);
  overflow-y: auto;
}

.game-main {
  grid-area: main;
  padding: var(--layout-padding);
  overflow-y: auto;
  background: var(--color-background);
}

.game-aside {
  grid-area: aside;
  background: var(--color-surface-secondary);
  border-left: 1px solid var(--color-border);
  padding: var(--layout-padding);
  overflow-y: auto;
}

.game-footer {
  grid-area: footer;
  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  padding: var(--spacing-4) var(--layout-padding);
  text-align: center;
}
```

### 3. Container Queries対応
```css
/* Container Queriesを使用した内在的レスポンシブ */
.adaptive-container {
  container-type: inline-size;
}

.adaptive-content {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-4);
}

/* コンテナが400px以上の場合 */
@container (min-width: 400px) {
  .adaptive-content {
    grid-template-columns: 1fr 1fr;
  }
}

/* コンテナが600px以上の場合 */
@container (min-width: 600px) {
  .adaptive-content {
    grid-template-columns: 1fr 2fr 1fr;
  }
}

/* コンテナが800px以上の場合 */
@container (min-width: 800px) {
  .adaptive-content {
    grid-template-columns: repeat(4, 1fr);
  }
}
```

## コンテナシステム

### 1. 基本コンテナ
```css
/* 基本コンテナ */
.container {
  width: 100%;
  margin: 0 auto;
  padding: 0 var(--layout-padding);
}

/* 固定幅コンテナ */
.container-sm {
  max-width: var(--container-sm);
}

.container-md {
  max-width: var(--container-md);
}

.container-lg {
  max-width: var(--container-lg);
}

.container-xl {
  max-width: var(--container-xl);
}

.container-xxl {
  max-width: var(--container-xxl);
}

/* 流体コンテナ */
.container-fluid {
  width: 100%;
  padding: 0 var(--layout-padding);
}

/* セクションコンテナ */
.section {
  padding: var(--spacing-8) 0;
}

.section-sm {
  padding: var(--spacing-6) 0;
}

.section-lg {
  padding: var(--spacing-12) 0;
}
```

### 2. 特殊コンテナ
```css
/* カードコンテナ */
.card-container {
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
}

/* パネルコンテナ */
.panel-container {
  background: var(--color-surface-secondary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-4);
  border: 1px solid var(--color-border);
}

/* モーダルコンテナ */
.modal-container {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-4);
  z-index: 1000;
}

.modal-content {
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-6);
  max-width: 90vw;
  max-height: 90vh;
  overflow: auto;
  box-shadow: var(--shadow-lg);
}
```

### 3. アスペクト比コンテナ
```css
/* アスペクト比固定コンテナ */
.aspect-ratio {
  position: relative;
  width: 100%;
}

.aspect-ratio::before {
  content: '';
  display: block;
  padding-bottom: var(--aspect-ratio, 100%);
}

.aspect-ratio > * {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

/* よく使用するアスペクト比 */
.aspect-1-1 { --aspect-ratio: 100%; }
.aspect-4-3 { --aspect-ratio: 75%; }
.aspect-16-9 { --aspect-ratio: 56.25%; }
.aspect-3-2 { --aspect-ratio: 66.67%; }
.aspect-golden { --aspect-ratio: 61.8%; }
```

## フレックスボックスユーティリティ

### 1. 基本フレックス
```css
/* フレックスコンテナ */
.flex {
  display: flex;
}

.inline-flex {
  display: inline-flex;
}

/* フレックス方向 */
.flex-row {
  flex-direction: row;
}

.flex-row-reverse {
  flex-direction: row-reverse;
}

.flex-col {
  flex-direction: column;
}

.flex-col-reverse {
  flex-direction: column-reverse;
}

/* フレックスラップ */
.flex-wrap {
  flex-wrap: wrap;
}

.flex-nowrap {
  flex-wrap: nowrap;
}

.flex-wrap-reverse {
  flex-wrap: wrap-reverse;
}

/* フレックス成長・収縮 */
.flex-1 {
  flex: 1 1 0%;
}

.flex-auto {
  flex: 1 1 auto;
}

.flex-initial {
  flex: 0 1 auto;
}

.flex-none {
  flex: none;
}

/* フレックス成長 */
.flex-grow {
  flex-grow: 1;
}

.flex-grow-0 {
  flex-grow: 0;
}

/* フレックス収縮 */
.flex-shrink {
  flex-shrink: 1;
}

.flex-shrink-0 {
  flex-shrink: 0;
}
```

### 2. アライメント
```css
/* 主軸の配置 (justify-content) */
.justify-start {
  justify-content: flex-start;
}

.justify-end {
  justify-content: flex-end;
}

.justify-center {
  justify-content: center;
}

.justify-between {
  justify-content: space-between;
}

.justify-around {
  justify-content: space-around;
}

.justify-evenly {
  justify-content: space-evenly;
}

/* 交差軸の配置 (align-items) */
.items-start {
  align-items: flex-start;
}

.items-end {
  align-items: flex-end;
}

.items-center {
  align-items: center;
}

.items-baseline {
  align-items: baseline;
}

.items-stretch {
  align-items: stretch;
}

/* 個別アイテムの配置 (align-self) */
.self-auto {
  align-self: auto;
}

.self-start {
  align-self: flex-start;
}

.self-end {
  align-self: flex-end;
}

.self-center {
  align-self: center;
}

.self-stretch {
  align-self: stretch;
}

.self-baseline {
  align-self: baseline;
}

/* 複数行の配置 (align-content) */
.content-start {
  align-content: flex-start;
}

.content-end {
  align-content: flex-end;
}

.content-center {
  align-content: center;
}

.content-between {
  align-content: space-between;
}

.content-around {
  align-content: space-around;
}

.content-evenly {
  align-content: space-evenly;
}
```

### 3. フレックスギャップ
```css
/* フレックスアイテム間のギャップ */
.gap-0 { gap: 0; }
.gap-1 { gap: var(--spacing-1); }
.gap-2 { gap: var(--spacing-2); }
.gap-3 { gap: var(--spacing-3); }
.gap-4 { gap: var(--spacing-4); }
.gap-5 { gap: var(--spacing-5); }
.gap-6 { gap: var(--spacing-6); }
.gap-8 { gap: var(--spacing-8); }
.gap-10 { gap: var(--spacing-10); }
.gap-12 { gap: var(--spacing-12); }

/* 水平ギャップ */
.gap-x-0 { column-gap: 0; }
.gap-x-1 { column-gap: var(--spacing-1); }
.gap-x-2 { column-gap: var(--spacing-2); }
.gap-x-3 { column-gap: var(--spacing-3); }
.gap-x-4 { column-gap: var(--spacing-4); }
.gap-x-5 { column-gap: var(--spacing-5); }
.gap-x-6 { column-gap: var(--spacing-6); }
.gap-x-8 { column-gap: var(--spacing-8); }

/* 垂直ギャップ */
.gap-y-0 { row-gap: 0; }
.gap-y-1 { row-gap: var(--spacing-1); }
.gap-y-2 { row-gap: var(--spacing-2); }
.gap-y-3 { row-gap: var(--spacing-3); }
.gap-y-4 { row-gap: var(--spacing-4); }
.gap-y-5 { row-gap: var(--spacing-5); }
.gap-y-6 { row-gap: var(--spacing-6); }
.gap-y-8 { row-gap: var(--spacing-8); }
```

## スペーシングシステム

### 1. 基本スペーシング
```css
:root {
  /* 8px基準のスペーシングスケール */
  --spacing-0: 0;
  --spacing-1: 0.25rem;  /* 4px */
  --spacing-2: 0.5rem;   /* 8px */
  --spacing-3: 0.75rem;  /* 12px */
  --spacing-4: 1rem;     /* 16px */
  --spacing-5: 1.25rem;  /* 20px */
  --spacing-6: 1.5rem;   /* 24px */
  --spacing-7: 1.75rem;  /* 28px */
  --spacing-8: 2rem;     /* 32px */
  --spacing-9: 2.25rem;  /* 36px */
  --spacing-10: 2.5rem;  /* 40px */
  --spacing-11: 2.75rem; /* 44px */
  --spacing-12: 3rem;    /* 48px */
  --spacing-14: 3.5rem;  /* 56px */
  --spacing-16: 4rem;    /* 64px */
  --spacing-20: 5rem;    /* 80px */
  --spacing-24: 6rem;    /* 96px */
  --spacing-28: 7rem;    /* 112px */
  --spacing-32: 8rem;    /* 128px */
}

/* マージンユーティリティ */
.m-0 { margin: var(--spacing-0); }
.m-1 { margin: var(--spacing-1); }
.m-2 { margin: var(--spacing-2); }
.m-3 { margin: var(--spacing-3); }
.m-4 { margin: var(--spacing-4); }
.m-5 { margin: var(--spacing-5); }
.m-6 { margin: var(--spacing-6); }
.m-8 { margin: var(--spacing-8); }
.m-10 { margin: var(--spacing-10); }
.m-12 { margin: var(--spacing-12); }
.m-16 { margin: var(--spacing-16); }
.m-20 { margin: var(--spacing-20); }
.m-24 { margin: var(--spacing-24); }
.m-32 { margin: var(--spacing-32); }

/* 自動マージン */
.m-auto { margin: auto; }

/* 水平マージン */
.mx-0 { margin-left: var(--spacing-0); margin-right: var(--spacing-0); }
.mx-1 { margin-left: var(--spacing-1); margin-right: var(--spacing-1); }
.mx-2 { margin-left: var(--spacing-2); margin-right: var(--spacing-2); }
.mx-3 { margin-left: var(--spacing-3); margin-right: var(--spacing-3); }
.mx-4 { margin-left: var(--spacing-4); margin-right: var(--spacing-4); }
.mx-5 { margin-left: var(--spacing-5); margin-right: var(--spacing-5); }
.mx-6 { margin-left: var(--spacing-6); margin-right: var(--spacing-6); }
.mx-8 { margin-left: var(--spacing-8); margin-right: var(--spacing-8); }
.mx-auto { margin-left: auto; margin-right: auto; }

/* 垂直マージン */
.my-0 { margin-top: var(--spacing-0); margin-bottom: var(--spacing-0); }
.my-1 { margin-top: var(--spacing-1); margin-bottom: var(--spacing-1); }
.my-2 { margin-top: var(--spacing-2); margin-bottom: var(--spacing-2); }
.my-3 { margin-top: var(--spacing-3); margin-bottom: var(--spacing-3); }
.my-4 { margin-top: var(--spacing-4); margin-bottom: var(--spacing-4); }
.my-5 { margin-top: var(--spacing-5); margin-bottom: var(--spacing-5); }
.my-6 { margin-top: var(--spacing-6); margin-bottom: var(--spacing-6); }
.my-8 { margin-top: var(--spacing-8); margin-bottom: var(--spacing-8); }

/* 個別マージン */
.mt-0 { margin-top: var(--spacing-0); }
.mt-1 { margin-top: var(--spacing-1); }
.mt-2 { margin-top: var(--spacing-2); }
.mt-3 { margin-top: var(--spacing-3); }
.mt-4 { margin-top: var(--spacing-4); }
.mt-5 { margin-top: var(--spacing-5); }
.mt-6 { margin-top: var(--spacing-6); }
.mt-8 { margin-top: var(--spacing-8); }

.mr-0 { margin-right: var(--spacing-0); }
.mr-1 { margin-right: var(--spacing-1); }
.mr-2 { margin-right: var(--spacing-2); }
.mr-3 { margin-right: var(--spacing-3); }
.mr-4 { margin-right: var(--spacing-4); }
.mr-5 { margin-right: var(--spacing-5); }
.mr-6 { margin-right: var(--spacing-6); }
.mr-8 { margin-right: var(--spacing-8); }

.mb-0 { margin-bottom: var(--spacing-0); }
.mb-1 { margin-bottom: var(--spacing-1); }
.mb-2 { margin-bottom: var(--spacing-2); }
.mb-3 { margin-bottom: var(--spacing-3); }
.mb-4 { margin-bottom: var(--spacing-4); }
.mb-5 { margin-bottom: var(--spacing-5); }
.mb-6 { margin-bottom: var(--spacing-6); }
.mb-8 { margin-bottom: var(--spacing-8); }

.ml-0 { margin-left: var(--spacing-0); }
.ml-1 { margin-left: var(--spacing-1); }
.ml-2 { margin-left: var(--spacing-2); }
.ml-3 { margin-left: var(--spacing-3); }
.ml-4 { margin-left: var(--spacing-4); }
.ml-5 { margin-left: var(--spacing-5); }
.ml-6 { margin-left: var(--spacing-6); }
.ml-8 { margin-left: var(--spacing-8); }
```

### 2. パディングユーティリティ
```css
/* パディングユーティリティ（マージンと同様の構造） */
.p-0 { padding: var(--spacing-0); }
.p-1 { padding: var(--spacing-1); }
.p-2 { padding: var(--spacing-2); }
.p-3 { padding: var(--spacing-3); }
.p-4 { padding: var(--spacing-4); }
.p-5 { padding: var(--spacing-5); }
.p-6 { padding: var(--spacing-6); }
.p-8 { padding: var(--spacing-8); }
.p-10 { padding: var(--spacing-10); }
.p-12 { padding: var(--spacing-12); }
.p-16 { padding: var(--spacing-16); }
.p-20 { padding: var(--spacing-20); }
.p-24 { padding: var(--spacing-24); }
.p-32 { padding: var(--spacing-32); }

/* 水平パディング */
.px-0 { padding-left: var(--spacing-0); padding-right: var(--spacing-0); }
.px-1 { padding-left: var(--spacing-1); padding-right: var(--spacing-1); }
.px-2 { padding-left: var(--spacing-2); padding-right: var(--spacing-2); }
.px-3 { padding-left: var(--spacing-3); padding-right: var(--spacing-3); }
.px-4 { padding-left: var(--spacing-4); padding-right: var(--spacing-4); }
.px-5 { padding-left: var(--spacing-5); padding-right: var(--spacing-5); }
.px-6 { padding-left: var(--spacing-6); padding-right: var(--spacing-6); }
.px-8 { padding-left: var(--spacing-8); padding-right: var(--spacing-8); }

/* 垂直パディング */
.py-0 { padding-top: var(--spacing-0); padding-bottom: var(--spacing-0); }
.py-1 { padding-top: var(--spacing-1); padding-bottom: var(--spacing-1); }
.py-2 { padding-top: var(--spacing-2); padding-bottom: var(--spacing-2); }
.py-3 { padding-top: var(--spacing-3); padding-bottom: var(--spacing-3); }
.py-4 { padding-top: var(--spacing-4); padding-bottom: var(--spacing-4); }
.py-5 { padding-top: var(--spacing-5); padding-bottom: var(--spacing-5); }
.py-6 { padding-top: var(--spacing-6); padding-bottom: var(--spacing-6); }
.py-8 { padding-top: var(--spacing-8); padding-bottom: var(--spacing-8); }

/* 個別パディング */
.pt-0 { padding-top: var(--spacing-0); }
.pt-1 { padding-top: var(--spacing-1); }
.pt-2 { padding-top: var(--spacing-2); }
.pt-3 { padding-top: var(--spacing-3); }
.pt-4 { padding-top: var(--spacing-4); }
.pt-5 { padding-top: var(--spacing-5); }
.pt-6 { padding-top: var(--spacing-6); }
.pt-8 { padding-top: var(--spacing-8); }

.pr-0 { padding-right: var(--spacing-0); }
.pr-1 { padding-right: var(--spacing-1); }
.pr-2 { padding-right: var(--spacing-2); }
.pr-3 { padding-right: var(--spacing-3); }
.pr-4 { padding-right: var(--spacing-4); }
.pr-5 { padding-right: var(--spacing-5); }
.pr-6 { padding-right: var(--spacing-6); }
.pr-8 { padding-right: var(--spacing-8); }

.pb-0 { padding-bottom: var(--spacing-0); }
.pb-1 { padding-bottom: var(--spacing-1); }
.pb-2 { padding-bottom: var(--spacing-2); }
.pb-3 { padding-bottom: var(--spacing-3); }
.pb-4 { padding-bottom: var(--spacing-4); }
.pb-5 { padding-bottom: var(--spacing-5); }
.pb-6 { padding-bottom: var(--spacing-6); }
.pb-8 { padding-bottom: var(--spacing-8); }

.pl-0 { padding-left: var(--spacing-0); }
.pl-1 { padding-left: var(--spacing-1); }
.pl-2 { padding-left: var(--spacing-2); }
.pl-3 { padding-left: var(--spacing-3); }
.pl-4 { padding-left: var(--spacing-4); }
.pl-5 { padding-left: var(--spacing-5); }
.pl-6 { padding-left: var(--spacing-6); }
.pl-8 { padding-left: var(--spacing-8); }
```

## ゲーム専用レイアウト

### 1. ゲームUIレイアウト
```css
/* ゲームメイン画面レイアウト */
.game-screen {
  display: grid;
  min-height: 100vh;
  grid-template-areas: 
    "header header header"
    "character-info main-content quick-actions"
    "inventory main-content messages"
    "footer footer footer";
  grid-template-rows: auto 1fr auto auto;
  grid-template-columns: 280px 1fr 240px;
  gap: var(--spacing-2);
  padding: var(--spacing-2);
  background: var(--color-background);
}

.game-header-area {
  grid-area: header;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
}

.character-info-area {
  grid-area: character-info;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
  overflow-y: auto;
}

.main-content-area {
  grid-area: main-content;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-sm);
  overflow-y: auto;
  min-height: 400px;
}

.quick-actions-area {
  grid-area: quick-actions;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
}

.inventory-area {
  grid-area: inventory;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
  overflow-y: auto;
}

.messages-area {
  grid-area: messages;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
  max-height: 200px;
  overflow-y: auto;
}

.game-footer-area {
  grid-area: footer;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-3);
  box-shadow: var(--shadow-sm);
  text-align: center;
}

/* タブレット対応 */
@media (max-width: 1024px) {
  .game-screen {
    grid-template-areas: 
      "header"
      "character-info"
      "main-content"
      "quick-actions"
      "inventory"
      "messages"
      "footer";
    grid-template-columns: 1fr;
    grid-template-rows: auto auto 1fr auto auto auto auto;
  }
}

/* モバイル対応 */
@media (max-width: 768px) {
  .game-screen {
    padding: var(--spacing-1);
    gap: var(--spacing-1);
  }
  
  .game-header-area,
  .character-info-area,
  .main-content-area,
  .quick-actions-area,
  .inventory-area,
  .messages-area,
  .game-footer-area {
    padding: var(--spacing-3);
  }
}
```

### 2. バトル画面レイアウト
```css
/* バトル画面専用レイアウト */
.battle-screen {
  display: grid;
  min-height: 100vh;
  grid-template-areas: 
    "battle-field battle-field"
    "player-status enemy-status"
    "battle-actions battle-log";
  grid-template-rows: 2fr auto 1fr;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-4);
  padding: var(--spacing-4);
  background: linear-gradient(135deg, 
    var(--color-background) 0%, 
    var(--color-surface-secondary) 100%);
}

.battle-field-area {
  grid-area: battle-field;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-md);
  display: flex;
  align-items: center;
  justify-content: space-around;
  min-height: 300px;
}

.player-status-area {
  grid-area: player-status;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
}

.enemy-status-area {
  grid-area: enemy-status;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
}

.battle-actions-area {
  grid-area: battle-actions;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
}

.battle-log-area {
  grid-area: battle-log;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
  overflow-y: auto;
}

/* モバイルバトル画面 */
@media (max-width: 768px) {
  .battle-screen {
    grid-template-areas: 
      "battle-field"
      "player-status"
      "enemy-status"
      "battle-actions"
      "battle-log";
    grid-template-columns: 1fr;
    grid-template-rows: auto auto auto auto 1fr;
  }
  
  .battle-field-area {
    min-height: 200px;
    flex-direction: column;
    gap: var(--spacing-4);
  }
}
```

### 3. インベントリレイアウト
```css
/* インベントリ画面レイアウト */
.inventory-screen {
  display: grid;
  grid-template-areas: 
    "inventory-grid item-details"
    "inventory-actions item-actions";
  grid-template-columns: 2fr 1fr;
  grid-template-rows: 1fr auto;
  gap: var(--spacing-4);
  padding: var(--spacing-4);
  min-height: 100vh;
}

.inventory-grid-area {
  grid-area: inventory-grid;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
}

.item-details-area {
  grid-area: item-details;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
}

.inventory-actions-area {
  grid-area: inventory-actions;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
}

.item-actions-area {
  grid-area: item-actions;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
}

/* インベントリグリッド */
.inventory-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: var(--spacing-2);
  max-width: 500px;
}

.inventory-slot {
  aspect-ratio: 1;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius-md);
  background: var(--color-surface-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all var(--transition-duration-normal);
}

.inventory-slot:hover {
  border-color: var(--color-primary-400);
  background: var(--color-primary-50);
}

.inventory-slot.occupied {
  background: var(--color-surface);
  border-color: var(--color-primary-600);
}

/* モバイルインベントリ */
@media (max-width: 768px) {
  .inventory-screen {
    grid-template-areas: 
      "inventory-grid"
      "item-details"
      "inventory-actions"
      "item-actions";
    grid-template-columns: 1fr;
    grid-template-rows: auto auto auto auto;
  }
  
  .inventory-grid {
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
  }
}
```

## モバイル最適化

### 1. タッチフレンドリーなレイアウト
```css
/* タッチターゲットサイズ */
.touch-target {
  min-height: 44px;
  min-width: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* タッチフィードバック */
.touch-feedback {
  transition: all var(--transition-duration-fast);
}

.touch-feedback:active {
  transform: scale(0.95);
  background-color: var(--color-primary-100);
}

/* スワイプ可能エリア */
.swipeable {
  touch-action: pan-x;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
}

.swipeable-item {
  scroll-snap-align: start;
  scroll-snap-stop: always;
}

/* モバイルナビゲーション */
.mobile-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  padding: var(--spacing-2);
  display: flex;
  justify-content: space-around;
  z-index: 100;
}

.mobile-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: var(--spacing-2);
  border-radius: var(--border-radius-md);
  transition: all var(--transition-duration-fast);
  min-width: 60px;
}

.mobile-nav-item:active {
  background: var(--color-primary-100);
}

.mobile-nav-icon {
  margin-bottom: var(--spacing-1);
}

.mobile-nav-label {
  font-size: var(--font-size-xs);
  text-align: center;
}
```

### 2. スクロール最適化
```css
/* スムーズスクロール */
.smooth-scroll {
  scroll-behavior: smooth;
}

/* オーバースクロール対応 */
.scroll-container {
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
}

/* スクロールスナップ */
.scroll-snap-container {
  scroll-snap-type: y mandatory;
  overflow-y: auto;
}

.scroll-snap-item {
  scroll-snap-align: start;
  scroll-snap-stop: always;
}

/* カスタムスクロールバー */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: var(--color-primary-400) var(--color-surface-secondary);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: var(--color-surface-secondary);
  border-radius: var(--border-radius-full);
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--color-primary-400);
  border-radius: var(--border-radius-full);
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--color-primary-600);
}
```

### 3. モバイル特有のパターン
```css
/* プルトゥリフレッシュ */
.pull-to-refresh {
  position: relative;
  overflow: hidden;
}

.pull-indicator {
  position: absolute;
  top: -60px;
  left: 50%;
  transform: translateX(-50%);
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-duration-normal);
}

.pull-to-refresh.pulling .pull-indicator {
  top: 0;
}

/* ボトムシート */
.bottom-sheet {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--color-surface);
  border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
  padding: var(--spacing-4);
  box-shadow: var(--shadow-lg);
  transform: translateY(100%);
  transition: transform var(--transition-duration-normal);
  z-index: 1000;
}

.bottom-sheet.open {
  transform: translateY(0);
}

.bottom-sheet-handle {
  width: 40px;
  height: 4px;
  background: var(--color-border);
  border-radius: var(--border-radius-full);
  margin: 0 auto var(--spacing-4);
}

/* セーフエリア対応 */
.safe-area-insets {
  padding-top: env(safe-area-inset-top);
  padding-right: env(safe-area-inset-right);
  padding-bottom: env(safe-area-inset-bottom);
  padding-left: env(safe-area-inset-left);
}
```

## アクセシビリティ配慮

### 1. フォーカス管理
```css
/* フォーカス表示 */
.focus-visible {
  outline: 2px solid var(--color-primary-600);
  outline-offset: 2px;
  border-radius: var(--border-radius-md);
}

/* フォーカストラップ */
.focus-trap {
  position: relative;
}

.focus-trap::before,
.focus-trap::after {
  content: '';
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

/* スキップリンク */
.skip-link {
  position: absolute;
  top: -40px;
  left: 6px;
  background: var(--color-primary-600);
  color: white;
  padding: var(--spacing-2) var(--spacing-4);
  border-radius: var(--border-radius-md);
  text-decoration: none;
  z-index: 10000;
  transition: top var(--transition-duration-fast);
}

.skip-link:focus {
  top: 6px;
}

/* 高コントラストモード対応 */
@media (prefers-contrast: high) {
  .game-layout {
    border: 2px solid;
  }
  
  .card-container,
  .panel-container {
    border: 2px solid;
  }
}

/* 縮小モーション対応 */
@media (prefers-reduced-motion: reduce) {
  .smooth-scroll {
    scroll-behavior: auto;
  }
  
  .transition-all {
    transition: none;
  }
}
```

### 2. スクリーンリーダー対応
```css
/* スクリーンリーダー専用テキスト */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* レイアウト情報の提供 */
.layout-info {
  position: absolute;
  top: -10000px;
  left: -10000px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

/* ARIAランドマーク強調 */
[role="main"],
[role="navigation"],
[role="banner"],
[role="contentinfo"],
[role="complementary"] {
  position: relative;
}

/* フォーカス可能要素の明確化 */
[tabindex="0"],
button,
input,
select,
textarea,
a[href] {
  position: relative;
}

[tabindex="0"]:focus,
button:focus,
input:focus,
select:focus,
textarea:focus,
a[href]:focus {
  outline: 2px solid var(--color-primary-600);
  outline-offset: 2px;
}
```

## 実装ガイドライン

### 1. レイアウトデバッガー
```javascript
class LayoutDebugger {
  constructor() {
    this.isEnabled = localStorage.getItem('debug-layout') === 'true';
    this.gridOverlay = null;
    this.spacingOverlay = null;
  }

  toggleGridOverlay() {
    if (this.gridOverlay) {
      this.removeGridOverlay();
    } else {
      this.addGridOverlay();
    }
  }

  addGridOverlay() {
    const style = document.createElement('style');
    style.id = 'grid-debug-style';
    style.textContent = `
      * {
        outline: 1px solid rgba(255, 0, 0, 0.3) !important;
      }
      
      .grid,
      [class*="grid"] {
        background: repeating-linear-gradient(
          90deg,
          rgba(255, 0, 0, 0.1),
          rgba(255, 0, 0, 0.1) 1px,
          transparent 1px,
          transparent calc(100% / 12)
        ) !important;
      }
      
      .container,
      [class*="container"] {
        background: rgba(0, 255, 0, 0.1) !important;
      }
    `;
    document.head.appendChild(style);
    this.gridOverlay = style;
  }

  removeGridOverlay() {
    if (this.gridOverlay) {
      this.gridOverlay.remove();
      this.gridOverlay = null;
    }
  }

  addSpacingOverlay() {
    const elements = document.querySelectorAll('*');
    elements.forEach(el => {
      const computed = window.getComputedStyle(el);
      const margin = computed.margin;
      const padding = computed.padding;
      
      if (margin !== '0px' || padding !== '0px') {
        el.style.boxShadow = `
          inset 0 0 0 2px rgba(0, 255, 0, 0.3),
          0 0 0 2px rgba(255, 0, 0, 0.3)
        `;
      }
    });
  }

  analyzeLayout() {
    const report = {
      containers: document.querySelectorAll('[class*="container"]').length,
      grids: document.querySelectorAll('.grid, [class*="grid"]').length,
      flexboxes: document.querySelectorAll('.flex, [class*="flex"]').length,
      responsiveElements: this.countResponsiveElements(),
      accessibility: this.checkAccessibility()
    };

    console.table(report);
    return report;
  }

  countResponsiveElements() {
    const breakpoints = ['sm', 'md', 'lg', 'xl', 'xxl'];
    let count = 0;
    
    breakpoints.forEach(bp => {
      count += document.querySelectorAll(`[class*="${bp}-"]`).length;
    });
    
    return count;
  }

  checkAccessibility() {
    return {
      landmarks: document.querySelectorAll('[role]').length,
      headings: document.querySelectorAll('h1, h2, h3, h4, h5, h6').length,
      skipLinks: document.querySelectorAll('.skip-link').length,
      focusableElements: document.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      ).length
    };
  }
}

// デバッグ機能を有効化
window.layoutDebugger = new LayoutDebugger();

// コンソールコマンド
if (window.layoutDebugger.isEnabled) {
  console.log('Layout Debugger Commands:');
  console.log('- window.layoutDebugger.toggleGridOverlay()');
  console.log('- window.layoutDebugger.addSpacingOverlay()');
  console.log('- window.layoutDebugger.analyzeLayout()');
}
```

### 2. レスポンシブテストツール
```javascript
class ResponsiveTestTool {
  constructor() {
    this.breakpoints = {
      xs: 0,
      sm: 576,
      md: 768,
      lg: 992,
      xl: 1200,
      xxl: 1400
    };
  }

  testBreakpoint(breakpoint) {
    const width = this.breakpoints[breakpoint];
    if (width !== undefined) {
      window.resizeTo(width + 100, 800);
      console.log(`Testing ${breakpoint} breakpoint (${width}px)`);
    }
  }

  testAllBreakpoints(callback) {
    Object.entries(this.breakpoints).forEach(([name, width], index) => {
      setTimeout(() => {
        this.testBreakpoint(name);
        if (callback) callback(name, width);
      }, index * 1000);
    });
  }

  captureLayoutScreenshots() {
    // 実際の実装では html2canvas などを使用
    console.log('Layout screenshots captured for all breakpoints');
  }

  generateResponsiveReport() {
    const report = {
      currentBreakpoint: this.getCurrentBreakpoint(),
      viewportSize: {
        width: window.innerWidth,
        height: window.innerHeight
      },
      devicePixelRatio: window.devicePixelRatio,
      orientation: window.screen.orientation?.type || 'unknown',
      layoutIssues: this.detectLayoutIssues()
    };

    console.log('Responsive Layout Report:', report);
    return report;
  }

  getCurrentBreakpoint() {
    const width = window.innerWidth;
    
    for (const [name, minWidth] of Object.entries(this.breakpoints).reverse()) {
      if (width >= minWidth) {
        return name;
      }
    }
    
    return 'xs';
  }

  detectLayoutIssues() {
    const issues = [];
    
    // 水平スクロールチェック
    if (document.documentElement.scrollWidth > document.documentElement.clientWidth) {
      issues.push('Horizontal scroll detected');
    }
    
    // オーバーフローチェック
    const elements = document.querySelectorAll('*');
    elements.forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.right > window.innerWidth) {
        issues.push(`Element overflow: ${el.tagName}.${el.className}`);
      }
    });
    
    // 小さすぎるタッチターゲット
    const touchTargets = document.querySelectorAll('button, a, input');
    touchTargets.forEach(target => {
      const rect = target.getBoundingClientRect();
      if (rect.width < 44 || rect.height < 44) {
        issues.push(`Small touch target: ${target.tagName}`);
      }
    });
    
    return issues;
  }
}

// レスポンシブテストツールを有効化
window.responsiveTester = new ResponsiveTestTool();
```

### 3. パフォーマンス監視
```javascript
class LayoutPerformanceMonitor {
  constructor() {
    this.observer = new PerformanceObserver(this.handlePerformanceEntries.bind(this));
    this.observer.observe({ entryTypes: ['layout-shift', 'largest-contentful-paint'] });
    
    this.metrics = {
      layoutShifts: [],
      cumulativeLayoutShift: 0,
      largestContentfulPaint: null
    };
  }

  handlePerformanceEntries(entries) {
    entries.getEntries().forEach(entry => {
      switch (entry.entryType) {
        case 'layout-shift':
          this.metrics.layoutShifts.push(entry);
          this.metrics.cumulativeLayoutShift += entry.value;
          break;
        case 'largest-contentful-paint':
          this.metrics.largestContentfulPaint = entry;
          break;
      }
    });
  }

  getMetrics() {
    return {
      ...this.metrics,
      cumulativeLayoutShift: Math.round(this.metrics.cumulativeLayoutShift * 1000) / 1000
    };
  }

  generateReport() {
    const metrics = this.getMetrics();
    const report = {
      performance: {
        cls: metrics.cumulativeLayoutShift,
        lcp: metrics.largestContentfulPaint?.value || 'Not measured',
        layoutShiftCount: metrics.layoutShifts.length
      },
      recommendations: this.generateRecommendations(metrics)
    };

    console.log('Layout Performance Report:', report);
    return report;
  }

  generateRecommendations(metrics) {
    const recommendations = [];
    
    if (metrics.cumulativeLayoutShift > 0.1) {
      recommendations.push('High Cumulative Layout Shift detected. Consider adding width/height attributes to images and reserve space for dynamic content.');
    }
    
    if (metrics.largestContentfulPaint?.value > 2500) {
      recommendations.push('Slow Largest Contentful Paint. Optimize critical rendering path and prioritize above-the-fold content.');
    }
    
    if (metrics.layoutShifts.length > 5) {
      recommendations.push('Multiple layout shifts detected. Review dynamic content loading patterns.');
    }
    
    return recommendations;
  }
}

// パフォーマンス監視を開始
window.layoutPerformanceMonitor = new LayoutPerformanceMonitor();
```

---

このレイアウトシステム書は、test_smgプロジェクトで一貫性があり、アクセシブルで、パフォーマンスに優れたレイアウトを実現するための包括的なガイドラインです。モバイルファーストアプローチを採用し、ゲーム特有のUI要件にも対応した設計となっています。実装時には、このシステムを活用して効率的で保守性の高いレイアウトを構築してください。