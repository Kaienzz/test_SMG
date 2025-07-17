@extends('shops.base.index')

@section('shop-content')
<!-- タブナビゲーション -->
<div class="bg-white rounded-t-lg shadow-lg">
    <div class="flex border-b">
        <button type="button" 
                class="tab-btn flex-1 px-6 py-3 text-center font-bold border-b-2 border-blue-500 text-blue-600 bg-blue-50"
                onclick="switchTab('purchase')">
            購入
        </button>
        <button type="button" 
                class="tab-btn flex-1 px-6 py-3 text-center font-bold border-b-2 border-transparent text-gray-600 hover:text-gray-800"
                onclick="switchTab('sell')">
            売却
        </button>
    </div>
</div>

<!-- 購入タブ -->
<div id="purchase-tab" class="tab-content bg-white rounded-b-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">販売アイテム</h2>
    
    @if(count($shopData['services']['items']) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($shopData['services']['items'] as $shopItem)
                <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800">{{ $shopItem['item']['name'] }}</h3>
                            <p class="text-sm text-gray-600">{{ $shopItem['item']['description'] }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-blue-600">{{ $shopItem['price'] }}G</div>
                            @if($shopItem['stock'] !== -1)
                                <div class="text-sm text-gray-500">
                                    在庫: {{ $shopItem['stock'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- アイテム効果表示 -->
                    @if($shopItem['effects'])
                        <div class="mb-3">
                            <div class="text-xs text-gray-500 mb-1">効果:</div>
                            <div class="text-sm text-green-600">
                                @foreach($shopItem['effects'] as $effect => $value)
                                    @if($effect === 'heal_hp')
                                        HP回復 +{{ $value }}
                                    @elseif($effect === 'heal_sp')
                                        SP回復 +{{ $value }}
                                    @elseif($effect === 'heal_mp')
                                        MP回復 +{{ $value }}
                                    @else
                                        {{ $effect }} +{{ $value }}
                                    @endif
                                    @if(!$loop->last), @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 購入ボタンとカンター -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button type="button" 
                                    class="quantity-btn bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded text-sm font-bold"
                                    onclick="changeQuantity({{ $shopItem['id'] }}, -1)">
                                -
                            </button>
                            <input type="number" 
                                   id="quantity-{{ $shopItem['id'] }}" 
                                   value="1" 
                                   min="1" 
                                   max="99" 
                                   class="w-16 text-center border border-gray-300 rounded px-2 py-1 text-sm">
                            <button type="button" 
                                    class="quantity-btn bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded text-sm font-bold"
                                    onclick="changeQuantity({{ $shopItem['id'] }}, 1)">
                                +
                            </button>
                        </div>
                        
                        <button type="button" 
                                class="purchase-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                                onclick="purchaseItem({{ $shopItem['id'] }})"
                                @if(!$shopItem['is_in_stock']) disabled @endif>
                            @if($shopItem['is_in_stock'])
                                購入
                            @else
                                売切
                            @endif
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-gray-500">現在販売中のアイテムはありません。</p>
        </div>
    @endif
</div>

<!-- 売却タブ -->
<div id="sell-tab" class="tab-content bg-white rounded-b-lg shadow-lg p-6 hidden">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">アイテム売却</h2>
    
    <div id="inventory-container">
        <div class="text-center py-8">
            <p class="text-gray-500">インベントリを読み込み中...</p>
        </div>
    </div>
</div>
@endsection

@section('shop-scripts')
<script>
let currentTab = 'purchase';
let inventoryData = null;

function switchTab(tabName) {
    // タブボタンの更新
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-blue-500', 'text-blue-600', 'bg-blue-50');
        btn.classList.add('border-transparent', 'text-gray-600');
    });
    
    // タブコンテンツの更新
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // アクティブなタブの設定
    const activeBtn = document.querySelector(`button[onclick="switchTab('${tabName}')"]`);
    activeBtn.classList.add('border-blue-500', 'text-blue-600', 'bg-blue-50');
    activeBtn.classList.remove('border-transparent', 'text-gray-600');
    
    document.getElementById(`${tabName}-tab`).classList.remove('hidden');
    currentTab = tabName;
    
    // 売却タブの場合はインベントリデータを読み込み
    if (tabName === 'sell') {
        loadInventoryData();
    }
}

function loadInventoryData() {
    fetch('/shops/item/inventory', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            inventoryData = data.inventory;
            renderInventory();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('インベントリの読み込みに失敗しました。', 'error');
    });
}

function renderInventory() {
    const container = document.getElementById('inventory-container');
    
    if (!inventoryData || inventoryData.slots.length === 0) {
        container.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">インベントリが空です。</p></div>';
        return;
    }
    
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
    
    inventoryData.slots.forEach(slot => {
        if (!slot.empty && slot.item_info) {
            const item = slot.item_info;
            const sellPrice = item.sell_price;
            
            html += `
                <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800">${item.name}</h3>
                            <p class="text-sm text-gray-600">${item.description}</p>
                            <p class="text-sm text-gray-500">所持: ${slot.quantity}個</p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-orange-600">${sellPrice}G</div>
                            <div class="text-xs text-gray-500">売価 (単価)</div>
                        </div>
                    </div>
                    
                    <!-- 売却ボタンとカンター -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button type="button" 
                                    class="quantity-btn bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded text-sm font-bold"
                                    onclick="changeSellQuantity(${slot.slot_index}, -1)">
                                -
                            </button>
                            <input type="number" 
                                   id="sell-quantity-${slot.slot_index}" 
                                   value="1" 
                                   min="1" 
                                   max="${slot.quantity}" 
                                   class="w-16 text-center border border-gray-300 rounded px-2 py-1 text-sm">
                            <button type="button" 
                                    class="quantity-btn bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded text-sm font-bold"
                                    onclick="changeSellQuantity(${slot.slot_index}, 1)">
                                +
                            </button>
                        </div>
                        
                        <button type="button" 
                                class="sell-btn bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded font-bold"
                                onclick="sellItem(${slot.slot_index})">
                            売却
                        </button>
                    </div>
                </div>
            `;
        }
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function changeSellQuantity(slotIndex, change) {
    const input = document.getElementById(`sell-quantity-${slotIndex}`);
    const currentValue = parseInt(input.value) || 1;
    const maxValue = parseInt(input.max) || 1;
    const newValue = Math.max(1, Math.min(maxValue, currentValue + change));
    input.value = newValue;
}

function sellItem(slotIndex) {
    const quantity = parseInt(document.getElementById(`sell-quantity-${slotIndex}`).value) || 1;
    
    const formData = new FormData();
    formData.append('sell_slot_index', slotIndex);
    formData.append('quantity', quantity);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('/shops/item/transaction', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(`${data.item.name} x${data.item.quantity} を ${data.item.total_price}G で売却しました！`, 'success');
            
            // 所持金更新
            updatePlayerGold(data.character.remaining_gold);
            
            // インベントリ更新
            inventoryData = data.inventory;
            renderInventory();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('売却処理中にエラーが発生しました。', 'error');
    });
}

function changeQuantity(shopItemId, change) {
    const input = document.getElementById(`quantity-${shopItemId}`);
    const currentValue = parseInt(input.value) || 1;
    const newValue = Math.max(1, Math.min(99, currentValue + change));
    input.value = newValue;
}

function purchaseItem(shopItemId) {
    const quantity = parseInt(document.getElementById(`quantity-${shopItemId}`).value) || 1;
    
    const formData = new FormData();
    formData.append('shop_item_id', shopItemId);
    formData.append('quantity', quantity);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('/shops/item/transaction', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(`${data.item.name} x${data.item.quantity} を ${data.item.total_price}G で購入しました！`, 'success');
            
            // 所持金更新
            updatePlayerGold(data.character.remaining_gold);
            
            // 在庫更新（必要に応じて）
            if (data.shop_item.remaining_stock !== -1 && !data.shop_item.is_in_stock) {
                const button = document.querySelector(`button[onclick="purchaseItem(${shopItemId})"]`);
                button.textContent = '売切';
                button.disabled = true;
            }
            
            // 数量を1にリセット
            document.getElementById(`quantity-${shopItemId}`).value = 1;
            
            // 売却タブが表示されていればインベントリを更新
            if (currentTab === 'sell') {
                loadInventoryData();
            }
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('購入処理中にエラーが発生しました。', 'error');
    });
}
</script>
@endsection