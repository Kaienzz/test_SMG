@extends('admin.layouts.app')

@section('title', 'モンスタースポーン管理')
@section('subtitle', 'Location別のモンスタースポーン設定管理（統合版）')

@section('content')
<div class="admin-content-container">
    
    <!-- エラーメッセージ -->
    @if(isset($error))
    <div class="admin-alert admin-alert-danger" style="margin-bottom: 2rem;">
        {{ $error }}
    </div>
    @endif

    <!-- 統計カード -->
    @if(!empty($stats))
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ number_format($stats['total_locations']) }}
                </div>
                <div style="color: var(--admin-secondary);">総Location数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ number_format($stats['locations_with_spawns']) }}
                </div>
                <div style="color: var(--admin-secondary);">スポーン設定済み</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ number_format($stats['total_spawns']) }}
                </div>
                <div style="color: var(--admin-secondary);">総スポーン数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ number_format($stats['active_spawns']) }}
                </div>
                <div style="color: var(--admin-secondary);">有効なスポーン</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-danger); margin-bottom: 0.5rem;">
                    {{ number_format($stats['unique_monsters']) }}
                </div>
                <div style="color: var(--admin-secondary);">登場モンスター種</div>
            </div>
        </div>
    </div>
    @endif

    <!-- フィルター・検索 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">検索・フィルター</h3>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.monster-spawns.index') }}" class="filter-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <!-- Location検索 -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Location検索</label>
                        <input type="text" name="location_search" value="{{ $filters['location_search'] ?? '' }}" 
                               placeholder="Location名・ID" class="admin-input">
                    </div>

                    <!-- モンスター検索 -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">モンスター検索</label>
                        <input type="text" name="monster_search" value="{{ $filters['monster_search'] ?? '' }}" 
                               placeholder="モンスター名" class="admin-input">
                    </div>

                    <!-- カテゴリー -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">カテゴリー</label>
                        <select name="category" class="admin-select">
                            <option value="">すべて</option>
                            <option value="road" {{ ($filters['category'] ?? '') === 'road' ? 'selected' : '' }}>道路</option>
                            <option value="dungeon" {{ ($filters['category'] ?? '') === 'dungeon' ? 'selected' : '' }}>ダンジョン</option>
                        </select>
                    </div>

                    <!-- ステータス -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">ステータス</label>
                        <select name="is_active" class="admin-select">
                            <option value="">すべて</option>
                            <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>有効のみ</option>
                            <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>無効のみ</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        🔍 検索
                    </button>
                    <a href="{{ route('admin.monster-spawns.index') }}" class="admin-btn admin-btn-secondary">
                        🔄 リセット
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Location一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Location別モンスタースポーン設定 ({{ $locations->count() }}件)</h3>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Location情報</th>
                            <th>カテゴリー</th>
                            <th>スポーン設定</th>
                            <th>出現モンスター</th>
                            <th>総出現率</th>
                            <th style="width: 150px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $location)
                        <tr>
                            <td>
                                <div>
                                    <div style="font-weight: 500; font-size: 1rem;">{{ $location->name }}</div>
                                    <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                        ID: {{ $location->id }}
                                    </div>
                                    @if($location->spawn_description)
                                    <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-top: 0.25rem; max-width: 200px;">
                                        {{ Str::limit($location->spawn_description, 60) }}
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-{{ $location->category === 'road' ? 'primary' : 'info' }}">
                                    {{ $location->category === 'road' ? '道路' : ($location->category === 'dungeon' ? 'ダンジョン' : $location->category) }}
                                </span>
                                @if($location->spawn_tags && count($location->spawn_tags) > 0)
                                <div style="margin-top: 0.5rem;">
                                    @foreach($location->spawn_tags as $tag)
                                    <span class="admin-badge admin-badge-secondary" style="font-size: 0.75rem; margin-right: 0.25rem;">
                                        {{ $tag }}
                                    </span>
                                    @endforeach
                                </div>
                                @endif
                            </td>
                            <td>
                                <div style="text-align: center;">
                                    @if($location->monsterSpawns->count() > 0)
                                        <div style="font-weight: bold; color: var(--admin-success); font-size: 1.1rem;">
                                            {{ $location->monsterSpawns->count() }}件
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--admin-secondary);">
                                            有効: {{ $location->monsterSpawns->where('is_active', true)->count() }}件
                                        </div>
                                    @else
                                        <span style="color: var(--admin-secondary); font-size: 0.875rem;">未設定</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; max-width: 200px;">
                                    @foreach($location->monsterSpawns->take(3) as $spawn)
                                    <span class="admin-badge admin-badge-{{ $spawn->is_active ? 'success' : 'secondary' }}" 
                                          style="font-size: 0.75rem;" title="{{ $spawn->monster->name }} ({{ round($spawn->spawn_rate * 100, 1) }}%)">
                                        {{ $spawn->monster->emoji ?? '👹' }} {{ Str::limit($spawn->monster->name, 8) }}
                                    </span>
                                    @endforeach
                                    @if($location->monsterSpawns->count() > 3)
                                    <span style="font-size: 0.75rem; color: var(--admin-secondary);">
                                        +{{ $location->monsterSpawns->count() - 3 }}体
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="text-align: center;">
                                    @php
                                        $totalRate = $location->monsterSpawns->sum('spawn_rate');
                                        $isComplete = $totalRate >= 0.99;
                                        $badgeClass = $isComplete ? 'success' : ($totalRate > 0.7 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="admin-badge admin-badge-{{ $badgeClass }}">
                                        {{ round($totalRate * 100, 1) }}%
                                    </span>
                                    @if(!$isComplete && $location->monsterSpawns->count() > 0)
                                    <div style="font-size: 0.65rem; color: var(--admin-warning); margin-top: 0.25rem;">
                                        未完了
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="{{ route('admin.monster-spawns.show', $location->id) }}" 
                                       class="admin-btn admin-btn-primary" 
                                       style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        詳細
                                    </a>
                                    @if(auth()->user()->can('monsters.create') && $location->monsterSpawns->count() === 0)
                                    <a href="{{ route('admin.monster-spawns.create', $location->id) }}" 
                                       class="admin-btn admin-btn-success" 
                                       style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        設定
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
                                @if(isset($error))
                                    エラーが発生しました
                                @else
                                    条件に一致するLocationが見つかりませんでした
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 統計情報サマリー -->
    @if($locations->count() > 0 && !isset($error))
    <div class="admin-card" style="margin-top: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">統計サマリー</h3>
        </div>
        <div class="admin-card-body">
            @php
                $configuredLocations = $locations->filter(fn($loc) => $loc->monsterSpawns->count() > 0);
                $completeLocations = $configuredLocations->filter(fn($loc) => $loc->monsterSpawns->sum('spawn_rate') >= 0.99);
                $totalSpawns = $locations->sum(fn($loc) => $loc->monsterSpawns->count());
                $activeSpawns = $locations->sum(fn($loc) => $loc->monsterSpawns->where('is_active', true)->count());
            @endphp
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                <div style="text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-info);">
                        {{ round(($configuredLocations->count() / max($locations->count(), 1)) * 100, 1) }}%
                    </div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">設定済み率</div>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-success);">
                        {{ round(($completeLocations->count() / max($configuredLocations->count(), 1)) * 100, 1) }}%
                    </div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">完了率</div>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-primary);">
                        {{ number_format($totalSpawns) }}
                    </div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">総スポーン数</div>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-warning);">
                        {{ round(($activeSpawns / max($totalSpawns, 1)) * 100, 1) }}%
                    </div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">有効スポーン率</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
/* 管理画面固有のスタイル調整 */
.admin-alert-danger {
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.admin-table th {
    background-color: #f9fafb;
    font-weight: 500;
    color: var(--admin-secondary);
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--admin-border);
}

.admin-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
}

.admin-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.admin-badge-primary { background-color: #dbeafe; color: #1d4ed8; }
.admin-badge-secondary { background-color: #f1f5f9; color: #475569; }
.admin-badge-success { background-color: #dcfce7; color: #166534; }
.admin-badge-warning { background-color: #fef3c7; color: #d97706; }
.admin-badge-danger { background-color: #fee2e2; color: #dc2626; }
.admin-badge-info { background-color: #e0f2fe; color: #0369a1; }
</style>
@endsection