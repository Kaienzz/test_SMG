{{-- Road State - Main Area: Location Progress and Movement System --}}

<div class="road-main">
    {{-- Current Position and Progress --}}
    <div class="position-info">
    <h3>🛤️ {{ $currentLocation->name ?? '' }}</h3>
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