<?php

namespace App\Domain\Location;

use App\Models\Player;

/**
 * 位置管理統一サービス
 * 
 * Player の位置情報に関する計算・変換を統一管理
 * GameController、GameState、Playerモデルの重複ロジックを統合
 */
class LocationService
{
    /**
     * 道路名の設定配列
     * 将来的な設定ファイル化を見据えた構造
     *
     * @var array<string, string>
     */
    private array $roadNames = [
        'road_1' => 'プリマ街道',
        'road_2' => '中央大通り', 
        'road_3' => '港湾道路',
        'road_4' => '山道',
        'road_5' => '森林道路',
        'road_6' => '商業街道',
        'road_7' => '北街道',
    ];

    /**
     * 町名の設定配列
     *
     * @var array<string, string>
     */
    private array $townNames = [
        'town_prima' => 'プリマ',
        'town_b' => 'B町',
        'town_c' => 'C町',
        'elven_village' => 'エルフの村',
        'merchant_city' => '商業都市',
    ];

    /**
     * ダンジョン名の設定配列
     *
     * @var array<string, string>
     */
    private array $dungeonNames = [
        'dungeon_1' => '古の洞窟',
        'dungeon_2' => '忘れられた遺跡',
    ];

    /**
     * T字路・交差点の分岐設定配列
     * [道路ID => [分岐位置 => [方向 => 接続先情報]]]
     *
     * @var array<string, array<int, array<string, array<string, string>>>>
     */
    private array $roadBranches = [
        'road_2' => [
            50 => [
                'straight' => ['type' => 'road', 'id' => 'road_3'],
                'right' => ['type' => 'road', 'id' => 'road_4'],
            ]
        ],
        // 将来の拡張例:
        // 'road_5' => [
        //     30 => [
        //         'straight' => ['type' => 'road', 'id' => 'road_6'],
        //         'left' => ['type' => 'town', 'id' => 'town_c'],
        //         'right' => ['type' => 'dungeon', 'id' => 'dungeon_1'],
        //     ]
        // ],
    ];

    /**
     * 町の複数接続設定配列
     * [町ID => [方向 => 接続先情報]]
     *
     * @var array<string, array<string, array<string, string>>>
     */
    private array $townConnections = [
        'town_prima' => [
            'east' => ['type' => 'road', 'id' => 'road_1'],
        ],
        'town_b' => [
            'west' => ['type' => 'road', 'id' => 'road_3'],
        ],
        'town_c' => [
            'east' => ['type' => 'road', 'id' => 'road_5'],
            'south' => ['type' => 'road', 'id' => 'road_6'],
            'north' => ['type' => 'road', 'id' => 'road_7'],
        ],
        'elven_village' => [
            'west' => ['type' => 'road', 'id' => 'road_5'],
        ],
        'merchant_city' => [
            'north' => ['type' => 'road', 'id' => 'road_6'],
        ],
    ];
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
        
