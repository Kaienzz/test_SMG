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
    <form method="POST" action="{{ route('admin.roads.update', $road->id) }}">
        @csrf
        @method('PUT')
        
        @include('admin.roads._form')
        
    </form>

    <!-- 危険な操作 -->
    @if(auth()->user()->can('locations.delete'))
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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // フォームバリデーション
    const form = document.querySelector('form[method="POST"]');
    if (form && !form.querySelector('input[name="_method"][value="DELETE"]')) {
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value;
            const length = document.getElementById('length').value;
            const difficulty = document.getElementById('difficulty').value;
            
            // 必須項目チェック
            if (!name || !length || !difficulty) {
                e.preventDefault();
                alert('必須項目をすべて入力してください。');
                return;
            }
            
            // 長さチェック
            if (length < 1 || length > 1000) {
                e.preventDefault();
                alert('長さは1-1000の範囲で入力してください。');
                return;
            }
            
            // エンカウント率チェック
            const encounterRate = document.getElementById('encounter_rate').value;
            if (encounterRate && (encounterRate < 0 || encounterRate > 1)) {
                e.preventDefault();
                alert('エンカウント率は0.00-1.00の範囲で入力してください。');
                return;
            }
        });
    }
});

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
@endsection