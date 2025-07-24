# シンプルブラウザゲーム UIデザインルール

## デザイン哲学

このデザインシステムは、昔懐かしいCGIゲームの操作感を現代的なUIデザインで表現することを目指しています。シンプルで明快な操作性を重視し、ボタンクリック中心のインタラクションで誰でも楽しめるゲーム体験を提供します。

### 核となる原則
- **シンプリシティ**: 迷わず直感的に操作できるシンプルなUI
- **クラシック感**: CGIゲーム時代のノスタルジーと現代的な美しさの融合
- **明瞭性**: ボタンや選択肢が明確に区別できるデザイン
- **軽量性**: ページ読み込みが早く、動作が軽いUI

---

## カラーシステム

### プライマリカラー
```css
/* クラシックゲーム風カラー */
--primary-50: #f0f9ff;
--primary-100: #dbeafe;
--primary-500: #3b82f6;  /* 安定感のあるブルー */
--primary-600: #2563eb;
--primary-900: #1e3a8a;

/* セカンダリカラー（温かみのあるアクセント） */
--secondary-50: #fff7ed;
--secondary-100: #ffedd5;
--secondary-500: #f97316;  /* フレンドリーなオレンジ */
--secondary-600: #ea580c;
--secondary-900: #9a3412;
```

### セマンティックカラー
```css
/* 状態表現カラー（穏やかで親しみやすい色調） */
--success: #059669;    /* 成功・ポジティブ */
--warning: #d97706;    /* 警告・注意 */
--error: #dc2626;      /* エラー・危険 */
--info: #0284c7;       /* 情報・中立 */

/* ゲーム専用カラー */
--town-color: #059669;      /* 町のテーマカラー（穏やかな緑） */
--road-color: #92400e;      /* 道のテーマカラー（土の色） */
--dice-color: #fbbf24;      /* サイコロ・ギャンブル要素 */
--neutral-bg: #f3f4f6;      /* 背景色 */
```

### ニュートラルカラー
```css
/* シンプルで読みやすいグレースケール */
--gray-50: #fafafa;
--gray-100: #f4f4f5;
--gray-200: #e4e4e7;
--gray-300: #d4d4d8;
--gray-500: #71717a;
--gray-700: #3f3f46;
--gray-900: #18181b;

/* 基本色設定（ライトモード重視） */
--bg-primary: #ffffff;
--bg-secondary: #fafafa;
--bg-game: #f8fafc;        /* ゲーム背景 */
--text-primary: #1f2937;
--text-secondary: #6b7280;
--border-light: #e5e7eb;
--border-medium: #d1d5db;
```

---

## タイポグラフィ

### フォントファミリー
```css
/* 読みやすさ重視のフォント */
--font-primary: system-ui, -apple-system, 'Segoe UI', 'Noto Sans JP', sans-serif;

/* 数字・データ表示用（サイコロの目など） */
--font-mono: 'SF Mono', Consolas, 'Liberation Mono', Menlo, monospace;

/* ゲームタイトル・見出し用 */
--font-display: system-ui, -apple-system, 'Segoe UI', sans-serif;
```

### フォントサイズ・行間
```css
/* シンプルで読みやすいサイズ設定 */
--text-3xl: 1.875rem;   /* 30px - ゲームタイトル */
--text-2xl: 1.5rem;     /* 24px - セクションタイトル */
--text-xl: 1.25rem;     /* 20px - 重要な見出し */
--text-lg: 1.125rem;    /* 18px - ボタンテキスト */
--text-base: 1rem;      /* 16px - 標準テキスト */
--text-sm: 0.875rem;    /* 14px - 補助情報 */
--text-xs: 0.75rem;     /* 12px - 注釈 */

/* 特殊用途 */
--text-dice: 1.5rem;    /* 24px - サイコロの数字 */
--text-status: 1.125rem;/* 18px - ゲーム状況表示 */

/* 行間（読みやすさ重視） */
--leading-tight: 1.2;
--leading-normal: 1.5;
--leading-relaxed: 1.6;
```

