<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\MovementService;

class GameState extends Model
{
    protected $fillable = [
        'user_id',
        'player_name',
        'character_id',
        'current_location_type',
        'current_location_id',
        'position',
        'game_data',
        'session_duration',
        'actions_count',
        'last_action_at',
    ];

    protected $casts = [
        'game_data' => 'array',
        'last_action_at' => 'datetime',
        'session_duration' => 'integer',
        'actions_count' => 'integer',
    ];

    public function getPlayer(): Player
    {
        return new Player([
            'name' => $this->player_name,
            'character_id' => $this->character_id,
            'current_location_type' => $this->current_location_type,
            'current_location_id' => $this->current_location_id,
            'position' => $this->position,
        ]);
    }
    
    public function updateFromPlayer(Player $player): void
    {
        $this->current_location_type = $player->current_location_type;
        $this->current_location_id = $player->current_location_id;
        $this->position = $player->position;
    }
    
    public function rollDice(): array
    {
        $movementService = new MovementService();
        $result = $movementService->rollDiceWithEffects($this->character_id);
        
        $diceData = $result['dice'];
        $movementData = $result['movement'];
        
        return [
            'dice_rolls' => $diceData['dice_rolls'],
            'dice_count' => $diceData['dice_count'],
            'dice1' => $diceData['dice_rolls'][0] ?? 0,
            'dice2' => $diceData['dice_rolls'][1] ?? 0,
            'base_total' => $diceData['base_total'],
            'bonus' => $diceData['bonus'],
            'total' => $diceData['total'],
            'final_movement' => $result['final_steps'],
            'movement_effects' => $movementData['effects_applied'],
            'rolled_at' => $diceData['rolled_at']
        ];
    }
    
    public function movePlayerOnRoad(int $steps, string $direction = 'forward'): array
    {
        $player = $this->getPlayer();
        
        if (!$player->isOnRoad()) {
            return ['success' => false, 'message' => 'プレイヤーは道路上にいません'];
        }
        
        $moveAmount = $direction === 'forward' ? $steps : -$steps;
        $newPosition = max(0, min(100, $player->position + $moveAmount));
        
        $this->position = $newPosition;
        $this->save();
        
        return [
            'success' => true,
            'new_position' => $newPosition,
            'can_move_to_next' => $newPosition >= 100,
            'can_move_to_previous' => $newPosition <= 0,
            'steps_moved' => abs($newPosition - $player->position)
        ];
    }
    
    public function getNextLocation(): ?array
    {
        $player = $this->getPlayer();
        
        if ($player->isInTown()) {
            if ($player->current_location_id === 'town_a') {
                return ['type' => 'road', 'id' => 'road_1', 'name' => '道路1'];
            } elseif ($player->current_location_id === 'town_b') {
                return ['type' => 'road', 'id' => 'road_3', 'name' => '道路3'];
            }
        } elseif ($player->isOnRoad()) {
            $roadNumber = (int) str_replace('road_', '', $player->current_location_id);
            
            if ($player->position <= 0) {
                if ($roadNumber === 1) {
                    return ['type' => 'town', 'id' => 'town_a', 'name' => 'A町'];
                } else {
                    return ['type' => 'road', 'id' => 'road_' . ($roadNumber - 1), 'name' => '道路' . ($roadNumber - 1)];
                }
            } elseif ($player->position >= 100) {
                if ($roadNumber === 3) {
                    return ['type' => 'town', 'id' => 'town_b', 'name' => 'B町'];
                } else {
                    return ['type' => 'road', 'id' => 'road_' . ($roadNumber + 1), 'name' => '道路' . ($roadNumber + 1)];
                }
            }
        }
        
        return null;
    }
    
    public function moveToNextLocation(): void
    {
        $nextLocation = $this->getNextLocation();
        
        if ($nextLocation) {
            $this->current_location_type = $nextLocation['type'];
            $this->current_location_id = $nextLocation['id'];
            $this->position = $nextLocation['type'] === 'road' ? 50 : 0;
        }
    }

    // User relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    // Game analysis methods
    public static function getUserAnalytics(int $userId): array
    {
        $states = self::where('user_id', $userId)->get();
        $totalSessions = $states->count();
        
        if ($totalSessions === 0) {
            return [
                'total_sessions' => 0,
                'total_playtime' => 0,
                'average_session_duration' => 0,
                'total_actions' => 0,
                'actions_per_session' => 0,
                'most_visited_locations' => [],
                'activity_pattern' => [],
            ];
        }

        $totalPlaytime = $states->sum('session_duration');
        $totalActions = $states->sum('actions_count');
        
        // Location analysis
        $locationFrequency = $states->groupBy('current_location_id')
                                  ->map(fn($group) => $group->count())
                                  ->sortDesc()
                                  ->take(5);

        // Activity pattern (by hour)
        $activityPattern = $states->filter(fn($state) => $state->last_action_at)
                                 ->groupBy(fn($state) => $state->last_action_at->format('H'))
                                 ->map(fn($group) => $group->count())
                                 ->sortKeys();

        return [
            'total_sessions' => $totalSessions,
            'total_playtime' => $totalPlaytime,
            'average_session_duration' => round($totalPlaytime / $totalSessions, 2),
            'total_actions' => $totalActions,
            'actions_per_session' => round($totalActions / $totalSessions, 2),
            'most_visited_locations' => $locationFrequency->toArray(),
            'activity_pattern' => $activityPattern->toArray(),
            'last_activity' => $states->max('last_action_at'),
        ];
    }

    public static function getGlobalAnalytics(): array
    {
        $totalUsers = self::distinct('user_id')->count();
        $totalSessions = self::count();
        $activeUsers = self::where('last_action_at', '>=', now()->subDays(7))->distinct('user_id')->count();
        
        return [
            'total_users' => $totalUsers,
            'active_users_7d' => $activeUsers,
            'total_sessions' => $totalSessions,
            'average_sessions_per_user' => $totalUsers > 0 ? round($totalSessions / $totalUsers, 2) : 0,
            'total_playtime' => self::sum('session_duration'),
            'total_actions' => self::sum('actions_count'),
        ];
    }

    // Update activity tracking
    public function trackAction(string $actionType = 'general'): void
    {
        $this->increment('actions_count');
        $this->update(['last_action_at' => now()]);
        
        // Update session duration if it's a continuing session
        if ($this->created_at && $this->last_action_at) {
            $this->update([
                'session_duration' => $this->created_at->diffInMinutes($this->last_action_at)
            ]);
        }
    }
}