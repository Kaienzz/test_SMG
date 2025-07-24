<?php

namespace App\Http\Controllers;

use App\Models\GameState;
use App\Models\Character;
use App\Models\ActiveEffect;
use App\Models\Monster;
use App\Services\MovementService;
use App\Services\BattleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    use \App\Http\Controllers\Traits\HasCharacter;
    public function index(): View
    {
        // Database-First: 認証ユーザーのキャラクターを取得または作成
        $character = $this->getOrCreateCharacter();
        
        // セッション→DB移行: 既存セッションデータがあればDBに反映
        $this->migrateSessionToDatabase($character);
        
        // Database-First: Characterモデルから位置情報を取得
        $playerData = [
            'name' => $character->name ?? 'プレイヤー',
            'current_location_type' => $character->location_type ?? 'town',
            'current_location_id' => $character->location_id ?? 'town_a',
            'position' => $character->game_position ?? 0,
        ];
        
        $currentLocation = $this->getCurrentLocationFromCharacter($character);
        $nextLocation = $this->getNextLocationFromCharacter($character);
        
        // プレイヤーオブジェクトにメソッドを追加
        $player = (object) array_merge($playerData, [
            'position' => $character->game_position ?? 0,  // 追加: position プロパティ
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
            'character' => $character,
            'player' => $this->createPlayerFromCharacter($character),
            'currentLocation' => (object) $currentLocation,
            'nextLocation' => $nextLocation,
            'movementInfo' => $movementInfo,
        ]);
    }
    
    /**
     * セッションデータをデータベースに移行する安全なブリッジメソッド
     */
    private function migrateSessionToDatabase(Character $character): void
    {
        $userId = Auth::id();
        $sessionKey = "user_{$userId}_game_data";
        
        // セッションデータが存在する場合はDBに移行
        if (session()->has($sessionKey) || session()->has('location_type')) {
            $sessionData = session($sessionKey) ?? [];
            
            // セッションからlocation情報を取得（フォールバック付き）
            $locationType = $sessionData['location_type'] ?? session('location_type', $character->location_type ?? 'town');
            $locationId = $sessionData['location_id'] ?? session('location_id', $character->location_id ?? 'town_a');
            $gamePosition = $sessionData['game_position'] ?? session('game_position', $character->game_position ?? 0);
            
            // DBのlocation情報が初期値の場合のみセッションデータで更新
            if (!$character->location_type || $character->location_type === 'town') {
                $character->updateLocation($locationType, $locationId, $gamePosition);
            }
            
            // リソース情報も移行（SP, Gold）
            if (isset($sessionData['character_sp']) && $character->sp !== $sessionData['character_sp']) {
                $character->update(['sp' => $sessionData['character_sp']]);
            }
            if (isset($sessionData['character_gold']) && $character->gold !== $sessionData['character_gold']) {
                $character->update(['gold' => $sessionData['character_gold']]);
            }
            
            // セッション個別キーも移行
            if (session()->has('character_sp') && $character->sp !== session('character_sp')) {
                $character->update(['sp' => session('character_sp')]);
            }
            if (session()->has('character_gold') && $character->gold !== session('character_gold')) {
                $character->update(['gold' => session('character_gold')]);
            }
            
            // セッションデータを削除（移行完了）
            session()->forget([
                $sessionKey, 
                'location_type', 'location_id', 'game_position',
                'character_sp', 'character_gold'
            ]);
        }
    }
    
    /**
     * CharacterモデルからPlayer風オブジェクトを作成
     */
    private function createPlayerFromCharacter(Character $character): object
    {
        return (object) [
            'current_location_type' => $character->location_type,
            'current_location_id' => $character->location_id,
            'game_position' => $character->game_position,
            'position' => $character->game_position ?? 0,  // 追加: position プロパティ
            'isInTown' => function() use ($character) {
                return $character->location_type === 'town';
            },
            'isOnRoad' => function() use ($character) {
                return $character->location_type === 'road';
            },
            'getCharacter' => function() use ($character) {
                return $character;
            }
        ];
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
        
        // Database-First: Characterに保存
        $character = $this->getOrCreateCharacter();
        $character->update(['game_position' => $newPosition]);
        
        $currentLocation = $this->getCurrentLocationFromCharacter($character);
        $nextLocation = $this->getNextLocationFromCharacter($character);
        
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
        $character = $this->getOrCreateCharacter();
        $nextLocation = $this->getNextLocationFromCharacter($character);
        
        if ($nextLocation) {
            $newPosition = $nextLocation['type'] === 'road' ? 50 : 0;
            
            $character->update([
                'location_type' => $nextLocation['type'],
                'location_id' => $nextLocation['id'],
                'game_position' => $newPosition,
            ]);
            
            // 町に入った場合、履歴を更新
            if ($nextLocation['type'] === 'town') {
                session(['last_visited_town' => $nextLocation['id']]);
            }
        }
        
        // 最新のキャラクター情報を取得
        $character->refresh();
        $currentLocation = $this->getCurrentLocationFromCharacter($character);
        $newNextLocation = $this->getNextLocationFromCharacter($character);
        $position = $character->game_position ?? 0;
        $locationType = $character->location_type ?? 'town';
        
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
        $character = $this->getOrCreateCharacter();
        $character->update([
            'location_type' => 'town',
            'location_id' => 'town_a',
            'game_position' => 0,
        ]);
        
        $currentLocation = $this->getCurrentLocationFromCharacter($character);
        $nextLocation = $this->getNextLocationFromCharacter($character);
        
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
        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        
        // characterIdパラメータは互換性のため残すが、認証ユーザーのキャラクターのみ処理
        if ($character->id !== $characterId) {
            return; // セキュリティ: 他ユーザーのキャラクター操作を防ぐ
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

    // Database-First: Characterから現在位置情報を取得
    private function getCurrentLocationFromCharacter(Character $character): array
    {
        $locationType = $character->location_type ?? 'town';
        $locationId = $character->location_id ?? 'town_a';
        
        return [
            'type' => $locationType,
            'id' => $locationId,
            'name' => $this->getLocationName($locationType, $locationId),
        ];
    }

    // Database-First: Characterから次の位置情報を取得
    private function getNextLocationFromCharacter(Character $character): ?array
    {
        $locationType = $character->location_type ?? 'town';
        $locationId = $character->location_id ?? 'town_a';
        $position = $character->game_position ?? 0;
        
        if ($locationType === 'town') {
            if ($locationId === 'town_a') {
                return ['type' => 'road', 'id' => 'road_1', 'name' => '道路1'];
            } elseif ($locationId === 'town_b') {
                return ['type' => 'road', 'id' => 'road_3', 'name' => '道路3'];
            }
        } elseif ($locationType === 'road') {
            $roadNumber = (int) str_replace('road_', '', $locationId);
            
            if ($position <= 0) {
                if ($roadNumber === 1) {
                    return ['type' => 'town', 'id' => 'town_a', 'name' => 'A町'];
                } else {
                    return ['type' => 'road', 'id' => 'road_' . ($roadNumber - 1), 'name' => '道路' . ($roadNumber - 1)];
                }
            } elseif ($position >= 100) {
                if ($roadNumber === 3) {
                    return ['type' => 'town', 'id' => 'town_b', 'name' => 'B町'];
                } else {
                    return ['type' => 'road', 'id' => 'road_' . ($roadNumber + 1), 'name' => '道路' . ($roadNumber + 1)];
                }
            }
        }
        
        return null;
    }

    // 位置名を取得
    private function getLocationName(string $type, string $id): string
    {
        if ($type === 'town') {
            return match($id) {
                'town_a' => 'A町',
                'town_b' => 'B町',
                default => '未知の町',
            };
        } elseif ($type === 'road') {
            $roadNumber = str_replace('road_', '', $id);
            return '道路' . $roadNumber;
        }
        
        return '未知の場所';
    }
}