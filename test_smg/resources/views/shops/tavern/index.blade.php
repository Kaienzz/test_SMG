@extends('shops.base.index')

@section('shop-content')
<!-- 回復サービス一覧 -->
<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">回復サービス</h2>
    
    <!-- キャラクター状態表示 -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <h3 class="text-lg font-bold text-gray-700 mb-3">現在の状態</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-red-100 rounded-lg p-3">
                <div class="text-sm text-red-600 font-semibold">HP</div>
                <div class="text-xl font-bold text-red-800" id="current-hp">
                    {{ $player->hp ?? 100 }} / {{ $player->max_hp ?? 100 }}
                </div>
                <div class="w-full bg-red-200 rounded-full h-2 mt-2">
                    <div class="bg-red-600 h-2 rounded-full" id="hp-bar" 
                         style="width: {{ ($player->hp ?? 100) / ($player->max_hp ?? 100) * 100 }}%"></div>
                </div>
            </div>
            
            <div class="bg-blue-100 rounded-lg p-3">
                <div class="text-sm text-blue-600 font-semibold">MP</div>
                <div class="text-xl font-bold text-blue-800" id="current-mp">
                    {{ $player->mp ?? 50 }} / {{ $player->max_mp ?? 50 }}
                </div>
                <div class="w-full bg-blue-200 rounded-full h-2 mt-2">
                    <div class="bg-blue-600 h-2 rounded-full" id="mp-bar" 
                         style="width: {{ ($player->mp ?? 50) / ($player->max_mp ?? 50) * 100 }}%"></div>
                </div>
            </div>
            
            <div class="bg-green-100 rounded-lg p-3">
                <div class="text-sm text-green-600 font-semibold">SP</div>
                <div class="text-xl font-bold text-green-800" id="current-sp">
                    {{ $player->sp ?? 100 }} / {{ $player->max_sp ?? 100 }}
                </div>
                <div class="w-full bg-green-200 rounded-full h-2 mt-2">
                    <div class="bg-green-600 h-2 rounded-full" id="sp-bar" 
                         style="width: {{ ($player->sp ?? 100) / ($player->max_sp ?? 100) * 100 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 回復サービス（1列4行レイアウト） -->
    <div class="space-y-4">
        @foreach($shopData['services'] as $serviceKey => $service)
            @if($serviceKey !== 'heal_all')
                <div class="border rounded-lg p-4 hover:shadow-md transition-shadow bg-white">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800">{{ $service['name'] }}</h3>
                            <p class="text-sm text-gray-600">{{ $service['description'] }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-green-600">{{ $service['rate'] }}{{ $service['unit'] }}</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <label class="text-sm text-gray-600">回復量:</label>
                            <input type="number" 
                                   id="amount-{{ $serviceKey }}" 
                                   value="10" 
                                   min="1" 
                                   max="999" 
                                   class="w-20 text-center border border-gray-300 rounded px-2 py-1 text-sm">
                        </div>
                        
                        <button type="button" 
                                class="heal-btn bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded font-bold"
                                onclick="calculateCost('{{ $serviceKey }}', {{ $service['rate'] }})">
                            費用計算
                        </button>
                        
                        <button type="button" 
                                class="heal-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-bold"
                                onclick="healService('{{ $serviceKey }}')">
                            回復
                        </button>
                    </div>
                    
                    <div id="cost-{{ $serviceKey }}" class="mt-2 text-sm text-gray-600"></div>
                </div>
            @endif
        @endforeach
        
        <!-- 全回復サービス -->
        @if(isset($shopData['services']['heal_all']))
            @php
                // 全回復の料金を計算
                $missingHP = ($player->max_hp ?? 100) - ($player->hp ?? 100);
                $missingMP = ($player->max_mp ?? 50) - ($player->mp ?? 50);
                $missingSP = ($player->max_sp ?? 100) - ($player->sp ?? 100);
                $totalCost = ($missingHP * 10) + ($missingMP * 15) + ($missingSP * 5);
            @endphp
            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow bg-white">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">{{ $shopData['services']['heal_all']['name'] }}</h3>
                        <p class="text-sm text-gray-600">{{ $shopData['services']['heal_all']['description'] }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-green-600">{{ $totalCost }}G</div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <label class="text-sm text-gray-600">
                            @if($totalCost == 0)
                                回復の必要はありません
                            @elseif(($player->gold ?? 1000) >= $totalCost)
                                <span class="text-green-600 font-bold">✓ 支払い可能</span>
                            @else
                                <span class="text-red-600 font-bold">✗ お金が不足 ({{ ($player->gold ?? 1000) }}G所持)</span>
                            @endif
                        </label>
                    </div>
                    
                    <button type="button" 
                            class="heal-btn bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded font-bold"
                            onclick="showFullHealDetails()">
                        費用計算
                    </button>
                    
                    <button type="button" 
                            class="heal-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-bold {{ $totalCost == 0 || ($player->gold ?? 1000) < $totalCost ? 'opacity-50 cursor-not-allowed' : '' }}"
                            onclick="healService('heal_all')"
                            @if($totalCost == 0 || ($player->gold ?? 1000) < $totalCost) disabled @endif>
                        全回復
                    </button>
                </div>
                
                <div id="cost-heal_all" class="mt-2 text-sm text-gray-600"></div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('shop-scripts')
<script>
const HP_RATE = 10;
const MP_RATE = 15;
const SP_RATE = 5;

const character = {
    hp: {{ $player->hp ?? 100 }},
    max_hp: {{ $player->max_hp ?? 100 }},
    mp: {{ $player->mp ?? 50 }},
    max_mp: {{ $player->max_mp ?? 50 }},
    sp: {{ $player->sp ?? 100 }},
    max_sp: {{ $player->max_sp ?? 100 }}
};

function calculateCost(serviceType, rate) {
    const amount = parseInt(document.getElementById(`amount-${serviceType}`).value) || 0;
    const cost = amount * rate;
    document.getElementById(`cost-${serviceType}`).textContent = `費用: ${cost}G`;
}

function showFullHealDetails() {
    const missingHP = character.max_hp - character.hp;
    const missingMP = character.max_mp - character.mp;
    const missingSP = character.max_sp - character.sp;
    
    const totalCost = (missingHP * HP_RATE) + (missingMP * MP_RATE) + (missingSP * SP_RATE);
    
    document.getElementById('cost-heal_all').innerHTML = `
        <div>HP回復: ${missingHP}ポイント × ${HP_RATE}G = ${missingHP * HP_RATE}G</div>
        <div>MP回復: ${missingMP}ポイント × ${MP_RATE}G = ${missingMP * MP_RATE}G</div>
        <div>SP回復: ${missingSP}ポイント × ${SP_RATE}G = ${missingSP * SP_RATE}G</div>
        <div class="font-bold text-gray-800 mt-1">合計費用: ${totalCost}G</div>
    `;
}

function reloadPage() {
    // 回復後に料金表示を更新するため、ページをリロード
    setTimeout(() => {
        location.reload();
    }, 1500);
}

function healService(serviceType) {
    const formData = new FormData();
    formData.append('service_type', serviceType);
    
    if (serviceType !== 'heal_all') {
        const amount = parseInt(document.getElementById(`amount-${serviceType}`).value) || 0;
        if (amount <= 0) {
            showMessage('回復量は1以上を指定してください。', 'error');
            return;
        }
        formData.append('amount', amount);
    }
    
    formData.append('_token', '{{ csrf_token() }}');

    fetch('/shops/tavern/transaction', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            
            // 所持金更新
            updatePlayerGold(data.current_gold);
            
            // キャラクター状態更新
            if (data.current_hp !== undefined) {
                character.hp = data.current_hp;
                updateStatusDisplay('hp', character.hp, character.max_hp);
            }
            if (data.current_mp !== undefined) {
                character.mp = data.current_mp;
                updateStatusDisplay('mp', character.mp, character.max_mp);
            }
            if (data.current_sp !== undefined) {
                character.sp = data.current_sp;
                updateStatusDisplay('sp', character.sp, character.max_sp);
            }
            
            // 入力フィールドをリセット
            if (serviceType !== 'heal_all') {
                document.getElementById(`amount-${serviceType}`).value = 10;
                document.getElementById(`cost-${serviceType}`).textContent = '';
            } else {
                // 全回復の場合は料金表示を更新するためページをリロード
                reloadPage();
            }
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('回復処理中にエラーが発生しました。', 'error');
    });
}

function updateStatusDisplay(type, current, max) {
    document.getElementById(`current-${type}`).textContent = `${current} / ${max}`;
    const percentage = (current / max) * 100;
    document.getElementById(`${type}-bar`).style.width = `${percentage}%`;
}
</script>
@endsection