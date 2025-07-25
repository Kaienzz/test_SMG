# CLAUDE.md - プロジェクト開発ガイド

## プロジェクト概要
Laravel/PHPベースのブラウザRPGゲーム「test_smg」の開発プロジェクトです。
昔懐かしいCGIゲームの操作感を現代的なUIデザインで表現したシンプルなWebゲームです。

## 開発ドキュメント構成

### Development Documents フォルダー

プロジェクトの開発に関連するすべてのドキュメントが格納されています。

#### 1. database_design.md
**役割**: データベース設計の詳細仕様書
**参照タイミング**:
- 新しいテーブルやカラムを追加する時
- モデル間のリレーションシップを確認する時
- データ構造に関する質問がある時
- マイグレーションファイルを作成する時

**重要な内容**:
- 全テーブル構造とカラム定義
- 外部キー制約とリレーションシップ
- インデックス戦略
- ゲームシステム設計思想

#### 2. design_rules.md
**役割**: UI/UXデザインシステムの統一ルール
**参照タイミング**:
- 新しい画面やコンポーネントを作成する時
- CSSスタイルを適用する時
- カラーパレットや余白を確認する時
- アクセシビリティ要件を確認する時

**重要な内容**:
- カラーシステム（プライマリ、セカンダリ、状態色）
- タイポグラフィ規則
- コンポーネント設計（ボタン、カード、フォーム）
- レスポンシブ対応ガイドライン

#### 3. Design_sample.md
**役割**: Modern Light Themeの具体的な実装サンプル
**参照タイミング**:
- ログイン・登録画面など管理画面系のデザインを実装する時
- モダンなライト系テーマを適用する時
- 既存デザインの統一性を確認する時

**重要な内容**:
- 具体的なCSS値とカラーコード
- レイアウト・スペーシングの詳細
- ホバーエフェクトとアニメーション
- 実装参照箇所

#### 4. implemented_note.md
**役割**: 実装済み機能の詳細記録
**参照タイミング**:
- 既存機能の動作を確認する時
- 関連機能を拡張・修正する時
- バグ修正で実装内容を確認する時
- 新機能と既存機能の連携を考える時

**重要な内容**:
- 実装済み機能一覧
- 各機能の技術的詳細
- ファイル構成と責任範囲
- 既知の問題・制限事項

#### 5. Tasks_23JUL2025.md
**役割**: 2025年7月23日時点のタスク記録
**参照タイミング**:
- 過去の実装履歴を確認する時
- 類似機能の実装方法を参考にする時
- プロジェクトの進捗履歴を振り返る時

#### 6. Tasks_24JUL2025.md
**役割**: 認証機能実装の具体的なタスクリスト
**参照タイミング**:
- 認証機能の実装を進める時
- フェーズごとの進捗を確認する時
- 関連する実装順序を確認する時

**重要な内容**:
- Laravel Breeze実装手順
- UI/UXデザイン適用方法
- Character作成連携ロジック
- テスト・品質保証計画

## 開発時の基本方針

### 1. デザイン統一性
- `design_rules.md`を基準とした統一されたUI/UX
- CGIゲーム風の親しみやすい操作感
- Modern Light Themeによるモダンな見た目

### 2. データベース設計
- `database_design.md`に従った正規化されたテーブル設計
- 1ユーザー1キャラクターシステム
- スキルベースレベルシステム

### 3. 実装アプローチ
- Laravel標準機能の積極活用
- 既存機能との整合性を重視
- セキュリティとパフォーマンスの両立

### 4. ドキュメント管理
- 実装完了後は`implemented_note.md`を更新
- 新しい機能仕様は該当ドキュメントに反映
- タスク完了後はタスクファイルを更新

## よく使用するコマンド

### Lint・型チェック
```bash
# PHP構文チェック
php artisan route:list
composer dump-autoload

# フロントエンド
npm run lint
npm run build
```

### テスト実行
```bash
# PHP Unit Tests
php artisan test

# JavaScript Tests (if available)
npm test
```

### データベース
```bash
# マイグレーション実行
php artisan migrate

# シーダ実行
php artisan db:seed
```

## 重要な設計決定

1. **認証システム**: Laravel Breeze使用
2. **デザインシステム**: Modern Light Theme + CGI風UI
3. **キャラクターシステム**: 1ユーザー1キャラクター
4. **レベルシステム**: スキルベース（総スキルレベル÷10+1）
5. **インベントリ**: JSON形式のスロット管理

このドキュメントは開発進行に合わせて随時更新し、プロジェクトの最新状況を反映します。