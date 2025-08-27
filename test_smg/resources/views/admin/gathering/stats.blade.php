@extends('admin.layouts.app')

@section('title', '採集統計')
@section('subtitle', '採集システムの詳細統計情報')

@section('content')
<div class="admin-content">
    {{-- ページヘッダー --}}
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">
                <span class="page-icon">📊</span>
                採集統計
            </h1>
            <p class="page-description">採集システム全体の詳細統計とシステム状況</p>
        </div>
        
        <div class="page-actions">
            <a href="{{ route('admin.gathering.index') }}" class="admin-btn admin-btn-secondary">
                <span class="btn-icon">↩️</span>
                採集管理に戻る
            </a>
            <button type="button" onclick="window.print()" class="admin-btn admin-btn-info">
                <span class="btn-icon">🖨️</span>
                印刷
            </button>
        </div>
    </div>

    {{-- システムサマリー --}}
    <div class="content-card">
        <div class="content-card-header">
            <h3>システムサマリー</h3>
        </div>
        <div class="content-card-body">
            <div class="stats-grid stats-grid-4">
                <div class="stat-card stat-card-primary">
                    <div class="stat-card-header">
                        <h4>総設定数</h4>
                        <span class="stat-icon">🌿</span>
                    </div>
                    <div class="stat-card-value">{{ $systemSummary['total_mappings'] ?? 0 }}</div>
                    <div class="stat-card-footer">採集マッピング総数</div>
                </div>
                
                <div class="stat-card stat-card-success">
                    <div class="stat-card-header">
                        <h4>アクティブ設定</h4>
                        <span class="stat-icon">✅</span>
                    </div>
                    <div class="stat-card-value">{{ $systemSummary['active_mappings'] ?? 0 }}</div>
                    <div class="stat-card-footer">
                        非アクティブ: {{ $systemSummary['inactive_mappings'] ?? 0 }}
                    </div>
                </div>
                
                <div class="stat-card stat-card-info">
                    <div class="stat-card-header">
                        <h4>採集対応ルート</h4>
                        <span class="stat-icon">🗺️</span>
                    </div>
                    <div class="stat-card-value">{{ $systemSummary['gathering_routes'] ?? 0 }}</div>
                    <div class="stat-card-footer">
                        総対象: {{ $systemSummary['total_gathering_eligible_routes'] ?? 0 }}
                    </div>
                </div>
                
                <div class="stat-card stat-card-warning">
                    <div class="stat-card-header">
                        <h4>設定完了率</h4>
                        <span class="stat-icon">📈</span>
                    </div>
                    <div class="stat-card-value">{{ $systemSummary['configuration_completion'] ?? 0 }}%</div>
                    <div class="stat-card-footer">
                        未設定: {{ $systemSummary['unused_routes'] ?? 0 }} ルート
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 環境別統計 --}}
    <div class="content-card">
        <div class="content-card-header">
            <h3>環境別採集統計</h3>
        </div>
        <div class="content-card-body">
            <div class="environment-stats">
                @foreach($environmentStats ?? [] as $envStat)
                <div class="environment-stat-card">
                    <div class="environment-header">
                        <h4>
                            <span class="environment-icon">
                                {{ $envStat['category'] === 'road' ? '🛤️' : '🏰' }}
                            </span>
                            {{ $envStat['category_name'] }}環境
                        </h4>
                        <div class="environment-badges">
                            <span class="admin-badge admin-badge-{{ $envStat['category'] === 'road' ? 'primary' : 'secondary' }}">
                                {{ $envStat['total_routes'] }}ルート
                            </span>
                        </div>
                    </div>
                    
                    <div class="environment-metrics">
                        <div class="metric">
                            <label>採集対応ルート</label>
                            <span class="metric-value">
                                {{ $envStat['routes_with_gathering'] }} / {{ $envStat['total_routes'] }}
                                <small>({{ round($envStat['total_routes'] > 0 ? ($envStat['routes_with_gathering'] / $envStat['total_routes']) * 100 : 0, 1) }}%)</small>
                            </span>
                        </div>
                        <div class="metric">
                            <label>採集アイテム総数</label>
                            <span class="metric-value">{{ $envStat['total_gathering_items'] }}</span>
                        </div>
                        <div class="metric">
                            <label>アクティブアイテム</label>
                            <span class="metric-value">
                                {{ $envStat['active_gathering_items'] }}
                                <small>({{ round($envStat['total_gathering_items'] > 0 ? ($envStat['active_gathering_items'] / $envStat['total_gathering_items']) * 100 : 0, 1) }}%)</small>
                            </span>
                        </div>
                    </div>
                    
                    @if(count($envStat['routes']) > 0)
                    <div class="routes-breakdown">
                        <h5>ルート別詳細</h5>
                        <div class="routes-table">
                            @foreach($envStat['routes'] as $route)
                            <div class="route-row {{ $route['total_items'] === 0 ? 'route-unconfigured' : '' }}">
                                <div class="route-name">
                                    <strong>{{ $route['route_name'] }}</strong>
                                    <small>{{ $route['route_id'] }}</small>
                                </div>
                                <div class="route-stats">
                                    <span class="route-stat">
                                        {{ $route['active_items'] }}/{{ $route['total_items'] }} アクティブ
                                    </span>
                                    @if($route['total_items'] > 0)
                                    <span class="route-completion admin-badge admin-badge-{{ $route['completion_rate'] >= 80 ? 'success' : ($route['completion_rate'] >= 50 ? 'warning' : 'danger') }}">
                                        {{ $route['completion_rate'] }}%
                                    </span>
                                    @else
                                    <span class="admin-badge admin-badge-secondary">未設定</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 設定検証結果 --}}
    @if(count($configurationIssues ?? []) > 0)
    <div class="content-card">
        <div class="content-card-header">
            <h3>設定検証結果</h3>
            <div class="content-card-meta text-warning">
                {{ count($configurationIssues) }}件の問題が検出されました
            </div>
        </div>
        <div class="content-card-body">
            @foreach($configurationIssues as $issue)
            <div class="issue-alert alert alert-{{ $issue['type'] === 'error' ? 'danger' : 'warning' }}">
                <div class="issue-header">
                    <span class="issue-icon">
                        {{ $issue['type'] === 'error' ? '❌' : '⚠️' }}
                    </span>
                    <strong>{{ $issue['message'] }}</strong>
                </div>
                
                @if(isset($issue['details']) && is_array($issue['details']) && count($issue['details']) > 0)
                <div class="issue-details">
                    @if(is_array(reset($issue['details'])))
                        {{-- ネストした配列の場合（ルート別問題など） --}}
                        @foreach($issue['details'] as $key => $detailArray)
                        <div class="issue-detail-group">
                            <h6>{{ $key }}</h6>
                            <ul>
                                @foreach($detailArray as $detail)
                                <li>{{ $detail }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    @else
                        {{-- シンプルな配列の場合 --}}
                        <ul>
                            @foreach($issue['details'] as $key => $detail)
                            <li>{{ is_numeric($key) ? $detail : "$key: $detail" }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="content-card">
        <div class="content-card-header">
            <h3>設定検証結果</h3>
        </div>
        <div class="content-card-body">
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <strong>すべての採集設定に問題は見つかりませんでした。</strong>
                <p>システムは正常に動作する状態です。</p>
            </div>
        </div>
    </div>
    @endif

    {{-- 統計の更新情報 --}}
    <div class="content-card">
        <div class="content-card-header">
            <h3>統計情報</h3>
        </div>
        <div class="content-card-body">
            <div class="info-grid">
                <div class="info-item">
                    <h5>📊 統計生成時刻</h5>
                    <p>{{ now()->format('Y/m/d H:i:s') }}</p>
                    <small class="text-muted">このレポートが生成された時刻です</small>
                </div>
                <div class="info-item">
                    <h5>🔄 データ更新頻度</h5>
                    <p>リアルタイム</p>
                    <small class="text-muted">統計は最新のデータベース情報を反映しています</small>
                </div>
                <div class="info-item">
                    <h5>📈 推奨アクション</h5>
                    @if(($systemSummary['configuration_completion'] ?? 0) < 80)
                    <p class="text-warning">設定完了率の向上</p>
                    <small class="text-muted">未設定のルートへの採集設定追加を推奨します</small>
                    @elseif(count($configurationIssues ?? []) > 0)
                    <p class="text-danger">設定問題の解決</p>
                    <small class="text-muted">検出された設定問題の修正を推奨します</small>
                    @else
                    <p class="text-success">維持・監視</p>
                    <small class="text-muted">現在の良好な状態を維持してください</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.environment-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.environment-stat-card {
    border: 1px solid #e0e0e0;
    border-radius: 0.5rem;
    padding: 1.5rem;
    background-color: #f8f9fa;
}

.environment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #dee2e6;
}

.environment-header h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    color: #495057;
}

