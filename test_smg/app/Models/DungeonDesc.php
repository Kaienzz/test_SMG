<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DungeonDesc extends Model
{
    protected $table = 'dungeons_desc';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'dungeon_id',
        'dungeon_name',
        'dungeon_desc',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * このダンジョンに属するフロア（Route）
     */
    public function floors()
    {
        return $this->hasMany(Route::class, 'dungeon_id', 'dungeon_id')
                    ->where('category', 'dungeon')
                    ->orderBy('name');
    }
    
    /**
     * アクティブなフロア
     */
    public function activeFloors()
    {
        return $this->floors()->where('is_active', true);
    }
    
    /**
     * スコープ: アクティブなダンジョンのみ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
