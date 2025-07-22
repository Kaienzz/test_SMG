/**
 * Game Management System
 * Handles dice rolling, movement, and battle encounters
 */

class GameManager {
    constructor(gameData) {
        this.gameData = gameData;
        this.currentSteps = 0;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeDisplay();
    }

    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.handleInitialLoad();
        });
    }

    handleInitialLoad() {
        console.log('Initial load - Player position:', this.gameData.player.position, 'Type:', this.gameData.player.current_location_type);
        console.log('Initial load - Next location:', this.gameData.nextLocation);
        
        // 初期状態でUI全体を適切に設定
        const initialData = {
            currentLocation: this.gameData.currentLocation,
            position: this.gameData.player.position,
            location_type: this.gameData.player.current_location_type
        };
        this.updateGameDisplay(initialData);
        
        // 道路でposition=100または0のとき、次の場所ボタンを表示
        if (this.gameData.player.current_location_type === 'road') {
            if ((this.gameData.player.position >= 100 || this.gameData.player.position <= 0) && this.gameData.nextLocation) {
                console.log('Initial load - Showing button for road at boundary');
                this.updateNextLocationDisplay(this.gameData.nextLocation, true);
            } else {
                console.log('Initial load - Hiding button for road not at boundary');
                this.updateNextLocationDisplay(this.gameData.nextLocation, false);
            }
        } else {
            // 町にいるときは次の場所ボタンを表示
            if (this.gameData.nextLocation) {
                console.log('Initial load - Showing button for town');
                this.updateNextLocationDisplay(this.gameData.nextLocation, true);
            } else {
                console.log('Initial load - Hiding button for town (no next location)');
                this.updateNextLocationDisplay(this.gameData.nextLocation, false);
            }
        }
    }

    initializeDisplay() {
        // 初期表示の設定
    }
}

class DiceManager {
    constructor(gameManager) {
        this.gameManager = gameManager;
    }

    rollDice() {
        const rollDiceButton = document.getElementById('roll-dice');
        if (rollDiceButton) rollDiceButton.disabled = true;
        
        fetch('/game/roll-dice', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            this.handleDiceResult(data);
            
            const rollDiceButton = document.getElementById('roll-dice');
            if (rollDiceButton) rollDiceButton.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            const rollDiceButton = document.getElementById('roll-dice');
            if (rollDiceButton) rollDiceButton.disabled = false;
        });
    }

    handleDiceResult(data) {
        const allDiceDiv = document.getElementById('all-dice');
        allDiceDiv.innerHTML = '';
        
        data.dice_rolls.forEach((roll, index) => {
            const diceElement = document.createElement('div');
            diceElement.className = 'dice';
            diceElement.textContent = roll;
            allDiceDiv.appendChild(diceElement);
        });
        
        const baseTotal = document.getElementById('base-total');
        const bonus = document.getElementById('bonus');
        const finalMovement = document.getElementById('final-movement');
        if (baseTotal) baseTotal.textContent = data.base_total;
        if (bonus) bonus.textContent = data.bonus;
        if (finalMovement) finalMovement.textContent = data.final_movement;
        
        this.gameManager.currentSteps = data.final_movement;
        
        const diceResult = document.getElementById('dice-result');
        const diceTotal = document.getElementById('dice-total');
        const diceToggle = document.getElementById('dice-display-toggle');
        
        // ダイス表示の制御
        if (diceToggle && diceToggle.checked) {
            if (diceResult) diceResult.classList.remove('hidden');
        } else {
            if (diceResult) diceResult.classList.add('hidden');
        }
        
        if (diceTotal) diceTotal.classList.remove('hidden');
        
        const movableLocations = ['road', 'dungeon'];
        if (movableLocations.includes(this.gameManager.gameData.player.current_location_type)) {
            const movementControls = document.getElementById('movement-controls');
            const moveLeft = document.getElementById('move-left');
            const moveRight = document.getElementById('move-right');
            if (movementControls) movementControls.classList.remove('hidden');
            if (moveLeft) moveLeft.disabled = false;
            if (moveRight) moveRight.disabled = false;
        } else {
            alert('町にいるときはサイコロを振っても移動できません。道路やダンジョンに移動してください。');
        }
    }
}

class MovementManager {
    constructor(gameManager) {
        this.gameManager = gameManager;
    }

    move(direction) {
        console.log('Move called with direction:', direction, 'currentSteps:', this.gameManager.currentSteps);
        
        if (this.gameManager.currentSteps <= 0) {
            alert('先にサイコロを振ってください！');
            return;
        }
        
        const moveLeft = document.getElementById('move-left');
        const moveRight = document.getElementById('move-right');
        if (moveLeft) moveLeft.disabled = true;
        if (moveRight) moveRight.disabled = true;
        
        const requestData = {
            direction: direction,
            steps: this.gameManager.currentSteps
        };
        
        console.log('Sending move request:', requestData);
        
        fetch('/game/move', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Move response:', data);
            if (data.success) {
                this.handleMoveSuccess(data);
            } else {
                this.handleMoveError(data);
            }
        })
        .catch(error => {
            console.error('Move error details:', error);
            alert('移動中にエラーが発生しました: ' + error.message);
            this.reenableMovementButtons();
        });
    }

    handleMoveSuccess(data) {
        this.gameManager.updateGameDisplay(data);
        this.gameManager.hideMovementControls();
        
        // gameDataのプレイヤー位置を更新
        this.gameManager.gameData.player.position = data.position;
        
        // エンカウント処理
        if (data.encounter && data.monster) {
            this.gameManager.handleEncounter(data.monster);
            return;
        }
        
        // position=100または0になったら次の道路ボタンを表示
        if (data.position >= 100 && data.nextLocation) {
            this.gameManager.updateNextLocationDisplay(data.nextLocation, true);
        } else if (data.position <= 0 && data.nextLocation) {
            this.gameManager.updateNextLocationDisplay(data.nextLocation, true);
        } else {
            this.gameManager.updateNextLocationDisplay(data.nextLocation, false);
        }
        
        this.gameManager.hideDiceResult();
    }

    handleMoveError(data) {
        alert(data.message || '移動に失敗しました');
        this.reenableMovementButtons();
    }

    reenableMovementButtons() {
        const moveLeft = document.getElementById('move-left');
        const moveRight = document.getElementById('move-right');
        if (moveLeft) moveLeft.disabled = false;
        if (moveRight) moveRight.disabled = false;
    }

    moveToNext() {
        fetch('/game/move-to-next', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            // UI全体を更新（新しいupdateGameDisplayが場所タイプに応じてUI切り替えを行う）
            this.gameManager.updateGameDisplay(data);
            
            // 移動後の次の場所ボタンの表示制御
            const locationType = data.location_type || this.gameManager.gameData.player.current_location_type;
            if (locationType === 'town') {
                // 町にいるときは次の道路ボタンを表示
                if (data.nextLocation) {
                    this.gameManager.updateNextLocationDisplay(data.nextLocation, true);
                } else {
                    this.gameManager.updateNextLocationDisplay(data.nextLocation, false);
                }
            } else {
                // 道路にいるときは端にいる場合のみ次の場所ボタンを表示
                if ((data.position >= 100 || data.position <= 0) && data.nextLocation) {
                    this.gameManager.updateNextLocationDisplay(data.nextLocation, true);
                } else {
                    this.gameManager.updateNextLocationDisplay(data.nextLocation, false);
                }
            }
        });
    }
}

class UIManager {
    constructor(gameManager) {
        this.gameManager = gameManager;
    }

    updateGameDisplay(data) {
        document.getElementById('current-location').textContent = data.currentLocation.name;
        
        // location_typeを確実に取得
        const locationType = data.location_type || (data.currentLocation.name.includes('町') ? 'town' : 'road');
        this.gameManager.gameData.player.current_location_type = locationType;
        this.gameManager.gameData.player.position = data.position;
        
        // 場所タイプに応じてUI全体を切り替え
        if (locationType === 'town') {
            this.showTownUI(data);
        } else if (locationType === 'road') {
            this.showRoadUI(data);
        }
        
        // 位置更新後にボタンの表示状態を確認
        console.log('Position updated to:', data.position, 'Type:', locationType);
    }

