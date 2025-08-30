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
        console.log('Initial load - Character position:', this.gameData.character.game_position, 'Type:', this.gameData.character.location_type);
        console.log('Initial load - Next location:', this.gameData.nextLocation);
        
        // 初期状態でUI全体を適切に設定
        const initialData = {
            currentLocation: this.gameData.currentLocation,
            position: this.gameData.character.game_position,
            location_type: this.gameData.character.location_type
        };
        this.updateGameDisplay(initialData);
        
        // 道路でpositionが0/50/100のとき、次の場所ボタンを表示
        if (this.gameData.character.location_type === 'road') {
            const pos = this.gameData.character.game_position;
            if ((pos <= 0 || pos === 50 || pos >= 100) && this.gameData.nextLocation) {
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

    enableDiceButton() {
        const rollDiceButton = document.getElementById('roll-dice');
        if (rollDiceButton) rollDiceButton.disabled = false;
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
        .then(response => ErrorHandler.handleApiResponse(
            response,
            (data) => {
                this.handleDiceResult(data);
                // サイコロを振った後は、移動が完了するまでボタンを無効のままにする
            },
            (error) => {
                const rollDiceButton = document.getElementById('roll-dice');
                if (rollDiceButton) rollDiceButton.disabled = false;
                alert(error.error || error.message || 'サイコロを振ることができませんでした');
            }
        ))
        .catch(error => {
            ErrorHandler.handleApiError(error, 'サイコロを振る');
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
        if (movableLocations.includes(this.gameManager.gameData.character.location_type)) {
            const movementControls = document.getElementById('movement-controls');
            if (movementControls) movementControls.classList.remove('hidden');
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
        
        // 境界チェック
        const currentPosition = this.gameManager.gameData.character.game_position || 0;
        if (direction === 'south' && currentPosition <= 0) {
            alert('道の端なので南に移動できません！');
            return;
        }
        if (direction === 'north' && currentPosition >= 100) {
            alert('道の端なので北に移動できません！');
            return;
        }
        
        const moveNorth = document.getElementById('move-north');
        const moveSouth = document.getElementById('move-south');
        if (moveNorth) moveNorth.disabled = true;
        if (moveSouth) moveSouth.disabled = true;
        
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
        .then(response => ErrorHandler.handleApiResponse(
            response,
            (data) => {
                console.log('Move response:', data);
                this.handleMoveSuccess(data);
            },
            (error) => {
                this.handleMoveError(error);
            }
        ))
        .catch(error => {
            ErrorHandler.handleApiError(error, '移動');
            this.reenableMovementButtons();
        });
    }

    handleMoveSuccess(data) {
        // 新しいDTO構造に対応したデータ拡張
        const extendedData = {
            ...data,
            location_type: this.getLocationTypeFromData(data)
        };
        
        this.gameManager.updateGameDisplay(extendedData);
        this.gameManager.hideMovementControls();
        
        // gameDataのキャラクター位置とnextLocationを更新
        this.gameManager.gameData.character.game_position = data.position;
        this.gameManager.gameData.nextLocation = data.nextLocation;
        
        // 移動後の位置に応じて移動コントロールを更新
        if (extendedData.location_type === 'road') {
            this.gameManager.uiManager.ensureMovementControls(data.position);
        }
        
        // 移動完了後：サイコロの状態をリセット
        this.gameManager.currentSteps = 0;
        this.gameManager.enableDiceButton();
        this.gameManager.hideDiceResult();
        
        // エンカウント処理（新しいDTO構造対応）
        if (data.encounter && data.monster) {
            this.gameManager.handleEncounter(data.monster);
            return;
        }
        
        // 位置ベースの次の場所ボタン表示制御（0/50/100）
        if (extendedData.location_type === 'road') {
            const isAtBoundaryOrBranch = data.position <= 0 || data.position === 50 || data.position >= 100;
            if (isAtBoundaryOrBranch && data.nextLocation) {
                console.log('Move success: Showing next location button at boundary, position:', data.position);
                this.gameManager.updateNextLocationDisplay(data.nextLocation, true);
            } else {
                console.log('Move success: Hiding next location button, position:', data.position);
                this.gameManager.updateNextLocationDisplay(data.nextLocation, false);
            }
        } else {
            // 町の場合は次の場所ボタンを表示
            if (data.nextLocation) {
                this.gameManager.updateNextLocationDisplay(data.nextLocation, true);
            } else {
                this.gameManager.updateNextLocationDisplay(data.nextLocation, false);
            }
        }
    }
    
    /**
     * レスポンスデータからlocation_typeを推測
     */
    getLocationTypeFromData(data) {
        if (data.location_type) {
            return data.location_type;
        }
        
        // currentLocationから推測
        if (data.currentLocation && data.currentLocation.type) {
            return data.currentLocation.type;
        }
        
        // フォールバック: 名前から推測
        if (data.currentLocation && data.currentLocation.name) {
            return data.currentLocation.name.includes('町') ? 'town' : 'road';
        }
        
        // 最終フォールバック
        return this.gameManager.gameData.character.location_type || 'town';
    }

    handleMoveError(data) {
        alert(data.message || '移動に失敗しました');
        this.reenableMovementButtons();
    }

    reenableMovementButtons() {
        const moveNorth = document.getElementById('move-north');
        const moveSouth = document.getElementById('move-south');
        if (moveNorth) moveNorth.disabled = false;
        if (moveSouth) moveSouth.disabled = false;
    }

    moveToNext() {
        // 互換性のため残しておく（古いコードからの呼び出し対応）
        this.moveToNextFromTown();
    }

    moveToNextFromTown() {
        console.log('Town movement: Moving to next location');
        this.performMoveToNext('town');
    }

    moveToNextFromRoad() {
        console.log('Road movement: Moving to next location');
        this.performMoveToNext('road');
    }

    performMoveToNext(sourceType) {
        fetch('/game/move-to-next', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => ErrorHandler.handleApiResponse(
            response,
            (data) => {
            console.log(`${sourceType} movement response:`, data);
            
            // 新しいDTO構造に対応したデータ拡張
            const extendedData = {
                ...data,
                location_type: this.getLocationTypeFromData(data)
            };
            
            // gameDataのキャラクター位置を更新（UI更新前に実行）
            this.gameManager.gameData.character.game_position = data.position;
            this.gameManager.gameData.character.location_type = extendedData.location_type;
            this.gameManager.gameData.nextLocation = data.nextLocation;
            
            // UI全体を更新（gameData更新後に実行）
            this.gameManager.updateGameDisplay(extendedData);
            
            // 移動後の次の場所ボタンの表示制御（新しいDTO構造）
            const locationType = extendedData.location_type;
            if (locationType === 'town') {
                // 町にいるときは次の道路ボタンを表示
                if (data.nextLocation) {
                    this.gameManager.updateNextLocationDisplay(data.nextLocation, true);
                } else {
                    this.gameManager.updateNextLocationDisplay(data.nextLocation, false);
                }
            } else {
                // 道路にいるときは位置で判定（0/50/100にいる場合のみ表示）
                const isAtBoundaryOrBranch = data.position <= 0 || data.position === 50 || data.position >= 100;
                if (isAtBoundaryOrBranch && data.nextLocation) {
                    this.gameManager.updateNextLocationDisplay(data.nextLocation, true);
                } else {
                    this.gameManager.updateNextLocationDisplay(data.nextLocation, false);
                }
            }
        }))
        .catch(error => {
            ErrorHandler.handleApiError(error, '場所移動');
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
        this.gameManager.gameData.character.location_type = locationType;
        this.gameManager.gameData.character.game_position = data.position;
        
        // 場所タイプに応じてUI全体を切り替え
        if (locationType === 'town') {
            this.showTownUI(data);
        } else if (locationType === 'road') {
            this.showRoadUI(data);
        }
        
        // 位置更新後にボタンの表示状態を確認
        console.log('Position updated to:', data.position, 'Type:', locationType);
        
        // 道路でのnextLocationボタンチェック（gameData更新後）
        if (locationType === 'road') {
            const isAtBoundaryOrBranch = data.position <= 0 || data.position === 50 || data.position >= 100;
            console.log('updateGameDisplay: Road position check - position:', data.position, 'isAtBoundaryOrBranch:', isAtBoundaryOrBranch, 'nextLocation:', this.gameManager.gameData.nextLocation);
            if (isAtBoundaryOrBranch && this.gameManager.gameData.nextLocation) {
                console.log('updateGameDisplay: Showing next location button');
                this.updateNextLocationDisplay(this.gameManager.gameData.nextLocation, true);
            } else {
                console.log('updateGameDisplay: Hiding next location button');
                this.updateNextLocationDisplay(this.gameManager.gameData.nextLocation, false);
            }
        }
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
        
        // 町の施設メニューを動的に更新
        this.updateTownMenu(data.currentLocation);
        
        // 道路専用UIを非表示
        this.hideRoadActions();
        
        // 移動コントロールを非表示
        this.hideMovementControls();
        this.hideDiceResult();
    }

    showRoadUI(data) {
        // 道路の表示
        document.getElementById('location-type').textContent = '道を歩いています';
        
        // プログレスバーを表示・更新（存在しない場合は作成）
        this.ensureProgressBar();
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.display = 'block';
        }
        const progressFill = document.getElementById('progress-fill');
        const progressText = document.getElementById('progress-text');
        if (progressFill && progressText) {
            progressFill.style.width = data.position + '%';
            progressText.textContent = data.position + '/100';
            console.log('Progress bar updated:', data.position);
        } else {
            console.error('Progress bar elements not found after ensureProgressBar()');
        }
        
        // サイコロコンテナを道路用に変更
        const diceContainer = document.getElementById('dice-container');
        if (diceContainer) {
            diceContainer.innerHTML = this.getDiceContainerHTML();
        }
        
        // 境界での制限を適用
        this.applyBoundaryRestrictions(data.position);
        
        // 町の施設メニューを非表示
        this.hideTownMenu();
        
        // 道路専用UIを表示
        this.showRoadActions();
        
        // 移動コントロール用のDOMを確保（位置に応じて表示）
        this.ensureMovementControls(data.position);
        
        // 次の場所ボタンの表示は updateGameDisplay で統一的に処理される
        console.log('showRoadUI - Position:', data.position);
    }

    updateTownMenu(currentLocation) {
        console.log('Updating town menu for:', currentLocation);
        
        // 施設データを取得してメニューを更新
        fetch(`/api/location/facilities?location_id=${currentLocation.id}&location_type=town`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Town facilities data:', data);
            this.renderTownMenu(data.facilities, data.connections);
        })
        .catch(error => {
            console.error('Failed to fetch town facilities:', error);
            // フォールバック: デフォルトの施設メニューを表示
            this.showDefaultTownMenu();
        });
    }

    renderTownMenu(facilities, connections) {
        const facilityMenu = document.querySelector('.facility-menu');
        if (!facilityMenu) {
            // facility-menuが存在しない場合は作成
            this.createTownMenuContainer();
        }
        
        const facilityMenuElement = document.querySelector('.facility-menu');
        if (facilityMenuElement) {
            let menuHTML = '<h3>町の施設</h3>';
            
            if (facilities && facilities.length > 0) {
                facilities.forEach(facility => {
                    const routeName = this.getFacilityRouteName(facility.facility_type);
                    if (routeName) {
                        menuHTML += `
                            <a href="${routeName}" class="btn btn-primary" title="${facility.description || this.getFacilityDescription(facility.facility_type)}" style="margin: 5px;">
                                <span class="facility-icon">${this.getFacilityIcon(facility.facility_type)}</span>
                                ${facility.name}
                            </a>
                        `;
                    }
                });
            } else {
                // フォールバック: 基本的な施設を表示
                menuHTML += this.getDefaultFacilitiesHTML();
            }
            
            // 接続ボタンを追加
            if (connections && Object.keys(connections).length > 0) {
                menuHTML += '<hr style="margin: 15px 0;">';
                menuHTML += '<h3>移動先選択</h3>';
                menuHTML += '<div class="connection-options">';
                
                Object.keys(connections).forEach(direction => {
                    const connection = connections[direction];
                    const directionIcons = {
                        'north': '⬆️',
                        'south': '⬇️', 
                        'east': '➡️',
                        'west': '⬅️'
                    };
                    const icon = directionIcons[direction] || '🚪';
                    
                    menuHTML += `
                        <button 
                            class="connection-btn btn btn-success"
                            title="${connection.name || 'Unknown destination'}"
                            data-direction="${direction}"
                            style="margin: 5px; padding: 10px 15px; width: 100%; display: flex; align-items: center; gap: 10px;"
                        >
                            <span class="direction-icon">${icon}</span>
                            <div class="direction-info" style="flex: 1; text-align: left;">
                                <div style="font-weight: bold;">${connection.direction_label || direction.charAt(0).toUpperCase() + direction.slice(1)}</div>
                                <div style="font-size: 12px; opacity: 0.8;">${connection.name || 'Unknown'}</div>
                            </div>
                        </button>
                    `;
                });
                
                menuHTML += '</div>';
            }
            
            facilityMenuElement.innerHTML = menuHTML;
            facilityMenuElement.style.display = 'block';
        }
    }

    createTownMenuContainer() {
        const locationInfo = document.querySelector('.location-info');
        if (locationInfo) {
            const facilityMenu = document.createElement('div');
            facilityMenu.className = 'facility-menu';
            facilityMenu.style.cssText = 'background-color: #e0f7fa; border: 2px solid #00acc1; border-radius: 8px; padding: 15px; margin-bottom: 15px;';
            
            // プログレスバーの前に挿入
            const progressBar = locationInfo.querySelector('.progress-bar');
            if (progressBar) {
                locationInfo.insertBefore(facilityMenu, progressBar);
            } else {
                locationInfo.appendChild(facilityMenu);
            }
        }
    }

    ensureProgressBar() {
        // プログレスバーが存在しない場合は作成
        let progressBar = document.querySelector('.progress-bar');
        if (!progressBar) {
            console.log('Creating progress bar');
            const locationInfo = document.querySelector('.location-info');
            if (locationInfo) {
                progressBar = document.createElement('div');
                progressBar.className = 'progress-bar';
                progressBar.style.cssText = `
                    width: 100%;
                    height: 30px;
                    background-color: #f0f0f0;
                    border: 2px solid #ccc;
                    border-radius: 15px;
                    position: relative;
                    margin: 10px 0;
                    overflow: hidden;
                `;
                
                const progressFill = document.createElement('div');
                progressFill.className = 'progress-fill';
                progressFill.id = 'progress-fill';
                progressFill.style.cssText = `
                    height: 100%;
                    background: linear-gradient(90deg, #4CAF50, #81C784);
                    border-radius: 13px;
                    transition: width 0.3s ease;
                    width: 0%;
                `;
                
                const progressText = document.createElement('div');
                progressText.className = 'progress-text';
                progressText.id = 'progress-text';
                progressText.style.cssText = `
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    color: #333;
                    font-weight: bold;
                    font-size: 14px;
                    z-index: 10;
                `;
                progressText.textContent = '0/100';
                
                progressBar.appendChild(progressFill);
                progressBar.appendChild(progressText);
                
                // facility-menuの後、次の場所ボタンの前に挿入
                const facilityMenu = locationInfo.querySelector('.facility-menu');
                const nextLocationInfo = locationInfo.querySelector('#next-location-info');
                
                if (facilityMenu && facilityMenu.nextSibling) {
                    locationInfo.insertBefore(progressBar, facilityMenu.nextSibling);
                } else if (nextLocationInfo) {
                    locationInfo.insertBefore(progressBar, nextLocationInfo);
                } else {
                    locationInfo.appendChild(progressBar);
                }
            }
        }
    }

    getFacilityRouteName(facilityType) {
        switch(facilityType) {
            case 'item_shop': return '/facilities/item';
            case 'general_store': return '/facilities/item';
            case 'blacksmith': return '/facilities/blacksmith';
            case 'tavern': return '/facilities/tavern';
            case 'alchemy_shop': return '/facilities/alchemy';
            case 'inn': return '/facilities/inn';
            case 'bank': return '/facilities/bank';
            default: return null;
        }
    }

    getFacilityIcon(facilityType) {
        switch(facilityType) {
            case 'item_shop':
            case 'general_store': return '🏪';
            case 'blacksmith': return '⚒️';
            case 'tavern': return '🍺';
            case 'alchemy_shop': return '⚗️';
            case 'inn': return '🛏️';
            case 'bank': return '🏦';
            default: return '🏬';
        }
    }

    getFacilityDescription(facilityType) {
        switch(facilityType) {
            case 'item_shop':
            case 'general_store': return '道具屋';
            case 'blacksmith': return '鍛冶屋';
            case 'tavern': return 'HP、MP、SPを回復できます。';
            case 'alchemy_shop': return '錬金屋';
            case 'inn': return '宿屋';
            case 'bank': return '銀行';
            default: return '施設';
        }
    }

    getDefaultFacilitiesHTML() {
        return `
            <a href="/facilities/item" class="btn btn-primary" title="道具屋" style="margin: 5px;">
                <span class="facility-icon">🏪</span>
                道具屋
            </a>
            <a href="/facilities/blacksmith" class="btn btn-primary" title="鍛冶屋" style="margin: 5px;">
                <span class="facility-icon">⚒️</span>
                鍛冶屋
            </a>
        `;
    }

    showTownMenu() {
        // 既存のメニューを表示（後方互換性のため残す）
        const facilityMenu = document.querySelector('.facility-menu');
        if (facilityMenu) {
            facilityMenu.style.display = 'block';
        }
    }

    showDefaultTownMenu() {
        // エラー時のフォールバック
        const facilityMenu = document.querySelector('.facility-menu');
        if (facilityMenu) {
            facilityMenu.innerHTML = '<h3>町の施設</h3>' + this.getDefaultFacilitiesHTML();
            facilityMenu.style.display = 'block';
        }
    }

    hideTownMenu() {
        const facilityMenu = document.querySelector('.facility-menu');
        if (facilityMenu) {
            facilityMenu.style.display = 'none';
        }
        // 移動メニューは next_location_button.blade.php で処理
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

    ensureMovementControls(position = 50) {
        // 移動コントロールのDOMを位置に応じて作成・更新
        let movementControls = document.getElementById('movement-controls');
        
        // 既存のコントロールがある場合は削除して再作成
        if (movementControls) {
            movementControls.remove();
        }
        
        // 新しい移動コントロールを作成
        movementControls = document.createElement('div');
        movementControls.className = 'movement-controls hidden';
        movementControls.id = 'movement-controls';
        
        let buttonsHTML = '';
        
        // 南ボタン：位置が0より大きい場合のみ表示（進歩を減らす）
        if (position > 0) {
            buttonsHTML += '<button class="btn btn-warning" id="move-south" onclick="move(\'south\')">⬇️南に移動（戻る）</button>';
        }
        
        // 北ボタン：位置が100未満の場合のみ表示（進歩を増やす）
        if (position < 100) {
            buttonsHTML += '<button class="btn btn-warning" id="move-north" onclick="move(\'north\')">⬆️北に移動（進む）</button>';
        }
        
        movementControls.innerHTML = buttonsHTML;
        
        // dice-containerの後に挿入
        const diceContainer = document.getElementById('dice-container');
        if (diceContainer && diceContainer.parentNode) {
            diceContainer.parentNode.insertBefore(movementControls, diceContainer.nextSibling);
        }
        
        console.log('Movement controls created for position:', position, 'South:', position > 0, 'North:', position < 100);
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
        let nextLocationInfo = document.getElementById('next-location-info');
        if (!nextLocationInfo) {
            // コンテナが存在しない場合は動的に作成して挿入
            const locationInfo = document.querySelector('.location-info');
            if (locationInfo) {
                nextLocationInfo = document.createElement('div');
                nextLocationInfo.className = 'next-location';
                nextLocationInfo.id = 'next-location-info';
                nextLocationInfo.style.display = 'none';

                // ボタンIDとハンドラを現在の場所タイプから決定
                const locationType = this.gameManager?.gameData?.character?.location_type || 'town';
                const buttonId = locationType === 'town' ? 'move-to-next-town' : 'move-to-next-road';
                const buttonHandler = locationType === 'town' ? 'moveToNextFromTown()' : 'moveToNextFromRoad()';

                nextLocationInfo.innerHTML = `
                    <p>次の場所: <strong></strong></p>
                    <button class="btn btn-success" id="${buttonId}" onclick="${buttonHandler}">
                        移動する
                    </button>
                `;

                // location-infoの末尾に挿入（存在すればプログレスバーの後）
                const progressBar = locationInfo.querySelector('.progress-bar');
                if (progressBar && progressBar.nextSibling) {
                    locationInfo.insertBefore(nextLocationInfo, progressBar.nextSibling);
                } else {
                    locationInfo.appendChild(nextLocationInfo);
                }
            } else {
                console.error('location-info container not found; cannot insert next-location-info');
            }
        }

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
                    // 位置タイプが変わっている可能性があるため、必要ならハンドラを更新
                    const locationType = this.gameManager?.gameData?.character?.location_type || 'town';
                    if (locationType === 'town') {
                        buttonElement.id = 'move-to-next-town';
                        buttonElement.setAttribute('onclick', 'moveToNextFromTown()');
                    } else {
                        buttonElement.id = 'move-to-next-road';
                        buttonElement.setAttribute('onclick', 'moveToNextFromRoad()');
                    }
                }
                nextLocationInfo.style.display = 'block';
            } else {
                console.log('Hiding next location button');
                nextLocationInfo.classList.add('hidden');
                nextLocationInfo.style.display = 'none';
            }
        } else {
            console.error('next-location-info element not found in DOM');
            // DOM要素が存在しない場合の対処
            console.log('Available elements with id:', Array.from(document.querySelectorAll('[id]')).map(el => el.id));
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

    applyBoundaryRestrictions(position) {
        const rollDiceButton = document.getElementById('roll-dice');
        
        // サイコロは境界でも振れるようにする
        if (rollDiceButton) {
            rollDiceButton.disabled = false;
            rollDiceButton.textContent = 'サイコロを振る';
            rollDiceButton.style.opacity = '1';
        }
        
        // 移動ボタンは ensureMovementControls で位置に応じて作成されるため、
        // ここでの制限処理は不要
        console.log('Boundary restrictions applied for position:', position);
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
            .then(response => ErrorHandler.handleApiResponse(
                response,
                (data) => {
                    // 戦闘画面に遷移
                    window.location.href = '/battle';
                },
                (error) => {
                    alert(error.error || error.message || '戦闘開始に失敗しました');
                }
            ))
            .catch(error => {
                ErrorHandler.handleApiError(error, '戦闘開始');
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

/**
 * 統一されたエラーハンドリング
 */
class ErrorHandler {
    static handleApiError(error, context = '') {
        console.error(`${context} error:`, error);
        
        let message = 'エラーが発生しました';
        if (context) {
            message = `${context}中にエラーが発生しました`;
        }
        
        if (error.message) {
            message += `: ${error.message}`;
        }
        
        alert(message);
    }
    
    static handleApiResponse(response, successCallback, errorCallback = null) {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json().then(data => {
            if (data.success === false) {
                const error = data.error || data.message || '処理に失敗しました';
                if (errorCallback) {
                    errorCallback(data);
                } else {
                    alert(error);
                }
                return;
            }
            
            successCallback(data);
        });
    }
}

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
    gameManager.movementManager = movementManager;
    
    // UIManagerのメソッドをGameManagerに追加
    gameManager.updateGameDisplay = (data) => uiManager.updateGameDisplay(data);
    gameManager.updateNextLocationDisplay = (nextLocation, canMove) => uiManager.updateNextLocationDisplay(nextLocation, canMove);
    gameManager.hideMovementControls = () => uiManager.hideMovementControls();
    gameManager.hideDiceResult = () => uiManager.hideDiceResult();
    gameManager.handleEncounter = (monster) => battleManager.handleEncounter(monster);
    
    // Initialize keyboard controls
    initializeKeyboardControls();
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

// New connection-based movement functions
function moveToConnection(connectionId) {
    console.log('moveToConnection called with ID:', connectionId);
    
    const button = document.querySelector(`[data-connection-id="${connectionId}"]`);
    if (button) {
        button.disabled = true;
        button.innerHTML = button.innerHTML.replace(/^(.*)$/, '<span class="spinner">🔄</span> 移動中...');
    }
    
    fetch('/api/game/move-to-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            connection_id: connectionId
        })
    })
    .then(response => ErrorHandler.handleApiResponse(
        response,
        (data) => {
            console.log('Connection movement response:', data);
            handleConnectionMovementSuccess(data);
        },
        (error) => {
            handleConnectionMovementError(error, connectionId);
        }
    ))
    .catch(error => {
        ErrorHandler.handleApiError(error, 'コネクション移動');
        resetConnectionButton(connectionId);
    });
}

function handleConnectionMovementSuccess(data) {
    // Update game display
    if (gameManager && typeof gameManager.updateGameDisplay === 'function') {
        const extendedData = {
            ...data,
            location_type: movementManager.getLocationTypeFromData(data)
        };
        gameManager.updateGameDisplay(extendedData);
    }
    
    // Show success message if provided
    if (data.message) {
        showSuccessMessage(data.message);
    }
    
    // Update game data
    if (gameManager) {
        gameManager.gameData.character.game_position = data.position;
        gameManager.gameData.character.location_type = data.location_type || 
            movementManager.getLocationTypeFromData(data);
        gameManager.gameData.nextLocation = data.nextLocation;
    }
}

function handleConnectionMovementError(error, connectionId) {
    console.error('Connection movement error:', error);
    const message = error.error || error.message || 'コネクション移動に失敗しました';
    showErrorMessage(message);
    resetConnectionButton(connectionId);
}

function resetConnectionButton(connectionId) {
    const button = document.querySelector(`[data-connection-id="${connectionId}"]`);
    if (button) {
        button.disabled = false;
        // Remove spinner and restore original content
        button.innerHTML = button.innerHTML.replace(/<span class="spinner">🔄<\/span>\s*移動中\.\.\./, '');
        
        // If content is empty, restore from data attributes or default
        if (button.innerHTML.trim() === '') {
            const actionText = button.title || '移動する';
            button.innerHTML = `<span class="btn-icon">🚶</span><span class="btn-text">${actionText}</span>`;
        }
    }
}

// Keyboard event handling for movement shortcuts
function initializeKeyboardControls() {
    document.addEventListener('keydown', function(event) {
        // Only handle keyboard shortcuts if not typing in an input field
        if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA' || 
            event.target.contentEditable === 'true') {
            return;
        }
        
        // Map keyboard keys to movement
        const keyboardMap = {
            'ArrowUp': 'up',
            'ArrowDown': 'down',
            'ArrowLeft': 'south',
            'ArrowRight': 'north'
        };
        
        const shortcut = keyboardMap[event.key];
        if (shortcut) {
            event.preventDefault();
            moveByKeyboard(shortcut);
        }
    });
    
    console.log('Keyboard controls initialized');
}

function moveByKeyboard(shortcut) {
    console.log('moveByKeyboard called with shortcut:', shortcut);
    
    // Find available connection with this keyboard shortcut
    const button = document.querySelector(`[data-keyboard="${shortcut}"]`);
    if (!button) {
        console.log('No connection found for keyboard shortcut:', shortcut);
        return;
    }
    
    // Get connection ID from button
    const connectionId = button.getAttribute('data-connection-id');
    if (connectionId) {
        console.log('Moving via keyboard shortcut to connection:', connectionId);
        moveToConnection(connectionId);
    } else {
        console.error('Connection ID not found for keyboard shortcut button');
    }
}

// API call for keyboard movement (alternative method)
function moveByKeyboardAPI(shortcut) {
    fetch('/api/game/move-by-keyboard', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            keyboard_shortcut: shortcut
        })
    })
    .then(response => ErrorHandler.handleApiResponse(
        response,
        (data) => {
            console.log('Keyboard movement response:', data);
            handleConnectionMovementSuccess(data);
        },
        (error) => {
            console.log('Keyboard movement failed:', error);
            // Don't show error for keyboard shortcuts that don't exist
        }
    ))
    .catch(error => {
        console.log('Keyboard movement API error:', error);
    });
}

function moveDirectly(direction = null, townDirection = null) {
    const data = {};
    if (direction) data.direction = direction;
    if (townDirection) data.town_direction = townDirection;
    
    fetch('/game/move-directly', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Direct movement result:', data);
        if (data.success) {
            movementManager.updateGameState(data);
            showMessage(data.message || '直接移動しました', 'success');
        } else {
            showMessage(data.error || '移動に失敗しました', 'error');
        }
    })
    .catch(error => {
        console.error('Direct movement error:', error);
        showMessage('移動中にエラーが発生しました', 'error');
    });
}

