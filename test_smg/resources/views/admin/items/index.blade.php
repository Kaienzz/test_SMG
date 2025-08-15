@extends('admin.layouts.app')

@section('title', 'アイテム管理')
@section('subtitle', 'ゲーム内アイテムの管理と設定')

@section('content')
<div class="admin-content-container">
    
    <!-- 統計カード -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ number_format($stats['total_items']) }}
                </div>
                <div style="color: var(--admin-secondary);">総アイテム数</div>
                <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                    標準: {{ number_format($stats['total_standard']) }} | カスタム: {{ number_format($stats['total_custom']) }}
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    標準: {{ number_format($stats['avg_value_standard'] ?? 0) }}G
                </div>
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    カスタム: {{ number_format($stats['avg_value_custom'] ?? 0) }}G
                </div>
                <div style="color: var(--admin-secondary);">平均価格</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ $stats['by_category']->count() }}
                </div>
                <div style="color: var(--admin-secondary);">カテゴリ数</div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    標準: {{ number_format($stats['total_value_standard'] ?? 0) }}G
                </div>
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    カスタム: {{ number_format($stats['total_value_custom'] ?? 0) }}G
                </div>
                <div style="color: var(--admin-secondary);">総価値</div>
            </div>
        </div>
    </div>

    <!-- フィルター・検索 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">検索・フィルター</h3>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.items.index') }}" class="filter-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <!-- 検索 -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">検索</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                               placeholder="アイテム名・説明" class="admin-input">
                    </div>

                    <!-- カテゴリ -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">カテゴリ</label>
                        <select name="category" class="admin-select">
                            <option value="">すべて</option>
                            @foreach(App\Enums\ItemCategory::cases() as $category)
                            <option value="{{ $category->value }}" {{ ($filters['category'] ?? '') === $category->value ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 価格範囲（最小） -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">最小価格</label>
                        <input type="number" name="min_value" value="{{ $filters['min_value'] ?? '' }}" 
                               placeholder="0" class="admin-input" min="0">
                    </div>

                    <!-- 価格範囲（最大） -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">最大価格</label>
                        <input type="number" name="max_value" value="{{ $filters['max_value'] ?? '' }}" 
                               placeholder="999999" class="admin-input" min="0">
                    </div>

                    <!-- 武器タイプ -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">武器タイプ</label>
                        <select name="weapon_type" class="admin-select">
                            <option value="">すべて</option>
                            <option value="physical" {{ ($filters['weapon_type'] ?? '') === 'physical' ? 'selected' : '' }}>物理武器</option>
                            <option value="magical" {{ ($filters['weapon_type'] ?? '') === 'magical' ? 'selected' : '' }}>魔法武器</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        🔍 検索
                    </button>
                    <a href="{{ route('admin.items.index') }}" class="admin-btn admin-btn-secondary">
                        🔄 リセット
                    </a>
                    @if(auth()->user()->can('items.create'))
                    <a href="{{ route('admin.items.create') }}" class="admin-btn admin-btn-success">
                        ➕ 新規作成
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- アイテム一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">アイテム一覧 ({{ $pagination['total'] ?? $items->count() }}件)</h3>
            <div style="display: flex; gap: 0.5rem;">
                <!-- ソート -->
                <select onchange="updateSort(this.value)" class="admin-select" style="width: auto;">
                    <option value="id-desc" {{ $sortBy === 'id' && $sortDirection === 'desc' ? 'selected' : '' }}>ID降順</option>
                    <option value="name-asc" {{ $sortBy === 'name' && $sortDirection === 'asc' ? 'selected' : '' }}>名前昇順</option>
                    <option value="category-asc" {{ $sortBy === 'category' && $sortDirection === 'asc' ? 'selected' : '' }}>カテゴリ昇順</option>
                    <option value="value-desc" {{ $sortBy === 'value' && $sortDirection === 'desc' ? 'selected' : '' }}>価格降順</option>
                    <option value="value-asc" {{ $sortBy === 'value' && $sortDirection === 'asc' ? 'selected' : '' }}>価格昇順</option>
                </select>

                @if(auth()->user()->can('items.edit'))
                <!-- 一括操作 -->
                <button type="button" onclick="toggleBulkActions()" class="admin-btn admin-btn-secondary" id="bulk-toggle">
                    ☑️ 一括操作
                </button>
                @endif
            </div>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            @if(auth()->user()->can('items.edit'))
            <!-- 一括操作パネル -->
            <div id="bulk-actions" style="display: none; padding: 1rem; background: #f9fafb; border-bottom: 1px solid var(--admin-border);">
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <span id="selected-count">0件選択</span>
                    <button type="button" onclick="selectAll()" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.75rem;">全選択</button>
                    <button type="button" onclick="deselectAll()" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.75rem;">選択解除</button>
                    
                    <div style="border-left: 1px solid var(--admin-border); height: 2rem; margin: 0 0.5rem;"></div>
                    
                    <button type="button" onclick="showBulkPriceModal()" class="admin-btn admin-btn-info" style="padding: 0.25rem 0.75rem;">💰 価格調整</button>
                    <button type="button" onclick="performBulkAction('duplicate')" class="admin-btn admin-btn-warning" style="padding: 0.25rem 0.75rem;">📋 複製</button>
                    
                    @if(auth()->user()->can('items.delete'))
                    <button type="button" onclick="performBulkAction('delete')" class="admin-btn admin-btn-danger" style="padding: 0.25rem 0.75rem;">🗑️ 削除</button>
                    @endif
                </div>
            </div>
            @endif

            <!-- テーブル -->
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            @if(auth()->user()->can('items.edit'))
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all-checkbox" style="display: none;">
                            </th>
                            @endif
                            <th>アイテム情報</th>
                            <th>カテゴリ</th>
                            <th>エフェクト</th>
                            <th>価格</th>
                            <th>耐久度</th>
                            <th style="width: 150px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            @if(auth()->user()->can('items.edit'))
                            <td>
                                <input type="checkbox" class="item-checkbox" value="{{ $item['id'] }}" style="display: none;">
                            </td>
                            @endif
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--admin-primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                                        {{ $item['emoji'] ?? '📦' }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;">{{ $item['name'] }}</div>
                                        @if($item['description'])
                                        <div style="font-size: 0.875rem; color: var(--admin-secondary); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $item['description'] }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-info">
                                    {{ $item['category'] }}
                                </span>
                                @if($item['weapon_type'])
                                    <div style="margin-top: 0.25rem;">
                                        <span class="admin-badge admin-badge-secondary" style="font-size: 0.75rem;">
                                            {{ $item['weapon_type'] === 'physical' ? '物理' : '魔法' }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($item['effects'] && count($item['effects']) > 0)
                                    <div style="font-size: 0.875rem;">
                                        @foreach(array_slice($item['effects'], 0, 3) as $effect => $value)
                                        <div style="margin-bottom: 0.25rem;">
                                            <strong>{{ $effect }}:</strong> +{{ $value }}
                                        </div>
                                        @endforeach
                                        @if(count($item['effects']) > 3)
                                        <div style="color: var(--admin-secondary); font-size: 0.75rem;">
                                            他{{ count($item['effects']) - 3 }}件
                                        </div>
                                        @endif
                                    </div>
                                @else
                                    <span style="color: var(--admin-secondary);">なし</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    <div><strong>{{ number_format($item['value']) }}G</strong></div>
                                    @if($item['sell_price'])
                                    <div style="color: var(--admin-secondary);">売却: {{ number_format($item['sell_price']) }}G</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($item['max_durability'])
                                    <div style="font-size: 0.875rem;">
                                        <strong>{{ $item['max_durability'] }}</strong>
                                    </div>
                                @else
                                    <span style="color: var(--admin-secondary);">-</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="{{ route('admin.items.show', $item['id']) }}" class="admin-btn admin-btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        詳細
                                    </a>
                                    @if(auth()->user()->can('items.edit'))
                                    <a href="{{ route('admin.items.edit', $item['id']) }}" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        編集
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('items.edit') ? '7' : '6' }}" style="text-align: center; padding: 3rem; color: var(--admin-secondary);">
                                条件に一致するアイテムが見つかりませんでした
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ページネーション -->
    @if($pagination['last_page'] > 1)
    <div style="margin-top: 2rem;">
        <nav style="display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
            @if($pagination['current_page'] > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}" 
                   class="admin-btn admin-btn-secondary" style="padding: 0.5rem 0.75rem;">前へ</a>
            @endif
            
            @for($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['last_page'], $pagination['current_page'] + 2); $i++)
                @if($i == $pagination['current_page'])
                    <span class="admin-btn admin-btn-primary" style="padding: 0.5rem 0.75rem;">{{ $i }}</span>
                @else
                    <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" 
                       class="admin-btn admin-btn-secondary" style="padding: 0.5rem 0.75rem;">{{ $i }}</a>
                @endif
            @endfor
            
            @if($pagination['current_page'] < $pagination['last_page'])
                <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}" 
                   class="admin-btn admin-btn-secondary" style="padding: 0.5rem 0.75rem;">次へ</a>
            @endif
        </nav>
    </div>
    @endif
