# パフォーマンス最適化書

## 文書の概要

- **作成日**: 2025年7月25日
- **対象システム**: test_smg（Laravel/PHPブラウザRPG）
- **作成者**: AI開発チーム
- **バージョン**: v1.0

## 目的

test_smgプロジェクトのパフォーマンス最適化戦略を定義し、高速で安定したゲーム体験を提供する。

## 目次

1. [パフォーマンス目標](#パフォーマンス目標)
2. [ベンチマーク指標](#ベンチマーク指標)
3. [サーバーサイド最適化](#サーバーサイド最適化)
4. [データベース最適化](#データベース最適化)
5. [フロントエンド最適化](#フロントエンド最適化)
6. [キャッシュ戦略](#キャッシュ戦略)
7. [アセット最適化](#アセット最適化)
8. [CDN活用](#cdn活用)
9. [ゲーム固有最適化](#ゲーム固有最適化)
10. [プロファイリング](#プロファイリング)
11. [負荷テスト](#負荷テスト)

## パフォーマンス目標

### 1. Core Web Vitals 目標
```
┌─────────────────────────────────┐
│        Core Web Vitals         │
├─────────────────────────────────┤
│ LCP (Largest Contentful Paint) │
│   目標: 2.5秒以下              │
│   理想: 1.5秒以下              │
├─────────────────────────────────┤
│ FID (First Input Delay)        │
│   目標: 100ms以下              │
│   理想: 50ms以下               │
├─────────────────────────────────┤
│ CLS (Cumulative Layout Shift)  │
│   目標: 0.1以下                │
│   理想: 0.05以下               │
└─────────────────────────────────┘
```

### 2. ゲーム特有の目標
- **サイコロロール応答**: 200ms以下
- **移動処理**: 100ms以下
- **ページ遷移**: 500ms以下
- **API応答時間**: 95%のリクエストが500ms以下
- **同時接続ユーザー**: 1,000ユーザーまで対応

### 3. リソース使用量目標
```php
<?php

namespace App\Services;

class PerformanceTarget
{
    public const TARGETS = [
        'memory_usage' => 128, // MB
        'cpu_usage' => 70, // %
        'database_queries' => 10, // per request
        'cache_hit_ratio' => 90, // %
        'response_time_p95' => 500, // ms
        'response_time_p99' => 1000, // ms
    ];
    
    public static function isWithinTarget(string $metric, $value): bool
    {
        return $value <= (self::TARGETS[$metric] ?? PHP_INT_MAX);
    }
}
```

## ベンチマーク指標

### 1. パフォーマンス計測
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PerformanceBenchmark
{
    private array $metrics = [];
    
    public function startTimer(string $name): void
    {
        $this->metrics[$name]['start'] = microtime(true);
    }
    
    public function endTimer(string $name): float
    {
        if (!isset($this->metrics[$name]['start'])) {
            throw new \InvalidArgumentException("Timer '{$name}' not started");
        }
        
        $duration = microtime(true) - $this->metrics[$name]['start'];
        $this->metrics[$name]['duration'] = $duration;
        
        return $duration;
    }
    
    public function measureMemory(string $name): int
    {
        $memory = memory_get_usage(true);
        $this->metrics[$name]['memory'] = $memory;
        
        return $memory;
    }
    
    public function measureDatabaseQueries(callable $callback): array
    {
        DB::enableQueryLog();
        $startQueries = count(DB::getQueryLog());
        
        $result = $callback();
        
        $endQueries = count(DB::getQueryLog());
        $queryCount = $endQueries - $startQueries;
        
        return [
            'result' => $result,
            'query_count' => $queryCount,
            'queries' => array_slice(DB::getQueryLog(), $startQueries)
        ];
    }
    
    public function getMetrics(): array
    {
        return $this->metrics;
    }
    
    public function reset(): void
    {
        $this->metrics = [];
    }
}
```

### 2. APM統合
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PerformanceMonitor;

class PerformanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // リクエスト開始時の計測
        $this->app['events']->listen('kernel.handling', function ($request) {
            app(PerformanceMonitor::class)->startRequest($request);
        });
        
        // レスポンス時の計測
        $this->app['events']->listen('kernel.handled', function ($request, $response) {
            app(PerformanceMonitor::class)->endRequest($request, $response);
        });
        
        // データベースクエリの監視
        DB::listen(function ($query) {
            if ($query->time > 1000) { // 1秒以上のクエリをログ
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time
                ]);
            }
        });
    }
}
```

## サーバーサイド最適化

### 1. Laravel最適化
```php
<?php

namespace App\Services;

class LaravelOptimization
{
    /**
     * 本番環境最適化コマンド
     */
    public static function optimize(): void
    {
        // 設定キャッシュ
        \Artisan::call('config:cache');
        
        // ルートキャッシュ
        \Artisan::call('route:cache');
        
        // ビューキャッシュ
        \Artisan::call('view:cache');
        
        // イベント＆リスナーキャッシュ
        \Artisan::call('event:cache');
        
        // Composerオートローダー最適化
        exec('composer dump-autoload --optimize --no-dev');
        
        // OPcache設定確認
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();
            if (!$status['opcache_enabled']) {
                throw new \Exception('OPcache is not enabled');
            }
        }
    }
}

// config/app.php 最適化設定
return [
    'debug' => env('APP_DEBUG', false),
    'log_level' => env('LOG_LEVEL', 'error'),
    
    // プロバイダーの最適化
    'providers' => [
        // 本番環境では不要なプロバイダーを除外
        // Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        // ...
    ],
];
```

### 2. PHPパフォーマンス設定
```ini
; php.ini 最適化設定

; OPcache設定
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=0
opcache.fast_shutdown=1

; メモリ設定
memory_limit=256M
max_execution_time=30
max_input_time=30

; アップロード設定
upload_max_filesize=10M
post_max_size=10M

; セッション設定
session.save_handler=redis
session.save_path="tcp://127.0.0.1:6379"

; リアルパス解決キャッシュ
realpath_cache_size=4096K
realpath_cache_ttl=600
```

### 3. Eloquent最適化
```php
<?php

namespace App\Services;

use App\Models\Character;
use Illuminate\Database\Eloquent\Builder;

class OptimizedCharacterService
{
    /**
     * N+1問題の回避
     */
    public function getCharactersWithUsers(): Collection
    {
        return Character::with('user')
            ->select(['id', 'user_id', 'name', 'hp', 'max_hp'])
            ->get();
    }
    
    /**
     * クエリの最適化
     */
    public function findActiveCharacters(): Builder
    {
        return Character::whereHas('user', function ($query) {
                $query->where('last_login_at', '>', now()->subDays(7));
            })
            ->where('hp', '>', 0)
            ->orderBy('updated_at', 'desc');
    }
    
    /**
     * バルクアップデート
     */
    public function updateMultipleCharacterHP(array $updates): void
    {
        $cases = [];
        $ids = [];
        
        foreach ($updates as $id => $hp) {
            $cases[] = "WHEN {$id} THEN {$hp}";
            $ids[] = $id;
        }
        
        $idsString = implode(',', $ids);
        $casesString = implode(' ', $cases);
        
        DB::statement("
            UPDATE characters 
            SET hp = CASE id {$casesString} END 
            WHERE id IN ({$idsString})
        ");
    }
    
    /**
     * チャンクを使った大量データ処理
     */
    public function processAllCharacters(callable $callback): void
    {
        Character::chunk(1000, function ($characters) use ($callback) {
            foreach ($characters as $character) {
                $callback($character);
            }
        });
    }
}
```

## データベース最適化

### 1. インデックス戦略
```sql
-- characters テーブルの最適化
CREATE INDEX idx_characters_user_location ON characters(user_id, location_type);
CREATE INDEX idx_characters_game_position ON characters(game_position);
CREATE INDEX idx_characters_updated_at ON characters(updated_at);

-- users テーブルの最適化
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_last_login ON users(last_login_at);

-- ゲームログテーブルの最適化
CREATE INDEX idx_game_logs_user_action ON game_logs(user_id, action, created_at);
CREATE INDEX idx_game_logs_created_at ON game_logs(created_at);

-- 複合インデックスの作成
CREATE INDEX idx_characters_active_players ON characters(location_type, hp, updated_at)
WHERE hp > 0;
```

### 2. クエリ最適化
```php
<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class OptimizedGameRepository
{
    /**
     * 効率的なランキングクエリ
     */
    public function getTopPlayers(int $limit = 10): array
    {
        return DB::select("
            SELECT 
                c.name,
                c.user_id,
                JSON_EXTRACT(c.skills, '$.attack') + 
                JSON_EXTRACT(c.skills, '$.defense') + 
                JSON_EXTRACT(c.skills, '$.agility') as total_skills
            FROM characters c
            INNER JOIN users u ON c.user_id = u.id
            WHERE u.last_login_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY total_skills DESC
            LIMIT ?
        ", [$limit]);
    }
    
    /**
     * 集計クエリの最適化
     */
    public function getLocationStatistics(): array
    {
        return DB::select("
            SELECT 
                location_type,
                COUNT(*) as player_count,
                AVG(hp) as avg_hp,
                AVG(game_position) as avg_position
            FROM characters
            WHERE updated_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
            GROUP BY location_type
        ");
    }
    
    /**
     * ページネーション最適化
     */
    public function getPaginatedCharacters(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        
        // OFFSET使用を避けたカーソルベースページネーション
        return DB::select("
            SELECT id, name, hp, max_hp, updated_at
            FROM characters
            WHERE id > ?
            ORDER BY id ASC
            LIMIT ?
        ", [$offset, $perPage]);
    }
}
```

### 3. パーティショニング
```sql
-- 大量のログデータに対するパーティショニング
CREATE TABLE game_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL,
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

## フロントエンド最適化

### 1. JavaScript最適化
```javascript
// resources/js/optimized/GameManager.js
export class OptimizedGameManager {
    constructor() {
        this.requestQueue = [];
        this.isProcessing = false;
        this.cache = new Map();
        
        // リクエストデバウンス
        this.debouncedUpdate = this.debounce(this.updateGameState.bind(this), 300);
        
        // DOM要素のキャッシュ
        this.domCache = new Map();
        this.initDOMCache();
    }
    
    initDOMCache() {
        const elements = [
            'character-hp',
            'character-sp', 
            'game-position',
            'current-location',
            'dice-result'
        ];
        
        elements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                this.domCache.set(id, element);
            }
        });
    }
    
    // デバウンス機能
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // リクエストのバッチ処理
    async batchRequests(requests) {
        this.requestQueue.push(...requests);
        
        if (!this.isProcessing) {
            this.isProcessing = true;
            await this.processBatch();
            this.isProcessing = false;
        }
    }
    
    async processBatch() {
        const batch = this.requestQueue.splice(0, 10); // 最大10件ずつ処理
        
        if (batch.length === 0) return;
        
        try {
            const promises = batch.map(request => this.makeRequest(request));
            await Promise.all(promises);
        } catch (error) {
            console.error('Batch processing error:', error);
        }
        
        // 残りがあれば続行
        if (this.requestQueue.length > 0) {
            setTimeout(() => this.processBatch(), 100);
        }
    }
    
    // DOM更新の最適化
    updateElement(id, content) {
        const element = this.domCache.get(id);
        if (element && element.textContent !== content) {
            element.textContent = content;
        }
    }
    
    // Virtual DOM風の差分更新
    updateCharacterStatus(newData) {
        const updates = [];
        
        if (this.lastCharacterData) {
            for (const [key, value] of Object.entries(newData)) {
                if (this.lastCharacterData[key] !== value) {
                    updates.push({ key, value });
                }
            }
        } else {
            updates.push(...Object.entries(newData).map(([key, value]) => ({ key, value })));
        }
        
        // バッチでDOM更新
        requestAnimationFrame(() => {
            updates.forEach(({ key, value }) => {
                this.updateElement(`character-${key}`, value);
            });
        });
        
        this.lastCharacterData = { ...newData };
    }
    
    // メモ化機能
    memoize(fn, maxSize = 100) {
        const cache = new Map();
        
        return function(...args) {
            const key = JSON.stringify(args);
            
            if (cache.has(key)) {
                return cache.get(key);
            }
            
            const result = fn.apply(this, args);
            
            if (cache.size >= maxSize) {
                const firstKey = cache.keys().next().value;
                cache.delete(firstKey);
            }
            
            cache.set(key, result);
            return result;
        };
    }
}

// Worker使用例
// resources/js/workers/gameCalculation.js
self.addEventListener('message', function(e) {
    const { type, data } = e.data;
    
    switch (type) {
        case 'calculateDamage':
            const damage = calculateComplexDamage(data);
            self.postMessage({ type: 'damageResult', result: damage });
            break;
            
        case 'pathfinding':
            const path = findOptimalPath(data.start, data.end, data.obstacles);
            self.postMessage({ type: 'pathResult', result: path });
            break;
    }
});

function calculateComplexDamage({ attack, defense, skills, modifiers }) {
    // 重い計算処理
    let baseDamage = attack - defense;
    
    // スキル修正
    for (const [skill, level] of Object.entries(skills)) {
        baseDamage *= (1 + level * 0.01);
    }
    
    // 各種修正値
    for (const modifier of modifiers) {
        baseDamage *= modifier;
    }
    
    return Math.max(1, Math.floor(baseDamage));
}
```

### 2. CSS最適化
```css
/* 重要なCSSを先読み */
/* resources/css/critical.css */
.game-container {
    contain: layout style paint;
    will-change: transform;
}

/* GPU加速を活用 */
.dice {
    transform: translateZ(0);
    will-change: transform;
    backface-visibility: hidden;
}

/* アニメーション最適化 */
@keyframes optimizedFadeIn {
    from {
        opacity: 0;
        transform: translate3d(0, 20px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.fade-in-optimized {
    animation: optimizedFadeIn 0.3s ease-out;
    animation-fill-mode: both;
}

/* フォント読み込み最適化 */
@font-face {
    font-family: 'GameFont';
    src: url('/fonts/game-font.woff2') format('woff2');
    font-display: swap;
    font-weight: 400;
    font-style: normal;
}

/* レイアウトシフト防止 */
.character-avatar {
    width: 64px;
    height: 64px;
    background-color: #f3f4f6;
    border-radius: 50%;
}

.character-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: inherit;
}

/* 効率的なCSSセレクタ */
/* 避ける: .game-container .character .stats .hp */
/* 推奨: .character-hp */
.character-hp,
.character-sp,
.character-level {
    display: block;
    font-weight: 600;
    color: #1f2937;
}

/* メディアクエリの最適化 */
@media (max-width: 768px) {
    .game-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

@media (min-width: 769px) {
    .game-layout {
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
    }
}
```

### 3. 画像最適化
```php
<?php

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;

class ImageOptimizationService
{
    /**
     * レスポンシブ画像生成
     */
    public function generateResponsiveImages(string $imagePath): array
    {
        $sizes = [
            'small' => ['width' => 320, 'quality' => 80],
            'medium' => ['width' => 640, 'quality' => 85],
            'large' => ['width' => 1280, 'quality' => 90],
        ];
        
        $generatedImages = [];
        
        foreach ($sizes as $size => $config) {
            $image = Image::make($imagePath);
            $image->resize($config['width'], null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            $outputPath = $this->getOutputPath($imagePath, $size);
            $image->save($outputPath, $config['quality']);
            
            $generatedImages[$size] = $outputPath;
        }
        
        return $generatedImages;
    }
    
    /**
     * WebP変換
     */
    public function convertToWebP(string $imagePath): string
    {
        $image = Image::make($imagePath);
        $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $imagePath);
        
        $image->encode('webp', 90)->save($webpPath);
        
        return $webpPath;
    }
    
    /**
     * 画像遅延読み込み用のプレースホルダー生成
     */
    public function generatePlaceholder(string $imagePath, int $width = 20): string
    {
        $image = Image::make($imagePath);
        $image->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        
        return 'data:image/jpeg;base64,' . base64_encode($image->encode('jpg', 10));
    }
}
```

## キャッシュ戦略

### 1. Redis キャッシュ実装
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class GameCacheService
{
    private const CACHE_PREFIX = 'game:';
    private const DEFAULT_TTL = 3600; // 1時間
    
    /**
     * キャラクター情報のキャッシュ
     */
    public function cacheCharacter(Character $character): void
    {
        $key = self::CACHE_PREFIX . "character:{$character->id}";
        
        $data = [
            'id' => $character->id,
            'name' => $character->name,
            'hp' => $character->hp,
            'max_hp' => $character->max_hp,
            'sp' => $character->sp,
            'max_sp' => $character->max_sp,
            'game_position' => $character->game_position,
            'location_type' => $character->location_type,
            'skills' => $character->skills,
            'level' => $character->getLevel(),
        ];
        
        Redis::setex($key, self::DEFAULT_TTL, json_encode($data));
    }
    
    /**
     * キャッシュからキャラクター情報を取得
     */
    public function getCachedCharacter(int $characterId): ?array
    {
        $key = self::CACHE_PREFIX . "character:{$characterId}";
        $cached = Redis::get($key);
        
        return $cached ? json_decode($cached, true) : null;
    }
    
    /**
     * ゲーム統計のキャッシュ
     */
    public function cacheGameStats(): array
    {
        return Cache::remember('game:stats', 300, function () {
            return [
                'active_players' => Character::whereHas('user', function ($query) {
                    $query->where('last_login_at', '>', now()->subHour());
                })->count(),
                'total_characters' => Character::count(),
                'avg_level' => Character::avg(DB::raw('
                    (JSON_EXTRACT(skills, "$.attack") + 
                     JSON_EXTRACT(skills, "$.defense") + 
                     JSON_EXTRACT(skills, "$.agility")) / 10 + 1
                ')),
            ];
        });
    }
    
    /**
     * 場所情報のキャッシュ
     */
    public function cacheLocationData(int $locationId): array
    {
        $key = "location:{$locationId}";
        
        return Cache::remember($key, 1800, function () use ($locationId) {
            $location = Location::find($locationId);
            if (!$location) {
                return null;
            }
            
            return [
                'id' => $location->id,
                'name' => $location->name,
                'type' => $location->type,
                'description' => $location->description,
                'facilities' => $location->facilities,
                'player_count' => Character::where('location_type', $location->type)->count(),
            ];
        });
    }
    
    /**
     * サイコロ結果のキャッシュ（不正防止）
     */
    public function cacheDiceResult(int $userId, array $diceResult): void
    {
        $key = self::CACHE_PREFIX . "dice:{$userId}";
        Redis::setex($key, 300, json_encode($diceResult)); // 5分間有効
    }
    
    public function getCachedDiceResult(int $userId): ?array
    {
        $key = self::CACHE_PREFIX . "dice:{$userId}";
        $cached = Redis::get($key);
        return $cached ? json_decode($cached, true) : null;
    }
    
    /**
     * キャッシュ無効化
     */
    public function invalidateCharacterCache(int $characterId): void
    {
        $key = self::CACHE_PREFIX . "character:{$characterId}";
        Redis::del($key);
    }
    
    /**
     * 複数キーの一括取得
     */
    public function getMultiple(array $keys): array
    {
        $prefixedKeys = array_map(function ($key) {
            return self::CACHE_PREFIX . $key;
        }, $keys);
        
        $values = Redis::mget($prefixedKeys);
        
        $result = [];
        foreach ($keys as $index => $key) {
            $result[$key] = $values[$index] ? json_decode($values[$index], true) : null;
        }
        
        return $result;
    }
}
```

### 2. HTTP キャッシュ
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CacheHeaders
{
    public function handle(Request $request, Closure $next, string $maxAge = '3600')
    {
        $response = $next($request);
        
        // 静的リソースのキャッシュ
        if ($request->is('assets/*') || $request->is('images/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
            return $response;
        }
        
        // APIレスポンスのキャッシュ
        if ($request->is('api/game/stats')) {
            $response->headers->set('Cache-Control', "public, max-age={$maxAge}");
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $maxAge));
        }
        
        // ETags for conditional requests
        if ($request->is('api/*')) {
            $etag = md5($response->getContent());
            $response->headers->set('ETag', $etag);
            
            if ($request->headers->get('If-None-Match') === $etag) {
                return response('', 304);
            }
        }
        
        return $response;
    }
}
```

## アセット最適化

### 1. Vite 最適化設定
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
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // ベンダーチャンクの分離
                    vendor: ['lodash', 'axios'],
                    game: [
                        'resources/js/game/GameManager.js',
                        'resources/js/game/DiceManager.js',
                        'resources/js/game/MovementManager.js'
                    ],
                },
            },
        },
        // 圧縮設定
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        // gzip圧縮
        reportCompressedSize: true,
    },
    // 開発サーバー設定
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
});
```

### 2. 画像最適化パイプライン
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImageOptimizationService;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {path?}';
    protected $description = 'Optimize images for web delivery';
    
    public function handle(ImageOptimizationService $optimizer): int
    {
        $path = $this->argument('path') ?? public_path('images');
        
        $images = glob($path . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        
        $this->info("Found " . count($images) . " images to optimize");
        
        $bar = $this->output->createProgressBar(count($images));
        
        foreach ($images as $image) {
            // WebP変換
            $optimizer->convertToWebP($image);
            
            // レスポンシブ画像生成
            $optimizer->generateResponsiveImages($image);
            
            // プレースホルダー生成
            $placeholder = $optimizer->generatePlaceholder($image);
            file_put_contents($image . '.placeholder', $placeholder);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Image optimization completed!');
        
        return Command::SUCCESS;
    }
}
```

## CDN活用

### 1. CloudFlare設定
```php
<?php

namespace App\Services;

class CdnService
{
    /**
     * アセットURLの生成
     */
    public function asset(string $path): string
    {
        $cdnUrl = config('app.cdn_url');
        
        if (!$cdnUrl) {
            return asset($path);
        }
        
        // ファイルのハッシュを追加してキャッシュバスティング
        $manifestPath = public_path('build/manifest.json');
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest[$path])) {
                $path = $manifest[$path]['file'];
            }
        }
        
        return rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
    }
    
    /**
     * 画像URL生成（WebP対応）
     */
    public function image(string $path, array $options = []): string
    {
        $cdnUrl = config('app.cdn_url');
        
        if (!$cdnUrl) {
            return asset("images/{$path}");
        }
        
        // WebP サポートチェック
        $supportsWebP = $this->supportsWebP();
        if ($supportsWebP && !str_ends_with($path, '.webp')) {
            $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $path);
            if (file_exists(public_path("images/{$webpPath}"))) {
                $path = $webpPath;
            }
        }
        
        $url = rtrim($cdnUrl, '/') . '/images/' . ltrim($path, '/');
        
        // CloudFlare Image Resizing
        if (!empty($options)) {
            $params = http_build_query([
                'width' => $options['width'] ?? null,
                'height' => $options['height'] ?? null,
                'quality' => $options['quality'] ?? 85,
                'format' => $options['format'] ?? 'auto',
            ]);
            
            $url = "/cdn-cgi/image/{$params}/{$url}";
        }
        
        return $url;
    }
    
    private function supportsWebP(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'image/webp') !== false;
    }
}
```

### 2. Service Worker キャッシュ
```javascript
// public/sw.js
const CACHE_NAME = 'test-smg-v1';
const STATIC_RESOURCES = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/images/logo.png',
    '/fonts/game-font.woff2'
];

// インストール時のキャッシュ
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(STATIC_RESOURCES))
    );
});

// フェッチイベントの処理
self.addEventListener('fetch', event => {
    // API リクエストの場合
    if (event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // ゲーム統計など更新頻度の低いAPIはキャッシュ
                    if (event.request.url.includes('/api/game/stats')) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => cache.put(event.request, responseClone));
                    }
                    return response;
                })
                .catch(() => {
                    // オフライン時はキャッシュから返す
                    return caches.match(event.request);
                })
        );
        return;
    }
    
    // 静的リソースの場合
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                
                return fetch(event.request)
                    .then(response => {
                        // 200番台のレスポンスのみキャッシュ
                        if (response.status === 200) {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME)
                                .then(cache => cache.put(event.request, responseClone));
                        }
                        return response;
                    });
            })
    );
});
```

## ゲーム固有最適化

### 1. リアルタイム処理最適化
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RealtimeGameService
{
    /**
     * サイコロロールの最適化
     */
    public function optimizedDiceRoll(Character $character): array
    {
        // 結果をあらかじめ計算してキャッシュ
        $cacheKey = "dice_pool:{$character->id}";
        $cachedResults = Redis::lrange($cacheKey, 0, -1);
        
        if (count($cachedResults) < 5) {
            // プリ計算してキューに追加
            $this->precomputeDiceResults($character);
            $cachedResults = Redis::lrange($cacheKey, 0, -1);
        }
        
        // キューから1つ取得
        $result = Redis::lpop($cacheKey);
        
        return $result ? json_decode($result, true) : $this->fallbackDiceRoll($character);
    }
    
    private function precomputeDiceResults(Character $character): void
    {
        $cacheKey = "dice_pool:{$character->id}";
        
        for ($i = 0; $i < 10; $i++) {
            $diceRolls = [
                rand(1, 6),
                rand(1, 6),
                rand(1, 6)
            ];
            
            $baseTotal = array_sum($diceRolls);
            $bonus = $this->calculateBonus($character);
            $finalMovement = $baseTotal + $bonus;
            
            $result = [
                'dice_rolls' => $diceRolls,
                'base_total' => $baseTotal,
                'bonus' => $bonus,
                'final_movement' => $finalMovement
            ];
            
            Redis::rpush($cacheKey, json_encode($result));
        }
        
        Redis::expire($cacheKey, 300); // 5分で期限切れ
    }
    
    /**
     * 移動処理の最適化
     */
    public function optimizedMove(Character $character, int $steps): array
    {
        // バッチ更新用のキューに追加
        $updateData = [
            'character_id' => $character->id,
            'old_position' => $character->game_position,
            'new_position' => min(100, max(0, $character->game_position + $steps)),
            'timestamp' => microtime(true)
        ];
        
        Redis::lpush('movement_queue', json_encode($updateData));
        
        // 即座にレスポンスを返す
        return [
            'position' => $updateData['new_position'],
            'old_position' => $updateData['old_position'],
            'steps_moved' => $steps
        ];
    }
    
    /**
     * バッチ処理での実際の更新
     */
    public function processBatchUpdates(): void
    {
        $updates = [];
        
        // キューから最大100件取得
        for ($i = 0; $i < 100; $i++) {
            $updateJson = Redis::rpop('movement_queue');
            if (!$updateJson) break;
            
            $update = json_decode($updateJson, true);
            $updates[$update['character_id']] = $update;
        }
        
        if (empty($updates)) return;
        
        // バルクアップデート実行
        $this->executeBulkPositionUpdate($updates);
    }
    
    private function executeBulkPositionUpdate(array $updates): void
    {
        $cases = [];
        $ids = [];
        
        foreach ($updates as $characterId => $update) {
            $cases[] = "WHEN {$characterId} THEN {$update['new_position']}";
            $ids[] = $characterId;
        }
        
        if (empty($cases)) return;
        
        $idsString = implode(',', $ids);
        $casesString = implode(' ', $cases);
        
        DB::statement("
            UPDATE characters 
            SET game_position = CASE id {$casesString} END,
                updated_at = NOW()
            WHERE id IN ({$idsString})
        ");
    }
}
```