function moveToNextFromTown() {
    movementManager.moveToNextFromTown();
}

function moveToNextFromRoad() {
    movementManager.moveToNextFromRoad();
}

function resetGame() {
    fetch('/game/reset', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => ErrorHandler.handleApiResponse(
        response,
        (data) => {
            // 新しいDTO構造に対応
            const extendedData = {
                ...data,
                location_type: movementManager.getLocationTypeFromData(data)
            };
            
            gameManager.updateGameDisplay(extendedData);
            
            // キャラクターデータを更新
            gameManager.gameData.character.game_position = data.position;
            gameManager.gameData.character.location_type = extendedData.location_type;
            gameManager.gameData.nextLocation = data.nextLocation;
            
            // 次の場所ボタンを非表示
            gameManager.updateNextLocationDisplay(data.nextLocation, false);
            gameManager.hideMovementControls();
            gameManager.hideDiceResult();
        }
    ))
    .catch(error => {
        ErrorHandler.handleApiError(error, 'ゲームリセット');
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
    .then(response => ErrorHandler.handleApiResponse(
        response,
        (data) => {
            let message = data.message;
            if (data.leveled_up) {
                message += `\n採集スキルがレベルアップしました！ (Lv.${data.skill_level})`;
            }
            message += `\n経験値: +${data.experience_gained}`;
            message += `\nSP: ${data.remaining_sp} (${data.sp_consumed}消費)`;
            
            alert(message);
            
            if (gatheringBtn) {
                gatheringBtn.disabled = false;
                gatheringBtn.innerHTML = '<span class="icon">🌿</span> 採集する';
            }
        },
        (error) => {
            alert(error.error || error.message || '採集に失敗しました');
            
            if (gatheringBtn) {
                gatheringBtn.disabled = false;
                gatheringBtn.innerHTML = '<span class="icon">🌿</span> 採集する';
            }
        }
    ))
    .catch(error => {
        ErrorHandler.handleApiError(error, '採集');
        
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
    .then(response => ErrorHandler.handleApiResponse(
        response,
        (data) => {
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
        },
        (error) => {
            alert(error.error || error.message || '採集情報の取得に失敗しました');
        }
    ))
    .catch(error => {
        ErrorHandler.handleApiError(error, '採集情報取得');
    });
}

/**
 * 分岐選択システム
 * T字路や交差点での方向選択を処理
 */
function selectBranch(direction) {
    console.log(`Branch selection: ${direction}`);
    
    // 分岐ボタンを無効化
    const branchButtons = document.querySelectorAll('.branch-btn');
    branchButtons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = btn.innerHTML.replace(/^(.*)$/, '<span class="spinner">🔄</span> $1');
    });
    
    fetch('/game/move-to-branch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            direction: direction
        })
    })
    .then(response => ErrorHandler.handleApiResponse(
        response,
        (data) => {
            console.log('Branch movement result:', data);
            
            if (data.success) {
                // 移動成功時の処理
                if (data.message) {
                    showSuccessMessage(data.message);
                }
                
                // ゲーム画面を更新
                if (gameManager && typeof gameManager.updateGameDisplay === 'function') {
                    gameManager.updateGameDisplay(data);
                } else {
                    console.error('GameManager updateGameDisplay not available');
                }
                
                // 分岐選択UIを隠す
                hideBranchSelection();
                
                // サイコロボタンを再度有効化
                const rollDiceButton = document.getElementById('roll-dice');
                if (rollDiceButton) {
                    rollDiceButton.disabled = false;
                }
            } else {
                // 移動失敗時の処理
                showErrorMessage(data.error || '分岐移動に失敗しました');
                resetBranchButtons();
            }
        },
        (error) => {
            showErrorMessage(error.error || error.message || '分岐移動に失敗しました');
            resetBranchButtons();
        }
    ))
    .catch(error => {
        ErrorHandler.handleApiError(error, '分岐移動');
        resetBranchButtons();
    });
}

