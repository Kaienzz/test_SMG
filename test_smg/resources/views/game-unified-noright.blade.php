<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        @if($gameState === 'town')
            {{ $currentLocation->name ?? 'プリマ町' }} - 町と道の冒険
        @elseif($gameState === 'road')
            {{ $currentLocation->name ?? 'プリマ街道' }} - 町と道の冒険
        @elseif($gameState === 'battle')
            戦闘 - 町と道の冒険
        @else
            町と道の冒険
        @endif
    </title>
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    {{-- Unified Layout CSS --}}
    <link rel="stylesheet" href="/css/game-unified-layout.css">
    
    {{-- State-specific CSS (if needed) --}}
    @if($gameState === 'battle')
        <style>
            /* Battle-specific enhancements */
            .battle-field {
                background: linear-gradient(135deg, var(--color-surface-secondary) 0%, #f3f4f6 100%);
            }
            
            .enemy-emoji {
                text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
                animation: enemyPulse 2s ease-in-out infinite;
            }
            
            @keyframes enemyPulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            
            .log-entry {
                animation: logEntrySlide 0.3s ease-out;
            }
            
            @keyframes logEntrySlide {
                from {
                    opacity: 0;
                    transform: translateX(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
        </style>
    @endif
</head>
<body>
    <div class="game-unified-layout game-layout-noright" 
         data-game-state="{{ $gameState }}"
         data-location-id="{{ $currentLocation->id ?? 'town_a' }}"
         data-location-type="{{ $currentLocation->type ?? 'town' }}">
        {{-- Header Area --}}
        <header class="unified-header">
            <div class="header-left">
                <h1 class="header-title">町と道の冒険ゲーム</h1>
                <div class="game-state-indicator {{ $gameState }}">
                    @if($gameState === 'town')
                        <span>🏘️ 町</span>
                    @elseif($gameState === 'road')
                        <span>🛤️ 道路</span>
                    @elseif($gameState === 'battle')
                        <span>⚔️ 戦闘</span>
                    @else
                        <span>🎮 ゲーム</span>
                    @endif
                </div>
            </div>
            
            <nav class="header-nav">
                @if($gameState !== 'battle')
                    <a href="/player">プレイヤー</a>
                    <a href="/inventory">インベントリー</a>
                    <a href="/skills">スキル</a>
                @endif
                <a href="/">ホーム</a>
            </nav>
        </header>

        {{-- Background Image Area --}}
        <div class="unified-background-image">
            {{-- Background images will be displayed here via CSS --}}
            
            {{-- Player Status Overlay --}}
            <div class="background-player-status">
                <div class="status-item-compact">
                    <span class="stat-label">HP</span>
                    <span class="stat-value hp">{{ $player->hp ?? $character['hp'] ?? 100 }}/{{ $player->max_hp ?? $character['max_hp'] ?? 100 }}</span>
                </div>
                <div class="status-item-compact">
                    <span class="stat-label">MP</span>
                    <span class="stat-value mp">{{ $player->mp ?? $character['mp'] ?? 20 }}/{{ $player->max_mp ?? $character['max_mp'] ?? 20 }}</span>
                </div>
                <div class="status-item-compact">
                    <span class="stat-label">SP</span>
                    <span class="stat-value sp">{{ $player->sp ?? $character['sp'] ?? 30 }}/{{ $player->max_sp ?? $character['max_sp'] ?? 30 }}</span>
                </div>
            </div>
        </div>

        {{-- Left Area (Merged with Right content) --}}
        <aside class="unified-left-area">
            @if($gameState === 'town')
                @include('game-states-noright.town-left-merged', [
                    'player' => $player ?? $character,
                    'currentLocation' => $currentLocation
                ])
            @elseif($gameState === 'road')
                @include('game-states-noright.road-left-merged', [
                    'player' => $player ?? $character,
                    'currentLocation' => $currentLocation,
                    'nextLocation' => $nextLocation ?? null,
                    'movementInfo' => $movementInfo ?? []
                ])
            @elseif($gameState === 'battle')
                @include('game-states-noright.battle-left-merged', [
                    'character' => $character ?? $player,
                    'monster' => $monster ?? []
                ])
            @endif
        </aside>

        {{-- Main Area --}}
        <main class="unified-main-area">
            @if($gameState === 'town')
                @include('game-states.town-main', [
                    'player' => $player ?? $character,
                    'currentLocation' => $currentLocation
                ])
            @elseif($gameState === 'road')
                @include('game-states.road-main', [
                    'player' => $player ?? $character,
                    'currentLocation' => $currentLocation,
                    'nextLocation' => $nextLocation ?? null,
                    'movementInfo' => $movementInfo ?? []
                ])
            @elseif($gameState === 'battle')
                @include('game-states.battle-main', [
                    'character' => $character ?? $player,
                    'monster' => $monster ?? [],
                    'battle' => $battle ?? []
                ])
            @endif
        </main>
    </div>

    {{-- Unified JavaScript --}}
    <script src="/js/game-unified.js"></script>
    
    <script>
        // Game initialization data
        const gameData = {
            gameState: '{{ $gameState }}',
            player: @json($player ?? $character ?? []),
            character: @json($character ?? $player ?? []),
            currentLocation: @json($currentLocation ?? []),
            nextLocation: @json($nextLocation ?? []),
            @if($gameState === 'battle')
                monster: @json($monster ?? []),
                battle: @json($battle ?? []),
            @endif
            @if($gameState === 'road')
                movementInfo: @json($movementInfo ?? []),
            @endif
            
            // CSRF token for AJAX requests
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };

        // Initialize the unified game system
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof initializeUnifiedGame === 'function') {
                initializeUnifiedGame(gameData);
            } else {
                console.warn('Unified game initialization function not found');
            }
            
            // Add state change animations
            addStateTransitionEffects();
        });

        // Add transition effects when switching between states
        function addStateTransitionEffects() {
            const layout = document.querySelector('.game-unified-layout');
            const areas = layout.querySelectorAll('.unified-left-area, .unified-background-image, .unified-main-area');
            
            // Add entrance animation
            areas.forEach((area, index) => {
                area.style.animationDelay = (index * 0.1) + 's';
            });
        }

        // Global functions for state management
        window.changeGameState = function(newState, data = {}) {
            console.log('Changing game state to:', newState);
            
            // Add loading state
            const layout = document.querySelector('.game-unified-layout');
            layout.classList.add('state-changing');
            
            // Update state indicator
            const stateIndicator = document.querySelector('.game-state-indicator');
            stateIndicator.className = `game-state-indicator ${newState}`;
            
            const stateText = {
                'town': '🏘️ 町',
                'road': '🛤️ 道路',
                'battle': '⚔️ 戦闘'
            };
            
            stateIndicator.innerHTML = `<span>${stateText[newState] || '🎮 ゲーム'}</span>`;
            
            // In a real implementation, this would trigger a server request
            // to get new state data and re-render the appropriate partials
            setTimeout(() => {
                layout.classList.remove('state-changing');
                layout.setAttribute('data-game-state', newState);
            }, 300);
        };

        // Error handling for the unified system
        window.addEventListener('error', function(e) {
            console.error('Unified Game Error:', e.error);
            
            // Show user-friendly error message
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-notification';
            errorMessage.innerHTML = `
                <div class="error-content">
                    <span class="error-icon">⚠️</span>
                    <span class="error-text">ゲーム中にエラーが発生しました。ページを再読み込みしてください。</span>
                    <button class="error-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            errorMessage.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--color-danger-500);
                color: white;
                padding: var(--spacing-4);
                border-radius: var(--radius-md);
                box-shadow: var(--shadow-lg);
                z-index: 10000;
                max-width: 400px;
            `;
            
            document.body.appendChild(errorMessage);
            
            // Auto-remove after 10 seconds
            setTimeout(() => {
                if (errorMessage.parentElement) {
                    errorMessage.remove();
                }
            }, 10000);
        });

        // Keyboard shortcuts for the unified system
        document.addEventListener('keydown', function(e) {
            // Only handle shortcuts when not in an input field
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            const gameState = gameData.gameState;
            
            // Common shortcuts
            switch(e.key) {
                case 'i':
                case 'I':
                    if (gameState !== 'battle') {
                        window.location.href = '/inventory';
                    }
                    break;
                case 'p':
                case 'P':
                    if (gameState !== 'battle') {
                        window.location.href = '/player';
                    }
                    break;
                case 'Escape':
                    if (gameState === 'battle' && typeof returnToGame === 'function') {
                        returnToGame();
                    }
                    break;
            }
            
            // State-specific shortcuts
            if (gameState === 'road') {
                switch(e.key) {
                    case ' ':
                    case 'Enter':
                        e.preventDefault();
                        if (typeof rollDice === 'function') {
                            rollDice();
                        }
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        if (typeof move === 'function') {
                            move('left');
                        }
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        if (typeof move === 'function') {
                            move('right');
                        }
                        break;
                }
            } else if (gameState === 'battle') {
                switch(e.key) {
                    case '1':
                        if (typeof performAction === 'function') {
                            performAction('attack');
                        }
                        break;
                    case '2':
                        if (typeof performAction === 'function') {
                            performAction('defend');
                        }
                        break;
                    case '3':
                        if (typeof toggleSkillMenu === 'function') {
                            toggleSkillMenu();
                        }
                        break;
                    case '4':
                        if (typeof performAction === 'function') {
                            performAction('escape');
                        }
                        break;
                }
            }
        });
    </script>

    {{-- State-specific JavaScript --}}
    @if($gameState === 'battle')
        <script>
            // Battle-specific initialization
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize battle data if available
                if (gameData.battle && gameData.monster) {
                    console.log('Initializing battle with data:', gameData.battle);
                    
                    // Set up battle-specific event listeners
                    setupBattleEventListeners();
                    
                    // Initialize skill menu
                    if (typeof updateSkillMenu === 'function' && gameData.character) {
                        updateSkillMenu(gameData.character);
                    }
                }
            });
            
            function setupBattleEventListeners() {
                // Close skill menu when clicking outside
                document.addEventListener('click', function(event) {
                    const skillMenu = document.getElementById('skill-menu');
                    const skillButton = document.getElementById('skill-button');
                    
                    if (skillMenu && skillButton) {
                        if (!skillButton.contains(event.target) && !skillMenu.contains(event.target)) {
                            skillMenu.classList.add('hidden');
                        }
                    }
                });
                
                // Prevent form submission on button clicks
                document.querySelectorAll('.action-btn').forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                    });
                });
            }
        </script>
    @endif

    {{-- Additional CSS for state transitions --}}
    <style>
        .state-changing {
            pointer-events: none;
        }
        
        .state-changing .unified-left-area,
        .state-changing .unified-background-image,
        .state-changing .unified-main-area {
            opacity: 0.7;
            transform: scale(0.98);
            transition: all var(--transition-normal);
        }
        
        .error-notification .error-content {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
        }
        
        .error-close {
            background: none;
            border: none;
            color: white;
            font-size: var(--font-size-lg);
            cursor: pointer;
            margin-left: auto;
        }
        
        /* Loading spinner for state changes */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>