### 2. セッション最適化
```php
<?php

namespace App\Services;

class OptimizedSessionService
{
    /**
     * ゲーム状態の差分更新
     */
    public function updateGameState(array $changes): void
    {
        $session = request()->session();
        $currentState = $session->get('game_state', []);
        
        // 差分のみ更新
        $newState = array_merge($currentState, $changes);
        
        // 不要なデータを削除
        $this->cleanupSessionData($newState);
        
        $session->put('game_state', $newState);
    }
    
    private function cleanupSessionData(array &$state): void
    {
        // 古いデータの削除
        if (isset($state['last_cleanup']) && 
            time() - $state['last_cleanup'] > 3600) {
            
            unset($state['temporary_data']);
            unset($state['old_positions']);
            $state['last_cleanup'] = time();
        }
        
        // サイズ制限
        if (strlen(serialize($state)) > 64000) { // 64KB制限
            // 最も古いデータから削除
            unset($state['history']);
            unset($state['cached_calculations']);
        }
    }
    
    /**
     * 圧縮されたセッションデータ
     */
    public function compressSessionData(array $data): string
    {
        return base64_encode(gzcompress(serialize($data)));
    }
    
    public function decompressSessionData(string $compressed): array
    {
        return unserialize(gzuncompress(base64_decode($compressed)));
    }
}
```

