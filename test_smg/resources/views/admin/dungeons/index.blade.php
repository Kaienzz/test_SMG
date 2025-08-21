@extends('admin.layouts.app')

@section('title', 'Dungeon管理')
@section('subtitle', 'ゲーム内ダンジョンの管理')

@section('content')
<div class="admin-content-container">
    
    <!-- 統計カード -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ $dungeons->total() }}
                </div>
                <div style="color: var(--admin-secondary);">総ダンジョン数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ $dungeons->where('is_active', true)->count() }}
                </div>
                <div style="color: var(--admin-secondary);">アクティブ数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ $dungeons->sum('floors_count') }}
                </div>
                <div style="color: var(--admin-secondary);">総フロア数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ number_format($dungeons->avg('floors_count') ?? 0, 1) }}
                </div>
                <div style="color: var(--admin-secondary);">平均フロア数</div>
            </div>
        </div>
    </div>

    <!-- アクションバー -->
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">ダンジョン一覧</h2>
        <div style="display: flex; gap: 1rem;">
            @if(auth()->user()->can('locations.edit'))
            <a href="{{ route('admin.dungeons.create') }}" class="admin-btn admin-btn-primary">
                <i class="fas fa-plus"></i> 新規ダンジョン作成
            </a>
            @endif
        </div>
    </div>

    @if(isset($error))
        <div class="admin-alert admin-alert-danger" style="margin-bottom: 2rem;">
            {{ $error }}
        </div>
    @endif

    <!-- ダンジョン一覧テーブル（トグル機能付き） -->
    <div class="admin-card">
        <div class="admin-card-body" style="padding: 0;">
            @if($dungeons->count() > 0)
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>ダンジョン名</th>
                            <th>ダンジョンID</th>
                            <th>フロア数</th>
                            <th>ステータス</th>
                            <th>作成日</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dungeons as $dungeon)
                        <tr x-data="{ expanded: false }" class="dungeon-row">
                            <td>
                                @if($dungeon->floors_count > 0)
                                <button @click="expanded = !expanded" 
                                        class="admin-btn admin-btn-sm admin-btn-ghost"
                                        style="padding: 0.25rem;"
                                        title="フロア一覧を表示/非表示">
                                    <i class="fas" :class="expanded ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                </button>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $dungeon->dungeon_name }}</div>
                                @if($dungeon->dungeon_desc)
                                <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                    {{ Str::limit($dungeon->dungeon_desc, 60) }}
                                </div>
                                @endif
                            </td>
                            <td>
                                <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                    {{ $dungeon->dungeon_id }}
                                </code>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-info">
                                    {{ $dungeon->floors_count }} フロア
                                </span>
                                @if($dungeon->floors_count > 0)
                                <a href="{{ route('admin.dungeons.floors', $dungeon->id) }}" 
                                   class="admin-btn admin-btn-xs admin-btn-secondary" 
                                   style="margin-left: 0.5rem;"
                                   title="フロア管理">
                                    <i class="fas fa-cog"></i>
                                </a>
                                @endif
                            </td>
                            <td>
                                @if($dungeon->is_active)
                                <span class="admin-badge admin-badge-success">アクティブ</span>
                                @else
                                <span class="admin-badge admin-badge-secondary">非アクティブ</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                                    {{ $dungeon->created_at->format('Y/m/d H:i') }}
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('admin.dungeons.show', $dungeon->id) }}" 
                                       class="admin-btn admin-btn-sm admin-btn-info" title="詳細">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(auth()->user()->can('locations.edit'))
                                    <a href="{{ route('admin.dungeons.edit', $dungeon->id) }}" 
                                       class="admin-btn admin-btn-sm admin-btn-warning" title="編集">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                    @if(auth()->user()->can('locations.delete'))
                                    <form method="POST" action="{{ route('admin.dungeons.destroy', $dungeon->id) }}" 
                                          style="display: inline;" 
                                          onsubmit="return confirm('このダンジョン「{{ $dungeon->dungeon_name }}」を削除してもよろしいですか？\n関連するフロア情報も削除されます。')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" title="削除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        
                        <!-- フロア一覧（トグル表示） -->
                        @if($dungeon->floors_count > 0)
                        <tr x-show="expanded" x-collapse>
                            <td colspan="7" style="background: var(--admin-bg); padding: 0;">
                                <div style="padding: 1rem; border-left: 4px solid var(--admin-primary);">
                                    <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--admin-primary);">
                                        <i class="fas fa-layer-group"></i> {{ $dungeon->dungeon_name }} のフロア
                                    </h4>
                                    <div style="display: grid; gap: 0.5rem;">
                                        @foreach($dungeon->floors as $floor)
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: white; border: 1px solid var(--admin-border); border-radius: 0.375rem;">
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                                                    {{ $floor->id }}
                                                </code>
                                                <div>
                                                    <div style="font-weight: 600;">{{ $floor->name }}</div>
                                                    @if($floor->description)
                                                    <div style="font-size: 0.75rem; color: var(--admin-secondary);">
                                                        {{ Str::limit($floor->description, 40) }}
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <span class="admin-badge admin-badge-info">長さ: {{ $floor->length }}</span>
                                                @php
                                                    $difficultyColors = ['easy' => 'success', 'normal' => 'info', 'hard' => 'danger'];
                                                    $difficultyLabels = ['easy' => '簡単', 'normal' => '普通', 'hard' => '困難'];
                                                @endphp
                                                <span class="admin-badge admin-badge-{{ $difficultyColors[$floor->difficulty] ?? 'secondary' }}">
                                                    {{ $difficultyLabels[$floor->difficulty] ?? $floor->difficulty }}
                                                </span>
                                                @if($floor->is_active)
                                                <span class="admin-badge admin-badge-success admin-badge-sm">Active</span>
                                                @else
                                                <span class="admin-badge admin-badge-secondary admin-badge-sm">Inactive</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    <div style="margin-top: 1rem; text-align: right;">
                                        <a href="{{ route('admin.dungeons.floors', $dungeon->id) }}" 
                                           class="admin-btn admin-btn-sm admin-btn-primary">
                                            <i class="fas fa-cog"></i> フロア管理
                                        </a>
                                        @if(auth()->user()->can('locations.edit'))
                                        <a href="{{ route('admin.dungeons.create-floor', $dungeon->id) }}" 
                                           class="admin-btn admin-btn-sm admin-btn-success">
                                            <i class="fas fa-plus"></i> フロア追加
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            @if($dungeons->hasPages())
            <div style="padding: 1.5rem; border-top: 1px solid var(--admin-border);">
                {{ $dungeons->links() }}
            </div>
            @endif
            @else
            <div style="text-align: center; padding: 3rem;">
                <div style="color: var(--admin-secondary); margin-bottom: 1rem;">
                    <i class="fas fa-dungeon fa-3x"></i>
                </div>
                <h3 style="margin-bottom: 0.5rem;">ダンジョンが見つかりません</h3>
                <p style="color: var(--admin-secondary); margin-bottom: 2rem;">
                    新規ダンジョンを作成してください。
                </p>
                @if(auth()->user()->can('locations.edit'))
                <a href="{{ route('admin.dungeons.create') }}" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i> 新規ダンジョン作成
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Alpine.js for toggle functionality -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
/* トグル機能用のカスタムスタイル */
.dungeon-row {
    border-bottom: 1px solid var(--admin-border);
}

.admin-btn-ghost {
    background: transparent;
    border: 1px solid transparent;
    color: var(--admin-secondary);
    transition: all 0.2s ease;
}

.admin-btn-ghost:hover {
    background: var(--admin-bg);
    border-color: var(--admin-border);
    color: var(--admin-primary);
}

.admin-btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.admin-badge-sm {
    font-size: 0.7rem;
    padding: 0.125rem 0.375rem;
}

/* Alpine.js collapse animation */
[x-cloak] { 
    display: none !important; 
}
</style>
@endsection