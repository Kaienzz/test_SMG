{{-- Road State - Left Area: Merged Right + Left Content --}}

{{-- === RIGHT BAR CONTENT (First) === --}}

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

{{-- === SEPARATOR === --}}
<hr class="content-separator">

{{-- === LEFT BAR CONTENT (Second) === --}}

{{-- Player Status on Road --}}
<div class="road-player-status">
    <h4>プレイヤー状態</h4>
    <div class="quick-stats">
        <div class="stat-item">
            <span class="stat-label">HP</span>
            <span class="stat-value hp">{{ $player->hp ?? 100 }}/{{ $player->max_hp ?? 100 }}</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">MP</span>
            <span class="stat-value mp">{{ $player->mp ?? 20 }}/{{ $player->max_mp ?? 20 }}</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">SP</span>
            <span class="stat-value sp">{{ $player->sp ?? 30 }}/{{ $player->max_sp ?? 30 }}</span>
        </div>
    </div>
</div>

{{-- Gathering Information (if skill exists) --}}
@php
    $gatheringSkill = null;
    // プレイヤーが採集スキルを持っているかチェック
    if (is_object($player) && method_exists($player, 'getSkill')) {
        $gatheringSkill = $player->getSkill('採集');
    }
@endphp

@if($gatheringSkill)
    <div class="gathering-info">
        <h4>採集可能</h4>
        <div class="gathering-details">
            <div class="skill-info">
                <span class="skill-label">採集スキル</span>
                <span class="skill-level">Lv.{{ $gatheringSkill->level ?? 1 }}</span>
            </div>
            <p class="gathering-note">道中でアイテムを採集できます</p>
        </div>
    </div>
@endif

{{-- Travel Tips --}}
<div class="travel-tips">
    <h4>旅のヒント</h4>
    <div class="tip-list">
        <div class="tip-item">
            <span class="tip-icon">💡</span>
            <p>サイコロの出目が大きいほど早く進めます</p>
        </div>
        <div class="tip-item">
            <span class="tip-icon">⚔️</span>
            <p>道中で魔物に遭遇することがあります</p>
        </div>
        <div class="tip-item">
            <span class="tip-icon">🌟</span>
            <p>採集でアイテムを入手できます</p>
        </div>
    </div>
</div>