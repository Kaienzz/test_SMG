<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>プレイヤーステータス - RPGゲーム</title>
    <style>
        :root {
            /* カラーシステム */
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --success: #059669;
            --warning: #d97706;
            --error: #dc2626;
            --info: #0284c7;
            
            --gray-50: #fafafa;
            --gray-100: #f4f4f5;
            --gray-200: #e4e4e7;
            --gray-300: #d4d4d8;
            --gray-500: #71717a;
            --gray-700: #3f3f46;
            --gray-900: #18181b;
            
            --bg-primary: #ffffff;
            --bg-secondary: #fafafa;
            --bg-game: #f8fafc;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-light: #e5e7eb;
            --border-medium: #d1d5db;
            
            /* スペーシング */
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            
            /* 角丸 */
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --radius-xl: 0.75rem;
            --radius-full: 9999px;
            
            /* 影 */
            --shadow-card: 0 2px 8px 0 rgba(0, 0, 0, 0.08);
            --shadow-button: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
            --shadow-button-hover: 0 3px 6px 0 rgba(0, 0, 0, 0.15);
            --shadow-focus: 0 0 0 3px rgba(59, 130, 246, 0.3);
            
            /* フォント */
            --font-primary: system-ui, -apple-system, 'Segoe UI', 'Noto Sans JP', sans-serif;
            --text-3xl: 1.875rem;
            --text-xl: 1.25rem;
            --text-lg: 1.125rem;
            --text-base: 1rem;
            --text-sm: 0.875rem;
            --font-medium: 500;
            --font-semibold: 600;
        }

        body {
            font-family: var(--font-primary);
            background: var(--bg-game);
            color: var(--text-primary);
            margin: 0;
            padding: var(--space-4);
        }

        .player-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .game-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: var(--space-5);
            box-shadow: var(--shadow-card);
            border: 2px solid var(--border-light);
            margin-bottom: var(--space-6);
        }

        .game-card-title {
            font-size: var(--text-xl);
            font-weight: var(--font-semibold);
            color: var(--text-primary);
            margin: 0 0 var(--space-4) 0;
            text-align: center;
        }

        .player-name {
            font-size: var(--text-3xl);
            font-weight: var(--font-semibold);
            text-align: center;
            color: var(--primary-500);
            margin-bottom: var(--space-4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .stat-section {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            border: 1px solid var(--border-light);
        }

        .stat-section h3 {
            font-size: var(--text-lg);
            font-weight: var(--font-semibold);
            margin: 0 0 var(--space-3) 0;
            color: var(--text-primary);
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-2) 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-weight: var(--font-medium);
            color: var(--text-secondary);
        }

        .stat-value {
            font-weight: var(--font-semibold);
            color: var(--text-primary);
        }

        .hp-mp-bars {
            margin: var(--space-4) 0;
        }

        .bar-container {
            margin-bottom: var(--space-3);
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--space-2);
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
        }

        .progress-bar {
            width: 100%;
            height: 1.5rem;
            background: var(--gray-200);
            border-radius: var(--radius-full);
            overflow: hidden;
            border: 1px solid var(--border-light);
        }

        .progress-fill {
            height: 100%;
            border-radius: var(--radius-full);
            transition: width 0.3s ease;
        }

        .hp-bar {
            background: linear-gradient(90deg, #dc2626, #16a34a);
        }

        .mp-bar {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }

        .sp-bar {
            background: linear-gradient(90deg, #f59e0b, #eab308);
        }

        .btn {
            padding: var(--space-3) var(--space-5);
            border-radius: var(--radius-md);
            font-family: var(--font-primary);
            font-weight: var(--font-medium);
            font-size: var(--text-lg);
            border: 2px solid transparent;
            cursor: pointer;
            display: inline-block;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            min-width: 120px;
            box-shadow: var(--shadow-button);
            margin: var(--space-2);
        }

        .btn:hover {
            box-shadow: var(--shadow-button-hover);
        }

        .btn:focus {
            box-shadow: var(--shadow-focus);
        }

        .btn-primary {
            background: var(--primary-500);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-600);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--text-primary);
            border-color: var(--border-medium);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .button-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin: var(--space-4) 0;
        }

        .level-info {
            text-align: center;
            background: linear-gradient(135deg, var(--primary-500), var(--info));
            color: white;
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            margin-bottom: var(--space-4);
        }

        .level-info .level {
            font-size: var(--text-3xl);
            font-weight: var(--font-semibold);
            margin-bottom: var(--space-2);
        }

        .experience-bar {
            background: rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-full);
            height: 0.75rem;
            margin-top: var(--space-2);
            overflow: hidden;
        }

        .experience-fill {
            background: white;
            height: 100%;
            border-radius: var(--radius-full);
            transition: width 0.3s ease;
        }

        .nav-link {
            display: inline-block;
            margin: var(--space-2);
            padding: var(--space-2) var(--space-4);
            background: var(--gray-100);
            color: var(--primary-500);
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: var(--font-medium);
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: var(--primary-50);
            color: var(--primary-600);
        }

        .message {
            margin: var(--space-4) 0;
            padding: var(--space-3);
            border-radius: var(--radius-md);
            text-align: center;
            font-weight: var(--font-medium);
            display: none;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .btn {
                min-width: 100px;
                font-size: var(--text-base);
            }
            
            .button-group {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="player-container">
        <nav>
            <a href="/inventory" class="nav-link">インベントリー</a>
            <a href="/skills" class="nav-link">スキル</a>
            <a href="/game" class="nav-link">← ゲームに戻る</a>
            <a href="/" class="nav-link">ホーム</a>
        </nav>

        <h1 class="player-name">{{ $player->name }}</h1>

        <div class="game-card">
            <div class="level-info">
                @php $player = $player ?? $character; @endphp
                <div class="level">Lv. {{ $player->level ?? 1 }}</div>
                <div>プレイヤーレベル</div>
                @if(isset($player->total_skill_level))
                    <div style="font-size: 0.9rem; margin-top: 0.5rem;">
                        総スキルレベル: {{ $player->total_skill_level }}
                    </div>
                @endif
            </div>
        </div>

        <div class="game-card">
            <div class="hp-mp-bars">
                <div class="bar-container">
                    <div class="bar-label">
                        <span>HP</span>
                        <span>{{ $player->hp }} / {{ $player->max_hp }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill hp-bar" style="width: {{ $player->getHpPercentage() }}%"></div>
                    </div>
                </div>

                <div class="bar-container">
                    <div class="bar-label">
                        <span>MP</span>
                        <span>{{ $player->mp }} / {{ $player->max_mp }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill mp-bar" style="width: {{ $player->getMpPercentage() }}%"></div>
                    </div>
                </div>

                <div class="bar-container">
                    <div class="bar-label">
                        <span>SP</span>
                        <span>{{ $player->sp }} / {{ $player->max_sp }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill sp-bar" style="width: {{ $player->getSpPercentage() }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-section">
                <h3>基本情報</h3>
                @foreach($stats['basic_info'] as $key => $value)
                    <div class="stat-item">
                        <span class="stat-label">
                            @switch($key)
                                @case('name') 名前 @break
                                @case('level') レベル @break
                                @default {{ $key }}
                            @endswitch
                        </span>
                        <span class="stat-value">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <div class="stat-section">
                <h3>戦闘ステータス</h3>
                @foreach($stats['combat_stats'] as $key => $value)
                    <div class="stat-item">
                        <span class="stat-label">
                            @switch($key)
                                @case('attack') 攻撃力 @break
                                @case('defense') 防御力 @break
                                @case('agility') 素早さ @break
                                @case('evasion') 回避 @break
                                @case('accuracy') 命中力 @break
                                @case('magic_attack') 魔力 @break
                                @default {{ $key }}
                            @endswitch
                        </span>
                        <span class="stat-value">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <div class="stat-section">
                <h3>HP/MP/SP</h3>
                <div class="stat-item">
                    <span class="stat-label">現在HP</span>
                    <span class="stat-value">{{ $player->hp }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">最大HP</span>
                    <span class="stat-value">{{ $player->max_hp }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">現在MP</span>
                    <span class="stat-value">{{ $player->mp }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">最大MP</span>
                    <span class="stat-value">{{ $player->max_mp }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">現在SP</span>
                    <span class="stat-value">{{ $player->sp }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">最大SP</span>
                    <span class="stat-value">{{ $player->max_sp }}</span>
                </div>
            </div>
        </div>

        <div id="message" class="message"></div>

        <div class="game-card">
            <h2 class="game-card-title">プレイヤー操作</h2>
            
            <div class="button-group">
                <button class="btn btn-success" onclick="healPlayer()">HP回復 (+10)</button>
                <button class="btn btn-primary" onclick="restoreMp()">MP回復 (+10)</button>
                <button class="btn btn-info" onclick="restoreSp()">SP回復 (+10)</button>
                <button class="btn btn-warning" onclick="gainExp()">経験値獲得 (+50)</button>
            </div>

            <div class="button-group">
                <button class="btn btn-secondary" onclick="takeDamage()">ダメージテスト (-10)</button>
                <button class="btn btn-secondary" onclick="resetPlayer()">リセット</button>
            </div>
        </div>
    </div>

    <script>
        function showMessage(text, type = 'success') {
            const messageEl = document.getElementById('message');
            messageEl.textContent = text;
            messageEl.className = `message ${type}`;
            messageEl.style.display = 'block';
            
            setTimeout(() => {
                messageEl.style.display = 'none';
            }, 3000);
        }

        function updatePlayerDisplay(player) {
            location.reload();
        }
        
        // 下位互換性のためのalias
        function updateCharacterDisplay(character) {
            updatePlayerDisplay(character);
        }
        
        function healCharacter() {
            healPlayer();
        }
        
        function resetCharacter() {
            resetPlayer();
        }

        function healPlayer() {
            fetch('/player/heal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ amount: 10 })
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message);
                updatePlayerDisplay(data.player || data.character);
            });
        }

        function restoreMp() {
            fetch('/player/restore-mp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ amount: 10 })
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message);
                updatePlayerDisplay(data.player || data.character);
            });
        }

        function restoreSp() {
            fetch('/player/restore-sp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ amount: 10 })
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message);
                updatePlayerDisplay(data.player || data.character);
            });
        }

        function gainExp() {
            fetch('/player/gain-experience', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ amount: 50 })
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message);
                updatePlayerDisplay(data.player || data.character);
            });
        }

        function takeDamage() {
            fetch('/player/take-damage', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ amount: 10 })
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message, data.is_alive ? 'success' : 'error');
                updatePlayerDisplay(data.player || data.character);
            });
        }

        function resetPlayer() {
            if (confirm('プレイヤーを初期状態にリセットしますか？')) {
                fetch('/player/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showMessage(data.message);
                    updatePlayerDisplay(data.player || data.character);
                });
            }
        }
    </script>
</body>
</html>