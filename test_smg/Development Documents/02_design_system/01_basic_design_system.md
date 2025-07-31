# 基本デザインシステム

## 文書の概要

- **作成日**: 2025年7月25日
- **対象システム**: test_smg（Laravel/PHPブラウザRPG）
- **作成者**: AI開発チーム
- **バージョン**: v1.0

## 目的

test_smgプロジェクトの統一されたデザインシステムを定義し、一貫性のあるユーザー体験とブランドアイデンティティを確立する。

## 目次

1. [デザインシステム概要](#デザインシステム概要)
2. [カラーシステム](#カラーシステム)
3. [タイポグラフィ](#タイポグラフィ)
4. [スペーシング](#スペーシング)
5. [グリッドシステム](#グリッドシステム)
6. [ブレークポイント](#ブレークポイント)
7. [影とエレベーション](#影とエレベーション)
8. [ボーダーと角丸](#ボーダーと角丸)
9. [アイコンシステム](#アイコンシステム)
10. [テーマシステム](#テーマシステム)

## デザインシステム概要

### 1. デザイン哲学
```
┌─────────────────────────────────┐
│        デザイン哲学             │
├─────────────────────────────────┤
│ 1. シンプルで親しみやすい       │
│    - CGIゲーム風の懐かしさ      │
│    - 現代的な洗練されたUI       │
├─────────────────────────────────┤
│ 2. 直感的で使いやすい           │
│    - 明確な視覚的階層          │
│    - 一貫したインタラクション   │
├─────────────────────────────────┤
│ 3. アクセシブルで包括的         │
│    - 色弱対応                  │
│    - 高コントラスト対応        │
├─────────────────────────────────┤
│ 4. レスポンシブで適応的         │
│    - モバイルファースト        │
│    - デバイス横断対応          │
└─────────────────────────────────┘
```

### 2. デザイントークン構造
```css
/* CSS カスタムプロパティとして定義 */
:root {
  /* === Colors === */
  --color-primary-50: #eff6ff;
  --color-primary-500: #3b82f6;
  --color-primary-900: #1e3a8a;
  
  /* === Spacing === */
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;
  
  /* === Typography === */
  --font-family-sans: 'Inter', system-ui, sans-serif;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  
  /* === Shadows === */
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  
  /* === Border Radius === */
  --radius-sm: 0.25rem;
  --radius-md: 0.375rem;
  --radius-lg: 0.5rem;
  --radius-xl: 0.75rem;
}
```

## カラーシステム

### 1. プライマリカラー（ブルー系）
```css
/* プライマリカラーパレット */
:root {
  --color-primary-50:  #eff6ff;
  --color-primary-100: #dbeafe;
  --color-primary-200: #bfdbfe;
  --color-primary-300: #93c5fd;
  --color-primary-400: #60a5fa;
  --color-primary-500: #3b82f6;  /* メインカラー */
  --color-primary-600: #2563eb;
  --color-primary-700: #1d4ed8;
  --color-primary-800: #1e40af;
  --color-primary-900: #1e3a8a;
  --color-primary-950: #172554;
}

/* 使用例 */
.btn-primary {
  background-color: var(--color-primary-500);
  color: white;
}

.btn-primary:hover {
  background-color: var(--color-primary-600);
}
```

### 2. セカンダリカラー（グリーン系）
```css
/* セカンダリカラーパレット */
:root {
  --color-secondary-50:  #f0fdf4;
  --color-secondary-100: #dcfce7;
  --color-secondary-200: #bbf7d0;
  --color-secondary-300: #86efac;
  --color-secondary-400: #4ade80;
  --color-secondary-500: #22c55e;  /* メインカラー */
  --color-secondary-600: #16a34a;
  --color-secondary-700: #15803d;
  --color-secondary-800: #166534;
  --color-secondary-900: #14532d;
  --color-secondary-950: #052e16;
}
```

### 3. ニュートラルカラー（グレー系）
```css
/* ニュートラルカラーパレット */
:root {
  --color-gray-50:  #f9fafb;
  --color-gray-100: #f3f4f6;
  --color-gray-200: #e5e7eb;
  --color-gray-300: #d1d5db;
  --color-gray-400: #9ca3af;
  --color-gray-500: #6b7280;
  --color-gray-600: #4b5563;
  --color-gray-700: #374151;
  --color-gray-800: #1f2937;
  --color-gray-900: #111827;
  --color-gray-950: #030712;
}
```

### 4. セマンティックカラー
```css
/* 状態を表すカラー */
:root {
  /* Success */
  --color-success-50:  #f0fdf4;
  --color-success-500: #22c55e;
  --color-success-600: #16a34a;
  --color-success-900: #14532d;
  
  /* Warning */
  --color-warning-50:  #fffbef;
  --color-warning-500: #f59e0b;
  --color-warning-600: #d97706;
  --color-warning-900: #78350f;
  
  /* Error */
  --color-error-50:  #fef2f2;
  --color-error-500: #ef4444;
  --color-error-600: #dc2626;
  --color-error-900: #7f1d1d;
  
  /* Info */
  --color-info-50:  #eff6ff;
  --color-info-500: #3b82f6;
  --color-info-600: #2563eb;
  --color-info-900: #1e3a8a;
}
```

### 5. ゲーム固有カラー
```css
/* ゲーム専用カラー */
:root {
  /* HP（体力） */
  --color-hp-high: #22c55e;    /* 緑 */
  --color-hp-medium: #f59e0b;  /* 黄 */
  --color-hp-low: #ef4444;     /* 赤 */
  --color-hp-critical: #991b1b; /* 濃い赤 */
  
  /* SP（スタミナ） */
  --color-sp-high: #3b82f6;    /* 青 */
  --color-sp-medium: #8b5cf6;  /* 紫 */
  --color-sp-low: #6b7280;     /* グレー */
  
  /* アイテム品質 */
  --color-quality-normal: #6b7280;     /* グレー */
  --color-quality-good: #22c55e;       /* 緑 */
  --color-quality-excellent: #3b82f6;  /* 青 */
  --color-quality-rare: #8b5cf6;       /* 紫 */
  --color-quality-legendary: #f59e0b;  /* オレンジ */
  
  /* 場所タイプ */
  --color-location-town: #22c55e;      /* 町：緑 */
  --color-location-road: #8b5a2b;      /* 道：茶色 */
  --color-location-dungeon: #6b7280;   /* ダンジョン：グレー */
  --color-location-shop: #f59e0b;      /* ショップ：オレンジ */
}
```

### 6. アクセシビリティ対応
```css
/* 高コントラストモード */
@media (prefers-contrast: high) {
  :root {
    --color-primary-500: #1e40af;
    --color-secondary-500: #15803d;
    --color-gray-500: #374151;
  }
}

/* カラーブラインド対応 */
:root {
  /* 形状やパターンで識別できるよう設計 */
  --pattern-success: url("data:image/svg+xml,%3csvg..."); /* チェックマーク */
  --pattern-warning: url("data:image/svg+xml,%3csvg..."); /* 三角形 */
  --pattern-error: url("data:image/svg+xml,%3csvg...");   /* X マーク */
}
```

## タイポグラフィ

### 1. フォントファミリー
```css
:root {
  /* プライマリフォント（UI用） */
  --font-family-sans: 
    'Inter', 
    'Hiragino Sans', 
    'Hiragino Kaku Gothic ProN', 
    'Noto Sans JP', 
    system-ui, 
    -apple-system, 
    BlinkMacSystemFont, 
    sans-serif;
  
  /* セカンダリフォント（ゲーム用） */
  --font-family-game: 
    'Roboto Mono', 
    'Courier New', 
    'Menlo', 
    'Monaco', 
    monospace;
  
  /* 数字用フォント */
  --font-family-mono: 
    'JetBrains Mono', 
    'Fira Code', 
    'Monaco', 
    'Consolas', 
    monospace;
}
```

### 2. フォントサイズスケール
```css
:root {
  /* Type Scale (Major Third: 1.25) */
  --font-size-xs:   0.75rem;   /* 12px */
  --font-size-sm:   0.875rem;  /* 14px */
  --font-size-base: 1rem;      /* 16px */
  --font-size-lg:   1.125rem;  /* 18px */
  --font-size-xl:   1.25rem;   /* 20px */
  --font-size-2xl:  1.5rem;    /* 24px */
  --font-size-3xl:  1.875rem;  /* 30px */
  --font-size-4xl:  2.25rem;   /* 36px */
  --font-size-5xl:  3rem;      /* 48px */
  --font-size-6xl:  3.75rem;   /* 60px */
}

/* 使用例 */
.text-xs { font-size: var(--font-size-xs); }
.text-sm { font-size: var(--font-size-sm); }
.text-base { font-size: var(--font-size-base); }
.text-lg { font-size: var(--font-size-lg); }
.text-xl { font-size: var(--font-size-xl); }
.text-2xl { font-size: var(--font-size-2xl); }
.text-3xl { font-size: var(--font-size-3xl); }
.text-4xl { font-size: var(--font-size-4xl); }
```

### 3. フォントウェイト
```css
:root {
  --font-weight-thin: 100;
  --font-weight-light: 300;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;
  --font-weight-extrabold: 800;
  --font-weight-black: 900;
}

/* 使用例 */
.font-normal { font-weight: var(--font-weight-normal); }
.font-medium { font-weight: var(--font-weight-medium); }
.font-semibold { font-weight: var(--font-weight-semibold); }
.font-bold { font-weight: var(--font-weight-bold); }
```

### 4. 行間（Line Height）
```css
:root {
  --line-height-none: 1;
  --line-height-tight: 1.25;
  --line-height-snug: 1.375;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.625;
  --line-height-loose: 2;
}

/* 使用例 */
.leading-tight { line-height: var(--line-height-tight); }
.leading-normal { line-height: var(--line-height-normal); }
.leading-relaxed { line-height: var(--line-height-relaxed); }
```

### 5. 文字間隔（Letter Spacing）
```css
:root {
  --letter-spacing-tighter: -0.05em;
  --letter-spacing-tight: -0.025em;
  --letter-spacing-normal: 0em;
  --letter-spacing-wide: 0.025em;
  --letter-spacing-wider: 0.05em;
  --letter-spacing-widest: 0.1em;
}

/* 使用例 */
.tracking-tight { letter-spacing: var(--letter-spacing-tight); }
.tracking-normal { letter-spacing: var(--letter-spacing-normal); }
.tracking-wide { letter-spacing: var(--letter-spacing-wide); }
```

### 6. タイポグラフィの組み合わせ
```css
/* 見出し */
.heading-1 {
  font-family: var(--font-family-sans);
  font-size: var(--font-size-4xl);
  font-weight: var(--font-weight-bold);
  line-height: var(--line-height-tight);
  letter-spacing: var(--letter-spacing-tight);
  color: var(--color-gray-900);
}

.heading-2 {
  font-family: var(--font-family-sans);
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-semibold);
  line-height: var(--line-height-tight);
  color: var(--color-gray-800);
}

.heading-3 {
  font-family: var(--font-family-sans);
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-semibold);
  line-height: var(--line-height-snug);
  color: var(--color-gray-800);
}

/* 本文 */
.body-lg {
  font-family: var(--font-family-sans);
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-normal);
  line-height: var(--line-height-relaxed);
  color: var(--color-gray-700);
}

.body-base {
  font-family: var(--font-family-sans);
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-normal);
  line-height: var(--line-height-normal);
  color: var(--color-gray-700);
}

.body-sm {
  font-family: var(--font-family-sans);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-normal);
  line-height: var(--line-height-normal);
  color: var(--color-gray-600);
}

/* ゲーム数値 */
.game-number {
  font-family: var(--font-family-mono);
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  line-height: var(--line-height-none);
  letter-spacing: var(--letter-spacing-wide);
}

/* キャプション */
.caption {
  font-family: var(--font-family-sans);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  line-height: var(--line-height-normal);
  color: var(--color-gray-500);
  text-transform: uppercase;
  letter-spacing: var(--letter-spacing-wide);
}
```

## スペーシング

### 1. スペーシングスケール
```css
:root {
  /* Base: 4px (0.25rem) */
  --spacing-0:   0;
  --spacing-px:  1px;
  --spacing-0-5: 0.125rem;  /* 2px */
  --spacing-1:   0.25rem;   /* 4px */
  --spacing-1-5: 0.375rem;  /* 6px */
  --spacing-2:   0.5rem;    /* 8px */
  --spacing-2-5: 0.625rem;  /* 10px */
  --spacing-3:   0.75rem;   /* 12px */
  --spacing-3-5: 0.875rem;  /* 14px */
  --spacing-4:   1rem;      /* 16px */
  --spacing-5:   1.25rem;   /* 20px */
  --spacing-6:   1.5rem;    /* 24px */
  --spacing-7:   1.75rem;   /* 28px */
  --spacing-8:   2rem;      /* 32px */
  --spacing-9:   2.25rem;   /* 36px */
  --spacing-10:  2.5rem;    /* 40px */
  --spacing-11:  2.75rem;   /* 44px */
  --spacing-12:  3rem;      /* 48px */
  --spacing-14:  3.5rem;    /* 56px */
  --spacing-16:  4rem;      /* 64px */
  --spacing-20:  5rem;      /* 80px */
  --spacing-24:  6rem;      /* 96px */
  --spacing-28:  7rem;      /* 112px */
  --spacing-32:  8rem;      /* 128px */
}
```

### 2. セマンティックスペーシング
```css
:root {
  /* コンポーネント内のスペーシング */
  --space-component-xs: var(--spacing-1);   /* 4px */
  --space-component-sm: var(--spacing-2);   /* 8px */
  --space-component-md: var(--spacing-4);   /* 16px */
  --space-component-lg: var(--spacing-6);   /* 24px */
  --space-component-xl: var(--spacing-8);   /* 32px */
  
  /* レイアウト間のスペーシング */
  --space-layout-xs: var(--spacing-4);   /* 16px */
  --space-layout-sm: var(--spacing-6);   /* 24px */
  --space-layout-md: var(--spacing-8);   /* 32px */
  --space-layout-lg: var(--spacing-12);  /* 48px */
  --space-layout-xl: var(--spacing-16);  /* 64px */
  
  /* セクション間のスペーシング */
  --space-section-xs: var(--spacing-8);   /* 32px */
  --space-section-sm: var(--spacing-12);  /* 48px */
  --space-section-md: var(--spacing-16);  /* 64px */
  --space-section-lg: var(--spacing-24);  /* 96px */
  --space-section-xl: var(--spacing-32);  /* 128px */
}
```

### 3. レスポンシブスペーシング
```css
/* モバイル */
@media (max-width: 640px) {
  :root {
    --space-responsive-xs: var(--spacing-2);
    --space-responsive-sm: var(--spacing-4);
    --space-responsive-md: var(--spacing-6);
    --space-responsive-lg: var(--spacing-8);
    --space-responsive-xl: var(--spacing-12);
  }
}

/* タブレット */
@media (min-width: 641px) and (max-width: 1024px) {
  :root {
    --space-responsive-xs: var(--spacing-3);
    --space-responsive-sm: var(--spacing-6);
    --space-responsive-md: var(--spacing-8);
    --space-responsive-lg: var(--spacing-12);
    --space-responsive-xl: var(--spacing-16);
  }
}

/* デスクトップ */
@media (min-width: 1025px) {
  :root {
    --space-responsive-xs: var(--spacing-4);
    --space-responsive-sm: var(--spacing-8);
    --space-responsive-md: var(--spacing-12);
    --space-responsive-lg: var(--spacing-16);
    --space-responsive-xl: var(--spacing-24);
  }
}
```

## グリッドシステム

### 1. コンテナ
```css
.container {
  width: 100%;
  margin-left: auto;
  margin-right: auto;
  padding-left: var(--spacing-4);
  padding-right: var(--spacing-4);
}

/* ブレークポイント別最大幅 */
@media (min-width: 640px) {
  .container {
    max-width: 640px;
    padding-left: var(--spacing-6);
    padding-right: var(--spacing-6);
  }
}

@media (min-width: 768px) {
  .container {
    max-width: 768px;
    padding-left: var(--spacing-8);
    padding-right: var(--spacing-8);
  }
}

@media (min-width: 1024px) {
  .container {
    max-width: 1024px;
  }
}

@media (min-width: 1280px) {
  .container {
    max-width: 1280px;
  }
}

@media (min-width: 1536px) {
  .container {
    max-width: 1536px;
  }
}
```

### 2. CSS Grid システム
```css
.grid {
  display: grid;
  gap: var(--spacing-4);
}

/* 基本的なグリッドレイアウト */
.grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
.grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.grid-cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
.grid-cols-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }

/* レスポンシブグリッド */
@media (min-width: 640px) {
  .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .sm\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (min-width: 768px) {
  .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .md\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

@media (min-width: 1024px) {
  .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
  .lg\:grid-cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
}
```

### 3. Flexbox システム
```css
.flex {
  display: flex;
}

.flex-col {
  flex-direction: column;
}

.flex-wrap {
  flex-wrap: wrap;
}

/* 主軸の配置 */
.justify-start { justify-content: flex-start; }
.justify-center { justify-content: center; }
.justify-end { justify-content: flex-end; }
.justify-between { justify-content: space-between; }
.justify-around { justify-content: space-around; }
.justify-evenly { justify-content: space-evenly; }

/* 交差軸の配置 */
.items-start { align-items: flex-start; }
.items-center { align-items: center; }
.items-end { align-items: flex-end; }
.items-stretch { align-items: stretch; }

/* Gap */
.gap-1 { gap: var(--spacing-1); }
.gap-2 { gap: var(--spacing-2); }
.gap-3 { gap: var(--spacing-3); }
.gap-4 { gap: var(--spacing-4); }
.gap-6 { gap: var(--spacing-6); }
.gap-8 { gap: var(--spacing-8); }
```

## ブレークポイント

### 1. ブレークポイント定義
```css
:root {
  --breakpoint-xs: 0px;      /* モバイル（小） */
  --breakpoint-sm: 640px;    /* モバイル（大） */
  --breakpoint-md: 768px;    /* タブレット */
  --breakpoint-lg: 1024px;   /* デスクトップ（小） */
  --breakpoint-xl: 1280px;   /* デスクトップ（大） */
  --breakpoint-2xl: 1536px;  /* デスクトップ（特大） */
}
```

### 2. メディアクエリミックスイン（SCSS）
```scss
// ブレークポイントミックスイン
@mixin mobile-only {
  @media (max-width: 639px) {
    @content;
  }
}

@mixin mobile-up {
  @media (min-width: 640px) {
    @content;
  }
}

@mixin tablet-only {
  @media (min-width: 640px) and (max-width: 1023px) {
    @content;
  }
}

@mixin tablet-up {
  @media (min-width: 768px) {
    @content;
  }
}

@mixin desktop-only {
  @media (min-width: 1024px) and (max-width: 1279px) {
    @content;
  }
}

@mixin desktop-up {
  @media (min-width: 1024px) {
    @content;
  }
}

@mixin large-desktop-up {
  @media (min-width: 1280px) {
    @content;
  }
}

// 使用例
.game-layout {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
  
  @include tablet-up {
    flex-direction: row;
    gap: var(--spacing-6);
  }
  
  @include desktop-up {
    gap: var(--spacing-8);
  }
}
```

## 影とエレベーション

### 1. 影のスケール
```css
:root {
  /* 影のレベル */
  --shadow-none: none;
  --shadow-xs:   0 1px 0 0 rgb(0 0 0 / 0.05);
  --shadow-sm:   0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-base: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md:   0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg:   0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --shadow-xl:   0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
  --shadow-2xl:  0 25px 50px -12px rgb(0 0 0 / 0.25);
  --shadow-inner: inset 0 2px 4px 0 rgb(0 0 0 / 0.05);
}

/* エレベーション別の影 */
:root {
  --elevation-0: var(--shadow-none);     /* Ground level */
  --elevation-1: var(--shadow-sm);       /* Raised */
  --elevation-2: var(--shadow-base);     /* Floating */
  --elevation-3: var(--shadow-md);       /* Hover */
  --elevation-4: var(--shadow-lg);       /* Modal */
  --elevation-5: var(--shadow-xl);       /* Dropdown */
  --elevation-6: var(--shadow-2xl);      /* Tooltip */
}
```

### 2. コンポーネント別の影
```css
/* カード */
.card {
  box-shadow: var(--elevation-1);
  transition: box-shadow 0.2s ease-in-out;
}

.card:hover {
  box-shadow: var(--elevation-2);
}

/* ボタン */
.btn {
  box-shadow: var(--elevation-1);
  transition: box-shadow 0.15s ease-in-out;
}

.btn:hover {
  box-shadow: var(--elevation-2);
}

.btn:active {
  box-shadow: var(--shadow-inner);
}

/* モーダル */
.modal {
  box-shadow: var(--elevation-4);
}

/* ドロップダウン */
.dropdown {
  box-shadow: var(--elevation-5);
}

/* ツールチップ */
.tooltip {
  box-shadow: var(--elevation-6);
}
```

### 3. ゲーム特有の影
```css
/* ゲーム要素用の特別な影 */
:root {
  --shadow-game-card: 
    0 4px 8px 0 rgb(0 0 0 / 0.12),
    0 2px 4px 0 rgb(0 0 0 / 0.08),
    inset 0 1px 0 0 rgb(255 255 255 / 0.05);
    
  --shadow-dice: 
    0 8px 16px 0 rgb(0 0 0 / 0.15),
    0 4px 8px 0 rgb(0 0 0 / 0.1),
    inset 0 1px 2px 0 rgb(255 255 255 / 0.1);
    
  --shadow-character-avatar:
    0 4px 12px 0 rgb(0 0 0 / 0.15),
    0 0 0 2px var(--color-primary-500);
}

.dice {
  box-shadow: var(--shadow-dice);
}

.character-avatar {
  box-shadow: var(--shadow-character-avatar);
}

.game-card {
  box-shadow: var(--shadow-game-card);
}
```

## ボーダーと角丸

### 1. ボーダー幅
```css
:root {
  --border-width-0: 0px;
  --border-width-1: 1px;
  --border-width-2: 2px;
  --border-width-4: 4px;
  --border-width-8: 8px;
}

/* 使用例 */
.border { border-width: var(--border-width-1); }
.border-2 { border-width: var(--border-width-2); }
.border-4 { border-width: var(--border-width-4); }
```

### 2. 角丸（Border Radius）
```css
:root {
  --radius-none: 0px;
  --radius-xs:   0.125rem;  /* 2px */
  --radius-sm:   0.25rem;   /* 4px */
  --radius-base: 0.375rem;  /* 6px */
  --radius-md:   0.5rem;    /* 8px */
  --radius-lg:   0.75rem;   /* 12px */
  --radius-xl:   1rem;      /* 16px */
  --radius-2xl:  1.5rem;    /* 24px */
  --radius-3xl:  2rem;      /* 32px */
  --radius-full: 9999px;    /* 完全な円形 */
}

/* 使用例 */
.rounded-none { border-radius: var(--radius-none); }
.rounded-sm { border-radius: var(--radius-sm); }
.rounded { border-radius: var(--radius-base); }
.rounded-md { border-radius: var(--radius-md); }
.rounded-lg { border-radius: var(--radius-lg); }
.rounded-xl { border-radius: var(--radius-xl); }
.rounded-full { border-radius: var(--radius-full); }
```

### 3. コンポーネント別の角丸
```css
/* コンポーネント別の角丸設定 */
:root {
  --radius-button: var(--radius-md);
  --radius-card: var(--radius-lg);
  --radius-input: var(--radius-base);
  --radius-modal: var(--radius-xl);
  --radius-avatar: var(--radius-full);
  --radius-badge: var(--radius-full);
}

.btn {
  border-radius: var(--radius-button);
}

.card {
  border-radius: var(--radius-card);
}

.input {
  border-radius: var(--radius-input);
}

.modal {
  border-radius: var(--radius-modal);
}

.avatar {
  border-radius: var(--radius-avatar);
}

.badge {
  border-radius: var(--radius-badge);
}
```

## アイコンシステム

### 1. アイコンサイズ
```css
:root {
  --icon-size-xs:  0.75rem;  /* 12px */
  --icon-size-sm:  1rem;     /* 16px */
  --icon-size-base: 1.25rem; /* 20px */
  --icon-size-md:  1.5rem;   /* 24px */
  --icon-size-lg:  2rem;     /* 32px */
  --icon-size-xl:  3rem;     /* 48px */
  --icon-size-2xl: 4rem;     /* 64px */
}

.icon {
  width: var(--icon-size-base);
  height: var(--icon-size-base);
  fill: currentColor;
}

.icon-xs { width: var(--icon-size-xs); height: var(--icon-size-xs); }
.icon-sm { width: var(--icon-size-sm); height: var(--icon-size-sm); }
.icon-md { width: var(--icon-size-md); height: var(--icon-size-md); }
.icon-lg { width: var(--icon-size-lg); height: var(--icon-size-lg); }
.icon-xl { width: var(--icon-size-xl); height: var(--icon-size-xl); }
.icon-2xl { width: var(--icon-size-2xl); height: var(--icon-size-2xl); }
```

### 2. ゲーム用アイコン定義
```html
<!-- SVGスプライト定義 -->
<svg style="display: none;">
  <defs>
    <!-- ダイス -->
    <symbol id="icon-dice" viewBox="0 0 24 24">
      <path d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5zm7 3a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm-4 4a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm8 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm-4 4a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
    </symbol>
    
    <!-- ハート（HP） -->
    <symbol id="icon-heart" viewBox="0 0 24 24">
      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
    </symbol>
    
    <!-- 稲妻（SP） -->
    <symbol id="icon-lightning" viewBox="0 0 24 24">
      <path d="M11 21h-1l1-7H7.5c-.58 0-.57-.32-.38-.66.19-.34.05-.08.07-.12C8.48 10.94 10.42 7.54 13 3h1l-1 7h3.5c.49 0 .56.33.47.51l-.07.15C12.96 17.55 11 21 11 21z"/>
    </symbol>
    
    <!-- 剣（攻撃） -->
    <symbol id="icon-sword" viewBox="0 0 24 24">
      <path d="M6.92 5L12 10.07L17.08 5H19.5L12.5 12h-.5v-.5L5.5 5h1.42zm.93 14c-.39-.39-.39-1.02 0-1.41L9.17 16.5H14.83l1.32 1.09c.39.39.39 1.02 0 1.41-.39.39-1.02.39-1.41 0L14 18.5H10l-.74.5c-.39.39-1.02.39-1.41 0z"/>
    </symbol>
    
    <!-- 盾（防御） -->
    <symbol id="icon-shield" viewBox="0 0 24 24">
      <path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M12,7C13.4,7 14.8,8.6 14.8,10V14H15.5V17H8.5V14H9.2V10C9.2,8.6 10.6,7 12,7M12,8.2C11.2,8.2 10.4,8.7 10.4,10V14H13.6V10C13.6,8.7 12.8,8.2 12,8.2Z"/>
    </symbol>
    
    <!-- 移動 -->
    <symbol id="icon-move" viewBox="0 0 24 24">
      <path d="M13,20H11V8L5.5,13.5L4.08,12.08L12,4.16L19.92,12.08L18.5,13.5L13,8V20Z"/>
    </symbol>
    
    <!-- 設定 -->
    <symbol id="icon-settings" viewBox="0 0 24 24">
      <path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>
    </symbol>
  </defs>
</svg>

<!-- アイコン使用例 -->
<svg class="icon icon-md text-red-500">
  <use href="#icon-heart"></use>
</svg>
```

## テーマシステム

### 1. ライトテーマ（デフォルト）
```css
[data-theme="light"] {
  /* 背景色 */
  --color-background: var(--color-gray-50);
  --color-surface: var(--color-gray-100);
  --color-card: #ffffff;
  
  /* テキスト色 */
  --color-text-primary: var(--color-gray-900);
  --color-text-secondary: var(--color-gray-700);
  --color-text-muted: var(--color-gray-500);
  
  /* ボーダー色 */
  --color-border: var(--color-gray-200);
  --color-border-light: var(--color-gray-100);
  
  /* 入力フィールド */
  --color-input-background: #ffffff;
  --color-input-border: var(--color-gray-300);
  --color-input-focus: var(--color-primary-500);
}
```

### 2. ダークテーマ
```css
[data-theme="dark"] {
  /* 背景色 */
  --color-background: var(--color-gray-900);
  --color-surface: var(--color-gray-800);
  --color-card: var(--color-gray-800);
  
  /* テキスト色 */
  --color-text-primary: var(--color-gray-100);
  --color-text-secondary: var(--color-gray-300);
  --color-text-muted: var(--color-gray-500);
  
  /* ボーダー色 */
  --color-border: var(--color-gray-700);
  --color-border-light: var(--color-gray-600);
  
  /* 入力フィールド */
  --color-input-background: var(--color-gray-700);
  --color-input-border: var(--color-gray-600);
  --color-input-focus: var(--color-primary-400);
  
  /* ゲーム特有の調整 */
  --color-hp-high: #10b981;
  --color-sp-high: #60a5fa;
}
```

### 3. テーマ切り替えJavaScript
```javascript
// テーマ管理システム
class ThemeManager {
  constructor() {
    this.currentTheme = this.getStoredTheme() || 'light';
    this.applyTheme(this.currentTheme);
  }
  
  getStoredTheme() {
    return localStorage.getItem('theme');
  }
  
  storeTheme(theme) {
    localStorage.setItem('theme', theme);
  }
  
  applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    this.currentTheme = theme;
    this.storeTheme(theme);
  }
  
  toggleTheme() {
    const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
    this.applyTheme(newTheme);
  }
  
  getTheme() {
    return this.currentTheme;
  }
}

// グローバルインスタンス
window.themeManager = new ThemeManager();

// システム設定に従うテーマの検出
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
if (!localStorage.getItem('theme')) {
  window.themeManager.applyTheme(prefersDark.matches ? 'dark' : 'light');
}

// システム設定変更の監視
prefersDark.addEventListener('change', (e) => {
  if (!localStorage.getItem('theme')) {
    window.themeManager.applyTheme(e.matches ? 'dark' : 'light');
  }
});
```

### 4. テーマ切り替えボタン
```html
<button 
  id="theme-toggle" 
  class="btn btn-ghost"
  aria-label="テーマを切り替え"
>
  <svg class="icon hidden dark:block">
    <use href="#icon-sun"></use>
  </svg>
  <svg class="icon block dark:hidden">
    <use href="#icon-moon"></use>
  </svg>
</button>

<script>
document.getElementById('theme-toggle').addEventListener('click', () => {
  window.themeManager.toggleTheme();
});
</script>
```

## まとめ

### デザインシステム活用ガイドライン
1. **一貫性の維持**: 定義されたトークンを必ず使用
2. **レスポンシブ対応**: ブレークポイントに応じた適切なサイズ設定
3. **アクセシビリティ**: コントラスト比とキーボードナビゲーションの確保
4. **パフォーマンス**: CSS カスタムプロパティを活用した効率的なスタイリング
5. **保守性**: セマンティックな命名とモジュラーな構造

このデザインシステムにより、test_smgの統一されたUI/UXを実現できます。