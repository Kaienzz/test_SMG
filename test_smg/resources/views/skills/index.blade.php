<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スキル管理 - {{ $character->name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ecf0f1;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #f39c12;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .character-info {
            background: rgba(52, 73, 94, 0.8);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 2px solid #f39c12;
        }

        .sp-display {
            background: rgba(52, 152, 219, 0.2);
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #3498db;
            margin-bottom: 20px;
            text-align: center;
        }

        .sp-bar {
            width: 100%;
            height: 20px;
            background: #34495e;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .sp-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2980b9);
            transition: width 0.3s ease;
        }

        .skills-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .skills-panel, .effects-panel {
            background: rgba(44, 62, 80, 0.9);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #34495e;
        }

        .panel-title {
            color: #e74c3c;
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 10px;
        }

        .skill-item {
            background: rgba(52, 73, 94, 0.8);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px solid #7f8c8d;
        }

        .skill-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .skill-name {
            color: #f39c12;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .skill-level {
            color: #27ae60;
            font-size: 0.9rem;
        }

        .skill-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .skill-effects {
            background: rgba(39, 174, 96, 0.1);
            padding: 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 10px;
        }

        .use-skill-btn {
            width: 100%;
            padding: 8px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .use-skill-btn:hover {
            background: #229954;
        }

        .use-skill-btn:disabled {
            background: #7f8c8d;
            cursor: not-allowed;
        }

        .active-effect {
            background: rgba(155, 89, 182, 0.8);
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px solid #9b59b6;
        }

        .effect-name {
            color: #e67e22;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .effect-duration {
            color: #3498db;
            font-size: 0.9rem;
        }

        .sample-skills {
            background: rgba(142, 68, 173, 0.2);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #8e44ad;
            margin-top: 30px;
        }

        .sample-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .sample-skill {
            background: rgba(52, 73, 94, 0.8);
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #8e44ad;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sample-skill:hover {
            border-color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
        }

        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
        }

        .btn-success:hover {
            background: #229954;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
        }

        .message.success {
            background: #27ae60;
            color: white;
        }

        .message.error {
            background: #e74c3c;
            color: white;
        }

        .skill-type {
            background: rgba(52, 152, 219, 0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            color: #3498db;
            border: 1px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>スキル管理</h1>
        </div>

        <div class="character-info">
            <h3>{{ $character->name }} (Lv.{{ $character->level }})</h3>
            <div class="sp-display">
                <div>SP: <span id="current-sp">{{ $character->sp }}</span> / {{ $character->max_sp }}</div>
                <div class="sp-bar">
                    <div class="sp-fill" style="width: {{ ($character->sp / $character->max_sp) * 100 }}%"></div>
                </div>
            </div>
        </div>

        <div class="skills-container">
            <div class="skills-panel">
                <h3 class="panel-title">習得スキル</h3>
                
                <div id="skills-list">
                    @forelse($skills as $skill)
                        <div class="skill-item" data-skill-id="{{ $skill['id'] }}">
                            <div class="skill-header">
                                <div class="skill-name">{{ $skill['name'] }}</div>
                                <div class="skill-level">Lv.{{ $skill['level'] }}</div>
                            </div>
                            <div class="skill-info">
                                <div>種別: <span class="skill-type">{{ $skill['type'] }}</span></div>
                                <div>SP消費: {{ $skill['sp_cost'] }}</div>
                                <div>経験値: {{ $skill['experience'] }}</div>
                                <div>使用可能: {{ $skill['can_use'] ? 'はい' : 'いいえ' }}</div>
                            </div>
                            <div class="skill-effects">
                                効果: 
                                @foreach($skill['effects'] as $effect => $value)
                                    @if(is_numeric($value))
                                        {{ $effect }}: +{{ $value }} 
                                    @else
                                        {{ $effect }}: {{ $value ? 'あり' : 'なし' }} 
                                    @endif
                                @endforeach
                            </div>
                            <button class="use-skill-btn" 
                                    onclick="useSkill('{{ $skill['name'] }}')"
                                    {{ !$skill['can_use'] ? 'disabled' : '' }}>
                                スキル使用 (SP {{ $skill['sp_cost'] }})
                            </button>
                        </div>
                    @empty
                        <p style="text-align: center; color: #bdc3c7;">スキルを習得していません</p>
                    @endforelse
                </div>
            </div>

            <div class="effects-panel">
                <h3 class="panel-title">アクティブ効果</h3>
                
                <div id="active-effects">
                    @forelse($activeEffects as $effect)
                        <div class="active-effect">
                            <div class="effect-name">{{ $effect->effect_name }}</div>
                            <div class="effect-duration">残り時間: {{ $effect->remaining_duration }}ターン</div>
                            <div style="font-size: 0.8rem; margin-top: 5px;">
                                効果: 
                                @foreach($effect->effects as $effectType => $value)
                                    @if(is_numeric($value))
                                        {{ $effectType }}: +{{ $value }} 
                                    @else
                                        {{ $effectType }}: {{ $value ? 'あり' : 'なし' }} 
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p style="text-align: center; color: #bdc3c7;">アクティブ効果なし</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="sample-skills">
            <h3 class="panel-title">習得可能スキル</h3>
            <div class="sample-items">
                @foreach($sampleSkills as $skill)
                    <div class="sample-skill" onclick="addSampleSkill('{{ $skill['skill_name'] }}')">
                        <div class="skill-name">{{ $skill['skill_name'] }}</div>
                        <div style="margin: 8px 0; font-size: 0.9rem; color: #bdc3c7;">
                            {{ $skill['description'] }}
                        </div>
                        <div style="font-size: 0.8rem;">
                            SP消費: {{ $skill['sp_cost'] }} | 持続: {{ $skill['duration'] }}ターン<br>
                            効果: 
                            @foreach($skill['effects'] as $effect => $value)
                                @if(is_numeric($value))
                                    {{ $effect }}: +{{ $value }} 
                                @endif
                            @endforeach
                        </div>
                        <button class="btn btn-success" style="margin-top: 10px; width: 100%;">習得</button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="message" id="message"></div>

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
    </script>
</body>
</html>