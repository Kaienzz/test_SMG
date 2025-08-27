<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatheringMapping extends Model
{
    protected $fillable = [
        'route_id',
        'item_id', 
        'required_skill_level',
        'success_rate',
        'quantity_min',
        'quantity_max',
        'is_active',
    ];

    protected $casts = [
        'required_skill_level' => 'integer',
        'success_rate' => 'integer',
        'quantity_min' => 'integer',
        'quantity_max' => 'integer',
        'is_active' => 'boolean',
    ];

    // リレーション
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // スコープ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSkillLevel($query, int $skillLevel)
    {
        return $query->where('required_skill_level', '<=', $skillLevel);
    }

    public function scopeForRoute($query, string $routeId)
    {
        return $query->where('route_id', $routeId);
    }

    public function scopeByEnvironment($query, string $environment)
    {
        return $query->whereHas('route', function($q) use ($environment) {
            if ($environment === 'road') {
                $q->where('category', 'road');
            } elseif ($environment === 'dungeon') {
                $q->where('category', 'dungeon');
            }
        });
    }

    // ビジネスロジックメソッド
    
    /**
     * プレイヤーのスキルレベルに基づいて実際の成功率を計算
     */
    public function calculateSuccessRate(int $playerSkillLevel): int
    {
        $baseRate = $this->success_rate;
        $skillBonus = max(0, ($playerSkillLevel - $this->required_skill_level) * 5);
        $finalRate = $baseRate + $skillBonus;
        
        return min(100, (int)$finalRate);
    }

    /**
     * ダンジョン採集可能判定（プレイヤーレベルチェック）
     */
    public function canGatherInDungeon(int $playerLevel): bool
    {
        $route = $this->route;
        if ($route && $route->min_level && $playerLevel < $route->min_level) {
            return false;
        }
        
        return true;
    }

    /**
     * プレイヤーが採集可能かチェック
     */
    public function canPlayerGather(int $skillLevel, ?int $playerLevel = null): bool
    {
        // スキルレベル要件チェック
        if ($skillLevel < $this->required_skill_level) {
            return false;
        }

        // アクティブ状態チェック
        if (!$this->is_active) {
            return false;
        }

        // ダンジョンの場合はプレイヤーレベルもチェック
        if ($this->route && $this->route->category === 'dungeon' && $playerLevel !== null) {
            return $this->canGatherInDungeon($playerLevel);
        }

        return true;
    }

    /**
     * 採集環境を取得
     */
    public function getGatheringEnvironment(): string
    {
        if (!$this->route) {
            return 'unknown';
        }

        return match($this->route->category) {
            'road' => 'road',
            'dungeon' => 'dungeon',
            default => 'unknown'
        };
    }

    /**
     * 採集環境の表示名を取得
     */
    public function getGatheringEnvironmentName(): string
    {
        return match($this->getGatheringEnvironment()) {
            'road' => '道路',
            'dungeon' => 'ダンジョン',
            default => '不明'
        };
    }

    /**
     * 数量範囲の文字列表現を取得
     */
    public function getQuantityRangeString(): string
    {
        if ($this->quantity_min === $this->quantity_max) {
            return (string)$this->quantity_min;
        }
        
        return "{$this->quantity_min}-{$this->quantity_max}";
    }

    /**
     * ランダムな採集数量を生成
     */
    public function generateRandomQuantity(): int
    {
        return mt_rand($this->quantity_min, $this->quantity_max);
    }

    /**
     * 採集情報の配列を取得（API用）
     */
    public function getGatheringInfo(?int $playerSkillLevel = null): array
    {
        $info = [
            'id' => $this->id,
            'route_id' => $this->route_id,
            'route_name' => $this->route?->name ?? '不明なルート',
            'item_id' => $this->item_id,
            'item_name' => $this->item?->name ?? '不明なアイテム',
            'item_category' => $this->item?->getCategoryName() ?? '不明',
            'required_skill_level' => $this->required_skill_level,
            'base_success_rate' => $this->success_rate,
            'quantity_range' => $this->getQuantityRangeString(),
            'quantity_min' => $this->quantity_min,
            'quantity_max' => $this->quantity_max,
            'is_active' => $this->is_active,
            'gathering_environment' => $this->getGatheringEnvironment(),
            'gathering_environment_name' => $this->getGatheringEnvironmentName(),
        ];

        // プレイヤースキルレベルが提供されている場合の追加情報
        if ($playerSkillLevel !== null) {
            $info['actual_success_rate'] = $this->calculateSuccessRate($playerSkillLevel);
            $info['can_gather'] = $this->canPlayerGather($playerSkillLevel);
        }

        return $info;
    }
}