## プロファイリング

### 1. デバッグバー設定
```php
<?php

// config/debugbar.php
return [
    'enabled' => env('DEBUGBAR_ENABLED', false),
    'except' => [
        'telescope*',
        'horizon*',
    ],
    'collectors' => [
        'phpinfo'         => true,
        'messages'        => true,
        'time'            => true,
        'memory'          => true,
        'exceptions'      => true,
        'log'             => true,
        'db'              => true,
        'views'           => true,
        'route'           => true,
        'auth'            => false,
        'gate'            => true,
        'session'         => true,
        'symfony_request' => true,
        'mail'            => true,
        'laravel'         => false,
        'events'          => false,
        'default_request' => false,
        'logs'            => false,
        'files'           => false,
        'config'          => false,
        'cache'           => false,
    ],
];
```

### 2. カスタムプロファイラー
```php
<?php

namespace App\Services;

class CustomProfiler
{
    private static array $timers = [];
    private static array $queries = [];
    private static array $memory = [];
    
    public static function start(string $name): void
    {
        self::$timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true)
        ];
    }
    
    public static function end(string $name): array
    {
        if (!isset(self::$timers[$name])) {
            throw new \InvalidArgumentException("Timer '{$name}' was not started");
        }
        
        $timer = self::$timers[$name];
        $duration = microtime(true) - $timer['start'];
        $memoryUsed = memory_get_usage(true) - $timer['memory_start'];
        
        $result = [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'queries' => count(DB::getQueryLog())
        ];
        
        unset(self::$timers[$name]);
        
        return $result;
    }
    
    public static function profile(callable $callback, string $name = null): array
    {
        $name = $name ?: 'anonymous_' . uniqid();
        
        self::start($name);
        DB::enableQueryLog();
        
        $result = $callback();
        
        $profile = self::end($name);
        $profile['queries_executed'] = DB::getQueryLog();
        $profile['result'] = $result;
        
        return $profile;
    }
    
    public static function dumpReport(): void
    {
        echo "=== Performance Report ===\n";
        echo "Peak Memory: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
        echo "Current Memory: " . number_format(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
        echo "===========================\n";
    }
}
```

