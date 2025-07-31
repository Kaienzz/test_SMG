<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Player extends Model
{
    protected $fillable = [
        'name',
        'current_location_type',
        'current_location_id',
        'position',
        'character_id',
    ];

    protected $casts = [
        'character_id' => 'integer',
    ];

    public function character(): HasOne
    {
        return $this->hasOne(Character::class, 'id', 'character_id');
    }

    public function getCharacter(): Character
    {
        if ($this->character_id && $this->character) {
            return $this->character;
        }
        
        return Character::createNewCharacter($this->name ?? '冒険者');
    }

    public function isInTown(): bool
    {
        return $this->current_location_type === 'town';
    }
    
    public function isOnRoad(): bool
    {
        return $this->current_location_type === 'road';
    }
    
    public function getCurrentLocation()
    {
        if ($this->isInTown()) {
            return new Town(['name' => $this->current_location_id === 'town_a' ? 'A町' : 'B町']);
        }
        
        $roadNames = ['道路1', '道路2', '道路3'];
        $roadIndex = (int) str_replace('road_', '', $this->current_location_id) - 1;
        
        return new Road([
            'name' => $roadNames[$roadIndex] ?? '道路1',
            'order' => $roadIndex + 1
        ]);
    }
    
    public function move(int $steps): void
    {
        if ($this->isOnRoad()) {
            $this->position = max(0, min(100, $this->position + $steps));
        }
    }

    public function getStatusSummary(): array
    {
        $character = $this->getCharacter();
        $location = $this->getCurrentLocation();
        
        return [
            'player_info' => [
                'name' => $this->name,
                'location' => $location->name ?? 'Unknown',
                'position' => $this->position,
                'is_in_town' => $this->isInTown(),
                'is_on_road' => $this->isOnRoad(),
            ],
            'character_stats' => $character->getStatusSummary(),
        ];
    }
}