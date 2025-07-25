# デザインルール - Apple Design Guidelines準拠

## デザイン哲学

このデザインシステムは、AppleのHuman Interface Guidelinesの原則に基づき、シンプルで直感的なCGIゲーム風UIを現代的な技術で実現します。

### 核となる原則

1. **クラリティ（明瞭性）**: すべての要素が明確で理解しやすい
2. **コンシステンシー（一貫性）**: 統一された見た目と操作感
3. **デプス（奥行き）**: 視覚的階層による情報整理
4. **シンプリシティ（簡潔性）**: 不要な装飾を排除した本質的なデザイン

---

## カラーシステム

### システムカラー（Apple準拠）

```css
/* プライマリ（ダークスレート - 現在の実装と統一） */
--color-primary-50: #f8fafc;
--color-primary-100: #f1f5f9;
--color-primary-200: #e2e8f0;
--color-primary-300: #cbd5e1;
--color-primary-400: #94a3b8;
--color-primary-500: #0f172a;  /* ダークスレート - 現在の実装 (#0f172a) */
--color-primary-600: #1e293b;  /* ホバー状態 - 現在の実装 (#1e293b) */
--color-primary-700: #334155;
--color-primary-800: #475569;
--color-primary-900: #64748b;
--color-primary-950: #020617;

/* セカンダリ（システムオレンジ） */
--color-secondary-50: #fff7ed;
--color-secondary-100: #ffedd5;
--color-secondary-200: #fed7aa;
--color-secondary-300: #fdba74;
--color-secondary-400: #fb923c;
--color-secondary-500: #f97316;  /* システムオレンジ */
--color-secondary-600: #ea580c;
--color-secondary-700: #c2410c;
--color-secondary-800: #9a3412;
--color-secondary-900: #7c2d12;
```

### ニュートラルカラー（システムグレー）

```css
/* システムグレー階層 */
--color-neutral-50: #fafafa;
--color-neutral-100: #f4f4f5;
--color-neutral-200: #e4e4e7;
--color-neutral-300: #d4d4d8;
--color-neutral-400: #a1a1aa;
--color-neutral-500: #71717a;  /* システムグレー基準 */
--color-neutral-600: #52525b;
--color-neutral-700: #3f3f46;
--color-neutral-800: #27272a;
--color-neutral-900: #18181b;
--color-neutral-950: #09090b;
```

### セマンティックカラー（Apple準拠）

```css
/* システムカラー準拠 */
--color-success: #10b981;    /* システムグリーン */
--color-warning: #f59e0b;    /* システムアンバー */
--color-error: #ef4444;      /* システムレッド */
--color-info: #06b6d4;       /* システムシアン */

/* ゲーム専用カラー */
--color-game-town: #059669;     /* 町（システムエメラルド） */
--color-game-road: #a16207;     /* 道（システムアンバー暗め） */
--color-game-battle: #dc2626;   /* 戦闘（システムレッド） */
--color-game-shop: #7c3aed;     /* ショップ（システムバイオレット） */
```

### 背景とサーフェス

```css
/* 背景レイヤー */
--surface-background: #ffffff;
--surface-primary: #fafafa;
--surface-secondary: #f4f4f5;
--surface-tertiary: rgba(0, 0, 0, 0.02);
--surface-quarternary: rgba(0, 0, 0, 0.04);

/* テキストカラー */
--text-primary: #18181b;
--text-secondary: #71717a;
--text-tertiary: #a1a1aa;
--text-quarternary: #d4d4d8;
--text-inverse: #ffffff;

/* ボーダー */
--border-primary: #e4e4e7;
--border-secondary: #d4d4d8;
--border-tertiary: #a1a1aa;
```

---

## タイポグラフィ

### フォントファミリー（Apple準拠）

```css
/* システムフォント優先 */
--font-system: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
--font-display: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
--font-mono: ui-monospace, 'SF Mono', 'Monaco', 'Cascadia Code', monospace;

/* 日本語対応（Noto Sans JP追加） */
--font-primary: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans JP', system-ui, sans-serif;
```

