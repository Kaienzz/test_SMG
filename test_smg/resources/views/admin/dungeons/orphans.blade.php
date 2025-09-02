@extends('admin.layouts.app')

@section('title', 'オーファンフロア整理')
@section('subtitle', '親に紐づいていないダンジョンフロアの管理')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; list-style: none; margin: 0; padding: 0; font-size: 0.875rem;">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary); text-decoration: none;">ダッシュボード</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.dungeons.index') }}" style="color: var(--admin-primary); text-decoration: none;">Dungeon管理</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li style="color: var(--admin-secondary);">オーファン整理</li>
        </ol>
    </nav>

    <!-- アクションバー -->
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">オーファンフロア整理</h2>
        <div style="display: flex; gap: 1rem;">
            <a href="{{ route('admin.dungeons.index') }}" class="admin-btn admin-btn-secondary">
                <i class="fas fa-arrow-left"></i> ダンジョン一覧に戻る
            </a>
        </div>
    </div>

    @if(isset($error))
        <div class="admin-alert admin-alert-danger" style="margin-bottom: 2rem;">
            {{ $error }}
        </div>
    @endif

    <!-- 統計情報 -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ $orphanFloors->total() }}
                </div>
                <div style="color: var(--admin-secondary);">オーファンフロア</div>
                <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                    (dungeon_id = null)
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-danger); margin-bottom: 0.5rem;">
                    {{ $missingParentFloors->total() }}
                </div>
                <div style="color: var(--admin-secondary);">親不在フロア</div>
                <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                    (参照先の親が存在しない)
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ $availableParents->count() }}
                </div>
                <div style="color: var(--admin-secondary);">利用可能な親</div>
                <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                    (アクティブなダンジョン)
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ $orphanFloors->total() + $missingParentFloors->total() }}
                </div>
                <div style="color: var(--admin-secondary);">要処理フロア数</div>
                <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                    (合計)
                </div>
            </div>
        </div>
    </div>

    @if($orphanFloors->count() > 0)
    <!-- オーファンフロア一覧 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title" style="color: var(--admin-warning);">
                <i class="fas fa-exclamation-triangle"></i> オーファンフロア ({{ $orphanFloors->total() }}件)
            </h3>
        </div>
        <div class="admin-card-body">
            <div style="margin-bottom: 1rem; padding: 1rem; background: var(--admin-warning-bg, #fef3c7); border-radius: 0.5rem; border-left: 4px solid var(--admin-warning);">
                <p style="margin: 0; color: var(--admin-warning-text, #92400e);">
                    <strong>オーファンフロア:</strong> dungeon_id が null のフロアです。これらのフロアは現在どのダンジョンにも属していません。
                </p>
            </div>

            <form id="orphan-process-form" method="POST" action="{{ route('admin.dungeons.process-orphans') }}">
                @csrf
                <input type="hidden" name="action" value="">
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="select-all-orphans" class="admin-form-checkbox">
                                </th>
                                <th>フロアID</th>
                                <th>フロア名</th>
                                <th>長さ</th>
                                <th>難易度</th>
                                <th>ステータス</th>
                                <th>作成日</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orphanFloors as $floor)
                            <tr>
                                <td>
                                    <input type="checkbox" name="floor_ids[]" value="{{ $floor->id }}" class="admin-form-checkbox orphan-checkbox">
                                </td>
                                <td>
                                    <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                        {{ $floor->id }}
                                    </code>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">{{ $floor->name }}</div>
                                    @if($floor->description)
                                    <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                        {{ Str::limit($floor->description, 60) }}
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="admin-badge admin-badge-info">{{ $floor->length }}</span>
                                </td>
                                <td>
                                    @php
                                        $difficultyColors = ['easy' => 'success', 'normal' => 'info', 'hard' => 'danger'];
                                        $difficultyLabels = ['easy' => '簡単', 'normal' => '普通', 'hard' => '困難'];
                                    @endphp
                                    <span class="admin-badge admin-badge-{{ $difficultyColors[$floor->difficulty] ?? 'secondary' }}">
                                        {{ $difficultyLabels[$floor->difficulty] ?? $floor->difficulty }}
                                    </span>
                                </td>
                                <td>
                                    @if($floor->is_active)
                                    <span class="admin-badge admin-badge-success">アクティブ</span>
                                    @else
                                    <span class="admin-badge admin-badge-secondary">非アクティブ</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                                        {{ $floor->created_at->format('Y/m/d H:i') }}
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($orphanFloors->hasPages())
                <div style="padding: 1.5rem; border-top: 1px solid var(--admin-border);">
                    {{ $orphanFloors->links() }}
                </div>
                @endif
            </form>
        </div>
    </div>
    @endif

    @if($missingParentFloors->count() > 0)
    <!-- 親不在フロア一覧 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title" style="color: var(--admin-danger);">
                <i class="fas fa-unlink"></i> 親不在フロア ({{ $missingParentFloors->total() }}件)
            </h3>
        </div>
        <div class="admin-card-body">
            <div style="margin-bottom: 1rem; padding: 1rem; background: var(--admin-danger-bg, #fee2e2); border-radius: 0.5rem; border-left: 4px solid var(--admin-danger);">
                <p style="margin: 0; color: var(--admin-danger-text, #991b1b);">
                    <strong>親不在フロア:</strong> dungeon_id は設定されていますが、対応する親ダンジョンが存在しないフロアです。データの整合性に問題があります。
                </p>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>フロアID</th>
                            <th>フロア名</th>
                            <th>参照先dungeon_id</th>
                            <th>長さ</th>
                            <th>難易度</th>
                            <th>ステータス</th>
                            <th>作成日</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($missingParentFloors as $floor)
                        <tr>
                            <td>
                                <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                    {{ $floor->id }}
                                </code>
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $floor->name }}</div>
                                @if($floor->description)
                                <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                    {{ Str::limit($floor->description, 60) }}
                                </div>
                                @endif
                            </td>
                            <td>
                                <code style="background: var(--admin-danger-bg, #fee2e2); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem; color: var(--admin-danger);">
                                    {{ $floor->dungeon_id }}
                                </code>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-info">{{ $floor->length }}</span>
                            </td>
                            <td>
                                @php
                                    $difficultyColors = ['easy' => 'success', 'normal' => 'info', 'hard' => 'danger'];
                                    $difficultyLabels = ['easy' => '簡単', 'normal' => '普通', 'hard' => '困難'];
                                @endphp
                                <span class="admin-badge admin-badge-{{ $difficultyColors[$floor->difficulty] ?? 'secondary' }}">
                                    {{ $difficultyLabels[$floor->difficulty] ?? $floor->difficulty }}
                                </span>
                            </td>
                            <td>
                                @if($floor->is_active)
                                <span class="admin-badge admin-badge-success">アクティブ</span>
                                @else
                                <span class="admin-badge admin-badge-secondary">非アクティブ</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                                    {{ $floor->created_at->format('Y/m/d H:i') }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($missingParentFloors->hasPages())
            <div style="padding: 1.5rem; border-top: 1px solid var(--admin-border);">
                {{ $missingParentFloors->links() }}
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($orphanFloors->count() > 0 && $availableParents->count() > 0)
    <!-- 処理アクション -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-tools"></i> オーファンフロア処理アクション
            </h3>
        </div>
        <div class="admin-card-body">
            <div x-data="{ actionType: 'attach_to_existing' }" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                
                <!-- 既存の親にアタッチ -->
                <div class="action-section" style="padding: 1.5rem; border: 2px solid var(--admin-border); border-radius: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; cursor: pointer;">
                        <input type="radio" name="action_type" value="attach_to_existing" 
                               x-model="actionType" class="admin-form-radio">
                        <div>
                            <h4 style="margin: 0; color: var(--admin-primary);">既存の親にアタッチ</h4>
                            <p style="margin: 0.25rem 0 0 0; color: var(--admin-secondary); font-size: 0.875rem;">
                                選択したフロアを既存のダンジョンに紐づけます
                            </p>
                        </div>
                    </label>
                    
                    <div x-show="actionType === 'attach_to_existing'">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--admin-secondary);">
                            アタッチ先ダンジョン
                        </label>
                        <select name="target_dungeon_id" class="admin-form-select" style="width: 100%;">
                            <option value="">-- ダンジョンを選択 --</option>
                            @foreach($availableParents as $parent)
                            <option value="{{ $parent->id }}">
                                {{ $parent->dungeon_name }} ({{ $parent->dungeon_id }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 新規親作成してアタッチ -->
                <div class="action-section" style="padding: 1.5rem; border: 2px solid var(--admin-border); border-radius: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; cursor: pointer;">
                        <input type="radio" name="action_type" value="create_new_parent" 
                               x-model="actionType" class="admin-form-radio">
                        <div>
                            <h4 style="margin: 0; color: var(--admin-success);">新規親作成してアタッチ</h4>
                            <p style="margin: 0.25rem 0 0 0; color: var(--admin-secondary); font-size: 0.875rem;">
                                新しいダンジョンを作成して選択したフロアを紐づけます
                            </p>
                        </div>
                    </label>
                    
                    <div x-show="actionType === 'create_new_parent'" style="display: grid; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--admin-secondary);">
                                ダンジョンID <span style="color: var(--admin-danger);">*</span>
                            </label>
                            <input type="text" name="new_dungeon_id" placeholder="例: dungeon_new_001"
                                   class="admin-form-input" style="width: 100%;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--admin-secondary);">
                                ダンジョン名 <span style="color: var(--admin-danger);">*</span>
                            </label>
                            <input type="text" name="new_dungeon_name" placeholder="例: 新しいダンジョン"
                                   class="admin-form-input" style="width: 100%;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--admin-secondary);">
                                説明 (任意)
                            </label>
                            <textarea name="new_dungeon_desc" placeholder="ダンジョンの説明..." 
                                      class="admin-form-textarea" style="width: 100%; min-height: 80px;"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 実行ボタン -->
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--admin-border); text-align: center;">
                <button type="button" id="process-orphans-btn" class="admin-btn admin-btn-primary admin-btn-lg" disabled>
                    <i class="fas fa-magic"></i> 選択したフロアを処理実行
                </button>
                <p style="margin-top: 0.5rem; color: var(--admin-secondary); font-size: 0.875rem;">
                    フロアを選択してください
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($orphanFloors->count() == 0 && $missingParentFloors->count() == 0)
    <!-- 処理対象なしの場合 -->
    <div class="admin-card">
        <div class="admin-card-body" style="text-align: center; padding: 3rem;">
            <div style="color: var(--admin-success); margin-bottom: 1rem;">
                <i class="fas fa-check-circle fa-3x"></i>
            </div>
            <h3 style="margin-bottom: 0.5rem; color: var(--admin-success);">すべてのフロアが適切に管理されています</h3>
            <p style="color: var(--admin-secondary); margin-bottom: 2rem;">
                現在、オーファンフロアや親不在フロアは存在しません。<br>
                すべてのダンジョンフロアが適切な親ダンジョンに紐づいています。
            </p>
            <a href="{{ route('admin.dungeons.index') }}" class="admin-btn admin-btn-primary">
                <i class="fas fa-list"></i> ダンジョン一覧に戻る
            </a>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<!-- Alpine.js for reactive form behavior -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-orphans');
    const orphanCheckboxes = document.querySelectorAll('.orphan-checkbox');
    const processBtn = document.getElementById('process-orphans-btn');
    const orphanForm = document.getElementById('orphan-process-form');
    
    // 全選択/全解除
    selectAllCheckbox?.addEventListener('change', function() {
        orphanCheckboxes.forEach(cb => cb.checked = this.checked);
        updateProcessButton();
    });
    
    // 個別チェックボックス
    orphanCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateProcessButton();
            updateSelectAllState();
        });
    });
    
    // 選択状態の更新
    function updateSelectAllState() {
        const checkedCount = document.querySelectorAll('.orphan-checkbox:checked').length;
        const totalCount = orphanCheckboxes.length;
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = checkedCount === totalCount && totalCount > 0;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < totalCount;
        }
    }
    
    function updateProcessButton() {
        const checkedCount = document.querySelectorAll('.orphan-checkbox:checked').length;
        if (processBtn) {
            processBtn.disabled = checkedCount === 0;
            const btnText = processBtn.querySelector('span');
            if (btnText) {
                btnText.textContent = checkedCount > 0 ? 
                    `選択した${checkedCount}個のフロアを処理実行` : 
                    '選択したフロアを処理実行';
            }
            
            const helpText = processBtn.nextElementSibling;
            if (helpText) {
                helpText.textContent = checkedCount > 0 ? 
                    `${checkedCount}個のフロアが選択されています` : 
                    'フロアを選択してください';
            }
        }
    }
    
    // 処理実行ボタン
    processBtn?.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.orphan-checkbox:checked');
        const actionType = document.querySelector('input[name="action_type"]:checked')?.value;
        
        if (checkedBoxes.length === 0) {
            alert('処理するフロアを選択してください。');
            return;
        }
        
        if (!actionType) {
            alert('処理方法を選択してください。');
            return;
        }
        
        // バリデーション
        if (actionType === 'attach_to_existing') {
            const targetDungeon = document.querySelector('select[name="target_dungeon_id"]').value;
            if (!targetDungeon) {
                alert('アタッチ先のダンジョンを選択してください。');
                return;
            }
        } else if (actionType === 'create_new_parent') {
            const dungeonId = document.querySelector('input[name="new_dungeon_id"]').value.trim();
            const dungeonName = document.querySelector('input[name="new_dungeon_name"]').value.trim();
            
            if (!dungeonId || !dungeonName) {
                alert('新規ダンジョンのIDと名前を入力してください。');
                return;
            }
        }
        
        // 確認ダイアログ
        const message = actionType === 'attach_to_existing' ?
            `${checkedBoxes.length}個のフロアを既存のダンジョンにアタッチしますか？` :
            `${checkedBoxes.length}個のフロアで新しいダンジョンを作成しますか？`;
            
        if (confirm(message + '\n\nこの操作は取り消せません。')) {
            // アクションタイプをフォームにセット
            document.querySelector('input[name="action"]').value = actionType;
            orphanForm.submit();
        }
    });
    
    // 初期状態の設定
    updateProcessButton();
    updateSelectAllState();
});
</script>

<style>
.action-section {
    transition: all 0.2s ease;
}

.action-section:has(input[type="radio"]:checked) {
    border-color: var(--admin-primary) !important;
    background: var(--admin-primary-bg, #eff6ff);
}

.admin-btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1rem;
}

.admin-danger-bg {
    background-color: #fee2e2;
}

.admin-warning-bg {
    background-color: #fef3c7;
}

.admin-primary-bg {
    background-color: #eff6ff;
}
</style>
@endsection