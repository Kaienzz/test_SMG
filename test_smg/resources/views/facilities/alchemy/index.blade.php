@extends('facilities.base.index')

@section('facility-content')
<!-- 錬金システム -->
<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">錬金システム</h2>
    
    <!-- 錬金の説明 -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>錬金について:</strong> 公式の武器・防具を素材と組み合わせて、カスタム性能のアイテムを作成できます。
                    ベースアイテムと素材は消費されます。カスタムアイテムは再錬金できません。
                </p>
            </div>
        </div>
    </div>

    <!-- 錬金フォーム -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 左側: アイテム選択 -->
        <div class="space-y-6">
            <!-- ベースアイテム選択 -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-lg font-bold text-gray-700 mb-3">ベースアイテム選択</h3>
                <div class="space-y-2">
                    @if(count($alchemizableItems) > 0)
                        @foreach($alchemizableItems as $itemData)
                            <div class="border rounded-lg p-3 hover:bg-white cursor-pointer base-item" 
                                 data-slot="{{ $itemData['slot'] }}"
                                 data-item='@json($itemData['item'])'>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <input type="radio" name="base_item" value="{{ $itemData['slot'] }}" 
                                               class="mr-3 base-item-radio">
                                        <div>
                                            <div class="font-semibold">{{ $itemData['item']['name'] ?? 'Unknown' }}</div>
                                            <div class="text-sm text-gray-600">{{ $itemData['item']['category_name'] ?? 'Unknown' }}</div>
                                            @if(isset($itemData['item']['durability']))
                                                <div class="text-sm text-blue-600">
                                                    耐久: {{ $itemData['item']['durability'] }}/{{ $itemData['item']['max_durability'] ?? 100 }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        スロット {{ $itemData['slot'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-gray-500 py-4">
                            錬金可能な武器・防具がありません
                        </div>
                    @endif
                </div>
            </div>

            <!-- 素材選択 -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-lg font-bold text-gray-700 mb-3">素材選択 (最大5個)</h3>
                <div class="space-y-2">
                    @if(count($materialItems) > 0)
                        @foreach($materialItems as $itemData)
                            <div class="border rounded-lg p-3 hover:bg-white cursor-pointer material-item" 
                                 data-slot="{{ $itemData['slot'] }}"
                                 data-item='@json($itemData['item'])'>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="materials[]" value="{{ $itemData['slot'] }}" 
                                               class="mr-3 material-checkbox">
                                        <div>
                                            <div class="font-semibold">{{ $itemData['item']['name'] ?? 'Unknown' }}</div>
                                            <div class="text-sm text-gray-600">数量: {{ $itemData['quantity'] }}</div>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        スロット {{ $itemData['slot'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-gray-500 py-4">
                            錬金に使用できる素材がありません
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 右側: プレビューと実行 -->
        <div class="space-y-6">
            <!-- 錬金プレビュー -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-lg font-bold text-gray-700 mb-3">錬金プレビュー</h3>
                <div id="alchemy-preview" class="text-center text-gray-500 py-8">
                    ベースアイテムと素材を選択してください
                </div>
            </div>

            <!-- 素材効果一覧 -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-lg font-bold text-gray-700 mb-3">利用可能な素材効果</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($availableMaterials as $material)
                        <div class="border rounded p-2 text-sm">
                            <div class="font-semibold">{{ $material['item_name'] }}</div>
                            <div class="text-gray-600">
                                @foreach($material['stat_bonuses'] as $stat => $value)
                                    {{ ucfirst($stat) }}: +{{ $value }}
                                    @if(!$loop->last) | @endif
                                @endforeach
                                @if($material['durability_bonus'] != 0)
                                    | 耐久: {{ $material['durability_bonus'] > 0 ? '+' : '' }}{{ $material['durability_bonus'] }}
                                @endif
                            </div>
                            <div class="text-xs text-blue-600">名匠確率: +{{ number_format($material['masterwork_chance_bonus'], 1) }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 錬金実行ボタン -->
            <div class="text-center">
                <button id="preview-btn" type="button" 
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-bold mr-3 disabled:bg-gray-400 disabled:cursor-not-allowed"
                        disabled>
                    効果プレビュー
                </button>
                <button id="alchemy-btn" type="button" 
                        class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-bold disabled:bg-gray-400 disabled:cursor-not-allowed"
                        disabled>
                    錬金実行
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('facility-scripts')
<script>
let selectedBaseItem = null;
let selectedMaterials = [];

// CSRF トークン
const csrfToken = '{{ csrf_token() }}';

// ベースアイテム選択
document.querySelectorAll('.base-item-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        selectedBaseItem = parseInt(this.value);
        updateButtons();
        clearPreview();
    });
});

// 素材選択（最大5個）
document.querySelectorAll('.material-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            if (selectedMaterials.length >= 5) {
                this.checked = false;
                showMessage('素材は最大5個まで選択できます。', 'error');
                return;
            }
            selectedMaterials.push(parseInt(this.value));
        } else {
            selectedMaterials = selectedMaterials.filter(slot => slot !== parseInt(this.value));
        }
        updateButtons();
        clearPreview();
    });
});

function updateButtons() {
    const previewBtn = document.getElementById('preview-btn');
    const alchemyBtn = document.getElementById('alchemy-btn');
    
    const canPerform = selectedBaseItem !== null && selectedMaterials.length > 0;
    
    previewBtn.disabled = !canPerform;
    alchemyBtn.disabled = !canPerform;
}

