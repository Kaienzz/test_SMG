<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Town extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function getDisplayName(): string
    {
        return $this->name;
    }
    
    public static function getAllTowns(): array
    {
        return [
            ['id' => 'town_a', 'name' => 'A町', 'description' => '冒険の出発点となる静かな町'],
            ['id' => 'town_b', 'name' => 'B町', 'description' => '目的地となる賑やかな町'],
        ];
    }
    
    public static function getTownById(string $townId): ?array
    {
        $towns = self::getAllTowns();
        foreach ($towns as $town) {
            if ($town['id'] === $townId) {
                return $town;
            }
        }
        return null;
    }
    
    public function getConnectedRoads(string $townId): array
    {
        if ($townId === 'town_a') {
            return [['type' => 'road', 'id' => 'road_1', 'name' => '道路1']];
        } elseif ($townId === 'town_b') {
            return [['type' => 'road', 'id' => 'road_3', 'name' => '道路3']];
        }
        
        return [];
    }
}