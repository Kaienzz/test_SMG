# パフォーマンス監視書

## 文書の概要

- **作成日**: 2025年7月25日
- **対象システム**: test_smg（Laravel/PHPブラウザRPG）
- **作成者**: AI開発チーム
- **バージョン**: v1.0

## 目的

test_smgプロジェクトの包括的なパフォーマンス監視システムを構築し、安定したサービス運用を実現する。

## 目次

1. [監視戦略](#監視戦略)
2. [監視指標(KPI)](#監視指標kpi)
3. [監視ツール](#監視ツール)
4. [ダッシュボード](#ダッシュボード)
5. [アラート設定](#アラート設定)
6. [ログ分析](#ログ分析)
7. [レポーティング](#レポーティング)
8. [インシデント対応](#インシデント対応)
9. [継続的改善](#継続的改善)
10. [運用手順](#運用手順)

## 監視戦略

### 1. 監視の4つの黄金シグナル
```
┌─────────────────────────────────┐
│        4つの黄金シグナル        │
├─────────────────────────────────┤
│ 1. Latency (レイテンシ)        │
│    - API応答時間               │
│    - ページ読み込み時間         │
├─────────────────────────────────┤
│ 2. Traffic (トラフィック)      │
│    - リクエスト数/秒           │
│    - 同時接続ユーザー数         │
├─────────────────────────────────┤
│ 3. Errors (エラー率)           │
│    - HTTPエラー率              │
│    - アプリケーションエラー率   │
├─────────────────────────────────┤
│ 4. Saturation (飽和度)         │
│    - CPU使用率                 │
│    - メモリ使用率              │
│    - ディスク使用率            │
└─────────────────────────────────┘
```

### 2. 監視レベル定義
```php
<?php

namespace App\Monitoring;

class MonitoringLevels
{
    public const LEVELS = [
        'INFRASTRUCTURE' => [
            'description' => 'サーバー、ネットワーク、ストレージ',
            'metrics' => ['cpu', 'memory', 'disk', 'network'],
            'tools' => ['Prometheus', 'Node Exporter']
        ],
        'APPLICATION' => [
            'description' => 'アプリケーションパフォーマンス',
            'metrics' => ['response_time', 'throughput', 'error_rate'],
            'tools' => ['Laravel Telescope', 'New Relic']
        ],
        'BUSINESS' => [
            'description' => 'ビジネスメトリクス',
            'metrics' => ['active_users', 'game_sessions', 'user_engagement'],
            'tools' => ['Custom Metrics', 'Google Analytics']
        ],
        'USER_EXPERIENCE' => [
            'description' => 'ユーザー体験',
            'metrics' => ['core_web_vitals', 'bounce_rate', 'conversion_rate'],
            'tools' => ['Google PageSpeed', 'Real User Monitoring']
        ]
    ];
}
```

### 3. 監視対象の優先度
```php
<?php

namespace App\Monitoring;

class MonitoringPriority
{
    public const HIGH_PRIORITY = [
        'api_response_time' => 'ゲーム体験に直接影響',
        'database_connection' => 'サービス可用性に影響',
        'memory_usage' => 'システム安定性に影響',
        'error_rate' => 'ユーザー体験に影響'
    ];
    
    public const MEDIUM_PRIORITY = [
        'cache_hit_ratio' => 'パフォーマンスに影響',
        'queue_size' => 'バックグラウンド処理の遅延',
        'disk_usage' => '将来の問題予測',
        'session_count' => 'リソースプランニング'
    ];
    
    public const LOW_PRIORITY = [
        'log_file_size' => '運用管理',
        'backup_status' => 'データ保護',
        'ssl_certificate' => 'セキュリティ',
        'dependency_updates' => 'メンテナンス'
    ];
}
```

## 監視指標(KPI)

### 1. パフォーマンス指標
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class MetricsCollector
{
    /**
     * アプリケーションメトリクス収集
     */
    public function collectApplicationMetrics(): array
    {
        return [
            'response_time' => $this->getAverageResponseTime(),
            'throughput' => $this->getRequestThroughput(),
            'error_rate' => $this->getErrorRate(),
            'active_users' => $this->getActiveUsers(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'db_connections' => $this->getDatabaseConnections(),
            'cache_hit_ratio' => $this->getCacheHitRatio(),
        ];
    }
    
    private function getAverageResponseTime(): float
    {
        // 過去5分間の平均応答時間
        $key = 'metrics:response_times:' . floor(time() / 300);
        $times = Redis::lrange($key, 0, -1);
        
        if (empty($times)) {
            return 0;
        }
        
        return array_sum($times) / count($times);
    }
    
    private function getRequestThroughput(): int
    {
        // 過去1分間のリクエスト数
        $key = 'metrics:requests:' . floor(time() / 60);
        return (int) Redis::get($key) ?: 0;
    }
    
    private function getErrorRate(): float
    {
        $totalRequests = $this->getRequestThroughput();
        $errorKey = 'metrics:errors:' . floor(time() / 60);
        $errors = (int) Redis::get($errorKey) ?: 0;
        
        return $totalRequests > 0 ? ($errors / $totalRequests) * 100 : 0;
    }
    
    private function getActiveUsers(): int
    {
        // 過去15分以内にアクティブなユーザー
        return DB::table('users')
            ->where('last_activity', '>', now()->subMinutes(15))
            ->count();
    }
    
    private function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => $this->getMemoryLimit(),
            'usage_percent' => (memory_get_usage(true) / $this->getMemoryLimit()) * 100
        ];
    }
    
    private function getCpuUsage(): float
    {
        $load = sys_getloadavg();
        return $load[0]; // 1分間の平均負荷
    }
    
    private function getDatabaseConnections(): array
    {
        $connections = DB::select("SHOW PROCESSLIST");
        return [
            'active' => count($connections),
            'max' => (int) DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value
        ];
    }
    
    private function getCacheHitRatio(): float
    {
        $info = Redis::info('stats');
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }
    
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }
}
```

### 2. ゲーム固有メトリクス
```php
<?php

namespace App\Services;

class GameMetricsCollector
{
    /**
     * ゲーム特有のメトリクス収集
     */
    public function collectGameMetrics(): array
    {
        return [
            'dice_rolls_per_minute' => $this->getDiceRollsPerMinute(),
            'character_movements_per_minute' => $this->getMovementsPerMinute(),
            'average_session_duration' => $this->getAverageSessionDuration(),
            'players_by_location' => $this->getPlayersByLocation(),
            'level_distribution' => $this->getLevelDistribution(),
            'battle_completion_rate' => $this->getBattleCompletionRate(),
            'user_retention_rate' => $this->getUserRetentionRate(),
        ];
    }
    
    private function getDiceRollsPerMinute(): int
    {
        return Redis::get('game_metrics:dice_rolls:' . floor(time() / 60)) ?: 0;
    }
    
    private function getMovementsPerMinute(): int
    {
        return Redis::get('game_metrics:movements:' . floor(time() / 60)) ?: 0;
    }
    
    private function getAverageSessionDuration(): float
    {
        $sessions = DB::table('user_sessions')
            ->where('created_at', '>', now()->subHour())
            ->whereNotNull('ended_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, ended_at)) as avg_duration')
            ->first();
            
        return $sessions->avg_duration ?: 0;
    }
    
    private function getPlayersByLocation(): array
    {
        return DB::table('characters')
            ->select([
                'location_type',
                DB::raw('COUNT(*) as count')
            ])
            ->where('updated_at', '>', now()->subMinutes(30))
            ->groupBy('location_type')
            ->pluck('count', 'location_type')
            ->toArray();
    }
    
    private function getLevelDistribution(): array
    {
        return DB::table('characters')
            ->selectRaw('
                CASE 
                    WHEN (JSON_EXTRACT(skills, "$.attack") + JSON_EXTRACT(skills, "$.defense") + JSON_EXTRACT(skills, "$.agility")) / 10 + 1 <= 5 THEN "1-5"
                    WHEN (JSON_EXTRACT(skills, "$.attack") + JSON_EXTRACT(skills, "$.defense") + JSON_EXTRACT(skills, "$.agility")) / 10 + 1 <= 10 THEN "6-10"
                    WHEN (JSON_EXTRACT(skills, "$.attack") + JSON_EXTRACT(skills, "$.defense") + JSON_EXTRACT(skills, "$.agility")) / 10 + 1 <= 20 THEN "11-20"
                    ELSE "21+"
                END as level_range,
                COUNT(*) as count
            ')
            ->groupBy('level_range')
            ->pluck('count', 'level_range')
            ->toArray();
    }
    
    private function getBattleCompletionRate(): float
    {
        $total = DB::table('battle_logs')
            ->where('created_at', '>', now()->subDay())
            ->count();
            
        $completed = DB::table('battle_logs')
            ->where('created_at', '>', now()->subDay())
            ->whereIn('result', ['victory', 'defeat'])
            ->count();
            
        return $total > 0 ? ($completed / $total) * 100 : 0;
    }
    
    private function getUserRetentionRate(): array
    {
        $oneDay = DB::table('users')
            ->where('created_at', '>', now()->subDay())
            ->where('last_login_at', '>', now()->subDay())
            ->count();
            
        $sevenDay = DB::table('users')
            ->where('created_at', '>', now()->subWeek())
            ->where('last_login_at', '>', now()->subDays(2))
            ->count();
            
        $thirtyDay = DB::table('users')
            ->where('created_at', '>', now()->subMonth())
            ->where('last_login_at', '>', now()->subWeek())
            ->count();
            
        return [
            'day_1' => $oneDay,
            'day_7' => $sevenDay,
            'day_30' => $thirtyDay
        ];
    }
}
```

## 監視ツール

### 1. Prometheus + Grafana 設定
```yaml
# docker-compose.monitoring.yml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus:latest
    ports:
      - "9090:9090"
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.libraries=/etc/prometheus/console_libraries'
      - '--web.console.templates=/etc/prometheus/consoles'
      - '--web.enable-lifecycle'

  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
    volumes:
      - grafana_data:/var/lib/grafana
      - ./grafana/dashboards:/etc/grafana/provisioning/dashboards
      - ./grafana/datasources:/etc/grafana/provisioning/datasources

  node_exporter:
    image: prom/node-exporter:latest
    ports:
      - "9100:9100"
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.rootfs=/rootfs'
      - '--path.sysfs=/host/sys'
      - '--collector.filesystem.ignored-mount-points=^/(sys|proc|dev|host|etc)($$|/)'

  redis_exporter:
    image: oliver006/redis_exporter:latest
    ports:
      - "9121:9121"
    environment:
      - REDIS_ADDR=redis://redis:6379

volumes:
  prometheus_data:
  grafana_data:
```

### 2. Laravel カスタムメトリクス
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class PrometheusMetrics
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $startTime;
        
        // メトリクスをRedisに記録
        $this->recordMetrics($request, $response, $duration);
        
        return $response;
    }
    
    private function recordMetrics(Request $request, $response, float $duration): void
    {
        $route = $request->route()?->getName() ?? 'unknown';
        $method = $request->method();
        $status = $response->getStatusCode();
        
        // 応答時間の記録
        Redis::lpush('metrics:response_times', $duration);
        Redis::expire('metrics:response_times', 300); // 5分間保持
        
        // リクエスト数のカウント
        $minute = floor(time() / 60);
        Redis::incr("metrics:requests:{$minute}");
        Redis::expire("metrics:requests:{$minute}", 3600);
        
        // エラー数のカウント
        if ($status >= 400) {
            Redis::incr("metrics:errors:{$minute}");
            Redis::expire("metrics:errors:{$minute}", 3600);
        }
        
        // ルート別メトリクス
        Redis::lpush("metrics:route:{$route}:duration", $duration);
        Redis::expire("metrics:route:{$route}:duration", 300);
        
        Redis::incr("metrics:route:{$route}:requests");
        Redis::expire("metrics:route:{$route}:requests", 3600);
    }
}
```

### 3. メトリクスエクスポーター
```php
<?php

namespace App\Http\Controllers;

use App\Services\MetricsCollector;
use App\Services\GameMetricsCollector;

class MetricsController extends Controller
{
    public function __construct(
        private MetricsCollector $metricsCollector,
        private GameMetricsCollector $gameMetricsCollector
    ) {}
    
    /**
     * Prometheus形式でメトリクスを出力
     */
    public function prometheus()
    {
        $metrics = $this->metricsCollector->collectApplicationMetrics();
        $gameMetrics = $this->gameMetricsCollector->collectGameMetrics();
        
        $output = [];
        
        // アプリケーションメトリクス
        $output[] = "# HELP app_response_time_seconds Average response time in seconds";
        $output[] = "# TYPE app_response_time_seconds gauge";
        $output[] = "app_response_time_seconds " . ($metrics['response_time'] / 1000);
        
        $output[] = "# HELP app_throughput_requests_per_second Requests per second";
        $output[] = "# TYPE app_throughput_requests_per_second gauge";
        $output[] = "app_throughput_requests_per_second " . $metrics['throughput'];
        
        $output[] = "# HELP app_error_rate_percent Error rate percentage";
        $output[] = "# TYPE app_error_rate_percent gauge";
        $output[] = "app_error_rate_percent " . $metrics['error_rate'];
        
        $output[] = "# HELP app_active_users Active users";
        $output[] = "# TYPE app_active_users gauge";
        $output[] = "app_active_users " . $metrics['active_users'];
        
        $output[] = "# HELP app_memory_usage_bytes Memory usage in bytes";
        $output[] = "# TYPE app_memory_usage_bytes gauge";
        $output[] = "app_memory_usage_bytes " . $metrics['memory_usage']['current'];
        
        $output[] = "# HELP app_memory_usage_percent Memory usage percentage";
        $output[] = "# TYPE app_memory_usage_percent gauge";
        $output[] = "app_memory_usage_percent " . $metrics['memory_usage']['usage_percent'];
        
        // ゲームメトリクス
        $output[] = "# HELP game_dice_rolls_per_minute Dice rolls per minute";
        $output[] = "# TYPE game_dice_rolls_per_minute gauge";
        $output[] = "game_dice_rolls_per_minute " . $gameMetrics['dice_rolls_per_minute'];
        
        $output[] = "# HELP game_movements_per_minute Character movements per minute";
        $output[] = "# TYPE game_movements_per_minute gauge";
        $output[] = "game_movements_per_minute " . $gameMetrics['character_movements_per_minute'];
        
        $output[] = "# HELP game_average_session_duration_minutes Average session duration in minutes";
        $output[] = "# TYPE game_average_session_duration_minutes gauge";
        $output[] = "game_average_session_duration_minutes " . $gameMetrics['average_session_duration'];
        
        // 場所別プレイヤー数
        foreach ($gameMetrics['players_by_location'] as $location => $count) {
            $output[] = "game_players_by_location{location=\"{$location}\"} {$count}";
        }
        
        return response(implode("\n", $output))
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    }
    
    /**
     * JSON形式でメトリクスを出力
     */
    public function json()
    {
        $metrics = array_merge(
            $this->metricsCollector->collectApplicationMetrics(),
            $this->gameMetricsCollector->collectGameMetrics()
        );
        
        return response()->json([
            'timestamp' => now()->toISOString(),
            'metrics' => $metrics
        ]);
    }
}
```

## ダッシュボード

### 1. Grafana ダッシュボード設定
```json
{
  "dashboard": {
    "id": null,
    "title": "test_smg Performance Dashboard",
    "tags": [
      "test_smg",
      "performance",
      "game"
    ],
    "timezone": "Asia/Tokyo",
    "panels": [
      {
        "id": 1,
        "title": "Response Time",
        "type": "stat",
        "targets": [
          {
            "expr": "app_response_time_seconds",
            "legendFormat": "Response Time"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "unit": "s",
            "thresholds": {
              "steps": [
                {"color": "green", "value": null},
                {"color": "yellow", "value": 0.5},
                {"color": "red", "value": 1.0}
              ]
            }
          }
        }
      },
      {
        "id": 2,
        "title": "Active Users",
        "type": "stat",
        "targets": [
          {
            "expr": "app_active_users",
            "legendFormat": "Active Users"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "unit": "short"
          }
        }
      },
      {
        "id": 3,
        "title": "Request Rate",
        "type": "graph",
        "targets": [
          {
            "expr": "rate(app_throughput_requests_per_second[5m])",
            "legendFormat": "Requests/sec"
          }
        ]
      },
      {
        "id": 4,
        "title": "Error Rate",
        "type": "graph",
        "targets": [
          {
            "expr": "app_error_rate_percent",
            "legendFormat": "Error Rate %"
          }
        ],
        "yAxes": [
          {
            "max": 100,
            "min": 0,
            "unit": "percent"
          }
        ]
      },
      {
        "id": 5,
        "title": "Memory Usage",
        "type": "graph",
        "targets": [
          {
            "expr": "app_memory_usage_percent",
            "legendFormat": "Memory Usage %"
          }
        ],
        "yAxes": [
          {
            "max": 100,
            "min": 0,
            "unit": "percent"
          }
        ]
      },
      {
        "id": 6,
        "title": "Game Activities",
        "type": "graph",
        "targets": [
          {
            "expr": "game_dice_rolls_per_minute",
            "legendFormat": "Dice Rolls/min"
          },
          {
            "expr": "game_movements_per_minute",
            "legendFormat": "Movements/min"
          }
        ]
      },
      {
        "id": 7,
        "title": "Players by Location",
        "type": "piechart",
        "targets": [
          {
            "expr": "game_players_by_location",
            "legendFormat": "{{location}}"
          }
        ]
      }
    ],
    "time": {
      "from": "now-1h",
      "to": "now"
    },
    "refresh": "30s"
  }
}
```

### 2. リアルタイムダッシュボード
```php
<?php

namespace App\Http\Controllers;

use App\Services\MetricsCollector;
use App\Services\GameMetricsCollector;

class DashboardController extends Controller
{
    public function realtime()
    {
        return view('dashboard.realtime');
    }
    
    public function realtimeData()
    {
        $metricsCollector = app(MetricsCollector::class);
        $gameMetricsCollector = app(GameMetricsCollector::class);
        
        $data = [
            'timestamp' => now()->toISOString(),
            'application' => $metricsCollector->collectApplicationMetrics(),
            'game' => $gameMetricsCollector->collectGameMetrics(),
            'system' => [
                'load_average' => sys_getloadavg(),
                'memory_info' => [
                    'total' => $this->getTotalMemory(),
                    'available' => $this->getAvailableMemory(),
                    'used_percent' => $this->getMemoryUsedPercent()
                ],
                'disk_usage' => [
                    'total' => disk_total_space('/'),
                    'free' => disk_free_space('/'),
                    'used_percent' => (1 - disk_free_space('/') / disk_total_space('/')) * 100
                ]
            ]
        ];
        
        return response()->json($data);
    }
    
    private function getTotalMemory(): int
    {
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+) kB/', $meminfo, $matches);
        return ($matches[1] ?? 0) * 1024;
    }
    
    private function getAvailableMemory(): int
    {
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemAvailable:\s+(\d+) kB/', $meminfo, $matches);
        return ($matches[1] ?? 0) * 1024;
    }
    
    private function getMemoryUsedPercent(): float
    {
        $total = $this->getTotalMemory();
        $available = $this->getAvailableMemory();
        return $total > 0 ? ((($total - $available) / $total) * 100) : 0;
    }
}
```

## アラート設定

### 1. アラートルール
```yaml
# prometheus_alerts.yml
groups:
  - name: test_smg_alerts
    rules:
      - alert: HighResponseTime
        expr: app_response_time_seconds > 1.0
        for: 2m
        labels:
          severity: warning
        annotations:
          summary: "High response time detected"
          description: "Response time is {{ $value }}s which is above threshold"

      - alert: HighErrorRate
        expr: app_error_rate_percent > 5
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "High error rate detected"
          description: "Error rate is {{ $value }}% which is above 5%"

      - alert: HighMemoryUsage
        expr: app_memory_usage_percent > 80
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High memory usage"
          description: "Memory usage is {{ $value }}% which is above 80%"

      - alert: DatabaseConnectionIssue
        expr: app_database_connections_active > app_database_connections_max * 0.8
        for: 2m
        labels:
          severity: critical
        annotations:
          summary: "Database connection pool nearly exhausted"
          description: "Active connections: {{ $value }}"

      - alert: LowCacheHitRatio
        expr: app_cache_hit_ratio < 70
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Low cache hit ratio"
          description: "Cache hit ratio is {{ $value }}% which is below 70%"

      - alert: NoGameActivity
        expr: game_dice_rolls_per_minute == 0 and game_movements_per_minute == 0
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "No game activity detected"
          description: "No dice rolls or movements in the last 10 minutes"
```

### 2. Laravel アラートシステム
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\AlertNotification;

class AlertManager
{
    private const ALERT_THRESHOLDS = [
        'response_time' => 1000, // ms
        'error_rate' => 5, // %
        'memory_usage' => 80, // %
        'cpu_usage' => 80, // %
        'disk_usage' => 80, // %
        'active_connections' => 80, // % of max
    ];
    
    /**
     * メトリクスをチェックしてアラートを送信
     */
    public function checkMetricsAndAlert(array $metrics): void
    {
        foreach ($metrics as $metric => $value) {
            if (isset(self::ALERT_THRESHOLDS[$metric])) {
                $this->checkThreshold($metric, $value, self::ALERT_THRESHOLDS[$metric]);
            }
        }
    }
    
    private function checkThreshold(string $metric, $value, $threshold): void
    {
        if ($this->isThresholdExceeded($metric, $value, $threshold)) {
            $this->sendAlert($metric, $value, $threshold);
        }
    }
    
    private function isThresholdExceeded(string $metric, $value, $threshold): bool
    {
        switch ($metric) {
            case 'response_time':
                return $value > $threshold;
            case 'error_rate':
            case 'memory_usage':
            case 'cpu_usage':
            case 'disk_usage':
            case 'active_connections':
                return $value > $threshold;
            default:
                return false;
        }
    }
    
    private function sendAlert(string $metric, $value, $threshold): void
    {
        $severity = $this->determineSeverity($metric, $value, $threshold);
        
        $alertData = [
            'metric' => $metric,
            'value' => $value,
            'threshold' => $threshold,
            'severity' => $severity,
            'timestamp' => now(),
            'hostname' => gethostname(),
        ];
        
        // ログに記録
        Log::channel('alerts')->{$severity}('Alert triggered', $alertData);
        
        // アラート頻度制限チェック
        if ($this->shouldSendAlert($metric, $severity)) {
            $this->sendNotification($alertData);
        }
    }
    
    private function determineSeverity(string $metric, $value, $threshold): string
    {
        $ratio = $value / $threshold;
        
        if ($ratio >= 2.0) return 'critical';
        if ($ratio >= 1.5) return 'error';
        if ($ratio >= 1.2) return 'warning';
        return 'info';
    }
    
    private function shouldSendAlert(string $metric, string $severity): bool
    {
        $key = "alert_throttle:{$metric}:{$severity}";
        $lastSent = Redis::get($key);
        
        // 重要度に応じた送信間隔
        $intervals = [
            'critical' => 300,  // 5分
            'error' => 900,     // 15分
            'warning' => 1800,  // 30分
            'info' => 3600,     // 1時間
        ];
        
        $interval = $intervals[$severity] ?? 3600;
        
        if (!$lastSent || (time() - $lastSent) > $interval) {
            Redis::set($key, time(), 'EX', $interval);
            return true;
        }
        
        return false;
    }
    
    private function sendNotification(array $alertData): void
    {
        // メール送信
        if (config('alerts.email.enabled')) {
            $emails = config('alerts.email.recipients');
            foreach ($emails as $email) {
                Mail::to($email)->send(new AlertNotification($alertData));
            }
        }
        
        // Slack送信
        if (config('alerts.slack.enabled')) {
            $this->sendSlackAlert($alertData);
        }
        
        // SMS送信（重要度が高い場合のみ）
        if (in_array($alertData['severity'], ['critical', 'error']) && config('alerts.sms.enabled')) {
            $this->sendSmsAlert($alertData);
        }
    }
    
    private function sendSlackAlert(array $alertData): void
    {
        $webhook = config('alerts.slack.webhook_url');
        $color = $this->getSeverityColor($alertData['severity']);
        
        $payload = [
            'text' => 'Performance Alert - test_smg',
            'attachments' => [
                [
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => 'Metric',
                            'value' => $alertData['metric'],
                            'short' => true
                        ],
                        [
                            'title' => 'Value',
                            'value' => $alertData['value'],
                            'short' => true
                        ],
                        [
                            'title' => 'Threshold',
                            'value' => $alertData['threshold'],
                            'short' => true
                        ],
                        [
                            'title' => 'Severity',
                            'value' => $alertData['severity'],
                            'short' => true
                        ],
                        [
                            'title' => 'Time',
                            'value' => $alertData['timestamp']->format('Y-m-d H:i:s'),
                            'short' => true
                        ],
                        [
                            'title' => 'Host',
                            'value' => $alertData['hostname'],
                            'short' => true
                        ]
                    ]
                ]
            ]
        ];
        
        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($payload))
        ]);
        
        curl_exec($ch);
        curl_close($ch);
    }
    
    private function getSeverityColor(string $severity): string
    {
        return match ($severity) {
            'critical' => '#FF0000',
            'error' => '#FF6600',
            'warning' => '#FFAA00',
            'info' => '#00AAFF',
            default => '#CCCCCC'
        };
    }
}
```

## ログ分析

### 1. ログ構造化
```php
<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class StructuredJsonFormatter extends JsonFormatter
{
    public function format(LogRecord $record): string
    {
        $normalized = [
            'timestamp' => $record->datetime->format('Y-m-d\TH:i:s.uP'),
            'level' => $record->level->getName(),
            'message' => $record->message,
            'context' => $record->context,
            'extra' => $record->extra,
            'application' => config('app.name'),
            'environment' => config('app.env'),
            'host' => gethostname(),
            'process_id' => getmypid(),
            'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ];
        
        return $this->toJson($normalized) . "\n";
    }
}
```

### 2. ログ集約システム
```php
<?php

namespace App\Services;

use Elasticsearch\Client;

class LogAnalyzer
{
    public function __construct(private Client $elasticsearch) {}
    
    /**
     * エラーログの分析
     */
    public function analyzeErrors(string $timeframe = '1h'): array
    {
        $params = [
            'index' => 'test-smg-logs-*',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['range' => ['timestamp' => ['gte' => "now-{$timeframe}"]]],
                            ['terms' => ['level' => ['ERROR', 'CRITICAL']]]
                        ]
                    ]
                ],
                'aggs' => [
                    'error_types' => [
                        'terms' => [
                            'field' => 'context.exception.class.keyword',
                            'size' => 10
                        ]
                    ],
                    'error_timeline' => [
                        'date_histogram' => [
                            'field' => 'timestamp',
                            'interval' => '5m'
                        ]
                    ],
                    'top_urls' => [
                        'terms' => [
                            'field' => 'url.keyword',
                            'size' => 10
                        ]
                    ]
                ]
            ]
        ];
        
        $response = $this->elasticsearch->search($params);
        
        return [
            'total_errors' => $response['hits']['total']['value'],
            'error_types' => $response['aggregations']['error_types']['buckets'],
            'timeline' => $response['aggregations']['error_timeline']['buckets'],
            'problem_urls' => $response['aggregations']['top_urls']['buckets']
        ];
    }
    
    /**
     * パフォーマンス異常の検出
     */
    public function detectPerformanceAnomalies(): array
    {
        $params = [
            'index' => 'test-smg-logs-*',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['range' => ['timestamp' => ['gte' => 'now-1h']]],
                            ['exists' => ['field' => 'context.response_time']]
                        ]
                    ]
                ],
                'aggs' => [
                    'slow_requests' => [
                        'filter' => [
                            'range' => ['context.response_time' => ['gte' => 1000]]
                        ],
                        'aggs' => [
                            'by_route' => [
                                'terms' => [
                                    'field' => 'context.route.keyword',
                                    'size' => 10
                                ]
                            ]
                        ]
                    ],
                    'avg_response_time' => [
                        'avg' => ['field' => 'context.response_time']
                    ],
                    'percentiles' => [
                        'percentiles' => [
                            'field' => 'context.response_time',
                            'percents' => [50, 95, 99]
                        ]
                    ]
                ]
            ]
        ];
        
        $response = $this->elasticsearch->search($params);
        
        return [
            'slow_requests' => $response['aggregations']['slow_requests']['by_route']['buckets'],
            'avg_response_time' => $response['aggregations']['avg_response_time']['value'],
            'percentiles' => $response['aggregations']['percentiles']['values']
        ];
    }
    
    /**
     * ユーザー行動分析
     */
    public function analyzeUserBehavior(): array
    {
        $params = [
            'index' => 'test-smg-logs-*',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['range' => ['timestamp' => ['gte' => 'now-24h']]],
                            ['exists' => ['field' => 'user_id']]
                        ]
                    ]
                ],
                'aggs' => [
                    'unique_users' => [
                        'cardinality' => ['field' => 'user_id']
                    ],
                    'popular_actions' => [
                        'terms' => [
                            'field' => 'context.action.keyword',
                            'size' => 10
                        ]
                    ],
                    'user_sessions' => [
                        'terms' => [
                            'field' => 'user_id',
                            'size' => 1000
                        ],
                        'aggs' => [
                            'session_duration' => [
                                'range' => [
                                    'field' => 'timestamp',
                                    'ranges' => [
                                        ['key' => 'short', 'to' => 'now-5m'],
                                        ['key' => 'medium', 'from' => 'now-5m', 'to' => 'now-30m'],
                                        ['key' => 'long', 'from' => 'now-30m']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $response = $this->elasticsearch->search($params);
        
        return [
            'unique_users' => $response['aggregations']['unique_users']['value'],
            'popular_actions' => $response['aggregations']['popular_actions']['buckets'],
            'session_patterns' => $response['aggregations']['user_sessions']['buckets']
        ];
    }
}
```

## レポーティング

### 1. 自動レポート生成
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportGenerator;

class GeneratePerformanceReport extends Command
{
    protected $signature = 'reports:performance {--period=daily} {--format=pdf}';
    protected $description = 'Generate performance report';
    
    public function handle(ReportGenerator $generator): int
    {
        $period = $this->option('period');
        $format = $this->option('format');
        
        $this->info("Generating {$period} performance report in {$format} format...");
        
        try {
            $reportPath = $generator->generatePerformanceReport($period, $format);
            
            $this->info("Report generated successfully: {$reportPath}");
            
            // メール送信
            if (config('reports.auto_email')) {
                $generator->emailReport($reportPath);
                $this->info('Report emailed to stakeholders');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to generate report: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
```

### 2. レポート生成サービス
```php
<?php

namespace App\Services;

use App\Services\MetricsCollector;
use App\Services\GameMetricsCollector;
use App\Services\LogAnalyzer;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportGenerator
{
    public function __construct(
        private MetricsCollector $metricsCollector,
        private GameMetricsCollector $gameMetricsCollector,
        private LogAnalyzer $logAnalyzer
    ) {}
    
    /**
     * パフォーマンスレポート生成
     */
    public function generatePerformanceReport(string $period, string $format = 'pdf'): string
    {
        $data = $this->collectReportData($period);
        
        return match ($format) {
            'pdf' => $this->generatePdfReport($data, $period),
            'html' => $this->generateHtmlReport($data, $period),
            'json' => $this->generateJsonReport($data, $period),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }
    
    private function collectReportData(string $period): array
    {
        $timeframe = match ($period) {
            'hourly' => '1h',
            'daily' => '24h',
            'weekly' => '7d',
            'monthly' => '30d',
            default => '24h'
        };
        
        return [
            'period' => $period,
            'timeframe' => $timeframe,
            'generated_at' => now(),
            'metrics' => [
                'application' => $this->getHistoricalMetrics('application', $timeframe),
                'game' => $this->getHistoricalMetrics('game', $timeframe),
                'system' => $this->getHistoricalMetrics('system', $timeframe),
            ],
            'logs' => [
                'errors' => $this->logAnalyzer->analyzeErrors($timeframe),
                'performance' => $this->logAnalyzer->detectPerformanceAnomalies(),
                'user_behavior' => $this->logAnalyzer->analyzeUserBehavior(),
            ],
            'insights' => $this->generateInsights($timeframe),
            'recommendations' => $this->generateRecommendations($timeframe),
        ];
    }
    
    private function getHistoricalMetrics(string $type, string $timeframe): array
    {
        // 時系列データの取得（実装は監視システムに依存）
        $key = "historical_metrics:{$type}:{$timeframe}";
        $cached = Redis::get($key);
        
        if ($cached) {
            return json_decode($cached, true);
        }
        
        // デフォルトデータ（実際の実装では監視システムから取得）
        return [
            'avg_response_time' => 0.3,
            'max_response_time' => 2.1,
            'total_requests' => 15420,
            'error_count' => 12,
            'error_rate' => 0.08,
            'peak_memory' => 128 * 1024 * 1024,
            'avg_cpu' => 35.2,
        ];
    }
    
    private function generateInsights(string $timeframe): array
    {
        return [
            'performance_trend' => 'improving', // stable, improving, degrading
            'error_trend' => 'stable',
            'usage_trend' => 'increasing',
            'critical_issues' => [],
            'positive_changes' => [
                'Response time improved by 15% compared to previous period',
                'Error rate decreased from 0.12% to 0.08%'
            ],
            'areas_of_concern' => [
                'Memory usage increased by 8%',
                'Database query time slightly increased'
            ]
        ];
    }
    
    private function generateRecommendations(string $timeframe): array
    {
        return [
            'immediate_actions' => [
                'Monitor memory usage closely',
                'Review database queries for optimization opportunities'
            ],
            'short_term_improvements' => [
                'Implement Redis caching for frequently accessed data',
                'Optimize image sizes and implement lazy loading'
            ],
            'long_term_strategies' => [
                'Consider horizontal scaling for increased load',
                'Implement CDN for static assets'
            ]
        ];
    }
    
    private function generatePdfReport(array $data, string $period): string
    {
        $pdf = Pdf::loadView('reports.performance', compact('data', 'period'));
        
        $filename = "performance_report_{$period}_" . now()->format('Y-m-d_H-i-s') . '.pdf';
        $path = storage_path("reports/{$filename}");
        
        $pdf->save($path);
        
        return $path;
    }
    
    private function generateHtmlReport(array $data, string $period): string
    {
        $html = view('reports.performance', compact('data', 'period'))->render();
        
        $filename = "performance_report_{$period}_" . now()->format('Y-m-d_H-i-s') . '.html';
        $path = storage_path("reports/{$filename}");
        
        file_put_contents($path, $html);
        
        return $path;
    }
    
    private function generateJsonReport(array $data, string $period): string
    {
        $filename = "performance_report_{$period}_" . now()->format('Y-m-d_H-i-s') . '.json';
        $path = storage_path("reports/{$filename}");
        
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        
        return $path;
    }
    
    public function emailReport(string $reportPath): void
    {
        $recipients = config('reports.recipients');
        
        foreach ($recipients as $recipient) {
            Mail::to($recipient)
                ->send(new PerformanceReportMail($reportPath));
        }
    }
}
```

## インシデント対応

### 1. 自動インシデント検出
```php
<?php

namespace App\Services;

use App\Models\Incident;

class IncidentManager
{
    /**
     * インシデント自動検出
     */
    public function detectIncidents(): void
    {
        $metrics = app(MetricsCollector::class)->collectApplicationMetrics();
        
        $incidents = [];
        
        // 高レスポンス時間の検出
        if ($metrics['response_time'] > 2000) {
            $incidents[] = [
                'type' => 'high_response_time',
                'severity' => $metrics['response_time'] > 5000 ? 'critical' : 'major',
                'description' => "Response time is {$metrics['response_time']}ms",
                'metrics' => ['response_time' => $metrics['response_time']]
            ];
        }
        
        // 高エラー率の検出
        if ($metrics['error_rate'] > 5) {
            $incidents[] = [
                'type' => 'high_error_rate',
                'severity' => $metrics['error_rate'] > 10 ? 'critical' : 'major',
                'description' => "Error rate is {$metrics['error_rate']}%",
                'metrics' => ['error_rate' => $metrics['error_rate']]
            ];
        }
        
        // メモリ使用量の検出
        if ($metrics['memory_usage']['usage_percent'] > 90) {
            $incidents[] = [
                'type' => 'high_memory_usage',
                'severity' => 'major',
                'description' => "Memory usage is {$metrics['memory_usage']['usage_percent']}%",
                'metrics' => ['memory_usage' => $metrics['memory_usage']]
            ];
        }
        
        // サービス停止の検出
        if ($metrics['active_users'] == 0 && $metrics['throughput'] == 0) {
            $lastActivity = Redis::get('last_user_activity');
            if ($lastActivity && (time() - $lastActivity) > 600) { // 10分間活動なし
                $incidents[] = [
                    'type' => 'service_outage',
                    'severity' => 'critical',
                    'description' => "No user activity detected for 10+ minutes",
                    'metrics' => ['active_users' => 0, 'throughput' => 0]
                ];
            }
        }
        
        foreach ($incidents as $incidentData) {
            $this->createIncident($incidentData);
        }
    }
    
    private function createIncident(array $data): Incident
    {
        // 重複チェック
        $existing = Incident::where('type', $data['type'])
            ->where('status', '!=', 'resolved')
            ->where('created_at', '>', now()->subMinutes(15))
            ->first();
            
        if ($existing) {
            return $existing;
        }
        
        $incident = Incident::create([
            'type' => $data['type'],
            'severity' => $data['severity'],
            'description' => $data['description'],
            'metrics' => $data['metrics'],
            'status' => 'open',
            'detected_at' => now(),
        ]);
        
        $this->notifyIncident($incident);
        
        return $incident;
    }
    
    private function notifyIncident(Incident $incident): void
    {
        // PagerDuty統合
        if (config('incidents.pagerduty.enabled')) {
            $this->sendPagerDutyAlerts($incident);
        }
        
        // Slack通知
        if (config('incidents.slack.enabled')) {
            $this->sendSlackIncidentNotification($incident);
        }
        
        // メール通知（重要度に応じて）
        if (in_array($incident->severity, ['critical', 'major'])) {
            $this->sendEmailNotification($incident);
        }
    }
    
    /**
     * インシデント状況更新
     */
    public function updateIncidentStatus(int $incidentId, string $status, string $notes = null): void
    {
        $incident = Incident::findOrFail($incidentId);
        
        $incident->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
        
        if ($notes) {
            $incident->notes()->create([
                'content' => $notes,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
        }
        
        // 解決通知
        if ($status === 'resolved') {
            $this->notifyIncidentResolved($incident);
        }
    }
    
    /**
     * インシデント分析レポート
     */
    public function generateIncidentReport(string $period = 'month'): array
    {
        $query = Incident::query();
        
        switch ($period) {
            case 'day':
                $query->where('created_at', '>', now()->subDay());
                break;
            case 'week':
                $query->where('created_at', '>', now()->subWeek());
                break;
            case 'month':
                $query->where('created_at', '>', now()->subMonth());
                break;
        }
        
        $incidents = $query->get();
        
        return [
            'total_incidents' => $incidents->count(),
            'by_severity' => $incidents->groupBy('severity')->map->count(),
            'by_type' => $incidents->groupBy('type')->map->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime($incidents),
            'mttr' => $this->calculateMTTR($incidents),
            'mtbf' => $this->calculateMTBF($incidents),
            'trends' => $this->calculateIncidentTrends($incidents),
        ];
    }
    
    private function calculateAverageResolutionTime($incidents): float
    {
        $resolved = $incidents->where('status', 'resolved')
            ->whereNotNull('resolved_at');
            
        if ($resolved->isEmpty()) {
            return 0;
        }
        
        $totalMinutes = $resolved->sum(function ($incident) {
            return $incident->created_at->diffInMinutes($incident->resolved_at);
        });
        
        return $totalMinutes / $resolved->count();
    }
    
    private function calculateMTTR($incidents): float
    {
        // Mean Time To Repair
        return $this->calculateAverageResolutionTime($incidents);
    }
    
    private function calculateMTBF($incidents): float
    {
        // Mean Time Between Failures
        if ($incidents->count() < 2) {
            return 0;
        }
        
        $timeSpan = $incidents->max('created_at')->diffInHours($incidents->min('created_at'));
        return $timeSpan / ($incidents->count() - 1);
    }
    
    private function calculateIncidentTrends($incidents): array
    {
        return [
            'trending_up' => $incidents->where('created_at', '>', now()->subDays(7))->count() > 
                           $incidents->where('created_at', '>', now()->subDays(14))
                                    ->where('created_at', '<=', now()->subDays(7))->count(),
            'most_common_type' => $incidents->groupBy('type')->sortByDesc->count()->keys()->first(),
            'peak_hours' => $incidents->groupBy(function ($incident) {
                return $incident->created_at->hour;
            })->sortByDesc->count()->take(3)->keys()->toArray(),
        ];
    }
}
```

## 継続的改善

### 1. パフォーマンス改善プロセス
```php
<?php

namespace App\Services;

class PerformanceImprovementTracker
{
    /**
     * 改善提案の生成
     */
    public function generateImprovementSuggestions(): array
    {
        $metrics = app(MetricsCollector::class)->collectApplicationMetrics();
        $gameMetrics = app(GameMetricsCollector::class)->collectGameMetrics();
        
        $suggestions = [];
        
        // レスポンス時間の改善
        if ($metrics['response_time'] > 500) {
            $suggestions[] = [
                'category' => 'performance',
                'priority' => 'high',
                'title' => 'Improve API Response Time',
                'description' => 'Current average response time is ' . $metrics['response_time'] . 'ms',
                'recommendations' => [
                    'Implement Redis caching for frequently accessed data',
                    'Optimize database queries using indexes',
                    'Consider implementing API response caching'
                ],
                'estimated_impact' => 'High',
                'estimated_effort' => 'Medium'
            ];
        }
        
        // キャッシュヒット率の改善
        if ($metrics['cache_hit_ratio'] < 80) {
            $suggestions[] = [
                'category' => 'caching',
                'priority' => 'medium',
                'title' => 'Improve Cache Hit Ratio',
                'description' => 'Current cache hit ratio is ' . $metrics['cache_hit_ratio'] . '%',
                'recommendations' => [
                    'Review cache key strategies',
                    'Implement cache warming for critical data',
                    'Increase cache TTL for stable data'
                ],
                'estimated_impact' => 'Medium',
                'estimated_effort' => 'Low'
            ];
        }
        
        // メモリ使用量の最適化
        if ($metrics['memory_usage']['usage_percent'] > 70) {
            $suggestions[] = [
                'category' => 'resource_optimization',
                'priority' => 'medium',
                'title' => 'Optimize Memory Usage',
                'description' => 'Memory usage is at ' . $metrics['memory_usage']['usage_percent'] . '%',
                'recommendations' => [
                    'Profile memory usage to identify leaks',
                    'Optimize object creation and destruction',
                    'Implement more efficient data structures'
                ],
                'estimated_impact' => 'Medium',
                'estimated_effort' => 'High'
            ];
        }
        
        // ゲーム体験の改善
        if ($gameMetrics['average_session_duration'] < 15) {
            $suggestions[] = [
                'category' => 'user_experience',
                'priority' => 'high',
                'title' => 'Improve User Engagement',
                'description' => 'Average session duration is only ' . $gameMetrics['average_session_duration'] . ' minutes',
                'recommendations' => [
                    'Analyze user behavior patterns',
                    'Implement more engaging game mechanics',
                    'Optimize loading times for better first impression'
                ],
                'estimated_impact' => 'High',
                'estimated_effort' => 'High'
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * 改善実装の追跡
     */
    public function trackImprovement(array $improvement): void
    {
        $baselineMetrics = $this->captureBaselineMetrics();
        
        // 改善レコードの作成
        $record = [
            'id' => uniqid(),
            'title' => $improvement['title'],
            'category' => $improvement['category'],
            'implemented_at' => now(),
            'baseline_metrics' => $baselineMetrics,
            'target_metrics' => $improvement['target_metrics'] ?? [],
            'status' => 'monitoring'
        ];
        
        Redis::setex("improvement:{$record['id']}", 2592000, json_encode($record)); // 30日保持
    }
    
    /**
     * 改善効果の測定
     */
    public function measureImprovementImpact(string $improvementId): array
    {
        $recordJson = Redis::get("improvement:{$improvementId}");
        if (!$recordJson) {
            throw new \Exception("Improvement record not found");
        }
        
        $record = json_decode($recordJson, true);
        $currentMetrics = $this->captureBaselineMetrics();
        
        $impact = [];
        foreach ($record['baseline_metrics'] as $metric => $baselineValue) {
            $currentValue = $currentMetrics[$metric] ?? 0;
            $improvement = $this->calculateImprovement($metric, $baselineValue, $currentValue);
            
            $impact[$metric] = [
                'baseline' => $baselineValue,
                'current' => $currentValue,
                'improvement_percent' => $improvement,
                'direction' => $this->getImprovementDirection($metric)
            ];
        }
        
        return [
            'improvement_id' => $improvementId,
            'measurement_date' => now(),
            'time_since_implementation' => now()->diffInDays($record['implemented_at']),
            'metrics_impact' => $impact,
            'overall_score' => $this->calculateOverallImprovementScore($impact)
        ];
    }
    
    private function captureBaselineMetrics(): array
    {
        $appMetrics = app(MetricsCollector::class)->collectApplicationMetrics();
        $gameMetrics = app(GameMetricsCollector::class)->collectGameMetrics();
        
        return [
            'response_time' => $appMetrics['response_time'],
            'error_rate' => $appMetrics['error_rate'],
            'memory_usage_percent' => $appMetrics['memory_usage']['usage_percent'],
            'cache_hit_ratio' => $appMetrics['cache_hit_ratio'],
            'throughput' => $appMetrics['throughput'],
            'session_duration' => $gameMetrics['average_session_duration'],
            'dice_rolls_per_minute' => $gameMetrics['dice_rolls_per_minute'],
        ];
    }
    
    private function calculateImprovement(string $metric, $baseline, $current): float
    {
        if ($baseline == 0) {
            return 0;
        }
        
        $change = ($current - $baseline) / $baseline * 100;
        
        // メトリクスによって改善の方向が異なる
        $improvementDirection = $this->getImprovementDirection($metric);
        
        return $improvementDirection === 'lower' ? -$change : $change;
    }
    
    private function getImprovementDirection(string $metric): string
    {
        $lowerIsBetter = [
            'response_time',
            'error_rate',
            'memory_usage_percent'
        ];
        
        return in_array($metric, $lowerIsBetter) ? 'lower' : 'higher';
    }
    
    private function calculateOverallImprovementScore(array $impact): float
    {
        $scores = [];
        $weights = [
            'response_time' => 3,
            'error_rate' => 3,
            'memory_usage_percent' => 2,
            'cache_hit_ratio' => 2,
            'throughput' => 2,
            'session_duration' => 1,
            'dice_rolls_per_minute' => 1,
        ];
        
        foreach ($impact as $metric => $data) {
            $weight = $weights[$metric] ?? 1;
            $scores[] = $data['improvement_percent'] * $weight;
        }
        
        return array_sum($scores) / array_sum($weights);
    }
}
```

## 運用手順

### 1. 日次運用チェックリスト
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DailyHealthCheck extends Command
{
    protected $signature = 'monitor:daily-check';
    protected $description = 'Perform daily health checks';
    
    public function handle(): int
    {
        $this->info('Starting daily health check...');
        
        $checks = [
            'System Resources' => $this->checkSystemResources(),
            'Application Performance' => $this->checkApplicationPerformance(),
            'Database Health' => $this->checkDatabaseHealth(),
            'Cache Status' => $this->checkCacheStatus(),
            'Game Metrics' => $this->checkGameMetrics(),
            'Security Alerts' => $this->checkSecurityAlerts(),
        ];
        
        $overallStatus = 'PASS';
        
        foreach ($checks as $checkName => $result) {
            $status = $result['status'] === 'pass' ? '✅' : '❌';
            $this->line("{$status} {$checkName}: {$result['message']}");
            
            if ($result['status'] !== 'pass') {
                $overallStatus = 'FAIL';
                
                if (!empty($result['recommendations'])) {
                    foreach ($result['recommendations'] as $recommendation) {
                        $this->warn("  → {$recommendation}");
                    }
                }
            }
        }
        
        $this->newLine();
        $this->info("Overall Status: {$overallStatus}");
        
        // レポート生成
        $this->generateDailyReport($checks);
        
        return $overallStatus === 'PASS' ? Command::SUCCESS : Command::FAILURE;
    }
    
    private function checkSystemResources(): array
    {
        $load = sys_getloadavg()[0];
        $memoryUsage = (memory_get_usage(true) / (1024 * 1024)); // MB
        $diskUsage = (1 - disk_free_space('/') / disk_total_space('/')) * 100;
        
        $issues = [];
        if ($load > 2.0) $issues[] = "High CPU load: {$load}";
        if ($memoryUsage > 200) $issues[] = "High memory usage: {$memoryUsage}MB";
        if ($diskUsage > 80) $issues[] = "High disk usage: {$diskUsage}%";
        
        return [
            'status' => empty($issues) ? 'pass' : 'fail',
            'message' => empty($issues) ? 'All system resources within normal range' : implode(', ', $issues),
            'recommendations' => empty($issues) ? [] : ['Monitor resource usage closely', 'Consider scaling resources']
        ];
    }
    
    private function checkApplicationPerformance(): array
    {
        $metrics = app(MetricsCollector::class)->collectApplicationMetrics();
        
        $issues = [];
        if ($metrics['response_time'] > 1000) $issues[] = "High response time: {$metrics['response_time']}ms";
        if ($metrics['error_rate'] > 1) $issues[] = "High error rate: {$metrics['error_rate']}%";
        if ($metrics['cache_hit_ratio'] < 70) $issues[] = "Low cache hit ratio: {$metrics['cache_hit_ratio']}%";
        
        return [
            'status' => empty($issues) ? 'pass' : 'fail',
            'message' => empty($issues) ? 'Application performance is good' : implode(', ', $issues),
            'recommendations' => empty($issues) ? [] : ['Review slow queries', 'Optimize cache strategies']
        ];
    }
    
    private function checkDatabaseHealth(): array
    {
        try {
            $connectionCheck = DB::connection()->getPdo();
            $slowQueries = DB::select("SELECT COUNT(*) as count FROM information_schema.processlist WHERE time > 10");
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            
            $issues = [];
            if ($slowQueries[0]->count > 0) $issues[] = "Slow queries detected: {$slowQueries[0]->count}";
            
            return [
                'status' => empty($issues) ? 'pass' : 'fail',
                'message' => empty($issues) ? 'Database is healthy' : implode(', ', $issues),
                'recommendations' => empty($issues) ? [] : ['Review slow query log', 'Optimize problematic queries']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'recommendations' => ['Check database server status', 'Verify connection parameters']
            ];
        }
    }
    
    private function checkCacheStatus(): array
    {
        try {
            Redis::ping();
            $info = Redis::info();
            $memoryUsage = $info['used_memory_human'] ?? 'unknown';
            $hitRatio = app(MetricsCollector::class)->getCacheHitRatio();
            
            $issues = [];
            if ($hitRatio < 70) $issues[] = "Low cache hit ratio: {$hitRatio}%";
            
            return [
                'status' => empty($issues) ? 'pass' : 'fail',
                'message' => empty($issues) ? "Cache is healthy (Memory: {$memoryUsage})" : implode(', ', $issues),
                'recommendations' => empty($issues) ? [] : ['Review cache key strategies', 'Increase cache TTL for stable data']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Cache connection failed: ' . $e->getMessage(),
                'recommendations' => ['Check Redis server status', 'Verify cache configuration']
            ];
        }
    }
    
    private function checkGameMetrics(): array
    {
        $gameMetrics = app(GameMetricsCollector::class)->collectGameMetrics();
        
        $issues = [];
        if ($gameMetrics['dice_rolls_per_minute'] == 0) $issues[] = "No dice roll activity";
        if ($gameMetrics['average_session_duration'] < 5) $issues[] = "Very short session duration: {$gameMetrics['average_session_duration']} min";
        
        return [
            'status' => empty($issues) ? 'pass' : 'fail',
            'message' => empty($issues) ? 'Game metrics are normal' : implode(', ', $issues),
            'recommendations' => empty($issues) ? [] : ['Investigate user engagement', 'Check for technical issues affecting gameplay']
        ];
    }
    
    private function checkSecurityAlerts(): array
    {
        $recentAlerts = DB::table('security_logs')
            ->where('created_at', '>', now()->subDay())
            ->where('level', 'error')
            ->count();
            
        $issues = [];
        if ($recentAlerts > 10) $issues[] = "High number of security alerts: {$recentAlerts}";
        
        return [
            'status' => empty($issues) ? 'pass' : 'fail',
            'message' => empty($issues) ? 'No security concerns' : implode(', ', $issues),
            'recommendations' => empty($issues) ? [] : ['Review security logs', 'Investigate suspicious activities']
        ];
    }
    
    private function generateDailyReport(array $checks): void
    {
        $reportData = [
            'date' => now()->format('Y-m-d'),
            'checks' => $checks,
            'generated_at' => now(),
        ];
        
        $reportPath = storage_path('reports/daily_health_' . now()->format('Y-m-d') . '.json');
        file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));
        
        $this->info("Daily report saved to: {$reportPath}");
    }
}
```

## まとめ

### 監視システム構築チェックリスト
- [ ] メトリクス収集システムの実装
- [ ] Prometheus + Grafana セットアップ
- [ ] アラート設定と通知システム
- [ ] ダッシュボード作成
- [ ] ログ分析システム構築
- [ ] 自動レポート生成
- [ ] インシデント対応プロセス確立
- [ ] 日次・週次運用手順作成

### 継続的改善プロセス
1. **定期的なメトリクス レビュー**: 週次パフォーマンス分析
2. **閾値の調整**: 成長に合わせたアラート基準見直し
3. **新しい監視項目の追加**: ビジネス成長に応じた監視拡張
4. **ツールの評価と更新**: 監視ツールの定期的な見直し
5. **チーム教育**: 監視データの読み方と対応方法の共有

このパフォーマンス監視システムにより、test_smgの安定した運用と継続的な改善を実現できます。

<function_calls>
<invoke name="TodoWrite">
<parameter name="todos">[{"content": "Phase 1-1: \u30b2\u30fc\u30e0\u30b3\u30f3\u30bb\u30d7\u30c8\u30fb\u8981\u4ef6\u5b9a\u7fa9\u66f8\u4f5c\u6210", "status": "completed", "priority": "high", "id": "1"}, {"content": "Phase 1-2: \u30d7\u30ed\u30b8\u30a7\u30af\u30c8\u958b\u59cb\u8cc7\u6599\uff08Inception Deck\uff09\u4f5c\u6210", "status": "completed", "priority": "high", "id": "2"}, {"content": "Phase 2-1: \u30a2\u30fc\u30ad\u30c6\u30af\u30c1\u30e3\u8a2d\u8a08\u30fb\u8cac\u52d9\u5b9a\u7fa9\u66f8\u4f5c\u6210", "status": "completed", "priority": "high", "id": "3"}, {"content": "Phase 2-2: \u30c7\u30fc\u30bf\u30d9\u30fc\u30b9\u8a2d\u8a08\u66f8\u4f5c\u6210", "status": "completed", "priority": "high", "id": "4"}, {"content": "Phase 2-3: API\u8a2d\u8a08\u66f8\u4f5c\u6210", "status": "completed", "priority": "medium", "id": "5"}, {"content": "Phase 2-4: \u753b\u9762\u9077\u79fb\u8a2d\u8a08\u66f8\u4f5c\u6210", "status": "completed", "priority": "medium", "id": "6"}, {"content": "Phase 2-5: \u30a8\u30e9\u30fc\u30cf\u30f3\u30c9\u30ea\u30f3\u30b0\u8a2d\u8a08\u66f8\u4f5c\u6210", "status": "completed", "priority": "medium", "id": "7"}, {"content": "Phase 2-6: \u578b\u5b9a\u7fa9\u30fb\u30c7\u30fc\u30bf\u69cb\u9020\u8a2d\u8a08\u66f8\u4f5c\u6210", "status": "completed", "priority": "medium", "id": "8"}, {"content": "Phase 2-7: \u958b\u767a\u74b0\u5883\u30bb\u30c3\u30c8\u30a2\u30c3\u30d7\u66f8\u4f5c\u6210", "status": "completed", "priority": "medium", "id": "9"}, {"content": "Phase 2-8: \u30c6\u30b9\u30c8\u6226\u7565\u66f8\u4f5c\u6210", "status": "completed", "priority": "medium", "id": "10"}, {"content": "Phase 2-9: \u30d5\u30ed\u30f3\u30c8\u30a8\u30f3\u30c9\u8a2d\u8a08\u66f8\u4f5c\u6210", "status": "completed", "priority": "medium", "id": "11"}, {"content": "Phase 2-10: \u30bb\u30ad\u30e5\u30ea\u30c6\u30a3\u8a2d\u8a08\u66f8\u4f5c\u6210", "status": "completed", "priority": "medium", "id": "12"}, {"content": "Phase 2-11: \u30d1\u30d5\u30a9\u30fc\u30de\u30f3\u30b9\u6700\u9069\u5316\u66f8\u4f5c\u6210", "status": "completed", "priority": "medium", "id": "13"}, {"content": "Phase 2-12: \u30d1\u30d5\u30a9\u30fc\u30de\u30f3\u30b9\u76e3\u8996\u66f8\u4f5c\u6210", "status": "completed", "priority": "low", "id": "14"}, {"content": "Phase 3-1: \u57fa\u672c\u30c7\u30b6\u30a4\u30f3\u30b7\u30b9\u30c6\u30e0\u4f5c\u6210", "status": "pending", "priority": "low", "id": "15"}, {"content": "Phase 3-2: \u30c7\u30b6\u30a4\u30f3\u539f\u5247\u66f8\u4f5c\u6210", "status": "pending", "priority": "low", "id": "16"}, {"content": "Phase 3-3: \u30b3\u30f3\u30dd\u30fc\u30cd\u30f3\u30c8\u8a2d\u8a08\u66f8\u4f5c\u6210", "status": "pending", "priority": "low", "id": "17"}, {"content": "Phase 3-4: \u30a2\u30cb\u30e1\u30fc\u30b7\u30e7\u30f3\u30b7\u30b9\u30c6\u30e0\u66f8\u4f5c\u6210", "status": "pending", "priority": "low", "id": "18"}, {"content": "Phase 3-5: \u30ec\u30a4\u30a2\u30a6\u30c8\u30b7\u30b9\u30c6\u30e0\u66f8\u4f5c\u6210", "status": "pending", "priority": "low", "id": "19"}]