function clearPreview() {
    document.getElementById('alchemy-preview').innerHTML = 
        '<div class="text-center text-gray-500 py-8">ベースアイテムと素材を選択してください</div>';
}

// プレビュー機能
document.getElementById('preview-btn').addEventListener('click', function() {
    if (selectedBaseItem === null || selectedMaterials.length === 0) {
        showMessage('ベースアイテムと素材を選択してください。', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('base_item_slot', selectedBaseItem);
    selectedMaterials.forEach(slot => {
        formData.append('material_slots[]', slot);
    });
    formData.append('_token', csrfToken);

    fetch('/facilities/alchemy/preview', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPreview(data);
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('プレビュー取得中にエラーが発生しました。', 'error');
    });
});

// 錬金実行
document.getElementById('alchemy-btn').addEventListener('click', function() {
    if (selectedBaseItem === null || selectedMaterials.length === 0) {
        showMessage('ベースアイテムと素材を選択してください。', 'error');
        return;
    }

    if (!confirm('錬金を実行しますか？ベースアイテムと素材は消費されます。')) {
        return;
    }

    const formData = new FormData();
    formData.append('base_item_slot', selectedBaseItem);
    selectedMaterials.forEach(slot => {
        formData.append('material_slots[]', slot);
    });
    formData.append('_token', csrfToken);

    this.disabled = true;
    this.textContent = '錬金中...';

    fetch('/facilities/alchemy/perform', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            displayAlchemyResult(data);
            resetForm();
            // ページをリロードして最新のインベントリ状態を反映
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('錬金処理中にエラーが発生しました。', 'error');
    })
    .finally(() => {
        this.disabled = !updateButtons();
        this.textContent = '錬金実行';
    });
});

function displayPreview(data) {
    const previewDiv = document.getElementById('alchemy-preview');
    const estimatedStats = data.estimated_stats;
    
    let html = `
        <div class="text-left">
            <h4 class="font-bold text-lg mb-3">錬金プレビュー</h4>
            <div class="bg-white border rounded p-3 mb-3">
                <div class="font-semibold text-blue-700">ベースアイテム: ${data.base_item.name}</div>
            </div>
            
            <div class="bg-white border rounded p-3 mb-3">
                <div class="font-semibold text-green-700 mb-2">素材効果合計:</div>
                <div class="text-sm">
    `;
    
    Object.entries(data.material_effects.combined_stats).forEach(([stat, value]) => {
        html += `<div>${stat}: +${value}</div>`;
    });
    
    if (data.material_effects.combined_durability_bonus !== 0) {
        html += `<div>耐久: ${data.material_effects.combined_durability_bonus > 0 ? '+' : ''}${data.material_effects.combined_durability_bonus}</div>`;
    }
    
    html += `</div></div>`;
    
    html += `
            <div class="bg-white border rounded p-3 mb-3">
                <div class="font-semibold text-purple-700 mb-2">予想ステータス範囲:</div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <div class="font-semibold">通常品 (90-110%)</div>
    `;
    
    Object.entries(estimatedStats.normal.min).forEach(([stat, minVal]) => {
        const maxVal = estimatedStats.normal.max[stat];
        html += `<div>${stat}: ${minVal} - ${maxVal}</div>`;
    });
    
    html += `
                    </div>
                    <div>
                        <div class="font-semibold text-yellow-600">名匠品 (120-150%)</div>
    `;
    
    Object.entries(estimatedStats.masterwork.min).forEach(([stat, minVal]) => {
        const maxVal = estimatedStats.masterwork.max[stat];
        html += `<div>${stat}: ${minVal} - ${maxVal}</div>`;
    });
    
    html += `
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-100 border border-yellow-400 rounded p-3">
                <div class="font-semibold text-yellow-800">名匠品確率: ${estimatedStats.masterwork_chance.toFixed(1)}%</div>
            </div>
        </div>
    `;
    
    previewDiv.innerHTML = html;
}

function displayAlchemyResult(data) {
    // 結果をプレビューエリアに表示
    const previewDiv = document.getElementById('alchemy-preview');
    const customItem = data.custom_item;
    
    let html = `
        <div class="text-left">
            <h4 class="font-bold text-lg mb-3 ${data.is_masterwork ? 'text-yellow-600' : 'text-green-600'}">
                ${data.is_masterwork ? '【名匠品】' : ''}錬金成功！
            </h4>
            <div class="bg-white border rounded p-3 mb-3">
                <div class="font-semibold text-lg">${customItem.name}</div>
                <div class="text-sm text-gray-600 mb-2">${customItem.description}</div>
                <div class="text-sm">
                    <div class="font-semibold mb-1">最終ステータス:</div>
    `;
    
    Object.entries(data.final_stats).forEach(([stat, value]) => {
        html += `<div>${stat}: ${value}</div>`;
    });
    
    html += `
                    <div class="mt-2 font-semibold text-blue-600">
                        耐久: ${customItem.durability}/${customItem.max_durability}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    previewDiv.innerHTML = html;
}

function resetForm() {
    // 選択をリセット
    selectedBaseItem = null;
    selectedMaterials = [];
    
    // チェックボックスをリセット
    document.querySelectorAll('.base-item-radio').forEach(radio => {
        radio.checked = false;
    });
    
    document.querySelectorAll('.material-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    updateButtons();
}
</script>
@endsection