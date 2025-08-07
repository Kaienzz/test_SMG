{{-- Road State Partial View --}}

{{-- Left Area: Location Info and Progress --}}
<div class="left-area-content" data-state="road">
    {{-- Current Position --}}
    <div class="position-info">
        <h3>🛤️ {{ $currentLocation->name ?? 'プリマ街道' }}</h3>
        <p class="location-type">道を歩いています</p>
        
        {{-- Progress Bar --}}
        <div class="road-progress">
            <div class="progress-label">進行状況</div>
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill" style="width: {{ $player->game_position ?? 50 }}%"></div>
                <div class="progress-text" id="progress-text">{{ $player->game_position ?? 50 }}/100</div>
            </div>
            <div class="progress-info">
                <span class="start-point">出発地</span>
                <span class="end-point">目的地</span>
            </div>
        </div>
    </div>

    {{-- Environment Info --}}
    <div class="environment-info">
        <h4>周辺環境</h4>
        <div class="environment-details">
            <div class="env-item">
                <span class="env-icon">🌤️</span>
                <div class="env-info">
                    <span class="env-label">天候</span>
                    <span class="env-value">晴れ</span>
                </div>
            </div>
            <div class="env-item">
                <span class="env-icon">🕐</span>
                <div class="env-info">
                    <span class="env-label">時間帯</span>
                    <span class="env-value">昼</span>
                </div>
            </div>
            <div class="env-item">
                <span class="env-icon">👁️</span>
                <div class="env-info">
                    <span class="env-label">視界</span>
                    <span class="env-value">良好</span>
                </div>
            </div>
            <div class="env-item">
                <span class="env-icon">⚠️</span>
                <div class="env-info">
                    <span class="env-label">危険度</span>
                    <span class="env-value danger-low">低</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Gathering Information --}}
    <div class="gathering-info">
        <h4>採集情報</h4>
        <div class="gathering-details">
            <div class="skill-info">
                <span class="skill-label">採集スキル</span>
                <span class="skill-level">Lv.{{ $player->gathering_level ?? 3 }}</span>
            </div>
            <div class="resources-available">
                <div class="resource-item">
                    <span class="resource-icon">🌿</span>
                    <span class="resource-name">薬草</span>
                    <span class="resource-probability">60%</span>
                </div>
                <div class="resource-item">
                    <span class="resource-icon">🪨</span>
                    <span class="resource-name">鉱石</span>
                    <span class="resource-probability">30%</span>
                </div>
                <div class="resource-item">
                    <span class="resource-icon">🍄</span>
                    <span class="resource-name">キノコ</span>
                    <span class="resource-probability">25%</span>
                </div>
            </div>
        </div>
    </div>

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
</div>

{{-- Main Area: Dice and Movement System --}}
<div class="main-area-content" data-state="road">
    <div class="road-main">
        {{-- Dice Container --}}
        <div class="dice-container">
            <h3>サイコロを振って移動しよう！</h3>
            
            {{-- Movement Information --}}
            <div class="movement-info">
                <h4>移動情報</h4>
                <div class="movement-details">
                    <div class="info-row">
                        <span class="info-label">サイコロ数:</span>
                        <span class="info-value">{{ $movementInfo['total_dice_count'] ?? 2 }}個</span>
                        <span class="info-breakdown">(基本: {{ $movementInfo['base_dice_count'] ?? 1 }}個 + 装備効果: {{ $movementInfo['extra_dice'] ?? 1 }}個)</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">サイコロボーナス:</span>
                        <span class="info-value">+{{ $movementInfo['dice_bonus'] ?? 0 }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">移動倍率:</span>
                        <span class="info-value">{{ $movementInfo['movement_multiplier'] ?? 1.0 }}倍</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">移動範囲:</span>
                        <span class="info-value">{{ $movementInfo['min_possible_movement'] ?? 2 }}〜{{ $movementInfo['max_possible_movement'] ?? 12 }}歩</span>
                    </div>
                </div>
            </div>

            {{-- Dice Controls --}}
            <div class="dice-controls">
                <button class="btn btn-primary btn-large" id="roll-dice">
                    <span class="btn-icon">🎲</span>
                    サイコロを振る
                </button>
                
                <div class="dice-options">
                    <label class="toggle-label">
                        <input type="checkbox" id="dice-display-toggle" checked onchange="toggleDiceDisplay()">
                        <span class="toggle-text">🎲 ダイス表示</span>
                    </label>
                    <label class="toggle-label">
                        <input type="checkbox" id="auto-move-toggle" onchange="toggleAutoMove()">
                        <span class="toggle-text">⚡ 自動移動</span>
                    </label>
                </div>
            </div>

            {{-- Dice Display --}}
            <div class="dice-display hidden" id="dice-result">
                <div class="dice-animation">
                    <div id="all-dice" class="dice-container-visual"></div>
                </div>
            </div>

            {{-- Dice Result --}}
            <div id="dice-total" class="dice-result hidden">
                <div class="result-summary">
                    <div class="result-item">
                        <span class="result-label">基本合計:</span>
                        <span class="result-value" id="base-total">0</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">ボーナス:</span>
                        <span class="result-value" id="bonus">+0</span>
                    </div>
                    <div class="result-item final-result">
                        <span class="result-label">最終移動距離:</span>
                        <span class="result-value" id="final-movement">0</span>
                        <span class="result-unit">歩</span>
                    </div>
                </div>
                <div class="movement-instruction">
                    <p>左右のボタンで移動方向を選択してください</p>
                </div>
            </div>
        </div>

        {{-- Branch Selection --}}
        <div class="branch-selection hidden" id="branch-selection">
            <div class="branch-header">
                <h3>🛤️ 分岐点</h3>
                <p>進む方向を選択してください</p>
            </div>
            
            <div class="branch-options">
                <button class="branch-btn" onclick="selectBranch('straight')" data-direction="straight">
                    <span class="branch-icon">⬆️</span>
                    <div class="branch-info">
                        <span class="branch-label">直進</span>
                        <span class="branch-destination">プリマ町</span>
                    </div>
                </button>
                <button class="branch-btn" onclick="selectBranch('left')" data-direction="left">
                    <span class="branch-icon">⬅️</span>
                    <div class="branch-info">
                        <span class="branch-label">左折</span>
                        <span class="branch-destination">森の道</span>
                    </div>
                </button>
                <button class="branch-btn" onclick="selectBranch('right')" data-direction="right">
                    <span class="branch-icon">➡️</span>
                    <div class="branch-info">
                        <span class="branch-label">右折</span>
                        <span class="branch-destination">山の道</span>
                    </div>
                </button>
            </div>
            
            <div class="branch-help">
                <p>💡 分岐点では通常の移動はできません。上記から進路を選択してください。</p>
            </div>
        </div>

        {{-- Random Events --}}
        <div class="random-events hidden" id="random-events">
            <div class="event-container">
                <h3>✨ イベント発生！</h3>
                <div class="event-content" id="event-content">
                    <!-- Dynamic event content -->
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Right Area: Movement Controls and Actions --}}
<div class="right-area-content" data-state="road">
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
            <button class="btn btn-success btn-large" id="move-to-next">
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
</div>