@extends('admin.layouts.app')

@section('title', '標準アイテム管理')
@section('subtitle', 'JSONファイルベースの標準アイテム管理')

@section('content')
<div class="admin-content-container">
    
    <!-- 統計情報 -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ number_format($stats['total_items']) }}
                </div>
                <div style="color: var(--admin-secondary);">標準アイテム数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ number_format($stats['by_category']->count()) }}
                </div>
                <div style="color: var(--admin-secondary);">カテゴリ数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ number_format($stats['avg_value'] ?? 0) }}G
                </div>
                <div style="color: var(--admin-secondary);">平均価格</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    JSONファイル
                </div>
                <div style="color: var(--admin-secondary);">管理方式</div>
            </div>
        </div>
    </div>

    <!-- 操作ボタン -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-body">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.25rem;">標準アイテム管理</h3>
                
                <div style="display: flex; gap: 0.5rem;">
                    <form action="{{ route('admin.items.standard.backup') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="admin-btn admin-btn-outline-info">
                            <i class="fas fa-download"></i> バックアップ作成
                        </button>
                    </form>
                    
                    <a href="{{ route('admin.items.standard.create') }}" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i> 新規作成
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- フィルター -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.items.standard') }}">
                <div style="display: grid; grid-template-columns: 1fr 200px auto; gap: 1rem; align-items: end;">
                    <div>
                        <label for="search" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">検索</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                               placeholder="アイテム名・説明で検索..."
                               style="width: 100%; padding: 0.5rem; border: 1px solid var(--admin-border); border-radius: 4px;">
                    </div>

                    <div>
                        <label for="category" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">カテゴリ</label>
                        <select id="category" name="category" style="width: 100%; padding: 0.5rem; border: 1px solid var(--admin-border); border-radius: 4px;">
                            <option value="">全カテゴリ</option>
                            @foreach(App\Enums\ItemCategory::cases() as $category)
                                <option value="{{ $category->value }}" 
                                        {{ request('category') === $category->value ? 'selected' : '' }}>
                                    {{ $category->getDisplayName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-search"></i> 検索
                        </button>
                        @if(request()->hasAny(['search', 'category']))
                            <a href="{{ route('admin.items.standard') }}" class="admin-btn admin-btn-outline-secondary" style="margin-left: 0.5rem;">
                                クリア
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- アイテム一覧 -->
    <div class="admin-card">
        <div class="admin-card-body">
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名前</th>
                            <th>カテゴリ</th>
                            <th>価格</th>
                            <th>効果</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($standardItems as $item)
                            <tr>
                                <td>
                                    <span class="badge" style="background-color: var(--admin-info); color: white;">
                                        {{ $item['id'] }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 500;">{{ $item['name'] }}</div>
                                    <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                        {{ Str::limit($item['description'], 50) }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: var(--admin-secondary); color: white;">
                                        {{ $item['category_name'] ?? $item['category'] }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 500;">{{ number_format($item['value']) }}G</span>
                                    @if(isset($item['sell_price']))
                                        <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                                            売却: {{ number_format($item['sell_price']) }}G
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($item['effects']))
                                        <div style="font-size: 0.875rem;">
                                            @foreach($item['effects'] as $effect => $value)
                                                <div>{{ $effect }}: +{{ $value }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: var(--admin-secondary);">なし</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.25rem;">
                                        <a href="{{ route('admin.items.standard.show', $item['id']) }}" 
                                           class="admin-btn admin-btn-sm admin-btn-outline-primary" title="詳細">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.items.standard.edit', $item['id']) }}" 
                                           class="admin-btn admin-btn-sm admin-btn-outline-warning" title="編集">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--admin-secondary);">
                                    アイテムが見つかりません
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection