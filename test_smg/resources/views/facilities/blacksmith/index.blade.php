@extends('facilities.base.index')

@section('facility-content')
<!-- サービス一覧 -->
<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">鍛冶屋サービス</h2>
    
    @if(count($facilityData['services']) > 0)
        <div class="space-y-4">
            @foreach($facilityData['services'] as $serviceKey => $service)
                @if($service['available'])
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-bold text-lg text-gray-800">{{ $service['name'] }}</h3>
                                <p class="text-sm text-gray-600">{{ $service['description'] }}</p>
                            </div>
                            <div class="text-right">
                                @if(isset($service['base_cost']))
                                    <div class="text-lg font-bold text-blue-600">基本料金: {{ $service['base_cost'] }}G</div>
                                @elseif(isset($service['cost']))
                                    <div class="text-lg font-bold text-blue-600">{{ $service['cost'] }}G</div>
                                @endif
                            </div>
                        </div>
                        
                        @if($serviceKey === 'repair')
                            <div class="bg-blue-50 rounded-lg p-3 mb-3">
                                <p class="text-sm text-blue-700">
                                    <strong>修理について:</strong> 耐久度が減ったアイテムを最大耐久度まで回復させます。
                                    実際の料金は損失耐久度によって変動します。
                                </p>
                            </div>
                        @elseif($serviceKey === 'enhance')
                            <div class="bg-green-50 rounded-lg p-3 mb-3">
                                <p class="text-sm text-green-700">
                                    <strong>強化について:</strong> 武器・防具の性能を向上させます。
                                    （現在開発中）
                                </p>
                            </div>
                        @elseif($serviceKey === 'dismantle')
                            <div class="bg-orange-50 rounded-lg p-3 mb-3">
                                <p class="text-sm text-orange-700">
                                    <strong>解体について:</strong> 装備を素材アイテムに分解します。
                                    （現在開発中）
                                </p>
                            </div>
                        @endif

                        <div class="text-center">
                            @if($serviceKey === 'repair')
                                <button type="button" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-bold"
                                        onclick="openRepairModal()">
                                    修理する
                                </button>
                            @else
                                <button type="button" 
                                        class="bg-gray-400 text-white px-4 py-2 rounded font-bold cursor-not-allowed" 
                                        disabled>
                                    準備中
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-gray-500">現在利用可能なサービスはありません。</p>
        </div>
    @endif
</div>

<!-- 修理モーダル -->
<div id="repair-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-mx-4 max-h-96 overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">修理するアイテムを選択</h3>
        <div id="repair-items-list">
            <div class="text-center py-4">
                <p class="text-gray-500">読み込み中...</p>
            </div>
        </div>
        <div class="mt-4 text-center">
            <button onclick="closeRepairModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">
                キャンセル
            </button>
        </div>
    </div>
</div>
@endsection

@section('facility-scripts')
<script>
function openRepairModal() {
    document.getElementById('repair-modal').classList.remove('hidden');
    loadRepairableItems();
}

function closeRepairModal() {
    document.getElementById('repair-modal').classList.add('hidden');
}

function loadRepairableItems() {
    // インベントリからダメージを受けているアイテムを取得
    fetch('/facilities/item/inventory', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayRepairableItems(data.inventory);
        } else {
            document.getElementById('repair-items-list').innerHTML = 
                '<div class="text-center text-red-500">インベントリの読み込みに失敗しました。</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('repair-items-list').innerHTML = 
            '<div class="text-center text-red-500">エラーが発生しました。</div>';
    });
}

function displayRepairableItems(inventory) {
    const container = document.getElementById('repair-items-list');
    let repairableItems = [];
    
    if (inventory && inventory.slots) {
        inventory.slots.forEach((slot, index) => {
            if (!slot.empty && slot.item_info) {
                const item = slot.item_info;
                const durability = slot.durability || item.max_durability || 100;
                const maxDurability = item.max_durability || 100;
                
                // 武器・防具で耐久度が減っているアイテムのみ
                if ((item.category === 'weapon' || item.category === 'armor') && 
                    durability < maxDurability) {
                    repairableItems.push({
                        slot: index,
                        item: item,
                        durability: durability,
                        maxDurability: maxDurability,
                        repairCost: Math.ceil(50 * ((maxDurability - durability) / 100))
                    });
                }
            }
        });
    }
    
    if (repairableItems.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-500">修理が必要なアイテムはありません。</div>';
        return;
    }
    
    let html = '<div class="space-y-2">';
    repairableItems.forEach(itemData => {
        html += `
            <div class="border rounded p-3 hover:bg-gray-50">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-semibold">${itemData.item.name}</div>
                        <div class="text-sm text-gray-600">
                            耐久: ${itemData.durability}/${itemData.maxDurability}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-blue-600">${itemData.repairCost}G</div>
                        <button onclick="repairItem(${itemData.slot}, ${itemData.repairCost})" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                            修理
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

function repairItem(slot, cost) {
    if (!confirm(`${cost}Gで修理しますか？`)) {
        return;
    }

    const formData = new FormData();
    formData.append('service_type', 'repair');
    formData.append('item_slot', slot);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('/facilities/blacksmith/transaction', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            updatePlayerGold(data.remaining_gold);
            closeRepairModal();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('修理処理中にエラーが発生しました。', 'error');
    });
}
</script>
@endsection