@extends('admin.layouts.app')

@section('title', '新規ダンジョン作成')
@section('subtitle', '新しいダンジョンを作成します')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; list-style: none; margin: 0; padding: 0; font-size: 0.875rem;">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary); text-decoration: none;">ダッシュボード</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.dungeons.index') }}" style="color: var(--admin-primary); text-decoration: none;">Dungeon管理</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li style="color: var(--admin-secondary);">新規作成</li>
        </ol>
    </nav>

    <!-- ヘッダー -->
    <div style="margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">
            <i class="fas fa-plus-circle"></i> 新規ダンジョン作成
        </h2>
        <p style="margin-top: 0.5rem; color: var(--admin-secondary);">
            新しいダンジョンの基本情報を入力してください。ダンジョン作成後にフロアを追加できます。
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

    <!-- フォーム -->
    <form method="POST" action="{{ route('admin.dungeons.store') }}">
        @csrf
        
        @include('admin.dungeons._form')
        
    </form>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ダンジョンID自動生成ヘルパー
    const nameInput = document.getElementById('dungeon_name');
    const idInput = document.getElementById('dungeon_id');
    
    if (nameInput && idInput && !idInput.value) {
        nameInput.addEventListener('input', function() {
            // 日本語の名前から英数字のIDを生成（簡易版）
            let suggestedId = this.value
                .toLowerCase()
                .replace(/[^\w\s]/gi, '')
                .replace(/\s+/g, '_')
                .replace(/の|と|や|で|に|を|が|は|へ|から|まで/g, '') // 日本語助詞を除去
                .substring(0, 30); // 長さ制限
            
            // 既存の値がない場合のみ自動入力
            if (!idInput.value) {
                idInput.value = suggestedId;
            }
        });
    }

    // フォームバリデーション
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const dungeonId = document.getElementById('dungeon_id').value;
        const dungeonName = document.getElementById('dungeon_name').value;
        
        // 必須項目チェック
        if (!dungeonId || !dungeonName) {
            e.preventDefault();
            alert('ダンジョンIDとダンジョン名は必須です。');
            return;
        }
        
        // ダンジョンID形式チェック
        if (!/^[a-zA-Z][a-zA-Z0-9_]*$/.test(dungeonId)) {
            e.preventDefault();
            alert('ダンジョンIDは英字で始まり、英数字とアンダースコアのみ使用してください。');
            return;
        }
        
        // 確認ダイアログ
        const confirmMessage = `以下の内容でダンジョンを作成してもよろしいですか？\n\nダンジョンID: ${dungeonId}\nダンジョン名: ${dungeonName}\n\n作成後にフロアを追加できます。`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return;
        }
    });

    // リアルタイムID検証
    const dungeonIdInput = document.getElementById('dungeon_id');
    if (dungeonIdInput) {
        dungeonIdInput.addEventListener('input', function() {
            const value = this.value;
            const isValid = /^[a-zA-Z][a-zA-Z0-9_]*$/.test(value);
            
            // フィードバック表示
            let feedback = this.parentNode.querySelector('.id-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'id-feedback';
                feedback.style.marginTop = '0.5rem';
                feedback.style.fontSize = '0.875rem';
                this.parentNode.appendChild(feedback);
            }
            
            if (value === '') {
                feedback.textContent = '';
                feedback.style.color = '';
            } else if (isValid) {
                feedback.textContent = '✓ 有効なダンジョンIDです';
                feedback.style.color = 'var(--admin-success)';
            } else {
                feedback.textContent = '✗ 英字で始まり、英数字とアンダースコアのみ使用してください';
                feedback.style.color = 'var(--admin-danger)';
            }
        });
    }
});
</script>

<style>
/* プレビュー用スタイル */
.dungeon-preview {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border: 2px dashed var(--admin-border);
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    margin-top: 1rem;
}

.dungeon-preview h4 {
    margin: 0 0 1rem 0;
    color: var(--admin-primary);
}

.dungeon-preview p {
    margin: 0;
    color: var(--admin-secondary);
}
</style>
@endsection