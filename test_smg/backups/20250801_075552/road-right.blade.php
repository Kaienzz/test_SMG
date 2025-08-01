{{-- Road State - Right Area: Movement Controls and Actions --}}

{{-- Movement Controls --}}
<div class="movement-controls-section">
    <h3>移動制御</h3>
    
    <div class="movement-controls hidden" id="movement-controls">
        <button class="btn btn-warning movement-btn" id="move-left" onclick="move('left')" data-direction="left">
            <span class="btn-icon">⬅️</span>
            <span class="btn-text">左に移動</span>
        </button>
        <button class="btn btn-warning movement-btn" id="move-right" onclick="move('right')" data-direction="right">
            <span class="btn-icon">➡️</span>
            <span class="btn-text">右に移動</span>
        </button>
    </div>

    {{-- Next Location --}}
    <div class="next-location hidden" id="next-location-info">
        <div class="next-location-header">
            <h4>次の場所</h4>
            <p class="destination-name">{{ $nextLocation->name ?? 'セカンダ町' }}</p>
        </div>
        <button class="btn btn-success btn-large" id="move-to-next" onclick="moveToNext()">
            <span class="btn-icon">🚀</span>
            {{ $nextLocation->name ?? 'セカンダ町' }}に移動
        </button>
    </div>

    {{-- Movement Status --}}
    <div class="movement-status">
        <div class="status-item">
            <span class="status-label">移動可能歩数:</span>
            <span class="status-value" id="available-steps">0</span>
        </div>
        <div class="status-item">
            <span class="status-label">移動方向:</span>
            <span class="status-value" id="movement-direction">待機中</span>
        </div>
    </div>
</div>

{{-- Road Actions --}}
<div class="road-actions-section">
    <h3>道での行動</h3>
    
    <div class="action-buttons">
        <button class="btn btn-success action-btn" onclick="performGathering()" id="gathering-btn">
            <span class="btn-icon">🌿</span>
            <span class="btn-text">採集する</span>
        </button>
        <button class="btn btn-info action-btn" onclick="showGatheringInfo()" id="gathering-info-btn">
            <span class="btn-icon">📊</span>
            <span class="btn-text">採集情報</span>
        </button>
        <button class="btn btn-secondary action-btn" onclick="takeRest()" id="rest-btn">
            <span class="btn-icon">💤</span>
            <span class="btn-text">休憩する</span>
        </button>
        <button class="btn btn-warning action-btn" onclick="lookAround()" id="scout-btn">
            <span class="btn-icon">🔍</span>
            <span class="btn-text">周囲を調べる</span>
        </button>
    </div>

    {{-- Action Results --}}
    <div class="action-results hidden" id="action-results">
        <div class="result-content"></div>
    </div>
</div>

{{-- Emergency Actions --}}
<div class="emergency-section">
    <h4>緊急時</h4>
    <div class="emergency-buttons">
        <button class="btn btn-danger btn-sm" onclick="returnToTown()" title="最寄りの町に戻ります">
            <span class="btn-icon">🏃</span>
            町に戻る
        </button>
        <button class="btn btn-secondary btn-sm" onclick="callForHelp()" title="助けを呼びます">
            <span class="btn-icon">📢</span>
            助けを呼ぶ
        </button>
    </div>
</div>