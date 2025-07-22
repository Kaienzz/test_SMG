<div class="location-info">
    <h2 id="current-location">{{ $currentLocation->name }}</h2>
    <p id="location-type">{{ $player->current_location_type === 'town' ? '町にいます' : '道を歩いています' }}</p>
    
    @if($player->current_location_type === 'town')
        @php
            $townShops = \App\Models\Shop::getShopsByLocation($player->current_location_id, 'town');
        @endphp
        
        @if($townShops->count() > 0)
            <div class="town-menu">
                <h3>町の施設</h3>
                <div class="town-actions">
                    @foreach($townShops as $shop)
                        @php
                            $shopType = \App\Enums\ShopType::from($shop->shop_type);
                            $routeName = match($shopType) {
                                \App\Enums\ShopType::ITEM_SHOP => 'shops.item.index',
                                \App\Enums\ShopType::BLACKSMITH => 'shops.blacksmith.index',
                                default => null
                            };
                        @endphp
                        
                        @if($routeName && \Route::has($routeName))
                            <a href="{{ route($routeName) }}" class="btn btn-primary" title="{{ $shopType->getDescription() }}">
                                <span class="shop-icon">{{ $shopType->getIcon() }}</span>
                                {{ $shopType->getDisplayName() }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @endif
    
    @if($player->current_location_type === 'road')
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill" style="width: {{ $player->position }}%"></div>
            <div class="progress-text" id="progress-text">{{ $player->position }}/100</div>
        </div>
        
        @php
            $character = $player->getCharacter();
            $gatheringSkill = $character->getSkill('採集');
        @endphp
        
        @if($gatheringSkill)
            <div class="road-actions">
                <h3>道での行動</h3>
                <div class="gathering-section">
                    <button id="gathering-btn" class="btn btn-success" onclick="performGathering()">
                        <span class="icon">🌿</span>
                        採集する
                    </button>
                    <button id="gathering-info-btn" class="btn btn-info" onclick="showGatheringInfo()">
                        <span class="icon">📊</span>
                        採集情報
                    </button>
                </div>
            </div>
        @endif
    @endif
</div>