### フォントウェイト
```css
--font-light: 300;
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

---

## 余白・間隔

### スペーシングシステム
```css
/* 基本単位：8px（操作しやすいサイズ） */
--space-1: 0.25rem;   /* 4px - 最小間隔 */
--space-2: 0.5rem;    /* 8px - 小間隔 */
--space-3: 0.75rem;   /* 12px - ボタン内余白 */
--space-4: 1rem;      /* 16px - 標準間隔 */
--space-5: 1.25rem;   /* 20px - 要素間 */
--space-6: 1.5rem;    /* 24px - セクション内 */
--space-8: 2rem;      /* 32px - セクション間 */
--space-12: 3rem;     /* 48px - 大きな区切り */
```

### コンポーネント間隔
```css
/* ゲーム専用間隔設定 */
--game-section-gap: var(--space-6);    /* ゲームセクション間 */
--card-padding: var(--space-5);        /* カード内余白 */
--button-padding-y: var(--space-3);    /* ボタン縦余白 */
--button-padding-x: var(--space-5);    /* ボタン横余白 */
--button-gap: var(--space-3);          /* ボタン間隔 */
--choice-gap: var(--space-2);          /* 選択肢間隔 */
```

---

## 角丸（Border Radius）

```css
/* 控えめな角丸システム */
--radius-sm: 0.25rem;    /* 4px - 小要素 */
--radius-md: 0.375rem;   /* 6px - ボタン */
--radius-lg: 0.5rem;     /* 8px - カード */
--radius-xl: 0.75rem;    /* 12px - 大きなカード */
--radius-full: 9999px;   /* 円形（サイコロなど） */

/* コンポーネント別設定 */
--button-radius: var(--radius-md);      /* 親しみやすい角丸 */
--card-radius: var(--radius-lg);        /* 柔らかい印象 */
--choice-radius: var(--radius-sm);      /* 選択肢 */
--dice-radius: var(--radius-md);        /* サイコロ */
```

---

## 影の効果（Shadow System）

```css
/* 軽量な影システム（GPU不使用） */
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.06);
--shadow-md: 0 2px 4px 0 rgba(0, 0, 0, 0.08);
--shadow-lg: 0 4px 6px 0 rgba(0, 0, 0, 0.1);

/* ボタン専用影 */
--shadow-button: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
--shadow-button-hover: 0 3px 6px 0 rgba(0, 0, 0, 0.15);
--shadow-button-active: 0 1px 2px 0 rgba(0, 0, 0, 0.1);

/* カード影 */
--shadow-card: 0 2px 8px 0 rgba(0, 0, 0, 0.08);
--shadow-card-hover: 0 4px 12px 0 rgba(0, 0, 0, 0.12);

/* フォーカス表示（アウトライン代替） */
--shadow-focus: 0 0 0 3px rgba(59, 130, 246, 0.3);
```

---

## コンポーネント設計

### ボタン
```css
/* 基本ボタンスタイル */
.btn {
  padding: var(--button-padding-y) var(--button-padding-x);
  border-radius: var(--button-radius);
  font-family: var(--font-primary);
  font-weight: var(--font-medium);
  font-size: var(--text-lg);
  line-height: var(--leading-normal);
  border: 2px solid transparent;
  cursor: pointer;
  display: inline-block;
  text-align: center;
  text-decoration: none;
  transition: all 0.2s ease;
  min-width: 120px;
  box-shadow: var(--shadow-button);
}

.btn:hover {
  box-shadow: var(--shadow-button-hover);
}

.btn:active {
  box-shadow: var(--shadow-button-active);
}

.btn:focus {
  box-shadow: var(--shadow-focus);
}

/* ボタンバリエーション */
.btn-primary {
  background: var(--primary-500);
  color: white;
}

.btn-primary:hover {
  background: var(--primary-600);
}

.btn-secondary {
  background: var(--gray-100);
  color: var(--text-primary);
  border-color: var(--border-medium);
}

.btn-secondary:hover {
  background: var(--gray-200);
}

.btn-success {
  background: var(--success);
  color: white;
}

.btn-warning {
  background: var(--warning);
  color: white;
}

/* 大きなボタン（重要なアクション用） */
.btn-large {
  padding: var(--space-4) var(--space-8);
  font-size: var(--text-xl);
  min-width: 160px;
}
```

### ゲーム情報カード
```css
.game-card {
  background: var(--bg-primary);
  border-radius: var(--card-radius);
  padding: var(--card-padding);
  box-shadow: var(--shadow-card);
  border: 2px solid var(--border-light);
  margin-bottom: var(--game-section-gap);
}

.game-card-title {
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  color: var(--text-primary);
  margin: 0 0 var(--space-4) 0;
  text-align: center;
}

.game-card-content {
  color: var(--text-secondary);
  font-size: var(--text-base);
  line-height: var(--leading-normal);
}

/* 特殊カード：現在地表示 */
.location-card {
  background: linear-gradient(135deg, var(--town-color), var(--success));
  color: white;
  text-align: center;
}

.location-card .game-card-title {
  color: white;
}

/* 特殊カード：サイコロエリア */
.dice-card {
  background: var(--neutral-bg);
  border-color: var(--dice-color);
  text-align: center;
}
```

### ラジオボタン・選択肢
```css
/* ラジオボタンコンテナ */
.choice-group {
  margin: var(--space-4) 0;
}

