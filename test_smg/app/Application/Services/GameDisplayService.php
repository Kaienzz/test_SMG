<?php

namespace App\Application\Services;

use App\Models\Player;
use App\Domain\Location\LocationService;
use App\Application\DTOs\GameViewData;
use App\Application\DTOs\LocationData;
use App\Application\DTOs\BattleData;

/**
 * ゲーム表示用データ変換サービス
 * 
 * Player からView用データへの変換を統一管理
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
     * @param Player $player
     * @return GameViewData
     */
    public function prepareGameView(Player $player): GameViewData
    {
        $currentLocation = $this->locationService->getCurrentLocation($player);
        $nextLocation = $this->locationService->getNextLocation($player);
        $locationStatus = $this->locationService->getLocationStatus($player);
        
        // LocationData DTOを作成
        $currentLocationDto = LocationData::fromArray($currentLocation);
        $nextLocationDto = $nextLocation ? LocationData::fromArray($nextLocation) : null;
        
        return GameViewData::create(
            player: $player,
            currentLocation: $currentLocationDto,
            nextLocation: $nextLocationDto,
            locationStatus: $locationStatus
        );
    }

    /**
     * 戦闘画面用のView用データを準備
     *
     * @param Player $player
     * @return BattleData
     */
    public function prepareBattleView(Player $player): BattleData
    {
        return BattleData::forBattleView($player);
    }

    /**
     * Ajax レスポンス用のゲーム状態データを準備
     *
     * @param Player $player
     * @return array
     */
    public function prepareGameStateResponse(Player $player): array
    {
        $currentLocation = $this->locationService->getCurrentLocation($player);
        $nextLocation = $this->locationService->getNextLocation($player);
        $locationStatus = $this->locationService->getLocationStatus($player);
        
        // LocationData DTOを作成
        $currentLocationDto = LocationData::fromArray($currentLocation);
        $nextLocationDto = $nextLocation ? LocationData::fromArray($nextLocation) : null;
        
        return [
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'location_type' => $player->location_type,
                'location_id' => $player->location_id,
                'game_position' => $player->game_position,
                'hp' => $player->hp,
                'max_hp' => $player->max_hp,
                'sp' => $player->sp,
                'max_sp' => $player->max_sp,
                'gold' => $player->gold,
            ],
            'currentLocation' => $currentLocationDto,
            'nextLocation' => $nextLocationDto,
            'position' => $player->game_position ?? 0,
            'location_type' => $player->location_type ?? 'town',
            'isInTown' => $locationStatus['isInTown'],
            'isOnRoad' => $locationStatus['isOnRoad'],
            'canMove' => $locationStatus['canMove'],
        ];
    }

    /**
     * View用プレイヤーデータオブジェクトを作成（Playerクラスの代替）
     * 
     * @deprecated Phase 2: DTOに移行済み、下位互換性のため残存
     * @param Player $player
     * @param array $locationStatus
     * @return object
     */
    private function createPlayerData(Player $player, array $locationStatus): object
    {
        return (object) [
            'name' => $player->name ?? 'プレイヤー',
            'current_location_type' => $player->location_type ?? 'town',
            'current_location_id' => $player->location_id ?? 'town_a',
            'position' => $player->game_position ?? 0,
            'game_position' => $player->game_position ?? 0,
            
            // 位置状態メソッド（既存Bladeテンプレート互換性のため）
            'isInTown' => function() use ($locationStatus) {
                return $locationStatus['isInTown'];
            },
            'isOnRoad' => function() use ($locationStatus) {
                return $locationStatus['isOnRoad'];
            },
            'getPlayer' => function() use ($player) {
                return $player;
            }
        ];
    }

    /**
     * 移動情報を取得（現在はダミーデータ）
     * 
     * @deprecated Phase 2: MovementInfo DTOに移行済み、下位互換性のため残存
     * @param Player $player
     * @return array
     */
    private function getMovementInfo(Player $player): array
    {
        // TODO: 将来的には Player のスキル・装備から動的計算
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
     * @param Player $player
     * @return array
     */
    public function preparePlayerStats(Player $player): array
    {
        return [
            'basic_stats' => [
                'level' => $player->level,
                'experience' => $player->experience,
                'experience_to_next' => $player->experience_to_next,
                'gold' => $player->gold,
            ],
            'battle_stats' => $player->getBattleStats(),
            'status_summary' => $player->getStatusSummary(),
            'skills' => $player->getSkillList(),
        ];
    }
}