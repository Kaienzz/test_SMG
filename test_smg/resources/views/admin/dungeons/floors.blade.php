@extends('admin.layouts.app')

@section('title', 'フロア管理')
@section('subtitle', $dungeon->dungeon_name . ' のフロア管理')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; list-style: none; margin: 0; padding: 0; font-size: 0.875rem;">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary); text-decoration: none;">ダッシュボード</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.dungeons.index') }}" style="color: var(--admin-primary); text-decoration: none;">Dungeon管理</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.dungeons.show', $dungeon->id) }}" style="color: var(--admin-primary); text-decoration: none;">{{ $dungeon->dungeon_name }}</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li style="color: var(--admin-secondary);">フロア管理</li>
        </ol>
    </nav>

    <!-- ダンジョン情報ヘッダー -->
    <div class="admin-card" style="margin-bottom: 2rem; background: linear-gradient(135deg, #f0f9ff, #e0f7fa);">
        <div class="admin-card-body">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600; color: var(--admin-primary);">
                        <i class="fas fa-dungeon"></i> {{ $dungeon->dungeon_name }}
                    </h2>
                    <p style="margin: 0.5rem 0 0 0; color: var(--admin-secondary);">
                        <code style="background: rgba(37, 99, 235, 0.1); padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                            {{ $dungeon->dungeon_id }}
                        </code>
                        @if($dungeon->dungeon_desc)
                        <span style="margin-left: 1rem;">{{ $dungeon->dungeon_desc }}</span>
                        @endif
                    </p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <a href="{{ route('admin.dungeons.show', $dungeon->id) }}" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i> ダンジョン詳細
                    </a>
                    @if(auth()->user()->can('locations.edit'))
                    <a href="{{ route('admin.dungeons.create-floor', $dungeon->id) }}" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i> 新規フロア作成
                    </a>
                    <button id="attach-floors-btn" class="admin-btn admin-btn-success">
                        <i class="fas fa-link"></i> 既存フロアをアタッチ
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- フロア統計 -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ $dungeon->floors->count() }}
                </div>
                <div style="color: var(--admin-secondary);">総フロア数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ $dungeon->floors->where('is_active', true)->count() }}
                </div>
                <div style="color: var(--admin-secondary);">アクティブフロア</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ number_format($dungeon->floors->avg('length') ?? 0) }}
                </div>
                <div style="color: var(--admin-secondary);">平均長さ</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ number_format(($dungeon->floors->avg('encounter_rate') ?? 0) * 100, 1) }}%
                </div>
                <div style="color: var(--admin-secondary);">平均エンカウント率</div>
            </div>
        </div>
    </div>

    <!-- フロア一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-layer-group"></i> フロア一覧
            </h3>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            @if($dungeon->floors->count() > 0)
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>フロアID</th>
                            <th>フロア名</th>
                            <th>長さ</th>
                            <th>難易度</th>
                            <th>エンカウント率</th>
                            <th>ステータス</th>
                            <th>モンスタースポーン</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dungeon->floors->sortBy('name') as $floor)
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
                                    {{ Str::limit($floor->description, 60) }}
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
                                @if($floor->hasMonsterSpawns())
                                <span class="admin-badge admin-badge-success admin-badge-sm">設定済み</span>
                                @else
                                <span class="admin-badge admin-badge-warning admin-badge-sm">未設定</span>
                                @endif
                                @if($floor->hasMonsterSpawns())
                                <a href="{{ route('admin.monster-spawns.show', $floor->id) }}" 
                                   class="admin-btn admin-btn-xs admin-btn-info" 
                                   style="margin-left: 0.5rem;"
                                   title="スポーン設定">
                                    <i class="fas fa-dragon"></i>
                                </a>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('admin.locations.show', $floor->id) }}" 
                                       class="admin-btn admin-btn-sm admin-btn-info" title="詳細">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(auth()->user()->can('locations.edit'))
                                    <!-- フロア編集は後で実装予定 -->
                                    <button class="admin-btn admin-btn-sm admin-btn-warning" 
                                            title="編集（準備中）" disabled>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif
                                    @if(auth()->user()->can('locations.delete'))
                                    <form method="POST" action="{{ route('admin.roads.destroy', $floor->id) }}" 
                                          style="display: inline;" 
                                          onsubmit="return confirm('このフロア「{{ $floor->name }}」を削除してもよろしいですか？')">
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
            @else
            <!-- フロアが存在しない場合 -->
            <div style="text-align: center; padding: 3rem;">
                <div style="color: var(--admin-secondary); margin-bottom: 1rem;">
                    <i class="fas fa-layer-group fa-3x"></i>
                </div>
                <h3 style="margin-bottom: 0.5rem;">フロアが作成されていません</h3>
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
            @endif
        </div>
    </div>

    @if($dungeon->floors->count() > 0)
    <!-- アタッチフォームモーダル -->
    <div id="attach-floors-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background: white; margin: 5% auto; padding: 0; width: 80%; max-width: 800px; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="modal-header" style="padding: 1.5rem; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: var(--admin-primary);">
                    <i class="fas fa-link"></i> フロアアタッチ - {{ $dungeon->dungeon_name }}
                </h3>
                <button id="close-attach-modal" style="background: none; border: none; font-size: 1.5rem; color: var(--admin-secondary); cursor: pointer;">&times;</button>
            </div>
            <div id="attach-floors-form-container" class="modal-body" style="padding: 1.5rem; max-height: 60vh; overflow-y: auto;">
                <!-- フォームは動的に読み込み -->
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--admin-primary);"></i>
                    <p style="margin-top: 1rem; color: var(--admin-secondary);">読み込み中...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 推奨次のアクション -->
    <div class="admin-card" style="margin-top: 2rem; background: linear-gradient(135deg, #f0fdf4, #dcfce7);">
        <div class="admin-card-header">
            <h3 class="admin-card-title" style="color: var(--admin-success);">
                <i class="fas fa-lightbulb"></i> 推奨次のアクション
            </h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                @if($dungeon->floors->filter(function($floor) { return !$floor->hasMonsterSpawns(); })->count() > 0)
                <div style="padding: 1rem; background: white; border-radius: 0.5rem; border: 1px solid var(--admin-border);">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--admin-warning);">
                        <i class="fas fa-dragon"></i> モンスタースポーン設定
                    </h4>
                    <p style="margin: 0 0 1rem 0; color: var(--admin-secondary); font-size: 0.875rem;">
                        {{ $dungeon->floors->filter(function($floor) { return !$floor->hasMonsterSpawns(); })->count() }} 個のフロアにモンスタースポーンが設定されていません。
                    </p>
                    <a href="{{ route('admin.monster-spawns.index') }}" class="admin-btn admin-btn-sm admin-btn-warning">
                        スポーン設定へ
                    </a>
                </div>
                @endif

                <div style="padding: 1rem; background: white; border-radius: 0.5rem; border: 1px solid var(--admin-border);">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--admin-info);">
                        <i class="fas fa-route"></i> 接続設定
                    </h4>
                    <p style="margin: 0 0 1rem 0; color: var(--admin-secondary); font-size: 0.875rem;">
                        フロア間の接続を設定してダンジョンの構造を完成させてください。
                    </p>
                    <a href="{{ route('admin.route-connections.index') }}" class="admin-btn admin-btn-sm admin-btn-info">
                        接続管理へ
                    </a>
                </div>

                <div style="padding: 1rem; background: white; border-radius: 0.5rem; border: 1px solid var(--admin-border);">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--admin-success);">
                        <i class="fas fa-play"></i> テストプレイ
                    </h4>
                    <p style="margin: 0 0 1rem 0; color: var(--admin-secondary); font-size: 0.875rem;">
                        ダンジョンの動作確認を行い、バランス調整を実施してください。
                    </p>
                    <button class="admin-btn admin-btn-sm admin-btn-success" disabled>
                        テスト機能（準備中）
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<style>
.admin-badge-sm {
    font-size: 0.7rem;
    padding: 0.125rem 0.375rem;
}

