<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ブラウザゲーム - 町と道の冒険</title>
    <style>
        .game-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .location-info {
            background: #f0f8ff;
            border: 2px solid #87ceeb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            position: relative;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4);
            border-radius: 15px;
            transition: width 0.3s ease;
        }
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
        }
        .controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: black;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .dice-container {
            background: #fff;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .movement-info {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: left;
        }
        .movement-info h4 {
            color: #0ea5e9;
            margin-bottom: 10px;
        }
        .movement-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .dice-display {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 15px 0;
        }
        .dice {
            width: 60px;
            height: 60px;
            border: 2px solid #333;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            background: white;
        }
        .hidden {
            display: none;
        }
        .movement-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }
        .dice-info {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            text-align: center;
        }
        .step-indicator {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
            margin: 10px 0;
        }
        .next-location {
            background: #e8f5e8;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="game-container">
        <nav style="margin-bottom: 20px;">
            <a href="/character" style="display: inline-block; margin-right: 10px; padding: 8px 16px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 5px;">キャラクター</a>
            <a href="/inventory" style="display: inline-block; margin-right: 10px; padding: 8px 16px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 5px;">インベントリー</a>
            <a href="/" style="display: inline-block; padding: 8px 16px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 5px;">ホーム</a>
        </nav>
        
        <h1>町と道の冒険ゲーム</h1>
        
        <div class="location-info">
            <h2 id="current-location">{{ $currentLocation->name }}</h2>
            <p id="location-type">{{ $player->current_location_type === 'town' ? '町にいます' : '道を歩いています' }}</p>
            
            @if($player->current_location_type === 'road')
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: {{ $player->position }}%"></div>
                    <div class="progress-text" id="progress-text">{{ $player->position }}/100</div>
                </div>
            @endif
        </div>

        @if($nextLocation)
            <div class="next-location" id="next-location-info" style="display: none;">
                <p>次の場所: <strong>{{ $nextLocation['name'] }}</strong></p>
                <button class="btn btn-success" id="move-to-next" onclick="moveToNext()">
                    {{ $nextLocation['name'] }}に移動する
                </button>
            </div>
        @endif

        @if($player->current_location_type === 'road')
            <div class="dice-container" id="dice-container">
                <h3>サイコロを振って移動しよう！</h3>
                
                <div class="movement-info">
                    <h4>移動情報</h4>
                    <p>サイコロ数: {{ $movementInfo['total_dice_count'] }}個 (基本: {{ $movementInfo['base_dice_count'] }}個 + 装備効果: {{ $movementInfo['extra_dice'] }}個)</p>
                    <p>サイコロボーナス: +{{ $movementInfo['dice_bonus'] }}</p>
                    <p>移動倍率: {{ $movementInfo['movement_multiplier'] }}倍</p>
                    <p>最小移動距離: {{ $movementInfo['min_possible_movement'] }}歩</p>
                    <p>最大移動距離: {{ $movementInfo['max_possible_movement'] }}歩</p>
                    @if(!empty($movementInfo['special_effects']))
                        <p>特殊効果: {{ implode(', ', $movementInfo['special_effects']) }}</p>
                    @endif
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
            </div>
        @else
            <div class="dice-container" id="dice-container">
                <h3>{{ $currentLocation->name }}にいます</h3>
                <p>道路に移動すると、サイコロを振って移動できます。</p>
                <p>今後、この町の店舗リストが表示される予定です。</p>
            </div>
        @endif

        <div class="movement-controls hidden" id="movement-controls">
            <button class="btn btn-warning" id="move-left" onclick="move('left')">←左に移動</button>
            <button class="btn btn-warning" id="move-right" onclick="move('right')">→右に移動</button>
        </div>

        <div class="controls">
            <button class="btn btn-danger" onclick="resetGame()">ゲームリセット</button>
        </div>
    </div>

    <script>
        let currentSteps = 0;
        let gameData = @json([
            'currentLocation' => $currentLocation,
            'player' => $player,
            'nextLocation' => $nextLocation
        ]);

        // 初期読み込み時の表示制御
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initial load - Player position:', gameData.player.position, 'Type:', gameData.player.current_location_type);
            console.log('Initial load - Next location:', gameData.nextLocation);
            
            // 道路でposition=100または0のとき、次の場所ボタンを表示
            if (gameData.player.current_location_type === 'road') {
                if ((gameData.player.position >= 100 || gameData.player.position <= 0) && gameData.nextLocation) {
                    console.log('Initial load - Showing button for road at boundary');
                    updateNextLocationDisplay(gameData.nextLocation, true);
                } else {
                    console.log('Initial load - Hiding button for road not at boundary');
                    updateNextLocationDisplay(gameData.nextLocation, false);
                }
            } else {
                // 町にいるときは次の場所ボタンを表示
                if (gameData.nextLocation) {
                    console.log('Initial load - Showing button for town');
                    updateNextLocationDisplay(gameData.nextLocation, true);
                } else {
                    console.log('Initial load - Hiding button for town (no next location)');
                    updateNextLocationDisplay(gameData.nextLocation, false);
                }
            }
        });

        function rollDice() {
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
                currentSteps = data.final_movement;
                
                const diceResult = document.getElementById('dice-result');
                const diceTotal = document.getElementById('dice-total');
                if (diceResult) diceResult.classList.remove('hidden');
                if (diceTotal) diceTotal.classList.remove('hidden');
                
                if (gameData.player.current_location_type === 'road') {
                    const movementControls = document.getElementById('movement-controls');
                    const moveLeft = document.getElementById('move-left');
                    const moveRight = document.getElementById('move-right');
                    if (movementControls) movementControls.classList.remove('hidden');
                    if (moveLeft) moveLeft.disabled = false;
                    if (moveRight) moveRight.disabled = false;
                } else {
                    alert('町にいるときはサイコロを振っても移動できません。道路に移動してください。');
                }
                
                const rollDiceButton = document.getElementById('roll-dice');
                if (rollDiceButton) rollDiceButton.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                const rollDiceButton = document.getElementById('roll-dice');
                if (rollDiceButton) rollDiceButton.disabled = false;
            });
        }

        function move(direction) {
            console.log('Move called with direction:', direction, 'currentSteps:', currentSteps);
            
            if (currentSteps <= 0) {
                alert('先にサイコロを振ってください！');
                return;
            }
            
            const moveLeft = document.getElementById('move-left');
            const moveRight = document.getElementById('move-right');
            if (moveLeft) moveLeft.disabled = true;
            if (moveRight) moveRight.disabled = true;
            
            const requestData = {
                direction: direction,
                steps: currentSteps
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
                    updateGameDisplay(data);
                    hideMovementControls();
                    
                    // gameDataのプレイヤー位置を更新
                    gameData.player.position = data.position;
                    
                    // position=100または0になったら次の道路ボタンを表示
                    if (data.position >= 100 && data.nextLocation) {
                        updateNextLocationDisplay(data.nextLocation, true);
                    } else if (data.position <= 0 && data.nextLocation) {
                        updateNextLocationDisplay(data.nextLocation, true);
                    } else {
                        updateNextLocationDisplay(data.nextLocation, false);
                    }
                    
                    hideDiceResult();
                } else {
                    alert(data.message || '移動に失敗しました');
                    const moveLeft = document.getElementById('move-left');
                    const moveRight = document.getElementById('move-right');
                    if (moveLeft) moveLeft.disabled = false;
                    if (moveRight) moveRight.disabled = false;
                }
            })
            .catch(error => {
                console.error('Move error details:', error);
                alert('移動中にエラーが発生しました: ' + error.message);
                const moveLeft = document.getElementById('move-left');
                const moveRight = document.getElementById('move-right');
                if (moveLeft) moveLeft.disabled = false;
                if (moveRight) moveRight.disabled = false;
            });
        }

        function moveToNext() {
            fetch('/game/move-to-next', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                updateGameDisplay(data);
                gameData.player.current_location_type = data.currentLocation.name.includes('町') ? 'town' : 'road';
                gameData.player.position = data.position;
                
                // 移動後の次の場所ボタンの表示制御
                if (gameData.player.current_location_type === 'town') {
                    // 町にいるときは次の道路ボタンを表示
                    if (data.nextLocation) {
                        updateNextLocationDisplay(data.nextLocation, true);
                    } else {
                        updateNextLocationDisplay(data.nextLocation, false);
                    }
                } else {
                    // 道路にいるときは端にいる場合のみ次の場所ボタンを表示
                    if ((data.position >= 100 || data.position <= 0) && data.nextLocation) {
                        updateNextLocationDisplay(data.nextLocation, true);
                    } else {
                        updateNextLocationDisplay(data.nextLocation, false);
                    }
                }
                
                hideMovementControls();
                hideDiceResult();
            });
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
                updateGameDisplay(data);
                updateNextLocationDisplay(data.nextLocation, false);
                hideMovementControls();
                hideDiceResult();
            });
        }

        function updateGameDisplay(data) {
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
                hideMovementControls();
                hideDiceResult();
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
                    diceContainer.innerHTML = `
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
            }
            
            gameData.player.current_location_type = data.currentLocation.name.includes('町') ? 'town' : 'road';
            gameData.player.position = data.position;
            
            // 位置更新後にボタンの表示状態を確認
            console.log('Position updated to:', data.position, 'Type:', gameData.player.current_location_type);
        }

        function updateNextLocationDisplay(nextLocation, canMove) {
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

        function hideMovementControls() {
            const movementControls = document.getElementById('movement-controls');
            if (movementControls) {
                movementControls.classList.add('hidden');
            }
        }

        function hideDiceResult() {
            const diceResult = document.getElementById('dice-result');
            const diceTotal = document.getElementById('dice-total');
            if (diceResult) {
                diceResult.classList.add('hidden');
            }
            if (diceTotal) {
                diceTotal.classList.add('hidden');
            }
            currentSteps = 0;
        }
    </script>
</body>
</html>