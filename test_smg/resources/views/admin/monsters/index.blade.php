@extends('admin.layouts.app')

@section('title', 'モンスター管理')
@section('subtitle', 'ゲーム内モンスターの管理とバランス調整')

@section('content')
<div class="admin-content-container">
    
    <!-- 統計カード -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ number_format($stats['total_monsters']) }}
                </div>
                <div style="color: var(--admin-secondary);">総モンスター数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ number_format($stats['avg_level'], 1) }}
                </div>
                <div style="color: var(--admin-secondary);">平均レベル</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ count($roads) }}
                </div>
                <div style="color: var(--admin-secondary);">出現エリア数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ number_format($stats['avg_stats']['experience']) }}
                </div>
                <div style="color: var(--admin-secondary);">平均経験値</div>
            </div>
        </div>
    </div>

    <!-- フィルター・検索 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">検索・フィルター</h3>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.monsters.index') }}" class="filter-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <!-- 検索 -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">検索</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                               placeholder="モンスター名・説明" class="admin-input">
                    </div>

                    <!-- 道路 -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">出現エリア</label>
                        <select name="road" class="admin-select">
                            <option value="">すべて</option>
                            @foreach($roads as $road)
                            <option value="{{ $road }}" {{ ($filters['road'] ?? '') === $road ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $road)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- レベル範囲（最小） -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">最小レベル</label>
                        <input type="number" name="min_level" value="{{ $filters['min_level'] ?? '' }}" 
                               placeholder="1" class="admin-input" min="1" max="100">
                    </div>

                    <!-- レベル範囲（最大） -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">最大レベル</label>
                        <input type="number" name="max_level" value="{{ $filters['max_level'] ?? '' }}" 
                               placeholder="100" class="admin-input" min="1" max="100">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        🔍 検索
                    </button>
                    <a href="{{ route('admin.monsters.index') }}" class="admin-btn admin-btn-secondary">
                        🔄 リセット
                    </a>
                    @if(auth()->user()->can('monsters.edit'))
                    <button type="button" onclick="showBalanceModal()" class="admin-btn admin-btn-warning">
                        ⚖️ バランス調整
                    </button>
                    <button type="button" onclick="showSpawnRateModal()" class="admin-btn admin-btn-info">
                        📊 出現率調整
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- モンスター一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">モンスター一覧 ({{ $totalCount }}体)</h3>
            <div style="display: flex; gap: 0.5rem;">
                <!-- ソート -->
                <select onchange="updateSort(this.value)" class="admin-select" style="width: auto;">
                    <option value="level-asc" {{ $sortBy === 'level' && $sortDirection === 'asc' ? 'selected' : '' }}>レベル昇順</option>
                    <option value="level-desc" {{ $sortBy === 'level' && $sortDirection === 'desc' ? 'selected' : '' }}>レベル降順</option>
                    <option value="name-asc" {{ $sortBy === 'name' && $sortDirection === 'asc' ? 'selected' : '' }}>名前昇順</option>
                    <option value="max_hp-desc" {{ $sortBy === 'max_hp' && $sortDirection === 'desc' ? 'selected' : '' }}>HP降順</option>
                    <option value="attack-desc" {{ $sortBy === 'attack' && $sortDirection === 'desc' ? 'selected' : '' }}>攻撃力降順</option>
                    <option value="experience_reward-desc" {{ $sortBy === 'experience_reward' && $sortDirection === 'desc' ? 'selected' : '' }}>経験値降順</option>
                </select>
            </div>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <!-- テーブル -->
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>モンスター情報</th>
                            <th>レベル</th>
                            <th>ステータス</th>
                            <th>出現エリア</th>
                            <th>出現率</th>
                            <th>経験値報酬</th>
                            <th style="width: 150px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedMonsters as $monster)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="font-size: 2rem;">{{ $monster['emoji'] ?? '👹' }}</div>
                                    <div>
                                        <div style="font-weight: 500;">{{ $monster['name'] }}</div>
                                        @if($monster['description'])
                                        <div style="font-size: 0.875rem; color: var(--admin-secondary); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $monster['description'] }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-{{ $monster['level'] <= 3 ? 'success' : ($monster['level'] <= 7 ? 'warning' : 'danger') }}">
                                    Lv.{{ $monster['level'] }}
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    <div><strong>HP:</strong> {{ number_format($monster['max_hp']) }}</div>
                                    <div><strong>攻撃:</strong> {{ number_format($monster['attack']) }}</div>
                                    <div><strong>防御:</strong> {{ number_format($monster['defense']) }}</div>
                                    <div><strong>敏捷:</strong> {{ number_format($monster['agility']) }}</div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                    @foreach($monster['spawn_roads'] as $road)
                                    <span class="admin-badge admin-badge-secondary" style="font-size: 0.75rem;">
                                        {{ ucfirst(str_replace('_', ' ', $road)) }}
                                    </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.875rem; text-align: center;">
                                    <div style="font-weight: bold;">{{ round($monster['spawn_rate'] * 100, 1) }}%</div>
                                    <div style="color: var(--admin-secondary); font-size: 0.75rem;">
                                        {{ $monster['spawn_rate'] > 0.3 ? '高' : ($monster['spawn_rate'] > 0.15 ? '中' : '低') }}
                                        @if($monster['spawn_rate_count'] > 1)
                                            <br><span style="font-size: 0.65rem;">
                                                ({{ round($monster['min_spawn_rate'] * 100, 1) }}%-{{ round($monster['max_spawn_rate'] * 100, 1) }}%)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="text-align: center;">
                                    <div style="font-weight: 500; color: var(--admin-success);">
                                        {{ number_format($monster['experience_reward']) }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--admin-secondary);">
                                        EXP
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="{{ route('admin.monsters.show', $monster['name']) }}" class="admin-btn admin-btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        詳細
                                    </a>
                                    @if(auth()->user()->can('monsters.edit'))
                                    <a href="{{ route('admin.monsters.edit', $monster['name']) }}" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        編集
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
                                条件に一致するモンスターが見つかりませんでした
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 簡易ページネーション -->
    @if($totalCount > $perPage)
    <div style="margin-top: 2rem; display: flex; justify-content: center; gap: 1rem;">
        @if($page > 1)
            <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}" class="admin-btn admin-btn-secondary">
                ← 前のページ
            </a>
        @endif
        
        <span style="padding: 0.5rem 1rem; background: #f9fafb; border-radius: 4px;">
            {{ $page }} / {{ ceil($totalCount / $perPage) }}
        </span>
        
        @if($page < ceil($totalCount / $perPage))
            <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}" class="admin-btn admin-btn-secondary">
                次のページ →
            </a>
        @endif
    </div>
    @endif
</div>

@if(auth()->user()->can('monsters.edit'))
<!-- バランス調整モーダル -->
<div id="balance-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 2rem; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-bottom: 1.5rem;">一括バランス調整</h3>
        <form id="balance-form" method="POST" action="{{ route('admin.monsters.balance_adjustment') }}">
            @csrf
            <!-- 対象選択 -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">調整対象</label>
                <select name="adjustment_type" class="admin-select" onchange="updateBalanceTargets(this.value)">
                    <option value="global">全モンスター</option>
                    <option value="level_range">レベル範囲指定</option>
                    <option value="road_based">エリア指定</option>
                </select>
            </div>

            <!-- レベル範囲 -->
            <div id="level-range-fields" style="display: none; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem;">最小レベル</label>
                        <input type="number" name="target_level_min" class="admin-input" min="1" max="100">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem;">最大レベル</label>
                        <input type="number" name="target_level_max" class="admin-input" min="1" max="100">
                    </div>
                </div>
            </div>

            <!-- エリア選択 -->
            <div id="road-fields" style="display: none; margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem;">対象エリア</label>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                    @foreach($roads as $road)
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="target_roads[]" value="{{ $road }}">
                        {{ ucfirst(str_replace('_', ' ', $road)) }}
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- 調整方法 -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">調整方法</label>
                <select name="adjustment_method" class="admin-select">
                    <option value="multiply">倍率調整</option>
                    <option value="add">固定値加算</option>
                    <option value="set">固定値設定</option>
                </select>
            </div>

            <!-- ステータス調整 -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">ステータス調整</label>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem;">HP調整</label>
                        <input type="number" name="stat_adjustments[max_hp]" class="admin-input" step="0.1" placeholder="例: 1.2, 10, 100">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem;">攻撃力調整</label>
                        <input type="number" name="stat_adjustments[attack]" class="admin-input" step="0.1" placeholder="例: 1.2, 5, 20">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem;">防御力調整</label>
                        <input type="number" name="stat_adjustments[defense]" class="admin-input" step="0.1" placeholder="例: 1.1, 3, 15">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem;">経験値調整</label>
                        <input type="number" name="stat_adjustments[experience_reward]" class="admin-input" step="0.1" placeholder="例: 1.5, 10, 50">
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <button type="button" onclick="hideBalanceModal()" class="admin-btn admin-btn-secondary">
                    キャンセル
                </button>
                <button type="submit" class="admin-btn admin-btn-warning">
                    調整実行
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 出現率調整モーダル -->
<div id="spawn-rate-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 2rem; width: 90%; max-width: 500px;">
        <h3 style="margin-bottom: 1.5rem;">出現率調整</h3>
        <form id="spawn-rate-form" method="POST" action="{{ route('admin.monsters.spawn_rates') }}">
            @csrf
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">対象エリア</label>
                <select name="road" class="admin-select" required>
                    <option value="">エリアを選択</option>
                    @foreach($roads as $road)
                    <option value="{{ $road }}">{{ ucfirst(str_replace('_', ' ', $road)) }}</option>
                    @endforeach
                </select>
            </div>
            <div id="spawn-rate-fields">
                <!-- JavaScript で動的に更新 -->
            </div>
            <div style="display: flex; gap: 1rem; justify-content: end;">
                <button type="button" onclick="hideSpawnRateModal()" class="admin-btn admin-btn-secondary">
                    キャンセル
                </button>
                <button type="submit" class="admin-btn admin-btn-info">
                    更新
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
// ソート変更
function updateSort(value) {
    const [sortBy, sortDirection] = value.split('-');
    const url = new URL(window.location);
    url.searchParams.set('sort_by', sortBy);
    url.searchParams.set('sort_direction', sortDirection);
    window.location.href = url.toString();
}

