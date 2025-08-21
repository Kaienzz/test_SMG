@extends('admin.layouts.app')

@section('title', 'ダンジョン詳細')
@section('subtitle', $dungeon->dungeon_name . ' の詳細情報')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; list-style: none; margin: 0; padding: 0; font-size: 0.875rem;">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary); text-decoration: none;">ダッシュボード</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.dungeons.index') }}" style="color: var(--admin-primary); text-decoration: none;">Dungeon管理</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li style="color: var(--admin-secondary);">{{ $dungeon->dungeon_name }}</li>
        </ol>
    </nav>

    <!-- アクションバー -->
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">{{ $dungeon->dungeon_name }}</h2>
        <div style="display: flex; gap: 1rem;">
            @if(auth()->user()->can('locations.edit'))
            <a href="{{ route('admin.dungeons.edit', $dungeon->id) }}" class="admin-btn admin-btn-warning">
                <i class="fas fa-edit"></i> 編集
            </a>
            <a href="{{ route('admin.dungeons.floors', $dungeon->id) }}" class="admin-btn admin-btn-info">
                <i class="fas fa-layer-group"></i> フロア管理
            </a>
            <a href="{{ route('admin.dungeons.create-floor', $dungeon->id) }}" class="admin-btn admin-btn-success">
                <i class="fas fa-plus"></i> フロア追加
            </a>
            @endif
            @if(auth()->user()->can('locations.delete'))
            <form method="POST" action="{{ route('admin.dungeons.destroy', $dungeon->id) }}" 
                  style="display: inline;" 
                  onsubmit="return confirm('このダンジョン「{{ $dungeon->dungeon_name }}」を削除してもよろしいですか？\n関連するフロア情報（{{ $dungeon->floors->count() }}個）も削除されます。')">
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn admin-btn-danger">
                    <i class="fas fa-trash"></i> 削除
                </button>
            </form>
            @endif
            <a href="{{ route('admin.dungeons.index') }}" class="admin-btn admin-btn-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        
        <!-- 基本情報 -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="fas fa-dungeon"></i> ダンジョン基本情報
                </h3>
            </div>
            <div class="admin-card-body">
                <dl style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin: 0;">
                    <dt style="font-weight: 600; color: var(--admin-secondary);">ダンジョンID</dt>
                    <dd style="margin: 0;">
                        <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                            {{ $dungeon->dungeon_id }}
                        </code>
                    </dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">ダンジョン名</dt>
                    <dd style="margin: 0; font-weight: 600;">{{ $dungeon->dungeon_name }}</dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">説明</dt>
                    <dd style="margin: 0;">
                        @if($dungeon->dungeon_desc)
                            {{ $dungeon->dungeon_desc }}
                        @else
                            <span style="color: var(--admin-secondary); font-style: italic;">未設定</span>
                        @endif
                    </dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">フロア数</dt>
                    <dd style="margin: 0;">
                        <span class="admin-badge admin-badge-info">{{ $dungeon->floors->count() }} フロア</span>
                    </dd>

                    <dt style="font-weight: 600; color: var(--admin-secondary);">ステータス</dt>
                    <dd style="margin: 0;">
                        @if($dungeon->is_active)
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
                            {{ $dungeon->created_at->format('Y年m月d日 H:i:s') }}
                        </dd>
                    </div>

                    <div>
                        <dt style="font-weight: 600; color: var(--admin-secondary); margin-bottom: 0.5rem;">更新日時</dt>
                        <dd style="margin: 0; font-size: 0.875rem;">
                            {{ $dungeon->updated_at->format('Y年m月d日 H:i:s') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    @if($dungeon->floors->count() > 0)
    <!-- フロア一覧 -->
    <div class="admin-card" style="margin-top: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-layer-group"></i> フロア一覧 ({{ $dungeon->floors->count() }}個)
            </h3>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>フロアID</th>
                            <th>名前</th>
                            <th>長さ</th>
                            <th>難易度</th>
                            <th>エンカウント率</th>
                            <th>ステータス</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dungeon->floors as $floor)
                        <tr>
                            <td>
                                <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                    {{ $floor->id }}
                                </code>
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $floor->name }}</div>
                                @if($floor->description)
                                <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                    {{ Str::limit($floor->description, 50) }}
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-info">{{ $floor->length }}</span>
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
                                <span class="admin-badge admin-badge-{{ $difficultyColors[$floor->difficulty] ?? 'secondary' }}">
                                    {{ $difficultyLabels[$floor->difficulty] ?? $floor->difficulty }}
                                </span>
                            </td>
                            <td>
                                @if($floor->encounter_rate)
                                {{ number_format($floor->encounter_rate * 100, 1) }}%
                                @else
                                <span style="color: var(--admin-secondary);">未設定</span>
                                @endif
                            </td>
                            <td>
                                @if($floor->is_active)
                                <span class="admin-badge admin-badge-success">アクティブ</span>
                                @else
                                <span class="admin-badge admin-badge-secondary">非アクティブ</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <!-- フロアの詳細は既存のLocationControllerを使用 -->
                                    <a href="{{ route('admin.locations.show', $floor->id) }}" 
                                       class="admin-btn admin-btn-sm admin-btn-info" title="詳細">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(auth()->user()->can('locations.edit'))
                                    <!-- フロア編集は後で実装 -->
                                    <button class="admin-btn admin-btn-sm admin-btn-warning" 
                                            title="編集（準備中）" disabled>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <!-- フロアが存在しない場合 -->
    <div class="admin-card" style="margin-top: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-layer-group"></i> フロア情報
            </h3>
        </div>
        <div class="admin-card-body" style="text-align: center; padding: 3rem;">
            <div style="color: var(--admin-secondary); margin-bottom: 1rem;">
                <i class="fas fa-layer-group fa-3x"></i>
            </div>
            <h4 style="margin-bottom: 0.5rem;">フロアが作成されていません</h4>
            <p style="color: var(--admin-secondary); margin-bottom: 2rem;">
                このダンジョンにはまだフロアが作成されていません。<br>
                フロアを追加してダンジョンを構築してください。
            </p>
            @if(auth()->user()->can('locations.edit'))
            <a href="{{ route('admin.dungeons.create-floor', $dungeon->id) }}" class="admin-btn admin-btn-primary">
                <i class="fas fa-plus"></i> 最初のフロアを作成
            </a>
            @endif
        </div>
    </div>
    @endif

    @if($dungeon->floors->count() > 0)
    <!-- フロア統計情報 -->
    <div class="admin-card" style="margin-top: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-chart-bar"></i> フロア統計
            </h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                <div style="text-align: center; padding: 1rem; background: var(--admin-bg); border-radius: 0.5rem;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-info);">
                        {{ number_format($dungeon->floors->avg('length')) }}
                    </div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">平均長さ</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: var(--admin-bg); border-radius: 0.5rem;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-success);">
                        {{ $dungeon->floors->where('is_active', true)->count() }}
                    </div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">アクティブフロア</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: var(--admin-bg); border-radius: 0.5rem;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-warning);">
                        {{ number_format($dungeon->floors->avg('encounter_rate') * 100, 1) }}%
                    </div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">平均エンカウント率</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: var(--admin-bg); border-radius: 0.5rem;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-danger);">
                        {{ $dungeon->floors->where('difficulty', 'hard')->count() }}
                    </div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">高難易度フロア</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection