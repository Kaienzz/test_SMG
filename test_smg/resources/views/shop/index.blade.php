@extends('layouts.app')

@section('title', $shop->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- ショップヘッダー -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">{{ $shop->name }}</h1>
                    <p class="text-gray-600 mt-2">{{ $shop->description }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        場所: {{ $currentLocation['name'] }}
                    </p>
                </div>
                <div class="bg-green-100 rounded-lg p-4">
                    <div class="text-sm text-gray-600">所持金</div>
                    <div class="text-2xl font-bold text-green-600" id="player-gold">{{ $character->gold ?? 1000 }}G</div>
                </div>
            </div>
        </div>

        <!-- 販売アイテム一覧 -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">販売アイテム</h2>
            
            @if($shopItems->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($shopItems as $shopItem)
                        <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800">{{ $shopItem->item->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $shopItem->item->description }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-600">{{ $shopItem->price }}G</div>
                                    @if($shopItem->stock !== -1)
                                        <div class="text-sm text-gray-500">
                                            在庫: {{ $shopItem->stock }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- アイテム効果表示 -->
                            @if($shopItem->item->effects)
                                <div class="mb-3">
                                    <div class="text-xs text-gray-500 mb-1">効果:</div>
                                    @php
                                        $effects = json_decode($shopItem->item->effects, true);
                                    @endphp
                                    <div class="text-sm text-green-600">
                                        @foreach($effects as $effect => $value)
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
                                            onclick="changeQuantity({{ $shopItem->id }}, -1)">
                                        -
                                    </button>
                                    <input type="number" 
                                           id="quantity-{{ $shopItem->id }}" 
                                           value="1" 
                                           min="1" 
                                           max="99" 
                                           class="w-16 text-center border border-gray-300 rounded px-2 py-1 text-sm">
                                    <button type="button" 
                                            class="quantity-btn bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded text-sm font-bold"
                                            onclick="changeQuantity({{ $shopItem->id }}, 1)">
                                        +
                                    </button>
                                </div>
                                
                                <button type="button" 
                                        class="purchase-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                                        onclick="purchaseItem({{ $shopItem->id }})"
                                        @if(!$shopItem->isInStock()) disabled @endif>
                                    @if($shopItem->isInStock())
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

        <!-- 戻るボタン -->
        <div class="mt-6 text-center">
            <a href="/game" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-bold inline-block">
                町に戻る
            </a>
        </div>
    </div>
</div>

<!-- メッセージモーダル -->
<div id="message-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-mx-4">
        <div id="message-content" class="mb-4"></div>
        <div class="text-center">
            <button id="close-modal" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                OK
            </button>
        </div>
    </div>
</div>

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
    formData.append('shop_item_id', shopItemId);
    formData.append('quantity', quantity);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('/shop/purchase', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功時の処理
            showMessage(`${data.item.name} x${data.item.quantity} を ${data.item.total_price}G で購入しました！`, 'success');
            
            // 所持金更新
            document.getElementById('player-gold').textContent = `${data.character.remaining_gold}G`;
            
            // 在庫更新（必要に応じて）
            if (data.shop_item.remaining_stock !== -1 && !data.shop_item.is_in_stock) {
                const button = document.querySelector(`button[onclick="purchaseItem(${shopItemId})"]`);
                button.textContent = '売切';
                button.disabled = true;
            }
            
            // 数量を1にリセット
            document.getElementById(`quantity-${shopItemId}`).value = 1;
        } else {
            // エラー時の処理
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('購入処理中にエラーが発生しました。', 'error');
    });
}

function showMessage(message, type = 'info') {
    const modal = document.getElementById('message-modal');
    const content = document.getElementById('message-content');
    
    let bgColor = 'bg-blue-100';
    let textColor = 'text-blue-800';
    
    if (type === 'success') {
        bgColor = 'bg-green-100';
        textColor = 'text-green-800';
    } else if (type === 'error') {
        bgColor = 'bg-red-100';
        textColor = 'text-red-800';
    }
    
    content.innerHTML = `<div class="${bgColor} border rounded p-3 ${textColor}">${message}</div>`;
    modal.classList.remove('hidden');
}

document.getElementById('close-modal').addEventListener('click', function() {
    document.getElementById('message-modal').classList.add('hidden');
});

// モーダルの背景クリックで閉じる
document.getElementById('message-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});
</script>
@endsection