    showTownUI(data) {
        // 町の表示
        document.getElementById('location-type').textContent = '町にいます';
        
        // プログレスバーを非表示
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.display = 'none';
        }
        
        // サイコロコンテナを町用に変更
        const diceContainer = document.getElementById('dice-container');
        if (diceContainer) {
            diceContainer.innerHTML = `
                <h3>${data.currentLocation.name}にいます</h3>
                <p>道路に移動すると、サイコロを振って移動できます。</p>
            `;
        }
        
        // 町の施設メニューを表示
        this.showTownMenu();
        
        // 道路専用UIを非表示
        this.hideRoadActions();
        
        // 移動コントロールを非表示
        this.hideMovementControls();
        this.hideDiceResult();
    }

    showRoadUI(data) {
        // 道路の表示
        document.getElementById('location-type').textContent = '道を歩いています';
        
        // プログレスバーを表示・更新
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.display = 'block';
        }
        const progressFill = document.getElementById('progress-fill');
        const progressText = document.getElementById('progress-text');
        if (progressFill && progressText) {
            progressFill.style.width = data.position + '%';
            progressText.textContent = data.position + '/100';
        }
        
        // サイコロコンテナを道路用に変更
        const diceContainer = document.getElementById('dice-container');
        if (diceContainer) {
            diceContainer.innerHTML = this.getDiceContainerHTML();
        }
        
        // 町の施設メニューを非表示
        this.hideTownMenu();
        
        // 道路専用UIを表示
        this.showRoadActions();
        
        // 移動コントロール用のDOMを確保（非表示状態で）
        this.ensureMovementControls();
    }

    showTownMenu() {
        const locationInfo = document.querySelector('.location-info');
        if (!locationInfo) return;
        
        // 既存の町メニューがあるか確認
        let townMenu = locationInfo.querySelector('.town-menu');
        if (!townMenu) {
            // 町メニューを動的作成
            townMenu = document.createElement('div');
            townMenu.className = 'town-menu';
            townMenu.innerHTML = `
                <h3>町の施設</h3>
                <div class="town-actions">
                    <a href="/shops/item" class="btn btn-primary" title="アイテムショップ">
                        <span class="shop-icon">🛒</span>
                        アイテムショップ
                    </a>
                    <a href="/shops/blacksmith" class="btn btn-primary" title="鍛冶屋">
                        <span class="shop-icon">⚒️</span>
                        鍛冶屋
                    </a>
                </div>
            `;
            locationInfo.appendChild(townMenu);
        }
        townMenu.style.display = 'block';
    }

    hideTownMenu() {
        const townMenu = document.querySelector('.town-menu');
        if (townMenu) {
            townMenu.style.display = 'none';
        }
    }

    showRoadActions() {
        const locationInfo = document.querySelector('.location-info');
        if (!locationInfo) return;
        
        // 道路専用アクション（採集など）を表示
        let roadActions = locationInfo.querySelector('.road-actions');
        if (!roadActions) {
            roadActions = document.createElement('div');
            roadActions.className = 'road-actions';
            roadActions.innerHTML = `
                <h3>道での行動</h3>
                <div class="gathering-section">
                    <button id="gathering-btn" class="btn btn-success" onclick="performGathering()">
                        <span class="icon">🌿</span>
                        採集する
                    </button>
                    <button id="gathering-info-btn" class="btn btn-info" onclick="showGatheringInfo()">
                        <span class="icon">📊</span>
                        採集情報
                    </button>
                </div>
            `;
            locationInfo.appendChild(roadActions);
        }
        roadActions.style.display = 'block';
    }

    hideRoadActions() {
        const roadActions = document.querySelector('.road-actions');
        if (roadActions) {
            roadActions.style.display = 'none';
        }
    }

    ensureMovementControls() {
        // 移動コントロールのDOMが存在しない場合作成
        let movementControls = document.getElementById('movement-controls');
        if (!movementControls) {
            movementControls = document.createElement('div');
            movementControls.className = 'movement-controls hidden';
            movementControls.id = 'movement-controls';
            movementControls.innerHTML = `
                <button class="btn btn-warning" id="move-left" onclick="move('left')">←左に移動</button>
                <button class="btn btn-warning" id="move-right" onclick="move('right')">→右に移動</button>
            `;
            
            // dice-containerの後に挿入
            const diceContainer = document.getElementById('dice-container');
            if (diceContainer && diceContainer.parentNode) {
                diceContainer.parentNode.insertBefore(movementControls, diceContainer.nextSibling);
            }
        }
    }

    getDiceContainerHTML() {
        return `
            <h3>サイコロを振って移動しよう！</h3>
            <div class="movement-info">
                <h4>移動情報</h4>
                <p>サイコロ数: 3個 (基本: 2個 + 装備効果: 1個)</p>
                <p>サイコロボーナス: +3</p>
                <p>移動倍率: 1.0倍</p>
                <p>最小移動距離: 6歩</p>
                <p>最大移動距離: 21歩</p>
            </div>
            <button class="btn btn-primary" id="roll-dice" onclick="rollDice()">サイコロを振る</button>
            <div class="dice-display hidden" id="dice-result">
                <div id="all-dice"></div>
            </div>
            <div id="dice-total" class="hidden">
                <div class="step-indicator">
                    <p>基本合計: <span id="base-total">0</span></p>
                    <p>ボーナス: +<span id="bonus">0</span></p>
                    <p>最終移動距離: <span id="final-movement">0</span>歩</p>
                    <p style="font-size: 14px; color: #6b7280;">左右のボタンで移動方向を選択してください</p>
                </div>
            </div>
        `;
    }

    updateNextLocationDisplay(nextLocation, canMove) {
        console.log('updateNextLocationDisplay called:', nextLocation, 'canMove:', canMove);
        const nextLocationInfo = document.getElementById('next-location-info');
        if (nextLocationInfo) {
            if (nextLocation && canMove) {
                console.log('Showing next location button for:', nextLocation.name);
                nextLocationInfo.classList.remove('hidden');
                const strongElement = nextLocationInfo.querySelector('strong');
                const buttonElement = nextLocationInfo.querySelector('button');
                if (strongElement) {
                    strongElement.textContent = nextLocation.name;
                }
                if (buttonElement) {
                    buttonElement.textContent = nextLocation.name + 'に移動する';
                }
                nextLocationInfo.style.display = 'block';
            } else {
                console.log('Hiding next location button');
                nextLocationInfo.classList.add('hidden');
                nextLocationInfo.style.display = 'none';
            }
        } else {
            console.log('next-location-info element not found');
        }
    }

    hideMovementControls() {
        const movementControls = document.getElementById('movement-controls');
        if (movementControls) {
            movementControls.classList.add('hidden');
        }
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
        this.gameManager.currentSteps = 0;
    }
}

class BattleManager {
    constructor(gameManager) {
        this.gameManager = gameManager;
    }

    handleEncounter(monster) {
        // エンカウント通知
        if (confirm(`${monster.emoji} ${monster.name}が現れた！\n戦闘を開始しますか？`)) {
            // 戦闘開始
            fetch('/battle/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    monster: monster
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 戦闘画面に遷移
                    window.location.href = '/battle';
                } else {
                    alert('戦闘開始に失敗しました');
                }
            })
            .catch(error => {
                console.error('Battle start error:', error);
                alert('戦闘開始中にエラーが発生しました');
            });
        } else {
            // 戦闘を拒否（現在の位置を元に戻す）
            alert('逃げることはできません！戦うか、逃げるかは戦闘中に決めてください。');
            this.handleEncounter(monster); // 再度エンカウント処理
        }
    }
}

// グローバル変数とメソッドの統合
let gameManager;
let diceManager;
let movementManager;
let uiManager;
let battleManager;

// 初期化
function initializeGame(gameData) {
    gameManager = new GameManager(gameData);
    diceManager = new DiceManager(gameManager);
    movementManager = new MovementManager(gameManager);
    uiManager = new UIManager(gameManager);
    battleManager = new BattleManager(gameManager);
    
    // GameManagerにUIManagerの参照を設定
    gameManager.uiManager = uiManager;
    gameManager.battleManager = battleManager;
    
    // UIManagerのメソッドをGameManagerに追加
    gameManager.updateGameDisplay = (data) => uiManager.updateGameDisplay(data);
    gameManager.updateNextLocationDisplay = (nextLocation, canMove) => uiManager.updateNextLocationDisplay(nextLocation, canMove);
    gameManager.hideMovementControls = () => uiManager.hideMovementControls();
    gameManager.hideDiceResult = () => uiManager.hideDiceResult();
    gameManager.handleEncounter = (monster) => battleManager.handleEncounter(monster);
}

