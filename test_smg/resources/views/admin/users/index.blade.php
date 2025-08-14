@extends('admin.layouts.app')

@section('title', 'ユーザー管理')
@section('subtitle', 'ユーザーアカウントの管理と監視')

@section('content')
<div class="admin-content-container">
    <!-- 統計カード -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ number_format($stats['total']) }}
                </div>
                <div style="color: var(--admin-secondary);">総ユーザー数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ number_format($stats['active_today']) }}
                </div>
                <div style="color: var(--admin-secondary);">今日のアクティブ</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ number_format($stats['registered_today']) }}
                </div>
                <div style="color: var(--admin-secondary);">今日の新規登録</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ number_format($stats['online_now']) }}
                </div>
                <div style="color: var(--admin-secondary);">現在オンライン</div>
            </div>
        </div>
    </div>

    <!-- フィルター・検索 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">検索・フィルター</h3>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="filter-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <!-- 検索 -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">検索</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                               placeholder="名前・メールアドレス" class="admin-input">
                    </div>

                    <!-- ステータス -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">ステータス</label>
                        <select name="status" class="admin-select">
                            <option value="">すべて</option>
                            <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>アクティブ</option>
                            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>非アクティブ</option>
                            <option value="verified" {{ ($filters['status'] ?? '') === 'verified' ? 'selected' : '' }}>認証済み</option>
                            <option value="unverified" {{ ($filters['status'] ?? '') === 'unverified' ? 'selected' : '' }}>未認証</option>
                        </select>
                    </div>

                    <!-- 管理者フィルター -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">権限</label>
                        <select name="admin_filter" class="admin-select">
                            <option value="">すべて</option>
                            <option value="admin_only" {{ ($filters['admin_filter'] ?? '') === 'admin_only' ? 'selected' : '' }}>管理者のみ</option>
                            <option value="regular_only" {{ ($filters['admin_filter'] ?? '') === 'regular_only' ? 'selected' : '' }}>一般ユーザーのみ</option>
                        </select>
                    </div>

                    <!-- 登録期間 -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">登録期間</label>
                        <select name="registration_period" class="admin-select">
                            <option value="">すべて</option>
                            <option value="24h" {{ ($filters['registration_period'] ?? '') === '24h' ? 'selected' : '' }}>過去24時間</option>
                            <option value="7d" {{ ($filters['registration_period'] ?? '') === '7d' ? 'selected' : '' }}>過去7日</option>
                            <option value="30d" {{ ($filters['registration_period'] ?? '') === '30d' ? 'selected' : '' }}>過去30日</option>
                            <option value="90d" {{ ($filters['registration_period'] ?? '') === '90d' ? 'selected' : '' }}>過去90日</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        🔍 検索
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-secondary">
                        🔄 リセット
                    </a>
                    <a href="{{ route('admin.users.online') }}" class="admin-btn admin-btn-success">
                        🟢 オンラインユーザー
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ユーザー一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">ユーザー一覧 ({{ $users->total() }}件)</h3>
            <div style="display: flex; gap: 0.5rem;">
                <!-- ソート -->
                <select onchange="updateSort(this.value)" class="admin-select" style="width: auto;">
                    <option value="created_at-desc" {{ $sortBy === 'created_at' && $sortDirection === 'desc' ? 'selected' : '' }}>登録日降順</option>
                    <option value="created_at-asc" {{ $sortBy === 'created_at' && $sortDirection === 'asc' ? 'selected' : '' }}>登録日昇順</option>
                    <option value="last_active_at-desc" {{ $sortBy === 'last_active_at' && $sortDirection === 'desc' ? 'selected' : '' }}>最終アクティブ降順</option>
                    <option value="name-asc" {{ $sortBy === 'name' && $sortDirection === 'asc' ? 'selected' : '' }}>名前昇順</option>
                    <option value="email-asc" {{ $sortBy === 'email' && $sortDirection === 'asc' ? 'selected' : '' }}>メール昇順</option>
                </select>

                <!-- 一括操作 -->
                <button type="button" onclick="toggleBulkActions()" class="admin-btn admin-btn-secondary" id="bulk-toggle">
                    ☑️ 一括操作
                </button>
            </div>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <!-- 一括操作パネル -->
            <div id="bulk-actions" style="display: none; padding: 1rem; background: #f9fafb; border-bottom: 1px solid var(--admin-border);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span id="selected-count">0件選択</span>
                    <button type="button" onclick="selectAll()" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.75rem;">全選択</button>
                    <button type="button" onclick="deselectAll()" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.75rem;">選択解除</button>
                    <button type="button" onclick="performBulkAction('force_logout')" class="admin-btn admin-btn-warning" style="padding: 0.25rem 0.75rem;">強制ログアウト</button>
                    <button type="button" onclick="performBulkAction('suspend')" class="admin-btn admin-btn-danger" style="padding: 0.25rem 0.75rem;">アカウント停止</button>
                </div>
            </div>

            <!-- テーブル -->
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all-checkbox" style="display: none;">
                            </th>
                            <th>ユーザー情報</th>
                            <th>ステータス</th>
                            <th>プレイヤー情報</th>
                            <th>登録日</th>
                            <th>最終アクティブ</th>
                            <th style="width: 150px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>
                                <input type="checkbox" class="user-checkbox" value="{{ $user->id }}" style="display: none;">
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--admin-primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;">
                                            {{ $user->name }}
                                            @if($user->is_admin)
                                                <span class="admin-badge admin-badge-danger" style="margin-left: 0.5rem;">管理者</span>
                                            @endif
                                        </div>
                                        <div style="font-size: 0.875rem; color: var(--admin-secondary);">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($user->email_verified_at)
                                    <span class="admin-badge admin-badge-success">認証済み</span>
                                @else
                                    <span class="admin-badge admin-badge-warning">未認証</span>
                                @endif

                                @if($user->last_active_at && $user->last_active_at >= now()->subMinutes(15))
                                    <span class="admin-badge admin-badge-info" style="margin-left: 0.5rem;">オンライン</span>
                                @elseif($user->last_active_at && $user->last_active_at >= now()->subDays(7))
                                    <span class="admin-badge admin-badge-success" style="margin-left: 0.5rem;">アクティブ</span>
                                @else
                                    <span class="admin-badge admin-badge-secondary" style="margin-left: 0.5rem;">非アクティブ</span>
                                @endif
                            </td>
                            <td>
                                @if($user->player)
                                    <div style="font-size: 0.875rem;">
                                        <div>Lv.{{ $user->player->level }}</div>
                                        <div style="color: var(--admin-secondary);">{{ number_format($user->player->gold) }}G</div>
                                    </div>
                                @else
                                    <span style="color: var(--admin-secondary);">未作成</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    {{ $user->created_at->format('Y/m/d') }}
                                    <div style="color: var(--admin-secondary);">{{ $user->created_at->format('H:i') }}</div>
                                </div>
                            </td>
                            <td>
                                @if($user->last_active_at)
                                    <div style="font-size: 0.875rem;">
                                        {{ $user->last_active_at->format('Y/m/d') }}
                                        <div style="color: var(--admin-secondary);">{{ $user->last_active_at->diffForHumans() }}</div>
                                    </div>
                                @else
                                    <span style="color: var(--admin-secondary);">未ログイン</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('admin.users.show', $user) }}" class="admin-btn admin-btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        詳細
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        編集
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
                                条件に一致するユーザーが見つかりませんでした
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ページネーション -->
    @if($users->hasPages())
    <div style="margin-top: 2rem;">
        {{ $users->links() }}
    </div>
    @endif
