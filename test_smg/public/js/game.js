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
        if (diceResult) diceResult.classList.remove('hidden');
        if (diceTotal) diceTotal.classList.remove('hidden');
        
        if (this.gameManager.gameData.player.current_location_type === 'road') {
            const movementControls = document.getElementById('movement-controls');
            const moveLeft = document.getElementById('move-left');
            const moveRight = document.getElementById('move-right');
            if (movementControls) movementControls.classList.remove('hidden');
            if (moveLeft) moveLeft.disabled = false;
            if (moveRight) moveRight.disabled = false;
        } else {
            alert('町にいるときはサイコロを振っても移動できません。道路に移動してください。');
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
            this.gameManager.updateGameDisplay(data);
            this.gameManager.gameData.player.current_location_type = data.currentLocation.name.includes('町') ? 'town' : 'road';
            this.gameManager.gameData.player.position = data.position;
            
            // 移動後の次の場所ボタンの表示制御
            if (this.gameManager.gameData.player.current_location_type === 'town') {
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
            
            this.gameManager.hideMovementControls();
            this.gameManager.hideDiceResult();
        });
    }
}

class UIManager {
    constructor(gameManager) {
        this.gameManager = gameManager;
    }

    updateGameDisplay(data) {
        document.getElementById('current-location').textContent = data.currentLocation.name;
        
        if (data.currentLocation.name.includes('町')) {
            document.getElementById('location-type').textContent = '町にいます';
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.display = 'none';
            }
            // 町にいるときはサイコロと移動コントロールを非表示
            const diceContainer = document.getElementById('dice-container');
            if (diceContainer) {
                diceContainer.innerHTML = '<h3>' + data.currentLocation.name + 'にいます</h3><p>道路に移動すると、サイコロを振って移動できます。</p><p>今後、この町の店舗リストが表示される予定です。</p>';
            }
            this.hideMovementControls();
            this.hideDiceResult();
        } else {
            document.getElementById('location-type').textContent = '道を歩いています';
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
            
            // 道路にいるときはサイコロコンテナを復元
            const diceContainer = document.getElementById('dice-container');
            if (diceContainer && !diceContainer.innerHTML.includes('サイコロを振って移動しよう！')) {
                diceContainer.innerHTML = this.getDiceContainerHTML();
            }
        }
        
        this.gameManager.gameData.player.current_location_type = data.currentLocation.name.includes('町') ? 'town' : 'road';
        this.gameManager.gameData.player.position = data.position;
        
        // 位置更新後にボタンの表示状態を確認
        console.log('Position updated to:', data.position, 'Type:', this.gameManager.gameData.player.current_location_type);
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