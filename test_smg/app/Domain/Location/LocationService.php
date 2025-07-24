<?php

namespace App\Domain\Location;

use App\Models\Character;

/**
 * 位置管理統一サービス
 * 
 * Character の位置情報に関する計算・変換を統一管理
 * GameController、GameState、Playerモデルの重複ロジックを統合
 */
class LocationService
{
    /**
     * Character から現在位置情報を取得
     *
     * @param Character $character
     * @return array{type: string, id: string, name: string}
     */
    public function getCurrentLocation(Character $character): array
    {
        $locationType = $character->location_type ?? 'town';
        $locationId = $character->location_id ?? 'town_a';
        
        return [
            'type' => $locationType,
            'id' => $locationId,
            'name' => $this->getLocationName($locationType, $locationId),
        ];
    }

    /**
     * Character から次の位置情報を取得
     *
     * @param Character $character
     * @return array{type: string, id: string, name: string}|null
     */
    public function getNextLocation(Character $character): ?array
    {
        $locationType = $character->location_type ?? 'town';
        $locationId = $character->location_id ?? 'town_a';
        $position = $character->game_position ?? 0;
        
        if ($locationType === 'town') {
            return $this->getNextLocationFromTown($locationId);
        } elseif ($locationType === 'road') {
            return $this->getNextLocationFromRoad($locationId, $position);
        }
        
        return null;
    }

    /**
     * 移動計算を実行
     *
     * @param Character $character
     * @param int $steps
     * @param string $direction ('forward' or 'backward')
     * @return array{success: bool, newPosition: int, canMoveToNext: bool, canMoveToPrevious: bool}
     */
    public function calculateMovement(Character $character, int $steps, string $direction): array
    {
        $currentPosition = $character->game_position ?? 0;
        $locationType = $character->location_type ?? 'town';
        
        // 町にいる場合は移動不可
        if ($locationType !== 'road') {
            return [
                'success' => false,
                'newPosition' => $currentPosition,
                'canMoveToNext' => false,
                'canMoveToPrevious' => false,
                'error' => 'プレイヤーは道路上にいません'
            ];
        }
        
        // 移動方向による計算
        $moveAmount = $direction === 'forward' ? $steps : -$steps;
        $newPosition = max(0, min(100, $currentPosition + $moveAmount));
        
        return [
            'success' => true,
            'newPosition' => $newPosition,
            'canMoveToNext' => $newPosition >= 100,
            'canMoveToPrevious' => $newPosition <= 0,
            'stepsMoved' => abs($newPosition - $currentPosition)
        ];
    }

    /**
     * Character の位置状態を判定
     *
     * @param Character $character
     * @return array{isInTown: bool, isOnRoad: bool, canMove: bool}
     */
    public function getLocationStatus(Character $character): array
    {
        $locationType = $character->location_type ?? 'town';
        
        return [
            'isInTown' => $locationType === 'town',
            'isOnRoad' => $locationType === 'road',
            'canMove' => in_array($locationType, ['road', 'dungeon'])
        ];
    }

    /**
     * 位置名を取得
     *
     * @param string $type
     * @param string $id
     * @return string
     */
    public function getLocationName(string $type, string $id): string
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

    /**
     * 町からの次の位置を取得
     *
     * @param string $locationId
     * @return array{type: string, id: string, name: string}|null
     */
    private function getNextLocationFromTown(string $locationId): ?array
    {
        return match($locationId) {
            'town_a' => ['type' => 'road', 'id' => 'road_1', 'name' => '道路1'],
            'town_b' => ['type' => 'road', 'id' => 'road_3', 'name' => '道路3'],
            default => null
        };
    }

    /**
     * 道路からの次の位置を取得
     *
     * @param string $locationId
     * @param int $position
     * @return array{type: string, id: string, name: string}|null
     */
    private function getNextLocationFromRoad(string $locationId, int $position): ?array
    {
        $roadNumber = (int) str_replace('road_', '', $locationId);
        
        if ($position <= 0) {
            // 道路の始点
            if ($roadNumber === 1) {
                return ['type' => 'town', 'id' => 'town_a', 'name' => 'A町'];
            } else {
                return ['type' => 'road', 'id' => 'road_' . ($roadNumber - 1), 'name' => '道路' . ($roadNumber - 1)];
            }
        } elseif ($position >= 100) {
            // 道路の終点
            if ($roadNumber === 3) {
                return ['type' => 'town', 'id' => 'town_b', 'name' => 'B町'];
            } else {
                return ['type' => 'road', 'id' => 'road_' . ($roadNumber + 1), 'name' => '道路' . ($roadNumber + 1)];
            }
        }
        
        return null;
    }
}