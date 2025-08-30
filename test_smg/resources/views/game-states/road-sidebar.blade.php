{{-- Road State - Left Area: Merged Right + Left Content --}}

{{-- === RIGHT BAR CONTENT (First) === --}}

{{-- Movement Controls --}}
<div class="movement-controls-section">
    <h3>移動制御</h3>
    
    
    <div class="movement-controls hidden" id="movement-controls">
        <button class="btn btn-warning movement-btn" id="move-north" onclick="move('north')" data-direction="north">
            <span class="btn-icon">⬆️</span>
            <span class="btn-text">北に移動（進む）</span>
        </button>
        <button class="btn btn-warning movement-btn" id="move-south" onclick="move('south')" data-direction="south">
            <span class="btn-icon">⬇️</span>
            <span class="btn-text">南に移動（戻る）</span>
        </button>
    </div>

    {{-- Next Location (only show when at road boundaries) --}}
    @php
        $showNextLocation = false;
        $nextName = null;
        $nextConnId = null;

        // 現在位置
        $pos = (int) ($player->game_position ?? 0);
        $atBoundary = ($pos <= 0) || ($pos === 50) || ($pos >= 100);

        // 1) 優先: コントローラからの nextLocation
        if (isset($nextLocation) && !empty($nextLocation)) {
            $nextName = is_array($nextLocation) ? ($nextLocation['name'] ?? null) : ($nextLocation->name ?? null);
        }

        // 2) Fallback: 利用可能接続から単一の接続を採用
        if (empty($nextName) && isset($availableConnections) && is_array($availableConnections) && count($availableConnections) === 1) {
            $only = $availableConnections[0] ?? null;
            if ($only) {
                // target_location 名称を取得（オブジェクト/配列対応）
                $t = $only['target_location'] ?? null;
                $name = is_array($t) ? ($t['name'] ?? null) : (is_object($t) ? ($t->name ?? null) : null);
                if (!empty($name)) {
                    $nextName = $name;
                    $nextConnId = $only['id'] ?? null;
                }
            }
        }

        // 表示条件: 境界かつ名前が確定
        $showNextLocation = $atBoundary && !empty($nextName);
    @endphp
    
    @if($showNextLocation)
        <div class="next-location" id="next-location-info">
            <div class="next-location-header">
                <h4>次の場所</h4>
                <p class="destination-name">{{ $nextName }}</p>
            </div>
            <button class="btn btn-success btn-large" id="move-to-next" @if($nextConnId) onclick="moveToConnection('{{ $nextConnId }}')" @endif>
                <span class="btn-icon">🚀</span>
                <span class="btn-text">{{ $nextName }}に移動</span>
            </button>
        </div>
    @endif

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


{{-- Environment Actions --}}
@php
    $gatheringSkill = null;
    $currentEnvironment = $currentLocation->category ?? 'road';
    $environmentName = $currentEnvironment === 'dungeon' ? 'ダンジョン' : '道路';
    $environmentIcon = $currentEnvironment === 'dungeon' ? '🏰' : '🛤️';
    
    // プレイヤーが採集スキルを持っているかチェック
    if (is_object($player) && method_exists($player, 'getSkill')) {
        $gatheringSkill = $player->getSkill('採集');
    }
    
    // レベル制限チェック（ダンジョンの場合）
    $levelRequirementMet = true;
    if ($currentEnvironment === 'dungeon' && isset($currentLocation->min_level)) {
        $levelRequirementMet = ($player->level ?? 1) >= $currentLocation->min_level;
    }
@endphp

