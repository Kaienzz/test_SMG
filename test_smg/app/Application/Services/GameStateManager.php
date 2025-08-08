<?php

namespace App\Application\Services;

use App\Models\Player;
use App\Domain\Location\LocationService;
use App\Application\DTOs\MoveResult;
use App\Application\DTOs\DiceResult;
use App\Application\DTOs\LocationData;
use App\Application\DTOs\EncounterData;
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
     * @param Player $player
     * @return DiceResult
     */
    public function rollDice(Player $player): DiceResult
    {
        // TODO: 将来的にはPlayerのスキル・装備による動的計算
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
     * プレイヤーを移動させる
     *
     * @param Player $player
     * @param Request $request
     * @return MoveResult
     */
    public function movePlayer(Player $player, Request $request): MoveResult
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
        $moveResult = $this->locationService->calculateMovement($player, $steps, $direction);
        
        if (!$moveResult['success']) {
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: $moveResult['error'],
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }
        
        // プレイヤー位置を更新
        $player->update(['game_position' => $moveResult['newPosition']]);
        
        $currentLocationArray = $this->locationService->getCurrentLocation($player);
        $nextLocationArray = $this->locationService->getNextLocation($player);
        
        $currentLocation = LocationData::fromArray($currentLocationArray);
        $nextLocation = $nextLocationArray ? LocationData::fromArray($nextLocationArray) : null;
        
        // エンカウント判定
        \Log::info('🚀 [ENCOUNTER] Checking encounter for player', [
            'player_id' => $player->id,
            'location_type' => $player->location_type,
            'location_id' => $player->location_id,
            'position' => $player->game_position
        ]);
        
        $encounter = $this->checkEncounter($player);
        
        if ($encounter) {
            \Log::info('🚀 [ENCOUNTER] Encounter occurred!', [
                'monster_name' => $encounter->name,
                'monster_level' => $encounter->level
            ]);
        } else {
            \Log::info('🚀 [ENCOUNTER] No encounter this time');
        }
        
        return MoveResult::success(
            position: $moveResult['newPosition'],
            stepsMoved: $moveResult['stepsMoved'],
            currentLocation: $currentLocation,
            nextLocation: $nextLocation,
            canMoveToNext: $moveResult['canMoveToNext'],
            canMoveToPrevious: $moveResult['canMoveToPrevious'],
            encounter: $encounter
        );
    }

    /**
     * 特定方向への移動（複数接続システム用）
     *
     * @param Player $player
     * @param string $direction
     * @return MoveResult
     */
    public function moveToDirection(Player $player, string $direction): MoveResult
    {
        // 現在町にいるかチェック
        if ($player->location_type !== 'town') {
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: '方向指定移動は町でのみ可能です',
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }

        // 接続が存在するかチェック（複数でなくても単一接続があれば OK）
        $connections = $this->locationService->getTownConnections($player->location_id);
        if (!$connections || empty($connections)) {
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: 'この町からは移動できません',
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }

        // 指定方向への移動先を取得
        $nextLocation = $this->locationService->getNextLocationFromTownDirection(
            $player->location_id,
            $direction
        );

        if (!$nextLocation) {
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: '指定された方向への移動先が見つかりません',
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }

        // 現在の位置情報を取得
        $currentLocationArray = $this->locationService->getCurrentLocation($player);

        // 移動方向に基づく開始位置を計算
        $newPosition = $this->locationService->calculateStartPosition(
            $currentLocationArray['type'],
            $currentLocationArray['id'],
            $nextLocation['type'],
            $nextLocation['id']
        );

        // プレイヤーの位置を更新
        $player->update([
            'location_type' => $nextLocation['type'],
            'location_id' => $nextLocation['id'],
            'game_position' => $newPosition,
        ]);

        // 町に入った場合、履歴を更新
        if ($nextLocation['type'] === 'town') {
            session(['last_visited_town' => $nextLocation['id']]);
        }

        // 最新情報を取得
        $player->refresh();
        $currentLocationArray = $this->locationService->getCurrentLocation($player);
        $newNextLocationArray = $this->locationService->getNextLocation($player);

        $currentLocation = LocationData::fromArray($currentLocationArray);
        $newNextLocation = $newNextLocationArray ? LocationData::fromArray($newNextLocationArray) : null;

        // 方向ラベルを取得
        $connections = $this->locationService->getTownConnections($player->location_id);
        $directionLabel = $connections[$direction]['direction_label'] ?? $direction;

        return MoveResult::transition(
            currentLocation: $currentLocation,
            nextLocation: $newNextLocation,
            position: $player->game_position ?? 0,
            message: "方向選択で移動しました（{$directionLabel}）"
        );
    }

    /**
     * 分岐選択による移動
     *
     * @param Player $player
     * @param string $direction
     * @return MoveResult
     */
    public function moveToBranch(Player $player, string $direction): MoveResult
    {
        // 現在道路上にいるかチェック
        if ($player->location_type !== 'road') {
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: '分岐移動は道路上でのみ可能です',
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }

        // 分岐可能位置にいるかチェック
        if (!$this->locationService->hasBranchAt($player->location_id, $player->game_position)) {
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: 'この位置には分岐がありません',
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }

        // 分岐先を取得
        $nextLocation = $this->locationService->getNextLocationFromBranch(
            $player->location_id,
            $player->game_position,
            $direction
        );

        if (!$nextLocation) {
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: '選択された方向への移動先が見つかりません',
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }

        // 現在の位置情報を取得
        $currentLocationArray = $this->locationService->getCurrentLocation($player);

        // 移動方向に基づく開始位置を計算
        $newPosition = $this->locationService->calculateStartPosition(
            $currentLocationArray['type'],
            $currentLocationArray['id'],
            $nextLocation['type'],
            $nextLocation['id']
        );

        // プレイヤーの位置を更新
        $player->update([
            'location_type' => $nextLocation['type'],
            'location_id' => $nextLocation['id'],
            'game_position' => $newPosition,
        ]);

        // 町に入った場合、履歴を更新
        if ($nextLocation['type'] === 'town') {
            session(['last_visited_town' => $nextLocation['id']]);
        }

        // 最新情報を取得
        $player->refresh();
        $currentLocationArray = $this->locationService->getCurrentLocation($player);
        $newNextLocationArray = $this->locationService->getNextLocation($player);

        $currentLocation = LocationData::fromArray($currentLocationArray);
        $newNextLocation = $newNextLocationArray ? LocationData::fromArray($newNextLocationArray) : null;

        return MoveResult::transition(
            currentLocation: $currentLocation,
            nextLocation: $newNextLocation,
            position: $player->game_position ?? 0,
            message: "分岐を選択して移動しました（{$direction}）"
        );
    }

    /**
     * 次の場所に移動する
     *
     * @param Player $player
     * @return MoveResult
     */
    public function moveToNextLocation(Player $player): MoveResult
    {
        \Log::info('🚀 [DEBUG] =============== moveToNextLocation START ===============');
        \Log::info('🚀 [DEBUG] Player state before moveToNext:', [
            'id' => $player->id,
            'location_type' => $player->location_type,
            'location_id' => $player->location_id,
            'game_position' => $player->game_position,
            'updated_at' => $player->updated_at?->toISOString()
        ]);
        
        $nextLocation = $this->locationService->getNextLocation($player);
        \Log::info('🚀 [DEBUG] Next location calculated:', $nextLocation);
        
        if (!$nextLocation) {
            \Log::warning('🚀 [DEBUG] No next location found');
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: '次の場所が見つかりません',
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }
        
        // 現在の位置情報を取得
        $currentLocationArray = $this->locationService->getCurrentLocation($player);
        \Log::info('🚀 [DEBUG] Current location array:', $currentLocationArray);
        
        // 移動方向に基づく開始位置を計算
        $newPosition = $this->locationService->calculateStartPosition(
            $currentLocationArray['type'],
            $currentLocationArray['id'],
            $nextLocation['type'],
            $nextLocation['id']
        );
        \Log::info('🚀 [DEBUG] Calculated new position: ' . $newPosition);
        
        \Log::info('🚀 [DEBUG] Updating player location in DB:', [
            'from' => [
                'location_type' => $player->location_type,
                'location_id' => $player->location_id,
                'game_position' => $player->game_position
            ],
            'to' => [
                'location_type' => $nextLocation['type'],
                'location_id' => $nextLocation['id'],
                'game_position' => $newPosition
            ]
        ]);
        
        // DB更新前の詳細状態チェック
        \Log::info('🚀 [DEBUG] Player state immediately before DB update:', [
            'id' => $player->id,
            'location_type' => $player->location_type,
            'location_id' => $player->location_id,
            'game_position' => $player->game_position,
            'updated_at' => $player->updated_at?->toISOString(),
            'exists' => $player->exists,
            'isDirty' => $player->isDirty()
        ]);
        
        $updateResult = $player->update([
            'location_type' => $nextLocation['type'],
            'location_id' => $nextLocation['id'],
            'game_position' => $newPosition,
        ]);
        
        \Log::info('🚀 [DEBUG] DB update result:', [
            'success' => $updateResult,
            'attempted_values' => [
                'location_type' => $nextLocation['type'],
                'location_id' => $nextLocation['id'],
                'game_position' => $newPosition
            ]
        ]);
        
        // 更新直後の状態確認（refresh前）
        \Log::info('🚀 [DEBUG] Player state immediately after update (before refresh):', [
            'location_type' => $player->location_type,
            'location_id' => $player->location_id,
            'game_position' => $player->game_position,
            'updated_at' => $player->updated_at?->toISOString(),
            'isDirty' => $player->isDirty()
        ]);
        
        // 町に入った場合、履歴を更新
        if ($nextLocation['type'] === 'town') {
            session(['last_visited_town' => $nextLocation['id']]);
            \Log::info('🚀 [DEBUG] Updated last_visited_town session to: ' . $nextLocation['id']);
        }
        
        // セッションライフサイクル改善: 移動完了後の古いセッションデータクリーンアップ
        $this->cleanupStaleSessionData($player->id);
        \Log::info('🚀 [DEBUG] Post-movement session cleanup completed');
        
        // 最新情報を取得
        $player->refresh();
        \Log::info('🚀 [DEBUG] Player state after DB update and refresh:', [
            'location_type' => $player->location_type,
            'location_id' => $player->location_id,
            'game_position' => $player->game_position,
            'updated_at' => $player->updated_at?->toISOString()
        ]);
        
        // データベースから直接確認（別インスタンスで検証）
        $freshPlayer = \App\Models\Player::find($player->id);
        \Log::info('🚀 [DEBUG] Fresh player instance from DB:', [
            'location_type' => $freshPlayer->location_type,
            'location_id' => $freshPlayer->location_id,
            'game_position' => $freshPlayer->game_position,
            'updated_at' => $freshPlayer->updated_at?->toISOString()
        ]);
        
        $currentLocationArray = $this->locationService->getCurrentLocation($player);
        $newNextLocationArray = $this->locationService->getNextLocation($player);
        
        $currentLocation = LocationData::fromArray($currentLocationArray);
        $newNextLocation = $newNextLocationArray ? LocationData::fromArray($newNextLocationArray) : null;
        
        \Log::info('🚀 [DEBUG] Final result data:', [
            'currentLocation' => $currentLocation->toArray(),
            'nextLocation' => $newNextLocation?->toArray(),
            'position' => $player->game_position ?? 0
        ]);
        
        $result = MoveResult::transition(
            currentLocation: $currentLocation,
            nextLocation: $newNextLocation,
            position: $player->game_position ?? 0,
            message: '移動しました'
        );
        
        \Log::info('🚀 [DEBUG] =============== moveToNextLocation END ===============');
        
        return $result;
    }

    /**
     * 直接移動（サイコロなし移動）
     *
     * @param Player $player
     * @param string|null $direction 分岐での方向指定
     * @param string|null $townDirection 町での方向指定
     * @return MoveResult
     */
    public function moveDirectly(Player $player, ?string $direction = null, ?string $townDirection = null): MoveResult
    {
        // 直接移動が可能かチェック
        if (!$this->locationService->canMoveDirectly($player)) {
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: '現在の位置からは直接移動できません',
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }

        // 直接移動を実行
        $moveResult = $this->locationService->moveDirectly($player, $direction, $townDirection);
        
        if (!$moveResult['success']) {
            $currentLocationArray = $this->locationService->getCurrentLocation($player);
            $currentLocation = LocationData::fromArray($currentLocationArray);
            return MoveResult::failure(
                error: $moveResult['error'] ?? '移動に失敗しました',
                currentPosition: $player->game_position ?? 0,
                currentLocation: $currentLocation
            );
        }

        $destination = $moveResult['destination'];
        $startPosition = $moveResult['startPosition'];

        // プレイヤー情報を更新
        $player->update([
            'location_type' => $destination['type'],
            'location_id' => $destination['id'],
            'game_position' => $startPosition,
        ]);

        // 町に入った場合、履歴を更新
        if ($destination['type'] === 'town') {
            session(['last_visited_town' => $destination['id']]);
        }

        // 最新情報を取得
        $player->refresh();
        $currentLocationArray = $this->locationService->getCurrentLocation($player);
        $newNextLocationArray = $this->locationService->getNextLocation($player);

        $currentLocation = LocationData::fromArray($currentLocationArray);
        $newNextLocation = $newNextLocationArray ? LocationData::fromArray($newNextLocationArray) : null;

        return MoveResult::transition(
            currentLocation: $currentLocation,
            nextLocation: $newNextLocation,
            position: $player->game_position ?? 0,
            message: 'サイコロを使わずに移動しました'
        );
    }

    /**
     * ゲーム状態をリセットする
     *
     * @param Player $player
     * @return MoveResult
     */
    public function resetGameState(Player $player): MoveResult
    {
        $player->update([
            'location_type' => 'town',
            'location_id' => 'town_a',
            'game_position' => 0,
        ]);
        
        $currentLocationArray = $this->locationService->getCurrentLocation($player);
        $nextLocationArray = $this->locationService->getNextLocation($player);
        
        $currentLocation = LocationData::fromArray($currentLocationArray);
        $nextLocation = $nextLocationArray ? LocationData::fromArray($nextLocationArray) : null;
        
        return MoveResult::success(
            position: 0,
            stepsMoved: 0,
            currentLocation: $currentLocation,
            nextLocation: $nextLocation,
            canMoveToNext: false,
            canMoveToPrevious: false
        );
    }

    /**
     * セッションデータをデータベースに移行する
     * 
     * @param Player $player
     * @return void
     */
    public function migrateSessionToDatabase(Player $player): void
    {
        \Log::info('🚀 [DEBUG] =============== migrateSessionToDatabase START ===============');
        
        $userId = Auth::id();
        $sessionKey = "user_{$userId}_game_data";
        
        \Log::info('🚀 [DEBUG] Migration check for user ID: ' . $userId);
        \Log::info('🚀 [DEBUG] Player state before migration:', [
            'location_type' => $player->location_type,
            'location_id' => $player->location_id,
            'game_position' => $player->game_position,
            'updated_at' => $player->updated_at?->toISOString(),
            'minutes_since_update' => $player->updated_at ? $player->updated_at->diffInMinutes(now()) : 'N/A'
        ]);
        
        // セッションデータが存在する場合はDBに移行
        $hasSessionKey = session()->has($sessionKey);
        $hasLocationData = session()->has('location_type');
        
        \Log::info('🚀 [DEBUG] Session check:', [
            'has_session_key' => $hasSessionKey,
            'has_location_type' => $hasLocationData,
            'session_key' => $sessionKey
        ]);
        
        if ($hasSessionKey || $hasLocationData) {
            $sessionData = session($sessionKey) ?? [];
            
            \Log::info('🚀 [DEBUG] Session data found:', [
                'session_data' => $sessionData,
                'individual_location_type' => session('location_type'),
                'individual_location_id' => session('location_id'),
                'individual_game_position' => session('game_position')
            ]);
            
            // セッションからlocation情報を取得（フォールバック付き）
            $locationType = $sessionData['location_type'] ?? session('location_type', $player->location_type ?? 'town');
            $locationId = $sessionData['location_id'] ?? session('location_id', $player->location_id ?? 'town_a');
            $gamePosition = $sessionData['game_position'] ?? session('game_position', $player->game_position ?? 0);
            
            \Log::info('🚀 [DEBUG] Extracted session values:', [
                'location_type' => $locationType,
                'location_id' => $locationId,
                'game_position' => $gamePosition
            ]);
            
            // 戦闘が最近終了した場合（過去5分以内にPlayerが更新された場合）はlocation移行をスキップ
            $recentlyUpdated = $player->updated_at && $player->updated_at->diffInMinutes(now()) < 5;
            
            \Log::info('🚀 [DEBUG] Recently updated check:', [
                'recently_updated' => $recentlyUpdated,
                'minutes_since_update' => $player->updated_at ? $player->updated_at->diffInMinutes(now()) : 'N/A'
            ]);
            
            // 移行判定の改善: セッションデータがある場合は常にクリーンアップを実行
            // ただし、DBへの移行は初期値の場合のみ
            $hasSessionData = !empty($sessionData);
            $shouldMigrateToDB = (!$player->location_type || $player->location_type === 'town') && !$recentlyUpdated;
            $shouldCleanupSession = $hasSessionData; // セッションデータがあれば常にクリーンアップ
            
            \Log::info('🚀 [DEBUG] Migration decision:', [
                'has_session_data' => $hasSessionData,
                'should_migrate_to_db' => $shouldMigrateToDB,
                'should_cleanup_session' => $shouldCleanupSession,
                'no_location_type' => !$player->location_type,
                'is_town' => ($player->location_type === 'town'),
                'recently_updated' => $recentlyUpdated
            ]);
            
            if ($shouldMigrateToDB) {
                \Log::info('🚀 [DEBUG] Migrating location data from session to DB');
                $player->updateLocation($locationType, $locationId, $gamePosition);
                \Log::info('🚀 [DEBUG] Location migration completed');
            } else {
                \Log::info('🚀 [DEBUG] Location migration to DB skipped due to conditions');
            }
            
            // リソース情報も移行（SP, Gold）
            if (isset($sessionData['player_sp']) && $player->sp !== $sessionData['player_sp']) {
                \Log::info('🚀 [DEBUG] Migrating SP from session:', ['from' => $player->sp, 'to' => $sessionData['player_sp']]);
                $player->update(['sp' => $sessionData['player_sp']]);
            }
            if (isset($sessionData['player_gold']) && $player->gold !== $sessionData['player_gold']) {
                \Log::info('🚀 [DEBUG] Migrating Gold from session:', ['from' => $player->gold, 'to' => $sessionData['player_gold']]);
                $player->update(['gold' => $sessionData['player_gold']]);
            }
            
            // セッション個別キーも移行
            if (session()->has('player_sp') && $player->sp !== session('player_sp')) {
                \Log::info('🚀 [DEBUG] Migrating SP from individual session key:', ['from' => $player->sp, 'to' => session('player_sp')]);
                $player->update(['sp' => session('player_sp')]);
            }
            if (session()->has('player_gold') && $player->gold !== session('player_gold')) {
                \Log::info('🚀 [DEBUG] Migrating Gold from individual session key:', ['from' => $player->gold, 'to' => session('player_gold')]);
                $player->update(['gold' => session('player_gold')]);
            }
            
            // セッションクリーンアップ: データがあれば常に実行
            if ($shouldCleanupSession) {
                $keysToForget = [
                    $sessionKey, 
                    'location_type', 'location_id', 'game_position',
                    'player_sp', 'player_gold',
                    'last_visited_town'  // 移動時に作成されるセッションキーも削除
                ];
                \Log::info('🚀 [DEBUG] Cleaning up session keys:', $keysToForget);
                session()->forget($keysToForget);
                \Log::info('🚀 [DEBUG] Session cleanup completed');
            } else {
                \Log::info('🚀 [DEBUG] Session cleanup skipped - no session data to clean');
            }
        } else {
            \Log::info('🚀 [DEBUG] No session data found to migrate');
        }
        
        \Log::info('🚀 [DEBUG] Player state after migration:', [
            'location_type' => $player->location_type,
            'location_id' => $player->location_id,
            'game_position' => $player->game_position,
            'updated_at' => $player->updated_at?->toISOString()
        ]);
        
        \Log::info('🚀 [DEBUG] =============== migrateSessionToDatabase END ===============');
    }
    
    /**
     * 移動完了後の古いセッションデータクリーンアップ
     * セッションとDBの一貫性を保つため、移動後に実行
     */
    private function cleanupStaleSessionData(int $playerId): void
    {
        \Log::info('🚀 [DEBUG] Starting post-movement session cleanup');
        
        // 移動処理で不要になった可能性のあるセッションキー
        $potentialStaleKeys = [
            'location_type',
            'location_id', 
            'game_position',
            'player_sp',
            'player_gold',
            "player_{$playerId}_location",
            "player_{$playerId}_state",
            "user_" . auth()->id() . "_game_data"
        ];
        
        $keysToCleanup = [];
        foreach ($potentialStaleKeys as $key) {
            if (session()->has($key)) {
                $keysToCleanup[] = $key;
            }
        }
        
        if (!empty($keysToCleanup)) {
            \Log::info('🚀 [DEBUG] Cleaning up stale session keys:', $keysToCleanup);
            session()->forget($keysToCleanup);
        } else {
            \Log::info('🚀 [DEBUG] No stale session data found to cleanup');
        }
    }

    /**
     * ターン効果処理
     *
     * @param Player $player
     * @return void
     */
    public function processTurnEffects(Player $player): void
    {
        $player->restoreSP(2);
        $player->save();

        $activeEffects = $player->activeEffects()
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
     * @param Player $player
     * @return EncounterData|null
     */
    private function checkEncounter(Player $player): ?EncounterData
    {
        // 道路にいる場合のみエンカウント判定
        if ($player->location_type === 'road') {
            $encounterArray = BattleService::checkEncounter($player->location_id);
            
            if ($encounterArray) {
                return EncounterData::fromArray($encounterArray);
            }
        }
        
        return null;
    }
}