### タイプスケール（Apple準拠）

```css
/* Apple標準のタイプスケール */
--text-3xs: 0.625rem;    /* 10px - Caption2 */
--text-2xs: 0.6875rem;   /* 11px - Caption1 */
--text-xs: 0.75rem;      /* 12px - Footnote */
--text-sm: 0.8125rem;    /* 13px - Subheadline */
--text-base: 1rem;       /* 16px - Body */
--text-lg: 1.0625rem;    /* 17px - Headline */
--text-xl: 1.375rem;     /* 22px - Title3 */
--text-2xl: 1.750rem;    /* 28px - Title2 */
--text-3xl: 2.125rem;    /* 34px - Title1 */
--text-4xl: 2.625rem;    /* 42px - Large Title */

/* ゲーム専用サイズ */
--text-dice: 1.5rem;     /* 24px - サイコロ表示 */
--text-status: 1.125rem; /* 18px - ゲーム状態 */
```

### フォントウェイト

```css
/* Apple標準ウェイト */
--font-ultralight: 100;
--font-thin: 200;
--font-light: 300;
--font-regular: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
--font-heavy: 800;
--font-black: 900;
```

### 行間（Line Height）

```css
/* Apple推奨値 */
--leading-none: 1;
--leading-tight: 1.2;
--leading-snug: 1.3;
--leading-normal: 1.5;
--leading-relaxed: 1.6;
--leading-loose: 2;
```

---

## スペーシングシステム

### Apple推奨スペーシング（4ptグリッド）

```css
/* 4ptベーススペーシング */
--space-0: 0;
--space-px: 1px;
--space-0-5: 0.125rem;  /* 2px */
--space-1: 0.25rem;     /* 4px */
--space-1-5: 0.375rem;  /* 6px */
--space-2: 0.5rem;      /* 8px */
--space-2-5: 0.625rem;  /* 10px */
--space-3: 0.75rem;     /* 12px */
--space-3-5: 0.875rem;  /* 14px */
--space-4: 1rem;        /* 16px */
--space-5: 1.25rem;     /* 20px */
--space-6: 1.5rem;      /* 24px */
--space-7: 1.75rem;     /* 28px */
--space-8: 2rem;        /* 32px */
--space-9: 2.25rem;     /* 36px */
--space-10: 2.5rem;     /* 40px */
--space-11: 2.75rem;    /* 44px */
--space-12: 3rem;       /* 48px */
--space-14: 3.5rem;     /* 56px */
--space-16: 4rem;       /* 64px */
--space-20: 5rem;       /* 80px */
--space-24: 6rem;       /* 96px */
```

### ゲーム専用スペーシング

```css
/* コンポーネント専用 */
--spacing-card-padding: var(--space-6);      /* 24px - カード内余白 */
--spacing-section-gap: var(--space-8);       /* 32px - セクション間 */
--spacing-button-gap: var(--space-3);        /* 12px - ボタン間隔 */
--spacing-choice-gap: var(--space-2);        /* 8px - 選択肢間隔 */
--spacing-inline-gap: var(--space-4);        /* 16px - インライン要素間 */
```

---

## ボーダー半径（Corner Radius）

### Apple準拠の角丸システム

```css
/* Apple標準角丸値 */
--radius-none: 0;
--radius-xs: 0.125rem;    /* 2px */
--radius-sm: 0.25rem;     /* 4px */
--radius-default: 0.375rem; /* 6px - Apple標準 */
--radius-md: 0.5rem;      /* 8px */
--radius-lg: 0.75rem;     /* 12px */
--radius-xl: 1rem;        /* 16px */
--radius-2xl: 1.5rem;     /* 24px */
--radius-3xl: 2rem;       /* 32px */
--radius-full: 9999px;    /* 完全な円形 */

/* コンポーネント専用 */
--radius-button: var(--radius-default);    /* 6px - ボタン */
--radius-card: var(--radius-lg);           /* 12px - カード */
--radius-input: var(--radius-default);     /* 6px - 入力フィールド */
--radius-badge: var(--radius-full);        /* 完全円形 - バッジ */
```

