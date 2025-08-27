@extends('admin.layouts.app')

@section('title', 'Road編集')
@section('subtitle', $road->name . ' の編集')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; list-style: none; margin: 0; padding: 0; font-size: 0.875rem;">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary); text-decoration: none;">ダッシュボード</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.roads.index') }}" style="color: var(--admin-primary); text-decoration: none;">Road管理</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.roads.show', $road->id) }}" style="color: var(--admin-primary); text-decoration: none;">{{ $road->name }}</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li style="color: var(--admin-secondary);">編集</li>
        </ol>
    </nav>

    <!-- ヘッダー -->
    <div style="margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">
            <i class="fas fa-edit"></i> Road編集: {{ $road->name }}
        </h2>
        <p style="margin-top: 0.5rem; color: var(--admin-secondary);">
            このRoadの情報を編集できます。変更は即座にゲームに反映されます。
        </p>
    </div>

    <!-- エラーメッセージ -->
    @if ($errors->any())
        <div class="admin-alert admin-alert-danger" style="margin-bottom: 2rem;">
            <h4 style="margin: 0 0 1rem 0;">入力内容にエラーがあります</h4>
            <ul style="margin: 0; padding-left: 1.5rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 成功メッセージ -->
    @if (session('success'))
        <div class="admin-alert admin-alert-success" style="margin-bottom: 2rem;">
            {{ session('success') }}
        </div>
    @endif

    <!-- フォーム -->
    <form id="road-edit-form" method="POST" action="{{ route('admin.roads.update', $road->id) }}">
        @csrf
        @method('PUT')
        
        @include('admin.roads._form')
        
    </form>

    <!-- 危険な操作 -->
    @if($canManageGameData ?? true)
    <div class="admin-card" style="margin-top: 3rem; border: 1px solid var(--admin-danger);">
        <div class="admin-card-header" style="background: rgba(239, 68, 68, 0.1);">
            <h3 class="admin-card-title" style="color: var(--admin-danger);">
                <i class="fas fa-exclamation-triangle"></i> 危険な操作
            </h3>
        </div>
        <div class="admin-card-body">
            <p style="margin-bottom: 1rem; color: var(--admin-secondary);">
                このRoadを削除すると、関連する接続情報やモンスタースポーン設定も失われる可能性があります。
                この操作は取り消せません。
            </p>
            <form method="POST" action="{{ route('admin.roads.destroy', $road->id) }}" 
                  style="display: inline;" 
                  onsubmit="return confirmDelete()">
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn admin-btn-danger">
                    <i class="fas fa-trash"></i> このRoadを削除
                </button>
            </form>
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
// 他のスクリプトとの干渉を防ぐため、独立して実行
(function() {
    'use strict';
    console.log('Road edit script starting...');
    
    // DOM読み込み完了を待つ
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeEditForm);
    } else {
        initializeEditForm();
    }
    
    function initializeEditForm() {
        console.log('Initializing edit form...');
        
        // 編集フォームを正確に特定
        const editForm = document.getElementById('road-edit-form');
        console.log('Edit form found:', editForm ? 'YES' : 'NO');
        
        if (editForm) {
            console.log('Form action URL:', editForm.action);
            console.log('Form method:', editForm.method);
            console.log('CSRF token present:', editForm.querySelector('input[name="_token"]') ? 'YES' : 'NO');
            console.log('Method spoofing:', editForm.querySelector('input[name="_method"]')?.value || 'NONE');
        
        // 更新ボタンクリック時のデバッグ
    const submitButton = document.getElementById('road-update-submit') || editForm.querySelector('button[type="submit"]');
        if (submitButton) {
            console.log('Submit button found:', submitButton.textContent.trim());
            submitButton.addEventListener('click', function(e) {
                console.log('Submit button clicked!');
                
                // フォーム送信が失敗する場合の直接送信
                setTimeout(() => {
                    console.log('Attempting direct form submission...');
                    try {
                        editForm.submit();
                    } catch (error) {
                        console.error('Direct form submission failed:', error);
                    }
                }, 100);
            });
        } else {
            console.error('Submit button not found!');
            const allButtons = editForm.querySelectorAll('button');
            console.log('All buttons in form:', allButtons.length);
            allButtons.forEach((btn, index) => {
                console.log(`Button ${index}:`, btn.type, btn.textContent.trim());
            });
        }
        
        // フォーム送信時のデバッグ（バリデーション無効化）
        editForm.addEventListener('submit', function(e) {
            console.log('Form submission event fired!');
            console.log('Form is being submitted to:', this.action);
            
            // デバッグ目的でフォームデータを出力
            const formData = new FormData(this);
            console.log('Form data being submitted:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            // JavaScriptバリデーションは一時的に無効化
            // フォームが正常に送信されるかテストする
            console.log('Allowing form submission without client-side validation...');
            return true;
        });
        
        // 追加のデバッグ: 他のイベントリスナーの干渉チェック
        setTimeout(() => {
            console.log('Checking for any JavaScript errors...');
            
            // テスト用の強制送信ボタンを追加
            const testButton = document.createElement('button');
            testButton.textContent = '[DEBUG] 強制送信テスト';
            testButton.style.cssText = 'position:fixed;top:10px;right:10px;z-index:9999;background:red;color:white;padding:10px;';
            testButton.onclick = function() {
                console.log('Force submit test clicked');
                console.log('Form exists:', !!editForm);
                console.log('Form action:', editForm.action);
                
                // 直接 POST リクエスト送信
                const formData = new FormData(editForm);
                console.log('Sending direct POST request...');
                
                fetch(editForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', [...response.headers.entries()]);
                    return response.text();
                }).then(data => {
                    console.log('Response data:', data.substring(0, 500) + '...');
                    if (data.includes('Road が正常に更新されました')) {
                        alert('更新成功！');
                        window.location.reload();
                    } else if (data.includes('error')) {
                        console.error('Server returned error:', data);
                    }
                }).catch(error => {
                    console.error('Fetch failed:', error);
                });
            };
            document.body.appendChild(testButton);
        }, 1000);
        
        } else {
        console.error('Road edit form not found!');
        // 全フォームを確認
        const allForms = document.querySelectorAll('form');
        console.log('All forms on page:', allForms.length);
        allForms.forEach((form, index) => {
            console.log(`Form ${index}:`, form.id || 'no-id', form.action, form.method);
        });
        }
    }
})();

function confirmDelete() {
    const roadName = '{{ $road->name }}';
    const confirmText = `Road「${roadName}」を本当に削除してもよろしいですか？\n\nこの操作により以下のデータが失われる可能性があります：\n• 関連する接続情報\n• モンスタースポーン設定\n• プレイヤーの進行状況\n\nこの操作は取り消せません。`;
    
    if (!confirm(confirmText)) {
        return false;
    }
    
    const doubleConfirm = prompt(`削除を確定するには「${roadName}」と入力してください：`);
    return doubleConfirm === roadName;
}
</script>
@endpush