</div>

@if(auth()->user()->can('items.edit'))
<!-- 一括価格調整モーダル -->
<div id="bulk-price-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 2rem; width: 90%; max-width: 500px;">
        <h3 style="margin-bottom: 1.5rem;">一括価格調整</h3>
        <form id="bulk-price-form">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">調整方法</label>
                <select name="price_type" class="admin-select">
                    <option value="multiply">倍率で調整</option>
                    <option value="add">固定値で加算</option>
                    <option value="set">固定価格に設定</option>
                </select>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">調整値</label>
                <input type="number" name="price_value" class="admin-input" placeholder="例: 1.2, 100, 500" step="0.01" min="0" required>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: end;">
                <button type="button" onclick="hideBulkPriceModal()" class="admin-btn admin-btn-secondary">
                    キャンセル
                </button>
                <button type="submit" class="admin-btn admin-btn-primary">
                    実行
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
// ソート変更
function updateSort(value) {
    const [sortBy, sortDirection] = value.split('-');
    const url = new URL(window.location);
    url.searchParams.set('sort_by', sortBy);
    url.searchParams.set('sort_direction', sortDirection);
    window.location.href = url.toString();
}

@if(auth()->user()->can('items.edit'))
// 一括操作の表示切り替え
function toggleBulkActions() {
    const panel = document.getElementById('bulk-actions');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        checkboxes.forEach(cb => cb.style.display = 'block');
        selectAllCheckbox.style.display = 'block';
        document.getElementById('bulk-toggle').textContent = '❌ 一括操作';
    } else {
        panel.style.display = 'none';
        checkboxes.forEach(cb => {
            cb.style.display = 'none';
            cb.checked = false;
        });
        selectAllCheckbox.style.display = 'none';
        selectAllCheckbox.checked = false;
        document.getElementById('bulk-toggle').textContent = '☑️ 一括操作';
        updateSelectedCount();
    }
}

