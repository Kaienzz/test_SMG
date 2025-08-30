<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'routes';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'category',
        'dungeon_id',
        'length',
        'difficulty',
        'default_movement_axis',
        'encounter_rate',
        'spawn_list_id',
        'spawn_tags',
        'spawn_description',
        'is_active',
        'type',
        'services',
        'special_actions',
        'branches',
        'floors',
        'min_level',
        'max_level',
        'boss',
    ];

    protected $casts = [
        'length' => 'integer',
        'encounter_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'spawn_tags' => 'array',
        'services' => 'array',
        'special_actions' => 'array',
        'branches' => 'array',
        'floors' => 'integer',
        'min_level' => 'integer',
        'max_level' => 'integer',
    ];

    // Relationships
    public function spawnList()
    {
        return $this->belongsTo(SpawnList::class, 'spawn_list_id');
    }

    /**
     * 新統合モンスタースポーンリスト（統合後のメインリレーション）
     */
    public function monsterSpawns()
    {
        return $this->hasMany(MonsterSpawnList::class, 'location_id');
    }

    /**
     * アクティブなモンスタースポーン
     */
    public function activeMonsterSpawns()
    {
        return $this->monsterSpawns()->active()->byPriority();
    }

    /**
     * スポーン可能なモンスター（Many-to-Many through MonsterSpawnList）
     */
    public function spawnableMonsters()
    {
        return $this->belongsToMany(Monster::class, 'monster_spawn_lists', 'location_id', 'monster_id')
                    ->withPivot(['spawn_rate', 'priority', 'min_level', 'max_level', 'is_active'])
                    ->withTimestamps();
    }

    public function sourceConnections()
    {
        return $this->hasMany(RouteConnection::class, 'source_location_id');
    }

    public function targetConnections()
    {
        return $this->hasMany(RouteConnection::class, 'target_location_id');
    }

    public function connections()
    {
        return $this->sourceConnections()->with(['targetLocation']);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeRoads($query)
    {
        return $query->where('category', 'road');
    }

    public function scopeTowns($query)
    {
        return $query->where('category', 'town');
    }

    public function scopeDungeons($query)
    {
        return $query->where('category', 'dungeon');
    }

    // Accessors
    public function getIsTownAttribute(): bool
    {
        return $this->category === 'town';
    }

    public function getIsRoadAttribute(): bool
    {
        return $this->category === 'road';
    }

    public function getIsDungeonAttribute(): bool
    {
        return $this->category === 'dungeon';
    }

    // 新統合システム用メソッド
    
    /**
     * スポーンタグをカンマ区切り文字列で取得
     */
    public function getSpawnTagsStringAttribute(): string
    {
        return $this->spawn_tags ? implode(', ', $this->spawn_tags) : '';
    }

    /**
     * モンスタースポーンが設定されているかチェック
     */
    public function hasMonsterSpawns(): bool
    {
        return $this->monsterSpawns()->exists();
    }

    /**
     * レベル適用のアクティブモンスター取得
     */
    public function getSpawnableMonstersForLevel(?int $level = null)
    {
        $query = $this->activeMonsterSpawns()->with('monster');
        
        if ($level !== null) {
            $query->forLevel($level);
        }
        
        return $query->get();
    }

    /**
     * ランダムモンスター選択
     */
    public function getRandomMonster(?int $playerLevel = null): ?Monster
    {
        return MonsterSpawnList::getRandomMonsterForLocation($this->id, $playerLevel);
    }

    /**
     * スポーン統計情報取得
     */
    public function getSpawnStats(): array
    {
        return MonsterSpawnList::getLocationSpawnStats($this->id);
    }

    /**
     * スポーン設定の有効性チェック
     */
    public function validateSpawnConfiguration(): array
    {
        $spawns = $this->monsterSpawns;
        $issues = [];
        
        // 出現率の合計チェック
        $totalRate = $spawns->sum('spawn_rate');
        if ($totalRate > 1.0) {
            $issues[] = '出現率の合計が100%を超えています';
        }
        
        // アクティブなスポーンが存在するかチェック
        $activeCount = $spawns->where('is_active', true)->count();
        if ($activeCount === 0) {
            $issues[] = 'アクティブなモンスタースポーンがありません';
        }
        
        // 優先度の重複チェック
        $priorities = $spawns->pluck('priority')->toArray();
        if (count($priorities) !== count(array_unique($priorities))) {
            $issues[] = '優先度に重複があります';
        }
        
        return $issues;
    }

    /**
     * DungeonDescとのリレーション
     */
    public function dungeonDesc()
    {
        return $this->belongsTo(DungeonDesc::class, 'dungeon_id', 'dungeon_id');
    }

    /**
     * 同じダンジョンの他フロア
     */
    public function siblingFloors()
    {
        return $this->where('dungeon_id', $this->dungeon_id)
                    ->where('id', '!=', $this->id)
                    ->where('category', 'dungeon');
    }

    /**
     * ダンジョンフロア判定
     */
    public function isDungeonFloor(): bool
    {
        return $this->category === 'dungeon' && !empty($this->dungeon_id);
    }

    // ==========================================
    // 採集システム関連メソッド
    // ==========================================

    /**
     * 採集マッピングリレーション
     */
    public function gatheringMappings()
    {
        return $this->hasMany(GatheringMapping::class, 'route_id', 'id')
                    ->where('is_active', true)
                    ->orderBy('required_skill_level')
                    ->orderBy('success_rate', 'desc');
    }

    /**
     * すべての採集マッピング（非アクティブ含む）
     */
    public function allGatheringMappings()
    {
        return $this->hasMany(GatheringMapping::class, 'route_id', 'id')
                    ->orderBy('required_skill_level')
                    ->orderBy('success_rate', 'desc');
    }

    /**
     * アクティブな採集アイテム（Many-to-Many through GatheringMapping）
     */
    public function gatheringItems()
    {
        return $this->belongsToMany(Item::class, 'gathering_mappings', 'route_id', 'item_id')
                    ->withPivot([
                        'required_skill_level',
                        'success_rate', 
                        'quantity_min',
                        'quantity_max',
                        'is_active'
                    ])
                    ->wherePivot('is_active', true)
                    ->withTimestamps();
    }

    /**
     * 採集可能判定（Road・Dungeon対応）
     */
    public function hasGatheringItems(): bool
    {
        return in_array($this->category, ['road', 'dungeon']) 
               && $this->gatheringMappings()->exists();
    }

    /**
     * 採集可能ルート判定（カテゴリベース）
     */
    public function isGatheringEligible(): bool
    {
        return in_array($this->category, ['road', 'dungeon']);
    }

    /**
     * プレイヤーレベルに適した採集アイテム取得
     */
    public function getGatheringItemsForSkillLevel(int $skillLevel, ?int $playerLevel = null)
    {
        $query = $this->gatheringMappings()
                      ->with('item')
                      ->forSkillLevel($skillLevel);

        // ダンジョンの場合はプレイヤーレベル制限も考慮
        if ($this->category === 'dungeon' && $playerLevel !== null && $this->min_level) {
            // プレイヤーレベルが足りない場合は空のコレクションを返す
            if ($playerLevel < $this->min_level) {
                return collect();
            }
        }

        return $query->get();
    }

    /**
     * 採集統計情報を取得
     */
    public function getGatheringStats(): array
    {
        $allMappings = $this->allGatheringMappings;
        $activeMappings = $allMappings->where('is_active', true);

        return [
            'total_items' => $allMappings->count(),
            'active_items' => $activeMappings->count(),
            'inactive_items' => $allMappings->where('is_active', false)->count(),
            'skill_level_range' => [
                'min' => $allMappings->min('required_skill_level') ?? 0,
                'max' => $allMappings->max('required_skill_level') ?? 0,
            ],
            'success_rate_range' => [
                'min' => $allMappings->min('success_rate') ?? 0,
                'max' => $allMappings->max('success_rate') ?? 0,
                'avg' => round($allMappings->avg('success_rate') ?? 0, 1),
            ],
            'quantity_range' => [
                'min_total' => $allMappings->min('quantity_min') ?? 0,
                'max_total' => $allMappings->max('quantity_max') ?? 0,
            ],
            'environment' => $this->category === 'road' ? '道路' : ($this->category === 'dungeon' ? 'ダンジョン' : '不明'),
            'has_level_requirement' => $this->category === 'dungeon' && !empty($this->min_level),
            'min_player_level' => $this->min_level,
        ];
    }

    /**
     * 採集設定の検証
     */
    public function validateGatheringConfiguration(): array
    {
        $issues = [];
        $mappings = $this->allGatheringMappings;

        // 基本チェック
        if (!$this->isGatheringEligible()) {
            $issues[] = 'このルートタイプ（' . $this->category . '）は採集に対応していません';
            return $issues;
        }

        // アクティブな設定があるかチェック
        $activeCount = $mappings->where('is_active', true)->count();
        if ($activeCount === 0 && $mappings->count() > 0) {
            $issues[] = 'アクティブな採集設定がありません';
        }

        // 重複チェック（同じアイテムが複数設定されていないか）
        $itemIds = $mappings->pluck('item_id');
        if ($itemIds->count() !== $itemIds->unique()->count()) {
            $issues[] = '同じアイテムが重複して設定されています';
        }

        // スキルレベル要件の妥当性チェック
        $invalidSkillLevels = $mappings->where('required_skill_level', '<=', 0)
                                      ->orWhere('required_skill_level', '>', 100);
        if ($invalidSkillLevels->count() > 0) {
            $issues[] = '無効なスキルレベル要件があります（1-100の範囲外）';
        }

        // 成功率の妥当性チェック  
        $invalidSuccessRates = $mappings->where('success_rate', '<=', 0)
                                       ->orWhere('success_rate', '>', 100);
        if ($invalidSuccessRates->count() > 0) {
            $issues[] = '無効な成功率があります（1-100の範囲外）';
        }

        // 数量設定の妥当性チェック
        $invalidQuantities = $mappings->where(function($mapping) {
            return $mapping->quantity_min <= 0 || 
                   $mapping->quantity_max <= 0 || 
                   $mapping->quantity_min > $mapping->quantity_max;
        });
        if ($invalidQuantities->count() > 0) {
            $issues[] = '無効な数量設定があります';
        }

        return $issues;
    }

    /**
     * プレイヤー用採集情報取得（API用）
     */
    public function getPlayerGatheringInfo(int $skillLevel, ?int $playerLevel = null): array
    {
        if (!$this->hasGatheringItems()) {
            return [
                'can_gather' => false,
                'reason' => 'このエリアでは採集できません',
                'items' => [],
            ];
        }

        $availableItems = $this->getGatheringItemsForSkillLevel($skillLevel, $playerLevel);
        
        if ($availableItems->isEmpty()) {
            $reason = $this->category === 'dungeon' && $playerLevel && $this->min_level && $playerLevel < $this->min_level
                ? "プレイヤーレベルが不足しています（必要Lv.{$this->min_level}）"
                : 'スキルレベルが不足しています';
                
            return [
                'can_gather' => false,
                'reason' => $reason,
                'items' => [],
            ];
        }

        return [
            'can_gather' => true,
            'reason' => null,
            'environment' => $this->category === 'road' ? '道路' : 'ダンジョン',
            'location_name' => $this->name,
            'available_items_count' => $availableItems->count(),
            'items' => $availableItems->map(function($mapping) use ($skillLevel) {
                return $mapping->getGatheringInfo($skillLevel);
            })->toArray(),
        ];
    }
}