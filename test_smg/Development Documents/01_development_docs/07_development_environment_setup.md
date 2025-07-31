# 開発環境セットアップ書

## 文書の概要

- **作成日**: 2025年7月25日
- **対象システム**: test_smg（Laravel/PHPブラウザRPG）
- **作成者**: AI開発チーム
- **バージョン**: v1.0

## 目的

test_smgプロジェクトの開発に必要な環境構築手順を定義し、チーム全体で統一された開発環境を実現する。

## 目次

1. [システム要件](#システム要件)
2. [基本環境構築](#基本環境構築)
3. [Laravel開発環境](#laravel開発環境)
4. [データベース環境](#データベース環境)
5. [フロントエンド環境](#フロントエンド環境)
6. [開発ツール設定](#開発ツール設定)
7. [Docker環境](#docker環境)
8. [CI/CD環境](#ci-cd環境)
9. [トラブルシューティング](#トラブルシューティング)

## システム要件

### 最小要件
```
OS: Windows 10/11, macOS 12+, Ubuntu 20.04+
RAM: 8GB以上（推奨16GB）
ストレージ: 20GB以上の空き容量
ネットワーク: インターネット接続必須
```

### 推奨要件
```
OS: Windows 11, macOS 13+, Ubuntu 22.04+
RAM: 16GB以上
ストレージ: SSD 50GB以上
CPU: 4コア以上
ネットワーク: 高速インターネット接続
```

## 基本環境構築

### 1. PHP環境
```bash
# Ubuntu/WSL
sudo apt update
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-mbstring php8.2-intl php8.2-bcmath php8.2-sqlite3

# macOS (Homebrew)
brew install php@8.2
brew link php@8.2

# Windows (Chocolatey)
choco install php --version=8.2
```

### 2. Composer
```bash
# 公式インストールコマンド
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 権限設定
sudo chmod +x /usr/local/bin/composer

# バージョン確認
composer --version
```

### 3. Node.js & npm
```bash
# Node.js LTS (18.x推奨)
# Ubuntu/WSL
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# macOS
brew install node@18

# Windows
# https://nodejs.org からダウンロードしてインストール

# バージョン確認
node --version
npm --version
```

### 4. Git
```bash
# Ubuntu/WSL
sudo apt install git

# macOS
brew install git

# Windows
# https://git-scm.com からダウンロードしてインストール

# 初期設定
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
git config --global init.defaultBranch main
```

## Laravel開発環境

### 1. プロジェクトクローン
```bash
# リポジトリクローン
git clone https://github.com/your-org/test_smg.git
cd test_smg

# 依存関係インストール
composer install
npm install
```

### 2. 環境設定
```bash
# .envファイル作成
cp .env.example .env

# アプリケーションキー生成
php artisan key:generate

# .env設定例
APP_NAME="test_smg"
APP_ENV=local
APP_KEY=base64:generated_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

MAIL_MAILER=log
LOG_CHANNEL=daily
LOG_LEVEL=debug
```

### 3. Laravel開発サーバー
```bash
# 開発サーバー起動
php artisan serve

# カスタムポート指定
php artisan serve --port=8080

# 外部アクセス許可
php artisan serve --host=0.0.0.0 --port=8000
```

### 4. Artisanコマンド設定
```bash
# 便利なエイリアス設定
echo 'alias art="php artisan"' >> ~/.bashrc
echo 'alias sail="./vendor/bin/sail"' >> ~/.bashrc
source ~/.bashrc

# 使用例
art migrate
art tinker
art route:list
```

## データベース環境

### 1. SQLite（開発用）
```bash
# SQLiteファイル作成
touch database/database.sqlite

# マイグレーション実行
php artisan migrate

# シーダ実行
php artisan db:seed

# データベースリセット
php artisan migrate:fresh --seed
```

### 2. MySQL（本番用）
```bash
# Ubuntu/WSL MySQL インストール
sudo apt install mysql-server mysql-client
sudo mysql_secure_installation

# macOS MySQL インストール
brew install mysql
brew services start mysql

# データベース作成
mysql -u root -p
CREATE DATABASE test_smg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'test_smg_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON test_smg.* TO 'test_smg_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# .env設定（MySQL用）
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=test_smg
DB_USERNAME=test_smg_user
DB_PASSWORD=secure_password
```

### 3. PostgreSQL（クラウド対応）
```bash
# Ubuntu/WSL PostgreSQL インストール
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql

# macOS PostgreSQL インストール
brew install postgresql
brew services start postgresql

# データベース作成
sudo -u postgres psql
CREATE DATABASE test_smg;
CREATE USER test_smg_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE test_smg TO test_smg_user;
\q

# .env設定（PostgreSQL用）
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=test_smg
DB_USERNAME=test_smg_user
DB_PASSWORD=secure_password
```

## フロントエンド環境

### 1. Laravel Mix/Vite設定
```bash
# package.jsonの確認
cat package.json

# 依存関係インストール
npm install

# 開発ビルド
npm run dev

# 本番ビルド
npm run build

# ファイル監視
npm run watch

# HMR（Hot Module Replacement）
npm run hot
```

### 2. TailwindCSS設定
```bash
# Tailwind CSS インストール
npm install -D tailwindcss
npx tailwindcss init

# tailwind.config.js
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}

# resources/css/app.css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### 3. JavaScript設定
```javascript
// resources/js/app.js
import './bootstrap';

// ゲーム関連JavaScript
import './game/gameManager.js';
import './game/diceManager.js';
import './game/movementManager.js';
import './game/battleManager.js';

// Alpine.js（軽量JSフレームワーク）
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

### 4. アセットコンパイル設定
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/game.js'
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
});
```

## 開発ツール設定

### 1. VSCode設定
```json
// .vscode/settings.json
{
    "php.validate.executablePath": "/usr/bin/php8.2",
    "php.suggest.basic": false,
    "emmet.includeLanguages": {
        "blade": "html"
    },
    "files.associations": {
        "*.blade.php": "blade"
    },
    "editor.formatOnSave": true,
    "editor.codeActionsOnSave": {
        "source.fixAll.eslint": true
    },
    "tailwindCSS.includeLanguages": {
        "blade": "html"
    }
}

// .vscode/extensions.json
{
    "recommendations": [
        "bmewburn.vscode-intelephense-client",
        "onecentlin.laravel-blade",
        "bradlc.vscode-tailwindcss",
        "esbenp.prettier-vscode",
        "ms-vscode.vscode-json"
    ]
}
```

### 2. PHP設定
```ini
; php.ini 推奨設定
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 100M
post_max_size = 100M
max_input_vars = 3000
date.timezone = Asia/Tokyo

; 開発用設定
display_errors = On
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php_errors.log

; OPcache設定（本番用）
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### 3. Xdebugデバッガー設定
```bash
# Xdebugインストール
sudo apt install php8.2-xdebug

# php.ini設定
# /etc/php/8.2/cli/conf.d/20-xdebug.ini
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
```

### 4. Laravel Telescope（デバッグツール）
```bash
# Telescopeインストール
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# 設定公開
php artisan vendor:publish --tag=telescope-config

# アクセス: http://localhost:8000/telescope
```

## Docker環境

### 1. Laravel Sail
```bash
# Sailインストール
composer require laravel/sail --dev
php artisan sail:install

# Dockerコンテナ起動
./vendor/bin/sail up -d

# Sailエイリアス設定
alias sail='./vendor/bin/sail'

# 基本コマンド
sail up -d          # バックグラウンド起動
sail down          # 停止
sail artisan       # Artisanコマンド実行
sail composer      # Composer実行
sail npm           # npm実行
sail test          # テスト実行
```

### 2. カスタムDocker設定
```dockerfile
# Dockerfile.dev
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

```yaml
# docker-compose.dev.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.dev
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
      - /var/www/html/vendor
      - /var/www/html/node_modules
    environment:
      - APP_ENV=local
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: test_smg
      MYSQL_USER: test_smg_user
      MYSQL_PASSWORD: password
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./docker/nginx.conf:/etc/nginx/nginx.conf
      - .:/var/www/html
    depends_on:
      - app

volumes:
  mysql_data:
```

### 3. Docker開発ワークフロー
```bash
# 初回セットアップ
docker-compose -f docker-compose.dev.yml up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed

# 日常開発
docker-compose -f docker-compose.dev.yml up -d
docker-compose exec app php artisan serve
docker-compose exec app npm run watch

# 停止
docker-compose -f docker-compose.dev.yml down
```

## CI/CD環境

### 1. GitHub Actions設定
```yaml
# .github/workflows/test.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, dom, fileinfo, mysql, gd

    - name: Cache dependencies
      uses: actions/cache@v3
      with:
        path: ~/.composer/cache/files
        key: dependencies-composer-${{ hashFiles('composer.lock') }}

    - name: Install dependencies
      run: composer install --prefer-dist --no-interaction

    - name: Setup Node
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'

    - name: Install npm dependencies
      run: npm ci

    - name: Build assets
      run: npm run build

    - name: Create Database
      run: |
        mysql -h 127.0.0.1 -u root -ppassword -e 'CREATE DATABASE IF NOT EXISTS testing;'

    - name: Copy .env
      run: cp .env.example .env

    - name: Generate key
      run: php artisan key:generate

    - name: Run migrations
      run: php artisan migrate
      env:
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: testing
        DB_USERNAME: root
        DB_PASSWORD: password

    - name: Run tests
      run: php artisan test
      env:
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: testing
        DB_USERNAME: root
        DB_PASSWORD: password
```

### 2. 品質チェック設定
```yaml
# .github/workflows/code-quality.yml
name: Code Quality

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  quality:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        tools: phpstan, php-cs-fixer

    - name: Install dependencies
      run: composer install --prefer-dist --no-interaction

    - name: Run PHP CS Fixer
      run: ./vendor/bin/php-cs-fixer fix --dry-run --diff

    - name: Run PHPStan
      run: ./vendor/bin/phpstan analyse

    - name: Setup Node
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'

    - name: Install npm dependencies
      run: npm ci

    - name: Run ESLint
      run: npm run lint

    - name: Run Prettier
      run: npm run format:check
```

### 3. デプロイ設定
```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'

    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader

    - name: Setup Node
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'

    - name: Build assets
      run: |
        npm ci
        npm run build

    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.PRIVATE_KEY }}
        script: |
          cd /var/www/test_smg
          git pull origin main
          composer install --no-dev --optimize-autoloader
          npm ci && npm run build
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo systemctl reload nginx
```

## トラブルシューティング

### 1. よくある問題と解決方法

#### Composer問題
```bash
# メモリ不足エラー
export COMPOSER_MEMORY_LIMIT=-1
composer install

# 権限エラー
sudo chown -R $USER:$USER ~/.composer
```

#### npm問題
```bash
# キャッシュクリア
npm cache clean --force

# node_modules再インストール
rm -rf node_modules package-lock.json
npm install
```

#### PHP拡張不足
```bash
# Ubuntu
sudo apt install php8.2-{extension-name}

# macOS
brew install php@8.2
pecl install {extension-name}
```

#### データベース接続エラー
```bash
# 接続テスト
php artisan tinker
>>> DB::connection()->getPdo();

# マイグレーション状態確認
php artisan migrate:status
```

### 2. パフォーマンス最適化
```bash
# Laravel最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Composer最適化
composer dump-autoload --optimize

# npm最適化
npm run build
```

### 3. ログとデバッグ
```bash
# Laravel ログ
tail -f storage/logs/laravel.log

# Nginx ログ
tail -f /var/log/nginx/error.log

# PHP-FPM ログ
tail -f /var/log/php8.2-fpm.log

# システムメトリクス監視
htop
iostat -x 1
```

### 4. バックアップとリストア
```bash
# データベースバックアップ
mysqldump -u root -p test_smg > backup.sql

# データベースリストア
mysql -u root -p test_smg < backup.sql

# ファイルバックアップ
tar -czf backup_$(date +%Y%m%d).tar.gz storage/ public/uploads/
```

## まとめ

### 開発環境チェックリスト
- [ ] PHP 8.2以上がインストールされている
- [ ] Composer が正常に動作する
- [ ] Node.js 18以上がインストールされている
- [ ] データベースが設定されている
- [ ] Laravel が正常に起動する
- [ ] フロントエンドアセットがビルドできる
- [ ] テストが実行できる
- [ ] デバッガーが設定されている

### セキュリティ考慮事項
- 開発環境では絶対に本番データを使用しない
- .envファイルをバージョン管理に含めない
- デバッグモードを本番環境で有効にしない
- 適切なファイル権限を設定する

この開発環境セットアップにより、test_smgプロジェクトの効率的な開発が可能になります。