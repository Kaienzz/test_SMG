// Game Unified JavaScript - Multi-State Game System

class UnifiedGameManager {
    constructor() {
        this.gameData = null;
        this.gameState = 'town';
        this.isTransitioning = false;
        this.battleData = null;
        this.diceResult = null;
        this.availableSteps = 0;
        this.backgroundManager = null;
        
        // Initialize system
        this.initialize();
    }

    initialize() {
        console.log('Initializing Unified Game Manager...');
        
        // Initialize background manager
        this.backgroundManager = new BackgroundManager();
        
        // Set up global event listeners
        this.setupGlobalEventListeners();
        
        // Set up state-specific managers
        this.setupStateManagers();
        
        console.log('Unified Game Manager initialized');
    }

    setupGlobalEventListeners() {
        // Global error handling
        window.addEventListener('error', (e) => {
            this.handleError('JavaScript Error', e.error);
        });

        // Handle AJAX errors
        window.addEventListener('unhandledrejection', (e) => {
            this.handleError('Promise Rejection', e.reason);
        });

        // Prevent double-click issues on buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn') && !e.target.disabled) {
                e.target.style.pointerEvents = 'none';
                setTimeout(() => {
                    e.target.style.pointerEvents = 'auto';
                }, 300);
            }
        });
    }

    setupStateManagers() {
        this.townManager = new TownStateManager(this);
        this.roadManager = new RoadStateManager(this);
        this.battleManager = new BattleStateManager(this);
    }

    // Public API for game initialization
    initializeGame(gameData) {
        console.log('Initializing game with data:', gameData);
        
        this.gameData = gameData;
        this.gameState = gameData.gameState || 'town';
        
        // Initialize appropriate state manager
        switch (this.gameState) {
            case 'town':
                this.townManager.initialize(gameData);
                break;
            case 'road':
                this.roadManager.initialize(gameData);
                break;
            case 'battle':
                this.battleManager.initialize(gameData);
                break;
            default:
                console.warn('Unknown game state:', this.gameState);
        }

        // Update UI based on state
        this.updateGameStateUI();
    }

    updateGameStateUI() {
        const layout = document.querySelector('.game-unified-layout');
        if (layout) {
            layout.setAttribute('data-game-state', this.gameState);
        }

        const stateIndicator = document.querySelector('.game-state-indicator');
        if (stateIndicator) {
            stateIndicator.className = `game-state-indicator ${this.gameState}`;
        }
    }

    // Utility method for AJAX requests
    async makeRequest(url, method = 'POST', data = {}) {
        if (this.isTransitioning) {
            console.log('Request blocked: system is transitioning');
            return null;
        }

        try {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.gameData?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            };

            if (method !== 'GET' && Object.keys(data).length > 0) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(url, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Request failed');
            }

            return result;
        } catch (error) {
            console.error('Request failed:', error);
            this.handleError('Network Error', error);
            return null;
        }
    }

    // Error handling
    handleError(type, error) {
        console.error(`${type}:`, error);
        
        const errorMessage = `${type}: ${error.message || error}`;
        this.showNotification(errorMessage, 'error');
    }

    // Notification system
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icons = {
            'info': 'ℹ️',
            'success': '✅',
            'warning': '⚠️',
            'error': '❌'
        };

        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${icons[type] || 'ℹ️'}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'error' ? 'var(--color-danger-500)' : 
                        type === 'success' ? 'var(--color-success-500)' : 
                        type === 'warning' ? 'var(--color-warning-500)' : 
                        'var(--color-info-500)'};
            color: white;
            padding: var(--spacing-4);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            max-width: 400px;
            animation: slideInRight 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        // Auto-remove
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    }

    // Loading state management
    showLoading(message = 'Loading...') {
        const loading = document.createElement('div');
        loading.id = 'unified-loading';
        loading.innerHTML = `
            <div class="loading-overlay">
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <div class="loading-message">${message}</div>
                </div>
            </div>
        `;

        loading.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;

        document.body.appendChild(loading);
    }

    hideLoading() {
        const loading = document.getElementById('unified-loading');
        if (loading) {
            loading.remove();
        }
    }
}

// Town State Manager
class TownStateManager {
    constructor(gameManager) {
        this.gameManager = gameManager;
    }

    initialize(gameData) {
        console.log('Initializing Town State');
        
        // Set up town-specific event listeners
        this.setupTownEventListeners();
    }

    setupTownEventListeners() {
        // Movement direction buttons
        document.querySelectorAll('.connection-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const direction = e.currentTarget.dataset.direction;
                this.moveToDirection(direction);
            });
        });

        // Facility links
        document.querySelectorAll('.facility-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const href = e.currentTarget.href;
                if (href && href !== '#') {
                    window.location.href = href;
                }
            });
        });
    }

    async moveToDirection(direction) {
        console.log('Moving to direction:', direction);
        
        this.gameManager.showLoading('移動中...');
        
        const result = await this.gameManager.makeRequest('/game/move-to-direction', 'POST', {
            direction: direction
        });

        this.gameManager.hideLoading();

        if (result && result.success) {
            // Transition to road state
            this.gameManager.showNotification('移動を開始しました', 'success');
            setTimeout(() => {
                window.location.reload(); // In real app, would update state dynamically
            }, 1000);
        }
    }
}

// Road State Manager
class RoadStateManager {
    constructor(gameManager) {
        this.gameManager = gameManager;
        this.diceResult = null;
        this.availableSteps = 0;
    }

    initialize(gameData) {
        console.log('Initializing Road State');
        
        this.setupRoadEventListeners();
        this.updateProgressBar(gameData.player?.game_position || 0);
    }

    setupRoadEventListeners() {
        // Dice roll button
        const rollDiceBtn = document.getElementById('roll-dice');
        if (rollDiceBtn) {
            rollDiceBtn.addEventListener('click', () => this.rollDice());
        }

        // Movement buttons
        document.querySelectorAll('.movement-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const direction = e.currentTarget.dataset.direction;
                this.move(direction);
            });
        });

        // Branch selection buttons
        document.querySelectorAll('.branch-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const direction = e.currentTarget.dataset.direction;
                this.selectBranch(direction);
            });
        });

        // Next location button
        const nextBtn = document.getElementById('move-to-next');
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.moveToNext());
        }

        // Road actions
        const gatheringBtn = document.getElementById('gathering-btn');
        if (gatheringBtn) {
            gatheringBtn.addEventListener('click', () => this.performGathering());
        }
    }

    async rollDice() {
        console.log('Rolling dice...');
        
        const rollButton = document.getElementById('roll-dice');
        if (rollButton) {
            rollButton.disabled = true;
            rollButton.textContent = 'サイコロを振っています...';
        }

        const result = await this.gameManager.makeRequest('/game/roll-dice');

        if (result && result.success) {
            this.diceResult = result.data;
            this.availableSteps = result.data.final_movement;
            
            this.displayDiceResult(result.data);
            this.showMovementControls();
            
            this.gameManager.showNotification(`${this.availableSteps}歩移動できます`, 'success');
        }

        if (rollButton) {
            rollButton.disabled = false;
            rollButton.textContent = 'サイコロを振る';
        }
    }

    displayDiceResult(diceData) {
        // Show dice visually
        const diceDisplay = document.getElementById('dice-result');
        const allDice = document.getElementById('all-dice');
        
        if (diceDisplay && allDice) {
            allDice.innerHTML = '';
            
            if (diceData.dice_results) {
                diceData.dice_results.forEach(result => {
                    const dice = document.createElement('div');
                    dice.className = 'dice';
                    dice.textContent = result;
                    allDice.appendChild(dice);
                });
            }
            
            diceDisplay.classList.remove('hidden');
        }

        // Show results summary
        const diceTotal = document.getElementById('dice-total');
        if (diceTotal) {
            document.getElementById('base-total').textContent = diceData.base_total || 0;
            document.getElementById('bonus').textContent = diceData.bonus || 0;
            document.getElementById('final-movement').textContent = diceData.final_movement || 0;
            
            diceTotal.classList.remove('hidden');
        }
    }

    showMovementControls() {
        const movementControls = document.getElementById('movement-controls');
        if (movementControls) {
            movementControls.classList.remove('hidden');
        }

        // Update available steps display
        const availableStepsEl = document.getElementById('available-steps');
        if (availableStepsEl) {
            availableStepsEl.textContent = this.availableSteps;
        }
    }

    async move(direction) {
        if (this.availableSteps <= 0) {
            this.gameManager.showNotification('移動可能な歩数がありません', 'warning');
            return;
        }

        console.log('Moving:', direction);

        const result = await this.gameManager.makeRequest('/game/move', 'POST', {
            direction: direction,
            steps: this.availableSteps
        });

        if (result && result.success) {
            this.availableSteps = 0;
            this.updateProgressBar(result.data.new_position);
            this.updateMovementDirection(direction);
            
            // Check for encounters or events
            if (result.data.encounter) {
                this.handleEncounter(result.data.encounter);
            } else if (result.data.reached_destination) {
                this.showNextLocationButton();
            }
            
            this.gameManager.showNotification('移動しました', 'success');
            this.hideMovementControls();
        }
    }

    updateProgressBar(position) {
        const progressFill = document.getElementById('progress-fill');
        const progressText = document.getElementById('progress-text');

        if (progressFill) {
            progressFill.style.width = position + '%';
        }
        if (progressText) {
            progressText.textContent = position + '/100';
        }
    }

    updateMovementDirection(direction) {
        const directionEl = document.getElementById('movement-direction');
        if (directionEl) {
            const directionText = direction === 'left' ? '左へ移動' : '右へ移動';
            directionEl.textContent = directionText;
        }
    }

    hideMovementControls() {
        const movementControls = document.getElementById('movement-controls');
        if (movementControls) {
            movementControls.classList.add('hidden');
        }
    }

    showNextLocationButton() {
        const nextLocation = document.getElementById('next-location-info');
        if (nextLocation) {
            nextLocation.classList.remove('hidden');
        }
    }

    async moveToNext() {
        console.log('Moving to next location');
        
        this.gameManager.showLoading('次の場所へ移動中...');

        const result = await this.gameManager.makeRequest('/game/move-to-next');

        this.gameManager.hideLoading();

        if (result && result.success) {
            this.gameManager.showNotification('次の場所に到着しました', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }

    async performGathering() {
        console.log('Performing gathering');
        
        const gatheringBtn = document.getElementById('gathering-btn');
        if (gatheringBtn) {
            gatheringBtn.disabled = true;
            gatheringBtn.textContent = '採集中...';
        }

        // Simulate gathering (replace with actual endpoint)
        setTimeout(() => {
            const items = ['薬草', '木の実', '鉱石', 'キノコ'];
            const randomItem = items[Math.floor(Math.random() * items.length)];
            
            this.gameManager.showNotification(`${randomItem}を発見しました！`, 'success');
            
            if (gatheringBtn) {
                gatheringBtn.disabled = false;
                gatheringBtn.textContent = '採集する';
            }
        }, 2000);
    }

    handleEncounter(encounter) {
        if (encounter.type === 'battle') {
            this.gameManager.showNotification('魔物が現れた！', 'warning');
            setTimeout(() => {
                window.location.href = '/battle';
            }, 2000);
        }
    }

    async selectBranch(direction) {
        console.log('Selecting branch:', direction);

        const result = await this.gameManager.makeRequest('/game/move-to-branch', 'POST', {
            direction: direction
        });

        if (result && result.success) {
            this.gameManager.showNotification(`${direction}方向に進みます`, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }
}

// Battle State Manager
class BattleStateManager {
    constructor(gameManager) {
        this.gameManager = gameManager;
        this.battleData = null;
        this.currentTurn = 1;
        this.battleEnded = false;
        this.availableSkills = [];
    }

    initialize(gameData) {
        console.log('Initializing Battle State');
        
        this.battleData = gameData.battle;
        this.setupBattleEventListeners();
        this.updateSkillMenu(gameData.character);
        this.initializeEscapeRate();
    }

    setupBattleEventListeners() {
        // Battle action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            const action = btn.onclick?.toString().match(/performAction\('([^']+)'/)?.[1];
            if (action) {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.performAction(action);
                });
            }
        });

        // Skill menu toggle
        const skillButton = document.getElementById('skill-button');
        if (skillButton) {
            skillButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSkillMenu();
            });
        }

        // Return to game button
        const returnBtn = document.getElementById('return-to-game-btn');
        if (returnBtn) {
            returnBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.returnToGame();
            });
        }

        // Quick item buttons
        document.querySelectorAll('.quick-item-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const itemType = e.currentTarget.onclick?.toString().match(/useQuickItem\('([^']+)'/)?.[1];
                if (itemType && !btn.classList.contains('disabled')) {
                    this.useQuickItem(itemType);
                }
            });
        });
    }

    async performAction(action, skillId = null) {
        if (this.battleEnded) return;

        console.log('Performing battle action:', action);

        // Disable all action buttons
        document.querySelectorAll('.action-btn').forEach(btn => btn.disabled = true);

        const url = skillId ? '/battle/skill' : `/battle/${action}`;
        const requestData = skillId ? { skill_id: skillId } : {};

        const result = await this.gameManager.makeRequest(url, 'POST', requestData);

        if (result && result.success) {
            this.updateBattleDisplay(result);
            this.updateBattleLog(result.battle_log);

            if (result.battle_end) {
                this.endBattle(result);
            } else {
                this.currentTurn = result.turn;
                document.getElementById('turn-indicator').textContent = `ターン ${this.currentTurn}`;
                
                // Re-enable buttons
                document.querySelectorAll('.action-btn').forEach(btn => btn.disabled = false);
                
                // Update escape rate
                if (result.escape_rate) {
                    const escapeRateEl = document.getElementById('escape-rate');
                    if (escapeRateEl) {
                        escapeRateEl.textContent = `成功率: ${result.escape_rate}%`;
                    }
                }
            }
        } else {
            document.querySelectorAll('.action-btn').forEach(btn => btn.disabled = false);
        }
    }

    updateBattleDisplay(data) {
        const character = data.character;
        const monster = data.monster;

        // Update character HP
        const characterHpPercentage = (character.hp / character.max_hp) * 100;
        const characterHpBar = document.getElementById('character-hp');
        const characterHpText = document.getElementById('character-hp-text');
        
        if (characterHpBar) characterHpBar.style.width = characterHpPercentage + '%';
        if (characterHpText) characterHpText.textContent = `${character.hp}/${character.max_hp}`;

        // Update character MP
        const characterMpPercentage = (character.mp / character.max_mp) * 100;
        const characterMpBar = document.getElementById('character-mp');
        const characterMpText = document.getElementById('character-mp-text');
        
        if (characterMpBar) characterMpBar.style.width = characterMpPercentage + '%';
        if (characterMpText) characterMpText.textContent = `${character.mp}/${character.max_mp}`;

        // Update monster HP
        const monsterHpPercentage = (monster.stats.hp / monster.stats.max_hp) * 100;
        const monsterHpBar = document.getElementById('monster-hp');
        const monsterHpText = document.getElementById('monster-hp-text');
        
        if (monsterHpBar) monsterHpBar.style.width = monsterHpPercentage + '%';
        if (monsterHpText) monsterHpText.textContent = `${monster.stats.hp}/${monster.stats.max_hp}`;

        // Update skill menu
        this.updateSkillMenu(character);
    }

    updateBattleLog(battleLog) {
        const logContainer = document.getElementById('log-container');
        if (!logContainer || !battleLog) return;

        logContainer.innerHTML = '';

        battleLog.forEach(entry => {
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry';

            if (entry.action?.includes('player')) {
                logEntry.classList.add('player-action');
            } else if (entry.action?.includes('monster')) {
                logEntry.classList.add('monster-action');
            } else if (entry.action === 'battle_end') {
                logEntry.classList.add('battle-end');
            }

            logEntry.textContent = entry.message;
            logContainer.appendChild(logEntry);
        });

        // Scroll to bottom
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    endBattle(data) {
        this.battleEnded = true;

        // Hide battle actions
        const battleActions = document.getElementById('battle-actions');
        if (battleActions) {
            battleActions.classList.add('hidden');
        }

        // Show result
        const resultDiv = document.getElementById('battle-result');
        const resultTitle = document.getElementById('result-title');
        const resultMessage = document.getElementById('result-message');

        if (resultDiv && resultTitle && resultMessage) {
            resultDiv.classList.remove('hidden');

            if (data.result === 'victory') {
                resultTitle.textContent = '勝利！';
                resultMessage.textContent = `${data.monster.name}を倒しました！`;
                
                if (data.experience_gained > 0) {
                    const expDiv = document.getElementById('experience-gained');
                    const expAmount = document.getElementById('exp-amount');
                    if (expDiv && expAmount) {
                        expAmount.textContent = data.experience_gained;
                        expDiv.classList.remove('hidden');
                    }
                }
            } else if (data.result === 'defeat') {
                resultTitle.textContent = '敗北...';
                resultMessage.textContent = '戦闘に敗れました。';
            } else if (data.result === 'escaped') {
                resultTitle.textContent = '逃走';
                resultMessage.textContent = '戦闘から逃げ出しました。';
            }
        }

        // Show continue button
        const continueActions = document.getElementById('continue-actions');
        if (continueActions) {
            continueActions.classList.remove('hidden');
        }
    }

    async returnToGame() {
        console.log('Returning to game');

        const continueBtn = document.getElementById('return-to-game-btn');
        if (continueBtn) {
            continueBtn.disabled = true;
            continueBtn.textContent = '戻り中...';
        }

        if (this.battleEnded) {
            window.location.href = '/game';
            return;
        }

        const result = await this.gameManager.makeRequest('/battle/end');

        if (result && result.success) {
            window.location.href = '/game';
        } else {
            window.location.href = '/game'; // Force return even on error
        }
    }

    toggleSkillMenu() {
        const skillMenu = document.getElementById('skill-menu');
        if (skillMenu) {
            skillMenu.classList.toggle('hidden');
        }
    }

    updateSkillMenu(character) {
        this.updateAvailableSkills(character);
        const skillMenu = document.getElementById('skill-menu');
        if (!skillMenu) return;

        skillMenu.innerHTML = '';

        if (this.availableSkills.length === 0) {
            const noSkillItem = document.createElement('div');
            noSkillItem.className = 'skill-item disabled';
            noSkillItem.textContent = '使用可能な特技がありません';
            skillMenu.appendChild(noSkillItem);
            return;
        }

        this.availableSkills.forEach(skill => {
            const skillItem = document.createElement('div');
            skillItem.className = 'skill-item';

            const canUse = character.mp >= skill.mp_cost;
            if (!canUse) {
                skillItem.classList.add('disabled');
            }

            skillItem.innerHTML = `
                <span>${skill.name}</span>
                <span class="skill-cost">MP ${skill.mp_cost}</span>
            `;

            if (canUse) {
                skillItem.addEventListener('click', () => {
                    skillMenu.classList.add('hidden');
                    this.performAction('skill', skill.skill_id);
                });
            }

            skillMenu.appendChild(skillItem);
        });
    }

    updateAvailableSkills(character) {
        // Mock skill data (replace with actual data from character)
        this.availableSkills = [
            {
                skill_id: 'fire_magic',
                name: 'ファイヤー',
                mp_cost: 5
            },
            {
                skill_id: 'heal',
                name: 'ヒール',
                mp_cost: 4
            },
            {
                skill_id: 'thunder',
                name: 'サンダー',
                mp_cost: 8
            }
        ];
    }

    initializeEscapeRate() {
        // Calculate and display initial escape rate
        const escapeRateEl = document.getElementById('escape-rate');
        if (escapeRateEl && this.gameManager.gameData.character && this.gameManager.gameData.monster) {
            const character = this.gameManager.gameData.character;
            const monster = this.gameManager.gameData.monster;
            
            const baseEscapeRate = 50;
            const speedDifference = (character.agility || 18) - (monster.stats?.agility || 10);
            const escapeRate = Math.max(10, Math.min(90, baseEscapeRate + (speedDifference * 3)));
            
            escapeRateEl.textContent = `成功率: ${escapeRate}%`;
        }
    }

    useQuickItem(itemType) {
        console.log('Using quick item:', itemType);
        
        // Mock item usage (replace with actual implementation)
        const itemNames = {
            'potion': '回復ポーション',
            'ether': 'マナポーション',
            'bomb': '爆弾'
        };
        
        this.gameManager.showNotification(`${itemNames[itemType] || itemType}を使用しました`, 'success');
    }
}

// Global functions for backward compatibility
let gameManager;

function initializeUnifiedGame(gameData) {
    gameManager = new UnifiedGameManager();
    gameManager.initializeGame(gameData);
}

// Expose individual functions for legacy support
function rollDice() {
    if (gameManager?.roadManager) {
        gameManager.roadManager.rollDice();
    }
}

function move(direction) {
    if (gameManager?.roadManager) {
        gameManager.roadManager.move(direction);
    }
}

function moveToNext() {
    if (gameManager?.roadManager) {
        gameManager.roadManager.moveToNext();
    }
}

function moveToDirection(direction) {
    if (gameManager?.townManager) {
        gameManager.townManager.moveToDirection(direction);
    }
}

function selectBranch(direction) {
    if (gameManager?.roadManager) {
        gameManager.roadManager.selectBranch(direction);
    }
}

function performAction(action, skillId = null) {
    if (gameManager?.battleManager) {
        gameManager.battleManager.performAction(action, skillId);
    }
}

function toggleSkillMenu() {
    if (gameManager?.battleManager) {
        gameManager.battleManager.toggleSkillMenu();
    }
}

function returnToGame() {
    if (gameManager?.battleManager) {
        gameManager.battleManager.returnToGame();
    }
}

function performGathering() {
    if (gameManager?.roadManager) {
        gameManager.roadManager.performGathering();
    }
}

function resetGame() {
    if (confirm('ゲームをリセットしますか？進行状況は失われます。')) {
        window.location.href = '/game/reset';
    }
}

function toggleDiceDisplay() {
    const diceResult = document.getElementById('dice-result');
    const toggle = document.getElementById('dice-display-toggle');
    
    if (diceResult && toggle) {
        if (toggle.checked) {
            diceResult.classList.remove('hidden');
        } else {
            diceResult.classList.add('hidden');
        }
    }
}

// Additional utility functions
function showGatheringInfo() {
    if (gameManager) {
        gameManager.showNotification('採集可能なアイテム: 薬草、木の実、鉱石', 'info');
    }
}

function takeRest() {
    if (gameManager) {
        gameManager.showNotification('少し休憩しました。HP+5', 'success');
    }
}

function lookAround() {
    if (gameManager) {
        gameManager.showNotification('周囲を調べましたが、特に何も見つかりませんでした。', 'info');
    }
}

function shortRest() {
    if (gameManager) {
        gameManager.showNotification('少し休憩しました。HP+5', 'success');
    }
}

function meditation() {
    if (gameManager) {
        gameManager.showNotification('瞑想しました。MP+3', 'success');
    }
}

function openMap() {
    if (gameManager) {
        gameManager.showNotification('地図機能は開発中です', 'info');
    }
}

