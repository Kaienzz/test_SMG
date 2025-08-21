@extends('admin.layouts.app')

@section('title', 'ダンジョン編集')
@section('subtitle', $dungeon->dungeon_name . ' の編集')

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
            <li style="color: var(--admin-secondary);">編集</li>
        </ol>
    </nav>

    <!-- ヘッダー -->
    <div style="margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">
            <i class="fas fa-edit"></i> ダンジョン編集: {{ $dungeon->dungeon_name }}
        </h2>
        <p style="margin-top: 0.5rem; color: var(--admin-secondary);">
            このダンジョンの基本情報を編集できます。変更は即座にゲームに反映されます。
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
    <form method="POST" action="{{ route('admin.dungeons.update', $dungeon->id) }}">
        @csrf
        @method('PUT')
        
        @include('admin.dungeons._form')
        
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
            <div style="margin-bottom: 1rem;">
                <h4 style="color: var(--admin-danger); margin-bottom: 0.5rem;">ダンジョンの削除</h4>
                <p style="margin-bottom: 1rem; color: var(--admin-secondary);">
                    このダンジョンを削除すると、以下のデータが失われます：
                </p>
                <ul style="margin-bottom: 1rem; color: var(--admin-secondary); padding-left: 1.5rem;">
                    <li><strong>{{ $dungeon->floors->count() }} 個のフロア</strong>（Route）</li>
                    <li>関連する接続情報</li>
                    <li>モンスタースポーン設定</li>
                    <li>プレイヤーの進行状況</li>
                </ul>
                <div style="padding: 1rem; background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--admin-danger); border-radius: 0.25rem; margin-bottom: 1rem;">
                    <strong>警告:</strong> この操作は取り消せません。削除する前に必要なデータをバックアップしてください。
                </div>
            </div>
            
            <form method="POST" action="{{ route('admin.dungeons.destroy', $dungeon->id) }}" 
                  style="display: inline;" 
                  onsubmit="return confirmDelete()">
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn admin-btn-danger">
                    <i class="fas fa-trash"></i> このダンジョンを削除
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
            const dungeonName = document.getElementById('dungeon_name').value;
            
            // 必須項目チェック
            if (!dungeonName) {
                e.preventDefault();
                alert('ダンジョン名は必須です。');
                return;
            }
        });
    }
});

function confirmDelete() {
    const dungeonName = '{{ $dungeon->dungeon_name }}';
    const dungeonId = '{{ $dungeon->dungeon_id }}';
    const floorCount = {{ $dungeon->floors->count() }};
    
    const confirmText = `ダンジョン「${dungeonName}」を本当に削除してもよろしいですか？\n\nこの操作により以下のデータが永続的に失われます：\n• ダンジョン基本情報\n• ${floorCount} 個のフロア\n• 関連する接続情報\n• モンスタースポーン設定\n• プレイヤーの進行状況\n\nこの操作は取り消せません。`;
    
    if (!confirm(confirmText)) {
        return false;
    }
    
    // 二重確認：ダンジョン名の入力を求める
    const doubleConfirm = prompt(`削除を確定するには「${dungeonName}」と正確に入力してください：`);
    if (doubleConfirm !== dungeonName) {
        alert('入力されたダンジョン名が正しくありません。削除をキャンセルします。');
        return false;
    }
    
    // 三重確認：フロア数の確認
    if (floorCount > 0) {
        const tripleConfirm = confirm(`最終確認: ${floorCount} 個のフロアも同時に削除されます。本当に実行しますか？`);
        return tripleConfirm;
    }
    
    return true;
}
</script>
@endsection