/**
 * 分岐選択ボタンをリセット
 */
function resetBranchButtons() {
    const branchButtons = document.querySelectorAll('.branch-btn');
    branchButtons.forEach(btn => {
        btn.disabled = false;
        // スピナーを削除
        btn.innerHTML = btn.innerHTML.replace(/<span class="spinner">🔄<\/span>\s*/, '');
    });
}

/**
 * 分岐選択UIを隠す
 */
function hideBranchSelection() {
    const branchSelection = document.getElementById('branch-selection');
    if (branchSelection) {
        branchSelection.style.display = 'none';
    }
}

/**
 * 成功メッセージを表示
 */
function showSuccessMessage(message) {
    // 簡易的なメッセージ表示（将来的にはより洗練されたUIに）
    const messageDiv = document.createElement('div');
    messageDiv.className = 'success-message';
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        border-radius: 4px;
        padding: 12px;
        z-index: 1000;
        max-width: 300px;
    `;
    messageDiv.textContent = message;
    document.body.appendChild(messageDiv);
    
    // 3秒後に自動削除
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.parentNode.removeChild(messageDiv);
        }
    }, 3000);
}

/**
 * エラーメッセージを表示
 */
function showErrorMessage(message) {
    // 簡易的なエラーメッセージ表示
    const messageDiv = document.createElement('div');
    messageDiv.className = 'error-message';
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        border-radius: 4px;
        padding: 12px;
        z-index: 1000;
        max-width: 300px;
    `;
    messageDiv.textContent = message;
    document.body.appendChild(messageDiv);
    
    // 5秒後に自動削除
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.parentNode.removeChild(messageDiv);
        }
    }, 5000);
}

