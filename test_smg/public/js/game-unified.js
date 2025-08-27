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

    /**
     * シームレスな状態遷移システム
     * @param {string} newState - 新しい状態 ('town', 'road', 'battle')
     * @param {object} newData - 新しい状態のデータ
     * @param {object} transitionOptions - 遷移オプション
     */
    async transitionToState(newState, newData, transitionOptions = {}) {
        console.log('🚀 [TRANSITION] Starting seamless transition to:', newState);
        console.log('🚀 [TRANSITION] New data:', newData);
        
        const oldState = this.gameState;
        
        // 1. 現在の状態を非アクティブ化
        await this.deactivateCurrentState();
        
        // 2. 遷移アニメーション開始
        if (transitionOptions.animation !== false) {
            await this.startTransitionAnimation(oldState, newState);
        }
        
        // 3. ゲームデータ更新
        this.gameData = { ...this.gameData, ...newData };
        this.gameState = newState;
        
        // 4. 新しいコンテンツを取得・更新
        await this.updatePageContent(newState, this.gameData);
        
        // 5. 新しい状態をアクティブ化
        await this.activateNewState(newState, this.gameData);
        
        // 6. UI更新
        this.updateGameStateUI();
        
        // 7. 遷移完了アニメーション
        if (transitionOptions.animation !== false) {
            await this.completeTransitionAnimation();
        }
        
        console.log('🚀 [TRANSITION] Seamless transition completed');
    }

    /**
     * 現在の状態を非アクティブ化
     */
    async deactivateCurrentState() {
        console.log('🚀 [TRANSITION] Deactivating current state:', this.gameState);
        
        switch (this.gameState) {
            case 'town':
                if (this.townManager && this.townManager.deactivate) {
                    await this.townManager.deactivate();
                }
                break;
            case 'road':
                if (this.roadManager && this.roadManager.deactivate) {
                    await this.roadManager.deactivate();
                }
                break;
            case 'battle':
                if (this.battleManager && this.battleManager.deactivate) {
                    await this.battleManager.deactivate();
                }
                break;
        }
    }

    /**
     * 新しい状態をアクティブ化
     */
    async activateNewState(newState, gameData) {
        console.log('🚀 [TRANSITION] Activating new state:', newState);
        
        switch (newState) {
            case 'town':
                if (this.townManager) {
                    if (this.townManager.activate) {
                        await this.townManager.activate(gameData);
                    } else {
                        this.townManager.initialize(gameData);
                    }
                }
                break;
            case 'road':
                if (this.roadManager) {
                    if (this.roadManager.activate) {
                        await this.roadManager.activate(gameData);
                    } else {
                        this.roadManager.initialize(gameData);
                    }
                }
                break;
            case 'battle':
                if (this.battleManager) {
                    if (this.battleManager.activate) {
                        await this.battleManager.activate(gameData);
                    } else {
                        this.battleManager.initialize(gameData);
                    }
                }
                break;
        }
    }

    /**
     * 遷移アニメーション開始
     */
    async startTransitionAnimation(oldState, newState) {
        console.log('🚀 [TRANSITION] Starting animation:', oldState, '→', newState);
        
        const gameContainer = document.querySelector('.game-container, .main-content, .game-unified-layout');
        if (gameContainer) {
            gameContainer.classList.add('transition-out');
            
            // アニメーション完了まで待機
            return new Promise(resolve => {
                setTimeout(() => {
                    resolve();
                }, 300); // CSS transition時間に合わせる
            });
        }
    }

    /**
     * 遷移完了アニメーション
     */
    async completeTransitionAnimation() {
        console.log('🚀 [TRANSITION] Completing animation');
        
        const gameContainer = document.querySelector('.game-container, .main-content, .game-unified-layout');
        if (gameContainer) {
            gameContainer.classList.remove('transition-out');
            gameContainer.classList.add('transition-in');
            
            // アニメーション完了後クラス削除
            setTimeout(() => {
                gameContainer.classList.remove('transition-in');
            }, 300);
        }
    }

    /**
     * ページコンテンツの動的更新
     */
    async updatePageContent(newState, gameData) {
        console.log('🚀 [TRANSITION] Updating page content for state:', newState);
        
        try {
            // サーバーから新しいコンテンツを取得
            const response = await fetch('/game', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.gameData?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const html = await response.text();
            
            // 新しいHTMLから必要な部分を抽出してDOM更新
            await this.updateDOMContent(html, newState);
            
            console.log('🚀 [TRANSITION] Page content updated successfully');
            
        } catch (error) {
            console.error('🚀 [TRANSITION] Failed to update page content:', error);
            // フォールバック: 現在のコンテンツをそのまま使用
        }
    }

    /**
     * DOMコンテンツの更新
     */
    async updateDOMContent(html, newState) {
        console.log('🚀 [TRANSITION] Updating DOM content for state:', newState);
        
        // 現在のレイアウトタイプを検出
        const layoutContainer = document.querySelector('.game-unified-layout');
        const isNoRightLayout = layoutContainer?.classList.contains('game-layout-noright');
        const isUnifiedLayout = !isNoRightLayout && layoutContainer?.classList.contains('game-unified-layout');
        
        console.log('🚀 [TRANSITION] Detected layout type:', {
            noright: isNoRightLayout,
            unified: isUnifiedLayout,
            containerClasses: layoutContainer?.className
        });
        
        // 一時的なDOMパーサーを作成
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        
        // 左エリア（サイドバー）の更新
        const currentLeftArea = document.querySelector('.unified-left-area');
        const newLeftArea = newDoc.querySelector('.unified-left-area');
        
        if (currentLeftArea && newLeftArea) {
            console.log('🚀 [TRANSITION] Updating left area content...');
            console.log('🚀 [TRANSITION] Current left area classes:', currentLeftArea.className);
            console.log('🚀 [TRANSITION] New left area content preview:', newLeftArea.innerHTML.substring(0, 200) + '...');
            currentLeftArea.innerHTML = newLeftArea.innerHTML;
            console.log('🚀 [TRANSITION] Left area updated successfully');
        } else {
            console.warn('🚀 [TRANSITION] Left area elements not found:', {
                current: !!currentLeftArea,
                new: !!newLeftArea
            });
            // フォールバック: より具体的なセレクタで試行
            const currentLeftSidebar = document.querySelector('.left-sidebar');
            const newLeftSidebar = newDoc.querySelector('.left-sidebar');
            if (currentLeftSidebar && newLeftSidebar) {
                console.log('🚀 [TRANSITION] Using .left-sidebar fallback');
                currentLeftSidebar.innerHTML = newLeftSidebar.innerHTML;
            }
        }
        
        // メインエリアの更新
        const currentMainArea = document.querySelector('.unified-main-area');
        const newMainArea = newDoc.querySelector('.unified-main-area');
        
        if (currentMainArea && newMainArea) {
            console.log('🚀 [TRANSITION] Updating main area content...');
            console.log('🚀 [TRANSITION] Current main area classes:', currentMainArea.className);
            console.log('🚀 [TRANSITION] New main area content preview:', newMainArea.innerHTML.substring(0, 200) + '...');
            currentMainArea.innerHTML = newMainArea.innerHTML;
            console.log('🚀 [TRANSITION] Main area updated successfully');
        } else {
            console.warn('🚀 [TRANSITION] Main area elements not found:', {
                current: !!currentMainArea,
                new: !!newMainArea
            });
            // フォールバック: より具体的なセレクタで試行  
            const currentMainContent = document.querySelector('.main-content');
            const newMainContent = newDoc.querySelector('.main-content');
            if (currentMainContent && newMainContent) {
                console.log('🚀 [TRANSITION] Using .main-content fallback');
                currentMainContent.innerHTML = newMainContent.innerHTML;
            }
        }
        
        // 右エリアの更新（3カラムレイアウトの場合のみ）
        const currentRightArea = document.querySelector('.unified-right-area');
        const newRightArea = newDoc.querySelector('.unified-right-area');
        
        if (currentRightArea && newRightArea) {
            console.log('🚀 [TRANSITION] Updating right area content...');
            console.log('🚀 [TRANSITION] Current right area classes:', currentRightArea.className);
            currentRightArea.innerHTML = newRightArea.innerHTML;
            console.log('🚀 [TRANSITION] Right area updated successfully');
        } else {
            console.log('🚀 [TRANSITION] Right area not found (probably noright layout):', {
                current: !!currentRightArea,
                new: !!newRightArea
            });
        }
        
        // 背景画像の更新
        const currentBgImage = document.querySelector('.unified-background-image');
        const newBgImage = newDoc.querySelector('.unified-background-image');
        
        if (currentBgImage && newBgImage) {
            currentBgImage.src = newBgImage.src;
            currentBgImage.alt = newBgImage.alt;
            console.log('🚀 [TRANSITION] Background image updated');
        }
        
        // ゲーム状態インジケーターの更新
        const currentStateIndicator = document.querySelector('.game-state-indicator');
        const newStateIndicator = newDoc.querySelector('.game-state-indicator');
        
        if (currentStateIndicator && newStateIndicator) {
            currentStateIndicator.className = newStateIndicator.className;
            currentStateIndicator.textContent = newStateIndicator.textContent;
            console.log('🚀 [TRANSITION] State indicator updated');
        }
        
        // DOM更新後にイベントリスナーを再設定
        this.reattachEventListeners(newState);
        
        console.log('🚀 [TRANSITION] DOM content update completed');
    }

    /**
     * onclick属性を完全に削除
     */
    removeAllOnclickAttributes() {
        console.log('🚀 [TRANSITION] Removing all onclick attributes');
        
        // 移動関連のボタンのonclick属性を削除
        const buttonsWithOnclick = document.querySelectorAll('button[onclick]');
        buttonsWithOnclick.forEach(btn => {
            const onclick = btn.getAttribute('onclick');
            if (onclick && (onclick.includes('moveToNext') || onclick.includes('moveToDirection') || 
                          onclick.includes('move(') || onclick.includes('rollDice'))) {
                console.log('🚀 [TRANSITION] Removing onclick from button:', onclick);
                btn.removeAttribute('onclick');
            }
        });
    }

    /**
     * 既存のイベントリスナーをクリア
     */
    clearExistingEventListeners() {
        console.log('🚀 [TRANSITION] Clearing existing event listeners');
        
        // 状態管理フラグをリセット
        if (this.townManager) {
            this.townManager.eventListenersAttached = false;
        }
        if (this.roadManager) {
            this.roadManager.eventListenersAttached = false;
        }
        if (this.battleManager) {
            this.battleManager.eventListenersAttached = false;
        }
        
        console.log('🚀 [TRANSITION] Event listener flags reset');
    }

    /**
     * DOM更新後のイベントリスナー再設定（改善版）
     */
    reattachEventListeners(newState) {
        console.log('🚀 [TRANSITION] Reattaching event listeners for state:', newState);
        
        // 1. 既存のonclick属性を完全削除
        this.removeAllOnclickAttributes();
        
        // 2. 既存のイベントリスナー状態をクリア
        this.clearExistingEventListeners();
        
        // 3. 新しいイベントリスナーを設定
        switch (newState) {
            case 'town':
                if (this.townManager && this.townManager.setupTownEventListeners) {
                    this.townManager.setupTownEventListeners();
                }
                break;
            case 'road':
                if (this.roadManager && this.roadManager.setupRoadEventListeners) {
                    this.roadManager.setupRoadEventListeners();
                }
                break;
            case 'battle':
                if (this.battleManager && this.battleManager.setupBattleEventListeners) {
                    this.battleManager.setupBattleEventListeners();
                }
                break;
        }
        
        console.log('🚀 [TRANSITION] Event listeners reattached with cleanup');
    }

    // Utility method for AJAX requests
    async makeRequest(url, method = 'POST', data = {}) {
        if (this.isTransitioning) {
            console.log('Request blocked: system is transitioning');
            return null;
        }

        console.log('Making request to:', url, 'with data:', data);

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

            console.log('Response status:', response.status, 'Result:', result);

            if (!response.ok) {
                throw new Error(result.message || `HTTP ${response.status}: ${response.statusText}`);
            }

            return result;
        } catch (error) {
            console.error('Request failed:', error);
            this.handleError('Network Error', error);
            return { success: false, error: error.message };
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
        this.isMoving = false; // 重複移動防止フラグ
        this.eventListenersAttached = false; // イベントリスナー重複防止フラグ
    }

    initialize(gameData) {
        console.log('Initializing Town State');
        
        // Set up town-specific event listeners
        this.setupTownEventListeners();
    }

    setupTownEventListeners() {
        console.log('🚀 [TOWN] Setting up town event listeners');
        
        // イベントリスナーが既に設定済みの場合はスキップ
        if (this.eventListenersAttached) {
            console.log('🚀 [TOWN] Event listeners already attached, skipping setup');
            return;
        }
        
        // 移動中フラグを確実にリセット（遷移後に残っている可能性があるため）
        this.isMoving = false;
        
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
        
        // Movement buttons - 重複実行を防ぐためonclick属性を無効化
        // まず、すべての接続ボタン（onclick属性の有無に関わらず）を取得
        const connectionButtons = document.querySelectorAll('.connection-btn, button[onclick*="moveToDirection"]');
        console.log('🚀 [TOWN] Found connection buttons:', connectionButtons.length);
        
        connectionButtons.forEach(btn => {
            console.log('🚀 [TOWN] Processing movement button:', btn, 'onclick:', btn.getAttribute('onclick'), 'data-direction:', btn.getAttribute('data-direction'));
            
            // Ensure button is clickable
            btn.disabled = false;
            btn.style.pointerEvents = 'auto';
            btn.classList.remove('disabled');
            
            // 方向を抽出 - data-direction属性またはonclick属性から
            let direction = btn.getAttribute('data-direction');
            if (!direction) {
                const onclickAttr = btn.getAttribute('onclick');
                if (onclickAttr) {
                    const match = onclickAttr.match(/moveToDirection\(['"]([^'"]+)['"]\)/);
                    if (match && match[1]) {
                        direction = match[1];
                    }
                }
            }
            
            // onclick属性を無効化し、JavaScript側のイベントリスナーのみ使用
            btn.addEventListener('click', (e) => {
                console.log('🚀 [TOWN] Movement button clicked via event listener:', e.target);
                
                // onclick属性の実行を防ぐ
                e.preventDefault();
                e.stopPropagation();
                
                if (direction) {
                    console.log('🚀 [TOWN] Moving to direction:', direction);
                    this.moveToDirection(direction);
                } else {
                    console.error('🚀 [TOWN] No direction found for button');
                }
            });
            
            // onclick属性を削除して完全に無効化
            btn.removeAttribute('onclick');
            console.log('🚀 [TOWN] Removed onclick attribute from button');
        });
        
        this.eventListenersAttached = true;
        console.log('🚀 [TOWN] Town event listeners setup completed');
    }

    async moveToDirection(direction) {
        console.log('🚀 [TOWN] TownStateManager.moveToDirection called with:', direction);
        console.log('🚀 [TOWN] Current game state:', this.gameManager?.gameState);
        console.log('🚀 [TOWN] Current game data:', this.gameManager?.gameData);
        
        // 移動中の場合は処理をスキップ
        if (this.isMoving) {
            console.log('🚀 [TOWN] Move already in progress, ignoring duplicate call');
            return;
        }
        
        this.isMoving = true;
        this.gameManager.showLoading('移動中...');
        
        try {
            console.log('🚀 [TOWN] Making API request to /game/move-to-direction');
            const result = await this.gameManager.makeRequest('/game/move-to-direction', 'POST', {
                direction: direction
            });

            this.gameManager.hideLoading();

            console.log('🚀 [TOWN] Move to direction result:', result);

            if (result && result.success) {
                // Transition to road state using seamless transition
                console.log('🚀 [TOWN] Move successful, starting seamless transition to road');
                this.gameManager.showNotification('移動を開始しました', 'success');
                
                // シームレス遷移で道画面に移動
                const newGameData = {
                    gameState: 'road',
                    player: result.player || this.gameManager.gameData.player,
                    currentLocation: result.currentLocation,
                    nextLocation: result.nextLocation
                };
                
                console.log('🚀 [TOWN] Starting seamless transition with data:', newGameData);
                setTimeout(async () => {
                    try {
                        await this.gameManager.transitionToState('road', newGameData);
                        // 遷移成功後にフラグをリセット（deactivate()でもリセットされるが保険として）
                        this.isMoving = false;
                    } catch (error) {
                        console.error('🚀 [TOWN] Seamless transition failed:', error);
                        this.isMoving = false; // エラー時もフラグリセット
                    }
                }, 500);
            } else {
                // Show error message
                const errorMessage = result?.message || result?.error || '移動に失敗しました';
                console.error('🚀 [TOWN] Move to direction failed:', result);
                this.gameManager.showNotification(errorMessage, 'error');
                this.isMoving = false; // フラグリセット
            }
        } catch (error) {
            console.error('🚀 [TOWN] Exception in moveToDirection:', error);
            this.gameManager.hideLoading();
            this.gameManager.showNotification('移動中にエラーが発生しました: ' + error.message, 'error');
            this.isMoving = false; // フラグリセット
        }
    }

    /**
     * 状態非アクティブ化（シームレス遷移用）
     */
    async deactivate() {
        console.log('🚀 [TRANSITION] Deactivating TownStateManager');
        
        // 町の状態をクリーンアップ
        this.isMoving = false; // 移動フラグをリセット
        this.eventListenersAttached = false; // イベントリスナーフラグをリセット
        
        console.log('🚀 [TRANSITION] TownStateManager deactivated');
    }

    /**
     * 状態アクティブ化（シームレス遷移用）
     */
    async activate(gameData) {
        console.log('🚀 [TRANSITION] Activating TownStateManager with data:', gameData);
        
        // 移動フラグを確実にリセット
        this.isMoving = false;
        this.eventListenersAttached = false;
        
        // 少し遅延を入れてDOMが完全に更新されてから再セットアップ
        setTimeout(() => {
            this.setupTownEventListeners();
        }, 100);
        
        console.log('🚀 [TRANSITION] TownStateManager activated');
    }
}

// Road State Manager
class RoadStateManager {
    constructor(gameManager) {
        this.gameManager = gameManager;
        this.diceResult = null;
        this.availableSteps = 0;
        this.currentSteps = 0; // Track current available steps (like old system)
        this.isMoving = false; // 移動中フラグ
        this.eventListenersAttached = false; // イベントリスナー重複防止フラグ
        this.autoMoveEnabled = localStorage.getItem('autoMoveEnabled') === 'true';
        
        // Initialize auto-move toggle state
        this.initializeAutoMoveToggle();
    }
    
    initializeAutoMoveToggle() {
        const toggle = document.getElementById('auto-move-toggle');
        if (toggle) {
            toggle.checked = this.autoMoveEnabled;
        }
    }

    initialize(gameData) {
        console.log('Initializing Road State Manager');
        console.log('Road state game data:', gameData);
        
        // Initialize with hidden movement controls (like backup)
        this.hideMovementControls();
        this.hideDiceResult();
        
        this.setupRoadEventListeners();
        this.updateProgressBar(gameData.player?.game_position || 0);
        
        // Check if player is at boundary position (can move to next location)
        const position = gameData.player?.game_position || 0;
        console.log('🚀 [DEBUG] Road initialization - Player position:', position);
        console.log('🚀 [DEBUG] Road initialization - Next location data:', gameData.nextLocation);
        
        if (position === 0 || position === 50 || position === 100) {
            console.log('🚀 [DEBUG] Player at boundary position (' + position + '), showing next location button');
            
            // Ensure the gameManager has the latest nextLocation data
            if (this.gameManager && gameData.nextLocation) {
                this.gameManager.gameData.nextLocation = gameData.nextLocation;
            }
            
            // Use a small delay to ensure DOM is ready
            setTimeout(() => {
                this.showNextLocationButton(gameData.nextLocation);
            }, 100);
        } else {
            console.log('🚀 [DEBUG] Player in middle of road (position: ' + position + '), hiding next location button');
            this.hideNextLocationButton();
        }
    }

