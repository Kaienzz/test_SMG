{{-- Town State - Main Area: Town Information and Events --}}

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