---

## シャドウシステム（Apple風）

### エレベーション対応シャドウ

```css
/* Apple準拠の自然な影 */
--shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
--shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
--shadow-default: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
--shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
--shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);

/* インタラクション用シャドウ */
--shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
--shadow-focus: 0 0 0 3px rgba(59, 130, 246, 0.3);
--shadow-focus-visible: 0 0 0 2px rgba(59, 130, 246, 0.8);

/* コンポーネント専用 */
--shadow-button: var(--shadow-sm);
--shadow-button-hover: var(--shadow-md);
--shadow-button-active: var(--shadow-xs);
--shadow-card: var(--shadow-default);
--shadow-card-hover: var(--shadow-lg);
--shadow-dialog: var(--shadow-2xl);
```

---

## コンポーネント設計

### ボタンコンポーネント

```css
/* 基本ボタン（Apple準拠） */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-2-5) var(--space-4);
  border-radius: var(--radius-button);
  font-family: var(--font-primary);
  font-size: var(--text-base);
  font-weight: var(--font-medium);
  line-height: var(--leading-tight);
  text-decoration: none;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  min-height: 44px; /* Apple推奨タッチターゲット */
  min-width: 44px;
  box-shadow: var(--shadow-button);
  user-select: none;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-button-hover);
}

.btn:active {
  transform: translateY(0);
  box-shadow: var(--shadow-button-active);
}

.btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus-visible);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: var(--shadow-xs);
}

/* ボタンバリエーション */
.btn-primary {
  background-color: var(--color-primary-500);
  color: var(--text-inverse);
}

.btn-primary:hover {
  background-color: var(--color-primary-600);
}

.btn-primary:active {
  background-color: var(--color-primary-700);
}

.btn-secondary {
  background-color: var(--surface-background);
  color: var(--text-primary);
  border-color: var(--border-primary);
}

.btn-secondary:hover {
  background-color: var(--surface-primary);
  border-color: var(--border-secondary);
}

.btn-success {
  background-color: var(--color-success);
  color: var(--text-inverse);
}

.btn-warning {
  background-color: var(--color-warning);
  color: var(--text-inverse);
}

.btn-error {
  background-color: var(--color-error);
  color: var(--text-inverse);
}

/* サイズバリエーション */
.btn-xs {
  padding: var(--space-1) var(--space-2);
  font-size: var(--text-xs);
  min-height: 32px;
  min-width: 32px;
}

.btn-sm {
  padding: var(--space-2) var(--space-3);
  font-size: var(--text-sm);
  min-height: 36px;
  min-width: 36px;
}

.btn-lg {
  padding: var(--space-3) var(--space-6);
  font-size: var(--text-lg);
  min-height: 52px;
  min-width: 52px;
}

.btn-xl {
  padding: var(--space-4) var(--space-8);
  font-size: var(--text-xl);
  min-height: 60px;
  min-width: 60px;
}
```

### カードコンポーネント

```css
/* ゲーム情報カード */
.card {
  background-color: var(--surface-background);
  border-radius: var(--radius-card);
  padding: var(--spacing-card-padding);
  box-shadow: var(--shadow-card);
  border: 1px solid var(--border-primary);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-card-hover);
  border-color: var(--border-secondary);
}

.card-title {
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  color: var(--text-primary);
  margin: 0 0 var(--space-4) 0;
  line-height: var(--leading-tight);
}

.card-content {
  color: var(--text-secondary);
  font-size: var(--text-base);
  line-height: var(--leading-normal);
}

.card-actions {
  margin-top: var(--space-6);
  display: flex;
  gap: var(--spacing-button-gap);
  flex-wrap: wrap;
}

/* カードバリエーション */
.card-elevated {
  box-shadow: var(--shadow-lg);
}

.card-interactive {
  cursor: pointer;
}

.card-compact {
  padding: var(--space-4);
}

/* ゲーム専用カード */
.card-location {
  background: linear-gradient(135deg, var(--color-game-town), var(--color-success));
  color: var(--text-inverse);
  border: none;
}

.card-location .card-title {
  color: var(--text-inverse);
}

.card-battle {
  border-color: var(--color-game-battle);
  background: linear-gradient(135deg, var(--surface-background), rgba(239, 68, 68, 0.05));
}
```

