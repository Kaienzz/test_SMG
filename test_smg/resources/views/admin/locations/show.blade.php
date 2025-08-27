@extends('admin.layouts.app')

@section('title', 'ロケーション詳細')
@section('subtitle', ($location['name'] ?? 'ロケーション') . ' の詳細情報')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary);">ダッシュボード</a></li>
            <li>/</li>
            <li><a href="{{ route('admin.locations.index') }}" style="color: var(--admin-primary);">ロケーション管理</a></li>
            <li>/</li>
            <li>{{ $location['name'] ?? 'ロケーション詳細' }}</li>
        </ol>
    </nav>

    <!-- ロケーションヘッダー -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div>
                    <h3 class="admin-card-title" style="margin: 0; font-size: 1.5rem;">
                        @if(isset($location['category']))
                            @php
                                $categoryIcon = match($location['category']) {
                                    'road' => 'fas fa-road',
                                    'dungeon' => 'fas fa-dungeon',
                                    'town' => 'fas fa-city',
                                    default => 'fas fa-map-marker-alt'
                                };
                            @endphp
                            <i class="{{ $categoryIcon }}" style="color: var(--admin-primary);"></i>
                        @endif
                        {{ $location['name'] ?? 'ロケーション詳細' }}
                    </h3>
                    <div style="margin-top: 0.5rem;">
                        <span class="admin-badge admin-badge-{{ $location['category'] === 'road' ? 'primary' : ($location['category'] === 'dungeon' ? 'danger' : 'info') }}">
                            {{ $location['category'] === 'road' ? '道路' : ($location['category'] === 'dungeon' ? 'ダンジョン' : ($location['category'] === 'town' ? '町' : $location['category'])) }}
                        </span>
                        <span style="margin-left: 0.5rem; color: var(--admin-secondary); font-size: 0.875rem;">
                            ID: {{ $location['id'] }}
                        </span>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                @if(auth()->user()->can('locations.edit'))
                    @php
                        $editRoute = match($location['category'] ?? '') {
                            'town' => route('admin.towns.edit', $location['id']),
                            'road' => route('admin.roads.edit', $location['id']),
                            'dungeon' => route('admin.dungeons.edit', $location['id']),
                            default => route('admin.locations.index')
                        };
                    @endphp
                    <a href="{{ $editRoute }}" class="admin-btn admin-btn-primary">
                        <i class="fas fa-edit"></i> 編集
                    </a>
                @endif
                @if(isset($location['modules']['monster_spawns']) && auth()->user()->can('monsters.view'))
                <a href="{{ route('admin.monster-spawns.show', $location['id']) }}" class="admin-btn admin-btn-success">
                    <i class="fas fa-dragon"></i> スポーン管理
                </a>
                @endif
                @php
                    $indexRoute = match($location['category'] ?? '') {
                        'town' => route('admin.towns.index'),
                        'road' => route('admin.roads.index'),
                        'dungeon' => route('admin.dungeons.index'),
                        default => route('admin.locations.index')
                    };
                @endphp
                <a href="{{ $indexRoute }}" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-list"></i> 一覧に戻る
                </a>
            </div>
        </div>
    </div>

    <!-- モジュールタブ表示 -->
    @if(isset($location['modules']) && count($location['modules']) > 0)
    <div class="admin-card">
        <div class="admin-card-body" style="padding: 0;">
            <!-- タブナビゲーション -->
            <nav style="border-bottom: 1px solid var(--admin-border);">
                <div style="display: flex; overflow-x: auto;">
                    @foreach($location['modules'] as $moduleKey => $module)
                    <button type="button" 
                            class="module-tab" 
                            data-target="{{ $moduleKey }}"
                            style="padding: 1rem 1.5rem; border: none; background: none; cursor: pointer; border-bottom: 3px solid transparent; white-space: nowrap; font-weight: 500; color: var(--admin-secondary); transition: all 0.2s;"
                            @if($loop->first) data-active="true" @endif>
                        <i class="{{ $module['icon'] ?? 'fas fa-info' }}" style="margin-right: 0.5rem;"></i>
                        {{ $module['title'] }}
                    </button>
                    @endforeach
                </div>
            </nav>

            <!-- タブコンテンツ -->
            <div style="padding: 2rem;">
                @foreach($location['modules'] as $moduleKey => $module)
                <div class="module-content" 
                     data-module="{{ $moduleKey }}" 
                     style="display: {{ $loop->first ? 'block' : 'none' }};">
                    
                    @if($moduleKey === 'basic_info')
                        @include('admin.locations.modules.basic-info', ['data' => $module['data']])
                    @elseif($moduleKey === 'monster_spawns')
                        @include('admin.locations.modules.monster-spawns', ['data' => $module['data']])
                    @elseif($moduleKey === 'connections')
                        @include('admin.locations.modules.connections', ['data' => $module['data']])
                    @elseif($moduleKey === 'gathering')
                        @include('admin.locations.modules.gathering', ['data' => $module['data']])
                    @elseif($moduleKey === 'events')
                        @include('admin.locations.modules.events', ['data' => $module['data']])
                    @elseif($moduleKey === 'town_facilities')
                        @include('admin.locations.modules.town-facilities', ['data' => $module['data']])
                    @else
                        <!-- 汎用モジュール表示 -->
                        <div style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                            <h4>{{ $module['title'] }}</h4>
                            <p>このモジュールはまだ実装されていません。</p>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- デバッグ情報（開発環境のみ） -->
    @if(config('app.debug') && auth()->user()->can('system.debug'))
    <div class="admin-card" style="margin-top: 2rem; border-left: 4px solid var(--admin-info);">
        <div class="admin-card-header">
            <h4 class="admin-card-title">🔧 デバッグ情報</h4>
        </div>
        <div class="admin-card-body">
            <details>
                <summary style="cursor: pointer; font-weight: 500; padding: 0.5rem;">データ構造を表示</summary>
                <pre style="background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; margin-top: 1rem; font-size: 0.875rem;">{{ json_encode($location, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>
        </div>
    </div>
    @endif
</div>

<script>
// タブ切り替え機能
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.module-tab');
    const contents = document.querySelectorAll('.module-content');

    // 初期状態設定
    tabs.forEach(tab => {
        if (tab.dataset.active === 'true') {
            activateTab(tab);
        }
    });

    // タブクリックイベント
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // 全タブを非アクティブ化
            tabs.forEach(t => deactivateTab(t));
            contents.forEach(c => c.style.display = 'none');

            // クリックされたタブをアクティブ化
            activateTab(this);
            const targetContent = document.querySelector(`[data-module="${this.dataset.target}"]`);
            if (targetContent) {
                targetContent.style.display = 'block';
            }
        });
    });

    function activateTab(tab) {
        tab.style.color = 'var(--admin-primary)';
        tab.style.borderBottomColor = 'var(--admin-primary)';
        tab.style.backgroundColor = '#f8f9fa';
    }

    function deactivateTab(tab) {
        tab.style.color = 'var(--admin-secondary)';
        tab.style.borderBottomColor = 'transparent';
        tab.style.backgroundColor = 'transparent';
    }

    // キーボードナビゲーション
    tabs.forEach((tab, index) => {
        tab.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                const nextIndex = e.key === 'ArrowRight' 
                    ? (index + 1) % tabs.length 
                    : (index - 1 + tabs.length) % tabs.length;
                tabs[nextIndex].click();
                tabs[nextIndex].focus();
            }
        });
        tab.setAttribute('tabindex', '0');
    });
});

// アニメーション効果
function fadeInContent(element) {
    element.style.opacity = '0';
    element.style.display = 'block';
    element.style.transition = 'opacity 0.2s ease-in-out';
    
    requestAnimationFrame(() => {
        element.style.opacity = '1';
    });
}
</script>

<style>
/* 管理画面固有のスタイル */
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

/* タブスタイリング */
.module-tab:hover {
    background-color: #f8f9fa !important;
    color: var(--admin-primary) !important;
}

.module-tab:focus {
    outline: 2px solid var(--admin-primary);
    outline-offset: -2px;
}

/* コンテンツエリア */
.module-content {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .admin-card-header > div:first-child {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem !important;
    }
    
    .admin-card-header > div:last-child {
        flex-wrap: wrap;
    }
    
    .module-tab {
        font-size: 0.875rem !important;
        padding: 0.75rem 1rem !important;
    }
}
</style>
@endsection