{{-- Town State Partial View --}}

{{-- Left Area: Town Facilities and Player Status --}}
<div class="left-area-content" data-state="town">
    {{-- Player Quick Status --}}
    <div class="player-quick-status">
        <h3>{{ $player->name ?? 'プレイヤー' }}</h3>
        <div class="quick-stats">
            <div class="stat-item">
                <span class="stat-label">Lv</span>
                <span class="stat-value">{{ $player->level ?? 5 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">HP</span>
                <span class="stat-value hp">{{ $player->hp ?? 85 }}/{{ $player->max_hp ?? 100 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">MP</span>
                <span class="stat-value mp">{{ $player->mp ?? 42 }}/{{ $player->max_mp ?? 50 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">所持金</span>
                <span class="stat-value gold">{{ number_format($player->gold ?? 1250) }}G</span>
            </div>
        </div>
    </div>

    {{-- Town Facilities --}}
    <div class="town-facilities">
        <h4>町の施設</h4>
        @php
            $townShops = [
                ['name' => '道具屋', 'icon' => '🏪', 'route' => '#', 'description' => '回復アイテムや消耗品を販売'],
                ['name' => '鍛冶屋', 'icon' => '⚒️', 'route' => '#', 'description' => '武器や防具の製造・強化'],
                ['name' => '宿屋', 'icon' => '🏨', 'route' => '#', 'description' => 'HP・MPの完全回復'],
                ['name' => '神殿', 'icon' => '⛪', 'route' => '#', 'description' => '状態異常の治療']
            ];
        @endphp

        <div class="facility-list">
            @foreach($townShops as $shop)
                <div class="facility-item" title="{{ $shop['description'] }}">
                    <a href="{{ $shop['route'] }}" class="facility-link">
                        <span class="facility-icon">{{ $shop['icon'] }}</span>
                        <span class="facility-name">{{ $shop['name'] }}</span>
                    </a>
                </div>
            @endforeach
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
</div>

{{-- Main Area: Town Information and Events --}}
<div class="main-area-content" data-state="town">
    <div class="town-welcome">
        <div class="location-header">
            <h2>🏘️ {{ $currentLocation->name ?? 'プリマ町' }}</h2>
            <p class="location-type">町にいます</p>
        </div>

        <div class="town-description">
            <p>{{ $currentLocation->description ?? 'プリマ町は冒険者たちの拠点となる平和な町です。様々な施設で冒険の準備を整えることができます。' }}</p>
        </div>

        {{-- Town Events/News --}}
        <div class="town-events">
            <h3>町の情報</h3>
            <div class="event-list">
                <div class="event-item">
                    <span class="event-icon">📢</span>
                    <div class="event-content">
                        <h4>新しい冒険者募集</h4>
                        <p>近くの森で魔物の目撃情報が増えています。冒険者の方は注意してください。</p>
                    </div>
                </div>
                <div class="event-item">
                    <span class="event-icon">⚡</span>
                    <div class="event-content">
                        <h4>特別セール開催中</h4>
                        <p>鍛冶屋では今週限定で武器強化が20%割引となっています。</p>
                    </div>
                </div>
                <div class="event-item">
                    <span class="event-icon">🎯</span>
                    <div class="event-content">
                        <h4>クエスト掲示板</h4>
                        <p>新しい依頼が追加されました。報酬は経験値とゴールドです。</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rest Area --}}
        <div class="rest-area">
            <h3>休憩エリア</h3>
            <p>町では時間の経過とともに少しずつHP・MPが回復します。</p>
            <div class="rest-actions">
                <button class="btn btn-success btn-sm" onclick="shortRest()">
                    <span class="btn-icon">💤</span>
                    少し休憩 (HP+5)
                </button>
                <button class="btn btn-info btn-sm" onclick="meditation()">
                    <span class="btn-icon">🧘</span>
                    瞑想 (MP+3)
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Right Area: Movement Options --}}
<div class="right-area-content" data-state="town">
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
</div>