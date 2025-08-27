@extends('admin.layouts.app')

@section('title', '採集管理')
@section('subtitle', 'ゲーム内採集システムの統合管理')

@section('content')
<div class="admin-content-container">
    {{-- エラー表示 --}}
    @if(isset($error))
        <div style="padding: 1rem; margin-bottom: 1rem; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem; color: #dc2626;">
            <span style="margin-right: 0.5rem;">⚠️</span>
            {{ $error }}
        </div>
    @endif

    {{-- アクションボタン --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: bold; margin: 0; margin-bottom: 0.25rem;">🌿 採集管理</h1>
            <p style="color: var(--admin-secondary); margin: 0;">各ルートでの採集可能アイテムを管理します（道路・ダンジョン対応）</p>
        </div>
        
        <div style="display: flex; gap: 0.75rem;">
            @if(auth()->user()->can('gathering.view'))
                <a href="{{ route('admin.gathering.stats') }}" class="admin-btn admin-btn-info">
                    📊 詳細統計
                </a>
            @endif
            
            @if(auth()->user()->can('gathering.create'))
                <a href="{{ route('admin.gathering.create') }}" class="admin-btn admin-btn-primary">
                    ➕ 新しい採集設定
                </a>
            @endif
            
            @if(auth()->user()->can('gathering.create'))
                <form method="POST" action="{{ route('admin.gathering.migrate-from-legacy') }}" style="display: inline;" onsubmit="return confirm('既存データを新システムに移行しますか？')">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn-warning">
                        🔄 データ移行
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- 統計カード -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    🗺️ {{ count($routeStats ?? []) }}
                </div>
                <div style="color: var(--admin-secondary);">採集可能ルート</div>
                <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                    @if(isset($environmentStats))
                    道路: {{ collect($environmentStats)->where('category', 'road')->first()['total_routes'] ?? 0 }} / 
                    ダンジョン: {{ collect($environmentStats)->where('category', 'dungeon')->first()['total_routes'] ?? 0 }}
                    @endif
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    🌿 {{ $systemSummary['total_mappings'] ?? 0 }}
                </div>
                <div style="color: var(--admin-secondary);">総採集アイテム</div>
                <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                    設定済みアイテム数
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    ✅ {{ $systemSummary['active_mappings'] ?? 0 }}
                </div>
                <div style="color: var(--admin-secondary);">アクティブ設定</div>
                <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                    アクティブな採集設定数
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    📈 {{ $systemSummary['configuration_completion'] ?? 0 }}%
                </div>
                <div style="color: var(--admin-secondary);">設定完了度</div>
                <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                    ルート設定カバー率
                </div>
            </div>
        </div>
    </div>

    <!-- フィルター・検索 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">検索・フィルター</h3>
            <button type="button" id="toggle-filters" class="admin-btn admin-btn-secondary admin-btn-sm">
                🔽 表示切替
            </button>
        </div>
        <div class="admin-card-body" id="filter-section">
            <form method="GET" action="{{ route('admin.gathering.index') }}">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">ルート</label>
                        <select name="route_id" class="admin-select">
                            <option value="">全てのルート</option>
                            @foreach($routes ?? [] as $route)
                            <option value="{{ $route->id }}" {{ (request('route_id') === $route->id) ? 'selected' : '' }}>
                                [{{ $route->category === 'road' ? '道路' : 'ダンジョン' }}] {{ $route->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">採集環境</label>
                        <select name="gathering_environment" class="admin-select">
                            <option value="">全ての環境</option>
                            @foreach($gatheringEnvironments ?? [] as $env)
                            <option value="{{ $env }}" {{ request('gathering_environment') === $env ? 'selected' : '' }}>
                                {{ $env === 'road' ? '道路' : 'ダンジョン' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">アイテムカテゴリ</label>
                        <select name="item_category" class="admin-select">
                            <option value="">全てのカテゴリ</option>
                            @foreach($itemCategories ?? [] as $category)
                            <option value="{{ $category }}" {{ request('item_category') === $category ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">必要スキルレベル</label>
                        <input type="number" name="skill_level" value="{{ request('skill_level') }}" 
                               placeholder="レベル以下を表示" min="1" max="100" class="admin-input">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">状態</label>
                        <select name="is_active" class="admin-select">
                            <option value="">全ての状態</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>アクティブ</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>非アクティブ</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        🔍 フィルタ適用
                    </button>
                    <a href="{{ route('admin.gathering.index') }}" class="admin-btn admin-btn-secondary">
                        🔄 リセット
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 採集設定一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">採集設定一覧</h3>
            <div style="color: var(--admin-secondary); font-size: 0.875rem;">
                {{ $gatheringMappings->count() }}件の設定
            </div>
        </div>
        <div class="admin-card-body">
            @if($gatheringMappings && $gatheringMappings->count() > 0)
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ルート</th>
                            <th>環境</th>
                            <th>アイテム</th>
                            <th>必要スキルLv</th>
                            <th>成功率</th>
                            <th>数量範囲</th>
                            <th>状態</th>
                            <th style="width: 150px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gatheringMappings as $mapping)
                        <tr>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <strong>{{ $mapping->route?->name ?? '不明なルート' }}</strong>
                                    <small style="color: var(--admin-secondary);">{{ $mapping->route_id }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="admin-badge" style="
                                    background: {{ $mapping->route?->category === 'road' ? 'var(--admin-primary)' : 'var(--admin-secondary)' }};
                                    color: white;
                                    padding: 0.25rem 0.5rem;
                                    border-radius: 0.25rem;
                                    font-size: 0.75rem;
                                    font-weight: bold;">
                                    {{ $mapping->route?->category === 'road' ? '道路' : 'ダンジョン' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <strong>{{ $mapping->item?->name ?? '不明なアイテム' }}</strong>
                                    <small style="color: var(--admin-secondary);">{{ $mapping->item?->getCategoryName() ?? '-' }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="admin-badge" style="
                                    background: var(--admin-info);
                                    color: white;
                                    padding: 0.25rem 0.5rem;
                                    border-radius: 0.25rem;
                                    font-size: 0.75rem;
                                    font-weight: bold;">
                                    Lv.{{ $mapping->required_skill_level }}
                                </span>
                            </td>
                            <td>
                                <div style="min-width: 80px;">
                                    <div style="font-weight: bold; margin-bottom: 2px; font-size: 12px;">{{ $mapping->success_rate }}%</div>
                                    <div style="width: 100%; height: 4px; background-color: #e0e0e0; border-radius: 2px; overflow: hidden;">
                                        <div style="height: 100%; background: linear-gradient(90deg, #ff4444 0%, #ffaa00 50%, #44ff44 100%); width: {{ $mapping->success_rate }}%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: bold; color: #666;">{{ $mapping->quantity_min }}-{{ $mapping->quantity_max }}</span>
                            </td>
                            <td>
                                <span class="admin-badge" style="
                                    background: {{ $mapping->is_active ? 'var(--admin-success)' : 'var(--admin-danger)' }};
                                    color: white;
                                    padding: 0.25rem 0.5rem;
                                    border-radius: 0.25rem;
                                    font-size: 0.75rem;
                                    font-weight: bold;">
                                    {{ $mapping->is_active ? 'アクティブ' : '非アクティブ' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.25rem;">
                                    @if(auth()->user()->can('gathering.edit'))
                                    <a href="{{ route('admin.gathering.edit', $mapping) }}" 
                                       class="admin-btn admin-btn-sm admin-btn-warning" title="編集">
                                        ✏️
                                    </a>
                                    
                                    <button type="button" 
                                            class="admin-btn admin-btn-sm admin-btn-info toggle-status-btn" 
                                            data-mapping-id="{{ $mapping->id }}"
                                            data-current-status="{{ $mapping->is_active ? '1' : '0' }}"
                                            title="{{ $mapping->is_active ? '非アクティブにする' : 'アクティブにする' }}">
                                        {{ $mapping->is_active ? '⏸️' : '▶️' }}
                                    </button>
                                    @endif
                                    
                                    @if(auth()->user()->can('gathering.delete'))
                                    <form method="POST" action="{{ route('admin.gathering.destroy', $mapping) }}" 
                                          style="display: inline;" 
                                          onsubmit="return confirm('この採集設定を削除してもよろしいですか？')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" title="削除">
                                            🗑️
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; padding: 3rem 1rem; color: var(--admin-secondary);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🌿</div>
                <h3>採集設定がありません</h3>
                <p>まだ採集設定が作成されていません。新しい採集設定を作成してください。</p>
                @if(auth()->user()->can('gathering.create'))
                <a href="{{ route('admin.gathering.create') }}" class="admin-btn admin-btn-primary">
                    ➕ 最初の採集設定を作成
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
/* 採集管理画面専用スタイル */
.success-rate {
    min-width: 80px;
}

.rate-value {
    font-weight: bold;
    margin-bottom: 2px;
    display: block;
    font-size: 12px;
}

.rate-bar {
    width: 100%;
    height: 4px;
    background-color: #e0e0e0;
    border-radius: 2px;
    overflow: hidden;
}

.rate-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff4444 0%, #ffaa00 50%, #44ff44 100%);
    transition: width 0.5s ease-in-out;
    width: 0%;
}

.location-info, .item-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.quantity-range {
    font-weight: bold;
    color: #666;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-weight: bold;
    color: #333;
    font-size: 13px;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e0e0e0;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #666;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.admin-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.admin-badge-primary { background-color: #007bff; color: white; }
.admin-badge-secondary { background-color: #6c757d; color: white; }
.admin-badge-success { background-color: #28a745; color: white; }
.admin-badge-danger { background-color: #dc3545; color: white; }
.admin-badge-warning { background-color: #ffc107; color: #212529; }
.admin-badge-info { background-color: #17a2b8; color: white; }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // フィルタ表示/非表示切り替え
    const toggleFiltersBtn = document.getElementById('toggle-filters');
    const filterSection = document.getElementById('filter-section');
    
    if (toggleFiltersBtn && filterSection) {
        toggleFiltersBtn.addEventListener('click', function() {
            const isVisible = filterSection.style.display !== 'none';
            filterSection.style.display = isVisible ? 'none' : 'block';
            
            const icon = this.querySelector('.btn-icon');
            icon.textContent = isVisible ? '🔽' : '🔼';
        });
    }
    
    // 成功率バーのアニメーション
    const rateBars = document.querySelectorAll('.rate-fill');
    rateBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
    
    // ステータス切り替え
    const toggleButtons = document.querySelectorAll('.toggle-status-btn');
    toggleButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const mappingId = this.dataset.mappingId;
            const currentStatus = this.dataset.currentStatus === '1';
            
            try {
                this.disabled = true;
                this.innerHTML = '<span class="btn-icon">⏳</span>';
                
                const response = await fetch(`/admin/gathering/${mappingId}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // ページをリロードして最新状態を表示
                    window.location.reload();
                } else {
                    alert('エラー: ' + result.message);
                    this.disabled = false;
                    this.innerHTML = currentStatus 
                        ? '<span class="btn-icon">⏸️</span>' 
                        : '<span class="btn-icon">▶️</span>';
                }
            } catch (error) {
                alert('通信エラーが発生しました: ' + error.message);
                this.disabled = false;
                this.innerHTML = currentStatus 
                    ? '<span class="btn-icon">⏸️</span>' 
                    : '<span class="btn-icon">▶️</span>';
            }
        });
    });
});
</script>
@endsection