@extends('admin.layouts.app')

@section('title', 'Road管理')
@section('subtitle', 'ゲーム内道路の管理')

@section('content')
<div class="admin-content-container">
    
    <!-- 統計カード -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ $roads->total() }}
                </div>
                <div style="color: var(--admin-secondary);">総Road数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ $roads->where('is_active', true)->count() }}
                </div>
                <div style="color: var(--admin-secondary);">アクティブ数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ number_format($roads->avg('length') ?? 0) }}
                </div>
                <div style="color: var(--admin-secondary);">平均長さ</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ number_format($roads->avg('encounter_rate') ?? 0, 2) }}
                </div>
                <div style="color: var(--admin-secondary);">平均エンカウント率</div>
            </div>
        </div>
    </div>

    <!-- アクションバー -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">Road一覧</h2>
        <div style="display: flex; gap: 1rem;">
            @if($canManageGameData ?? true)
            <a href="{{ route('admin.roads.create') }}" class="admin-btn admin-btn-primary">
                <i class="fas fa-plus"></i> 新規Road作成
            </a>
            @endif
        </div>
    </div>

    @if(isset($error))
        <div class="admin-alert admin-alert-danger" style="margin-bottom: 2rem;">
            {{ $error }}
        </div>
    @endif

    <!-- Road一覧テーブル -->
    <div class="admin-card">
        <div class="admin-card-body" style="padding: 0;">
            @if($roads->count() > 0)
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名前</th>
                            <th>長さ</th>
                            <th>難易度</th>
                            <th>エンカウント率</th>
                            <th>ステータス</th>
                            <th>作成日</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roads as $road)
                        <tr>
                            <td>
                                <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                    {{ $road->id }}
                                </code>
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $road->name }}</div>
                                @if($road->description)
                                <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                    {{ Str::limit($road->description, 50) }}
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-info">
                                    {{ $road->length }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $difficultyColors = [
                                        'easy' => 'success',
                                        'normal' => 'info', 
                                        'hard' => 'danger'
                                    ];
                                    $difficultyLabels = [
                                        'easy' => '簡単',
                                        'normal' => '普通',
                                        'hard' => '困難'
                                    ];
                                @endphp
                                <span class="admin-badge admin-badge-{{ $difficultyColors[$road->difficulty] ?? 'secondary' }}">
                                    {{ $difficultyLabels[$road->difficulty] ?? $road->difficulty }}
                                </span>
                            </td>
                            <td>
                                @if($road->encounter_rate)
                                {{ number_format($road->encounter_rate * 100, 1) }}%
                                @else
                                <span style="color: var(--admin-secondary);">未設定</span>
                                @endif
                            </td>
                            <td>
                                @if($road->is_active)
                                <span class="admin-badge admin-badge-success">アクティブ</span>
                                @else
                                <span class="admin-badge admin-badge-secondary">非アクティブ</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                                    {{ $road->created_at->format('Y/m/d H:i') }}
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('admin.roads.show', $road->id) }}" 
                                       class="admin-btn admin-btn-sm admin-btn-info" title="詳細">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($canManageGameData ?? true)
                                    <a href="{{ route('admin.roads.edit', $road->id) }}" 
                                       class="admin-btn admin-btn-sm admin-btn-warning" title="編集">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                    @if($canManageGameData ?? true)
                                    <form method="POST" action="{{ route('admin.roads.destroy', $road->id) }}" 
                                          style="display: inline;" 
                                          onsubmit="return confirm('このRoadを削除してもよろしいですか？')">
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
            @if($roads->hasPages())
            <div style="padding: 1.5rem; border-top: 1px solid var(--admin-border);">
                {{ $roads->links() }}
            </div>
            @endif
            @else
            <div style="text-align: center; padding: 3rem;">
                <div style="color: var(--admin-secondary); margin-bottom: 1rem;">
                    <i class="fas fa-road fa-3x"></i>
                </div>
                <h3 style="margin-bottom: 0.5rem;">Roadが見つかりません</h3>
                <p style="color: var(--admin-secondary); margin-bottom: 2rem;">
                    新規Roadを作成してください。
                </p>
                @if($canManageGameData ?? true)
                <a href="{{ route('admin.roads.create') }}" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i> 新規Road作成
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection