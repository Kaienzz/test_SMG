{{-- Battle State Partial View --}}

{{-- Left Area: Player Status and Stats --}}
<div class="left-area-content" data-state="battle">
    {{-- Player Battle Status --}}
    <div class="player-battle-status">
        <div class="character-header">
            <h3>{{ $character['name'] ?? 'プレイヤー' }}</h3>
            <div class="character-level">Lv.{{ $character['level'] ?? 5 }}</div>
        </div>

        {{-- HP/MP Bars --}}
        <div class="resource-bars">
            <div class="resource-bar hp-bar">
                <div class="resource-label">
                    <span class="resource-name">HP</span>
                    <span class="resource-text" id="character-hp-text">{{ $character['hp'] ?? 100 }}/{{ $character['max_hp'] ?? 100 }}</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill hp" id="character-hp" style="width: {{ (($character['hp'] ?? 100) / ($character['max_hp'] ?? 100)) * 100 }}%"></div>
                </div>
            </div>

            <div class="resource-bar mp-bar">
                <div class="resource-label">
                    <span class="resource-name">MP</span>
                    <span class="resource-text" id="character-mp-text">{{ $character['mp'] ?? 50 }}/{{ $character['max_mp'] ?? 50 }}</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill mp" id="character-mp" style="width: {{ (($character['mp'] ?? 50) / ($character['max_mp'] ?? 50)) * 100 }}%"></div>
                </div>
            </div>

            <div class="resource-bar sp-bar">
                <div class="resource-label">
                    <span class="resource-name">SP</span>
                    <span class="resource-text" id="character-sp-text">{{ $character['sp'] ?? 80 }}/{{ $character['max_sp'] ?? 80 }}</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill sp" id="character-sp" style="width: {{ (($character['sp'] ?? 80) / ($character['max_sp'] ?? 80)) * 100 }}%"></div>
                </div>
            </div>
        </div>

        {{-- Battle Stats --}}
        <div class="battle-stats">
            <h4>戦闘ステータス</h4>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-icon">⚔️</span>
                    <div class="stat-info">
                        <span class="stat-label">攻撃力</span>
                        <span class="stat-value">{{ $character['attack'] ?? 15 }}</span>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">✨</span>
                    <div class="stat-info">
                        <span class="stat-label">魔法攻撃</span>
                        <span class="stat-value">{{ $character['magic_attack'] ?? 12 }}</span>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">🛡️</span>
                    <div class="stat-info">
                        <span class="stat-label">防御力</span>
                        <span class="stat-value">{{ $character['defense'] ?? 12 }}</span>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">💨</span>
                    <div class="stat-info">
                        <span class="stat-label">素早さ</span>
                        <span class="stat-value">{{ $character['agility'] ?? 18 }}</span>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">🎯</span>
                    <div class="stat-info">
                        <span class="stat-label">命中率</span>
                        <span class="stat-value">{{ $character['accuracy'] ?? 85 }}%</span>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">💫</span>
                    <div class="stat-info">
                        <span class="stat-label">回避率</span>
                        <span class="stat-value">{{ $character['evasion'] ?? 15 }}%</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Effects --}}
        <div class="status-effects">
            <h4>状態効果</h4>
            <div class="effects-list" id="player-status-effects">
                <div class="effect-item positive">
                    <span class="effect-icon">💪</span>
                    <div class="effect-info">
                        <span class="effect-name">攻撃力上昇</span>
                        <span class="effect-duration">3ターン</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Equipment Quick View --}}
        <div class="equipment-quick">
            <h4>装備中</h4>
            <div class="equipment-list">
                <div class="equipment-item">
                    <span class="equipment-icon">⚔️</span>
                    <span class="equipment-name">鉄の剣</span>
                </div>
                <div class="equipment-item">
                    <span class="equipment-icon">🛡️</span>
                    <span class="equipment-name">革の鎧</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Area: Battle Field and Enemy Info --}}
<div class="main-area-content" data-state="battle">
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
</div>

