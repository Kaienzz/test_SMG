<?php

namespace App\Domain\Location;

use App\Models\Player;
use App\Models\Route;
use App\Models\RouteConnection;
use Illuminate\Support\Facades\Log;

/**
 * 位置管理統一サービス (SQLite対応)
 * 
 * Player の位置情報に関する計算・変換を統一管理
 * SQLiteデータベース完全移行済み
 */
class LocationService
{


    /**
     * 道路名を取得（SQLiteのみ）
     *
     * @param string $roadId
     * @return string
     * @throws \Exception 道路情報が見つからない場合
     */
    private function getRoadNameFromConfig(string $roadId): string
    {
        try {
            $location = Route::where('id', $roadId)
                                  ->where('category', 'road')
                                  ->first();
            
            if ($location) {
                return $location->name;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get road name from SQLite', [
                'road_id' => $roadId,
                'error' => $e->getMessage()
            ]);
        }
        
        throw new \Exception("Road '{$roadId}' not found in SQLite database");
    }

    /**
     * 町名を取得（SQLiteのみ）
     *
     * @param string $townId
     * @return string
     * @throws \Exception 町情報が見つからない場合
     */
    private function getTownNameFromConfig(string $townId): string
    {
        try {
            $location = Route::where('id', $townId)
                                  ->where('category', 'town')
                                  ->first();
            
            if ($location) {
                return $location->name;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get town name from SQLite', [
                'town_id' => $townId,
                'error' => $e->getMessage()
            ]);
        }
        
        throw new \Exception("Town '{$townId}' not found in SQLite database");
    }

    /**
     * ダンジョン名を取得（SQLiteのみ）
     *
     * @param string $dungeonId
     * @return string
     * @throws \Exception ダンジョン情報が見つからない場合
     */
    private function getDungeonNameFromConfig(string $dungeonId): string
    {
        try {
            $location = Route::where('id', $dungeonId)
                                  ->where('category', 'dungeon')
                                  ->first();
            
            if ($location) {
                return $location->name;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get dungeon name from SQLite', [
                'dungeon_id' => $dungeonId,
                'error' => $e->getMessage()
            ]);
        }
        
        throw new \Exception("Dungeon '{$dungeonId}' not found in SQLite database");
    }

    /**
     * 分岐設定を取得（SQLiteのみ）
     *
     * @param string $locationId
     * @param int $position
     * @return array|null
     */
    private function getBranchesFromConfig(string $locationId, int $position): ?array
    {
        try {
            $connections = RouteConnection::where('source_location_id', $locationId)
                                           ->where('connection_type', 'branch')
                                           ->where('position', $position)
                                           ->with('targetLocation')
                                           ->get();
            
            if ($connections->isNotEmpty()) {
                $branches = [];
                foreach ($connections as $connection) {
                    if ($connection->targetLocation) {
                        $branches[$connection->direction] = [
                            'type' => $connection->targetLocation->category,
                            'id' => $connection->targetLocation->id
                        ];
                    }
                }
                return !empty($branches) ? $branches : null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get branches from SQLite', [
                'location_id' => $locationId,
                'position' => $position,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * 町の接続設定を取得（SQLiteのみ）
     *
     * @param string $townId
     * @return array|null
     */
    private function getTownConnectionsFromConfig(string $townId): ?array
    {
        try {
            $connections = RouteConnection::where('source_location_id', $townId)
                                           ->whereIn('connection_type', ['town_connection', 'start', 'end'])
                                           ->with('targetLocation')
                                           ->get();
            
            if ($connections->isNotEmpty()) {
                $townConnections = [];
                foreach ($connections as $connection) {
                    if ($connection->targetLocation) {
                        $townConnections[$connection->direction ?: 'default'] = [
                            'type' => $connection->targetLocation->category,
                            'id' => $connection->targetLocation->id
                        ];
                    }
                }
                return !empty($townConnections) ? $townConnections : null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get town connections from SQLite', [
                'town_id' => $townId,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Player から現在位置情報を取得
     *
     * @param Player $player
     * @return array{type: string, id: string, name: string}
     */
    public function getCurrentLocation(Player $player): array
    {
        $locationType = $player->location_type ?? 'town';
        $locationId = $player->location_id ?? 'town_prima';
        
        return [
            'type' => $locationType,
            'id' => $locationId,
            'name' => $this->getLocationName($locationType, $locationId),
        ];
    }

    /**
     * Player から次の位置情報を取得
     *
     * @param Player $player
     * @return array{type: string, id: string, name: string}|null
     */
    public function getNextLocation(Player $player): ?array
    {
        $locationType = $player->location_type ?? 'town';
        $locationId = $player->location_id ?? 'town_prima';
        $position = $player->game_position ?? 0;
        
        if ($locationType === 'town') {
            return $this->getNextLocationFromTown($locationId);
        } elseif ($locationType === 'road') {
            return $this->getNextLocationFromRoad($locationId, $position);
        } elseif ($locationType === 'dungeon') {
            // ダンジョンも道路と同じロジックで処理
            return $this->getNextLocationFromRoad($locationId, $position);
        }
        
        return null;
    }

    /**
     * 移動計算を実行
     *
     * @param Player $player
     * @param int $steps
     * @param string $direction ('forward' or 'backward')
     * @return array{success: bool, newPosition: int, canMoveToNext: bool, canMoveToPrevious: bool}
     */
    public function calculateMovement(Player $player, int $steps, string $direction): array
    {
        $currentPosition = $player->game_position ?? 0;
        $locationType = $player->location_type ?? 'town';
        $locationId = $player->location_id ?? '';
        
        // 町にいる場合は移動不可、道路・ダンジョンは移動可能
        if (!in_array($locationType, ['road', 'dungeon'])) {
            return [
                'success' => false,
                'newPosition' => $currentPosition,
                'canMoveToNext' => false,
                'canMoveToPrevious' => false,
                'error' => 'プレイヤーは道路またはダンジョン上にいません'
            ];
        }
        
        // 移動方向による計算
        $moveAmount = $direction === 'forward' ? $steps : -$steps;
        $calculatedPosition = $currentPosition + $moveAmount;
        $newPosition = max(0, min(100, $calculatedPosition));
        
        // 位置50で分岐がある場合の自動停止処理
        if ($this->hasBranchAt($locationId, 50)) {
            // 位置50を通過する移動の場合、50で停止
            if (($currentPosition < 50 && $newPosition > 50) || 
                ($currentPosition > 50 && $newPosition < 50)) {
                $newPosition = 50;
            }
        }
        
        // 特別アクションがある位置での自動停止処理
        for ($checkPosition = min($currentPosition, $newPosition); $checkPosition <= max($currentPosition, $newPosition); $checkPosition++) {
            if ($checkPosition !== $currentPosition && $this->hasSpecialActionAt($locationId, $checkPosition)) {
                // 特別アクションのある位置を通過する場合、その位置で停止
                $newPosition = $checkPosition;
                break;
            }
        }
        
        $result = [
            'success' => true,
            'newPosition' => $newPosition,
            'canMoveToNext' => $newPosition >= 100,
            'canMoveToPrevious' => $newPosition <= 0,
            'stepsMoved' => abs($newPosition - $currentPosition)
        ];
        
        // 位置50の分岐情報を追加
        if ($newPosition == 50 && $this->hasBranchAt($locationId, 50)) {
            $result['hasBranch'] = true;
            $result['branchOptions'] = $this->getBranchOptions($locationId, 50);
        } else {
            $result['hasBranch'] = false;
        }
        
        // 特別アクション情報を追加
        if ($this->hasSpecialActionAt($locationId, $newPosition)) {
            $result['hasSpecialAction'] = true;
            $result['specialActionOptions'] = $this->getSpecialActionOptions($locationId, $newPosition);
        } else {
            $result['hasSpecialAction'] = false;
        }
        
        return $result;
    }

    /**
     * Player の位置状態を判定
     *
     * @param Player $player
     * @return array{isInTown: bool, isOnRoad: bool, canMove: bool}
     */
    public function getLocationStatus(Player $player): array
    {
        $locationType = $player->location_type ?? 'town';
        
        return [
            'isInTown' => $locationType === 'town',
            'isOnRoad' => $locationType === 'road',
            'isInDungeon' => $locationType === 'dungeon',
            'canMove' => in_array($locationType, ['road', 'dungeon'])
        ];
    }

    /**
     * 直接移動が可能かどうか判定
     *
     * @param Player $player
     * @return bool
     */
    public function canMoveDirectly(Player $player): bool
    {
        $locationType = $player->location_type ?? 'town';
        $locationId = $player->location_id ?? '';
        $position = $player->game_position ?? 0;
        
        // 町にいる場合は直接移動可能
        if ($locationType === 'town') {
            return true;
        }
        
        // 道路またはダンジョンにいる場合
        if ($locationType === 'road' || $locationType === 'dungeon') {
            // 位置0、50、100にいる場合は直接移動可能
            if ($position === 0 || $position === 50 || $position === 100) {
                return $this->getNextLocation($player) !== null;
            }
            
            // 位置50で分岐がある場合は直接移動可能（上記で既にチェック済みだが念のため）
            if ($position === 50 && $this->hasBranchAt($locationId, 50)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 直接移動を実行
     *
     * @param Player $player
     * @param string|null $direction 分岐での方向指定（分岐時のみ）
     * @param string|null $townDirection 町での方向指定（複数接続町のみ）
     * @return array{success: bool, destination: array|null, error: string|null}
     */
    public function moveDirectly(Player $player, ?string $direction = null, ?string $townDirection = null): array
    {
        $locationType = $player->location_type ?? 'town';
        $locationId = $player->location_id ?? '';
        $position = $player->game_position ?? 0;
        
        // 町にいる場合
        if ($locationType === 'town') {
            // 複数接続がある町で方向指定がある場合
            if ($townDirection && $this->hasMultipleConnections($locationId)) {
                $destination = $this->getNextLocationFromTownDirection($locationId, $townDirection);
            } else {
                // 通常の次の場所取得
                $destination = $this->getNextLocation($player);
            }
            
            if ($destination) {
                $startPosition = $this->calculateStartPosition($locationType, $locationId, $destination['type'], $destination['id']);
                return [
                    'success' => true,
                    'destination' => $destination,
                    'startPosition' => $startPosition,
                    'error' => null
                ];
            }
        }
        
        // 道路またはダンジョンにいる場合
        if ($locationType === 'road' || $locationType === 'dungeon') {
            // 位置50で分岐がある場合
            if ($position === 50 && $this->hasBranchAt($locationId, 50) && $direction) {
                $destination = $this->getNextLocationFromBranch($locationId, $position, $direction);
                if ($destination) {
                    $startPosition = $this->calculateStartPosition($locationType, $locationId, $destination['type'], $destination['id']);
                    return [
                        'success' => true,
                        'destination' => $destination,
                        'startPosition' => $startPosition,
                        'error' => null
                    ];
                }
            }
            
            // 位置0、50、100にいる場合
            if ($position === 0 || $position === 50 || $position === 100) {
                $destination = $this->getNextLocation($player);
                if ($destination) {
                    $startPosition = $this->calculateStartPosition($locationType, $locationId, $destination['type'], $destination['id']);
                    return [
                        'success' => true,
                        'destination' => $destination,
                        'startPosition' => $startPosition,
                        'error' => null
                    ];
                }
            }
        }
        
        return [
            'success' => false,
            'destination' => null,
            'error' => '直接移動できる場所がありません'
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
        return match($type) {
            'town' => $this->getTownName($id),
            'road' => $this->getRoadName($id),
            'dungeon' => $this->getDungeonName($id),
            default => '未知の場所',
        };
    }

    /**
     * 町名を取得
     *
     * @param string $townId
     * @return string
     */
    private function getTownName(string $townId): string
    {
        return $this->getTownNameFromConfig($townId);
    }

    /**
     * 道路名を取得（JSON設定から）
     *
     * @param string $roadId
     * @return string
     * @throws \Exception JSON設定に道路情報がない場合
     */
    private function getRoadName(string $roadId): string
    {
        return $this->getRoadNameFromConfig($roadId);
    }

    /**
     * ダンジョン名を取得（JSON設定から）
     *
     * @param string $dungeonId
     * @return string
     * @throws \Exception JSON設定にダンジョン情報がない場合
     */
    private function getDungeonName(string $dungeonId): string
    {
        return $this->getDungeonNameFromConfig($dungeonId);
    }


    /**
     * 道路の指定位置で分岐が可能かチェック
     *
     * @param string $roadId
     * @param int $position
     * @return bool
     */
    public function hasBranchAt(string $roadId, int $position): bool
    {
        return $this->getBranchesFromConfig($roadId, $position) !== null;
    }

    /**
     * 道路の指定位置での分岐選択肢を取得
     *
     * @param string $roadId
     * @param int $position
     * @return array<string, array<string, string>>|null
     */
    public function getBranchOptions(string $roadId, int $position): ?array
    {
        $branches = $this->getBranchesFromConfig($roadId, $position);
        if ($branches === null) {
            return null;
        }

        $options = [];

        foreach ($branches as $direction => $destination) {
            $options[$direction] = [
                'type' => $destination['type'],
                'id' => $destination['id'],
                'name' => $this->getLocationName($destination['type'], $destination['id']),
                'direction_label' => $this->getDirectionLabel($direction)
            ];
        }

        return $options;
    }

    /**
     * 分岐方向のラベルを取得
     *
     * @param string $direction
     * @return string
     */
    private function getDirectionLabel(string $direction): string
    {
        return match($direction) {
            'straight' => '直進',
            'left' => '左折',
            'right' => '右折',
            'back' => '後退',
            'north' => '北へ',
            'south' => '南へ',
            'east' => '東へ',
            'west' => '西へ',
            default => $direction
        };
    }

    /**
     * 町の複数接続先を取得
     *
     * @param string $townId
     * @return array<string, array<string, string>>|null
     */
    public function getTownConnections(string $townId): ?array
    {
        $townConnections = $this->getTownConnectionsFromConfig($townId);
        if ($townConnections === null) {
            return null;
        }

        $connections = [];
        foreach ($townConnections as $direction => $destination) {
            $connections[$direction] = [
                'type' => $destination['type'],
                'id' => $destination['id'],
                'name' => $this->getLocationName($destination['type'], $destination['id']),
                'direction_label' => $this->getDirectionLabel($direction)
            ];
        }

        return $connections;
    }

    /**
     * 町に複数接続があるかチェック
     *
     * @param string $townId
     * @return bool
     */
    public function hasMultipleConnections(string $townId): bool
    {
        $townConnections = $this->getTownConnectionsFromConfig($townId);
        return $townConnections !== null && count($townConnections) > 1;
    }

    /**
     * 特定方向への町からの接続先を取得
     *
     * @param string $townId
     * @param string $direction
     * @return array{type: string, id: string, name: string}|null
     */
    public function getNextLocationFromTownDirection(string $townId, string $direction): ?array
    {
        $townConnections = $this->getTownConnectionsFromConfig($townId);
        if ($townConnections === null || !isset($townConnections[$direction])) {
            return null;
        }

        $destination = $townConnections[$direction];
        return [
            'type' => $destination['type'],
            'id' => $destination['id'],
            'name' => $this->getLocationName($destination['type'], $destination['id'])
        ];
    }

    /**
     * 分岐選択に基づいて次の場所を取得
     *
     * @param string $roadId
     * @param int $position
     * @param string $direction
     * @return array{type: string, id: string, name: string}|null
     */
    public function getNextLocationFromBranch(string $roadId, int $position, string $direction): ?array
    {
        $options = $this->getBranchOptions($roadId, $position);
        
        if (!$options || !isset($options[$direction])) {
            return null;
        }

        $destination = $options[$direction];
        return [
            'type' => $destination['type'],
            'id' => $destination['id'],
            'name' => $destination['name']
        ];
    }

    /**
     * 町からの次の位置を取得
     * JSON設定の複数接続システムから取得
     *
     * @param string $locationId
     * @return array{type: string, id: string, name: string}|null
     */
    private function getNextLocationFromTown(string $locationId): ?array
    {
        $connections = $this->getTownConnections($locationId);
        if ($connections && !empty($connections)) {
            // 複数接続がある場合は最初の接続を返す
            $firstConnection = reset($connections);
            return [
                'type' => $firstConnection['type'],
                'id' => $firstConnection['id'],
                'name' => $firstConnection['name']
            ];
        }

        return null;
    }

    /**
     * 道路からの次の位置を取得（SQLite優先、JSONフォールバック）
     *
     * @param string $locationId
     * @param int $position
     * @return array{type: string, id: string, name: string}|null
     */
    private function getNextLocationFromRoad(string $locationId, int $position): ?array
    {
        try {
            // SQLiteから接続情報を取得
            $connectionType = null;
            if ($position === 0) {
                $connectionType = 'start';
            } elseif ($position === 100) {
                $connectionType = 'end';
            }
            
            if ($connectionType) {
                $connection = RouteConnection::where('source_location_id', $locationId)
                                               ->where('connection_type', $connectionType)
                                               ->with('targetLocation')
                                               ->first();
                
                if ($connection && $connection->targetLocation) {
                    return [
                        'type' => $connection->targetLocation->category,
                        'id' => $connection->targetLocation->id,
                        'name' => $connection->targetLocation->name
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get road connections from SQLite, falling back to JSON', [
                'location_id' => $locationId,
                'position' => $position,
                'error' => $e->getMessage()
            ]);
        }
        
        // SQLite取得失敗時はJSONフォールバック
        $this->loadConfigData();
        
        // 新しい統合フォーマット対応
        $locationData = null;
        if (isset($this->configData['pathways'][$locationId])) {
            $locationData = $this->configData['pathways'][$locationId];
        }
        // 後方互換性：古いフォーマット対応
        elseif (isset($this->configData['roads'][$locationId])) {
            $locationData = $this->configData['roads'][$locationId];
        } elseif (isset($this->configData['dungeons'][$locationId])) {
            $locationData = $this->configData['dungeons'][$locationId];
        }
        
        if (!$locationData) {
            return null;
        }
        
        if ($position === 0) {
            // 始点での接続
            if (isset($locationData['connections']['start'])) {
                $connection = $locationData['connections']['start'];
                return [
                    'type' => $connection['type'],
                    'id' => $connection['id'],
                    'name' => $this->getLocationName($connection['type'], $connection['id'])
                ];
            }
        } elseif ($position === 100) {
            // 終点での接続
            if (isset($locationData['connections']['end'])) {
                $connection = $locationData['connections']['end'];
                return [
                    'type' => $connection['type'],
                    'id' => $connection['id'],
                    'name' => $this->getLocationName($connection['type'], $connection['id'])
                ];
            }
        }
        
        return null;
    }

    /**
     * 移動方向に基づいて次の場所での開始位置を計算
     *
     * @param string $fromType 移動元の場所タイプ
     * @param string $fromId 移動元の場所ID
     * @param string $toType 移動先の場所タイプ
     * @param string $toId 移動先の場所ID
     * @return int 移動先での開始位置 (道路の場合0-100、町の場合0)
     */
    public function calculateStartPosition(string $fromType, string $fromId, string $toType, string $toId): int
    {
        // 町への移動の場合は常に位置0
        if ($toType === 'town') {
            return 0;
        }

        // 道路・ダンジョンへの移動の場合、移動元によって開始位置を決定
        if ($toType === 'road' || $toType === 'dungeon') {
            return $this->calculateRoadStartPosition($fromType, $fromId, $toId);
        }

        return 0;
    }

    /**
     * 道路への移動時の開始位置を計算（JSON設定から）
     *
     * @param string $fromType 移動元の場所タイプ
     * @param string $fromId 移動元の場所ID
     * @param string $toRoadId 移動先の道路ID
     * @return int 道路での開始位置 (0 または 100)
     */
    private function calculateRoadStartPosition(string $fromType, string $fromId, string $toRoadId): int
    {
        $this->loadConfigData();
        
        if ($fromType === 'town') {
            // 町からの移動 - JSON設定から取得
            if (isset($this->configData['towns'][$fromId]['connections'])) {
                $connections = $this->configData['towns'][$fromId]['connections'];
                
                // 特定の道路への接続情報を検索
                foreach ($connections as $direction => $connection) {
                    if ($connection['type'] === 'road' && $connection['id'] === $toRoadId) {
                        // 接続情報に開始位置が指定されている場合はそれを使用
                        if (isset($connection['start_position'])) {
                            return $connection['start_position'];
                        }
                        
                        // デフォルトの位置決定（方向に基づく）
                        return match($direction) {
                            'north', 'east', 'south', 'west', 'straight' => 0,
                            'back', 'return' => 100,
                            default => 0
                        };
                    }
                }
            }
            
            return 0; // デフォルト
        } elseif ($fromType === 'road' || $fromType === 'dungeon') {
            // 道路・ダンジョンから道路・ダンジョンへの移動 - JSON設定から取得
            $fromData = null;
            // 新しい統合フォーマット対応
            if (isset($this->configData['pathways'][$fromId])) {
                $fromData = $this->configData['pathways'][$fromId];
            }
            // 後方互換性：古いフォーマット対応
            elseif (isset($this->configData['roads'][$fromId])) {
                $fromData = $this->configData['roads'][$fromId];
            } elseif (isset($this->configData['dungeons'][$fromId])) {
                $fromData = $this->configData['dungeons'][$fromId];
            }
            
            if ($fromData) {
                // 分岐情報をチェック
                if (isset($fromData['branches'])) {
                    foreach ($fromData['branches'] as $position => $branches) {
                        foreach ($branches as $direction => $destination) {
                            if (($destination['type'] === 'road' || $destination['type'] === 'dungeon') && $destination['id'] === $toRoadId) {
                                // 分岐位置からの接続の場合、指定された開始位置を使用
                                if (isset($destination['start_position'])) {
                                    return $destination['start_position'];
                                }
                                return $position; // 分岐位置をそのまま使用
                            }
                        }
                    }
                }
                
                // 通常の接続情報をチェック
                if (isset($fromData['connections'])) {
                    if (isset($fromData['connections']['start']) && 
                        in_array($fromData['connections']['start']['type'], ['road', 'dungeon']) && 
                        $fromData['connections']['start']['id'] === $toRoadId) {
                        return 100; // 始点から来た場合は終点へ
                    }
                    if (isset($fromData['connections']['end']) && 
                        in_array($fromData['connections']['end']['type'], ['road', 'dungeon']) && 
                        $fromData['connections']['end']['id'] === $toRoadId) {
                        return 0; // 終点から来た場合は始点へ
                    }
                }
            }
            
            return 0; // デフォルト
        }
        
        return 0;
    }

    /**
     * 指定位置に特別アクションがあるかチェック
     *
     * @param string $locationId
     * @param int $position
     * @return bool
     */
    public function hasSpecialActionAt(string $locationId, int $position): bool
    {
        return $this->getSpecialActionsAt($locationId, $position) !== null;
    }

    /**
     * 指定位置での特別アクションを取得
     *
     * @param string $locationId
     * @param int $position
     * @return array|null
     */
    public function getSpecialActionsAt(string $locationId, int $position): ?array
    {
        $this->loadConfigData();

        // 新しい統合フォーマット対応
        if (isset($this->configData['pathways'][$locationId]['special_actions'][$position])) {
            return $this->configData['pathways'][$locationId]['special_actions'][$position];
        }

        // 後方互換性：古いフォーマット対応
        if (isset($this->configData['roads'][$locationId]['special_actions'][$position])) {
            return $this->configData['roads'][$locationId]['special_actions'][$position];
        }
        
        if (isset($this->configData['dungeons'][$locationId]['special_actions'][$position])) {
            return $this->configData['dungeons'][$locationId]['special_actions'][$position];
        }

        return null;
    }

    /**
     * 特別アクションの選択肢を取得
     *
     * @param string $locationId
     * @param int $position
     * @return array|null
     */
    public function getSpecialActionOptions(string $locationId, int $position): ?array
    {
        $action = $this->getSpecialActionsAt($locationId, $position);
        if ($action === null) {
            return null;
        }

        return [
            'type' => $action['type'],
            'name' => $action['name'],
            'condition' => $action['condition'] ?? 'none',
            'action' => $action['action'],
            'data' => $action['data'] ?? [],
            'position' => $position,
            'location_id' => $locationId
        ];
    }

    /**
     * 特別アクションが実行可能かチェック
     *
     * @param Player $player
     * @param array $action
     * @return bool
     */
    public function canExecuteSpecialAction(Player $player, array $action): bool
    {
        $condition = $action['condition'] ?? 'none';
        
        switch ($condition) {
            case 'none':
                return true;
                
            case 'key_required':
                // プレイヤーが必要なキーを持っているかチェック
                $requiredItem = $action['data']['required_item'] ?? '';
                if ($requiredItem) {
                    // TODO: プレイヤーのインベントリをチェック
                    // 現在は簡易実装
                    return true;
                }
                return true;
                
            case 'level_required':
                $minLevel = $action['data']['min_level'] ?? 1;
                return $player->level >= $minLevel;
                
            default:
                return false;
        }
    }

    /**
     * 特別アクション実行の結果を取得
     *
     * @param Player $player
     * @param array $action
     * @return array
     */
    public function executeSpecialAction(Player $player, array $action): array
    {
        if (!$this->canExecuteSpecialAction($player, $action)) {
            return [
                'success' => false,
                'message' => 'このアクションを実行する条件を満たしていません',
                'type' => 'error'
            ];
        }

        $actionType = $action['action'] ?? '';
        $data = $action['data'] ?? [];

        switch ($actionType) {
            case 'boss_fight':
                return [
                    'success' => true,
                    'type' => 'boss_battle',
                    'message' => $action['name'] . 'との戦闘が始まります！',
                    'data' => [
                        'boss' => $data['boss'] ?? 'Unknown Boss',
                        'min_level' => $data['min_level'] ?? 1,
                        'rewards' => $data['rewards'] ?? []
                    ]
                ];

            case 'get_treasure':
                return [
                    'success' => true,
                    'type' => 'treasure',
                    'message' => $action['name'] . 'を開けました！',
                    'data' => [
                        'items' => $data['items'] ?? []
                    ]
                ];

            case 'teleport':
                return [
                    'success' => true,
                    'type' => 'teleport',
                    'message' => $action['name'] . 'を使って移動します',
                    'data' => [
                        'destination_type' => $data['destination_type'] ?? 'town',
                        'destination_id' => $data['destination_id'] ?? 'town_prima'
                    ]
                ];

            case 'shop_access':
                return [
                    'success' => true,
                    'type' => 'shop',
                    'message' => $action['name'] . 'を利用できます',
                    'data' => [
                        'items' => $data['items'] ?? []
                    ]
                ];

            case 'rest_recovery':
                return [
                    'success' => true,
                    'type' => 'rest',
                    'message' => $action['name'] . 'で休息を取りました',
                    'data' => [
                        'hp_recovery' => $data['hp_recovery'] ?? 0,
                        'sp_recovery' => $data['sp_recovery'] ?? 0
                    ]
                ];

            default:
                return [
                    'success' => false,
                    'message' => '不明なアクションです',
                    'type' => 'error'
                ];
        }
    }

}