    setupRoadEventListeners() {
        console.log('🚀 [ROAD] Setting up road event listeners...');
        
        // イベントリスナーが既に設定済みの場合はスキップ
        if (this.eventListenersAttached) {
            console.log('🚀 [ROAD] Event listeners already attached, skipping setup');
            return;
        }
        
        // Dice roll button
        const rollDiceBtn = document.getElementById('roll-dice');
        if (rollDiceBtn) {
            console.log('Found roll-dice button, attaching event listener');
            rollDiceBtn.addEventListener('click', () => this.rollDice());
        } else {
            console.warn('roll-dice button not found!');
        }

        // Movement buttons
        const movementBtns = document.querySelectorAll('.movement-btn');
        console.log('Found movement buttons:', movementBtns.length);
        
        movementBtns.forEach(btn => {
            const direction = btn.dataset.direction;
            console.log('Setting up movement button for direction:', direction);
            btn.addEventListener('click', (e) => {
                const btnDirection = e.currentTarget.dataset.direction;
                console.log('Movement button clicked:', btnDirection);
                this.move(btnDirection);
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
        
        this.eventListenersAttached = true;
        console.log('🚀 [ROAD] Road event listeners setup completed');
    }

    async rollDice() {
        console.log('Rolling dice...');
        
        const rollButton = document.getElementById('roll-dice');
        if (rollButton) {
            rollButton.disabled = true;
            rollButton.textContent = 'サイコロを振っています...';
        }

        try {
            const response = await fetch('/game/roll-dice', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Dice roll response:', data);

            if (data.success === false) {
                // Handle server error response
                const errorMessage = data.error || 'サイコロを振ることができませんでした';
                if (this.gameManager && this.gameManager.showNotification) {
                    this.gameManager.showNotification(errorMessage, 'warning');
                } else {
                    alert(errorMessage);
                }
                return;
            }

            if (data) {
                this.diceResult = data;
                this.availableSteps = data.final_movement;
                this.currentSteps = data.final_movement; // Set current steps for movement tracking
                
                this.displayDiceResult(data);
                this.showMovementControls();
                
                console.log('Dice rolled. Available steps:', this.currentSteps);
                
                if (this.gameManager && this.gameManager.showNotification) {
                    this.gameManager.showNotification(`${this.availableSteps}歩移動できます`, 'success');
                } else {
                    console.log(`${this.availableSteps}歩移動できます`);
                }
            }
        } catch (error) {
            console.error('Dice roll error:', error);
            if (this.gameManager && this.gameManager.showNotification) {
                this.gameManager.showNotification('サイコロを振ることができませんでした', 'error');
            } else {
                alert('サイコロを振ることができませんでした');
            }
        } finally {
            if (rollButton) {
                rollButton.disabled = false;
                rollButton.textContent = 'サイコロを振る';
            }
        }
    }

    displayDiceResult(diceData) {
        console.log('Displaying dice result:', diceData);
        
        // Show dice visually
        const diceDisplay = document.getElementById('dice-result');
        const allDice = document.getElementById('all-dice');
        
        if (diceDisplay && allDice) {
            allDice.innerHTML = '';
            
            // Use dice_rolls (actual DTO property) instead of dice_results
            if (diceData.dice_rolls && Array.isArray(diceData.dice_rolls)) {
                diceData.dice_rolls.forEach(result => {
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
            const baseTotalEl = document.getElementById('base-total');
            const bonusEl = document.getElementById('bonus');
            const finalMovementEl = document.getElementById('final-movement');
            
            if (baseTotalEl) baseTotalEl.textContent = diceData.base_total || 0;
            if (bonusEl) bonusEl.textContent = diceData.bonus || 0;
            if (finalMovementEl) finalMovementEl.textContent = diceData.final_movement || 0;
            
            diceTotal.classList.remove('hidden');
        }
    }

    showMovementControls() {
        console.log('Showing movement controls...');
        const movementControls = document.getElementById('movement-controls');
        if (movementControls) {
            movementControls.classList.remove('hidden');
            console.log('Movement controls revealed');
        } else {
            console.warn('Movement controls element not found');
        }

        // Enable movement buttons - IMPORTANT: This was missing!
        this.enableMovementButtons();
        console.log('Movement buttons enabled');

        // Update available steps display
        const availableStepsEl = document.getElementById('available-steps');
        if (availableStepsEl) {
            availableStepsEl.textContent = this.availableSteps;
            console.log('Available steps updated to:', this.availableSteps);
        }

        // Update movement direction display
        const movementDirectionEl = document.getElementById('movement-direction');
        if (movementDirectionEl) {
            movementDirectionEl.textContent = '移動準備完了';
        }
    }

    async move(direction) {
        console.log('Move called with direction:', direction, 'currentSteps:', this.currentSteps);
        
        // 移動中の場合は処理をスキップ
        if (this.isMoving) {
            console.log('Move already in progress, ignoring duplicate call');
            return;
        }
        
        if (this.currentSteps <= 0) {
            const message = '先にサイコロを振ってください！';
            if (this.gameManager && this.gameManager.showNotification) {
                this.gameManager.showNotification(message, 'warning');
            } else {
                alert(message);
            }
            return;
        }

        // 移動処理開始フラグを設定
        this.isMoving = true;
        
        // Disable movement buttons during movement
        this.disableMovementButtons();
        
        try {
            const response = await fetch('/game/move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    direction: direction,
                    steps: this.currentSteps // Use currentSteps like backup
                })
            });

            const data = await response.json();
            console.log('Move response:', data);
            
            if (data.success) {
                // Update game display
                this.updateProgressBar(data.position);
                
                // Handle encounters
                if (data.encounter && data.monster) {
                    this.handleEncounter(data.monster);
                    return;
                }
                
                // Check if reached next location (boundary positions: 0, 50, 100)
                if (data.position === 0 || data.position === 50 || data.position === 100) {
                    console.log('🚀 [DEBUG] Reached boundary position (' + data.position + '), showing next location button');
                    console.log('🚀 [DEBUG] nextLocation data:', data.nextLocation);
                    
                    // Force update the game data with the latest nextLocation
                    if (this.gameManager && data.nextLocation) {
                        this.gameManager.gameData.nextLocation = data.nextLocation;
                    }
                    
                    // Use a small delay to ensure DOM is ready and any other operations have completed
                    setTimeout(() => {
                        this.showNextLocationButton(data.nextLocation);
                    }, 50);
                    
                    // Hide movement controls when at boundary
                    this.hideMovementControls();
                    this.hideDiceResult();
                } else {
                    // Still in the middle of the road, keep controls visible for next dice roll
                    console.log('🚀 [DEBUG] Still on road, position:', data.position);
                    
                    // Hide next location button when not at boundary
                    this.hideNextLocationButton();
                    
                    // Hide dice result but keep movement controls for next roll
                    this.hideDiceResult();
                    // Don't hide movement controls immediately, wait for next dice roll
                }
                
                // 移動成功の通知 - 境界到達時は通知しない（moveToNext()で統一）
                if ((data.position !== 0 && data.position !== 50 && data.position !== 100) && this.gameManager && this.gameManager.showNotification) {
                    this.gameManager.showNotification('移動しました', 'success');
                }
                
                // Force a comprehensive UI state update to ensure consistency
                setTimeout(() => {
                    console.log('🚀 [DEBUG] Final UI state check after move...');
                    const finalPosition = data.position;
                    const isBoundary = finalPosition === 0 || finalPosition === 50 || finalPosition === 100;
                    const nextLocationElement = document.getElementById('next-location-info');
                    
                    console.log('🚀 [DEBUG] Final check - Position:', finalPosition, 'IsBoundary:', isBoundary, 'Element found:', !!nextLocationElement);
                    
                    if (isBoundary && data.nextLocation && nextLocationElement) {
                        const isCurrentlyVisible = !nextLocationElement.classList.contains('hidden') && 
                                                 nextLocationElement.style.display !== 'none' &&
                                                 nextLocationElement.offsetHeight > 0;
                        
                        console.log('🚀 [DEBUG] Button currently visible:', isCurrentlyVisible);
                        
                        if (!isCurrentlyVisible) {
                            console.log('🚀 [DEBUG] Button should be visible but is not, forcing show...');
                            this.showNextLocationButton(data.nextLocation);
                        }
                    }
                }, 200);
            } else {
                alert(data.message || '移動に失敗しました');
                this.enableMovementButtons();
            }
        } catch (error) {
            console.error('Move error:', error);
            alert('移動中にエラーが発生しました: ' + error.message);
            this.enableMovementButtons();
        } finally {
            // 移動処理完了フラグをリセット
            this.isMoving = false;
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
        console.log('Hiding movement controls...');
        const movementControls = document.getElementById('movement-controls');
        if (movementControls) {
            movementControls.classList.add('hidden');
            console.log('Movement controls hidden');
        }
        
        // Reset button states when hiding controls
        // This ensures buttons are in proper state for next time
        this.enableMovementButtons();
        console.log('Movement buttons reset to enabled state (for next use)');
    }
    
    hideDiceResult() {
        const diceResult = document.getElementById('dice-result');
        const diceTotal = document.getElementById('dice-total');
        if (diceResult) {
            diceResult.classList.add('hidden');
        }
        if (diceTotal) {
            diceTotal.classList.add('hidden');
        }
        this.currentSteps = 0; // Reset current steps like backup
        this.availableSteps = 0; // Also reset available steps
        console.log('Dice result hidden, currentSteps and availableSteps reset to 0');
        
        // Update movement direction display
        const movementDirectionEl = document.getElementById('movement-direction');
        if (movementDirectionEl) {
            movementDirectionEl.textContent = '待機中';
        }
    }
    
    disableMovementButtons() {
        console.log('Disabling movement buttons...');
        const moveLeft = document.getElementById('move-left');
        const moveRight = document.getElementById('move-right');
        
        if (moveLeft) {
            moveLeft.disabled = true;
            console.log('Left movement button disabled');
        }
        
        if (moveRight) {
            moveRight.disabled = true;
            console.log('Right movement button disabled');
        }
    }
    
    enableMovementButtons() {
        console.log('Enabling movement buttons...');
        const moveLeft = document.getElementById('move-left');
        const moveRight = document.getElementById('move-right');
        
        if (moveLeft) {
            moveLeft.disabled = false;
            console.log('Left movement button enabled');
        } else {
            console.warn('Left movement button not found');
        }
        
        if (moveRight) {
            moveRight.disabled = false;
            console.log('Right movement button enabled');
        } else {
            console.warn('Right movement button not found');
        }
    }

    showNextLocationButton(nextLocationData) {
        console.log('🚀 [DEBUG] showNextLocationButton called with data:', nextLocationData);
        const nextLocation = document.getElementById('next-location-info');
        console.log('🚀 [DEBUG] Found next-location-info element:', nextLocation);
        
        if (nextLocation) {
            console.log('🚀 [DEBUG] Element classes before:', nextLocation.className);
            console.log('🚀 [DEBUG] Element style.display before:', nextLocation.style.display);
            
            // Force show the element
            nextLocation.classList.remove('hidden');
            nextLocation.style.display = 'block'; // Force block display to override any inline styles
            nextLocation.style.visibility = 'visible'; // Ensure visibility
            nextLocation.style.opacity = '1'; // Ensure opacity
            
            console.log('🚀 [DEBUG] Element classes after:', nextLocation.className);
            console.log('🚀 [DEBUG] Element style.display after:', nextLocation.style.display);
            console.log('🚀 [DEBUG] Next location button revealed');
            
            // Update destination name if data is provided
            if (nextLocationData && nextLocationData.name) {
                console.log('🚀 [DEBUG] Updating destination name to:', nextLocationData.name);
                const destinationName = nextLocation.querySelector('.destination-name');
                if (destinationName) {
                    destinationName.textContent = nextLocationData.name;
                    console.log('🚀 [DEBUG] Destination name updated');
                } else {
                    console.warn('🚀 [DEBUG] Destination name element not found');
                }
                
                const moveButton = nextLocation.querySelector('#move-to-next');
                console.log('🚀 [DEBUG] Found move-to-next button:', moveButton);
                if (moveButton) {
                    // Ensure button is enabled and visible
                    moveButton.disabled = false;
                    moveButton.style.display = '';
                    moveButton.style.pointerEvents = 'auto';
                    
                    // Update button text - try different approaches
                    const buttonTextSpan = moveButton.querySelector('.btn-text');
                    if (buttonTextSpan) {
                        buttonTextSpan.textContent = `${nextLocationData.name}に移動`;
                        console.log('🚀 [DEBUG] Button text updated via .btn-text span');
                        
                        // ボタン状態の詳細チェック
                        console.log('🚀 [DEBUG] ======= BUTTON STATE ANALYSIS =======');
                        console.log('🚀 [DEBUG] Button element:', moveButton);
                        console.log('🚀 [DEBUG] Button disabled:', moveButton.disabled);
                        console.log('🚀 [DEBUG] Button style.display:', moveButton.style.display);
                        console.log('🚀 [DEBUG] Button style.visibility:', moveButton.style.visibility);
                        console.log('🚀 [DEBUG] Button style.pointerEvents:', moveButton.style.pointerEvents);
                        console.log('🚀 [DEBUG] Button onclick:', moveButton.onclick);
                        console.log('🚀 [DEBUG] Button getAttribute onclick:', moveButton.getAttribute('onclick'));
                        
                        // クリックテスト用のイベントリスナーも追加
                        moveButton.addEventListener('click', function(e) {
                            console.log('🚀 [DEBUG] Button click event listener fired!');
                            console.log('🚀 [DEBUG] Event:', e);
                        });
                        
                        console.log('🚀 [DEBUG] ======= END BUTTON ANALYSIS =======');
                        
                        // ボタンクリック可能性テスト（実際にはクリックしない）
                        console.log('🚀 [DEBUG] Testing button clickability...');
                        const rect = moveButton.getBoundingClientRect();
                        console.log('🚀 [DEBUG] Button position:', {
                            x: rect.x, y: rect.y, 
                            width: rect.width, height: rect.height,
                            visible: rect.width > 0 && rect.height > 0
                        });
                        
                        // Z-index確認
                        const computedStyle = window.getComputedStyle(moveButton);
                        console.log('🚀 [DEBUG] Button computed style:', {
                            zIndex: computedStyle.zIndex,
                            position: computedStyle.position,
                            pointerEvents: computedStyle.pointerEvents,
                            display: computedStyle.display,
                            opacity: computedStyle.opacity
                        });
                    } else {
                        // Try updating the text content after the icon
                        const iconSpan = moveButton.querySelector('.btn-icon');
                        if (iconSpan && iconSpan.nextSibling) {
                            iconSpan.nextSibling.textContent = ` ${nextLocationData.name}に移動`;
                            console.log('🚀 [DEBUG] Button text updated via nextSibling');
                        } else {
                            // Fallback: replace all text content
                            moveButton.innerHTML = `<span class="btn-icon">🚀</span> ${nextLocationData.name}に移動`;
                            console.log('🚀 [DEBUG] Button text updated via innerHTML');
                        }
                    }
                }
            }
            
            // Force a layout reflow to ensure visibility
            setTimeout(() => {
                const finalCheck = document.getElementById('next-location-info');
                console.log('🚀 [DEBUG] Final visibility check:', {
                    element: finalCheck,
                    isVisible: finalCheck && finalCheck.offsetHeight > 0,
                    offsetHeight: finalCheck?.offsetHeight,
                    classList: finalCheck?.className,
                    displayStyle: finalCheck?.style.display
                });
            }, 100);
            
        } else {
            console.error('🚀 [DEBUG] next-location-info element not found in DOM!');
            
            // Attempt to find similar elements for debugging
            const allNextElements = document.querySelectorAll('[id*="next"]');
            console.log('🚀 [DEBUG] All elements with "next" in ID:', allNextElements);
            
            const allLocationElements = document.querySelectorAll('[id*="location"]');
            console.log('🚀 [DEBUG] All elements with "location" in ID:', allLocationElements);
        }
    }

    hideNextLocationButton() {
        console.log('🚀 [DEBUG] hideNextLocationButton called');
        const nextLocation = document.getElementById('next-location-info');
        if (nextLocation) {
            console.log('🚀 [DEBUG] Hiding next location button');
            nextLocation.classList.add('hidden');
            nextLocation.style.display = 'none';
            console.log('🚀 [DEBUG] Next location button hidden');
        } else {
            console.error('🚀 [DEBUG] next-location-info element not found in DOM when trying to hide!');
        }
    }

    async moveToNext() {
        console.log('🚀 [DEBUG] RoadStateManager.moveToNext() called');
        
        // 二重実行防止
        if (this.isMoving) {
            console.log('🚀 [DEBUG] Move already in progress, ignoring duplicate call');
            return;
        }
        this.isMoving = true;
        
        let result = null; // スコープ修正: try-catch外で宣言
        
        try {
            console.log('🚀 [DEBUG] Current gameManager state:', {
                gameState: this.gameManager?.gameState,
                currentData: this.gameManager?.gameData
            });
            
            // ローカルストレージとセッションストレージの状態をログ
            console.log('🚀 [DEBUG] Storage before move:');
            console.log('  - localStorage keys:', Object.keys(localStorage));
            console.log('  - sessionStorage keys:', Object.keys(sessionStorage));
            
            // 現在のページ状態をログ
            console.log('🚀 [DEBUG] Current page state:', {
                url: window.location.href,
                gameElements: {
                    progressBar: document.getElementById('progress-fill')?.style.width,
                    nextLocationInfo: document.getElementById('next-location-info')?.classList.contains('hidden'),
                    movementControls: document.getElementById('movement-controls')?.classList.contains('hidden')
                }
            });
            
            this.gameManager.showLoading('次の場所へ移動中...');

            console.log('🚀 [DEBUG] Making API request to /game/move-to-next');
            result = await this.gameManager.makeRequest('/game/move-to-next');
            console.log('🚀 [DEBUG] API response received:', result);

            this.gameManager.hideLoading();
        } catch (error) {
            console.error('❌ [ERROR] moveToNext() failed:', error);
            this.gameManager.hideLoading();
            this.gameManager.showNotification('移動処理でエラーが発生しました: ' + error.message, 'error');
            this.isMoving = false; // フラグ解除
            return;
        }

        console.log('🚀 [DEBUG] Move to next result:', result);

        if (result && result.success) {
            console.log('🚀 [DEBUG] Move to next successful, updating UI dynamically...');
            
            // 動的UI更新: ページリロードを避ける
            console.log('🚀 [DEBUG] Dynamic UI update with result:', result);
            
            this.gameManager.showNotification('移動しました', 'success');
            
            // 移動結果に基づいて適切な画面に遷移（シームレス遷移使用）
            if (result.currentLocation && result.currentLocation.type === 'town') {
                console.log('🚀 [DEBUG] Seamless transition to town view...');
                
                // シームレス遷移で町画面に移動
                const newGameData = {
                    gameState: 'town',
                    player: result.player || this.gameManager.gameData.player,
                    currentLocation: result.currentLocation,
                    nextLocation: result.nextLocation
                };
                
                // UnifiedGameManagerのシームレス遷移を使用
                await this.gameManager.transitionToState('town', newGameData);
                
            } else if (result.currentLocation && result.currentLocation.type === 'road') {
                console.log('🚀 [DEBUG] Seamless transition to road view...');
                
                // 道画面への遷移
                const newGameData = {
                    gameState: 'road',
                    player: result.player || this.gameManager.gameData.player,
                    currentLocation: result.currentLocation,
                    nextLocation: result.nextLocation
                };
                
                await this.gameManager.transitionToState('road', newGameData);
                
            } else {
                console.log('🚀 [DEBUG] Unexpected result, using default state...');
                // フォールバック: 不明な状態の場合は町に遷移
                const newGameData = {
                    gameState: 'town',
                    player: result.player || this.gameManager.gameData.player
                };
                
                await this.gameManager.transitionToState('town', newGameData);
            }
        } else {
            // エラーハンドリング追加
            const errorMessage = result?.message || result?.error || '移動に失敗しました';
            console.error('🚀 [DEBUG] Move to next failed:', result);
            this.gameManager.showNotification(errorMessage, 'error');
            this.isMoving = false; // フラグ解除
        }
    }

    async performGathering() {
        console.log('Performing gathering');
        
        const gatheringBtn = document.getElementById('gathering-btn');
        if (gatheringBtn) {
            gatheringBtn.disabled = true;
            gatheringBtn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">採集中...</span>';
        }

        try {
            const response = await this.gameManager.makeRequest('/api/gathering/gather', 'POST');
            
            if (response && response.success) {
                // 成功時の処理
                let message = response.message;
                if (response.item && response.quantity) {
                    message = `${response.item}を${response.quantity}個採集しました！`;
                }
                
                this.gameManager.showNotification(message, 'success');
                
                // プレイヤー情報を更新
                if (response.remaining_sp !== undefined) {
                    this.gameManager.updatePlayerStats({
                        sp: response.remaining_sp
                    });
                }
                
                // 経験値情報を表示
                if (response.experience_gained > 0) {
                    setTimeout(() => {
                        this.gameManager.showNotification(`採集経験値: +${response.experience_gained}`, 'info');
                    }, 1000);
                }
                
            } else {
                // 失敗時の処理
                const errorMessage = response && response.message ? response.message : '採集に失敗しました。';
                this.gameManager.showNotification(errorMessage, 'warning');
                
                // SPなどの更新情報があれば反映
                if (response && response.remaining_sp !== undefined) {
                    this.gameManager.updatePlayerStats({
                        sp: response.remaining_sp
                    });
                }
            }
            
        } catch (error) {
            console.error('Gathering error:', error);
            let errorMessage = '採集中にエラーが発生しました。';
            
            if (error.message && error.message.includes('error')) {
                const errorData = JSON.parse(error.message);
                errorMessage = errorData.error || errorMessage;
            }
            
            this.gameManager.showNotification(errorMessage, 'error');
        } finally {
            // ボタンを元に戻す
            if (gatheringBtn) {
                gatheringBtn.disabled = false;
                gatheringBtn.innerHTML = '<span class="btn-icon">🌿</span><span class="btn-text">採集する</span>';
            }
        }
    }

    handleEncounter(monster) {
        console.log('🚀 [ENCOUNTER] Monster encountered:', monster);
        
        // モンスター情報をセッションに保存
        if (this.gameManager && this.gameManager.makeRequest) {
            // バトル開始の準備
            this.gameManager.showNotification(`${monster.name}が現れた！`, 'warning');
            
            setTimeout(() => {
                // バトル画面に遷移（新しいシームレス遷移システム使用）
                const battleData = {
                    gameState: 'battle',
                    monster: monster,
                    player: this.gameManager.gameData.player
                };
                
                this.gameManager.transitionToState('battle', battleData);
            }, 2000);
        } else {
            // フォールバック: 古い遷移方法
            this.gameManager.showNotification(`${monster.name}が現れた！`, 'warning');
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
            
            // シームレス遷移で適切な状態に移動
            const newGameData = {
                gameState: result.currentLocation?.type || 'road',
                player: result.player || this.gameManager.gameData.player,
                currentLocation: result.currentLocation,
                nextLocation: result.nextLocation
            };
            
            setTimeout(async () => {
                await this.gameManager.transitionToState(newGameData.gameState, newGameData);
            }, 500);
        }
    }

    /**
     * 状態非アクティブ化（シームレス遷移用）
     */
    async deactivate() {
        console.log('🚀 [TRANSITION] Deactivating RoadStateManager');
        
        // 道の状態をクリーンアップ
        this.hideMovementControls();
        this.hideDiceResult();
        
        // フラグリセット
        this.isMoving = false;
        this.eventListenersAttached = false; // イベントリスナーフラグをリセット
        this.diceResult = null;
        this.availableSteps = 0;
        this.currentSteps = 0;
        
        // ボタンの無効化
        this.disableMovementButtons();
        const rollButton = document.getElementById('roll-dice');
        if (rollButton) {
            rollButton.disabled = true;
        }
        
        console.log('🚀 [TRANSITION] RoadStateManager deactivated');
    }

    /**
     * 状態アクティブ化（シームレス遷移用）
     */
    async activate(gameData) {
        console.log('🚀 [TRANSITION] Activating RoadStateManager with data:', gameData);
        
        // 状態フラグをリセット
        this.isMoving = false;
        this.eventListenersAttached = false;
        
        // プレイヤー状態更新
        if (gameData.player) {
            this.updateProgressBar(gameData.player.game_position || 0);
        }
        
        // 境界位置チェック
        const position = gameData.player?.game_position || 0;
        console.log('🚀 [DEBUG] RoadStateManager activate - Position:', position, 'NextLocation:', gameData.nextLocation);
        
        if (position === 0 || position === 50 || position === 100) {
            console.log('🚀 [DEBUG] Activate: Player at boundary, showing next location button');
            setTimeout(() => {
                this.showNextLocationButton(gameData.nextLocation);
            }, 150);
        } else {
            console.log('🚀 [DEBUG] Activate: Player not at boundary, hiding next location button');
            this.hideNextLocationButton();
        }
        
        // ボタンの有効化
        const rollButton = document.getElementById('roll-dice');
        if (rollButton) {
            rollButton.disabled = false;
            rollButton.textContent = 'サイコロを振る';
        }
        
        // イベントリスナーを再セットアップ
        setTimeout(() => {
            this.setupRoadEventListeners();
        }, 100);
        
        console.log('🚀 [TRANSITION] RoadStateManager activated');
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
        this.eventListenersAttached = false; // イベントリスナー重複防止フラグ
    }

    initialize(gameData) {
        console.log('Initializing Battle State');
        
        this.battleData = gameData.battle;
        this.setupBattleEventListeners();
        this.updateSkillMenu(gameData.character);
        this.initializeEscapeRate();
    }

    setupBattleEventListeners() {
        console.log('🚀 [BATTLE] Setting up battle event listeners');
        
        // イベントリスナーが既に設定済みの場合はスキップ
        if (this.eventListenersAttached) {
            console.log('🚀 [BATTLE] Event listeners already attached, skipping setup');
            return;
        }
        
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
        
        this.eventListenersAttached = true;
        console.log('🚀 [BATTLE] Battle event listeners setup completed');
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
            // シームレス遷移で戦闘終了
            const newGameData = {
                gameState: 'town',
                player: this.gameManager.gameData.player
            };
            await this.gameManager.transitionToState('town', newGameData);
            return;
        }

        const result = await this.gameManager.makeRequest('/battle/end');

        if (result && result.success) {
            // シームレス遷移で戦闘終了
            const newGameData = {
                gameState: result.currentLocation?.type || 'town',
                player: result.player || this.gameManager.gameData.player,
                currentLocation: result.currentLocation
            };
            await this.gameManager.transitionToState(newGameData.gameState, newGameData);
        } else {
            // エラー時もシームレス遷移で町に戻る
            const newGameData = {
                gameState: 'town',
                player: this.gameManager.gameData.player
            };
            await this.gameManager.transitionToState('town', newGameData);
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

    /**
     * 状態非アクティブ化（シームレス遷移用）
     */
    async deactivate() {
        console.log('🚀 [TRANSITION] Deactivating BattleStateManager');
        
        // 戦闘状態をクリーンアップ
        this.battleData = null;
        this.eventListenersAttached = false; // イベントリスナーフラグをリセット
        
        // スキルメニューを隠す
        const skillMenu = document.getElementById('skill-menu');
        if (skillMenu) {
            skillMenu.classList.add('hidden');
        }
        
        console.log('🚀 [TRANSITION] BattleStateManager deactivated');
    }

    /**
     * 状態アクティブ化（シームレス遷移用）
     */
    async activate(gameData) {
        console.log('🚀 [TRANSITION] Activating BattleStateManager with data:', gameData);
        
        // 戦闘データを更新
        this.battleData = gameData.battle;
        
        // 戦闘UIを初期化
        if (gameData.battle) {
            this.updateBattleUI();
        }
        
        console.log('🚀 [TRANSITION] BattleStateManager activated');
    }
}

// Global functions for backward compatibility
let gameManager;

// グローバル重複実行防止フラグ
let globalMoveInProgress = false;
let globalActionInProgress = false;

function initializeUnifiedGame(gameData) {
    gameManager = new UnifiedGameManager();
    gameManager.initializeGame(gameData);
}

// Expose individual functions for legacy support
function rollDice() {
    console.log('Global rollDice called');
    console.log('gameManager:', gameManager);
    console.log('roadManager:', gameManager?.roadManager);
    
    if (gameManager?.roadManager) {
        gameManager.roadManager.rollDice();
    } else {
        console.warn('gameManager or roadManager not available');
        // Fallback: direct roll dice call
        testRollDice();
    }
}

// Debug/test function for direct dice rolling
async function testRollDice() {
    console.log('Testing direct dice roll...');
    
    try {
        const response = await fetch('/game/roll-dice', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();
        console.log('Direct dice test result:', data);
        
        if (data.success === false) {
            alert(data.error || 'サイコロを振ることができませんでした');
        } else {
            alert(`サイコロ結果: ${data.dice_rolls?.join(', ')} = ${data.final_movement}歩`);
        }
    } catch (error) {
        console.error('Direct dice test error:', error);
        alert('エラーが発生しました: ' + error.message);
    }
}

function move(direction) {
    if (gameManager?.roadManager) {
        gameManager.roadManager.move(direction);
    }
}

async function moveToNext() {
    console.log('🚀 [DEBUG] Global moveToNext() called');
    
    // グローバル重複実行防止
    if (globalMoveInProgress) {
        console.log('🚀 [DEBUG] Global move already in progress, ignoring call');
        return;
    }
    
    globalMoveInProgress = true;
    
    try {
        console.log('🚀 [DEBUG] gameManager available:', !!gameManager);
        console.log('🚀 [DEBUG] roadManager available:', !!gameManager?.roadManager);
        
        if (gameManager?.roadManager) {
            console.log('🚀 [DEBUG] Calling roadManager.moveToNext()');
            await gameManager.roadManager.moveToNext();
        } else {
            console.error('❌ gameManager or roadManager not available');
            console.error('gameManager:', gameManager);
            console.error('roadManager:', gameManager?.roadManager);
            
            // ユーザーへのエラー表示
            if (gameManager) {
                gameManager.showNotification('移動システムの初期化に失敗しました。ページを再読み込みしてください。', 'error');
            } else {
                alert('ゲームマネージャーが初期化されていません。ページを再読み込みしてください。');
            }
        }
    } catch (error) {
        console.error('🚀 [DEBUG] Error in global moveToNext:', error);
        if (gameManager) {
            gameManager.showNotification('移動処理でエラーが発生しました。', 'error');
        }
    } finally {
        globalMoveInProgress = false;
    }
}

async function moveToDirection(direction) {
    console.log('🚀 [GLOBAL] Global moveToDirection called with:', direction);
    
    // グローバル重複実行防止
    if (globalActionInProgress) {
        console.log('🚀 [GLOBAL] Global action already in progress, ignoring call');
        return;
    }
    
    globalActionInProgress = true;
    
    try {
        console.log('🚀 [GLOBAL] gameManager:', gameManager);
        console.log('🚀 [GLOBAL] gameManager.gameState:', gameManager?.gameState);
        console.log('🚀 [GLOBAL] townManager:', gameManager?.townManager);
        
        // 重複実行チェック（TownStateManager側でも行うが、二重安全装置として）
        if (gameManager?.townManager?.isMoving) {
            console.log('🚀 [GLOBAL] Move already in progress in townManager, ignoring call');
            return;
        }
        
        if (gameManager?.townManager) {
            console.log('🚀 [GLOBAL] Calling townManager.moveToDirection...');
            await gameManager.townManager.moveToDirection(direction);
        } else {
            console.error('🚀 [GLOBAL] gameManager or townManager not available');
            console.error('🚀 [GLOBAL] gameManager exists:', !!gameManager);
            console.error('🚀 [GLOBAL] townManager exists:', !!gameManager?.townManager);
            alert('ゲームマネージャーが初期化されていません。ページを再読み込みしてください。');
        }
    } catch (error) {
        console.error('🚀 [GLOBAL] Error in global moveToDirection:', error);
        if (gameManager) {
            gameManager.showNotification('移動処理でエラーが発生しました。', 'error');
        }
    } finally {
        globalActionInProgress = false;
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

function toggleAutoMove() {
    const toggle = document.getElementById('auto-move-toggle');
    
    if (toggle) {
        const isEnabled = toggle.checked;
        console.log('Auto-move toggled:', isEnabled);
        
        // Store the setting in localStorage for persistence
        localStorage.setItem('autoMoveEnabled', isEnabled ? 'true' : 'false');
        
        // Show feedback to user
        const feedback = document.createElement('div');
        feedback.className = 'toggle-feedback';
        feedback.textContent = isEnabled ? '⚡ 自動移動が有効になりました' : '⚡ 自動移動が無効になりました';
        feedback.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${isEnabled ? '#10b981' : '#6b7280'};
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            z-index: 1000;
            transition: all 0.3s ease;
        `;
        
        document.body.appendChild(feedback);
        setTimeout(() => {
            feedback.remove();
        }, 2000);
        
        // Update game manager if available
        if (gameManager && gameManager.roadManager) {
            gameManager.roadManager.autoMoveEnabled = isEnabled;
        }
    }
}

// Additional utility functions
async function showGatheringInfo() {
    if (!gameManager) {
        console.error('Game manager not available');
        return;
    }

    try {
        const response = await gameManager.makeRequest('/api/gathering/info', 'GET');
        
        if (response) {
            // 採集情報を表示する詳細なモーダルまたは通知を作成
            const gatheringInfoHtml = createGatheringInfoDisplay(response);
            
            // モーダル表示（既存の通知システムを拡張）
            showGatheringInfoModal(gatheringInfoHtml, response);
            
        } else {
            gameManager.showNotification('採集情報の取得に失敗しました。', 'error');
        }
        
    } catch (error) {
        console.error('Gathering info error:', error);
        let errorMessage = '採集情報の取得中にエラーが発生しました。';
        
        if (error.message && error.message.includes('error')) {
            const errorData = JSON.parse(error.message);
            errorMessage = errorData.error || errorMessage;
        }
        
        gameManager.showNotification(errorMessage, 'error');
    }
}

function createGatheringInfoDisplay(gatheringData) {
    const environmentName = gatheringData.environment_name || '不明なエリア';
    const locationName = gatheringData.location_name || '不明な場所';
    const skillLevel = gatheringData.skill_level || 0;
    const spCost = gatheringData.sp_cost || 0;
    const currentSp = gatheringData.current_sp || 0;
    const canGather = gatheringData.can_gather;
    
    let itemsHtml = '';
    if (gatheringData.all_items && gatheringData.all_items.length > 0) {
        itemsHtml = gatheringData.all_items.map(item => {
            const statusClass = item.can_gather ? 'available' : 'unavailable';
            const statusIcon = item.can_gather ? '✅' : '❌';
            const actualRate = item.actual_success_rate || item.base_success_rate || 0;
            
            return `
                <div class="gathering-item ${statusClass}">
                    <div class="item-header">
                        <span class="item-status">${statusIcon}</span>
                        <span class="item-name">${item.item_name}</span>
                        <span class="item-category">(${item.item_category})</span>
                    </div>
                    <div class="item-details">
                        <span class="item-requirement">必要スキル: Lv.${item.required_skill_level}</span>
                        <span class="item-success-rate">成功率: ${actualRate}%</span>
                        <span class="item-quantity">数量: ${item.quantity_range}個</span>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        itemsHtml = '<p class="no-items">このエリアでは採集できるアイテムがありません。</p>';
    }
    
    const levelRequirementHtml = gatheringData.min_level_requirement ? 
        `<p class="level-requirement">最低レベル要求: ${gatheringData.min_level_requirement} (現在レベル: ${gatheringData.player_level})</p>` : '';
    
    return `
        <div class="gathering-info-content">
            <div class="gathering-header">
                <h3>📍 ${locationName} (${environmentName})</h3>
                <div class="gathering-stats">
                    <div class="stat-item">
                        <span class="stat-label">採集スキル:</span>
                        <span class="stat-value">Lv.${skillLevel}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">消費SP:</span>
                        <span class="stat-value">${spCost} (残り: ${currentSp})</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">採集可否:</span>
                        <span class="stat-value ${canGather ? 'can-gather' : 'cannot-gather'}">
                            ${canGather ? '可能' : '不可'}
                        </span>
                    </div>
                </div>
                ${levelRequirementHtml}
            </div>
            <div class="gathering-items">
                <h4>採集可能アイテム (${gatheringData.available_items_count || 0}種類)</h4>
                ${itemsHtml}
            </div>
        </div>
    `;
}

function showGatheringInfoModal(content, data) {
    // 既存のモーダルがあれば削除
    const existingModal = document.getElementById('gathering-info-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'gathering-info-modal';
    modal.className = 'gathering-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeGatheringModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2>🌿 採集情報</h2>
                    <button class="modal-close" onclick="closeGatheringModal()">×</button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeGatheringModal()">閉じる</button>
                    ${data.can_gather ? '<button class="btn btn-success" onclick="closeGatheringModal(); performGathering()">採集開始</button>' : ''}
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // スタイルを追加
    addGatheringModalStyles();
}

function closeGatheringModal() {
    const modal = document.getElementById('gathering-info-modal');
    if (modal) {
        modal.remove();
    }
}

function addGatheringModalStyles() {
    // スタイルが既に追加されているかチェック
    if (document.getElementById('gathering-modal-styles')) {
        return;
    }
    
    const styles = document.createElement('style');
    styles.id = 'gathering-modal-styles';
    styles.textContent = `
        .gathering-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .gathering-modal .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }
        
        .gathering-modal .modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            margin: 20px;
        }
        
        .gathering-modal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .gathering-modal .modal-header h2 {
            margin: 0;
            color: #2d3748;
        }
        
        .gathering-modal .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .gathering-modal .modal-close:hover {
            background: #f7fafc;
        }
        
        .gathering-modal .modal-body {
            padding: 20px;
        }
        
        .gathering-modal .modal-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .gathering-info-content .gathering-header h3 {
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .gathering-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .gathering-stats .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .gathering-stats .stat-label {
            font-weight: bold;
            color: #4a5568;
        }
        
        .gathering-stats .can-gather {
            color: #38a169;
        }
        
        .gathering-stats .cannot-gather {
            color: #e53e3e;
        }
        
        .gathering-items h4 {
            color: #2d3748;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .gathering-item {
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .gathering-item.available {
            background: #f0fff4;
            border-color: #68d391;
        }
        
        .gathering-item.unavailable {
            background: #fffaf0;
            border-color: #fc8181;
            opacity: 0.8;
        }
        
        .gathering-item .item-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        
        .gathering-item .item-name {
            font-weight: bold;
            color: #2d3748;
        }
        
        .gathering-item .item-category {
            color: #718096;
            font-size: 12px;
        }
        
        .gathering-item .item-details {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #4a5568;
        }
        
        .level-requirement {
            color: #d69e2e;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .no-items {
            text-align: center;
            color: #718096;
            font-style: italic;
            padding: 20px;
        }
    `;
    
    document.head.appendChild(styles);
}

function takeRest() {
    if (gameManager) {
        gameManager.showNotification('少し休憩しました。HP+5', 'success');
    }
}

// Layout switching functionality
function switchLayout(layout) {
    // Add loading state
    const switcher = document.querySelector('.layout-switcher');
    if (switcher) {
        switcher.classList.add('switching');
    }
    
    // Navigate to current game URL with layout parameter
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('layout', layout);
    
    // Smooth transition
    document.body.style.opacity = '0.7';
    document.body.style.transition = 'opacity 0.3s ease';
    
    setTimeout(() => {
        window.location.href = currentUrl.toString();
    }, 150);
}

function initializeLayoutSwitcher() {
    // Add keyboard shortcut for layout switching
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + L for layout switching
        if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
            e.preventDefault();
            
            // Cycle through layouts
            const currentLayout = getCurrentLayout();
            const layouts = ['default', 'unified', 'noright'];
            const currentIndex = layouts.indexOf(currentLayout);
            const nextLayout = layouts[(currentIndex + 1) % layouts.length];
            
            switchLayout(nextLayout);
        }
    });
    
    // Add tooltips to layout buttons
    const layoutButtons = document.querySelectorAll('.layout-btn');
    layoutButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

function getCurrentLayout() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('layout') || 'default';
}

// Initialize layout switcher when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeLayoutSwitcher();
});

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
            
            // シームレス遷移で町に戻る
            const newGameData = {
                gameState: 'town',
                player: gameManager.gameData.player
            };
            
            setTimeout(async () => {
                await gameManager.transitionToState('town', newGameData);
            }, 500);
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

// グローバルエラーハンドラー
window.addEventListener('error', function(e) {
    console.error('❌ [GLOBAL ERROR]', e.error, 'at', e.filename + ':' + e.lineno);
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('❌ [UNHANDLED PROMISE REJECTION]', e.reason);
});

// Page load debugging
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [DEBUG] =============== PAGE LOADED ===============');
    console.log('🚀 [DEBUG] Current timestamp:', new Date().toISOString());
    console.log('🚀 [DEBUG] Current URL:', window.location.href);
    
    // セッションストレージから移動結果をチェック
    const debugMoveResult = sessionStorage.getItem('debug_move_result');
    if (debugMoveResult) {
        try {
            const moveData = JSON.parse(debugMoveResult);
            console.log('🚀 [DEBUG] Found previous move result in sessionStorage:', moveData);
            console.log('🚀 [DEBUG] Time since move:', Date.now() - moveData.timestamp, 'ms');
            console.log('🚀 [DEBUG] Expected location:', moveData.expectedLocation);
            
            // ページ読み込み後にサーバーデータと比較するため少し待つ
            setTimeout(() => {
                if (typeof gameData !== 'undefined') {
                    console.log('🚀 [DEBUG] Current server data location:', gameData?.currentLocation);
                    
                    // データの比較
                    if (gameData?.currentLocation) {
                        const matches = JSON.stringify(moveData.expectedLocation) === JSON.stringify(gameData.currentLocation);
                        console.log('🚀 [DEBUG] Location data matches expected:', matches);
                        if (!matches) {
                            console.warn('🚀 [DEBUG] ⚠️ MISMATCH DETECTED!');
                            console.warn('🚀 [DEBUG] Expected:', moveData.expectedLocation);
                            console.warn('🚀 [DEBUG] Actual:', gameData.currentLocation);
                        }
                    }
                }
            }, 100);
            
            // デバッグデータを削除
            sessionStorage.removeItem('debug_move_result');
        } catch (e) {
            console.error('🚀 [DEBUG] Error parsing debug move result:', e);
        }
    }
    
    // ストレージの現在の状態をログ
    console.log('🚀 [DEBUG] Storage after page load:');
    console.log('  - localStorage keys:', Object.keys(localStorage));
    console.log('  - sessionStorage keys:', Object.keys(sessionStorage));
    
    // DOM要素の初期状態をログ
    console.log('🚀 [DEBUG] DOM elements initial state:', {
        progressBar: document.getElementById('progress-fill')?.style.width,
        nextLocationInfo: document.getElementById('next-location-info')?.classList.contains('hidden'),
        movementControls: document.getElementById('movement-controls')?.classList.contains('hidden'),
        gameStateIndicator: document.querySelector('.game-state-indicator')?.className
    });
    
    console.log('🚀 [DEBUG] =============== DOM READY COMPLETE ===============');
});