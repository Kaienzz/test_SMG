@extends('admin.layouts.app')

@section('title', 'ユーザー詳細')
@section('subtitle', $user->name . ' の詳細情報')

@section('content')
<div class="admin-content-container">
    
    <!-- ユーザー基本情報 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">基本情報</h3>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('admin.users.edit', $user) }}" class="admin-btn admin-btn-primary">
                    ✏️ 編集
                </a>
                @if(auth()->user()->can('users.suspend'))
                <button type="button" onclick="showSuspendModal()" class="admin-btn admin-btn-danger">
                    🚫 アカウント停止
                </button>
                @endif
                @if(auth()->user()->can('users.edit'))
                <button type="button" onclick="forceLogout()" class="admin-btn admin-btn-warning">
                    🔄 強制ログアウト
                </button>
                @endif
            </div>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: auto 1fr; gap: 2rem;">
                <!-- アバター -->
                <div style="text-align: center;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--admin-primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold; margin-bottom: 1rem;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    @if($user->is_admin)
                        <span class="admin-badge admin-badge-danger">管理者</span>
                    @endif
                </div>

                <!-- 詳細情報 -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">アカウント情報</h4>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>ユーザー名:</strong> {{ $user->name }}
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>メールアドレス:</strong> {{ $user->email }}
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>認証状態:</strong>
                            @if($user->email_verified_at)
                                <span class="admin-badge admin-badge-success">認証済み</span>
                                <small>({{ $user->email_verified_at->format('Y/m/d H:i') }})</small>
                            @else
                                <span class="admin-badge admin-badge-warning">未認証</span>
                            @endif
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>ユーザーID:</strong> {{ $user->id }}
                        </div>
                        @if($user->admin_notes)
                        <div style="margin-bottom: 0.75rem;">
                            <strong>管理者メモ:</strong><br>
                            <div style="background: #f9fafb; padding: 0.75rem; border-radius: 4px; margin-top: 0.5rem;">
                                {{ $user->admin_notes }}
                            </div>
                        </div>
                        @endif
                    </div>

                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">アクティビティ</h4>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>登録日:</strong> {{ $user->created_at->format('Y年m月d日 H:i') }}
                            <small>({{ $user->created_at->diffForHumans() }})</small>
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>最終アクティブ:</strong>
                            @if($user->last_active_at)
                                {{ $user->last_active_at->format('Y年m月d日 H:i') }}
                                <small>({{ $user->last_active_at->diffForHumans() }})</small>
                                @if($user->last_active_at >= now()->subMinutes(15))
                                    <span class="admin-badge admin-badge-success" style="margin-left: 0.5rem;">オンライン中</span>
                                @endif
                            @else
                                <span style="color: var(--admin-secondary);">未ログイン</span>
                            @endif
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>最終IPアドレス:</strong> {{ $sessionInfo['ip_address'] ?? '不明' }}
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>デバイス:</strong> {{ $sessionInfo['device_type'] ?? '不明' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- プレイヤー情報 -->
    @if($user->player)
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">プレイヤー情報</h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
                <!-- 基本ステータス -->
                <div>
                    <h4 style="margin-bottom: 1rem; color: #374151;">基本ステータス</h4>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>レベル:</strong> {{ $playerStats['basic_info']['level'] ?? $user->player->level }}
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>経験値:</strong> {{ number_format($playerStats['basic_info']['experience'] ?? $user->player->experience) }}
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>ゴールド:</strong> {{ number_format($playerStats['basic_info']['gold'] ?? $user->player->gold) }}G
                    </div>
                </div>

                <!-- 戦闘ステータス -->
                <div>
                    <h4 style="margin-bottom: 1rem; color: #374151;">戦闘ステータス</h4>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>HP:</strong> {{ $playerStats['combat_stats']['hp'] ?? "{$user->player->hp}/{$user->player->max_hp}" }}
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>MP:</strong> {{ $playerStats['combat_stats']['mp'] ?? "{$user->player->mp}/{$user->player->max_mp}" }}
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>SP:</strong> {{ $playerStats['combat_stats']['sp'] ?? "{$user->player->sp}/{$user->player->max_sp}" }}
                    </div>
                </div>

                <!-- 位置情報 -->
                <div>
                    <h4 style="margin-bottom: 1rem; color: #374151;">位置・戦績</h4>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>現在地:</strong> 
                        {{ $playerStats['location']['type'] ?? $user->player->location_type }}
                        (ID: {{ $playerStats['location']['id'] ?? $user->player->location_id }})
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>位置:</strong> {{ $playerStats['location']['position'] ?? $user->player->game_position }}
                    </div>
                    @if(isset($playerStats['battle_stats']))
                    <div style="margin-bottom: 0.75rem;">
                        <strong>総バトル数:</strong> {{ number_format($playerStats['battle_stats']['total_battles']) }}
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>勝利数:</strong> {{ number_format($playerStats['battle_stats']['wins']) }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-body" style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
            このユーザーはまだプレイヤーキャラクターを作成していません
        </div>
    </div>
    @endif

    <!-- アクティビティ履歴 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">アクティビティ履歴</h3>
        </div>
        <div class="admin-card-body">
            @if($activityLogs->count() > 0)
            <div class="activity-timeline" style="max-height: 400px; overflow-y: auto;">
                @foreach($activityLogs as $log)
                <div class="activity-item" style="padding: 1rem; border-bottom: 1px solid var(--admin-border); last:border-bottom: none;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                        <div>
                            <div style="font-weight: 500; margin-bottom: 0.25rem;">
                                {{ $log->description }}
                            </div>
                            <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                                操作者: {{ $log->admin_name ?? 'システム' }}
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: end; gap: 0.25rem;">
                            <span class="admin-badge admin-badge-{{ $log->severity === 'critical' ? 'danger' : ($log->severity === 'high' ? 'warning' : 'info') }}">
                                {{ ucfirst($log->severity ?? 'info') }}
                            </span>
                            <div style="font-size: 0.75rem; color: var(--admin-secondary);">
                                {{ \Carbon\Carbon::parse($log->event_time)->format('Y/m/d H:i:s') }}
                            </div>
                        </div>
                    </div>
                    @if($log->is_security_event)
                    <div style="font-size: 0.875rem; background: #fef2f2; color: #dc2626; padding: 0.5rem; border-radius: 4px;">
                        🔒 セキュリティイベント
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
                アクティビティ履歴がありません
            </div>
            @endif
        </div>
    </div>
</div>

<!-- アカウント停止モーダル -->
<div id="suspend-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 2rem; width: 90%; max-width: 500px;">
        <h3 style="margin-bottom: 1.5rem;">アカウント停止</h3>
        <form id="suspend-form" method="POST" action="{{ route('admin.users.suspend', $user) }}">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">停止理由 (必須)</label>
                <textarea name="reason" rows="3" class="admin-input" placeholder="停止理由を入力してください..." required></textarea>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">停止期間</label>
                <select name="duration" class="admin-select">
                    <option value="">無期限</option>
                    <option value="1">1日</option>
                    <option value="7">7日</option>
                    <option value="30">30日</option>
                    <option value="90">90日</option>
                </select>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: end;">
                <button type="button" onclick="hideSuspendModal()" class="admin-btn admin-btn-secondary">
                    キャンセル
                </button>
                <button type="submit" class="admin-btn admin-btn-danger">
                    停止実行
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showSuspendModal() {
    document.getElementById('suspend-modal').style.display = 'block';
}

function hideSuspendModal() {
    document.getElementById('suspend-modal').style.display = 'none';
    document.getElementById('suspend-form').reset();
}

function forceLogout() {
    if (!confirm('このユーザーを強制ログアウトしますか？')) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.users.force_logout", $user) }}';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    document.body.appendChild(form);
    form.submit();
}

// モーダル外をクリックで閉じる
document.getElementById('suspend-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideSuspendModal();
    }
});
</script>
@endsection