<div class="environment-actions-section">
    <h3>{{ $environmentName }}での行動</h3>
    
    <div class="action-buttons">
        @if($gatheringSkill)
            <button class="btn btn-success action-btn" onclick="performGathering()" id="gathering-btn">
                <span class="btn-icon">{{ $currentEnvironment === 'dungeon' ? '💎' : '🌿' }}</span>
                <span class="btn-text">採集する</span>
            </button>
            <button class="btn btn-info action-btn" onclick="showGatheringInfo()" id="gathering-info-btn">
                <span class="btn-icon">📊</span>
                <span class="btn-text">採集情報</span>
            </button>
        @else
            <button class="btn btn-secondary action-btn" disabled title="採集スキルが必要です">
                <span class="btn-icon">🚫</span>
                <span class="btn-text">採集不可</span>
            </button>
        @endif
        
        <button class="btn btn-secondary action-btn" onclick="takeRest()" id="rest-btn">
            <span class="btn-icon">💤</span>
            <span class="btn-text">休憩する</span>
        </button>
        
        <button class="btn btn-warning action-btn" onclick="lookAround()" id="scout-btn">
            <span class="btn-icon">{{ $currentEnvironment === 'dungeon' ? '🔦' : '🔍' }}</span>
            <span class="btn-text">{{ $currentEnvironment === 'dungeon' ? '探索する' : '周囲を調べる' }}</span>
        </button>
    </div>

    {{-- Action Results --}}
    <div class="action-results hidden" id="action-results">
        <div class="result-content"></div>
    </div>
    
    {{-- Environment-specific notices --}}
    @if($currentEnvironment === 'dungeon' && !$levelRequirementMet)
        <div class="environment-notice warning">
            <span class="notice-icon">⚠️</span>
            <span class="notice-text">レベル不足のため一部機能が制限されています</span>
        </div>
    @endif
</div>


{{-- === SEPARATOR === --}}
<hr class="content-separator">

{{-- === LEFT BAR CONTENT (Second) === --}}

{{-- Player Status on Road --}}
<div class="road-player-status">
    <h4>プレイヤー状態</h4>
    <p class="status-note">プレイヤーの状態は上部の背景エリアで確認できます</p>
</div>

{{-- Gathering Information (dynamic for Road/Dungeon) --}}

@if($gatheringSkill)
    <div class="gathering-info environment-{{ $currentEnvironment }}">
        <h4>{{ $environmentIcon }} {{ $environmentName }}採集</h4>
        <div class="gathering-details">
            <div class="skill-info">
                <span class="skill-label">採集スキル</span>
                <span class="skill-level">Lv.{{ $gatheringSkill->level ?? 1 }}</span>
            </div>
            
            @if($currentEnvironment === 'dungeon')
                <div class="environment-requirements">
                    @if(isset($currentLocation->min_level))
                        <div class="level-requirement {{ $levelRequirementMet ? 'met' : 'unmet' }}">
                            <span class="req-icon">{{ $levelRequirementMet ? '✅' : '❌' }}</span>
                            <span class="req-text">
                                必要レベル: {{ $currentLocation->min_level }}
                                (現在: {{ $player->level ?? 1 }})
                            </span>
                        </div>
                    @endif
                    
                    @if($levelRequirementMet)
                        <p class="gathering-note success">ダンジョン内でレアアイテムを採集できます</p>
                    @else
                        <p class="gathering-note warning">レベルが足りないため採集できません</p>
                    @endif
                </div>
            @else
                <p class="gathering-note">{{ $environmentName }}でアイテムを採集できます</p>
            @endif
            
            {{-- SP状況表示 --}}
            <div class="sp-status">
                <span class="sp-label">消費SP:</span>
                <span class="sp-cost">{{ $gatheringSkill->getSkillSpCost() ?? 5 }}</span>
                <span class="sp-remaining">(残り: {{ $player->sp ?? 0 }})</span>
            </div>
        </div>
    </div>
@else
    <div class="no-gathering-skill">
        <h4>⚠️ 採集不可</h4>
        <p class="no-skill-note">採集スキルを習得してください</p>
    </div>
@endif

{{-- Travel Tips (environment-specific) --}}
<div class="travel-tips environment-{{ $currentEnvironment }}">
    <h4>{{ $environmentIcon }} {{ $environmentName }}のヒント</h4>
    <div class="tip-list">
        @if($currentEnvironment === 'dungeon')
            <div class="tip-item">
                <span class="tip-icon">🏰</span>
                <p>ダンジョンでは強力な魔物が出現します</p>
            </div>
            <div class="tip-item">
                <span class="tip-icon">💎</span>
                <p>レアアイテムの採集成功率が高めです</p>
            </div>
            <div class="tip-item">
                <span class="tip-icon">⚠️</span>
                <p>レベル制限があるので注意してください</p>
            </div>
            <div class="tip-item">
                <span class="tip-icon">🌟</span>
                <p>採集スキルが高いほど有利です</p>
            </div>
        @else
            <div class="tip-item">
                <span class="tip-icon">💡</span>
                <p>サイコロの出目が大きいほど早く進めます</p>
            </div>
            <div class="tip-item">
                <span class="tip-icon">⚔️</span>
                <p>道中で魔物に遭遇することがあります</p>
            </div>
            <div class="tip-item">
                <span class="tip-icon">🌿</span>
                <p>基本的なアイテムを採集できます</p>
            </div>
            <div class="tip-item">
                <span class="tip-icon">💤</span>
                <p>疲れたら休憩でHPを回復できます</p>
            </div>
        @endif
    </div>
