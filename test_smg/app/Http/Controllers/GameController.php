<?php

namespace App\Http\Controllers;

use App\Models\GameState;
use App\Models\Character;
use App\Models\ActiveEffect;
use App\Models\Monster;
use App\Services\MovementService;
use App\Services\DummyDataService;
use App\Services\BattleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        // セッションの初期化（初回アクセス時）
        if (!session()->has('location_type')) {
            session([
                'location_type' => 'town',
                'location_id' => 'town_a',
                'game_position' => 0,
                'character_sp' => 30,
            ]);
        }
        
        $character = DummyDataService::getCharacter(1);
        $playerData = DummyDataService::getPlayer();
        $currentLocation = DummyDataService::getCurrentLocation();
        $nextLocation = DummyDataService::getNextLocation();
        
        // プレイヤーオブジェクトにメソッドを追加
        $player = (object) array_merge($playerData, [
            'isInTown' => function() use ($playerData) {
                return $playerData['current_location_type'] === 'town';
            },
            'isOnRoad' => function() use ($playerData) {
                return $playerData['current_location_type'] === 'road';
            },
            'getCharacter' => function() use ($character) {
                return (object) $character;
            }
        ]);
        
        // ダミー移動情報
        $movementInfo = [
            'base_dice_count' => 2,
            'extra_dice' => 1,
            'total_dice_count' => 3,
            'dice_bonus' => 3,
            'movement_multiplier' => 1.0,
            'special_effects' => [],
            'min_possible_movement' => 6,
            'max_possible_movement' => 21,
        ];
        
        return view('game.index', [
            'character' => (object) $character,
            'player' => $player,
            'currentLocation' => (object) $currentLocation,
            'nextLocation' => $nextLocation,
            'movementInfo' => $movementInfo,
        ]);
    }
    
    public function rollDice(Request $request): JsonResponse
    {
        // ダミーサイコロ結果
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);
        $dice3 = rand(1, 6); // 追加サイコロ
        $diceRolls = [$dice1, $dice2, $dice3];
        $baseTotal = $dice1 + $dice2 + $dice3;
        $bonus = 3; // 飛脚術効果
        $total = $baseTotal + $bonus;
        
        return response()->json([
            'dice_rolls' => $diceRolls,
            'dice_count' => 3,
            'dice1' => $dice1,
            'dice2' => $dice2,
            'base_total' => $baseTotal,
            'bonus' => $bonus,
            'total' => $total,
            'final_movement' => $total,
            'movement_effects' => [
                'dice_bonus' => 3,
                'extra_dice' => 1,
                'movement_multiplier' => 1.0,
            ],
            'rolled_at' => now()->toISOString()
        ]);
    }
    
    public function move(Request $request): JsonResponse
    {
        $request->validate([
            'direction' => 'required|in:left,right,forward,backward',
            'steps' => 'required|integer|min:1|max:30'
        ]);
        
        $direction = $request->input('direction');
        $steps = $request->input('steps');
        
        $currentPosition = session('game_position', 50);
        $currentLocation = session('location_type', 'road');
        
        if ($currentLocation !== 'road') {
            return response()->json([
                'success' => false,
                'message' => 'プレイヤーは道路上にいません'
            ], 400);
        }
        
        // 左右の移動を前後の移動に変換
        if ($direction === 'left') {
            $direction = 'backward';
        } elseif ($direction === 'right') {
            $direction = 'forward';
        }
        
        $moveAmount = $direction === 'forward' ? $steps : -$steps;
        $newPosition = max(0, min(100, $currentPosition + $moveAmount));
        
        // セッションに保存
        session(['game_position' => $newPosition]);
        
        $currentLocation = DummyDataService::getCurrentLocation();
        $nextLocation = DummyDataService::getNextLocation();
        
        // エンカウント判定
        $currentLocationId = session('location_id', 'road_1');
        $encounteredMonster = null;
        
        if (session('location_type') === 'road') {
            $encounteredMonster = BattleService::checkEncounter($currentLocationId);
        }
        
        $response = [
            'success' => true,
            'position' => $newPosition,
            'steps_moved' => abs($newPosition - $currentPosition),
            'currentLocation' => $currentLocation,
            'nextLocation' => $nextLocation,
            'canMoveToNext' => $newPosition >= 100,
            'canMoveToPrevious' => $newPosition <= 0,
        ];
        
        if ($encounteredMonster) {
            $response['encounter'] = true;
            $response['monster'] = $encounteredMonster;
        }
        
        return response()->json($response);
    }
    
    public function moveToNext(Request $request): JsonResponse
    {
        $currentPosition = session('game_position', 0);
        $nextLocation = DummyDataService::getNextLocation();
        
        if ($nextLocation) {
            $newPosition = DummyDataService::calculateNewPosition($nextLocation, $currentPosition);
            
            session([
                'location_type' => $nextLocation['type'],
                'location_id' => $nextLocation['id'],
                'game_position' => $newPosition,
            ]);
        }
        
        $currentLocation = DummyDataService::getCurrentLocation();
        $newNextLocation = DummyDataService::getNextLocation();
        $position = session('game_position', 0);
        $locationType = session('location_type', 'town');
        
        return response()->json([
            'currentLocation' => $currentLocation,
            'position' => $position,
            'nextLocation' => $newNextLocation,
            'location_type' => $locationType,
            'success' => true
        ]);
    }
    
    public function reset(): JsonResponse
    {
        session([
            'location_type' => 'town',
            'location_id' => 'town_a',
            'game_position' => 0,
        ]);
        
        $currentLocation = DummyDataService::getCurrentLocation();
        $nextLocation = DummyDataService::getNextLocation();
        
        return response()->json([
            'success' => true,
            'currentLocation' => $currentLocation,
            'position' => 0,
            'nextLocation' => $nextLocation,
            'message' => 'ゲームがリセットされました'
        ]);
    }
    
    private function getOrCreateGameState(): GameState
    {
        return new GameState([
            'player_name' => 'Player',
            'character_id' => 1,
            'current_location_type' => 'town',
            'current_location_id' => 'town_a',
            'position' => 0,
            'game_data' => []
        ]);
    }

    private function processTurnEffects(int $characterId): void
    {
        $character = Character::find($characterId);
        if (!$character) {
            return;
        }

        $character->restoreSP(2);
        $character->save();

        $activeEffects = ActiveEffect::where('character_id', $characterId)
                                   ->where('is_active', true)
                                   ->where('remaining_duration', '>', 0)
                                   ->get();

        foreach ($activeEffects as $effect) {
            $effect->decreaseDuration(1);
        }
    }
}