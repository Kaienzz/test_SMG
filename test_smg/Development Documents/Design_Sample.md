# Design Sample - モダンライト風テーマ

## 概要
トップページで採用したモダンなライト風テーマの詳細仕様書です。今後のデザイン統一の参考資料として活用してください。

## カラーパレット

### 背景色
- **メイン背景**: `linear-gradient(135deg, #fafafa 0%, #f8fafc 50%, #f1f5f9 100%)`
- **微細なアクセント**: 
  - `rgba(148, 163, 184, 0.05)` - スレートグレー系
  - `rgba(203, 213, 225, 0.05)` - ライトスレート系
  - `rgba(226, 232, 240, 0.05)` - 非常に薄いスレート系

### テキストカラー
- **プライマリテキスト**: `#1e293b` (ダークスレート)
- **セカンダリテキスト**: `#475569` (ミディアムスレート) 
- **説明文**: `#64748b` (ライトスレート)

### ボタンカラー
- **プライマリボタン背景**: `#0f172a` (非常に濃いスレート)
- **プライマリボタンホバー**: `#1e293b` (濃いスレート)
- **セカンダリボタン背景**: `white`
- **セカンダリボタンテキスト**: `#475569`
- **セカンダリボタンボーダー**: `#e2e8f0`

### カード・境界線
- **カード背景**: `white`
- **カードボーダー**: `#e2e8f0` (薄いスレート)
- **カードホバーボーダー**: `#cbd5e1` (やや濃いスレート)

## タイポグラフィ

### フォントサイズ
- **メインタイトル**: `clamp(3rem, 8vw, 5rem)` - レスポンシブ対応
- **サブタイトル**: `1.25rem` (20px)
- **カードタイトル**: `1.25rem` (20px)
- **説明文**: `0.95rem` (約15px)
- **ボタンテキスト**: `1rem` (16px)

### フォントウェイト
- **メインタイトル**: `700` (Bold)
- **カードタイトル**: `600` (Semi-bold)
- **ボタン**: `500` (Medium)

## レイアウト・スペーシング

### コンテナ
- **最大幅**: `1200px`
- **パディング**: `1.5rem` (24px)
- **中央配置**: `margin: 0 auto`

### グリッドシステム
- **フィーチャーグリッド**: `repeat(auto-fit, minmax(280px, 1fr))`
- **グリッド間隔**: `2rem` (32px)
- **上下マージン**: `4rem` (64px)

### カードデザイン
- **パディング**: `2rem` (32px)
- **ボーダー半径**: `1rem` (16px)
- **基本シャドウ**: `0 1px 3px rgba(0, 0, 0, 0.05)`
- **ホバーシャドウ**: `0 8px 25px rgba(0, 0, 0, 0.1)`

## インタラクション・アニメーション

### ホバーエフェクト
- **カード**: `translateY(-4px)` + シャドウ強化
- **ボタン**: `translateY(-1px)` + シャドウ強化
- **トランジション**: `all 0.2s ease` (ボタン) / `all 0.3s ease` (カード)

### ボタンスタイル
- **パディング**: `0.875rem 1.5rem` (14px 24px)
- **ボーダー半径**: `0.5rem` (8px)
- **最小幅**: `140px`
- **マージン**: `0.5rem` (8px)

## シャドウシステム

### 基本シャドウ
- **軽微**: `0 1px 3px rgba(0, 0, 0, 0.05)`
- **標準**: `0 1px 3px rgba(0, 0, 0, 0.1)`
- **強調**: `0 4px 12px rgba(0, 0, 0, 0.1)`
- **ホバー**: `0 4px 12px rgba(0, 0, 0, 0.15)`
- **カードホバー**: `0 8px 25px rgba(0, 0, 0, 0.1)`

## デザイン原則

### 1. ミニマリズム
- 不要な装飾を排除
- ホワイトスペースを効果的に活用
- シンプルで読みやすい構成

### 2. 階層構造
- テキストサイズと色で情報の重要度を表現
- 明確な視覚的階層の構築

### 3. 一貫性
- 統一されたスペーシングシステム
- 一貫したカラーパレット
- 統一されたコンポーネントスタイル

### 4. アクセシビリティ
- 十分なコントラスト比の確保
- 読みやすいフォントサイズ
- ホバー状態の明確な表現

## 実装参照
- **メインファイル**: `resources/views/welcome.blade.php:15-158`
- **スタイル**: インラインCSS（Light Modern Theme Override）
- **ベースデザインシステム**: `public/css/game-design-system.css`

## 使用推奨場面
- ランディングページ
- 管理画面
- ダッシュボード
- フォーム画面
- 設定画面

このデザインサンプルを基準として、他のページでも同様の統一感を保ったデザインを実装することを推奨します。