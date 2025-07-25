<?php

namespace App\Application\Services;

use App\Models\Character;
use App\Domain\Location\LocationService;
use App\Application\DTOs\MoveResult;
use App\Application\DTOs\DiceResult;
use App\Services\BattleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ゲーム状態管理サービス
 * 
 * GameController からビジネスロジックを抽出し、ゲーム状態の変更を統一管理
 * Phase 3: Controller純化でのサービス層統合
 */
class GameStateManager
{
    public function __construct(
        private readonly LocationService $locationService
    ) {}

    /**
     * サイコロを振る
     *
     * @param Character $character
     * @return DiceResult
     */
    public function rollDice(Character $character): DiceResult
    {
        // TODO: 将来的にはCharacterのスキル・装備による動的計算
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);
        $dice3 = rand(1, 6); // 追加サイコロ
        
        $diceRolls = [$dice1, $dice2, $dice3];
        $bonus = 3; // 飛脚術効果
        $movementEffects = [
            'dice_bonus' => 3,
            'extra_dice' => 1,
            'movement_multiplier' => 1.0,
        ];
        
        return DiceResult::create($diceRolls, $bonus, $movementEffects);
    }

    /**
     * キャラクターを移動させる
     *
     * @param Character $character
     * @param Request $request
     * @return MoveResult
     */
    public function moveCharacter(Character $character, Request $request): MoveResult
    {
        $direction = $request->input('direction');
        $steps = $request->input('steps');
        
        // 左右の移動を前後の移動に変換
        if ($direction === 'left') {
            $direction = 'backward';
        } elseif ($direction === 'right') {
            $direction = 'forward';
        }
        
        // LocationService で移動計算
        $moveResult = $this->locationService->calculateMovement($character, $steps, $direction);
        
        if (!$moveResult['success']) {
            return MoveResult::failure($moveResult['error']);
        }
        
        // キャラクター位置を更新
        $character->update(['game_position' => $moveResult['newPosition']]);
        
        $currentLocation = $this->locationService->getCurrentLocation($character);
        $nextLocation = $this->locationService->getNextLocation($character);
        
        // エンカウント判定
        $encounter = $this->checkEncounter($character);
        
        return MoveResult::success([
            'position' => $moveResult['newPosition'],
            'steps_moved' => $moveResult['stepsMoved'],
            'currentLocation' => $currentLocation,
            'nextLocation' => $nextLocation,
            'canMoveToNext' => $moveResult['canMoveToNext'],
            'canMoveToPrevious' => $moveResult['canMoveToPrevious'],
        ], $encounter);
    }

    /**
     * 次の場所に移動する
     *
     * @param Character $character
     * @return MoveResult
     */
    public function moveToNextLocation(Character $character): MoveResult
    {
        $nextLocation = $this->locationService->getNextLocation($character);
        
        if (!$nextLocation) {
            return MoveResult::failure('次の場所が見つかりません');
        }
        
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
        
        // 最新情報を取得
        $character->refresh();
        $currentLocation = $this->locationService->getCurrentLocation($character);
        $newNextLocation = $this->locationService->getNextLocation($character);
        
        return MoveResult::transition([
            'currentLocation' => $currentLocation,
            'position' => $character->game_position ?? 0,
            'nextLocation' => $newNextLocation,
            'location_type' => $character->location_type ?? 'town',
        ]);
    }

    /**
     * ゲーム状態をリセットする
     *
     * @param Character $character
     * @return MoveResult
     */
    public function resetGameState(Character $character): MoveResult
    {
        $character->update([
            'location_type' => 'town',
            'location_id' => 'town_a',
            'game_position' => 0,
        ]);
        
        $currentLocation = $this->locationService->getCurrentLocation($character);
        $nextLocation = $this->locationService->getNextLocation($character);
        
        return MoveResult::success([
            'currentLocation' => $currentLocation,
            'position' => 0,
            'nextLocation' => $nextLocation,
            'message' => 'ゲームがリセットされました'
        ]);
    }

    /**
     * セッションデータをデータベースに移行する
     * 
     * @param Character $character
     * @return void
     */
    public function migrateSessionToDatabase(Character $character): void
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
     * ターン効果処理
     *
     * @param Character $character
     * @return void
     */
    public function processTurnEffects(Character $character): void
    {
        $character->restoreSP(2);
        $character->save();

        $activeEffects = $character->activeEffects()
                                  ->where('is_active', true)
                                  ->where('remaining_duration', '>', 0)
                                  ->get();

        foreach ($activeEffects as $effect) {
            $effect->decreaseDuration(1);
        }
    }

    /**
     * エンカウント判定
     *
     * @param Character $character
     * @return array|null
     */
    private function checkEncounter(Character $character): ?array
    {
        // 道路にいる場合のみエンカウント判定
        if ($character->location_type === 'road') {
            return BattleService::checkEncounter($character->location_id);
        }
        
        return null;
    }
}