### フォームコンポーネント

```css
/* 入力フィールド */
.input {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  border: 1px solid var(--border-primary);
  border-radius: var(--radius-input);
  font-family: var(--font-primary);
  font-size: var(--text-base);
  line-height: var(--leading-normal);
  background-color: var(--surface-background);
  color: var(--text-primary);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  min-height: 44px; /* Apple推奨 */
}

.input:focus {
  outline: none;
  border-color: var(--color-primary-500);
  box-shadow: var(--shadow-focus);
}

.input:hover {
  border-color: var(--border-secondary);
}

.input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background-color: var(--surface-secondary);
}

.input::placeholder {
  color: var(--text-tertiary);
}

/* ラジオボタングループ */
.radio-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-choice-gap);
  margin: var(--space-4) 0;
}

.radio-option {
  display: flex;
  align-items: center;
  padding: var(--space-3) var(--space-4);
  border: 1px solid var(--border-primary);
  border-radius: var(--radius-default);
  background-color: var(--surface-background);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  min-height: 44px; /* Apple推奨 */
}

.radio-option:hover {
  border-color: var(--color-primary-500);
  background-color: var(--color-primary-50);
}

.radio-option input[type="radio"] {
  margin-right: var(--space-3);
  width: 20px;
  height: 20px;
  accent-color: var(--color-primary-500);
}

.radio-option:has(input:checked) {
  border-color: var(--color-primary-500);
  background-color: var(--color-primary-100);
}

.radio-option:focus-within {
  box-shadow: var(--shadow-focus);
}
```

### プログレスバー

```css
.progress {
  width: 100%;
  height: var(--space-6);
  background-color: var(--surface-secondary);
  border-radius: var(--radius-full);
  overflow: hidden;
  position: relative;
  border: 1px solid var(--border-primary);
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--color-primary-500), var(--color-primary-400));
  border-radius: var(--radius-full);
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-weight: var(--font-medium);
  color: var(--text-primary);
  font-size: var(--text-sm);
  text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
}

/* プログレスバリエーション */
.progress-success .progress-fill {
  background: linear-gradient(90deg, var(--color-success), #34d399);
}

.progress-warning .progress-fill {
  background: linear-gradient(90deg, var(--color-warning), #fbbf24);
}
```

### ゲーム専用コンポーネント

```css
/* サイコロ */
.dice {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: var(--space-12);
  height: var(--space-12);
  background-color: var(--surface-background);
  border: 2px solid var(--border-secondary);
  border-radius: var(--radius-default);
  font-size: var(--text-dice);
  font-weight: var(--font-bold);
  font-family: var(--font-mono);
  color: var(--text-primary);
  margin: 0 var(--space-2);
  box-shadow: var(--shadow-md);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dice-container {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: var(--space-3);
  margin: var(--space-6) 0;
}

/* ゲーム状態表示 */
.game-status {
  background-color: var(--surface-secondary);
  border: 1px solid var(--border-primary);
  border-radius: var(--radius-default);
  padding: var(--space-4);
  margin: var(--space-4) 0;
  text-align: center;
  font-size: var(--text-status);
  color: var(--text-secondary);
  font-weight: var(--font-medium);
}

/* ボタングループ */
.button-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-button-gap);
  justify-content: center;
  margin: var(--space-6) 0;
}

.button-group .btn {
  flex: 0 1 auto;
}

/* バッジ */
.badge {
  display: inline-flex;
  align-items: center;
  padding: var(--space-1) var(--space-2);
  border-radius: var(--radius-badge);
  font-size: var(--text-xs);
  font-weight: var(--font-medium);
  line-height: var(--leading-none);
}

.badge-primary {
  background-color: var(--color-primary-500);
  color: var(--text-inverse);
}

.badge-success {
  background-color: var(--color-success);
  color: var(--text-inverse);
}

.badge-warning {
  background-color: var(--color-warning);
  color: var(--text-inverse);
}

.badge-error {
  background-color: var(--color-error);
  color: var(--text-inverse);
}
```

