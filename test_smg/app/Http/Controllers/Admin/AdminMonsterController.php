<?php

namespace App\Http\Controllers\Admin;

use App\Models\Monster;
use App\Services\Admin\AdminAuditService;
use App\Services\Monster\MonsterConfigService;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminMonsterController extends AdminController
{
    private MonsterConfigService $monsterConfigService;

    public function __construct(AdminAuditService $auditService, MonsterConfigService $monsterConfigService)
    {
        parent::__construct($auditService);
        $this->monsterConfigService = $monsterConfigService;
        // Laravel 11では、コンストラクタ内でのmiddleware()は使用できません
        // 各メソッド内で権限チェックを実行します
    }

    /**
     * モンスター一覧表示
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.view');
        
        $filters = $request->only(['search', 'road', 'min_level', 'max_level', 'sort_by', 'sort_direction']);
        
        // 動的データの取得（JSONベース・スポーン情報統合）
        $allMonsters = $this->monsterConfigService->getActiveMonstersWithSpawnInfo();
        
        // フィルタリング処理
        $filteredMonsters = collect($allMonsters);
        
        // 検索フィルター
        if (!empty($filters['search'])) {
            $filteredMonsters = $filteredMonsters->filter(function($monster) use ($filters) {
                return stripos($monster['name'], $filters['search']) !== false ||
                       stripos($monster['description'], $filters['search']) !== false;
            });
        }
        
        // 道路フィルター
        if (!empty($filters['road'])) {
            $filteredMonsters = $filteredMonsters->filter(function($monster) use ($filters) {
                return in_array($filters['road'], $monster['spawn_roads'] ?? []);
            });
        }
        
        // レベル範囲フィルター
        if (!empty($filters['min_level'])) {
            $filteredMonsters = $filteredMonsters->filter(function($monster) use ($filters) {
                return $monster['level'] >= $filters['min_level'];
            });
        }
        if (!empty($filters['max_level'])) {
            $filteredMonsters = $filteredMonsters->filter(function($monster) use ($filters) {
                return $monster['level'] <= $filters['max_level'];
            });
        }
        
        // ソート
        $sortBy = $filters['sort_by'] ?? 'level';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        
        $filteredMonsters = $filteredMonsters->sortBy($sortBy, SORT_REGULAR, $sortDirection === 'desc');
        
        // ページネーション（手動実装）
        $perPage = 20;
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        $paginatedMonsters = $filteredMonsters->slice($offset, $perPage)->values();
        $totalCount = $filteredMonsters->count();
        
        // 統計データ
        $stats = $this->generateMonsterStats($allMonsters);
        
        // 道路情報
        $roads = $this->monsterConfigService->getAvailablePathways();
        
        $this->auditLog('monsters.index.viewed', [
            'filters' => $filters,
            'result_count' => $totalCount
        ]);
        
        return view('admin.monsters.index', compact(
            'paginatedMonsters', 
            'stats', 
            'filters', 
            'sortBy', 
            'sortDirection',
            'roads',
            'totalCount',
            'page',
            'perPage'
        ));
    }

    /**
     * モンスター詳細表示
     */
    public function show($monsterId)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.view');
        
        $allMonsters = $this->monsterConfigService->getActiveMonstersWithSpawnInfo();
        $monster = collect($allMonsters)->firstWhere('name', $monsterId) 
                  ?? collect($allMonsters)->where('id', $monsterId)->first();
        
        if (!$monster) {
            return redirect()->route('admin.monsters.index')
                           ->with('error', 'モンスターが見つかりませんでした。');
        }
        
        // バランス分析
        $balanceAnalysis = $this->analyzeMonsterBalance($monster);
        
        // 遭遇統計（仮想データ）
        $encounterStats = $this->getMonsterEncounterStats($monster);
        
        // 関連モンスター（同じ道路・レベル帯）
        $relatedMonsters = $this->getRelatedMonsters($monster, $allMonsters);
        
        $this->auditLog('monsters.show.viewed', [
            'monster_name' => $monster['name'],
            'monster_id' => $monster['id'] ?? $monsterId
        ]);
        
        return view('admin.monsters.show', compact(
            'monster', 
            'balanceAnalysis', 
            'encounterStats', 
            'relatedMonsters'
        ));
    }

    /**
     * モンスター編集フォーム
     */
    public function edit($monsterId)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.edit');
        
        $allMonsters = $this->monsterConfigService->getActiveMonstersWithSpawnInfo();
        $monster = collect($allMonsters)->firstWhere('name', $monsterId) 
                  ?? collect($allMonsters)->where('id', $monsterId)->first();
        
        if (!$monster) {
            return redirect()->route('admin.monsters.index')
                           ->with('error', 'モンスターが見つかりませんでした。');
        }
        
        // 利用可能な道路
        $roads = $this->monsterConfigService->getAvailablePathways();
        
        return view('admin.monsters.edit', compact('monster', 'roads'));
    }

    /**
     * モンスター更新
     * 注意: 実際の実装では設定ファイルやデータベースに保存する必要があります
     */
    public function update(Request $request, $monsterId)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.edit');
        
        $validator = $this->validateMonsterData($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            // 現在の実装では動的データ更新は制限されているため、
            // 設定変更をキャッシュに保存して疑似的に実装
            $changes = $request->only([
                'name', 'level', 'hp', 'max_hp', 'attack', 'defense', 'agility',
                'evasion', 'accuracy', 'experience_reward', 'description', 
                'spawn_roads', 'spawn_rate'
            ]);
            
            // 変更内容をキャッシュに保存（実際の実装では永続化が必要）
            $cacheKey = "monster_overrides.{$monsterId}";
            Cache::put($cacheKey, $changes, now()->addDays(30));
            
            $this->auditLog('monsters.updated', [
                'monster_id' => $monsterId,
                'changes' => $changes
            ], 'high');
            
            return redirect()->route('admin.monsters.show', $monsterId)
                           ->with('success', 'モンスター設定が更新されました。');
            
        } catch (\Exception $e) {
            $this->auditLog('monsters.update.failed', [
                'monster_id' => $monsterId,
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()->withError('モンスターの更新に失敗しました: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * 出現率調整
     */
    public function updateSpawnRates(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.edit');
        
        $request->validate([
            'road' => 'required|string',
            'spawn_rates' => 'required|array',
            'spawn_rates.*' => 'required|numeric|min:0|max:1',
        ]);
        
        try {
            $road = $request->road;
            $spawnRates = $request->spawn_rates;
            
            // 出現率の合計が1.0を超えないかチェック
            $totalRate = array_sum($spawnRates);
            if ($totalRate > 1.0) {
                return back()->withError('出現率の合計が1.0を超えています。現在の合計: ' . $totalRate);
            }
            
            // キャッシュに保存
            $cacheKey = "spawn_rates.{$road}";
            Cache::put($cacheKey, $spawnRates, now()->addDays(30));
            
            $this->auditLog('monsters.spawn_rates.updated', [
                'road' => $road,
                'spawn_rates' => $spawnRates,
                'total_rate' => $totalRate
            ], 'high');
            
            return back()->with('success', "{$road}の出現率を更新しました。");
            
        } catch (\Exception $e) {
            $this->auditLog('monsters.spawn_rates.update.failed', [
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()->withError('出現率の更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * バランス調整（一括ステータス調整）
     */
    public function balanceAdjustment(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.edit');
        
        $request->validate([
            'adjustment_type' => 'required|in:level_range,road_based,global',
            'target_level_min' => 'nullable|integer|min:1',
            'target_level_max' => 'nullable|integer|min:1',
            'target_roads' => 'nullable|array',
            'stat_adjustments' => 'required|array',
            'adjustment_method' => 'required|in:multiply,add,set',
        ]);
        
        try {
            $adjustmentType = $request->adjustment_type;
            $adjustments = $request->stat_adjustments;
            $method = $request->adjustment_method;
            
            $allMonsters = $this->monsterConfigService->getActiveMonstersWithSpawnInfo();
            $affectedMonsters = [];
            
            // 対象モンスターの特定
            foreach ($allMonsters as $index => $monster) {
                $shouldAdjust = false;
                
                switch ($adjustmentType) {
                    case 'level_range':
                        $shouldAdjust = $monster['level'] >= ($request->target_level_min ?? 1) 
                                     && $monster['level'] <= ($request->target_level_max ?? 99);
                        break;
                    
                    case 'road_based':
                        $targetRoads = $request->target_roads ?? [];
                        $shouldAdjust = !empty(array_intersect($monster['spawn_roads'] ?? [], $targetRoads));
                        break;
                    
                    case 'global':
                        $shouldAdjust = true;
                        break;
                }
                
                if ($shouldAdjust) {
                    $adjustedMonster = $this->applyStatAdjustments($monster, $adjustments, $method);
                    $cacheKey = "monster_overrides.{$monster['name']}";
                    Cache::put($cacheKey, $adjustedMonster, now()->addDays(30));
                    $affectedMonsters[] = $monster['name'];
                }
            }
            
            $this->auditLog('monsters.balance_adjustment', [
                'adjustment_type' => $adjustmentType,
                'affected_monsters' => $affectedMonsters,
                'adjustments' => $adjustments,
                'method' => $method
            ], 'critical');
            
            return back()->with('success', count($affectedMonsters) . '体のモンスターのバランスを調整しました。');
            
        } catch (\Exception $e) {
            $this->auditLog('monsters.balance_adjustment.failed', [
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()->withError('バランス調整に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 統計データの生成
     */
    private function generateMonsterStats(array $monsters): array
    {
        $stats = [
            'total_monsters' => count($monsters),
            'avg_level' => collect($monsters)->avg('level'),
            'level_distribution' => [],
            'road_distribution' => [],
            'avg_stats' => [],
        ];
        
        // レベル分布
        $levelGroups = collect($monsters)->groupBy(function($monster) {
            return floor($monster['level'] / 5) * 5; // 5レベル刻み
        });
        
        foreach ($levelGroups as $levelRange => $monsters) {
            $stats['level_distribution'][$levelRange . '-' . ($levelRange + 4)] = count($monsters);
        }
        
        // 道路別分布
        $roadCounts = [];
        foreach ($monsters as $monster) {
            foreach ($monster['spawn_roads'] ?? [] as $road) {
                $roadCounts[$road] = ($roadCounts[$road] ?? 0) + 1;
            }
        }
        $stats['road_distribution'] = $roadCounts;
        
        // 平均ステータス
        $stats['avg_stats'] = [
            'hp' => collect($monsters)->avg('max_hp'),
            'attack' => collect($monsters)->avg('attack'),
            'defense' => collect($monsters)->avg('defense'),
            'experience' => collect($monsters)->avg('experience_reward'),
        ];
        
        return $stats;
    }

    /**
     * 利用可能な道路の取得（統合データ対応）
     */
    private function getAvailableRoads(array $monsters): array
    {
        $roads = [];
        foreach ($monsters as $monster) {
            foreach ($monster['spawn_roads'] ?? [] as $road) {
                if (!in_array($road, $roads)) {
                    $roads[] = $road;
                }
            }
        }
        sort($roads);
        return $roads;
    }

    /**
     * モンスターバランス分析
     */
    private function analyzeMonsterBalance(array $monster): array
    {
        $level = $monster['level'];
        
        // レベルに対する期待値の計算
        $expectedStats = [
            'hp' => 20 + ($level * 10),
            'attack' => 8 + ($level * 2),
            'defense' => 3 + ($level * 1.5),
            'experience' => 10 + ($level * 10),
        ];
        
        $analysis = [
            'balance_score' => 0,
            'stat_ratios' => [],
            'recommendations' => [],
        ];
        
        // バランススコアの計算
        $deviations = [];
        foreach ($expectedStats as $stat => $expected) {
            $actual = $monster[$stat === 'hp' ? 'max_hp' : $stat];
            $ratio = $actual / $expected;
            $analysis['stat_ratios'][$stat] = $ratio;
            $deviations[] = abs($ratio - 1.0);
        }
        
        $analysis['balance_score'] = max(0, 100 - (array_sum($deviations) / count($deviations) * 100));
        
        // 推奨事項の生成
        foreach ($analysis['stat_ratios'] as $stat => $ratio) {
            if ($ratio > 1.3) {
                $analysis['recommendations'][] = "{$stat}が高すぎます（期待値の" . round($ratio * 100) . "%）";
            } elseif ($ratio < 0.7) {
                $analysis['recommendations'][] = "{$stat}が低すぎます（期待値の" . round($ratio * 100) . "%）";
            }
        }
        
        return $analysis;
    }

    /**
     * モンスター遭遇統計の取得
     */
    private function getMonsterEncounterStats(array $monster): array
    {
        return [
            'encounter_count' => rand(50, 500),
            'defeat_rate' => rand(60, 95) / 100,
            'avg_battle_duration' => rand(30, 180),
            'player_level_when_encountered' => rand($monster['level'] - 2, $monster['level'] + 3),
        ];
    }

    /**
     * 関連モンスターの取得
     */
    private function getRelatedMonsters(array $monster, array $allMonsters): array
    {
        return collect($allMonsters)
            ->filter(function($m) use ($monster) {
                return $m['name'] !== $monster['name'] && (
                    abs($m['level'] - $monster['level']) <= 2 ||
                    !empty(array_intersect($m['spawn_roads'] ?? [], $monster['spawn_roads'] ?? []))
                );
            })
            ->take(5)
            ->values()
            ->toArray();
    }

    /**
     * モンスターデータのバリデーション
     */
    private function validateMonsterData(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1|max:100',
            'hp' => 'required|integer|min:1|max:9999',
            'max_hp' => 'required|integer|min:1|max:9999',
            'attack' => 'required|integer|min:1|max:999',
            'defense' => 'required|integer|min:0|max:999',
            'agility' => 'required|integer|min:1|max:999',
            'evasion' => 'required|integer|min:0|max:100',
            'accuracy' => 'required|integer|min:0|max:100',
            'experience_reward' => 'required|integer|min:0|max:9999',
            'description' => 'nullable|string|max:1000',
            'spawn_roads' => 'required|array',
            'spawn_roads.*' => 'string',
            'spawn_rate' => 'required|numeric|min:0|max:1',
        ]);
    }

    /**
     * ステータス調整の適用
     */
    private function applyStatAdjustments(array $monster, array $adjustments, string $method): array
    {
        foreach ($adjustments as $stat => $value) {
            if (!isset($monster[$stat]) || empty($value)) continue;
            
            $currentValue = $monster[$stat];
            
            switch ($method) {
                case 'multiply':
                    $monster[$stat] = round($currentValue * $value);
                    break;
                case 'add':
                    $monster[$stat] = $currentValue + $value;
                    break;
                case 'set':
                    $monster[$stat] = $value;
                    break;
            }
            
            // 負の値や異常値の防止
            if (in_array($stat, ['hp', 'max_hp', 'attack', 'level'])) {
                $monster[$stat] = max(1, $monster[$stat]);
            } else {
                $monster[$stat] = max(0, $monster[$stat]);
            }
        }
        
        return $monster;
    }
}