## 負荷テスト

### 1. Apache Bench設定
```bash
#!/bin/bash

# 基本的な負荷テスト
ab -n 1000 -c 10 http://localhost/api/game/stats

# サイコロロールのテスト
ab -n 500 -c 5 -p dice_payload.json -T application/json -H "Authorization: Bearer TOKEN" http://localhost/api/game/roll-dice

# 移動処理のテスト
ab -n 1000 -c 20 -p move_payload.json -T application/json -H "Authorization: Bearer TOKEN" http://localhost/api/game/move
```

### 2. K6 負荷テストスクリプト
```javascript
// k6-load-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export let options = {
    vus: 50, // 50 virtual users
    duration: '5m', // 5分間実行
    thresholds: {
        http_req_duration: ['p(95)<500'], // 95%のリクエストが500ms以下
        errors: ['rate<0.01'], // エラー率1%以下
    },
};

export default function() {
    // ログイン
    let loginResponse = http.post('http://localhost/api/login', {
        email: 'test@example.com',
        password: 'password'
    });
    
    let success = check(loginResponse, {
        'login successful': (r) => r.status === 200,
    });
    
    if (!success) {
        errorRate.add(1);
        return;
    }
    
    let token = loginResponse.json('token');
    let headers = { 'Authorization': `Bearer ${token}` };
    
    // ゲーム統計の取得
    let statsResponse = http.get('http://localhost/api/game/stats', { headers });
    check(statsResponse, {
        'stats retrieved': (r) => r.status === 200,
        'response time < 200ms': (r) => r.timings.duration < 200,
    });
    
    // サイコロロール
    let diceResponse = http.post('http://localhost/api/game/roll-dice', null, { headers });
    check(diceResponse, {
        'dice roll successful': (r) => r.status === 200,
        'has dice_rolls': (r) => r.json('dice_rolls') !== undefined,
    });
    
    if (diceResponse.status === 200) {
        let finalMovement = diceResponse.json('final_movement');
        
        // 移動処理
        let moveResponse = http.post('http://localhost/api/game/move', {
            direction: 'right',
            steps: finalMovement
        }, { headers });
        
        check(moveResponse, {
            'move successful': (r) => r.status === 200,
        });
    }
    
    sleep(1); // 1秒待機
}
```

