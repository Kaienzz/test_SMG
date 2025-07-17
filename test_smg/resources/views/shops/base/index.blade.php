@extends('layouts.app')

@section('title', $shop->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- ショップヘッダー -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                        <span class="text-4xl mr-2">{{ $shopType->getIcon() }}</span>
                        {{ $shop->name }}
                    </h1>
                    <p class="text-gray-600 mt-2">{{ $shop->description }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        場所: {{ $currentLocation['name'] }} | タイプ: {{ $shopType->getDisplayName() }}
                    </p>
                </div>
                <div class="bg-green-100 rounded-lg p-4">
                    <div class="text-sm text-gray-600">所持金</div>
                    <div class="text-2xl font-bold text-green-600" id="player-gold">{{ $character->gold ?? 1000 }}G</div>
                </div>
            </div>
        </div>

        <!-- ショップ固有のコンテンツ -->
        @yield('shop-content')

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

@yield('shop-scripts')

<script>
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

function updatePlayerGold(newAmount) {
    document.getElementById('player-gold').textContent = `${newAmount}G`;
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