function checkWeather() {
    if (gameManager) {
        gameManager.showNotification('今日の天気: 晴れ', 'info');
    }
}

function openSettings() {
    if (gameManager) {
        gameManager.showNotification('設定画面は開発中です', 'info');
    }
}

function returnToTown() {
    if (confirm('最寄りの町に戻りますか？')) {
        if (gameManager) {
            gameManager.showNotification('町に戻りました', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }
}

function callForHelp() {
    if (gameManager) {
        gameManager.showNotification('助けを呼びましたが、誰も来ませんでした...', 'warning');
    }
}

function forfeitBattle() {
    if (confirm('戦闘を放棄しますか？')) {
        if (gameManager?.battleManager) {
            gameManager.battleManager.returnToGame();
        }
    }
}

function useQuickItem(itemType) {
    if (gameManager?.battleManager) {
        gameManager.battleManager.useQuickItem(itemType);
    }
}

// CSS for notifications and loading
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
    }
    
    .notification-close {
        background: none;
        border: none;
        color: inherit;
        font-size: var(--font-size-lg);
        cursor: pointer;
        margin-left: auto;
    }
    
    .loading-overlay {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
    }
    
    .loading-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--spacing-4);
    }
    
    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
    }
    
    .loading-message {
        font-size: var(--font-size-lg);
        font-weight: 500;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;

document.head.appendChild(additionalStyles);

// === Background Manager System ===
class BackgroundManager {
    constructor() {
        this.currentBackground = null;
        this.transitionDuration = 300;
        this.weatherEffects = {
            clear: null,
            rain: 'rain',
            night: 'night',
            dawn: 'dawn',
            dusk: 'dusk'
        };
        
        this.initialize();
    }
    
    initialize() {
        console.log('Background Manager initialized');
        this.currentBackground = this.getCurrentBackground();
    }
    
    getCurrentBackground() {
        const layout = document.querySelector('.game-unified-layout');
        if (!layout) return null;
        
        return {
            gameState: layout.getAttribute('data-game-state'),
            locationId: layout.getAttribute('data-location-id'),
            locationType: layout.getAttribute('data-location-type'),
            weather: layout.getAttribute('data-weather') || 'clear',
            time: layout.getAttribute('data-time') || 'day'
        };
    }
    
    updateBackground(gameState, locationId, locationType, options = {}) {
        const layout = document.querySelector('.game-unified-layout');
        if (!layout) {
            console.warn('Game layout not found');
            return;
        }
        
        // Add transition class
        layout.style.transition = `all ${this.transitionDuration}ms ease-in-out`;
        
        // Update data attributes
        layout.setAttribute('data-game-state', gameState);
        layout.setAttribute('data-location-id', locationId);
        layout.setAttribute('data-location-type', locationType);
        
        // Apply weather and time effects if provided
        if (options.weather) {
            layout.setAttribute('data-weather', options.weather);
        }
        if (options.time) {
            layout.setAttribute('data-time', options.time);
        }
        
        // Update current background tracking
        this.currentBackground = {
            gameState,
            locationId,
            locationType,
            weather: options.weather || 'clear',
            time: options.time || 'day'
        };
        
        console.log('Background updated:', this.currentBackground);
        
        // Remove transition after animation completes
        setTimeout(() => {
            layout.style.transition = '';
        }, this.transitionDuration);
    }
    
    setWeather(weatherType) {
        if (!this.weatherEffects.hasOwnProperty(weatherType)) {
            console.warn('Unknown weather type:', weatherType);
            return;
        }
        
        const layout = document.querySelector('.game-unified-layout');
        if (layout) {
            layout.setAttribute('data-weather', weatherType);
            this.currentBackground.weather = weatherType;
            console.log('Weather updated to:', weatherType);
        }
    }
    
    setTimeOfDay(timeType) {
        const validTimes = ['day', 'dawn', 'dusk', 'night'];
        if (!validTimes.includes(timeType)) {
            console.warn('Unknown time type:', timeType);
            return;
        }
        
        const layout = document.querySelector('.game-unified-layout');
        if (layout) {
            layout.setAttribute('data-time', timeType);
            this.currentBackground.time = timeType;
            console.log('Time updated to:', timeType);
        }
    }
    
    // Smooth transition between locations
    transitionToLocation(newGameState, newLocationId, newLocationType, options = {}) {
        return new Promise((resolve) => {
            const layout = document.querySelector('.game-unified-layout');
            if (!layout) {
                resolve();
                return;
            }
            
            // Fade out effect
            layout.style.opacity = '0.7';
            
            setTimeout(() => {
                this.updateBackground(newGameState, newLocationId, newLocationType, options);
                
                // Fade back in
                layout.style.opacity = '1';
                
                setTimeout(() => {
                    resolve();
                }, this.transitionDuration);
            }, this.transitionDuration / 2);
        });
    }
    
    // Preset background configurations
    presets = {
        townA: { gameState: 'town', locationId: 'town_a', locationType: 'town' },
        townB: { gameState: 'town', locationId: 'town_b', locationType: 'town' },
        roadA: { gameState: 'road', locationId: 'road_a', locationType: 'road' },
        roadB: { gameState: 'road', locationId: 'road_b', locationType: 'road' },
        battle: { gameState: 'battle', locationId: 'battle_field', locationType: 'battle' }
    };
    
    applyPreset(presetName, options = {}) {
        const preset = this.presets[presetName];
        if (!preset) {
            console.warn('Unknown preset:', presetName);
            return;
        }
        
        this.updateBackground(preset.gameState, preset.locationId, preset.locationType, options);
    }
    
    // Debug function to list available backgrounds
    listAvailableBackgrounds() {
        console.log('Available background presets:');
        Object.keys(this.presets).forEach(preset => {
            console.log(`- ${preset}:`, this.presets[preset]);
        });
        
        console.log('Available weather effects:', Object.keys(this.weatherEffects));
        console.log('Available time effects: day, dawn, dusk, night');
    }
}

// Global background change functions for demo purposes
window.changeBackground = function(gameState, locationId, locationType, options = {}) {
    if (gameManager?.backgroundManager) {
        gameManager.backgroundManager.updateBackground(gameState, locationId, locationType, options);
    }
};

window.setWeather = function(weatherType) {
    if (gameManager?.backgroundManager) {
        gameManager.backgroundManager.setWeather(weatherType);
    }
};

window.setTime = function(timeType) {
    if (gameManager?.backgroundManager) {
        gameManager.backgroundManager.setTimeOfDay(timeType);
    }
};

console.log('Game Unified JavaScript loaded successfully');