.admin-btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.modal {
    animation: fadeIn 0.2s ease-out;
}

.modal-content {
    animation: slideInDown 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInDown {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const attachBtn = document.getElementById('attach-floors-btn');
    const attachModal = document.getElementById('attach-floors-modal');
    const closeBtn = document.getElementById('close-attach-modal');
    const formContainer = document.getElementById('attach-floors-form-container');
    
    // アタッチボタンクリック
    attachBtn?.addEventListener('click', function() {
        attachModal.style.display = 'block';
        loadAttachForm();
    });
    
    // モーダルを閉じる
    closeBtn?.addEventListener('click', function() {
        attachModal.style.display = 'none';
    });
    
    // 背景クリックで閉じる
    attachModal?.addEventListener('click', function(e) {
        if (e.target === attachModal) {
            attachModal.style.display = 'none';
        }
    });
    
    // アタッチフォーム読み込み
    function loadAttachForm(searchQuery = '', onlyOrphans = true) {
        const params = new URLSearchParams({
            search: searchQuery,
            only_orphans: onlyOrphans ? '1' : '0'
        });
        
        fetch(`{{ route('admin.dungeons.attach-floors-form', $dungeon->id) }}?${params}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                formContainer.innerHTML = data.html;
                bindFormEvents();
            } else {
                formContainer.innerHTML = `
                    <div class="admin-alert admin-alert-danger">
                        ${data.error}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            formContainer.innerHTML = `
                <div class="admin-alert admin-alert-danger">
                    アタッチフォームの読み込みに失敗しました。
                </div>
            `;
        });
    }
    
    // フォームイベントをバインド
    function bindFormEvents() {
        // 検索フォーム
        const searchForm = document.getElementById('attach-search-form');
        searchForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(searchForm);
            loadAttachForm(
                formData.get('search') || '',
                formData.get('only_orphans') === '1'
            );
        });
        
        // アタッチ実行フォーム
        const attachForm = document.getElementById('attach-floors-form');
        attachForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(attachForm);
            
            fetch(`{{ route('admin.dungeons.attach-floors', $dungeon->id) }}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 成功時はページをリロード
                    window.location.reload();
                } else {
                    alert('エラー: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('アタッチ処理に失敗しました。');
            });
        });
    }
    
    // グローバル関数として公開
    window.loadAttachForm = loadAttachForm;
});
</script>
@endsection