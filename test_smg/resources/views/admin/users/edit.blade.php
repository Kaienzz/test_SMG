@extends('admin.layouts.app')

@section('title', 'ユーザー編集')
@section('subtitle', $user->name . ' の情報を編集')

@section('content')
<div class="admin-content-container">
    
    <form method="POST" action="{{ route('admin.users.update', $user) }}" id="user-edit-form">
        @csrf
        @method('PUT')
        
        <!-- 基本情報編集 -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div class="admin-card-header">
                <h3 class="admin-card-title">基本情報</h3>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">アカウント情報</h4>
                        
                        <!-- ユーザー名 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                ユーザー名 <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" 
                                   class="admin-input @error('name') admin-input-error @enderror" required>
                            @error('name')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- メールアドレス -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                メールアドレス <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" 
                                   class="admin-input @error('email') admin-input-error @enderror" required>
                            @error('email')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- パスワード -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                新しいパスワード <small style="color: var(--admin-secondary);">(変更する場合のみ)</small>
                            </label>
                            <input type="password" id="password" name="password" 
                                   class="admin-input @error('password') admin-input-error @enderror">
                            @error('password')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- パスワード確認 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="password_confirmation" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                パスワード確認
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" 
                                   class="admin-input">
                        </div>
                    </div>

                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">アカウント状態</h4>
                        
                        <!-- 認証状態 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">認証状態</label>
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 4px;">
                                @if($user->email_verified_at)
                                    <span class="admin-badge admin-badge-success">認証済み</span>
                                    <small style="margin-left: 0.5rem;">{{ $user->email_verified_at->format('Y/m/d H:i') }}</small>
                                @else
                                    <span class="admin-badge admin-badge-warning">未認証</span>
                                @endif
                            </div>
                        </div>

                        <!-- 登録日 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">登録日</label>
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 4px;">
                                {{ $user->created_at->format('Y年m月d日 H:i') }}
                            </div>
                        </div>

                        <!-- 最終アクティブ -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">最終アクティブ</label>
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 4px;">
                                @if($user->last_active_at)
                                    {{ $user->last_active_at->format('Y年m月d日 H:i') }}
                                    <small>({{ $user->last_active_at->diffForHumans() }})</small>
                                @else
                                    <span style="color: var(--admin-secondary);">未ログイン</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 管理者権限設定 -->
        @if(auth()->user()->can('admin.roles'))
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div class="admin-card-header">
                <h3 class="admin-card-title">管理者権限設定</h3>
                <div style="background: #fef3cd; color: #856404; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.875rem;">
                    ⚠️ 管理者権限の変更は慎重に行ってください
                </div>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                    <div>
                        <!-- 管理者権限 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="is_admin" value="1" 
                                       {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                                       onchange="toggleAdminFields(this.checked)">
                                <span style="font-weight: 500;">管理者権限を付与</span>
                            </label>
                        </div>

                        <!-- 管理者レベル -->
                        <div style="margin-bottom: 1.5rem;" id="admin-level-field" {{ !$user->is_admin ? 'style=display:none;' : '' }}>
                            <label for="admin_level" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                管理者レベル
                            </label>
                            <select id="admin_level" name="admin_level" class="admin-select">
                                <option value="basic" {{ old('admin_level', $user->admin_level) === 'basic' ? 'selected' : '' }}>
                                    Basic - 基本操作
                                </option>
                                <option value="advanced" {{ old('admin_level', $user->admin_level) === 'advanced' ? 'selected' : '' }}>
                                    Advanced - 高度な操作
                                </option>
                                <option value="admin" {{ old('admin_level', $user->admin_level) === 'admin' ? 'selected' : '' }}>
                                    Admin - 管理者操作
                                </option>
                                <option value="super" {{ old('admin_level', $user->admin_level) === 'super' ? 'selected' : '' }}>
                                    Super - 最高権限
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <!-- 管理者アクティベート日時 -->
                        @if($user->admin_activated_at)
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">管理者権限付与日</label>
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 4px;">
                                {{ $user->admin_activated_at->format('Y年m月d日 H:i') }}
                            </div>
                        </div>
                        @endif

                        <!-- 権限説明 -->
                        <div id="admin-level-description" {{ !$user->is_admin ? 'style=display:none;' : '' }}>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">権限説明</label>
                            <div id="level-description" style="padding: 0.75rem; background: #f0f9ff; border-radius: 4px; font-size: 0.875rem;">
                                <!-- JavaScript で動的に更新 -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- 管理者メモ -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div class="admin-card-header">
                <h3 class="admin-card-title">管理者メモ</h3>
            </div>
            <div class="admin-card-body">
                <div style="margin-bottom: 1rem;">
                    <label for="admin_notes" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        内部メモ <small style="color: var(--admin-secondary);">(ユーザーには表示されません)</small>
                    </label>
                    <textarea id="admin_notes" name="admin_notes" rows="4" 
                              class="admin-input @error('admin_notes') admin-input-error @enderror" 
                              placeholder="このユーザーに関する管理者向けのメモを記入してください...">{{ old('admin_notes', $user->admin_notes) }}</textarea>
                    @error('admin_notes')
                        <div class="admin-error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 操作ボタン -->
        <div style="display: flex; gap: 1rem; justify-content: end;">
            <a href="{{ route('admin.users.show', $user) }}" class="admin-btn admin-btn-secondary">
                ← 詳細に戻る
            </a>
            <button type="button" onclick="resetForm()" class="admin-btn admin-btn-secondary">
                🔄 リセット
            </button>
            <button type="submit" class="admin-btn admin-btn-primary">
                💾 保存
            </button>
        </div>
    </form>
</div>

<script>
// 管理者権限フィールドの表示切り替え
function toggleAdminFields(isAdmin) {
    const adminLevelField = document.getElementById('admin-level-field');
    const adminLevelDescription = document.getElementById('admin-level-description');
    
    if (isAdmin) {
        adminLevelField.style.display = 'block';
        adminLevelDescription.style.display = 'block';
        updateLevelDescription();
    } else {
        adminLevelField.style.display = 'none';
        adminLevelDescription.style.display = 'none';
    }
}

// 管理者レベル説明の更新
function updateLevelDescription() {
    const level = document.getElementById('admin_level').value;
    const descriptions = {
        'basic': '基本的なユーザー管理、分析データの閲覧が可能です。',
        'advanced': '基本操作に加え、ゲームデータの管理、詳細分析が可能です。',
        'admin': '高度な管理操作、システム設定の変更、ユーザー権限管理が可能です。',
        'super': '全ての管理操作、システム設定、緊急時対応が可能です。最高権限。'
    };
    
    document.getElementById('level-description').textContent = descriptions[level] || '';
}

// フォームリセット
function resetForm() {
    if (confirm('入力内容をリセットしますか？未保存の変更は失われます。')) {
        document.getElementById('user-edit-form').reset();
        
        // 管理者権限フィールドの表示状態もリセット
        const isAdmin = {{ $user->is_admin ? 'true' : 'false' }};
        document.querySelector('input[name="is_admin"]').checked = isAdmin;
        toggleAdminFields(isAdmin);
        
        // 管理者レベルもリセット
        document.getElementById('admin_level').value = '{{ $user->admin_level ?? 'basic' }}';
        updateLevelDescription();
    }
}

// 管理者レベル変更時の説明更新
document.getElementById('admin_level')?.addEventListener('change', updateLevelDescription);

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    const isAdmin = document.querySelector('input[name="is_admin"]').checked;
    toggleAdminFields(isAdmin);
    updateLevelDescription();
});

// フォーム送信前の確認
document.getElementById('user-edit-form').addEventListener('submit', function(e) {
    const isAdmin = document.querySelector('input[name="is_admin"]').checked;
    const originalIsAdmin = {{ $user->is_admin ? 'true' : 'false' }};
    
    // 管理者権限の変更があった場合は確認
    if (isAdmin !== originalIsAdmin) {
        const message = isAdmin 
            ? 'このユーザーに管理者権限を付与しますか？'
            : 'このユーザーの管理者権限を削除しますか？';
            
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    }
    
    // パスワード変更の確認
    const password = document.getElementById('password').value;
    if (password && !confirm('このユーザーのパスワードを変更しますか？')) {
        e.preventDefault();
        return false;
    }
});
</script>

<style>
.admin-input-error {
    border-color: #dc2626;
}

.admin-error-message {
    color: #dc2626;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
</style>
@endsection