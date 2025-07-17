@extends('shops.base.index')

@section('shop-content')
<!-- 販売アイテム一覧 -->
<div class="bg-white rounded-lg shadow-lg p-6">
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
@endsection

@section('shop-scripts')
<script>
function changeQuantity(shopItemId, change) {
    const input = document.getElementById(`quantity-${shopItemId}`);
    const currentValue = parseInt(input.value) || 1;
    const newValue = Math.max(1, Math.min(99, currentValue + change));
    input.value = newValue;
}

function purchaseItem(shopItemId) {
    const quantity = parseInt(document.getElementById(`quantity-${shopItemId}`).value) || 1;
    
    const formData = new FormData();
    formData.append('item_id', shopItemId);
    formData.append('quantity', quantity);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('/shops/blacksmith/transaction', {
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