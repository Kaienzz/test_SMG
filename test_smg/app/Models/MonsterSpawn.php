<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonsterSpawn extends Model
{
    protected $fillable = [
        'spawn_list_id',
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
    public function spawnList()
    {
        return $this->belongsTo(SpawnList::class, 'spawn_list_id');
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
}
