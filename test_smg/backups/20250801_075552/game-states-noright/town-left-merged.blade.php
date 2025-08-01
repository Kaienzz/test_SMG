{{-- Town State - Left Area: Merged Right + Left Content --}}

{{-- === RIGHT BAR CONTENT (First) === --}}

{{-- Movement Options --}}
<div class="movement-section">
    <h3>移動先選択</h3>
    
    {{-- Multiple Connections --}}
    @php
        $connections = [
            ['direction' => 'north', 'name' => 'プリマ街道', 'icon' => '⬆️', 'description' => '北の町へ続く街道'],
            ['direction' => 'east', 'name' => '森の街道', 'icon' => '➡️', 'description' => '森を抜ける道'],
            ['direction' => 'south', 'name' => '商業街道', 'icon' => '⬇️', 'description' => '商業都市への道'],
            ['direction' => 'west', 'name' => '山岳街道', 'icon' => '⬅️', 'description' => '山間部への険しい道']
        ];
    @endphp

    <div class="connection-options">
        @foreach($connections as $connection)
            <button 
                class="connection-btn"
                onclick="moveToDirection('{{ $connection['direction'] }}')"
                title="{{ $connection['description'] }}"
                data-direction="{{ $connection['direction'] }}"
            >
                <span class="direction-icon">{{ $connection['icon'] }}</span>
                <div class="direction-info">
                    <span class="direction-label">{{ ucfirst($connection['direction']) }}</span>
                    <span class="destination-name">{{ $connection['name'] }}</span>
                </div>
            </button>
        @endforeach
    </div>

    <div class="movement-help">
        <p class="help-text">
            <span class="help-icon">💡</span>
            道を選択して冒険に出発しましょう！
        </p>
    </div>
</div>

{{-- Quick Actions --}}
<div class="quick-actions-section">
    <h4>クイックアクション</h4>
    <div class="action-buttons">
        <button class="btn btn-warning btn-sm" onclick="openMap()">
            <span class="btn-icon">🗺️</span>
            地図を見る
        </button>
        <button class="btn btn-info btn-sm" onclick="checkWeather()">
            <span class="btn-icon">🌤️</span>
            天気確認
        </button>
        <button class="btn btn-secondary btn-sm" onclick="openSettings()">
            <span class="btn-icon">⚙️</span>
            設定
        </button>
    </div>
</div>

{{-- Emergency Actions --}}
<div class="emergency-section">
    <h4>緊急時</h4>
    <div class="emergency-buttons">
        <button class="btn btn-danger btn-sm" onclick="resetGame()" title="ゲームをリセットします">
            <span class="btn-icon">🔄</span>
            ゲームリセット
        </button>
    </div>
</div>

{{-- === SEPARATOR === --}}
<hr class="content-separator">

{{-- === LEFT BAR CONTENT (Second) === --}}

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