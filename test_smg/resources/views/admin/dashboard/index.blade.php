@extends('admin.layouts.app')

@section('title', '管理者ダッシュボード')
@section('subtitle', 'ゲーム運営状況の概要と統計情報')

@section('content')
<div class="dashboard-container">
    <!-- 概要統計カード -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- 総ユーザー数 -->
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ number_format($dashboardData['overview']['total_users']) }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">総ユーザー数</div>
                <div style="margin-top: 0.5rem;">
                    <span class="admin-badge admin-badge-success">
                        今日: {{ number_format($dashboardData['overview']['active_users_today']) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 総プレイヤー数 -->
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ number_format($dashboardData['overview']['total_players']) }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">総プレイヤー数</div>
                <div style="margin-top: 0.5rem;">
                    <span class="admin-badge admin-badge-info">
                        保持率: {{ $dashboardData['users']['retention']['weekly'] }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- アクティブバトル -->
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ number_format($dashboardData['overview']['active_battles']) }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">進行中のバトル</div>
                <div style="margin-top: 0.5rem;">
                    <span class="admin-badge admin-badge-warning">
                        今週: {{ number_format($dashboardData['game']['battles']['this_week']) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 管理者数 -->
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ number_format($dashboardData['overview']['admin_users']) }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">管理者アカウント</div>
                <div style="margin-top: 0.5rem;">
                    <span class="admin-badge admin-badge-info">
                        今日: {{ number_format($dashboardData['system']['admin_activity']['total_actions_today']) }} 操作
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- アラート表示 -->
    @if(!empty($dashboardData['alerts']))
    <div style="margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem; color: #1f2937;">🚨 重要な通知</h3>
        @foreach($dashboardData['alerts'] as $alert)
        <div class="admin-alert admin-alert-{{ $alert['type'] === 'security' ? 'danger' : ($alert['type'] === 'warning' ? 'warning' : 'info') }}" style="margin-bottom: 0.5rem;">
            <div class="flex justify-between items-center">
                <span>{{ $alert['message'] }}</span>
                <a href="{{ $alert['action_url'] }}" class="admin-btn admin-btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.875rem;">
                    確認
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- メイン統計エリア -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- ユーザー・ゲーム統計 -->
        <div>
            <!-- ユーザー統計 -->
            <div class="admin-card" style="margin-bottom: 2rem;">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">ユーザー統計</h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                        <div>
                            <h4 style="margin-bottom: 1rem; color: #374151;">新規登録</h4>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span>昨日</span>
                                <span class="admin-badge admin-badge-info">{{ number_format($dashboardData['users']['registrations']['yesterday']) }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span>過去7日</span>
                                <span class="admin-badge admin-badge-success">{{ number_format($dashboardData['users']['registrations']['last_7_days']) }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>過去30日</span>
                                <span class="admin-badge admin-badge-warning">{{ number_format($dashboardData['users']['registrations']['last_30_days']) }}</span>
                            </div>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 1rem; color: #374151;">アクティブユーザー</h4>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span>日次 (DAU)</span>
                                <span class="admin-badge admin-badge-success">{{ number_format($dashboardData['users']['activity']['daily_active_users']) }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span>週次 (WAU)</span>
                                <span class="admin-badge admin-badge-info">{{ number_format($dashboardData['users']['activity']['weekly_active_users']) }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>月次 (MAU)</span>
                                <span class="admin-badge admin-badge-warning">{{ number_format($dashboardData['users']['activity']['monthly_active_users']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ゲーム統計 -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">ゲーム統計</h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                        <div>
                            <h4 style="margin-bottom: 1rem; color: #374151;">バトル統計</h4>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span>総バトル数</span>
                                <span>{{ number_format($dashboardData['game']['battles']['total']) }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span>今日のバトル</span>
                                <span class="admin-badge admin-badge-success">{{ number_format($dashboardData['game']['battles']['today']) }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>進行中</span>
                                <span class="admin-badge admin-badge-warning">{{ number_format($dashboardData['game']['battles']['active_now']) }}</span>
                            </div>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 1rem; color: #374151;">経済統計</h4>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span>総ゴールド</span>
                                <span>{{ number_format($dashboardData['game']['economy']['total_gold']) }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span>平均ゴールド</span>
                                <span>{{ number_format($dashboardData['game']['economy']['average_gold']) }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>今週のアイテム</span>
                                <span class="admin-badge admin-badge-info">{{ number_format($dashboardData['game']['economy']['items_created']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- サイドパネル -->
        <div>
            <!-- 最近のアクティビティ -->
            <div class="admin-card" style="margin-bottom: 2rem;">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">最近の管理者操作</h3>
                </div>
                <div class="admin-card-body">
                    @if(!empty($dashboardData['recent_activity']['admin_actions']))
                        @foreach(array_slice($dashboardData['recent_activity']['admin_actions'], 0, 5) as $action)
                        <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--admin-border); last:border-bottom: none;">
                            <div style="font-weight: 500; color: #1f2937; margin-bottom: 0.25rem;">
                                {{ $action->description }}
                            </div>
                            <div style="font-size: 0.875rem; color: var(--admin-secondary); display: flex; justify-content: space-between;">
                                <span>{{ $action->admin_name }}</span>
                                <span class="admin-badge admin-badge-{{ $action->severity === 'critical' ? 'danger' : ($action->severity === 'high' ? 'warning' : 'info') }}">
                                    {{ ucfirst($action->severity) }}
                                </span>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--admin-secondary);">
                                {{ \Carbon\Carbon::parse($action->event_time)->diffForHumans() }}
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p style="text-align: center; color: var(--admin-secondary); padding: 2rem;">
                            管理者操作の履歴がありません
                        </p>
                    @endif
                </div>
            </div>

            <!-- システム情報 -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">システム情報</h3>
                </div>
                <div class="admin-card-body">
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span>総監査ログ</span>
                            <span>{{ number_format($dashboardData['system']['database']['total_audit_logs']) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span>セキュリティイベント (今日)</span>
                            <span class="admin-badge admin-badge-{{ $dashboardData['system']['admin_activity']['security_events_today'] > 0 ? 'danger' : 'success' }}">
                                {{ $dashboardData['system']['admin_activity']['security_events_today'] }}
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>失敗した操作 (今日)</span>
                            <span class="admin-badge admin-badge-{{ $dashboardData['system']['admin_activity']['failed_operations_today'] > 0 ? 'warning' : 'success' }}">
                                {{ $dashboardData['system']['admin_activity']['failed_operations_today'] }}
                            </span>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px solid var(--admin-border); padding-top: 1rem;">
                        <h4 style="margin-bottom: 0.5rem; color: #374151;">パフォーマンス</h4>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span>平均レスポンス</span>
                            <span>{{ $dashboardData['performance']['response_time']['average'] }}ms</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span>メモリ使用量</span>
                            <span>{{ round($dashboardData['performance']['memory_usage']['current'] / 1024 / 1024, 1) }}MB</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>キャッシュヒット率</span>
                            <span class="admin-badge admin-badge-success">{{ $dashboardData['performance']['cache_hit_rate'] }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- クイックアクション -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">クイックアクション</h3>
        </div>
        <div class="admin-card-body">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                @if($canManageUsers || (isset($adminUser) && $adminUser->admin_level === 'super'))
                <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-primary">
                    👥 ユーザー管理
                </a>
                <a href="{{ route('admin.players.index') }}" class="admin-btn admin-btn-secondary">
                    🎮 プレイヤー管理
                </a>
                @endif
                
                @if($canManageGameData || (isset($adminUser) && $adminUser->admin_level === 'super'))
                <a href="{{ route('admin.items.index') }}" class="admin-btn admin-btn-success">
                    🛡️ アイテム管理
                </a>
                <a href="{{ route('admin.monsters.index') }}" class="admin-btn admin-btn-info">
                    🐉 モンスター管理
                </a>
                <a href="{{ route('admin.shops.index') }}" class="admin-btn admin-btn-warning">
                    🏪 ショップ管理
                </a>
                @endif
                
                @if($canAccessAnalytics || (isset($adminUser) && $adminUser->admin_level === 'super'))
                <a href="{{ route('admin.analytics.index') }}" class="admin-btn admin-btn-info">
                    📊 詳細分析
                </a>
                <a href="{{ route('admin.audit.index') }}" class="admin-btn admin-btn-secondary">
                    📋 監査ログ
                </a>
                @endif
                
                @if($canManageSystem || (isset($adminUser) && $adminUser->admin_level === 'super'))
                <a href="{{ route('admin.system.config') }}" class="admin-btn admin-btn-danger">
                    ⚙️ システム設定
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- リアルタイム更新用JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // リアルタイム統計の更新
    function updateRealTimeStats() {
        fetch('{{ route("admin.api.stats.realtime") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // オンラインユーザー数などの更新
                    console.log('Real-time stats updated:', data.data);
                }
            })
            .catch(error => {
                console.log('Failed to update real-time stats:', error);
            });
    }

    // 5分ごとにリアルタイム統計を更新
    setInterval(updateRealTimeStats, 5 * 60 * 1000);
});
</script>
@endsection