.environment-icon {
    font-size: 1.25rem;
}

.environment-badges {
    display: flex;
    gap: 0.5rem;
}

.environment-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.metric {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.metric label {
    font-size: 12px;
    color: #6c757d;
    font-weight: bold;
    text-transform: uppercase;
}

.metric-value {
    font-size: 16px;
    font-weight: bold;
    color: #495057;
}

.metric-value small {
    font-size: 11px;
    color: #6c757d;
    font-weight: normal;
}

.routes-breakdown {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
}

.routes-breakdown h5 {
    margin-bottom: 0.75rem;
    color: #495057;
    font-size: 14px;
}

.routes-table {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.route-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    border-radius: 0.25rem;
    background-color: white;
    border: 1px solid #e9ecef;
}

.route-row.route-unconfigured {
    background-color: #fff3cd;
    border-color: #ffeaa7;
}

.route-name {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.route-name strong {
    color: #495057;
    font-size: 13px;
}

.route-name small {
    color: #6c757d;
    font-size: 11px;
}

.route-stats {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.route-stat {
    font-size: 12px;
    color: #6c757d;
}

.stats-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

@media (max-width: 1200px) {
    .stats-grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid-4 {
        grid-template-columns: 1fr;
    }
    
    .environment-stats {
        grid-template-columns: 1fr;
    }
}

.stat-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    border-left: 4px solid #007bff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card-primary { border-left-color: #007bff; }
.stat-card-success { border-left-color: #28a745; }
.stat-card-info { border-left-color: #17a2b8; }
.stat-card-warning { border-left-color: #ffc107; }
.stat-card-danger { border-left-color: #dc3545; }

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.stat-card-header h4 {
    margin: 0;
    color: #495057;
    font-size: 14px;
    font-weight: 600;
}

.stat-icon {
    font-size: 1.25rem;
    opacity: 0.7;
}

.stat-card-value {
    font-size: 2rem;
    font-weight: bold;
    color: #212529;
    margin-bottom: 0.5rem;
}

.stat-card-footer {
    color: #6c757d;
    font-size: 12px;
}

.issue-alert {
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 0.375rem;
    border: 1px solid transparent;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.issue-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.issue-icon {
    font-size: 1.1rem;
}

.issue-details {
    margin-left: 1.5rem;
}

.issue-detail-group {
    margin-bottom: 1rem;
}

.issue-detail-group h6 {
    color: #495057;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.issue-details ul {
    margin: 0.5rem 0;
    padding-left: 1.25rem;
}

.issue-details li {
    margin-bottom: 0.25rem;
    font-size: 13px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.info-item {
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 0.375rem;
    background-color: #f8f9fa;
}

.info-item h5 {
    margin-bottom: 0.75rem;
    color: #495057;
    font-size: 14px;
}

.info-item p {
    margin-bottom: 0.5rem;
    font-weight: bold;
    color: #212529;
}

.text-muted {
    color: #6c757d !important;
}

.text-warning {
    color: #856404 !important;
}

.text-danger {
    color: #721c24 !important;
}

.text-success {
    color: #155724 !important;
}

.admin-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
}

.admin-badge-primary { background-color: #007bff; color: white; }
.admin-badge-secondary { background-color: #6c757d; color: white; }
.admin-badge-success { background-color: #28a745; color: white; }
.admin-badge-danger { background-color: #dc3545; color: white; }
.admin-badge-warning { background-color: #ffc107; color: #212529; }
.admin-badge-info { background-color: #17a2b8; color: white; }

.alert-icon {
    margin-right: 0.5rem;
}

/* Print styles */
@media print {
    .page-actions {
        display: none !important;
    }
    
    .stat-card {
        break-inside: avoid;
    }
    
    .environment-stat-card {
        break-inside: avoid;
    }
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 印刷機能の改善
    window.addEventListener('beforeprint', function() {
        document.title = '採集統計_' + new Date().toISOString().slice(0, 10);
    });
    
    // 統計カードの数値アニメーション（オプショナル）
    const statValues = document.querySelectorAll('.stat-card-value');
    const animateNumbers = function() {
        statValues.forEach(element => {
            const finalValue = parseInt(element.textContent);
            if (!isNaN(finalValue) && finalValue > 0) {
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 20);
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    element.textContent = currentValue;
                }, 50);
            }
        });
    };
    
    // ページ読み込み完了後にアニメーション実行
    setTimeout(animateNumbers, 500);
});
</script>
@endsection