/**
 * 複数接続システム
 * 町からの方向選択移動を処理
 */
function moveToDirection(direction) {
    console.log(`Direction movement: ${direction}`);
    
    // 方向選択ボタンを無効化
    const connectionButtons = document.querySelectorAll('.connection-btn');
    connectionButtons.forEach(btn => {
        btn.disabled = true;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<span class="spinner">🔄</span> 移動中...';
        btn.setAttribute('data-original-content', originalContent);
    });
    
    fetch('/game/move-to-direction', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            direction: direction
        })
    })
    .then(response => ErrorHandler.handleApiResponse(
        response,
        (data) => {
            console.log('Direction movement result:', data);
            
            if (data.success) {
                // 移動成功時の処理
                if (data.message) {
                    showSuccessMessage(data.message);
                }
                
                // ゲーム画面を更新
                if (gameManager && typeof gameManager.updateGameDisplay === 'function') {
                    gameManager.updateGameDisplay(data);
                } else {
                    console.error('GameManager updateGameDisplay not available');
                }
                
                // 複数接続UIを隠す
                hideMultipleConnections();
                
                // サイコロボタンを再度有効化
                const rollDiceButton = document.getElementById('roll-dice');
                if (rollDiceButton) {
                    rollDiceButton.disabled = false;
                }
            } else {
                // 移動失敗時の処理
                showErrorMessage(data.error || '方向移動に失敗しました');
                resetConnectionButtons();
            }
        },
        (error) => {
            showErrorMessage(error.error || error.message || '方向移動に失敗しました');
            resetConnectionButtons();
        }
    ))
    .catch(error => {
        ErrorHandler.handleApiError(error, '方向移動');
        resetConnectionButtons();
    });
}

/**
 * 複数接続ボタンをリセット
 */
function resetConnectionButtons() {
    const connectionButtons = document.querySelectorAll('.connection-btn');
    connectionButtons.forEach(btn => {
        btn.disabled = false;
        const originalContent = btn.getAttribute('data-original-content');
        if (originalContent) {
            btn.innerHTML = originalContent;
            btn.removeAttribute('data-original-content');
        }
    });
}

/**
 * 複数接続UIを隠す
 */
function hideMultipleConnections() {
    const multipleConnections = document.getElementById('multiple-connections');
    if (multipleConnections) {
        multipleConnections.style.display = 'none';
    }
}

/**
 * イベントデリゲーションを使用してゲームボタンのクリックを処理
 * 動的に追加されるボタンにも対応
 */
document.addEventListener('click', function(event) {
    // move-to-nextボタンがクリックされた場合
    if (event.target && event.target.id === 'move-to-next') {
        console.log('Move to next button clicked via event delegation');
        event.preventDefault();
        
        if (movementManager && typeof movementManager.moveToNextFromRoad === 'function') {
            console.log('Calling movementManager.moveToNextFromRoad()');
            movementManager.moveToNextFromRoad();
        } else if (gameManager && gameManager.movementManager && typeof gameManager.movementManager.moveToNextFromRoad === 'function') {
            console.log('Calling gameManager.movementManager.moveToNextFromRoad()');
            gameManager.movementManager.moveToNextFromRoad();
        } else {
            console.error('MovementManager or moveToNextFromRoad method not available');
        }
        return;
    }
    
    // ボタン内のspan要素がクリックされた場合も対応
    if (event.target && event.target.closest && event.target.closest('#move-to-next')) {
        console.log('Move to next button clicked via span element');
        event.preventDefault();
        
        if (movementManager && typeof movementManager.moveToNextFromRoad === 'function') {
            console.log('Calling movementManager.moveToNextFromRoad()');
            movementManager.moveToNextFromRoad();
        } else if (gameManager && gameManager.movementManager && typeof gameManager.movementManager.moveToNextFromRoad === 'function') {
            console.log('Calling gameManager.movementManager.moveToNextFromRoad()');
            gameManager.movementManager.moveToNextFromRoad();
        } else {
            console.error('MovementManager or moveToNextFromRoad method not available');
        }
        return;
    }
    
    // connection-btnボタンがクリックされた場合（町からの移動）
    let connectionButton = null;
    if (event.target && event.target.classList && event.target.classList.contains('connection-btn')) {
        connectionButton = event.target;
    } else if (event.target && event.target.closest && event.target.closest('.connection-btn')) {
        connectionButton = event.target.closest('.connection-btn');
    }
    
    if (connectionButton) {
        console.log('Connection button clicked via event delegation');
        event.preventDefault();
        
        const direction = connectionButton.getAttribute('data-direction');
        if (direction && typeof moveToDirection === 'function') {
            console.log('Calling moveToDirection with direction:', direction);
            moveToDirection(direction);
        } else {
            console.error('Direction not found or moveToDirection function not available');
        }
        return;
    }
});