        // 道路にいる場合
        if ($locationType === 'road') {
            // 位置0、50、100にいる場合は直接移動可能
            if ($position === 0 || $position === 50 || $position === 100) {
                return $this->getNextLocation($player) !== null;
            }
            
            // 位置50で分岐がある場合は直接移動可能（上記で既にチェック済みだが念のため）
            if ($position == 50 && $this->hasBranchAt($locationId, 50)) {
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
        
        // 道路にいる場合
        if ($locationType === 'road') {
            // 位置50で分岐がある場合
            if ($position == 50 && $this->hasBranchAt($locationId, 50) && $direction) {
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
        return $this->townNames[$townId] ?? '未知の町';
    }

    /**
     * 道路名を取得
     *
     * @param string $roadId
     * @return string
     */
    private function getRoadName(string $roadId): string
    {
        // カスタム道路名が設定されている場合は使用
        if (isset($this->roadNames[$roadId])) {
            return $this->roadNames[$roadId];
        }
        
        // フォールバック: 従来の自動生成名
        $roadNumber = str_replace('road_', '', $roadId);
        return '道路' . $roadNumber;
    }

    /**
     * ダンジョン名を取得
     *
     * @param string $dungeonId
     * @return string
     */
    private function getDungeonName(string $dungeonId): string
    {
        return $this->dungeonNames[$dungeonId] ?? '未知のダンジョン';
    }

    /**
     * 道路名を設定または更新
     * 将来的な動的道路追加の基盤
     *
     * @param string $roadId
     * @param string $roadName
     * @return void
     */
    public function setRoadName(string $roadId, string $roadName): void
    {
        $this->roadNames[$roadId] = $roadName;
    }

    /**
     * 町名を設定または更新
     * 将来的な動的町追加の基盤
     *
     * @param string $townId
     * @param string $townName
     * @return void
     */
    public function setTownName(string $townId, string $townName): void
    {
        $this->townNames[$townId] = $townName;
    }

    /**
     * 全ての道路名設定を取得
     * デバッグ・管理用
     *
     * @return array<string, string>
     */
    public function getAllRoadNames(): array
    {
        return $this->roadNames;
    }

    /**
     * 全ての町名設定を取得
     * デバッグ・管理用
     *
     * @return array<string, string>
     */
    public function getAllTownNames(): array
    {
        return $this->townNames;
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
        return isset($this->roadBranches[$roadId][$position]);
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
        if (!$this->hasBranchAt($roadId, $position)) {
            return null;
        }

        $branches = $this->roadBranches[$roadId][$position];
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
        if (!isset($this->townConnections[$townId])) {
            return null;
        }

        $connections = [];
        foreach ($this->townConnections[$townId] as $direction => $destination) {
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
        return isset($this->townConnections[$townId]) && count($this->townConnections[$townId]) > 1;
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
        if (!isset($this->townConnections[$townId][$direction])) {
            return null;
        }

        $destination = $this->townConnections[$townId][$direction];
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
     * 複数接続がある場合は最初の接続を返す（後方互換性のため）
     *
     * @param string $locationId
     * @return array{type: string, id: string, name: string}|null
     */
    private function getNextLocationFromTown(string $locationId): ?array
    {
        // 新しい複数接続システムから取得を試行
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

        // フォールバック: 従来の固定マッピング（下位互換性）
        $nextRoadId = match($locationId) {
            'town_prima' => 'road_1',
            'town_b' => 'road_3',
            default => null
        };
        
        if ($nextRoadId === null) {
            return null;
        }
        
        return [
            'type' => 'road',
            'id' => $nextRoadId,
            'name' => $this->getRoadName($nextRoadId)
        ];
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
            // 道路の始点での接続
            $startConnection = match($locationId) {
                'road_1' => ['type' => 'town', 'id' => 'town_prima'],
                'road_2' => ['type' => 'road', 'id' => 'road_1'], // 中央大通りからプリマ街道へ戻る
                'road_3' => ['type' => 'road', 'id' => 'road_2'],
                'road_4' => ['type' => 'road', 'id' => 'road_2'], // 山道から中央大通りへ戻る（T字路から）
                'road_5' => ['type' => 'town', 'id' => 'town_c'],
                'road_6' => ['type' => 'town', 'id' => 'town_c'],
                'road_7' => ['type' => 'town', 'id' => 'town_c'],
                default => $roadNumber === 1 ? ['type' => 'town', 'id' => 'town_prima'] : ['type' => 'road', 'id' => 'road_' . ($roadNumber - 1)]
            };
            
            return [
                'type' => $startConnection['type'],
                'id' => $startConnection['id'],
                'name' => $this->getLocationName($startConnection['type'], $startConnection['id'])
            ];
        } elseif ($position >= 100) {
            // 道路の終点での接続（論理的なマッピング）
            $endConnection = match($locationId) {
                'road_1' => ['type' => 'road', 'id' => 'road_2'], // road_1 → road_2 への接続
                'road_2' => ['type' => 'road', 'id' => 'road_3'], // road_2 → road_3 への接続
                'road_3' => ['type' => 'town', 'id' => 'town_b'], // road_3 → town_b への接続
                'road_4' => ['type' => null, 'id' => null], // 山道の終点（現在未接続）
                'road_5' => ['type' => 'town', 'id' => 'elven_village'],
                'road_6' => ['type' => 'town', 'id' => 'merchant_city'], 
                'road_7' => ['type' => null, 'id' => null], // 北街道の終点（現在未接続）
                default => $roadNumber < 3 ? ['type' => 'road', 'id' => 'road_' . ($roadNumber + 1)] : ['type' => 'town', 'id' => 'town_b']
            };
            
            if ($endConnection['type'] === null) {
                return null; // 接続先なし
            }
            
            return [
                'type' => $endConnection['type'],
                'id' => $endConnection['id'],
                'name' => $this->getLocationName($endConnection['type'], $endConnection['id'])
            ];
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

        // 道路への移動の場合、移動元によって開始位置を決定
        if ($toType === 'road') {
            return $this->calculateRoadStartPosition($fromType, $fromId, $toId);
        }

        return 0;
    }

    /**
     * 道路への移動時の開始位置を計算
     *
     * @param string $fromType 移動元の場所タイプ
     * @param string $fromId 移動元の場所ID
     * @param string $toRoadId 移動先の道路ID
     * @return int 道路での開始位置 (0 または 100)
     */
    private function calculateRoadStartPosition(string $fromType, string $fromId, string $toRoadId): int
    {
        $toRoadNumber = (int) str_replace('road_', '', $toRoadId);
        
        if ($fromType === 'town') {
            // 町からの移動 - 新しい複数接続対応
            $townPosition = match($fromId) {
                'town_prima' => 0,    // プリマから道路への移動は左端から
                'town_b' => 100,      // B町から道路への移動は右端から
                'town_c' => 0,        // C町から道路への移動は左端から（全方向）
                'elven_village' => 100, // エルフの村から道路への移動は右端から
                'merchant_city' => 100, // 商業都市から道路への移動は右端から
                default => 0
            };
            
            // 特定の道路への移動でオーバーライド
            return match([$fromId, $toRoadId]) {
                ['town_c', 'road_5'] => 0,   // C町 → 森林道路（東）
                ['town_c', 'road_6'] => 0,   // C町 → 商業街道（南）
                ['town_c', 'road_7'] => 0,   // C町 → 北街道（北）
                ['elven_village', 'road_5'] => 100, // エルフの村 → 森林道路（西）
                ['merchant_city', 'road_6'] => 100, // 商業都市 → 商業街道（北）
                default => $townPosition
            };
        } elseif ($fromType === 'road') {
            // 道路から道路への移動
            $fromRoadNumber = (int) str_replace('road_', '', $fromId);
            
            // 特定の道路間接続でオーバーライド
            $specificConnection = match([$fromId, $toRoadId]) {
                ['road_2', 'road_4'] => 50,  // T字路からの分岐（山道へ）
                default => null
            };
            
            if ($specificConnection !== null) {
                return $specificConnection;
            }
            
            // より小さい番号の道路から来る場合は左端、大きい番号から来る場合は右端
            return $fromRoadNumber < $toRoadNumber ? 0 : 100;
        }
        
        return 0;
    }

    /**
     * 場所の接続関係を定義
     * 町A ↔ 道路1 ↔ 道路2 ↔ 道路3 ↔ 町B
     *
     * @return array 接続関係の定義
     */
    public function getLocationConnections(): array
    {
        return [
            'town_prima' => [
                'type' => 'town',
                'connections' => [
                    'right' => ['type' => 'road', 'id' => 'road_1']
                ]
            ],
            'road_1' => [
                'type' => 'road',
                'connections' => [
                    'left' => ['type' => 'town', 'id' => 'town_prima'],
                    'right' => ['type' => 'road', 'id' => 'road_2']
                ]
            ],
            'road_2' => [
                'type' => 'road',
                'connections' => [
                    'left' => ['type' => 'road', 'id' => 'road_1'],
                    'right' => ['type' => 'road', 'id' => 'road_3']
                ]
            ],
            'road_3' => [
                'type' => 'road',
                'connections' => [
                    'left' => ['type' => 'road', 'id' => 'road_2'],
                    'right' => ['type' => 'town', 'id' => 'town_b']
                ]
            ],
            'town_b' => [
                'type' => 'town',
                'connections' => [
                    'left' => ['type' => 'road', 'id' => 'road_3']
                ]
            ]
        ];
    }
}