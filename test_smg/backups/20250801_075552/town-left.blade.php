{{-- Town State - Left Area: Town Facilities and Player Status --}}

{{-- Player Quick Status --}}
<div class="player-quick-status">
    <h3>{{ $player->name ?? 'プレイヤー' }}</h3>
    <div class="quick-stats">
        <div class="stat-item">
            <span class="stat-label">Lv</span>
            <span class="stat-value">{{ $player->level ?? 1 }}</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">HP</span>
            <span class="stat-value hp">{{ $player->hp ?? 100 }}/{{ $player->max_hp ?? 100 }}</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">MP</span>
            <span class="stat-value mp">{{ $player->mp ?? 20 }}/{{ $player->max_mp ?? 20 }}</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">所持金</span>
            <span class="stat-value gold">{{ number_format($player->gold ?? 1000) }}G</span>
        </div>
    </div>
</div>

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

{{-- Quick Links --}}
<div class="quick-links">
    <h4>クイックアクセス</h4>
    <div class="link-list">
        <a href="/inventory" class="quick-link">
            <span class="link-icon">🎒</span>
            <span class="link-text">インベントリ</span>
        </a>
        <a href="/player" class="quick-link">
            <span class="link-icon">👤</span>
            <span class="link-text">ステータス</span>
        </a>
        <a href="/skills" class="quick-link">
            <span class="link-icon">✨</span>
            <span class="link-text">スキル</span>
        </a>
    </div>
</div>