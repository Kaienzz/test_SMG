<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpawnList extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'is_active',
        'tags',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tags' => 'array',
    ];

    // Relationships
    public function monsterSpawns()
    {
        return $this->hasMany(MonsterSpawn::class, 'spawn_list_id');
    }

    public function monsters()
    {
        return $this->belongsToMany(Monster::class, 'monster_spawns', 'spawn_list_id', 'monster_id')
                    ->withPivot(['spawn_rate', 'priority', 'min_level', 'max_level', 'is_active'])
                    ->withTimestamps();
    }

    public function gameLocations()
    {
        return $this->hasMany(Route::class, 'spawn_list_id');
    }

    // Active monsters in this spawn list
    public function activeMonsters()
    {
        return $this->monsters()->wherePivot('is_active', true)->orderByPivot('priority');
    }
}
