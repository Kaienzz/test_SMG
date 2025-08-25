@extends('admin.layouts.app')

@section('title', 'ショップ管理')

@section('content')
<div class="admin-content">
    {{-- ページヘッダー --}}
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">
                <span class="page-icon">🏪</span>
                ショップ管理
            </h1>
            <p class="page-description">各町の施設・ショップを管理します</p>
        </div>
        
        <div class="page-actions">
            @if($canManageShops ?? false)
                <a href="#" class="admin-btn admin-btn-primary" onclick="alert('新規ショップ作成機能は今後実装予定です')">
                    <span class="btn-icon">➕</span>
                    新規ショップ作成
                </a>
            @endif
        </div>
    </div>

    {{-- 統計情報 --}}
    <div class="stats-grid stats-grid-4">
        <div class="stat-card">
            <div class="stat-card-header">
                <h3>総ショップ数</h3>
                <span class="stat-icon">🏪</span>
            </div>
            <div class="stat-card-value">{{ $shops->total() }}</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <h3>町数</h3>
                <span class="stat-icon">🏘️</span>
            </div>
            <div class="stat-card-value">{{ $shopsByLocation->count() }}</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <h3>ショップタイプ数</h3>
                <span class="stat-icon">🏬</span>
            </div>
            <div class="stat-card-value">{{ $shopsByType->count() }}</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <h3>アクティブショップ</h3>
                <span class="stat-icon">✅</span>
            </div>
            <div class="stat-card-value">
                {{ $shopsByLocation->sum('active_count') }}
            </div>
        </div>
    </div>

    {{-- フィルター --}}
    <div class="filter-section">
        <form method="GET" action="{{ route('admin.shops.index') }}" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="location_id" class="filter-label">町で絞り込み</label>
                    <select name="location_id" id="location_id" class="admin-select">
                        <option value="">すべての町</option>
                        @foreach($shopsByLocation->unique('location_id') as $location)
                            <option value="{{ $location->location_id }}" 
                                {{ $filters['location_id'] === $location->location_id ? 'selected' : '' }}>
                                {{ $location->location_id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="shop_type" class="filter-label">ショップタイプ</label>
                    <select name="shop_type" id="shop_type" class="admin-select">
                        <option value="">すべてのタイプ</option>
                        @foreach($shopTypes as $shopType)
                            <option value="{{ $shopType->value }}" 
                                {{ $filters['shop_type'] === $shopType->value ? 'selected' : '' }}>
                                {{ $shopType->getIcon() }} {{ $shopType->getDisplayName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search" class="filter-label">名前で検索</label>
                    <input type="text" name="search" id="search" 
                           value="{{ $filters['search'] }}" 
                           placeholder="ショップ名を入力..."
                           class="admin-input">
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <span class="btn-icon">🔍</span>
                        検索
                    </button>
                    <a href="{{ route('admin.shops.index') }}" class="admin-btn admin-btn-secondary">
                        <span class="btn-icon">🔄</span>
                        リセット
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 町別ショップ一覧 --}}
    <div class="content-section">
        <h2 class="section-title">町別ショップ一覧</h2>
        
        <div class="location-shops-grid">
            @foreach($shopsByLocation as $location)
                <div class="location-shop-card">
                    <div class="location-header">
                        <h3 class="location-name">
                            <span class="location-icon">🏘️</span>
                            {{ $location->location_id }}
                        </h3>
                        <div class="location-stats">
                            <span class="shop-count">{{ $location->shop_count }}店舗</span>
                            <span class="active-count">({{ $location->active_count }}稼働中)</span>
                        </div>
                    </div>
                    
                    <div class="location-shops">
                        @php
                            $locationShops = $shops->getCollection()->where('location_id', $location->location_id);
                        @endphp
                        
                        @if($locationShops->count() > 0)
                            <div class="shop-list">
                                @foreach($locationShops as $shop)
                                    @php
                                        $shopType = \App\Enums\ShopType::from($shop->shop_type);
                                    @endphp
                                    <div class="shop-item {{ !$shop->is_active ? 'shop-inactive' : '' }}">
                                        <div class="shop-icon">{{ $shopType->getIcon() }}</div>
                                        <div class="shop-info">
                                            <div class="shop-name">{{ $shop->name }}</div>
                                            <div class="shop-type">{{ $shopType->getDisplayName() }}</div>
                                        </div>
                                        <div class="shop-status">
                                            @if($shop->is_active)
                                                <span class="status-badge status-active">稼働中</span>
                                            @else
                                                <span class="status-badge status-inactive">停止中</span>
                                            @endif
                                        </div>
                                        <div class="shop-actions">
                                            <button class="admin-btn admin-btn-sm admin-btn-secondary" 
                                                    onclick="alert('ショップ詳細機能は今後実装予定です')">
                                                詳細
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="no-shops">
                                <p>この町にはショップがありません</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ショップタイプ別統計 --}}
    <div class="content-section">
        <h2 class="section-title">ショップタイプ別統計</h2>
        
        <div class="shop-type-stats">
            @foreach($shopsByType as $typeStats)
                @php
                    $shopType = \App\Enums\ShopType::from($typeStats->shop_type);
                @endphp
                <div class="shop-type-card">
                    <div class="shop-type-header">
                        <span class="shop-type-icon">{{ $shopType->getIcon() }}</span>
                        <h3>{{ $shopType->getDisplayName() }}</h3>
                    </div>
                    <div class="shop-type-stats-content">
                        <div class="stat-row">
                            <span class="stat-label">総数:</span>
                            <span class="stat-value">{{ $typeStats->count }}店舗</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">稼働中:</span>
                            <span class="stat-value">{{ $typeStats->active_count }}店舗</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ページネーション --}}
    @if($shops->hasPages())
        <div class="pagination-section">
            {{ $shops->withQueryString()->links() }}
        </div>
    @endif
</div>

<style>
.location-shops-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: var(--spacing-4);
    margin-top: var(--spacing-4);
}

.location-shop-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
}

.location-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-3);
    padding-bottom: var(--spacing-2);
    border-bottom: 1px solid var(--color-border-light);
}

.location-name {
    margin: 0;
    font-size: var(--font-size-lg);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.location-stats {
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
}

.shop-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.shop-item {
    display: flex;
    align-items: center;
    padding: var(--spacing-2);
    border: 1px solid var(--color-border-light);
    border-radius: var(--radius-md);
    background: var(--color-surface-light);
}

.shop-item.shop-inactive {
    opacity: 0.6;
    background: var(--color-surface-muted);
}

.shop-icon {
    font-size: var(--font-size-xl);
    margin-right: var(--spacing-2);
}

.shop-info {
    flex: 1;
}

.shop-name {
    font-weight: var(--font-weight-semibold);
    margin-bottom: var(--spacing-1);
}

.shop-type {
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
}

.shop-status {
    margin-right: var(--spacing-2);
}

.status-badge {
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
}

.status-active {
    background: var(--color-success-light);
    color: var(--color-success-dark);
}

.status-inactive {
    background: var(--color-warning-light);
    color: var(--color-warning-dark);
}

.shop-type-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-3);
    margin-top: var(--spacing-4);
}

.shop-type-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--spacing-3);
}

.shop-type-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-2);
}

.shop-type-icon {
    font-size: var(--font-size-xl);
}

.stat-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--spacing-1);
}

.no-shops {
    text-align: center;
    padding: var(--spacing-4);
    color: var(--color-text-secondary);
    font-style: italic;
}
</style>
@endsection