<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\GameState;
use App\Models\BattleLog;

class AnalyticsController extends Controller
{
    public function getUserAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // GameStateとBattleLogの統合分析
        $gameAnalytics = GameState::getUserAnalytics($user->id);
        $battleAnalytics = BattleLog::getUserBattleAnalytics($user->id);
        
        // 統合分析データの作成
        $integratedAnalytics = $this->createIntegratedUserAnalytics($gameAnalytics, $battleAnalytics);
        
        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'game_analytics' => $gameAnalytics,
            'battle_analytics' => $battleAnalytics,
            'integrated_analytics' => $integratedAnalytics,
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function getGlobalAnalytics(Request $request): JsonResponse
    {
        // 管理者のみアクセス可能（将来的な実装）
        // if (!Auth::user()->isAdmin()) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }
        
        $gameAnalytics = GameState::getGlobalAnalytics();
        $battleAnalytics = BattleLog::getGlobalBattleAnalytics();
        
        $integratedAnalytics = $this->createIntegratedGlobalAnalytics($gameAnalytics, $battleAnalytics);
        
        return response()->json([
            'success' => true,
            'game_analytics' => $gameAnalytics,
            'battle_analytics' => $battleAnalytics,
            'integrated_analytics' => $integratedAnalytics,
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function getUserEngagementReport(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $gameAnalytics = GameState::getUserAnalytics($user->id);
        $battleAnalytics = BattleLog::getUserBattleAnalytics($user->id);
        
        // エンゲージメント指標の計算
        $engagementMetrics = $this->calculateUserEngagementMetrics($gameAnalytics, $battleAnalytics);
        
        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'engagement_metrics' => $engagementMetrics,
            'recommendations' => $this->generateUserRecommendations($engagementMetrics),
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function getGameBalanceReport(Request $request): JsonResponse
    {
        $battleAnalytics = BattleLog::getGlobalBattleAnalytics();
        $gameAnalytics = GameState::getGlobalAnalytics();
        
        // ゲームバランス分析
        $balanceMetrics = $this->analyzeGameBalance($battleAnalytics, $gameAnalytics);
        
        return response()->json([
            'success' => true,
            'balance_metrics' => $balanceMetrics,
            'balance_recommendations' => $this->generateBalanceRecommendations($balanceMetrics),
            'generated_at' => now()->toISOString(),
        ]);
    }

    // ユーザー統合分析の作成
    private function createIntegratedUserAnalytics(array $gameAnalytics, array $battleAnalytics): array
    {
        $totalSessions = $gameAnalytics['total_sessions'] ?? 0;
        $totalBattles = $battleAnalytics['total_battles'] ?? 0;
        
        return [
            'engagement_score' => $this->calculateEngagementScore($gameAnalytics, $battleAnalytics),
            'skill_progression_rate' => $this->calculateSkillProgressionRate($battleAnalytics),
            'battle_to_session_ratio' => $totalSessions > 0 ? round($totalBattles / $totalSessions, 2) : 0,
            'playtime_efficiency' => $this->calculatePlaytimeEfficiency($gameAnalytics, $battleAnalytics),
            'preferred_playstyle' => $this->determinePreferredPlaystyle($gameAnalytics, $battleAnalytics),
        ];
    }

    // グローバル統合分析の作成
    private function createIntegratedGlobalAnalytics(array $gameAnalytics, array $battleAnalytics): array
    {
        return [
            'user_retention_indicators' => $this->calculateRetentionIndicators($gameAnalytics, $battleAnalytics),
            'game_difficulty_balance' => $this->assessDifficultyBalance($battleAnalytics),
            'content_engagement_levels' => $this->analyzeContentEngagement($gameAnalytics, $battleAnalytics),
            'system_health_metrics' => $this->calculateSystemHealthMetrics($gameAnalytics, $battleAnalytics),
        ];
    }

    // エンゲージメント指標の計算
    private function calculateUserEngagementMetrics(array $gameAnalytics, array $battleAnalytics): array
    {
        $totalPlaytime = $gameAnalytics['total_playtime'] ?? 0;
        $totalBattles = $battleAnalytics['total_battles'] ?? 0;
        $totalSessions = $gameAnalytics['total_sessions'] ?? 0;
        
        return [
            'activity_level' => $this->categorizeActivityLevel($totalSessions, $totalPlaytime),
            'combat_engagement' => $totalSessions > 0 ? ($totalBattles / $totalSessions) : 0,
            'session_quality' => $this->calculateSessionQuality($gameAnalytics),
            'progression_velocity' => $this->calculateProgressionVelocity($battleAnalytics),
            'exploration_tendency' => $this->calculateExplorationTendency($gameAnalytics),
        ];
    }

    // 補助計算メソッド群
    private function calculateEngagementScore(array $gameAnalytics, array $battleAnalytics): int
    {
        $score = 0;
        
        // セッション頻度スコア (0-30)
        $sessions = $gameAnalytics['total_sessions'] ?? 0;
        $score += min(30, $sessions * 2);
        
        // バトル参加スコア (0-25)
        $battles = $battleAnalytics['total_battles'] ?? 0;
        $score += min(25, $battles);
        
        // 勝率スコア (0-20)
        $winRate = $battleAnalytics['performance_metrics']['win_rate'] ?? 0;
        $score += ($winRate / 100) * 20;
        
        // プレイ時間スコア (0-25)
        $playtime = $gameAnalytics['total_playtime'] ?? 0;
        $score += min(25, $playtime / 10);
        
        return min(100, (int) $score);
    }

    private function calculateSkillProgressionRate(array $battleAnalytics): string
    {
        $difficultyProgression = $battleAnalytics['difficulty_progression'] ?? [];
        $experienceTrend = $difficultyProgression['experience_trend'] ?? 'Insufficient Data';
        $turnTrend = $difficultyProgression['turn_efficiency_trend'] ?? 'Insufficient Data';
        
        if ($experienceTrend === 'Increasing' && $turnTrend === 'Improving') {
            return 'Excellent';
        } elseif ($experienceTrend === 'Increasing' || $turnTrend === 'Improving') {
            return 'Good';
        } elseif ($experienceTrend === 'Stable' && $turnTrend === 'Stable') {
            return 'Normal';
        } else {
            return 'Needs Improvement';
        }
    }

    private function calculatePlaytimeEfficiency(array $gameAnalytics, array $battleAnalytics): string
    {
        $avgSessionDuration = $gameAnalytics['average_session_duration'] ?? 0;
        $actionsPerSession = $gameAnalytics['actions_per_session'] ?? 0;
        
        if ($avgSessionDuration > 30 && $actionsPerSession > 15) {
            return 'High';
        } elseif ($avgSessionDuration > 15 && $actionsPerSession > 8) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    private function determinePreferredPlaystyle(array $gameAnalytics, array $battleAnalytics): string
    {
        $totalBattles = $battleAnalytics['total_battles'] ?? 0;
        $totalSessions = $gameAnalytics['total_sessions'] ?? 1;
        $battleRatio = $totalBattles / $totalSessions;
        
        if ($battleRatio > 3) {
            return 'Combat-Focused';
        } elseif ($battleRatio > 1) {
            return 'Balanced';
        } else {
            return 'Exploration-Focused';
        }
    }

    private function calculateRetentionIndicators(array $gameAnalytics, array $battleAnalytics): array
    {
        return [
            'active_user_ratio' => $gameAnalytics['active_users_7d'] / max(1, $gameAnalytics['total_users']),
            'session_consistency' => $this->calculateSessionConsistency($gameAnalytics),
            'combat_retention' => $this->calculateCombatRetention($battleAnalytics),
        ];
    }

    private function assessDifficultyBalance(array $battleAnalytics): array
    {
        $globalWinRate = $battleAnalytics['global_metrics']['global_win_rate'] ?? 0;
        
        return [
            'overall_balance' => $this->categorizeDifficultyBalance($globalWinRate),
            'monster_difficulty_spread' => $this->analyzeMonsterDifficulty($battleAnalytics['challenging_monsters'] ?? []),
            'location_balance' => $this->analyzeLocationBalance($battleAnalytics['popular_locations'] ?? []),
        ];
    }

    private function analyzeContentEngagement(array $gameAnalytics, array $battleAnalytics): array
    {
        return [
            'location_diversity' => count($gameAnalytics['most_visited_locations'] ?? []),
            'battle_location_diversity' => count($battleAnalytics['location_analysis'] ?? []),
            'monster_encounter_diversity' => count($battleAnalytics['monster_analysis'] ?? []),
        ];
    }

    private function calculateSystemHealthMetrics(array $gameAnalytics, array $battleAnalytics): array
    {
        return [
            'data_completeness' => $this->calculateDataCompleteness($gameAnalytics, $battleAnalytics),
            'user_activity_distribution' => $this->analyzeActivityDistribution($gameAnalytics),
            'performance_indicators' => $this->calculatePerformanceIndicators($battleAnalytics),
        ];
    }

    // ユーザー推奨事項の生成
    private function generateUserRecommendations(array $engagementMetrics): array
    {
        $recommendations = [];
        
        if ($engagementMetrics['combat_engagement'] < 1) {
            $recommendations[] = '戦闘により積極的に参加することで、経験値とゴールドを効率的に獲得できます。';
        }
        
        if ($engagementMetrics['session_quality'] < 0.5) {
            $recommendations[] = 'より長いセッションでプレイすることで、ゲームの深い部分を体験できます。';
        }
        
        if ($engagementMetrics['exploration_tendency'] < 0.3) {
            $recommendations[] = '新しい場所を探索することで、様々なコンテンツを発見できます。';
        }
        
        return $recommendations;
    }

    // ゲームバランス推奨事項の生成
    private function generateBalanceRecommendations(array $balanceMetrics): array
    {
        $recommendations = [];
        
        $difficultyBalance = $balanceMetrics['overall_balance'] ?? 'Unknown';
        
        if ($difficultyBalance === 'Too Easy') {
            $recommendations[] = 'モンスターの強さを調整し、より挑戦的な体験を提供することを検討してください。';
        } elseif ($difficultyBalance === 'Too Hard') {
            $recommendations[] = 'プレイヤーにより多くのリソースや戦略オプションを提供することを検討してください。';
        }
        
        return $recommendations;
    }

    // 補助分析メソッド（簡略化）
    private function categorizeActivityLevel(int $sessions, int $playtime): string
    {
        if ($sessions > 50 && $playtime > 1000) return 'Very High';
        if ($sessions > 20 && $playtime > 400) return 'High';
        if ($sessions > 10 && $playtime > 200) return 'Medium';
        if ($sessions > 5 && $playtime > 100) return 'Low';
        return 'Very Low';
    }

    private function calculateSessionQuality(array $gameAnalytics): float
    {
        $avgDuration = $gameAnalytics['average_session_duration'] ?? 0;
        $actionsPerSession = $gameAnalytics['actions_per_session'] ?? 0;
        
        return min(1.0, ($avgDuration * $actionsPerSession) / 500);
    }

    private function calculateProgressionVelocity(array $battleAnalytics): string
    {
        $experienceTrend = $battleAnalytics['difficulty_progression']['experience_trend'] ?? 'Insufficient Data';
        
        return match($experienceTrend) {
            'Increasing' => 'Fast',
            'Stable' => 'Normal',
            'Decreasing' => 'Slow',
            default => 'Unknown'
        };
    }

    private function calculateExplorationTendency(array $gameAnalytics): float
    {
        $locationCount = count($gameAnalytics['most_visited_locations'] ?? []);
        return min(1.0, $locationCount / 10);
    }

    private function calculateSessionConsistency(array $gameAnalytics): float
    {
        // セッション活動パターンの一貫性を計算（簡略化）
        $activityPattern = $gameAnalytics['activity_pattern'] ?? [];
        if (empty($activityPattern)) return 0.0;
        
        $variance = $this->calculateVariance(array_values($activityPattern));
        return max(0, 1 - ($variance / 100));
    }

    private function calculateCombatRetention(array $battleAnalytics): float
    {
        $recentWinRate = $battleAnalytics['difficulty_progression']['recent_win_rate'] ?? 0;
        return $recentWinRate / 100;
    }

    private function categorizeDifficultyBalance(float $winRate): string
    {
        if ($winRate > 80) return 'Too Easy';
        if ($winRate > 60) return 'Well Balanced';
        if ($winRate > 40) return 'Challenging';
        return 'Too Hard';
    }

    private function analyzeMonsterDifficulty(array $monsters): string
    {
        if (empty($monsters)) return 'No Data';
        
        $avgWinRate = collect($monsters)->avg('win_rate');
        return $this->categorizeDifficultyBalance($avgWinRate);
    }

    private function analyzeLocationBalance(array $locations): string
    {
        if (empty($locations)) return 'No Data';
        
        $avgWinRate = collect($locations)->avg('win_rate');
        return $this->categorizeDifficultyBalance($avgWinRate);
    }

    private function calculateDataCompleteness(array $gameAnalytics, array $battleAnalytics): float
    {
        $gameDataPoints = count(array_filter($gameAnalytics));
        $battleDataPoints = count(array_filter($battleAnalytics));
        $totalExpected = 20; // 期待されるデータポイント数
        
        return min(1.0, ($gameDataPoints + $battleDataPoints) / $totalExpected);
    }

    private function analyzeActivityDistribution(array $gameAnalytics): array
    {
        return [
            'peak_hours' => $this->findPeakActivityHours($gameAnalytics['activity_pattern'] ?? []),
            'activity_consistency' => $this->calculateActivityConsistency($gameAnalytics['activity_pattern'] ?? []),
        ];
    }

    private function calculatePerformanceIndicators(array $battleAnalytics): array
    {
        return [
            'average_battle_efficiency' => $battleAnalytics['global_metrics']['average_battle_duration'] ?? 0,
            'system_stability' => 'Good', // 実際の実装では詳細な計算が必要
        ];
    }

    private function findPeakActivityHours(array $activityPattern): array
    {
        if (empty($activityPattern)) return [];
        
        arsort($activityPattern);
        return array_slice(array_keys($activityPattern), 0, 3, true);
    }

    private function calculateActivityConsistency(array $activityPattern): float
    {
        if (empty($activityPattern)) return 0.0;
        
        $variance = $this->calculateVariance(array_values($activityPattern));
        $mean = array_sum($activityPattern) / count($activityPattern);
        
        return $mean > 0 ? max(0, 1 - ($variance / ($mean * $mean))) : 0;
    }

    private function calculateVariance(array $values): float
    {
        if (empty($values)) return 0.0;
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => ($x - $mean) ** 2, $values)) / count($values);
        
        return $variance;
    }
}