</div>

<script>
// ソート変更
function updateSort(value) {
    const [sortBy, sortDirection] = value.split('-');
    const url = new URL(window.location);
    url.searchParams.set('sort_by', sortBy);
    url.searchParams.set('sort_direction', sortDirection);
    window.location.href = url.toString();
}

// 一括操作の表示切り替え
function toggleBulkActions() {
    const panel = document.getElementById('bulk-actions');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        checkboxes.forEach(cb => cb.style.display = 'block');
        selectAllCheckbox.style.display = 'block';
        document.getElementById('bulk-toggle').textContent = '❌ 一括操作';
    } else {
        panel.style.display = 'none';
        checkboxes.forEach(cb => {
            cb.style.display = 'none';
            cb.checked = false;
        });
        selectAllCheckbox.style.display = 'none';
        selectAllCheckbox.checked = false;
        document.getElementById('bulk-toggle').textContent = '☑️ 一括操作';
        updateSelectedCount();
    }
}

// 全選択
function selectAll() {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('select-all-checkbox').checked = true;
    updateSelectedCount();
}

// 選択解除
function deselectAll() {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('select-all-checkbox').checked = false;
    updateSelectedCount();
}

// 選択数の更新
function updateSelectedCount() {
    const count = document.querySelectorAll('.user-checkbox:checked').length;
    document.getElementById('selected-count').textContent = `${count}件選択`;
}

// 一括操作の実行
function performBulkAction(action) {
    const selectedIds = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('操作対象のユーザーを選択してください。');
        return;
    }
    
    let confirmMessage = '';
    switch (action) {
        case 'force_logout':
            confirmMessage = `選択した${selectedIds.length}件のユーザーを強制ログアウトしますか？`;
            break;
        case 'suspend':
            const reason = prompt('停止理由を入力してください：');
            if (!reason) return;
            confirmMessage = `選択した${selectedIds.length}件のユーザーを停止しますか？\n理由: ${reason}`;
            break;
    }
    
    if (!confirm(confirmMessage)) return;
    
    // フォーム作成・送信
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.users.bulk_action") }}';
    
    // CSRFトークン
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    // アクション
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);
    
    // 理由（停止の場合）
    if (action === 'suspend') {
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'reason';
        reasonInput.value = prompt('停止理由を入力してください：');
        form.appendChild(reasonInput);
    }
    
    // ユーザーID
    selectedIds.forEach(id => {
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'user_ids[]';
        idInput.value = id;
        form.appendChild(idInput);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// チェックボックス変更の監視
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('user-checkbox') || e.target.id === 'select-all-checkbox') {
        updateSelectedCount();
        
        // 全選択チェックボックスの状態更新
        if (e.target.id === 'select-all-checkbox') {
            document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = e.target.checked);
        } else {
            const allCheckboxes = document.querySelectorAll('.user-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
            document.getElementById('select-all-checkbox').checked = allCheckboxes.length === checkedCheckboxes.length;
        }
        
        updateSelectedCount();
    }
});
</script>
@endsection