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
}