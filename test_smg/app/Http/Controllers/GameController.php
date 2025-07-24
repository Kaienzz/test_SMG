<?php

namespace App\Http\Controllers;

use App\Models\GameState;
use App\Models\Character;
use App\Models\ActiveEffect;
use App\Models\Monster;
use App\Services\MovementService;
use App\Services\BattleService;
use App\Domain\Location\LocationService;
use App\Application\Services\GameDisplayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    use \App\Http\Controllers\Traits\HasCharacter;
    
    protected LocationService $locationService;
    protected GameDisplayService $gameDisplayService;
    
    public function __construct(
        LocationService $locationService,
        GameDisplayService $gameDisplayService
    ) {
        $this->locationService = $locationService;
        $this->gameDisplayService = $gameDisplayService;
    }
    
    public function index(): View
    {
        // Database-First: 認証ユーザーのキャラクターを取得または作成
        $character = $this->getOrCreateCharacter();
        
        // セッション→DB移行: 既存セッションデータがあればDBに反映
        $this->migrateSessionToDatabase($character);
        
        // GameDisplayService で View用データを統一準備
        $gameViewData = $this->gameDisplayService->prepareGameView($character);
        
        return view('game.index', $gameViewData);
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
        
        // Database-First: Characterから現在状態を取得
        $character = $this->getOrCreateCharacter();
        
        // 左右の移動を前後の移動に変換
        if ($direction === 'left') {
            $direction = 'backward';
        } elseif ($direction === 'right') {
            $direction = 'forward';
        }
        
        // LocationService で移動計算
        $moveResult = $this->locationService->calculateMovement($character, $steps, $direction);
        
        if (!$moveResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $moveResult['error']
            ], 400);
        }
        
        // Database-First: Characterに保存
        $character->update(['game_position' => $moveResult['newPosition']]);
        
        $currentLocation = $this->locationService->getCurrentLocation($character);
        $nextLocation = $this->locationService->getNextLocation($character);
        
        // エンカウント判定
        $currentLocationId = session('location_id', 'road_1');
        $encounteredMonster = null;
        
        if (session('location_type') === 'road') {
            $encounteredMonster = BattleService::checkEncounter($currentLocationId);
        }
        
        $response = [
            'success' => true,
            'position' => $moveResult['newPosition'],
            'steps_moved' => $moveResult['stepsMoved'],
            'currentLocation' => $currentLocation,
            'nextLocation' => $nextLocation,
            'canMoveToNext' => $moveResult['canMoveToNext'],
            'canMoveToPrevious' => $moveResult['canMoveToPrevious'],
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
        $nextLocation = $this->locationService->getNextLocation($character);
        
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
        $currentLocation = $this->locationService->getCurrentLocation($character);
        $newNextLocation = $this->locationService->getNextLocation($character);
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
        
        $currentLocation = $this->locationService->getCurrentLocation($character);
        $nextLocation = $this->locationService->getNextLocation($character);
        
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

}