@extends('admin.layouts.app')

@section('title', 'モンスタースポーン詳細')
@section('subtitle', $location->name . ' のスポーン設定')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary);">ダッシュボード</a></li>
            <li>/</li>
            <li><a href="{{ route('admin.monster-spawns.index') }}" style="color: var(--admin-primary);">モンスタースポーン管理</a></li>
            <li>/</li>
            <li>{{ $location->name }}</li>
        </ol>
    </nav>

    <!-- Location基本情報 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Location情報</h3>
            <div style="display: flex; gap: 0.5rem;">
                @if(auth()->user()->can('monsters.create'))
                <a href="{{ route('admin.monster-spawns.create', $location->id) }}" class="admin-btn admin-btn-success">
                    ➕ スポーン追加
                </a>
                @endif
                <a href="{{ route('admin.monster-spawns.index') }}" class="admin-btn admin-btn-secondary">
                    ← 一覧に戻る
                </a>
            </div>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--admin-primary);">基本情報</h4>
                    <dl style="margin: 0;">
                        <dt style="font-weight: 500; margin-bottom: 0.25rem;">Location ID</dt>
                        <dd style="margin-bottom: 1rem; color: var(--admin-secondary);">{{ $location->id }}</dd>
                        
                        <dt style="font-weight: 500; margin-bottom: 0.25rem;">名前</dt>
                        <dd style="margin-bottom: 1rem;">{{ $location->name }}</dd>
                        
                        <dt style="font-weight: 500; margin-bottom: 0.25rem;">カテゴリー</dt>
                        <dd style="margin-bottom: 1rem;">
                            <span class="admin-badge admin-badge-{{ $location->category === 'road' ? 'primary' : 'info' }}">
                                {{ $location->category === 'road' ? '道路' : ($location->category === 'dungeon' ? 'ダンジョン' : $location->category) }}
                            </span>
                        </dd>
                    </dl>
                </div>

                @if($location->spawn_description || ($location->spawn_tags && count($location->spawn_tags) > 0))
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--admin-primary);">スポーン情報</h4>
                    @if($location->spawn_description)
                    <dl style="margin: 0 0 1rem 0;">
                        <dt style="font-weight: 500; margin-bottom: 0.25rem;">説明</dt>
                        <dd style="margin-bottom: 1rem; color: var(--admin-secondary);">{{ $location->spawn_description }}</dd>
                    </dl>
                    @endif
                    
                    @if($location->spawn_tags && count($location->spawn_tags) > 0)
                    <dl style="margin: 0;">
                        <dt style="font-weight: 500; margin-bottom: 0.25rem;">タグ</dt>
                        <dd style="margin: 0;">
                            @foreach($location->spawn_tags as $tag)
                            <span class="admin-badge admin-badge-secondary" style="margin-right: 0.5rem;">{{ $tag }}</span>
                            @endforeach
                        </dd>
                    </dl>
                    @endif
                </div>
                @endif

                @if(isset($spawnStats))
                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--admin-primary);">統計</h4>
                    <dl style="margin: 0;">
                        <dt style="font-weight: 500; margin-bottom: 0.25rem;">スポーン数</dt>
                        <dd style="margin-bottom: 1rem; font-size: 1.25rem; font-weight: bold; color: var(--admin-success);">
                            {{ $spawnStats['total_spawns'] ?? $location->monsterSpawns->count() }}件
                        </dd>
                        
                        <dt style="font-weight: 500; margin-bottom: 0.25rem;">総出現率</dt>
                        <dd style="margin-bottom: 1rem;">
                            @php
                                $totalRate = $spawnStats['total_rate'] ?? $location->monsterSpawns->sum('spawn_rate');
                                $isComplete = $totalRate >= 0.99;
                                $badgeClass = $isComplete ? 'success' : ($totalRate > 0.7 ? 'warning' : 'danger');
                            @endphp
                            <span class="admin-badge admin-badge-{{ $badgeClass }}" style="font-size: 1rem;">
                                {{ round($totalRate * 100, 1) }}%
                            </span>
                        </dd>
                        
                        <dt style="font-weight: 500; margin-bottom: 0.25rem;">有効スポーン</dt>
                        <dd style="margin: 0; color: var(--admin-info);">
                            {{ $location->monsterSpawns->where('is_active', true)->count() }}件
                        </dd>
                    </dl>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 検証結果 -->
    @if(isset($validationIssues) && count($validationIssues) > 0)
    <div class="admin-card" style="margin-bottom: 2rem; border-left: 4px solid var(--admin-warning);">
        <div class="admin-card-header">
            <h3 class="admin-card-title" style="color: var(--admin-warning);">⚠️ 設定に関する注意</h3>
        </div>
        <div class="admin-card-body">
            <ul style="margin: 0; padding-left: 1.5rem;">
                @foreach($validationIssues as $issue)
                <li style="margin-bottom: 0.5rem; color: var(--admin-warning);">{{ $issue }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- モンスタースポーン一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                モンスタースポーン設定 ({{ $location->monsterSpawns->count() }}件)
            </h3>
            @if(auth()->user()->can('monsters.edit') && $location->monsterSpawns->count() > 1)
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" onclick="showBulkActionModal()" class="admin-btn admin-btn-info" style="font-size: 0.875rem;">
                    📝 一括操作
                </button>
            </div>
            @endif
        </div>
        <div class="admin-card-body" style="padding: 0;">
            @if($location->monsterSpawns->count() > 0)
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            @if(auth()->user()->can('monsters.edit'))
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                            </th>
                            @endif
                            <th>モンスター</th>
                            <th>出現率</th>
                            <th>優先度</th>
                            <th>レベル制限</th>
                            <th>ステータス</th>
                            <th style="width: 150px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($location->monsterSpawns->sortBy('priority') as $spawn)
                        <tr>
                            @if(auth()->user()->can('monsters.edit'))
                            <td>
                                <input type="checkbox" name="spawn_ids[]" value="{{ $spawn->id }}" class="spawn-checkbox">
                            </td>
                            @endif
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="font-size: 2rem;">{{ $spawn->monster->emoji ?? '👹' }}</div>
                                    <div>
                                        <div style="font-weight: 500;">{{ $spawn->monster->name }}</div>
                                        <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                                            Lv.{{ $spawn->monster->level }} | 
                                            HP: {{ number_format($spawn->monster->max_hp) }} | 
                                            EXP: {{ number_format($spawn->monster->experience_reward) }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="text-align: center;">
                                    <div style="font-weight: bold; font-size: 1.1rem;">
                                        {{ round($spawn->spawn_rate * 100, 1) }}%
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--admin-secondary);">
                                        ({{ number_format($spawn->spawn_rate, 3) }})
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="admin-badge admin-badge-secondary">
                                    {{ $spawn->priority }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                @if($spawn->min_level || $spawn->max_level)
                                    <div style="font-size: 0.875rem;">
                                        @if($spawn->min_level)
                                            Lv.{{ $spawn->min_level }}以上
                                        @endif
                                        @if($spawn->min_level && $spawn->max_level)
                                            <br>
                                        @endif
                                        @if($spawn->max_level)
                                            Lv.{{ $spawn->max_level }}以下
                                        @endif
                                    </div>
                                @else
                                    <span style="color: var(--admin-secondary); font-size: 0.875rem;">制限なし</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <span class="admin-badge admin-badge-{{ $spawn->is_active ? 'success' : 'secondary' }}">
                                    {{ $spawn->is_active ? '有効' : '無効' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    @if(auth()->user()->can('monsters.edit'))
                                    <a href="{{ route('admin.monster-spawns.edit', $spawn->id) }}" 
                                       class="admin-btn admin-btn-primary" 
                                       style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        編集
                                    </a>
                                    @endif
                                    @if(auth()->user()->can('monsters.delete'))
                                    <form method="POST" action="{{ route('admin.monster-spawns.destroy', $spawn->id) }}" 
                                          style="display: inline;" 
                                          onsubmit="return confirm('このスポーン設定を削除しますか？')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn admin-btn-danger" 
                                                style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            削除
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
            <div style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                <h4 style="margin-bottom: 1rem;">スポーン設定がありません</h4>
                <p style="margin-bottom: 2rem;">このLocationにはまだモンスタースポーンが設定されていません。</p>
                @if(auth()->user()->can('monsters.create'))
                <a href="{{ route('admin.monster-spawns.create', $location->id) }}" class="admin-btn admin-btn-success">
                    ➕ 最初のスポーンを追加
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

@if(auth()->user()->can('monsters.edit') && $location->monsterSpawns->count() > 1)
<!-- 一括操作モーダル -->
<div id="bulk-action-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 2rem; width: 90%; max-width: 500px;">
        <h3 style="margin-bottom: 1.5rem;">一括操作</h3>
        <form id="bulk-action-form" method="POST" action="{{ route('admin.monster-spawns.bulk-action') }}">
            @csrf
            <input type="hidden" name="spawn_ids" id="selected-spawn-ids">
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">操作を選択</label>
                <select name="action" class="admin-select" required>
                    <option value="">操作を選択してください</option>
                    <option value="activate">すべて有効にする</option>
                    <option value="deactivate">すべて無効にする</option>
                    @if(auth()->user()->can('monsters.delete'))
                    <option value="delete">すべて削除する</option>
                    @endif
                </select>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <button type="button" onclick="hideBulkActionModal()" class="admin-btn admin-btn-secondary">
                    キャンセル
                </button>
                <button type="submit" class="admin-btn admin-btn-warning">
                    実行
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
@if(auth()->user()->can('monsters.edit'))
// チェックボックス全選択/全解除
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.spawn-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

// 一括操作モーダル
function showBulkActionModal() {
    const selectedIds = Array.from(document.querySelectorAll('.spawn-checkbox:checked')).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('操作対象を選択してください。');
        return;
    }
    
    document.getElementById('selected-spawn-ids').value = selectedIds.join(',');
    document.getElementById('bulk-action-modal').style.display = 'block';
}

function hideBulkActionModal() {
    document.getElementById('bulk-action-modal').style.display = 'none';
}

// 一括操作フォーム送信確認
document.getElementById('bulk-action-form').addEventListener('submit', function(e) {
    const action = this.querySelector('select[name="action"]').value;
    const selectedIds = document.getElementById('selected-spawn-ids').value.split(',');
    
    let message = `選択した${selectedIds.length}件のスポーン設定を`;
    
    switch(action) {
        case 'activate':
            message += '有効にしますか？';
            break;
        case 'deactivate':
            message += '無効にしますか？';
            break;
        case 'delete':
            message += '削除しますか？\n※この操作は元に戻せません。';
            break;
        default:
            message = '操作を選択してください。';
            e.preventDefault();
            return;
    }
    
    if (!confirm(message)) {
        e.preventDefault();
    }
});

// モーダル外クリックで閉じる
document.getElementById('bulk-action-modal').addEventListener('click', function(e) {
    if (e.target === this) hideBulkActionModal();
});
@endif
</script>

<style>
/* 管理画面固有のスタイル */
.admin-table th {
    background-color: #f9fafb;
    font-weight: 500;
    color: var(--admin-secondary);
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--admin-border);
}

.admin-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
}

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

dl dt {
    margin: 0;
}

dl dd {
    margin: 0;
}
</style>
@endsection