</div>

{{-- Environment-specific styles --}}
<style>
/* Gathering Info Environment Styling */
.gathering-info.environment-dungeon {
    border-left: 4px solid #8B5A3C;
    background: linear-gradient(135deg, #2D1810 0%, #3E2723 100%);
    color: #FFFFFF;
    border-radius: 8px;
    padding: 12px;
}

.gathering-info.environment-road {
    border-left: 4px solid #4CAF50;
    background: linear-gradient(135deg, #F1F8E9 0%, #E8F5E8 100%);
    color: #2E7D32;
    border-radius: 8px;
    padding: 12px;
}

.gathering-info h4 {
    margin-bottom: 10px;
    font-weight: bold;
}

.skill-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    padding: 6px 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.environment-requirements {
    margin: 10px 0;
}

.level-requirement {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    padding: 6px 10px;
    border-radius: 4px;
}

.level-requirement.met {
    background: rgba(76, 175, 80, 0.2);
    color: #2E7D32;
}

.level-requirement.unmet {
    background: rgba(244, 67, 54, 0.2);
    color: #C62828;
}

.gathering-note {
    font-size: 13px;
    margin: 8px 0;
    padding: 6px 8px;
    border-radius: 4px;
}

.gathering-note.success {
    background: rgba(76, 175, 80, 0.2);
    color: #2E7D32;
}

.gathering-note.warning {
    background: rgba(255, 152, 0, 0.2);
    color: #E65100;
}

.sp-status {
    display: flex;
    gap: 8px;
    font-size: 13px;
    margin-top: 8px;
    padding: 6px 8px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.sp-label {
    font-weight: bold;
}

.sp-cost {
    font-weight: bold;
    color: #FF6B35;
}

.sp-remaining {
    color: #666;
}

/* No Gathering Skill Styling */
.no-gathering-skill {
    border-left: 4px solid #FF6B35;
    background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
    color: #E65100;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
}

.no-gathering-skill h4 {
    margin-bottom: 6px;
}

.no-skill-note {
    font-size: 13px;
    margin: 0;
}

/* Travel Tips Environment Styling */
.travel-tips.environment-dungeon {
    background: linear-gradient(135deg, #2D1810 0%, #3E2723 100%);
    color: #FFFFFF;
    border-radius: 8px;
    padding: 12px;
    border: 2px solid #8B5A3C;
}

.travel-tips.environment-road {
    background: linear-gradient(135deg, #F1F8E9 0%, #E8F5E8 100%);
    color: #2E7D32;
    border-radius: 8px;
    padding: 12px;
    border: 2px solid #4CAF50;
}

.travel-tips h4 {
    margin-bottom: 12px;
    font-weight: bold;
}

.tip-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.tip-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 8px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.tip-icon {
    font-size: 18px;
    width: 20px;
    text-align: center;
}

.tip-item p {
    margin: 0;
    font-size: 13px;
    line-height: 1.4;
}

/* Movement Controls Styling */
.movement-controls {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: stretch;
    margin-bottom: 12px;
}

.movement-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    min-height: 44px;
}

.movement-btn .btn-icon {
    font-size: 18px;
}

.movement-btn .btn-text {
    font-weight: 500;
}

/* Environment Actions Styling */
.environment-actions-section h3 {
    color: #2D3748;
    margin-bottom: 12px;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.action-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.action-btn .btn-icon {
    font-size: 16px;
}

.action-btn .btn-text {
    flex: 1;
    text-align: left;
}

/* Environment Notice */
.environment-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 6px;
    font-size: 13px;
    margin-top: 10px;
}

.environment-notice.warning {
    background: rgba(255, 152, 0, 0.2);
    border: 1px solid #FF9800;
    color: #E65100;
}

.notice-icon {
    font-size: 16px;
}

.notice-text {
    flex: 1;
}

</style>