// グローバル関数（HTMLから呼び出し用）
function rollDice() {
    diceManager.rollDice();
}

function move(direction) {
    movementManager.move(direction);
}

function moveToNext() {
    movementManager.moveToNext();
}

function resetGame() {
    fetch('/game/reset', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        gameManager.updateGameDisplay(data);
        gameManager.updateNextLocationDisplay(data.nextLocation, false);
        gameManager.hideMovementControls();
        gameManager.hideDiceResult();
    });
}

function toggleDiceDisplay() {
    const diceResult = document.getElementById('dice-result');
    const diceToggle = document.getElementById('dice-display-toggle');
    
    if (diceToggle && diceResult) {
        if (diceToggle.checked) {
            diceResult.classList.remove('hidden');
        } else {
            diceResult.classList.add('hidden');
        }
    }
}

// 採集関連の関数
function performGathering() {
    const gatheringBtn = document.getElementById('gathering-btn');
    if (gatheringBtn) {
        gatheringBtn.disabled = true;
        gatheringBtn.textContent = '採集中...';
    }
    
    fetch('/gathering/gather', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = data.message;
            if (data.leveled_up) {
                message += `\n採集スキルがレベルアップしました！ (Lv.${data.skill_level})`;
            }
            message += `\n経験値: +${data.experience_gained}`;
            message += `\nSP: ${data.remaining_sp} (${data.sp_consumed}消費)`;
            
            alert(message);
        } else {
            alert(data.error || data.message || '採集に失敗しました');
        }
        
        if (gatheringBtn) {
            gatheringBtn.disabled = false;
            gatheringBtn.innerHTML = '<span class="icon">🌿</span> 採集する';
        }
    })
    .catch(error => {
        console.error('Gathering error:', error);
        alert('採集中にエラーが発生しました');
        
        if (gatheringBtn) {
            gatheringBtn.disabled = false;
            gatheringBtn.innerHTML = '<span class="icon">🌿</span> 採集する';
        }
    });
}

function showGatheringInfo() {
    fetch('/gathering/info', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        
        let info = `=== 採集情報 (${data.road_name}) ===\n`;
        info += `採集スキル: Lv.${data.skill_level}\n`;
        info += `経験値: ${data.experience}/${data.required_exp_for_next_level}\n`;
        info += `SP消費: ${data.sp_cost} (現在SP: ${data.current_sp})\n`;
        info += `採集可能: ${data.can_gather ? 'はい' : 'いいえ'}\n`;
        info += `採集可能アイテム数: ${data.available_items_count}\n\n`;
        
        info += `=== 採集可能アイテム ===\n`;
        data.all_items.forEach(item => {
            const status = item.can_gather ? '✓' : '✗';
            info += `${status} ${item.item_name} (Lv.${item.required_skill_level}必要, 成功率${item.success_rate}%)\n`;
        });
        
        alert(info);
    })
    .catch(error => {
        console.error('Gathering info error:', error);
        alert('採集情報の取得中にエラーが発生しました');
    });
}