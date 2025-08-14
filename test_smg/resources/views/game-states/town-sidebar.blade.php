{{-- Town State - Left Area: Merged Right + Left Content --}}

{{-- === RIGHT BAR CONTENT (First) === --}}

{{-- Movement Options --}}
<div class="movement-section">
    <h3>移動先選択</h3>
    
    {{-- Actual Town Connections from LocationService --}}
    <div class="connection-options">
        @if(isset($townConnections) && !empty($townConnections))
            @foreach($townConnections as $direction => $connection)
                @php
                    $directionIcons = [
                        'north' => '⬆️',
                        'south' => '⬇️', 
                        'east' => '➡️',
                        'west' => '⬅️'
                    ];
                    $icon = $directionIcons[$direction] ?? '🚪';
                @endphp
                <button 
                    class="connection-btn"
                    onclick="moveToDirection('{{ $direction }}')"
                    title="{{ $connection['name'] ?? 'Unknown destination' }}"
                    data-direction="{{ $direction }}"
                >
                    <span class="direction-icon">{{ $icon }}</span>
                    <div class="direction-info">
                        <span class="direction-label">{{ $connection['direction_label'] ?? ucfirst($direction) }}</span>
                        <span class="destination-name">{{ $connection['name'] ?? 'Unknown' }}</span>
                    </div>
                </button>
            @endforeach
        @else
            <div class="no-connections">
                <p class="help-text">
                    <span class="help-icon">🚫</span>
                    この町からは移動できません
                </p>
            </div>
        @endif
    </div>

    <div class="movement-help">
        <p class="help-text">
            <span class="help-icon">💡</span>
            道を選択して冒険に出発しましょう！
        </p>
    </div>
</div>



{{-- === SEPARATOR === --}}
<hr class="content-separator">

{{-- === LEFT BAR CONTENT (Second) === --}}


{{-- Town Facilities --}}
<div class="town-facilities">
    <h4>町の施設</h4>
    @php
        // 実装済みショップを取得
        $townShops = \App\Models\Shop::getShopsByLocation($player->location_id ?? 'town_a', 'town');
    @endphp

    <div class="facility-list">
        @if($townShops->count() > 0)
            @foreach($townShops as $shop)
                @php
                    $shopType = \App\Enums\ShopType::from($shop->shop_type);
                    $routeName = match($shopType) {
                        \App\Enums\ShopType::ITEM_SHOP => 'shops.item.index',
                        \App\Enums\ShopType::BLACKSMITH => 'shops.blacksmith.index',
                        \App\Enums\ShopType::TAVERN => 'shops.tavern.index',
                        \App\Enums\ShopType::ALCHEMY_SHOP => 'shops.alchemy.index',
                        default => null
                    };
                @endphp
                
                @if($routeName && \Route::has($routeName))
                    <div class="facility-item" title="{{ $shop->description ?? $shopType->getDescription() }}">
                        <a href="{{ route($routeName) }}" class="facility-link">
                            <span class="facility-icon">{{ $shopType->getIcon() }}</span>
                            <span class="facility-name">{{ $shop->name }}</span>
                        </a>
                    </div>
                @endif
            @endforeach
        @else
            {{-- フォールバック表示 --}}
            <div class="facility-item">
                <a href="#" class="facility-link" onclick="gameManager.showNotification('この町には店がありません', 'info')">
                    <span class="facility-icon">🏪</span>
                    <span class="facility-name">施設なし</span>
                </a>
            </div>
        @endif
    </div>
</div>