---

## アクセシビリティ

### カラーコントラスト（WCAG準拠）

- **通常テキスト**: 最低4.5:1（AAレベル）
- **大テキスト**: 最低3:1（AAレベル）
- **UIコンポーネント**: 最低3:1（AAレベル）
- **推奨**: 7:1以上（AAAレベル）

### フォーカス管理

```css
/* フォーカス表示の統一 */
.focusable:focus-visible {
  outline: 2px solid var(--color-primary-500);
  outline-offset: 2px;
  border-radius: var(--radius-default);
}

/* キーボードナビゲーション */
*:focus-visible {
  box-shadow: var(--shadow-focus-visible);
}
```

### タッチターゲット

- **最小サイズ**: 44x44px（Apple推奨）
- **推奨間隔**: 8px以上
- **テキストリンク**: 行間も含めて44px以上

### モーション配慮

```css
@media (prefers-reduced-motion: reduce) {
  * {
    transition-duration: 0.01s !important;
    animation-duration: 0.01s !important;
  }
  
  .btn:hover,
  .card:hover {
    transform: none !important;
  }
}
```

### セマンティックHTML

- `<button>`タグの適切な使用
- `<fieldset>`と`<legend>`でフォームグループ化
- `role`属性による意味付け
- `aria-label`による説明
- `aria-describedby`による補足説明

---

## レスポンシブデザイン

### ブレークポイント（Apple準拠）

```css
/* iPhone SE: 375px */
@media (max-width: 23.4375em) { /* 375px */ }

/* iPhone Pro: 393px */  
@media (max-width: 24.5625em) { /* 393px */ }

/* iPad Mini: 744px */
@media (max-width: 46.5em) { /* 744px */ }

/* iPad Pro: 1024px */
@media (max-width: 64em) { /* 1024px */ }

/* Desktop: 1280px以上 */
@media (min-width: 80em) { /* 1280px */ }
```

### レスポンシブコンポーネント

```css
/* モバイル対応ボタン */
@media (max-width: 46.5em) {
  .btn {
    padding: var(--space-3) var(--space-4);
    font-size: var(--text-base);
    min-width: 120px;
  }
  
  .button-group {
    flex-direction: column;
    align-items: stretch;
  }
  
  .button-group .btn {
    flex: 1;
  }
}

/* モバイル対応カード */
@media (max-width: 46.5em) {
  .card {
    padding: var(--space-4);
    margin-bottom: var(--space-4);
  }
  
  .card-title {
    font-size: var(--text-lg);
  }
}

/* モバイル対応サイコロ */
@media (max-width: 46.5em) {
  .dice {
    width: var(--space-10);
    height: var(--space-10);
    font-size: var(--text-lg);
  }
}
```

---

## 実装ガイドライン

### CSS変数の活用

```css
:root {
  /* システム全体で一貫したデザイントークン */
  color-scheme: light;
}

/* ダークモード対応準備 */
@media (prefers-color-scheme: dark) {
  :root {
    /* ダークモード用変数定義 */
    color-scheme: dark;
  }
}
```

### パフォーマンス重視

- GPU加速は控えめに使用
- `will-change`は動的に制御
- アニメーションは60fps維持
- 画像よりCSS描画を優先

### メンテナビリティ

- BEMまたはUtility-First方式
- コンポーネント単位での分割
- 一貫したクラス命名規則
- ドキュメント化された使用例

---

このデザインルールにより、Apple の Human Interface Guidelines に準拠した、美しく使いやすいゲームUIを構築できます。