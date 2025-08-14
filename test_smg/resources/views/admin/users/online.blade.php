@extends('admin.layouts.app')

@section('title', 'オンラインユーザー')
@section('subtitle', '現在オンライン中のユーザー一覧')

@section('content')
<div class="admin-content-container">
    
    <!-- 統計情報 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; text-align: center;">
                <div>
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                        {{ $onlineUsers->total() }}
                    </div>
                    <div style="color: var(--admin-secondary);">オンラインユーザー</div>
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                        {{ $onlineUsers->where('is_admin', true)->count() }}
                    </div>
                    <div style="color: var(--admin-secondary);">オンライン管理者</div>
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                        {{ $onlineUsers->whereNotNull('player')->count() }}
                    </div>
                    <div style="color: var(--admin-secondary);">プレイヤー参加中</div>
                </div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-secondary); margin-bottom: 0.5rem;">
                        15分以内
                    </div>
                    <div style="color: var(--admin-secondary);">アクティブ基準</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 更新情報 -->
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
        <div style="color: var(--admin-secondary); font-size: 0.875rem;">
            最終更新: <span id="last-updated">{{ now()->format('Y/m/d H:i:s') }}</span>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" onclick="refreshData()" class="admin-btn admin-btn-secondary" style="padding: 0.5rem 1rem;">
                🔄 更新
            </button>
            <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-primary" style="padding: 0.5rem 1rem;">
                👥 全ユーザー
            </a>
        </div>
    </div>

    <!-- オンラインユーザー一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">オンラインユーザー一覧</h3>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ユーザー</th>
                            <th>ステータス</th>
                            <th>プレイヤー情報</th>
                            <th>アクティブ時間</th>
                            <th>セッション情報</th>
                            <th style="width: 120px;">操作</th>
                        </tr>
                    </thead>
                    <tbody id="online-users-table">
                        @forelse($onlineUsers as $user)
                        <tr data-user-id="{{ $user->id }}">
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <!-- オンライン状態インジケータ -->
                                    <div style="width: 12px; height: 12px; border-radius: 50%; background: {{ $user->last_active_at >= now()->subMinutes(5) ? '#10b981' : '#f59e0b' }}; position: relative;">
                                        @if($user->last_active_at >= now()->subMinutes(5))
                                        <div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; position: absolute; animation: pulse 2s infinite; opacity: 0.75;"></div>
                                        @endif
                                    </div>
                                    
                                    <!-- ユーザー情報 -->
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
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    @if($user->last_active_at >= now()->subMinutes(5))
                                        <span class="admin-badge admin-badge-success">アクティブ</span>
                                    @elseif($user->last_active_at >= now()->subMinutes(15))
                                        <span class="admin-badge admin-badge-warning">準アクティブ</span>
                                    @else
                                        <span class="admin-badge admin-badge-secondary">アイドル</span>
                                    @endif
                                    
                                    @if($user->email_verified_at)
                                        <span class="admin-badge admin-badge-info" style="font-size: 0.75rem;">認証済み</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($user->player)
                                    <div style="font-size: 0.875rem;">
                                        <div><strong>Lv.{{ $user->player->level }}</strong></div>
                                        <div style="color: var(--admin-secondary);">{{ number_format($user->player->gold) }}G</div>
                                        <div style="color: var(--admin-secondary); font-size: 0.75rem;">
                                            {{ $user->player->location_type ?? 'unknown' }}
                                        </div>
                                    </div>
                                @else
                                    <span style="color: var(--admin-secondary); font-size: 0.875rem;">プレイヤー未作成</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    <div><strong>{{ $user->last_active_at->format('H:i:s') }}</strong></div>
                                    <div style="color: var(--admin-secondary);">{{ $user->last_active_at->diffForHumans() }}</div>
                                    @if($user->created_at >= now()->subHours(24))
                                        <div style="color: #10b981; font-size: 0.75rem;">新規ユーザー</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    <div style="color: var(--admin-secondary);">
                                        IP: {{ $user->last_ip_address ?? '不明' }}
                                    </div>
                                    <div style="color: var(--admin-secondary);">
                                        {{ $user->last_device_type ?? 'unknown' }}
                                    </div>
                                    @if($user->last_active_at >= now()->subMinutes(1))
                                        <div style="color: #10b981; font-size: 0.75rem;">⚡ 高活動</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="admin-btn admin-btn-primary" 
                                       style="padding: 0.25rem 0.5rem; font-size: 0.75rem; text-align: center;">
                                        詳細
                                    </a>
                                    @if(auth()->user()->can('users.edit'))
                                    <button type="button" 
                                            onclick="forceLogout({{ $user->id }})" 
                                            class="admin-btn admin-btn-warning" 
                                            style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        ログアウト
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
                                現在オンラインのユーザーはいません
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ページネーション -->
    @if($onlineUsers->hasPages())
    <div style="margin-top: 2rem;">
        {{ $onlineUsers->links() }}
    </div>
    @endif
</div>

<script>
// 自動更新の設定
let autoRefreshInterval;
let isAutoRefreshEnabled = true;

// ページ読み込み時に自動更新開始
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
});

// 自動更新開始
function startAutoRefresh() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    
    autoRefreshInterval = setInterval(function() {
        if (isAutoRefreshEnabled) {
            refreshData();
        }
    }, 30000); // 30秒ごと
}

// データ更新
function refreshData() {
    fetch(window.location.href, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        // テーブル部分のみ更新
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTable = doc.querySelector('#online-users-table');
        
        if (newTable) {
            document.getElementById('online-users-table').innerHTML = newTable.innerHTML;
        }
        
        // 統計情報も更新
        const newStats = doc.querySelector('.admin-card .admin-card-body');
        if (newStats) {
            document.querySelector('.admin-card .admin-card-body').innerHTML = newStats.innerHTML;
        }
        
        // 最終更新時間を更新
        document.getElementById('last-updated').textContent = new Date().toLocaleString('ja-JP');
    })
    .catch(error => {
        console.error('更新に失敗しました:', error);
    });
}

// 強制ログアウト
function forceLogout(userId) {
    if (!confirm('このユーザーを強制ログアウトしますか？')) return;
    
    fetch(`/admin/users/${userId}/force-logout`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 該当行を削除または更新
            const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (row) {
                row.style.opacity = '0.5';
                setTimeout(() => {
                    row.remove();
                }, 1000);
            }
            
            alert('ユーザーを強制ログアウトしました。');
        } else {
            alert('エラーが発生しました: ' + (data.message || '不明なエラー'));
        }
    })
    .catch(error => {
        console.error('エラー:', error);
        alert('操作に失敗しました。');
    });
}

// ページを離れる時に自動更新を停止
window.addEventListener('beforeunload', function() {
    isAutoRefreshEnabled = false;
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});

// ページがフォーカスを失った時は更新頻度を下げる
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        isAutoRefreshEnabled = false;
    } else {
        isAutoRefreshEnabled = true;
        refreshData(); // フォーカス戻り時に即座に更新
    }
});
</script>

<style>
@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.7;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.admin-table tbody tr {
    transition: all 0.3s ease;
}

.admin-table tbody tr:hover {
    background-color: #f9fafb;
}
</style>
@endsection