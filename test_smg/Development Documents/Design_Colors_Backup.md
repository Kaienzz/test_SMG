# デザインカラーバックアップ - システムブルー版

## 概要
このファイルは、Design_Rules_New.mdでシステムブルーをプライマリカラーとして使用していた時の色コードのバックアップです。
将来的にブルー系のデザインに変更する場合の参考資料として保管しています。

## システムブルー版 - プライマリカラー

### カラーパレット（元の仕様）

```css
/* プライマリ（システムブルー準拠） */
--color-primary-50: #eff6ff;
--color-primary-100: #dbeafe;
--color-primary-200: #bfdbfe;
--color-primary-300: #93c5fd;
--color-primary-400: #60a5fa;
--color-primary-500: #3b82f6;  /* システムブルー基準 */
--color-primary-600: #2563eb;
--color-primary-700: #1d4ed8;
--color-primary-800: #1e40af;
--color-primary-900: #1e3a8a;
--color-primary-950: #172554;
```

### ボタンコンポーネント（システムブルー版）

```css
.btn-primary {
  background-color: var(--color-primary-500);  /* #3b82f6 */
  color: var(--text-inverse);
}

.btn-primary:hover {
  background-color: var(--color-primary-600);  /* #2563eb */
}

.btn-primary:active {
  background-color: var(--color-primary-700);  /* #1d4ed8 */
}
```

### 使用例（システムブルー版）

#### プライマリボタンスタイル
```css
.btn {
  background-color: #3b82f6;
  color: white;
  border: 1px solid transparent;
  padding: 0.875rem 1.5rem;
  border-radius: 0.5rem;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn:hover {
  background-color: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.btn:active {
  background-color: #1d4ed8;
}

.btn:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.8);
}
```

### フォーカス・アクセント色

```css
/* フォーカス表示 */
--shadow-focus: 0 0 0 3px rgba(59, 130, 246, 0.3);
--shadow-focus-visible: 0 0 0 2px rgba(59, 130, 246, 0.8);

/* プログレスバー */
.progress-fill {
  background: linear-gradient(90deg, #3b82f6, #60a5fa);
}
```

### セマンティックカラー（ブルー系統合）

```css
/* 情報カラー */
--color-info: #3b82f6;      /* プライマリと同じブルー */
--color-info-light: #60a5fa;
--color-info-dark: #2563eb;

/* リンクカラー */
--color-link: #3b82f6;
--color-link-hover: #2563eb;
--color-link-visited: #1d4ed8;
```

## 変更履歴

- **2025年7月24日**: Design_Rules_New.mdのプライマリカラーをシステムブルー(`#3b82f6`)からダークスレート(`#0f172a`)に変更
- **理由**: Design_sample.mdとの統一、および実際の実装との整合性確保

## 使用上の注意

このバックアップを使用する場合：

1. **アクセシビリティ**: ブルー系は背景色によってはコントラスト比に注意が必要
2. **ブランドカラー**: 現在のダークスレート系デザインとの整合性を確認
3. **ユーザビリティ**: ゲームUIとしての親しみやすさを検討

## 関連ファイル

- `Design_Rules_New.md` (現在の仕様)
- `Design_sample.md` (Modern Light Theme仕様)
- `/resources/views/auth/register.blade.php` (実装例)
- `/resources/views/dashboard.blade.php` (実装例)

---

**注意**: このファイルは参考資料です。実際のデザイン変更時は、関連するすべてのファイルとの整合性を確認してください。