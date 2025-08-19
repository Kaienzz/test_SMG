@extends('admin.layouts.app')

@section('title', 'モンスタースポーン管理')

@section('content')
<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">モンスタースポーン管理</h1>
        <p class="admin-subtitle">各エリアのモンスター出現設定を管理</p>
    </div>

    @if($errors->any())
        <div class="admin-alert admin-alert-error">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="admin-alert admin-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="admin-alert admin-alert-error">
            {{ session('error') }}
        </div>
    @endif

    <!-- フィルター -->
    <form method="GET" class="admin-filters">
        <div class="admin-filter-row">
            <div class="admin-filter-item">
                <label for="search" class="admin-label">検索</label>
                <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}" 
                       placeholder="エリア名・ID..." class="admin-input">
            </div>
            
            <div class="admin-filter-item">
                <label for="pathway_id" class="admin-label">特定エリア</label>
                <select id="pathway_id" name="pathway_id" class="admin-select">
                    <option value="">すべてのエリア</option>
                    @foreach($pathways as $pathwayId => $pathway)
                        <option value="{{ $pathwayId }}" {{ ($filters['pathway_id'] ?? '') === $pathwayId ? 'selected' : '' }}>
                            {{ $pathway['name'] }} ({{ $pathwayId }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="admin-filter-actions">
                <button type="submit" class="admin-btn admin-btn-primary">フィルター</button>
                <a href="{{ route('admin.monsters.spawn-lists.index') }}" class="admin-btn admin-btn-secondary">クリア</a>
            </div>
        </div>
    </form>

    <!-- 統計 -->
    <div class="admin-stats">
        <div class="admin-stat-item">
            <span class="admin-stat-label">総エリア数</span>
            <span class="admin-stat-value">{{ $total_pathways }}</span>
        </div>
        <div class="admin-stat-item">
            <span class="admin-stat-label">表示中</span>
            <span class="admin-stat-value">{{ $filtered_pathways }}</span>
        </div>
    </div>

    <!-- エリア一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>エリア別スポーン設定</h2>
            <div class="admin-actions">
                <a href="{{ route('admin.monsters.spawn-lists.validate') }}" class="admin-btn admin-btn-outline">
                    全エリア検証
                </a>
            </div>
        </div>

        <div class="admin-card-content">
            @if(count($pathwaySpawns) > 0)
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>エリア名</th>
                                <th>カテゴリー</th>
                                <th>難易度</th>
                                <th>設定済みモンスター</th>
                                <th>出現率合計</th>
                                <th>状態</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pathwaySpawns as $pathwayId => $data)
                                @php
                                    $pathway = $data['pathway'];
                                    $spawns = $data['spawns'];
                                    $validation = $data['validation'];
                                    $totalRate = array_sum(array_column($spawns, 'spawn_rate'));
                                    $isValid = $validation['is_valid'] ?? true;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="admin-item-main">{{ $pathway['name'] }}</div>
                                        <div class="admin-item-sub">{{ $pathwayId }}</div>
                                    </td>
                                    <td>
                                        <span class="admin-badge admin-badge-{{ $pathway['category'] === 'dungeon' ? 'warning' : 'info' }}">
                                            {{ $pathway['category'] === 'dungeon' ? 'ダンジョン' : '道路' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="admin-badge admin-badge-difficulty-{{ $pathway['difficulty'] ?? 'normal' }}">
                                            {{ ucfirst($pathway['difficulty'] ?? 'normal') }}
                                        </span>
                                    </td>
                                    <td>{{ count($spawns) }}種類</td>
                                    <td>
                                        <span class="admin-rate {{ $totalRate > 1.0 ? 'admin-rate-error' : ($totalRate < 0.5 ? 'admin-rate-warning' : 'admin-rate-good') }}">
                                            {{ number_format($totalRate * 100, 1) }}%
                                        </span>
                                    </td>
                                    <td>
                                        @if($isValid)
                                            <span class="admin-status admin-status-success">正常</span>
                                        @else
                                            <span class="admin-status admin-status-error">エラー</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="admin-actions">
                                            <a href="{{ route('admin.monsters.spawn-lists.pathway', $pathwayId) }}" 
                                               class="admin-btn admin-btn-sm admin-btn-primary">編集</a>
                                            <a href="{{ route('admin.monsters.spawn-lists.test', $pathwayId) }}" 
                                               class="admin-btn admin-btn-sm admin-btn-outline">テスト</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="admin-empty">
                    <p>表示するエリアがありません。</p>
                    @if(!empty($filters['search']) || !empty($filters['pathway_id']))
                        <a href="{{ route('admin.monsters.spawn-lists.index') }}" class="admin-btn admin-btn-primary">フィルターをクリア</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.admin-rate {
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 4px;
}

.admin-rate-good {
    background-color: #d4edda;
    color: #155724;
}

.admin-rate-warning {
    background-color: #fff3cd;
    color: #856404;
}

.admin-rate-error {
    background-color: #f8d7da;
    color: #721c24;
}

.admin-badge-difficulty-easy {
    background-color: #d4edda;
    color: #155724;
}

.admin-badge-difficulty-normal {
    background-color: #d1ecf1;
    color: #0c5460;
}

.admin-badge-difficulty-hard {
    background-color: #f8d7da;
    color: #721c24;
}
</style>
@endsection