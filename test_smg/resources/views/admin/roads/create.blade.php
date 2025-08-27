@extends('admin.layouts.app')

@section('title', '新規Road作成')
@section('subtitle', '新しいRoadを作成します')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; list-style: none; margin: 0; padding: 0; font-size: 0.875rem;">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary); text-decoration: none;">ダッシュボード</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.roads.index') }}" style="color: var(--admin-primary); text-decoration: none;">Road管理</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li style="color: var(--admin-secondary);">新規作成</li>
        </ol>
    </nav>

    <!-- ヘッダー -->
    <div style="margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">
            <i class="fas fa-plus-circle"></i> 新規Road作成
        </h2>
        <p style="margin-top: 0.5rem; color: var(--admin-secondary);">
            新しいRoadの情報を入力してください。すべての必須項目を入力する必要があります。
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
    <form method="POST" action="{{ route('admin.roads.store') }}">
        @csrf
        
        @include('admin.roads._form')
        
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Road ID自動生成ヘルパー
    const nameInput = document.getElementById('name');
    const idInput = document.getElementById('id');
    
    if (nameInput && idInput && !idInput.value) {
        nameInput.addEventListener('input', function() {
            // 日本語の名前から英数字のIDを生成（簡易版）
            let suggestedId = this.value
                .toLowerCase()
                .replace(/[^\w\s]/gi, '')
                .replace(/\s+/g, '_');
            
            // 既存の値がない場合のみ自動入力
            if (!idInput.value) {
                idInput.value = suggestedId;
            }
        });
    }

    // フォームバリデーション
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submission started...');
            
            const id = document.getElementById('id') ? document.getElementById('id').value.trim() : '';
            const name = document.getElementById('name') ? document.getElementById('name').value.trim() : '';
            const length = document.getElementById('length') ? document.getElementById('length').value : '';
            const difficulty = document.getElementById('difficulty') ? document.getElementById('difficulty').value : '';
            
            console.log('Form values:', { id, name, length, difficulty });
            
            // 必須項目チェック
            if (!id || !name || !length || !difficulty) {
                e.preventDefault();
                alert('必須項目をすべて入力してください。\n\nID: ' + (id ? '✓' : '✗') + '\n名前: ' + (name ? '✓' : '✗') + '\n長さ: ' + (length ? '✓' : '✗') + '\n難易度: ' + (difficulty ? '✓' : '✗'));
                return false;
            }
            
            // ID形式チェック（より緩和）
            if (!/^[a-zA-Z][a-zA-Z0-9_-]*$/.test(id)) {
                e.preventDefault();
                alert('Road IDは英字で始まり、英数字・アンダースコア・ハイフンのみ使用してください。\n現在の入力値: "' + id + '"');
                return false;
            }
            
            // 長さチェック
            const lengthNum = parseInt(length);
            if (isNaN(lengthNum) || lengthNum < 1 || lengthNum > 1000) {
                e.preventDefault();
                alert('長さは1-1000の範囲の数値で入力してください。\n現在の入力値: "' + length + '"');
                return false;
            }
            
            // エンカウント率チェック
            const encounterRateElement = document.getElementById('encounter_rate');
            const encounterRate = encounterRateElement ? encounterRateElement.value : '';
            if (encounterRate && encounterRate.trim() !== '') {
                const encounterRateNum = parseFloat(encounterRate);
                if (isNaN(encounterRateNum) || encounterRateNum < 0 || encounterRateNum > 1) {
                    e.preventDefault();
                    alert('エンカウント率は0.00-1.00の範囲で入力してください。\n現在の入力値: "' + encounterRate + '"');
                    return false;
                }
            }
            
            console.log('Form validation passed, submitting...');
            return true;
        });
    } else {
        console.error('Form not found!');
    }
});
</script>
@endpush