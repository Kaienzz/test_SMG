<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ブラウザゲーム - 町と道の冒険 (レイアウト最適版)</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/css/game-layout-optimized.css">
</head>
<body>
    <div class="game-layout">
        {{-- Header Area --}}
        <header class="game-header">
            <h1>町と道の冒険ゲーム</h1>
            <nav>
                <a href="/player">プレイヤー</a>
                <a href="/inventory">インベントリー</a>
                <a href="/">ホーム</a>
            </nav>
        </header>

        {{-- Location Info Area --}}
        <aside class="location-info-area">
            <div class="location-info">
                <h2 id="current-location">{{ $currentLocation->name ?? 'プリマ町' }}</h2>
                <p id="location-type">{{ ($player->location_type ?? 'town') === 'town' ? '町にいます' : '道を歩いています' }}</p>
                
                @if(($player->location_type ?? 'town') === 'town')
                    {{-- Shop Menu for Towns --}}
                    <div class="shop-menu">
                        <h3>町の施設</h3>
                        <a href="#" class="btn btn-primary" title="道具屋">
                            <span class="shop-icon">🏪</span>
                            道具屋
                        </a>
                        <a href="#" class="btn btn-primary" title="鍛冶屋">
                            <span class="shop-icon">⚒️</span>
                            鍛冶屋
                        </a>
                        <a href="#" class="btn btn-primary" title="宿屋">
                            <span class="shop-icon">🏨</span>
                            宿屋
                        </a>
                        <a href="#" class="btn btn-primary" title="神殿">
                            <span class="shop-icon">⛪</span>
                            神殿
                        </a>
                    </div>
                @endif
                
                @if(($player->location_type ?? 'town') === 'road')
                    {{-- Progress Bar for Roads --}}
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: {{ $player->game_position ?? 0 }}%"></div>
                        <div class="progress-text" id="progress-text">{{ $player->game_position ?? 0 }}/100</div>
                    </div>
                    
                    {{-- Road Actions --}}
                    <div class="road-actions">
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
                    </div>
                @endif
            </div>
        </aside>

        {{-- Main Actions Area --}}
        <main class="main-actions-area">
            {{-- Dice Container --}}
            @if(($player->current_location_type ?? 'town') === 'road')
                <div class="dice-container" id="dice-container">
                    <h3>サイコロを振って移動しよう！</h3>
                    
                    <div class="movement-info">
                        <h4>移動情報</h4>
                        <p>サイコロ数: 2個 (基本: 1個 + 装備効果: 1個)</p>
                        <p>サイコロボーナス: +0</p>
                        <p>移動倍率: 1.0倍</p>
                        <p>最小移動距離: 2歩</p>
                        <p>最大移動距離: 12歩</p>
                    </div>
                    
                    <div class="dice-controls">
                        <button class="btn btn-primary" id="roll-dice" onclick="rollDice()">サイコロを振る</button>
                        
                        <div class="dice-toggle">
                            <label class="toggle-label">
                                <input type="checkbox" id="dice-display-toggle" checked onchange="toggleDiceDisplay()">
                                <span class="toggle-text">🎲 ダイス表示</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="dice-display hidden" id="dice-result">
                        <div id="all-dice"></div>
                    </div>
                    
                    <div id="dice-total" class="hidden">
                        <div class="step-indicator">
                            <p>基本合計: <span id="base-total">0</span></p>
                            <p>ボーナス: +<span id="bonus">0</span></p>
                            <p>最終移動距離: <span id="final-movement">0</span>歩</p>
                            <p style="font-size: 0.75rem; color: var(--color-text-secondary);">左右のボタンで移動方向を選択してください</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="dice-container" id="dice-container">
                    <h3>{{ $currentLocation->name ?? 'プリマ町' }}にいます</h3>
                    <p>道路に移動すると、サイコロを振って移動できます。</p>
                    <p>町の施設を利用して、冒険の準備をしましょう。</p>
                </div>
            @endif

            {{-- Branch Selection (for roads) --}}
            @if(($player->location_type ?? 'town') === 'road')
                <div class="branch-selection hidden" id="branch-selection">
                    <h3>🛤️ 分岐点です</h3>
                    <p>進む方向を選択してください：</p>
                    
                    <div class="branch-options">
                        <button class="btn btn-warning branch-btn" onclick="selectBranch('straight')" data-direction="straight">
                            <span class="direction-icon">⬆️</span>
                            <div>
                                <strong>直進</strong><br>
                                <small>プリマ町</small>
                            </div>
                        </button>
                        <button class="btn btn-warning branch-btn" onclick="selectBranch('left')" data-direction="left">
                            <span class="direction-icon">⬅️</span>
                            <div>
                                <strong>左折</strong><br>
                                <small>森の道</small>
                            </div>
                        </button>
                        <button class="btn btn-warning branch-btn" onclick="selectBranch('right')" data-direction="right">
                            <span class="direction-icon">➡️</span>
                            <div>
                                <strong>右折</strong><br>
                                <small>山の道</small>
                            </div>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Multiple Connections (for towns) --}}
            @if(($player->location_type ?? 'town') === 'town')
                <div class="multiple-connections" id="multiple-connections">
                    <h3>🗺️ 複数の道が繋がっています</h3>
                    <p>進む方向を選択してください：</p>
                    
                    <div class="connection-options">
                        <button class="btn btn-success connection-btn" onclick="moveToDirection('north')" data-direction="north">
                            <div class="direction-info">
                                <span class="direction-icon">⬆️</span>
                                <div>
                                    <strong>北へ</strong><br>
                                    <small>プリマ街道</small>
                                </div>
                            </div>
                        </button>
                        <button class="btn btn-success connection-btn" onclick="moveToDirection('east')" data-direction="east">
                            <div class="direction-info">
                                <span class="direction-icon">➡️</span>
                                <div>
                                    <strong>東へ</strong><br>
                                    <small>森の街道</small>
                                </div>
                            </div>
                        </button>
                        <button class="btn btn-success connection-btn" onclick="moveToDirection('south')" data-direction="south">
                            <div class="direction-info">
                                <span class="direction-icon">⬇️</span>
                                <div>
                                    <strong>南へ</strong><br>
                                    <small>商業街道</small>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            @endif
        </main>

        {{-- Quick Actions Area --}}
        <aside class="quick-actions-area">
            {{-- Movement Controls --}}
            @if(($player->location_type ?? 'town') === 'road')
                <div class="movement-controls" id="movement-controls">
                    <button class="btn btn-warning" id="move-left" onclick="move('left')">←左に移動</button>
                    <button class="btn btn-warning" id="move-right" onclick="move('right')">→右に移動</button>
                </div>
            @endif

            {{-- Next Location Button --}}
            <div class="next-location" id="next-location-info">
                <p>次の場所: <strong>{{ $nextLocation->name ?? 'セカンダ町' }}</strong></p>
                <button class="btn btn-success" id="move-to-next" onclick="moveToNext()">
                    {{ $nextLocation->name ?? 'セカンダ町' }}に移動する
                </button>
            </div>

            {{-- Game Controls --}}
            <div class="controls">
                <button class="btn btn-danger" onclick="resetGame()">ゲームリセット</button>
            </div>

            {{-- Quick Status --}}
            <div class="quick-status" style="background: var(--color-surface-secondary); border-radius: var(--radius-md); padding: var(--spacing-3); margin-top: var(--spacing-3); border: 1px solid var(--color-border);">
                <h4 style="font-size: 0.875rem; margin-bottom: var(--spacing-2); color: var(--color-text-primary);">ステータス</h4>
                <div style="font-size: 0.75rem; color: var(--color-text-secondary);">
                    <p>レベル: <span style="font-weight: 600;">5</span></p>
                    <p>HP: <span style="font-weight: 600; color: #10b981;">85/100</span></p>
                    <p>MP: <span style="font-weight: 600; color: #3b82f6;">42/50</span></p>
                    <p>所持金: <span style="font-weight: 600; color: #f59e0b;">1,250G</span></p>
                </div>
            </div>
        </aside>
    </div>

    <script src="/js/game.js"></script>
    <script>
        // Demo game data for layout testing
        const gameData = {
            player: {
                name: "テストプレイヤー",
                location_type: "{{ $player->location_type ?? 'town' }}",
                location_id: "{{ $player->location_id ?? 'town_a' }}",
                game_position: {{ $player->game_position ?? 0 }}
            },
            character: {
                name: "テストプレイヤー",
                level: 5,
                hp: 85,
                max_hp: 100,
                mp: 42,
                max_mp: 50
            },
            currentLocation: {
                id: "{{ $currentLocation->id ?? 'town_a' }}",
                name: "{{ $currentLocation->name ?? 'プリマ町' }}",
                type: "{{ $currentLocation->type ?? 'town' }}"
            },
            nextLocation: {
                id: "{{ $nextLocation->id ?? 'town_b' }}",
                name: "{{ $nextLocation->name ?? 'セカンダ町' }}",
                type: "{{ $nextLocation->type ?? 'town' }}"
            }
        };

        // Initialize game with layout optimizations
        function initializeGame(data) {
            console.log('Game initialized with optimized layout', data);
            
            // Show/hide appropriate UI elements based on location type
            updateUIForLocationType(data.player.location_type);
            
            // Update progress bar if on road
            if (data.player.location_type === 'road') {
                updateProgressBar(data.player.game_position);
            }
        }

        function updateUIForLocationType(locationType) {
            const diceContainer = document.getElementById('dice-container');
            const movementControls = document.getElementById('movement-controls');
            const nextLocationInfo = document.getElementById('next-location-info');
            const branchSelection = document.getElementById('branch-selection');
            const multipleConnections = document.getElementById('multiple-connections');

            if (locationType === 'town') {
                // Show town UI
                if (multipleConnections) multipleConnections.classList.remove('hidden');
                if (branchSelection) branchSelection.classList.add('hidden');
                if (movementControls) movementControls.classList.add('hidden');
            } else if (locationType === 'road') {
                // Show road UI
                if (multipleConnections) multipleConnections.classList.add('hidden');
                if (movementControls) movementControls.classList.remove('hidden');
                // Branch selection visibility handled by game logic
            }
        }

        function updateProgressBar(position) {
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            
            if (progressFill) {
                progressFill.style.width = position + '%';
            }
            if (progressText) {
                progressText.textContent = position + '/100';
            }
        }

        // Stub functions for demo purposes
        function rollDice() {
            console.log('Rolling dice...');
            // Demo dice roll
            const dice1 = Math.floor(Math.random() * 6) + 1;
            const dice2 = Math.floor(Math.random() * 6) + 1;
            const total = dice1 + dice2;
            
            document.getElementById('base-total').textContent = total;
            document.getElementById('final-movement').textContent = total;
            document.getElementById('dice-total').classList.remove('hidden');
            document.getElementById('movement-controls').classList.remove('hidden');
        }

        function toggleDiceDisplay() {
            const diceResult = document.getElementById('dice-result');
            const toggle = document.getElementById('dice-display-toggle');
            
            if (toggle.checked) {
                diceResult.classList.remove('hidden');
            } else {
                diceResult.classList.add('hidden');
            }
        }

        function move(direction) {
            console.log('Moving', direction);
            // Demo movement logic
        }

        function moveToNext() {
            console.log('Moving to next location');
            // Demo next location logic
        }

        function moveToDirection(direction) {
            console.log('Moving to direction', direction);
            // Demo direction movement logic
        }

        function selectBranch(direction) {
            console.log('Selected branch', direction);
            // Demo branch selection logic
        }

        function resetGame() {
            console.log('Resetting game');
            // Demo reset logic
        }

        function performGathering() {
            console.log('Performing gathering');
            // Demo gathering logic
        }

        function showGatheringInfo() {
            console.log('Showing gathering info');
            // Demo gathering info
        }

        // Initialize the game
        initializeGame(gameData);
    </script>
</body>
</html>