@if(auth()->user()->can('monsters.edit'))
// バランス調整モーダル
function showBalanceModal() {
    document.getElementById('balance-modal').style.display = 'block';
}

function hideBalanceModal() {
    document.getElementById('balance-modal').style.display = 'none';
    document.getElementById('balance-form').reset();
}

function updateBalanceTargets(type) {
    document.getElementById('level-range-fields').style.display = type === 'level_range' ? 'block' : 'none';
    document.getElementById('road-fields').style.display = type === 'road_based' ? 'block' : 'none';
}

// 出現率調整モーダル
function showSpawnRateModal() {
    document.getElementById('spawn-rate-modal').style.display = 'block';
}

function hideSpawnRateModal() {
    document.getElementById('spawn-rate-modal').style.display = 'none';
    document.getElementById('spawn-rate-form').reset();
}

// バランス調整フォーム送信確認
document.getElementById('balance-form').addEventListener('submit', function(e) {
    if (!confirm('モンスターのバランスを調整しますか？\n※この操作は元に戻せません。')) {
        e.preventDefault();
    }
});

// モーダル外クリックで閉じる
document.getElementById('balance-modal').addEventListener('click', function(e) {
    if (e.target === this) hideBalanceModal();
});

document.getElementById('spawn-rate-modal').addEventListener('click', function(e) {
    if (e.target === this) hideSpawnRateModal();
});
@endif
</script>
@endsection