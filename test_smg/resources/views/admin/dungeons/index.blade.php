@extends('admin.layouts.app')

@section('title', 'Dungeon管理')
@section('subtitle', 'ゲーム内ダンジョンの管理')

@section('content')
<div class="admin-content-container">
    
    <!-- 検索・フィルター -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.dungeons.index') }}" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <!-- 検索ボックス -->
                <div style="flex: 1; min-width: 300px;">
                    <input type="text" name="search" value="{{ $searchQuery }}" 
                           placeholder="ダンジョン名またはIDで検索..."
                           class="admin-form-input" style="width: 100%;">
                </div>
                
                <!-- 非アクティブトグル -->
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="include_inactive" id="include_inactive" 
                           {{ $includeInactive ? 'checked' : '' }} 
                           class="admin-form-checkbox">
                    <label for="include_inactive" style="margin: 0; color: var(--admin-secondary); font-size: 0.875rem;">
                        非アクティブを含める
                    </label>
                </div>
                
                <!-- 検索ボタン -->
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">
                        <i class="fas fa-search"></i> 検索
                    </button>
                    @if($searchQuery || $includeInactive)
                    <a href="{{ route('admin.dungeons.index') }}" class="admin-btn admin-btn-secondary admin-btn-sm">
                        <i class="fas fa-times"></i> クリア
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- 統計カード -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ $totalStats['total_dungeons'] ?? $dungeons->total() }}
                </div>
                <div style="color: var(--admin-secondary);">総ダンジョン数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ $totalStats['active_dungeons'] ?? $dungeons->where('is_active', true)->count() }}
                </div>
                <div style="color: var(--admin-secondary);">アクティブ数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ $totalStats['total_floors'] ?? $dungeons->sum('floors_count') }}
                </div>
                <div style="color: var(--admin-secondary);">総フロア数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ number_format($totalStats['avg_floors'] ?? ($dungeons->avg('floors_count') ?? 0), 1) }}
                </div>
                <div style="color: var(--admin-secondary);">平均フロア数</div>
            </div>
        </div>
    </div>

    <!-- アクションバー -->
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">ダンジョン一覧</h2>
            @if($searchQuery || $includeInactive)
            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                @if($searchQuery)
                検索: "{{ $searchQuery }}" 
                @endif
                @if($includeInactive)
                <span class="admin-badge admin-badge-info admin-badge-sm">非アクティブ含む</span>
                @endif
                ({{ $dungeons->total() }}件表示)
            </div>
            @endif
        </div>
        <div style="display: flex; gap: 1rem;">
            @php
                $user = auth()->user();
                $permissionService = app(\App\Services\Admin\AdminPermissionService::class);
                $canView = $user && ($user->admin_level === 'super' || $permissionService->hasPermission($user, 'locations.view'));
                $canEdit = $user && ($user->admin_level === 'super' || $permissionService->hasPermission($user, 'locations.edit'));
            @endphp
            @if($canView)
            <a href="{{ route('admin.dungeons.orphans') }}" class="admin-btn admin-btn-warning">
                <i class="fas fa-tools"></i> オーファン整理
            </a>
            @endif
            @if($canEdit)
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
                        <tr class="dungeon-row">
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
                                    @php
                                        $user = auth()->user();
                                        $permissionService = app(\App\Services\Admin\AdminPermissionService::class);
                                        $canEdit = $user && ($user->admin_level === 'super' || $permissionService->hasPermission($user, 'locations.edit'));
                                        $canDelete = $user && ($user->admin_level === 'super' || $permissionService->hasPermission($user, 'locations.delete'));
                                    @endphp
                                    @if($canEdit)
                                    <a href="{{ route('admin.dungeons.edit', $dungeon->id) }}" 
                                       class="admin-btn admin-btn-sm admin-btn-warning" title="編集">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                    @if($canDelete)
                                    <form method="POST" action="{{ route('admin.dungeons.destroy', $dungeon->id) }}" 
                                          style="display: inline;" 
                                          onsubmit="return confirm('このダンジョン「{{ $dungeon->dungeon_name }}」を削除してもよろしいですか？\n子フロアは削除されず、dungeon_idがnullになります。')">
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
                @php
                    $user = auth()->user();
                    $permissionService = app(\App\Services\Admin\AdminPermissionService::class);
                    $canEdit = $user && ($user->admin_level === 'super' || $permissionService->hasPermission($user, 'locations.edit'));
                @endphp
                @if($canEdit)
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

<style>
/* ダンジョン管理画面のカスタムスタイル */
.dungeon-row {
    border-bottom: 1px solid var(--admin-border);
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