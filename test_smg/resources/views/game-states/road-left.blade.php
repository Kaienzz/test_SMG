{{-- Road State - Left Area: Player Status and Tips --}}

{{-- Player Status on Road --}}
<div class="road-player-status">
    <h4>プレイヤー状態</h4>
    <p class="status-note">プレイヤーの状態は上部の背景エリアで確認できます</p>
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