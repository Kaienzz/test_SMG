@extends('admin.layouts.app')

@section('title', 'Road詳細')
@section('subtitle', $road->name . ' の詳細情報')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; list-style: none; margin: 0; padding: 0; font-size: 0.875rem;">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary); text-decoration: none;">ダッシュボード</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.roads.index') }}" style="color: var(--admin-primary); text-decoration: none;">Road管理</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li style="color: var(--admin-secondary);">{{ $road->name }}</li>
        </ol>
    </nav>

    <!-- アクションバー -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">{{ $road->name }}</h2>
        <div style="display: flex; gap: 1rem;">
            @if($canManageGameData ?? true)
            <a href="{{ route('admin.roads.edit', $road->id) }}" class="admin-btn admin-btn-warning">
                <i class="fas fa-edit"></i> 編集
            </a>
            @endif
            @if($canManageGameData ?? true)
            <form method="POST" action="{{ route('admin.roads.destroy', $road->id) }}" 
                  style="display: inline;" 
                  onsubmit="return confirm('このRoadを削除してもよろしいですか？関連するデータも失われる可能性があります。')">
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn admin-btn-danger">
                    <i class="fas fa-trash"></i> 削除
                </button>
            </form>
            @endif
            <a href="{{ route('admin.roads.index') }}" class="admin-btn admin-btn-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        
        <!-- 基本情報 -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">基本情報</h3>
            </div>
            <div class="admin-card-body">
                <dl style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin: 0;">
                    <dt style="font-weight: 600; color: var(--admin-secondary);">ID</dt>
                    <dd style="margin: 0;">
                        <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                            {{ $road->id }}
                        </code>
                    </dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">名前</dt>
                    <dd style="margin: 0; font-weight: 600;">{{ $road->name }}</dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">説明</dt>
                    <dd style="margin: 0;">
                        @if($road->description)
                            {{ $road->description }}
                        @else
                            <span style="color: var(--admin-secondary); font-style: italic;">未設定</span>
                        @endif
                    </dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">カテゴリ</dt>
                    <dd style="margin: 0;">
                        <span class="admin-badge admin-badge-primary">{{ $road->category }}</span>
                    </dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">長さ</dt>
                    <dd style="margin: 0;">
                        <span class="admin-badge admin-badge-info">{{ $road->length }}</span>
                    </dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">難易度</dt>
                    <dd style="margin: 0;">
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
                    </dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">エンカウント率</dt>
                    <dd style="margin: 0;">
                        @if($road->encounter_rate)
                            {{ number_format($road->encounter_rate * 100, 1) }}%
                        @else
                            <span style="color: var(--admin-secondary); font-style: italic;">未設定</span>
                        @endif
                    </dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">ステータス</dt>
                    <dd style="margin: 0;">
                        @if($road->is_active)
                            <span class="admin-badge admin-badge-success">アクティブ</span>
                        @else
                            <span class="admin-badge admin-badge-secondary">非アクティブ</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        <!-- メタデータ -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">メタデータ</h3>
            </div>
            <div class="admin-card-body">
                <dl style="display: grid; gap: 1rem; margin: 0;">
                    <div>
                        <dt style="font-weight: 600; color: var(--admin-secondary); margin-bottom: 0.5rem;">作成日時</dt>
                        <dd style="margin: 0; font-size: 0.875rem;">
                            {{ $road->created_at->format('Y年m月d日 H:i:s') }}
                        </dd>
                    </div>

                    <div>
                        <dt style="font-weight: 600; color: var(--admin-secondary); margin-bottom: 0.5rem;">更新日時</dt>
                        <dd style="margin: 0; font-size: 0.875rem;">
                            {{ $road->updated_at->format('Y年m月d日 H:i:s') }}
                        </dd>
                    </div>

                    @if($road->spawn_list_id)
                    <div>
                        <dt style="font-weight: 600; color: var(--admin-secondary); margin-bottom: 0.5rem;">スポーンリストID</dt>
                        <dd style="margin: 0;">
                            <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                {{ $road->spawn_list_id }}
                            </code>
                        </dd>
                    </div>
                    @endif

                    @if($road->type)
                    <div>
                        <dt style="font-weight: 600; color: var(--admin-secondary); margin-bottom: 0.5rem;">タイプ</dt>
                        <dd style="margin: 0;">
                            {{ $road->type }}
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    @if($road->hasMonsterSpawns())
    <!-- モンスタースポーン情報 -->
    <div class="admin-card" style="margin-top: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-dragon"></i> モンスタースポーン
            </h3>
        </div>
        <div class="admin-card-body">
            <p style="margin-bottom: 1rem; color: var(--admin-secondary);">
                このRoadには {{ $road->monsterSpawns()->count() }} 件のモンスタースポーンが設定されています。
            </p>
            <a href="{{ route('admin.monster-spawns.show', $road->id) }}" class="admin-btn admin-btn-info">
                <i class="fas fa-list"></i> スポーン設定を確認
            </a>
        </div>
    </div>
    @endif

    @if($road->sourceConnections()->exists() || $road->targetConnections()->exists())
    <!-- 接続情報 -->
    <div class="admin-card" style="margin-top: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-route"></i> 接続情報
            </h3>
        </div>
        <div class="admin-card-body">
            @if($road->sourceConnections()->exists())
            <div style="margin-bottom: 1.5rem;">
                <h4 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">このRoadからの接続</h4>
                <div style="display: grid; gap: 0.5rem;">
                    @foreach($road->sourceConnections as $connection)
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: var(--admin-bg); border-radius: 0.5rem;">
                        <span class="admin-badge admin-badge-info">{{ $connection->connection_type }}</span>
                        <span>→</span>
                        <span style="font-weight: 600;">{{ $connection->target_location_id }}</span>
                        @if($connection->direction)
                        <span class="admin-badge admin-badge-secondary">{{ $connection->direction }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($road->targetConnections()->exists())
            <div>
                <h4 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">このRoadへの接続</h4>
                <div style="display: grid; gap: 0.5rem;">
                    @foreach($road->targetConnections as $connection)
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: var(--admin-bg); border-radius: 0.5rem;">
                        <span style="font-weight: 600;">{{ $connection->source_location_id }}</span>
                        <span>→</span>
                        <span class="admin-badge admin-badge-info">{{ $connection->connection_type }}</span>
                        @if($connection->direction)
                        <span class="admin-badge admin-badge-secondary">{{ $connection->direction }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection