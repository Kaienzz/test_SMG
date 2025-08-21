<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MonsterSpawnList Model
 * 
 * RouteとMonsterの関係を管理する統合モデル
 * 旧SpawnList + MonsterSpawnの機能を統合
 */
class MonsterSpawnList extends Model
{
    protected $fillable = [
        'location_id',
        'monster_id',
        'spawn_rate',
        'priority',
        'min_level',
        'max_level',
        'is_active',
    ];

    protected $casts = [
        'spawn_rate' => 'decimal:2',
        'priority' => 'integer',
        'min_level' => 'integer',
        'max_level' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function gameLocation()
    {
        return $this->belongsTo(Route::class, 'location_id');
    }

    public function monster()
    {
        return $this->belongsTo(Monster::class, 'monster_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority');
    }

    public function scopeForLevel($query, int $level)
    {
        return $query->where(function ($q) use ($level) {
            $q->where('min_level', '<=', $level)
              ->orWhereNull('min_level');
        })->where(function ($q) use ($level) {
            $q->where('max_level', '>=', $level)
              ->orWhereNull('max_level');
        });
    }

    public function scopeForLocation($query, string $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeOrderedBySpawnRate($query)
    {
        return $query->orderByDesc('spawn_rate');
    }

    // Utility Methods
    public function getSpawnRatePercentage(): float
    {
        return $this->spawn_rate * 100;
    }

    public function isLevelEligible(int $level): bool
    {
        $minOk = !$this->min_level || $level >= $this->min_level;
        $maxOk = !$this->max_level || $level <= $this->max_level;
        
        return $minOk && $maxOk;
    }

    public function getLevelRangeDisplay(): string
    {
        if ($this->min_level && $this->max_level) {
            return "Lv.{$this->min_level}-{$this->max_level}";
        } elseif ($this->min_level) {
            return "Lv.{$this->min_level}+";
        } elseif ($this->max_level) {
            return "～Lv.{$this->max_level}";
        }
        
        return '制限なし';
    }

    /**
     * ロケーション用のモンスター出現リストを取得
     */
    public static function getSpawnableMonsters(string $locationId, ?int $playerLevel = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::with('monster')
                    ->active()
                    ->forLocation($locationId)
                    ->byPriority();

        if ($playerLevel !== null) {
            $query->forLevel($playerLevel);
        }

        return $query->get();
    }

    /**
     * 確率に基づいてランダムモンスターを選択
     */
    public static function getRandomMonsterForLocation(string $locationId, ?int $playerLevel = null): ?Monster
    {
        $spawns = self::getSpawnableMonsters($locationId, $playerLevel);
        
        if ($spawns->isEmpty()) {
            return null;
        }

        // 合計出現率を計算
        $totalRate = $spawns->sum('spawn_rate');
        
        if ($totalRate <= 0) {
            return null;
        }

        // ランダム値生成
        $random = mt_rand(1, intval($totalRate * 100)) / 100;
        
        // 確率に基づいて選択
        $cumulative = 0;
        foreach ($spawns as $spawn) {
            $cumulative += $spawn->spawn_rate;
            if ($random <= $cumulative) {
                return $spawn->monster;
            }
        }

        // フォールバック: 最初のモンスター
        return $spawns->first()->monster;
    }

    /**
     * ロケーションの出現設定統計を取得
     */
    public static function getLocationSpawnStats(string $locationId): array
    {
        $spawns = self::with('monster')->forLocation($locationId)->get();
        
        return [
            'total_monsters' => $spawns->count(),
            'active_monsters' => $spawns->where('is_active', true)->count(),
            'total_spawn_rate' => $spawns->sum('spawn_rate'),
            'level_range' => [
                'min' => $spawns->whereNotNull('min_level')->min('min_level'),
                'max' => $spawns->whereNotNull('max_level')->max('max_level'),
            ],
            'avg_priority' => $spawns->avg('priority'),
        ];
    }
}