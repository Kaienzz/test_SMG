<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>スキル - RPGゲーム</title>
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

        .skills-container {
            max-width: 1200px;
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

        .character-name {
            font-size: var(--text-3xl);
            font-weight: var(--font-semibold);
            text-align: center;
            color: var(--primary-500);
            margin-bottom: var(--space-4);
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .skill-section {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            border: 1px solid var(--border-light);
        }

        .skill-section h3 {
            font-size: var(--text-lg);
            font-weight: var(--font-semibold);
            margin: 0 0 var(--space-3) 0;
            color: var(--text-primary);
        }

        .skill-item {
            background: var(--bg-primary);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            margin-bottom: var(--space-3);
            transition: all 0.2s ease;
        }

        .skill-item:hover {
            border-color: var(--primary-500);
            box-shadow: var(--shadow-card);
        }

        .skill-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-3);
        }

        .skill-name {
            font-weight: var(--font-semibold);
            color: var(--primary-500);
            font-size: var(--text-lg);
        }

        .skill-level {
            background: var(--success);
            color: white;
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-full);
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
        }

        .skill-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-2);
            margin-bottom: var(--space-3);
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

        .experience-bar {
            background: var(--gray-200);
            border-radius: var(--radius-full);
            height: 1rem;
            margin: var(--space-2) 0;
            overflow: hidden;
        }

        .experience-fill {
            background: linear-gradient(90deg, var(--primary-500), var(--info));
            height: 100%;
            border-radius: var(--radius-full);
            transition: width 0.3s ease;
        }

        .sp-bar {
            background: var(--gray-200);
            border-radius: var(--radius-full);
            height: 1.5rem;
            overflow: hidden;
            border: 1px solid var(--border-light);
        }

        .sp-fill {
            background: linear-gradient(90deg, #f59e0b, #eab308);
            height: 100%;
            border-radius: var(--radius-full);
            transition: width 0.3s ease;
        }

        .btn {
            padding: var(--space-3) var(--space-5);
            border-radius: var(--radius-md);
            font-family: var(--font-primary);
            font-weight: var(--font-medium);
            font-size: var(--text-base);
            border: 2px solid transparent;
            cursor: pointer;
            display: inline-block;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-button);
        }

        .btn:hover {
            box-shadow: var(--shadow-button-hover);
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

        .btn-secondary {
            background: var(--gray-100);
            color: var(--text-primary);
            border-color: var(--border-medium);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .btn:disabled {
            background: var(--gray-300);
            color: var(--gray-500);
            cursor: not-allowed;
            box-shadow: none;
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

        .skill-type-badge {
            background: var(--info);
            color: white;
            padding: 2px 8px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: var(--font-medium);
        }

        .skill-type-badge.gathering {
            background: var(--success);
        }

        .skill-type-badge.combat {
            background: var(--error);
        }

        .skill-type-badge.utility {
            background: var(--warning);
        }

        @media (max-width: 768px) {
            .skills-grid {
                grid-template-columns: 1fr;
            }
            
            .skill-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="skills-container">
        <nav>
            <a href="/character" class="nav-link">キャラクター</a>
            <a href="/inventory" class="nav-link">インベントリー</a>
            <a href="/game" class="nav-link">← ゲームに戻る</a>
            <a href="/" class="nav-link">ホーム</a>
        </nav>

        <h1 class="character-name">{{ $character->name }} - スキル</h1>

        <div class="game-card">
            <h2 class="game-card-title">SPステータス</h2>
            <div class="stat-item">
                <span class="stat-label">現在SP</span>
                <span class="stat-value" id="current-sp">{{ $character->sp }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">最大SP</span>
                <span class="stat-value">{{ $character->max_sp }}</span>
            </div>
            <div class="sp-bar" style="margin-top: var(--space-3);">
                <div class="sp-fill" style="width: {{ ($character->sp / $character->max_sp) * 100 }}%"></div>
            </div>
        </div>

        <div class="skills-grid">
            <div class="game-card">
                <h2 class="game-card-title">習得済みスキル</h2>
                <div id="skills-list">
                    @forelse($skills as $skill)
                        <div class="skill-item" data-skill-id="{{ $skill['id'] }}">
                            <div class="skill-header">
                                <div class="skill-name">{{ $skill['name'] }}</div>
                                <div class="skill-level">Lv.{{ $skill['level'] }}</div>
                            </div>
                            <div style="margin-bottom: var(--space-3);">
                                <span class="skill-type-badge {{ $skill['type'] }}">{{ $skill['type'] }}</span>
                            </div>
                            <div class="skill-section">
                                <div class="stat-item">
                                    <span class="stat-label">SP消費</span>
                                    <span class="stat-value">{{ $skill['sp_cost'] }}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">経験値</span>
                                    <span class="stat-value">{{ $skill['experience'] }}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">次のレベルまで</span>
                                    <span class="stat-value">{{ $skill['next_level_exp'] ?? '最大' }}</span>
                                </div>
                            </div>
                            @if($skill['experience'] > 0 && isset($skill['next_level_exp']))
                                <div class="experience-bar">
                                    <div class="experience-fill" style="width: {{ ($skill['experience'] / ($skill['experience'] + $skill['next_level_exp'])) * 100 }}%"></div>
                                </div>
                            @endif
                            <div class="skill-section" style="margin: var(--space-3) 0;">
                                <h4 style="margin-bottom: var(--space-2); color: var(--text-secondary);">効果</h4>
                                @foreach($skill['effects'] as $effect => $value)
                                    <div class="stat-item">
                                        <span class="stat-label">{{ $effect }}</span>
                                        <span class="stat-value">
                                            @if(is_numeric($value))
                                                +{{ $value }}
                                            @else
                                                {{ $value ? 'あり' : 'なし' }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                            <button class="btn btn-primary" style="width: 100%;" 
                                    onclick="useSkill('{{ $skill['name'] }}')"
                                    {{ !$skill['can_use'] ? 'disabled' : '' }}>
                                スキル使用 (SP {{ $skill['sp_cost'] }})
                            </button>
                        </div>
                    @empty
                        <p style="text-align: center; color: var(--text-secondary);">スキルを習得していません</p>
                    @endforelse
                </div>
            </div>

            <div class="game-card">
                <h2 class="game-card-title">アクティブ効果</h2>
                <div id="active-effects">
                    @forelse($activeEffects as $effect)
                        <div class="skill-item">
                            <div class="skill-header">
                                <div class="skill-name">{{ $effect->effect_name }}</div>
                                <div class="skill-level">{{ $effect->remaining_duration }}ターン</div>
                            </div>
                            <div class="skill-section">
                                <h4 style="margin-bottom: var(--space-2); color: var(--text-secondary);">効果内容</h4>
                                @foreach($effect->effects as $effectType => $value)
                                    <div class="stat-item">
                                        <span class="stat-label">{{ $effectType }}</span>
                                        <span class="stat-value">
                                            @if(is_numeric($value))
                                                +{{ $value }}
                                            @else
                                                {{ $value ? 'あり' : 'なし' }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p style="text-align: center; color: var(--text-secondary);">アクティブ効果なし</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="game-card">
            <h2 class="game-card-title">習得可能スキル</h2>
            <div class="skills-grid">
                @foreach($sampleSkills as $skill)
                    <div class="skill-item" style="cursor: pointer;" onclick="addSampleSkill('{{ $skill['skill_name'] }}')">
                        <div class="skill-header">
                            <div class="skill-name">{{ $skill['skill_name'] }}</div>
                            <span class="skill-type-badge {{ $skill['skill_type'] }}">{{ $skill['skill_type'] }}</span>
                        </div>
                        <p style="color: var(--text-secondary); margin-bottom: var(--space-3);">
                            {{ $skill['description'] }}
                        </p>
                        <div class="skill-section">
                            <div class="stat-item">
                                <span class="stat-label">SP消費</span>
                                <span class="stat-value">{{ $skill['sp_cost'] }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">持続時間</span>
                                <span class="stat-value">{{ $skill['duration'] }}ターン</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">最大レベル</span>
                                <span class="stat-value">{{ $skill['max_level'] }}</span>
                            </div>
                        </div>
                        @if(!empty($skill['effects']))
                            <div class="skill-section" style="margin-top: var(--space-3);">
                                <h4 style="margin-bottom: var(--space-2); color: var(--text-secondary);">効果</h4>
                                @foreach($skill['effects'] as $effect => $value)
                                    <div class="stat-item">
                                        <span class="stat-label">{{ $effect }}</span>
                                        <span class="stat-value">
                                            @if(is_numeric($value))
                                                +{{ $value }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <button class="btn btn-success" style="width: 100%; margin-top: var(--space-3);">
                            習得する
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <div id="message" class="message"></div>
    </div>

    <script>
        const characterId = {{ $character->id }};

        function showMessage(text, type = 'success') {
            const message = document.getElementById('message');
            message.textContent = text;
            message.className = `message ${type}`;
            message.style.display = 'block';
            
            setTimeout(() => {
                message.style.display = 'none';
            }, 3000);
        }

        function useSkill(skillName) {
            fetch('/skills/use', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    character_id: characterId,
                    skill_name: skillName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    updateSp(data.character_sp);
                    if (data.leveled_up) {
                        showMessage(`スキルがレベルアップしました！現在Lv.${data.skill_level}`, 'success');
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('エラーが発生しました', 'error');
            });
        }

        function addSampleSkill(skillName) {
            fetch('/skills/add-sample', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    character_id: characterId,
                    skill_name: skillName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('エラーが発生しました', 'error');
            });
        }

        function updateSp(newSp) {
            document.getElementById('current-sp').textContent = newSp;
            const maxSp = {{ $character->max_sp }};
            const percentage = (newSp / maxSp) * 100;
            document.querySelector('.sp-fill').style.width = `${percentage}%`;
        }

        function showMessage(text, type = 'success') {
            const messageEl = document.getElementById('message');
            messageEl.textContent = text;
            messageEl.className = `message ${type}`;
            messageEl.style.display = 'block';
            
            setTimeout(() => {
                messageEl.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>