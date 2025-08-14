<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Road extends Model
{
    protected $fillable = [
        'name',
        'order',
        'from_town',
        'to_town',
        'description',
    ];

    public function getDisplayName(): string
    {
        return $this->name;
    }
    
    public function isStart(int $position): bool
    {
        return $position === 0;
    }
    
    public function isEnd(int $position): bool
    {
        return $position === 100;
    }
    
    public function getPreviousLocation(): ?array
    {
        if ($this->order === 1) {
            return ['type' => 'town', 'id' => 'town_a', 'name' => 'A町'];
        }
        
        return ['type' => 'road', 'id' => 'road_' . ($this->order - 1), 'name' => '道路' . ($this->order - 1)];
    }
    
    public function getNextLocation(): ?array
    {
        if ($this->order === 3) {
            return ['type' => 'town', 'id' => 'town_b', 'name' => 'B町'];
        }
        
        return ['type' => 'road', 'id' => 'road_' . ($this->order + 1), 'name' => '道路' . ($this->order + 1)];
    }
    
    public static function getAllRoads(): array
    {
        return [
            ['id' => 'road_1', 'name' => '道路1', 'order' => 1, 'from_town' => 'town_a', 'to_town' => 'road_2'],
            ['id' => 'road_2', 'name' => '道路2', 'order' => 2, 'from_town' => 'road_1', 'to_town' => 'road_3'],
            ['id' => 'road_3', 'name' => '道路3', 'order' => 3, 'from_town' => 'road_2', 'to_town' => 'town_b'],
        ];
    }
    
    public static function getRoadById(string $roadId): ?array
    {
        $roads = self::getAllRoads();
        foreach ($roads as $road) {
            if ($road['id'] === $roadId) {
                return $road;
            }
        }
        return null;
    }
}