### 3. 継続監視設定
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PerformanceMonitor;

class PerformanceHealthCheck extends Command
{
    protected $signature = 'performance:health-check';
    protected $description = 'Run performance health checks';
    
    public function handle(PerformanceMonitor $monitor): int
    {
        $checks = [
            'database_response_time',
            'memory_usage',
            'cache_hit_ratio',
            'queue_size',
            'disk_usage'
        ];
        
        $results = [];
        
        foreach ($checks as $check) {
            $result = $monitor->runCheck($check);
            $results[$check] = $result;
            
            $status = $result['status'] === 'ok' ? 'OK' : 'FAIL';
            $this->line("{$check}: {$status} ({$result['value']})");
            
            if ($result['status'] !== 'ok') {
                $this->error("  Issue: {$result['message']}");
            }
        }
        
        // アラート送信
        $failedChecks = array_filter($results, fn($r) => $r['status'] !== 'ok');
        if (!empty($failedChecks)) {
            $monitor->sendAlert($failedChecks);
        }
        
        return empty($failedChecks) ? Command::SUCCESS : Command::FAILURE;
    }
}
```

## まとめ

### パフォーマンス最適化チェックリスト
- [ ] Laravel最適化コマンドの実行
- [ ] OPcache設定の確認
- [ ] データベースインデックスの最適化
- [ ] Redisキャッシュの実装
- [ ] フロントエンド最適化
- [ ] 画像最適化パイプライン
- [ ] CDN設定
- [ ] 負荷テストの実施
- [ ] 継続監視の設定

### 継続的改善プロセス
1. **定期的な計測**: 週次パフォーマンス監査
2. **ボトルネック特定**: プロファイリングツール活用
3. **負荷テスト**: リリース前の性能確認
4. **監視アラート**: 閾値超過時の自動通知
5. **改善実装**: 計測結果に基づく最適化

このパフォーマンス最適化により、test_smgの高速で安定したゲーム体験を実現できます。