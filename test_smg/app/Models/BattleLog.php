<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleLog extends Model
{
    protected $fillable = [
        'user_id',
        'monster_name',
        'location',
        'result',
        'experience_gained',
        'gold_lost',
        'turns',
        'battle_data',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'experience_gained' => 'integer',
        'gold_lost' => 'integer',
        'turns' => 'integer',
        'battle_data' => 'array',
    ];

    // リレーション
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // スコープ
    public function scopeVictories($query)
    {
        return $query->where('result', 'victory');
    }

    public function scopeDefeats($query)
    {
        return $query->where('result', 'defeat');
    }

    public function scopeEscapes($query)
    {
        return $query->where('result', 'escaped');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // 戦闘ログ作成
    public static function createLog(int $userId, array $battleData): self
    {
        return self::create([
            'user_id' => $userId,
            'monster_name' => $battleData['monster']['name'] ?? 'Unknown Monster',
            'location' => $battleData['location'] ?? 'Unknown Location',
            'result' => $battleData['result'] ?? 'unknown',
            'experience_gained' => $battleData['experience_gained'] ?? 0,
            'gold_lost' => $battleData['gold_lost'] ?? 0,
            'turns' => $battleData['turns'] ?? 1,
            'battle_data' => $battleData,
        ]);
    }

    // ユーザーの戦闘統計を取得
    public static function getUserStats(int $userId): array
    {
        $logs = self::forUser($userId);

        return [
            'total_battles' => $logs->count(),
            'victories' => $logs->victories()->count(),
            'defeats' => $logs->defeats()->count(),
            'escapes' => $logs->escapes()->count(),
            'total_experience' => $logs->sum('experience_gained'),
            'total_gold_lost' => $logs->sum('gold_lost'),
            'average_turns' => round($logs->avg('turns'), 1),
            'win_rate' => $logs->count() > 0 ? round(($logs->victories()->count() / $logs->count()) * 100, 1) : 0,
        ];
    }

    // 詳細戦闘分析を取得
    public static function getUserBattleAnalytics(int $userId): array
    {
        $logs = self::forUser($userId)->get();
        
        if ($logs->isEmpty()) {
            return [
                'total_battles' => 0,
                'performance_metrics' => [],
                'location_analysis' => [],
                'monster_analysis' => [],
                'time_analysis' => [],
                'difficulty_progression' => [],
            ];
        }

        // パフォーマンス指標
        $performanceMetrics = [
            'win_rate' => round(($logs->where('result', 'victory')->count() / $logs->count()) * 100, 1),
            'escape_rate' => round(($logs->where('result', 'escaped')->count() / $logs->count()) * 100, 1),
            'average_turns_per_battle' => round($logs->avg('turns'), 1),
            'experience_per_battle' => round($logs->avg('experience_gained'), 1),
            'gold_loss_per_battle' => round($logs->avg('gold_lost'), 1),
        ];

        // 場所別分析
        $locationAnalysis = $logs->groupBy('location')
                                ->map(function ($locationLogs) {
                                    return [
                                        'battles' => $locationLogs->count(),
                                        'win_rate' => round(($locationLogs->where('result', 'victory')->count() / $locationLogs->count()) * 100, 1),
                                        'avg_turns' => round($locationLogs->avg('turns'), 1),
                                        'total_experience' => $locationLogs->sum('experience_gained'),
                                    ];
                                })
                                ->sortByDesc('battles')
                                ->take(5);

        // モンスター別分析
        $monsterAnalysis = $logs->groupBy('monster_name')
                               ->map(function ($monsterLogs) {
                                   return [
                                       'encounters' => $monsterLogs->count(),
                                       'win_rate' => round(($monsterLogs->where('result', 'victory')->count() / $monsterLogs->count()) * 100, 1),
                                       'avg_turns' => round($monsterLogs->avg('turns'), 1),
                                       'total_experience' => $monsterLogs->sum('experience_gained'),
                                       'difficulty_rating' => self::calculateDifficultyRating($monsterLogs),
                                   ];
                               })
                               ->sortByDesc('encounters')
                               ->take(10);

        // 時間帯別分析
        $timeAnalysis = $logs->groupBy(function ($log) {
                                 return $log->created_at->format('H');
                             })
                             ->map(function ($timeLogs) {
                                 return [
                                     'battles' => $timeLogs->count(),
                                     'win_rate' => round(($timeLogs->where('result', 'victory')->count() / $timeLogs->count()) * 100, 1),
                                     'avg_experience' => round($timeLogs->avg('experience_gained'), 1),
                                 ];
                             })
                             ->sortKeys();

        // 難易度進行分析（最近30戦）
        $recentBattles = $logs->sortByDesc('created_at')->take(30);
        $difficultyProgression = [
            'recent_win_rate' => $recentBattles->count() > 0 ? 
                                round(($recentBattles->where('result', 'victory')->count() / $recentBattles->count()) * 100, 1) : 0,
            'turn_efficiency_trend' => self::calculateTurnEfficiencyTrend($recentBattles),
            'experience_trend' => self::calculateExperienceTrend($recentBattles),
        ];

        return [
            'total_battles' => $logs->count(),
            'performance_metrics' => $performanceMetrics,
            'location_analysis' => $locationAnalysis->toArray(),
            'monster_analysis' => $monsterAnalysis->toArray(),
            'time_analysis' => $timeAnalysis->toArray(),
            'difficulty_progression' => $difficultyProgression,
            'last_battle' => $logs->sortByDesc('created_at')->first()?->created_at,
        ];
    }

    // グローバル戦闘統計を取得
    public static function getGlobalBattleAnalytics(): array
    {
        $totalBattles = self::count();
        $totalUsers = self::distinct('user_id')->count();
        
        if ($totalBattles === 0) {
            return [
                'total_battles' => 0,
                'active_users' => 0,
                'global_metrics' => [],
                'popular_locations' => [],
                'challenging_monsters' => [],
            ];
        }

        $globalMetrics = [
            'total_battles' => $totalBattles,
            'active_users' => $totalUsers,
            'global_win_rate' => round((self::victories()->count() / $totalBattles) * 100, 1),
            'average_battle_duration' => round(self::avg('turns'), 1),
            'total_experience_earned' => self::sum('experience_gained'),
            'total_gold_lost' => self::sum('gold_lost'),
        ];

        $popularLocations = self::select('location')
                               ->selectRaw('COUNT(*) as battle_count')
                               ->selectRaw('AVG(CASE WHEN result = "victory" THEN 1 ELSE 0 END) * 100 as win_rate')
                               ->groupBy('location')
                               ->orderByDesc('battle_count')
                               ->limit(5)
                               ->get();

        $challengingMonsters = self::select('monster_name')
                                  ->selectRaw('COUNT(*) as encounter_count')
                                  ->selectRaw('AVG(CASE WHEN result = "victory" THEN 1 ELSE 0 END) * 100 as win_rate')
                                  ->selectRaw('AVG(turns) as avg_turns')
                                  ->groupBy('monster_name')
                                  ->having('encounter_count', '>=', 10)
                                  ->orderBy('win_rate', 'asc')
                                  ->limit(5)
                                  ->get();

        return [
            'total_battles' => $totalBattles,
            'active_users' => $totalUsers,
            'global_metrics' => $globalMetrics,
            'popular_locations' => $popularLocations->toArray(),
            'challenging_monsters' => $challengingMonsters->toArray(),
        ];
    }

    // 難易度評価を計算
    private static function calculateDifficultyRating($logs): string
    {
        $winRate = ($logs->where('result', 'victory')->count() / $logs->count()) * 100;
        $avgTurns = $logs->avg('turns');

        if ($winRate >= 80 && $avgTurns <= 3) return 'Easy';
        if ($winRate >= 60 && $avgTurns <= 5) return 'Normal';
        if ($winRate >= 40 && $avgTurns <= 8) return 'Hard';
        return 'Very Hard';
    }

    // ターン効率トレンドを計算
    private static function calculateTurnEfficiencyTrend($battles): string
    {
        if ($battles->count() < 10) return 'Insufficient Data';
        
        $firstHalf = $battles->slice(0, ceil($battles->count() / 2));
        $secondHalf = $battles->slice(ceil($battles->count() / 2));
        
        $firstAvg = $firstHalf->avg('turns');
        $secondAvg = $secondHalf->avg('turns');
        
        if ($secondAvg < $firstAvg * 0.9) return 'Improving';
        if ($secondAvg > $firstAvg * 1.1) return 'Declining';
        return 'Stable';
    }

    // 経験値獲得トレンドを計算
    private static function calculateExperienceTrend($battles): string
    {
        if ($battles->count() < 10) return 'Insufficient Data';
        
        $firstHalf = $battles->slice(0, ceil($battles->count() / 2));
        $secondHalf = $battles->slice(ceil($battles->count() / 2));
        
        $firstAvg = $firstHalf->avg('experience_gained');
        $secondAvg = $secondHalf->avg('experience_gained');
        
        if ($secondAvg > $firstAvg * 1.1) return 'Increasing';
        if ($secondAvg < $firstAvg * 0.9) return 'Decreasing';
        return 'Stable';
    }
}