{{-- Right Area: Battle Commands and Actions --}}
<div class="right-area-content" data-state="battle">
    {{-- Battle Actions --}}
    <div class="battle-actions-section">
        <h3>戦闘コマンド</h3>
        
        <div class="action-buttons" id="battle-actions">
            <button class="btn btn-danger action-btn" onclick="performAction('attack')" id="attack-button">
                <span class="btn-icon">⚔️</span>
                <span class="btn-text">攻撃</span>
            </button>
            
            <button class="btn btn-primary action-btn" onclick="performAction('defend')">
                <span class="btn-icon">🛡️</span>
                <span class="btn-text">防御</span>
            </button>
            
            <div class="skill-container">
                <button class="btn btn-secondary action-btn" onclick="toggleSkillMenu()" id="skill-button">
                    <span class="btn-icon">✨</span>
                    <span class="btn-text">特技</span>
                </button>
                <div class="skill-menu hidden" id="skill-menu">
                    <!-- Dynamic skills -->
                </div>
            </div>
            
            <button class="btn btn-warning action-btn" onclick="performAction('escape')">
                <span class="btn-icon">🏃</span>
                <div class="btn-content">
                    <span class="btn-text">逃げる</span>
                    <div class="escape-rate" id="escape-rate">成功率: 50%</div>
                </div>
            </button>
        </div>

        {{-- Continue Actions --}}
        <div class="continue-actions hidden" id="continue-actions">
            <button class="btn btn-success btn-large" onclick="returnToGame()" id="return-to-game-btn">
                <span class="btn-icon">🚀</span>
                ゲームに戻る
            </button>
        </div>
    </div>

    {{-- Battle Strategy --}}
    <div class="battle-strategy-section">
        <h4>戦略</h4>
        <div class="strategy-tips">
            <div class="tip-item">
                <span class="tip-icon">💡</span>
                <p>敵の攻撃パターンを観察しよう</p>
            </div>
            <div class="tip-item">
                <span class="tip-icon">⚡</span>
                <p>MPを効率的に使って特技で勝負</p>
            </div>
            <div class="tip-item">
                <span class="tip-icon">🛡️</span>
                <p>HPが低い時は防御で様子見</p>
            </div>
        </div>
    </div>

    {{-- Quick Items --}}
    <div class="quick-items-section">
        <h4>クイックアイテム</h4>
        <div class="quick-item-slots">
            <button class="quick-item-btn" onclick="useQuickItem('potion')" title="回復ポーション">
                <span class="item-icon">🧪</span>
                <span class="item-count">3</span>
            </button>
            <button class="quick-item-btn" onclick="useQuickItem('ether')" title="マナポーション">
                <span class="item-icon">💙</span>
                <span class="item-count">2</span>
            </button>
            <button class="quick-item-btn" onclick="useQuickItem('bomb')" title="爆弾">
                <span class="item-icon">💣</span>
                <span class="item-count">1</span>
            </button>
            <button class="quick-item-btn disabled">
                <span class="item-icon">❌</span>
                <span class="item-count">0</span>
            </button>
        </div>
    </div>

    {{-- Battle Statistics --}}
    <div class="battle-stats-section">
        <h4>戦闘統計</h4>
        <div class="battle-statistics">
            <div class="stat-item">
                <span class="stat-label">与ダメージ:</span>
                <span class="stat-value" id="total-damage-dealt">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">被ダメージ:</span>
                <span class="stat-value" id="total-damage-taken">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">命中回数:</span>
                <span class="stat-value" id="hits-landed">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">回避回数:</span>
                <span class="stat-value" id="attacks-evaded">0</span>
            </div>
        </div>
    </div>

    {{-- Emergency Actions --}}
    <div class="emergency-section">
        <h4>緊急時</h4>
        <div class="emergency-buttons">
            <button class="btn btn-danger btn-sm" onclick="forfeitBattle()" title="戦闘を放棄します">
                <span class="btn-icon">🏳️</span>
                戦闘放棄
            </button>
        </div>
    </div>
</div>