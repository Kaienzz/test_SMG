{{-- Battle State - Main Area: Battle Field and Enemy Info --}}

<div class="battle-main">
    {{-- Battle Header --}}
    <div class="battle-header">
        <h2>⚔️ 戦闘</h2>
        <div class="battle-status">
            <div class="turn-indicator" id="turn-indicator">ターン 1</div>
            <div class="battle-phase" id="battle-phase">プレイヤーターン</div>
        </div>
    </div>

    {{-- Battle Field --}}
    <div class="battle-field">
        {{-- Enemy Display --}}
        <div class="enemy-display">
            <div class="enemy-info">
                <div class="enemy-visual">
                    <div class="enemy-emoji">{{ $monster['emoji'] ?? '👹' }}</div>
                    <div class="enemy-name">{{ $monster['name'] ?? 'Unknown Monster' }}</div>
                </div>
                
                <div class="enemy-hp-container">
                    <div class="enemy-hp-label">
                        <span class="hp-text" id="monster-hp-text">{{ $monster['stats']['hp'] ?? 100 }}/{{ $monster['stats']['max_hp'] ?? 100 }}</span>
                    </div>
                    <div class="progress-bar enemy-hp-bar">
                        <div class="progress-fill monster-hp" id="monster-hp" style="width: {{ (($monster['stats']['hp'] ?? 100) / ($monster['stats']['max_hp'] ?? 100)) * 100 }}%"></div>
                    </div>
                </div>

                <div class="enemy-stats-quick">
                    <div class="enemy-stat">
                        <span class="stat-icon">⚔️</span>
                        <span class="stat-value">{{ $monster['stats']['attack'] ?? 15 }}</span>
                    </div>
                    <div class="enemy-stat">
                        <span class="stat-icon">🛡️</span>
                        <span class="stat-value">{{ $monster['stats']['defense'] ?? 10 }}</span>
                    </div>
                    <div class="enemy-stat">
                        <span class="stat-icon">💨</span>
                        <span class="stat-value">{{ $monster['stats']['agility'] ?? 10 }}</span>
                    </div>
                </div>
            </div>

            {{-- Enemy Status Effects --}}
            <div class="enemy-status-effects" id="enemy-status-effects">
                <!-- Dynamic status effects -->
            </div>
        </div>

        {{-- Battle Animation Area --}}
        <div class="battle-animation-area" id="battle-animation">
            <div class="animation-content">
                <div class="battle-effects" id="battle-effects">
                    <!-- Dynamic battle effects -->
                </div>
            </div>
        </div>
    </div>

    {{-- Enemy Details --}}
    <div class="enemy-details">
        <h3>敵の情報</h3>
        <div class="enemy-description">
            <p>{{ $monster['description'] ?? 'モンスターの説明はありません' }}</p>
        </div>
        
        <div class="enemy-detailed-stats">
            <div class="stats-row">
                <div class="stat-item">
                    <span class="stat-label">攻撃力:</span>
                    <span class="stat-value">{{ $monster['stats']['attack'] ?? 15 }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">防御力:</span>
                    <span class="stat-value">{{ $monster['stats']['defense'] ?? 10 }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">素早さ:</span>
                    <span class="stat-value">{{ $monster['stats']['agility'] ?? 10 }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">回避率:</span>
                    <span class="stat-value">{{ $monster['stats']['evasion'] ?? 10 }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Battle Log --}}
    <div class="battle-log-container">
        <h3>戦闘ログ</h3>
        <div class="battle-log" id="log-container">
            <div class="log-entry initial">{{ $monster['name'] ?? 'Unknown Monster' }}が現れた！</div>
        </div>
    </div>

    {{-- Battle Result --}}
    <div class="battle-result hidden" id="battle-result">
        <div class="result-header">
            <h2 id="result-title"></h2>
        </div>
        <div class="result-content">
            <p id="result-message"></p>
            <div class="result-rewards hidden" id="experience-gained">
                <div class="reward-item">
                    <span class="reward-icon">⭐</span>
                    <span class="reward-text">経験値 <span id="exp-amount">0</span> 獲得！</span>
                </div>
            </div>
            <div class="result-penalties hidden" id="defeat-penalty">
                <div class="penalty-item">
                    <span class="penalty-icon">📍</span>
                    <span class="penalty-text" id="teleport-message"></span>
                </div>
                <div class="penalty-item">
                    <span class="penalty-icon">💰</span>
                    <span class="penalty-text" id="gold-penalty-message"></span>
                </div>
            </div>
        </div>
    </div>
</div>