// 選択数の更新
function updateSelectedCount() {
    const count = document.querySelectorAll('.item-checkbox:checked').length;
    document.getElementById('selected-count').textContent = `${count}件選択`;
}

// 全選択・選択解除
function selectAll() {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('select-all-checkbox').checked = true;
    updateSelectedCount();
}

function deselectAll() {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('select-all-checkbox').checked = false;
    updateSelectedCount();
}

// 一括操作の実行
function performBulkAction(action) {
    const selectedIds = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('操作対象のアイテムを選択してください。');
        return;
    }
    
    let confirmMessage = '';
    switch (action) {
        case 'duplicate':
            confirmMessage = `選択した${selectedIds.length}件のアイテムを複製しますか？`;
            break;
        case 'delete':
            confirmMessage = `選択した${selectedIds.length}件のアイテムを削除しますか？\n※この操作は取り消せません。`;
            break;
    }
    
    if (!confirm(confirmMessage)) return;
    
    submitBulkAction(action, selectedIds);
}

// 価格調整モーダル
function showBulkPriceModal() {
    const selectedIds = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) {
        alert('操作対象のアイテムを選択してください。');
        return;
    }
    document.getElementById('bulk-price-modal').style.display = 'block';
}

function hideBulkPriceModal() {
    document.getElementById('bulk-price-modal').style.display = 'none';
    document.getElementById('bulk-price-form').reset();
}

// 一括操作フォーム送信
function submitBulkAction(action, selectedIds, extraData = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.items.bulk_action") }}';
    
    // CSRFトークン
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    // アクション
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);
    
    // アイテムID
    selectedIds.forEach(id => {
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'item_ids[]';
        idInput.value = id;
        form.appendChild(idInput);
    });
    
    // 追加データ
    Object.entries(extraData).forEach(([key, value]) => {
        const extraInput = document.createElement('input');
        extraInput.type = 'hidden';
        extraInput.name = key;
        extraInput.value = value;
        form.appendChild(extraInput);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// 価格調整フォーム送信
document.getElementById('bulk-price-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const selectedIds = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
    
    const extraData = {
        price_type: formData.get('price_type'),
        price_value: formData.get('price_value')
    };
    
    hideBulkPriceModal();
    submitBulkAction('update_prices', selectedIds, extraData);
});

// チェックボックス変更の監視
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-checkbox') || e.target.id === 'select-all-checkbox') {
        if (e.target.id === 'select-all-checkbox') {
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = e.target.checked);
        } else {
            const allCheckboxes = document.querySelectorAll('.item-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
            document.getElementById('select-all-checkbox').checked = allCheckboxes.length === checkedCheckboxes.length;
        }
        updateSelectedCount();
    }
});

// モーダル外クリックで閉じる
document.getElementById('bulk-price-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideBulkPriceModal();
    }
});
@endif
</script>
@endsection