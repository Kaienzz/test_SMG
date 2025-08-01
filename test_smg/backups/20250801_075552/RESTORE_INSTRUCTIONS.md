# バックアップ復元手順

このバックアップは 2025-08-01 07:55:52 に作成されました。

## 復元方法

### 1. 全てのファイルを復元する場合
```bash
# ビューファイルを復元
cp -r /workspaces/test_SMG/test_smg/backups/20250801_075552/game-states /workspaces/test_SMG/test_smg/resources/views/
cp -r /workspaces/test_SMG/test_smg/backups/20250801_075552/game-states-noright /workspaces/test_SMG/test_smg/resources/views/

# テンプレートファイルを復元
cp /workspaces/test_SMG/test_smg/backups/20250801_075552/game-unified.blade.php /workspaces/test_SMG/test_smg/resources/views/
cp /workspaces/test_SMG/test_smg/backups/20250801_075552/game-unified-noright.blade.php /workspaces/test_SMG/test_smg/resources/views/

# CSSファイルを復元
cp /workspaces/test_SMG/test_smg/backups/20250801_075552/game-unified-layout.css /workspaces/test_SMG/test_smg/public/css/
```

### 2. 個別ファイルを復元する場合
特定のファイルのみ復元したい場合は、上記のコマンドから必要な行のみを実行してください。

## バックアップ内容
- game-states/ - 既存の3カラムレイアウト用ビューファイル
- game-states-noright/ - 2カラムレイアウト用ビューファイル
- game-unified.blade.php - 3カラムレイアウト用メインテンプレート
- game-unified-noright.blade.php - 2カラムレイアウト用メインテンプレート
- game-unified-layout.css - 統一レイアウトCSS

## 変更予定内容
HP/MP/SP情報を背景画像エリアの右上に表示し、左バーから削除する変更を行います。