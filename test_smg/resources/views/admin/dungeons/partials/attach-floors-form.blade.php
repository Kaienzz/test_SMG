<!-- 検索・フィルターフォーム -->
<form id="attach-search-form" style="margin-bottom: 1.5rem; padding: 1rem; background: var(--admin-bg); border-radius: 0.5rem;">
    <div style="display: flex; gap: 1rem; align-items: end;">
        <div style="flex: 1;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--admin-secondary);">
                フロア検索
            </label>
            <input type="text" name="search" value="{{ $searchQuery }}" 
                   placeholder="フロア名またはIDで検索..."
                   class="admin-form-input" style="width: 100%;">
        </div>
        <div>
            <label style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="hidden" name="only_orphans" value="0">
                <input type="checkbox" name="only_orphans" value="1" {{ $onlyOrphans ? 'checked' : '' }} 
                       class="admin-form-checkbox">
                <span style="color: var(--admin-secondary); font-size: 0.875rem;">
                    オーファンのみ表示
                </span>
            </label>
        </div>
        <button type="submit" class="admin-btn admin-btn-primary">
            <i class="fas fa-search"></i> 検索
        </button>
    </div>
</form>

@if($candidates->count() > 0)
<!-- アタッチ候補フロア一覧 -->
<form id="attach-floors-form">
    <div style="margin-bottom: 1rem;">
        <h4 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">
            アタッチ候補フロア ({{ $candidates->total() }}件)
        </h4>
        <p style="margin: 0; color: var(--admin-secondary); font-size: 0.875rem;">
            選択したフロアが「{{ $dungeon->dungeon_name }}」にアタッチされます。
        </p>
    </div>

    <div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--admin-border); border-radius: 0.5rem;">
        @foreach($candidates as $candidate)
        <div style="display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid var(--admin-border); {{ $loop->last ? 'border-bottom: none;' : '' }}">
            <label style="display: flex; align-items: center; gap: 1rem; width: 100%; cursor: pointer;">
                <input type="checkbox" name="floor_ids[]" value="{{ $candidate->id }}" 
                       class="admin-form-checkbox">
                
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                        <code style="background: var(--admin-bg); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                            {{ $candidate->id }}
                        </code>
                        <div style="font-weight: 600;">{{ $candidate->name }}</div>
                        
                        <!-- 現在の親表示 -->
                        @if($candidate->dungeon_id)
                        @php
                            $currentParent = \App\Models\DungeonDesc::where('dungeon_id', $candidate->dungeon_id)->first();
                        @endphp
                        @if($currentParent)
                        <span class="admin-badge admin-badge-warning admin-badge-sm">
                            {{ $currentParent->dungeon_name }}
                        </span>
                        @else
                        <span class="admin-badge admin-badge-danger admin-badge-sm">
                            親不在 ({{ $candidate->dungeon_id }})
                        </span>
                        @endif
                        @else
                        <span class="admin-badge admin-badge-secondary admin-badge-sm">
                            オーファン
                        </span>
                        @endif
                    </div>
                    
                    @if($candidate->description)
                    <div style="color: var(--admin-secondary); font-size: 0.875rem;">
                        {{ Str::limit($candidate->description, 60) }}
                    </div>
                    @endif
                    
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                        <span class="admin-badge admin-badge-info admin-badge-sm">
                            長さ: {{ $candidate->length }}
                        </span>
                        @php
                            $difficultyColors = ['easy' => 'success', 'normal' => 'info', 'hard' => 'danger'];
                            $difficultyLabels = ['easy' => '簡単', 'normal' => '普通', 'hard' => '困難'];
                        @endphp
                        <span class="admin-badge admin-badge-{{ $difficultyColors[$candidate->difficulty] ?? 'secondary' }} admin-badge-sm">
                            {{ $difficultyLabels[$candidate->difficulty] ?? $candidate->difficulty }}
                        </span>
                        @if(!$candidate->is_active)
                        <span class="admin-badge admin-badge-secondary admin-badge-sm">
                            非アクティブ
                        </span>
                        @endif
                    </div>
                </div>
            </label>
        </div>
        @endforeach
    </div>

    <!-- ページネーション -->
    @if($candidates->hasPages())
    <div style="margin: 1rem 0;">
        {{ $candidates->links() }}
    </div>
    @endif

    <!-- アクションボタン -->
    <div style="display: flex; justify-content: end; gap: 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
        <button type="button" id="close-attach-modal" class="admin-btn admin-btn-secondary">
            <i class="fas fa-times"></i> キャンセル
        </button>
        <button type="submit" class="admin-btn admin-btn-success">
            <i class="fas fa-link"></i> 選択したフロアをアタッチ
        </button>
    </div>
</form>

<script>
// 全選択/全解除機能
document.addEventListener('DOMContentLoaded', function() {
    // 全選択ボタンの追加
    const formHeader = document.querySelector('#attach-floors-form h4');
    if (formHeader) {
        const selectAllBtn = document.createElement('button');
        selectAllBtn.type = 'button';
        selectAllBtn.className = 'admin-btn admin-btn-xs admin-btn-info';
        selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> 全選択';
        selectAllBtn.style.marginLeft = '1rem';
        
        const deselectAllBtn = document.createElement('button');
        deselectAllBtn.type = 'button';
        deselectAllBtn.className = 'admin-btn admin-btn-xs admin-btn-secondary';
        deselectAllBtn.innerHTML = '<i class="fas fa-square"></i> 全解除';
        deselectAllBtn.style.marginLeft = '0.5rem';
        
        formHeader.appendChild(selectAllBtn);
        formHeader.appendChild(deselectAllBtn);
        
        selectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('input[name="floor_ids[]"]').forEach(cb => cb.checked = true);
        });
        
        deselectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('input[name="floor_ids[]"]').forEach(cb => cb.checked = false);
        });
    }
});
</script>

@else
<!-- 候補が見つからない場合 -->
<div style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
    <div style="margin-bottom: 1rem;">
        <i class="fas fa-search fa-3x"></i>
    </div>
    <h4 style="margin-bottom: 0.5rem;">アタッチ可能なフロアが見つかりません</h4>
    <p style="margin: 0;">
        @if($searchQuery)
            「{{ $searchQuery }}」に一致するフロアがありません。<br>
        @endif
        @if($onlyOrphans)
            オーファン（親に紐づいていない）フロアが存在しないか、<br>
        @endif
        検索条件を変更してお試しください。
    </p>
    
    <div style="margin-top: 1.5rem;">
        <button type="button" onclick="loadAttachForm('', false)" class="admin-btn admin-btn-info admin-btn-sm">
            <i class="fas fa-expand"></i> 他の親に紐づいているフロアも含めて検索
        </button>
    </div>
</div>
@endif

<style>
.admin-badge-sm {
    font-size: 0.7rem;
    padding: 0.125rem 0.375rem;
}

.admin-btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
</style>