.choice-option {
  display: block;
  margin-bottom: var(--choice-gap);
  cursor: pointer;
  padding: var(--space-3) var(--space-4);
  border: 2px solid var(--border-light);
  border-radius: var(--choice-radius);
  background: var(--bg-primary);
  transition: all 0.2s ease;
}

.choice-option:hover {
  border-color: var(--primary-500);
  background: var(--primary-50);
}

.choice-option input[type="radio"] {
  margin-right: var(--space-2);
  accent-color: var(--primary-500);
}

.choice-option:has(input:checked) {
  border-color: var(--primary-500);
  background: var(--primary-100);
}

/* シンプルなプログレスバー */
.progress {
  width: 100%;
  height: 1.5rem;
  background: var(--gray-200);
  border-radius: var(--radius-full);
  position: relative;
  overflow: hidden;
  border: 1px solid var(--border-light);
}

.progress-fill {
  height: 100%;
  background: var(--road-color);
  border-radius: var(--radius-full);
  transition: width 0.3s ease;
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-weight: var(--font-medium);
  color: var(--text-primary);
  font-size: var(--text-sm);
}
```

### サイコロ・特殊UI
```css
/* サイコロ表示 */
.dice {
  display: inline-block;
  width: 3rem;
  height: 3rem;
  background: white;
  border: 2px solid var(--gray-300);
  border-radius: var(--dice-radius);
  font-size: var(--text-dice);
  font-weight: var(--font-bold);
  font-family: var(--font-mono);
  color: var(--text-primary);
  text-align: center;
  line-height: 2.5rem;
  margin: 0 var(--space-2);
  box-shadow: var(--shadow-md);
}

.dice-container {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: var(--space-4) 0;
}

/* ゲーム状態表示 */
.game-status {
  background: var(--bg-secondary);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  padding: var(--space-3);
  margin: var(--space-4) 0;
  text-align: center;
  font-size: var(--text-status);
  color: var(--text-secondary);
}

/* ボタングループ */
.button-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--button-gap);
  justify-content: center;
  margin: var(--space-4) 0;
}

.button-group .btn {
  flex: 0 1 auto;
}
```

---

## アクセシビリティ配慮

### カラーコントラスト
- テキストとBackground間のコントラスト比：最低4.5:1（AAレベル）
- 重要な情報は7:1以上（AAAレベル）を目指す
- 色のみに依存しない情報伝達（アイコンや形状も併用）

### フォーカス管理
```css
/* フォーカス可視化 */
.focusable:focus {
  outline: 2px solid var(--primary-500);
  outline-offset: 2px;
}

/* キーボードナビゲーション */
.btn:focus-visible,
.input:focus-visible {
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.3);
}
```

### モーション配慮
```css
/* アニメーション削減対応（軽量設定） */
@media (prefers-reduced-motion: reduce) {
  * {
    transition-duration: 0.01ms !important;
  }
  
  .progress-fill {
    transition: none !important;
  }
}
```

### セマンティックHTML
- ボタンには適切な`<button>`タグを使用
- ラジオボタンには`<fieldset>`と`<legend>`でグループ化
- 現在地情報には`role="status"`を設定
- 重要な状態変化には`aria-live`を設定

### レスポンシブ対応
```css
/* モバイル対応 */
@media (max-width: 640px) {
  .btn {
    padding: var(--space-3) var(--space-4);
    min-width: 100px;
    font-size: var(--text-base);
  }
  
  .button-group {
    flex-direction: column;
    align-items: center;
  }
  
  .game-card {
    padding: var(--space-4);
  }
  
  .dice {
    width: 2.5rem;
    height: 2.5rem;
    line-height: 2rem;
    font-size: 1.25rem;
  }
}
```

---

## 実装ガイドライン

### シンプルさの維持
- CSS変数を使用してデザイントークンを一元管理
- 複雑なアニメーションやエフェクトは避ける
- コンポーネントクラスの再利用でコードを簡潔に保つ

### パフォーマンス重視
- GPUアクセラレーションは使用せず、軽量なCSS transitionのみ使用
- 画像の使用は最小限に抑え、CSS描画を優先
- 外部フォントの読み込みは必要最小限に

### CGIゲーム風の実装
- テーブルレイアウトではなく、flexboxで柔軟性を保つ
- クリック感のあるボタンデザイン（影とホバー効果）
- シンプルで分かりやすい視覚的階層

### メンテナビリティ
- 単純なクラス命名規則（.btn, .game-card, .dice等）
- インラインスタイルは避け、CSS classで統一
- 状態管理はJavaScriptで、見た目はCSSで分離

### アクセシビリティ
- キーボード操作に対応
- 色以外の視覚的手がかりを提供
- 明確なフォーカス表示

これらのルールに従うことで、昔懐かしいCGIゲームの操作感を現代的な技術で再現した、軽量で使いやすいゲームUIを構築できます。