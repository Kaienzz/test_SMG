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
     * JSON設定データ
     * @var array
     */
    private array $configData = [];

    /**
     * 設定データロード済みフラグ
     * @var bool
     */
    private bool $dataLoaded = false;

    /**
     * JSON設定データを読み込む（SQLite使用時はスキップ）
     * @return void
     */
    private function loadConfigData(): void
    {
        if ($this->dataLoaded) {
            return;
        }
        
        // SQLite使用時は空の配列で初期化（JSON設定は使用しない）
        $this->configData = [
            'towns' => [],
            'roads' => [],
            'dungeons' => [],
            'pathways' => []
        ];
        
        $this->dataLoaded = true;
    }

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
     * 分岐設定を取得（新スキーマ対応）
     * @deprecated Use getAvailableConnectionsWithData() for consistent connection handling
     *
     * @param string $locationId
     * @param int $position
     * @return array|null
     */
    private function getBranchesFromConfig(string $locationId, int $position): ?array
    {
        try {
            // 新スキーマ: 指定位置に接続があれば分岐として扱う（edge_typeは問わない）
            $connections = RouteConnection::where('source_location_id', $locationId)
                                           ->where('source_position', $position)
                                           ->enabled()
                                           ->with('targetLocation')
                                           ->get();
            
            if ($connections->isNotEmpty()) {
                $branches = [];
                foreach ($connections as $connection) {
                    if ($connection->targetLocation) {
                        // action_labelを方向キーとして使用
                        $direction = $connection->action_label ?: 'default';
                        $branches[$direction] = [
                            'type' => $connection->targetLocation->category,
                            'id' => $connection->targetLocation->id
                        ];
                    }
                }
                return !empty($branches) ? $branches : null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get branches from new schema', [
                'location_id' => $locationId,
                'position' => $position,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * 町の接続設定を取得（新スキーマ対応）
     * @deprecated Use getAvailableConnectionsWithData() for consistent connection handling
     *
     * @param string $townId
     * @return array|null
     */
    private function getTownConnectionsFromConfig(string $townId): ?array
    {
        try {
            // 新スキーマ: 町の接続はsource_position=NULLで取得
            $connections = RouteConnection::where('source_location_id', $townId)
                                           ->whereNull('source_position')
                                           ->enabled()
                                           ->with('targetLocation')
                                           ->get();
            
            if ($connections->isNotEmpty()) {
                $townConnections = [];
                foreach ($connections as $connection) {
                    if ($connection->targetLocation) {
                        // action_labelがあればそれを使用、なければdefaultキー
                        $key = $connection->action_label ?: 'default';
                        $townConnections[$key] = [
                            'type' => $connection->targetLocation->category,
                            'id' => $connection->targetLocation->id
                        ];
                    }
                }
                return !empty($townConnections) ? $townConnections : null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get town connections from new schema', [
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
            // 現在位置が50でない場合、移動によって50に到達する、または50を通過する場合は50で強制停止
            if ($currentPosition != 50 && 
                (($currentPosition < 50 && $newPosition >= 50) || 
                 ($currentPosition > 50 && $newPosition <= 50))) {
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
     * 道路からの次の位置を取得（新スキーマ対応）
     * @deprecated Use getAvailableConnectionsWithData() for multiple connections support
     *
     * @param string $locationId
     * @param int $position
     * @return array{type: string, id: string, name: string}|null
     */
    private function getNextLocationFromRoad(string $locationId, int $position): ?array
    {
        try {
            // 新スキーマ: source_positionベースで接続を取得
            $connection = RouteConnection::where('source_location_id', $locationId)
                                       ->where('source_position', $position)
                                       ->enabled()
                                       ->with('targetLocation')
                                       ->first();
            
            if ($connection && $connection->targetLocation) {
                return [
                    'type' => $connection->targetLocation->category,
                    'id' => $connection->targetLocation->id,
                    'name' => $connection->targetLocation->name
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get road connections from new schema, falling back to JSON', [
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

            case 'facility_access':
                return [
                    'success' => true,
                    'type' => 'facility',
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
    
    /**
     * Get available connections for current player position (New Logic)
     *
     * @param Player $player
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableConnections(Player $player): \Illuminate\Database\Eloquent\Collection
    {
        $currentLocationId = $player->location_id;
        $currentPosition = $player->game_position ?? 0;
        
        // Get all potential connections from current location
        $connections = RouteConnection::where('source_location_id', $currentLocationId)
                                     ->enabled()
                                     ->with(['targetLocation'])
                                     ->get();
        
        // Filter connections based on position rules
        return $connections->filter(function ($connection) use ($currentPosition) {
            return $this->shouldShowConnectionAtPosition($connection, $currentPosition);
        });
    }
    
    /**
     * Check if connection should be visible at current position (New Logic)
     *
     * @param RouteConnection $connection
     * @param int $currentPosition
     * @return bool
     */
    public function shouldShowConnectionAtPosition(RouteConnection $connection, int $currentPosition): bool
    {
        // If source_position is null (town connections), always show
        if ($connection->source_position === null) {
            return true;
        }
        
        // Ensure both values are integers for consistent comparison
        $sourcePos = (int) $connection->source_position;
        $currentPos = (int) $currentPosition;
        
        // Apply position-based visibility rules with explicit type conversion
        if ($sourcePos === 0) {
            return $currentPos <= 0;
        }
        
        if ($sourcePos === 100) {
            return $currentPos >= 100;
        }
        
        // For all other positions (like 50), require exact match
        $match = $currentPos === $sourcePos;
        
        // Enhanced debug log for all boundary positions
        if (in_array($sourcePos, [0, 50, 100]) || in_array($currentPos, [0, 50, 100])) {
            \Log::info('🔍 [POSITION] shouldShowConnectionAtPosition debug (explicit int conversion)', [
                'connection_id' => $connection->id,
                'original_source_position' => $connection->source_position,
                'original_source_position_type' => gettype($connection->source_position),
                'converted_source_position' => $sourcePos,
                'original_current_position' => $currentPosition,
                'original_current_position_type' => gettype($currentPosition),
                'converted_current_position' => $currentPos,
                'match' => $match,
                'is_boundary_0' => $sourcePos === 0,
                'is_boundary_100' => $sourcePos === 100,
                'final_result' => (
                    ($sourcePos === 0 && $currentPos <= 0) ||
                    ($sourcePos === 100 && $currentPos >= 100) ||
                    ($sourcePos !== 0 && $sourcePos !== 100 && $match)
                )
            ]);
        }
        
        // Use explicit int comparison for reliable results
        return $match;
    }
    
    /**
     * Get available connections with enhanced data (New Logic)
     *
     * @param Player $player
     * @return array
     */
    public function getAvailableConnectionsWithData(Player $player): array
    {
        $connections = $this->getAvailableConnections($player);
        
        $result = [];
        foreach ($connections as $connection) {
            $result[] = [
                'id' => $connection->id,
                'target_location_id' => $connection->target_location_id,
                'target_location' => $connection->targetLocation,
                'source_position' => $connection->source_position,
                'target_position' => $connection->target_position,
                'action_label' => $connection->action_label,
                'keyboard_shortcut' => $connection->keyboard_shortcut,
                'edge_type' => $connection->edge_type,
                'is_enabled' => $connection->is_enabled,
                'action_text' => $this->getActionText($connection),
                'keyboard_display' => $this->getKeyboardDisplay($connection->keyboard_shortcut)
            ];
        }
        
        return $result;
    }
    
    /**
     * Get action text for connection
     *
     * @param RouteConnection $connection
     * @return string
     */
    private function getActionText(RouteConnection $connection): string
    {
        if ($connection->action_label && class_exists('App\Helpers\ActionLabel')) {
            return \App\Helpers\ActionLabel::getActionLabelText(
                $connection->action_label,
                $connection->targetLocation?->name
            );
        }
        
        $targetName = $connection->targetLocation?->name ?? 'Unknown';
        return "{$targetName}に移動する";
    }
    
    /**
     * Get keyboard shortcut display
     *
     * @param string|null $keyboardShortcut
     * @return string|null
     */
    private function getKeyboardDisplay(?string $keyboardShortcut): ?string
    {
        if (!$keyboardShortcut) {
            return null;
        }
        
        return match($keyboardShortcut) {
            'up' => '↑',
            'down' => '↓',
            'left' => '←', 
            'right' => '→',
            default => strtoupper($keyboardShortcut)
        };
    }
    
    /**
     * Move player using keyboard shortcut (New Logic)
     *
     * @param Player $player
     * @param string $keyboardShortcut
     * @return array
     */
    public function moveByKeyboard(Player $player, string $keyboardShortcut): array
    {
        $availableConnections = $this->getAvailableConnections($player);
        
        // キーボードショートカットに対応する接続を検索
        $connection = $availableConnections->first(function ($conn) use ($keyboardShortcut) {
            return $conn->keyboard_shortcut === $keyboardShortcut;
        });
        
        if (!$connection) {
            return [
                'success' => false,
                'error' => "キーボードショートカット '{$keyboardShortcut}' に対応する移動先が見つかりません"
            ];
        }
        
        return $this->moveToConnectionTarget($player, $connection->id);
    }

    /**
     * Move player to target location (Enhanced)
     *
     * @param Player $player
     * @param string $connectionId
     * @return array
     */
    public function moveToConnectionTarget(Player $player, string $connectionId): array
    {
        try {
            $connection = RouteConnection::with(['targetLocation'])->find($connectionId);
            
            if (!$connection) {
                return [
                    'success' => false,
                    'error' => '接続が見つかりません'
                ];
            }
            
            // Verify player is in the correct location for this connection
            if ($player->location_id !== $connection->source_location_id) {
                \Log::warning('🔍 [MOVE] Location mismatch', [
                    'connection_id' => $connectionId,
                    'player_id' => $player->id,
                    'player_location' => $player->location_id,
                    'connection_source_location' => $connection->source_location_id,
                    'mismatch' => true
                ]);
                return [
                    'success' => false,
                    'error' => 'プレイヤーの現在地と接続の出発地が一致しません'
                ];
            }
            
            // Refresh player data to ensure we have the latest position
            $player->refresh();
            
            // Verify connection is available at current position
            $currentPosition = $player->game_position ?? 0;
            $shouldShow = $this->shouldShowConnectionAtPosition($connection, $currentPosition);
            
            // Only log validation failures now to reduce noise
            if (!$shouldShow) {
                \Log::warning('🔍 [MOVE] Connection validation FAILED', [
                    'connection_id' => $connectionId,
                    'player_id' => $player->id,
                    'player_location' => $player->location_id,
                    'player_position' => $currentPosition,
                    'player_position_type' => gettype($currentPosition),
                    'connection_source_location' => $connection->source_location_id,
                    'connection_source_position' => $connection->source_position,
                    'connection_source_position_type' => gettype($connection->source_position),
                    'connection_target_location' => $connection->target_location_id,
                    'connection_edge_type' => $connection->edge_type,
                    'location_match' => $player->location_id === $connection->source_location_id,
                    'shouldShow' => $shouldShow,
                    'position_exact_match' => $currentPosition === $connection->source_position,
                    'position_loose_match' => $currentPosition == $connection->source_position,
                    'validation_context' => 'MOVE_TO_CONNECTION_TARGET'
                ]);
                
                return [
                    'success' => false,
                    'error' => "この接続は現在の位置では利用できません (プレイヤー: {$player->location_id}:{$currentPosition}, 接続: {$connection->source_location_id}:{$connection->source_position})"
                ];
            }
            
            if (!$connection->targetLocation) {
                return [
                    'success' => false,
                    'error' => '移動先が見つかりません'
                ];
            }
            
            // Update player position
            $player->location_type = $connection->targetLocation->category;
            $player->location_id = $connection->targetLocation->id;
            
            // Set new position based on target_position
            if ($connection->target_position !== null) {
                $player->game_position = $connection->target_position;
            } else {
                $player->game_position = 0; // Town default
            }
            
            $player->save();
            
            return [
                'success' => true,
                'destination' => [
                    'type' => $connection->targetLocation->category,
                    'id' => $connection->targetLocation->id,
                    'name' => $connection->targetLocation->name,
                    'position' => $player->game_position
                ],
                'action_performed' => $this->getActionText($connection)
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to move to connection target', [
                'player_id' => $player->id,
                'connection_id' => $connectionId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => '移動に失敗しました: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 道路の移動軸を取得
     * GameControllerから移植：left/rightをnorth/south/east/westに変換するために使用
     *
     * @param string $roadId
     * @return string 'vertical'|'horizontal'|'cross'|'mixed'
     */
    public function getRoadMovementAxis(string $roadId): string
    {
        try {
            // データベースから道路情報を取得して移動軸を確認
            $route = \App\Models\Route::where('id', $roadId)
                                    ->where('category', 'road')
                                    ->first();
            
            if ($route && !empty($route->default_movement_axis)) {
                return $route->default_movement_axis;
            }
            
            // データベースにない場合はフォールバック（パターンマッチング）
            if (str_contains($roadId, 'north') || str_contains($roadId, 'south') || str_contains($roadId, 'vertical')) {
                return 'vertical';
            } elseif (str_contains($roadId, 'east') || str_contains($roadId, 'west') || str_contains($roadId, 'horizontal')) {
                return 'horizontal';
            }
            
            // 最終デフォルト（プリマ街道など既存の垂直道路）
            return 'vertical';
            
        } catch (\Exception $e) {
            \Log::error('Failed to get road movement axis from database', [
                'road_id' => $roadId,
                'error' => $e->getMessage()
            ]);
            
            // エラー時はフォールバック
            return 'vertical';
        }
    }

    /**
     * left/rightを道路軸に応じて適切な方角に変換
     *
     * @param Player $player
     * @param string $leftRight 'left'|'right'
     * @return string 変換後の方角 ('north'|'south'|'east'|'west'|元の値)
     */
    public function convertLeftRightToDirection(Player $player, string $leftRight): string
    {
        // 道路にいない場合はそのまま返す
        if ($player->location_type !== 'road') {
            return $leftRight;
        }
        
        // 道路軸を取得
        $roadAxis = $this->getRoadMovementAxis($player->location_id);
        
        // 軸に応じて変換
        return match($roadAxis) {
            'horizontal' => match($leftRight) {
                'left' => 'west',   // 水平道路：左=西
                'right' => 'east',  // 水平道路：右=東
                default => $leftRight
            },
            'vertical' => match($leftRight) {
                'left' => 'south',  // 垂直道路：左=南（位置減少・戻る）
                'right' => 'north', // 垂直道路：右=北（位置増加・進む）
                default => $leftRight
            },
            default => $leftRight // cross/mixedは従来通り
        };
    }

    /**
     * 道路軸に応じた移動ボタン表示情報を取得
     *
     * @param string $roadId
     * @return array ['left' => ['text' => '南に移動', 'icon' => '⬇️'], 'right' => ['text' => '北に移動', 'icon' => '⬆️']]
     */
    public function getMovementButtonsInfo(string $roadId): array
    {
        $roadAxis = $this->getRoadMovementAxis($roadId);
        
        return match($roadAxis) {
            'horizontal' => [
                'left' => ['text' => '西に移動', 'icon' => '⬅️'],
                'right' => ['text' => '東に移動', 'icon' => '➡️']
            ],
            'vertical' => [
                'left' => ['text' => '南に移動', 'icon' => '⬇️'],  // 左=南（戻る）
                'right' => ['text' => '北に移動', 'icon' => '⬆️'] // 右=北（進む）
            ],
            default => [ // cross/mixed or fallback
                'left' => ['text' => '左に移動', 'icon' => '⬅️'],
                'right' => ['text' => '右に移動', 'icon' => '➡️']
            ]
        };
    }

}