<?php

namespace App\Application\Services;

use App\Models\Character;
use App\Domain\Location\LocationService;
use App\Application\DTOs\GameViewData;
use App\Application\DTOs\LocationData;
use App\Application\DTOs\BattleData;

/**
 * ゲーム表示用データ変換サービス
 * 
 * Character からView用データへの変換を統一管理
 * GameController の Player オブジェクト生成ロジックを統合
 * Phase 2: DTO統合により型安全性を向上
 */
class GameDisplayService
{
    public function __construct(
        private LocationService $locationService
    ) {}

    /**
     * ゲーム画面用のView用データを準備
     *
     * @param Character $character
     * @return GameViewData
     */
    public function prepareGameView(Character $character): GameViewData
    {
        $currentLocation = $this->locationService->getCurrentLocation($character);
        $nextLocation = $this->locationService->getNextLocation($character);
        $locationStatus = $this->locationService->getLocationStatus($character);
        
        // LocationData DTOを作成
        $currentLocationDto = LocationData::fromArray($currentLocation);
        $nextLocationDto = $nextLocation ? LocationData::fromArray($nextLocation) : null;
        
        return GameViewData::create(
            character: $character,
            currentLocation: $currentLocationDto,
            nextLocation: $nextLocationDto,
            locationStatus: $locationStatus
        );
    }

    /**
     * 戦闘画面用のView用データを準備
     *
     * @param Character $character
     * @return BattleData
     */
    public function prepareBattleView(Character $character): BattleData
    {
        return BattleData::forBattleView($character);
    }

    /**
     * Ajax レスポンス用のゲーム状態データを準備
     *
     * @param Character $character
     * @return array
     */
    public function prepareGameStateResponse(Character $character): array
    {
        $currentLocation = $this->locationService->getCurrentLocation($character);
        $nextLocation = $this->locationService->getNextLocation($character);
        $locationStatus = $this->locationService->getLocationStatus($character);
        
        return [
            'character' => [
                'id' => $character->id,
                'name' => $character->name,
                'location_type' => $character->location_type,
                'location_id' => $character->location_id,
                'game_position' => $character->game_position,
                'hp' => $character->hp,
                'max_hp' => $character->max_hp,
                'sp' => $character->sp,
                'max_sp' => $character->max_sp,
                'gold' => $character->gold,
            ],
            'currentLocation' => $currentLocation,
            'nextLocation' => $nextLocation,
            'position' => $character->game_position ?? 0,
            'location_type' => $character->location_type ?? 'town',
            'isInTown' => $locationStatus['isInTown'],
            'isOnRoad' => $locationStatus['isOnRoad'],
            'canMove' => $locationStatus['canMove'],
        ];
    }

    /**
     * View用プレイヤーデータオブジェクトを作成（Playerクラスの代替）
     * 
     * @deprecated Phase 2: DTOに移行済み、下位互換性のため残存
     * @param Character $character
     * @param array $locationStatus
     * @return object
     */
    private function createPlayerData(Character $character, array $locationStatus): object
    {
        return (object) [
            'name' => $character->name ?? 'プレイヤー',
            'current_location_type' => $character->location_type ?? 'town',
            'current_location_id' => $character->location_id ?? 'town_a',
            'position' => $character->game_position ?? 0,
            'game_position' => $character->game_position ?? 0,
            
            // 位置状態メソッド（既存Bladeテンプレート互換性のため）
            'isInTown' => function() use ($locationStatus) {
                return $locationStatus['isInTown'];
            },
            'isOnRoad' => function() use ($locationStatus) {
                return $locationStatus['isOnRoad'];
            },
            'getCharacter' => function() use ($character) {
                return $character;
            }
        ];
    }

    /**
     * 移動情報を取得（現在はダミーデータ）
     * 
     * @deprecated Phase 2: MovementInfo DTOに移行済み、下位互換性のため残存
     * @param Character $character
     * @return array
     */
    private function getMovementInfo(Character $character): array
    {
        // TODO: 将来的には Character のスキル・装備から動的計算
        return [
            'base_dice_count' => 2,
            'extra_dice' => 1,
            'total_dice_count' => 3,
            'dice_bonus' => 3,
            'movement_multiplier' => 1.0,
            'special_effects' => [],
            'min_possible_movement' => 6,
            'max_possible_movement' => 21,
        ];
    }

    /**
     * キャラクター統計情報を準備
     *
     * @param Character $character
     * @return array
     */
    public function prepareCharacterStats(Character $character): array
    {
        return [
            'basic_stats' => [
                'level' => $character->level,
                'experience' => $character->experience,
                'experience_to_next' => $character->experience_to_next,
                'gold' => $character->gold,
            ],
            'battle_stats' => $character->getBattleStats(),
            'status_summary' => $character->getStatusSummary(),
            'skills' => $character->getSkillList(),
        ];
    }
}