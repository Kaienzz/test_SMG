<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>戦闘 - ブラウザゲーム</title>
    <style>
        .battle-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .battle-area {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .character-info, .monster-info {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        .character-info {
            border-color: #007bff;
        }
        .monster-info {
            border-color: #dc3545;
        }
        .monster-emoji {
            font-size: 80px;
            margin: 20px 0;
        }
        .character-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .monster-name {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 10px;
        }
        .hp-bar {
            width: 100%;
            height: 25px;
            background: #e9ecef;
            border-radius: 12px;
            position: relative;
            margin: 15px 0;
        }
        .hp-fill {
            height: 100%;
            border-radius: 12px;
            transition: width 0.3s ease;
        }
        .character-hp {
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        .monster-hp {
            background: linear-gradient(90deg, #dc3545, #e74c3c);
        }
        .hp-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
            font-size: 14px;
        }
        .stat {
            background: #e9ecef;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
        }
        .battle-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        .action-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .attack-btn {
            background: #dc3545;
            color: white;
        }
        .defend-btn {
            background: #007bff;
            color: white;
        }
        .escape-btn {
            background: #ffc107;
            color: black;
        }
        .skill-btn {
            background: #6f42c1;
            color: white;
        }
        .continue-btn {
            background: #28a745;
            color: white;
        }
        .mp-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            position: relative;
            margin: 10px 0;
        }
        .mp-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #6f42c1, #8b5cf6);
            transition: width 0.3s ease;
        }
        .mp-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
            font-size: 12px;
        }
        .skill-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 2px solid #6f42c1;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 100;
            min-width: 200px;
        }
        .skill-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .skill-item:last-child {
            border-bottom: none;
        }
        .skill-item:hover {
            background: #f8f9fa;
        }
        .skill-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .skill-cost {
            font-size: 12px;
            color: #6f42c1;
            font-weight: bold;
        }
        .skill-button-container {
            position: relative;
            display: inline-block;
        }
        .battle-log {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        .battle-log h3 {
            margin-top: 0;
            color: #495057;
        }
        .log-entry {
            margin: 10px 0;
            padding: 8px;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #dee2e6;
        }
        .log-entry.player-action {
            border-left-color: #007bff;
        }
        .log-entry.monster-action {
            border-left-color: #dc3545;
        }
        .log-entry.battle-end {
            border-left-color: #28a745;
            font-weight: bold;
        }
        .battle-result {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .victory {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            color: #155724;
        }
        .defeat {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }
        .escaped {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            color: #856404;
        }
        .turn-indicator {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
            color: #495057;
        }
        .escape-rate {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="battle-container">
        <h1>戦闘</h1>
        
        <div class="turn-indicator" id="turn-indicator">
            ターン 1
        </div>
        
        <div class="battle-area">
            <div class="character-info">
                <div class="character-name">{{ $character['name'] }}</div>
                <div class="hp-bar">
                    <div class="hp-fill character-hp" id="character-hp" style="width: {{ ($character['hp'] / $character['max_hp']) * 100 }}%"></div>
                    <div class="hp-text" id="character-hp-text">{{ $character['hp'] }}/{{ $character['max_hp'] }}</div>
                </div>
                <div class="mp-bar">
                    <div class="mp-fill" id="character-mp" style="width: {{ ($character['mp'] / $character['max_mp']) * 100 }}%"></div>
                    <div class="mp-text" id="character-mp-text">{{ $character['mp'] }}/{{ $character['max_mp'] }}</div>
                </div>
                <div class="stats">
                    <div class="stat">攻撃力: {{ $character['attack'] }}</div>
                    <div class="stat">魔法攻撃力: {{ $character['magic_attack'] }}</div>
                    <div class="stat">防御力: {{ $character['defense'] }}</div>
                    <div class="stat">素早さ: {{ $character['agility'] }}</div>
                </div>
            </div>
            
            <div class="monster-info">
                <div class="monster-name">{{ $monster['name'] }}</div>
                <div class="monster-emoji">{{ $monster['emoji'] }}</div>
                <div class="hp-bar">
                    <div class="hp-fill monster-hp" id="monster-hp" style="width: {{ ($monster['hp'] / $monster['max_hp']) * 100 }}%"></div>
                    <div class="hp-text" id="monster-hp-text">{{ $monster['hp'] }}/{{ $monster['max_hp'] }}</div>
                </div>
                <div class="stats">
                    <div class="stat">攻撃力: {{ $monster['attack'] }}</div>
                    <div class="stat">防御力: {{ $monster['defense'] }}</div>
                    <div class="stat">素早さ: {{ $monster['agility'] }}</div>
                    <div class="stat">回避率: {{ $monster['evasion'] }}</div>
                </div>
                <div style="margin-top: 10px; font-size: 14px; color: #6c757d;">
                    {{ $monster['description'] }}
                </div>
            </div>
        </div>
        
        <div class="battle-result hidden" id="battle-result">
            <h2 id="result-title"></h2>
            <p id="result-message"></p>
        </div>
        
        <div class="battle-actions" id="battle-actions">
            <button class="action-btn attack-btn" onclick="performAction('attack')" id="attack-button">攻撃</button>
            <button class="action-btn defend-btn" onclick="performAction('defend')">防御</button>
            <div class="skill-button-container">
                <button class="action-btn skill-btn" onclick="toggleSkillMenu()" id="skill-button">特技</button>
                <div class="skill-menu" id="skill-menu">
                    <!-- 特技一覧がここに表示される -->
                </div>
            </div>
            <button class="action-btn escape-btn" onclick="performAction('escape')">
                逃げる
                <div class="escape-rate" id="escape-rate"></div>
            </button>
        </div>
        
        <div class="battle-actions hidden" id="continue-actions">
            <button class="action-btn continue-btn" onclick="returnToGame()">ゲームに戻る</button>
        </div>
        
        <div class="battle-log">
            <h3>戦闘ログ</h3>
            <div id="log-container">
                <div class="log-entry">{{ $monster['name'] }}が現れた！</div>
            </div>
        </div>
    </div>

    <script>
        let battleData = @json($battle);
        let currentTurn = 1;
        let battleEnded = false;
        let availableSkills = [];
        
        function performAction(action, skillId = null) {
            if (battleEnded) return;
            
            const buttons = document.querySelectorAll('.action-btn');
            buttons.forEach(btn => btn.disabled = true);
            
            const url = skillId ? '/battle/skill' : `/battle/${action}`;
            const requestData = skillId ? { skill_id: skillId } : {};
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateBattleDisplay(data);
                    updateBattleLog(data.battle_log);
                    
                    if (data.battle_end) {
                        endBattle(data);
                    } else {
                        currentTurn = data.turn;
                        document.getElementById('turn-indicator').textContent = `ターン ${currentTurn}`;
                        
                        // ボタンを再有効化
                        buttons.forEach(btn => btn.disabled = false);
                        
                        // 逃走率を更新
                        if (data.escape_rate) {
                            document.getElementById('escape-rate').textContent = `成功率: ${data.escape_rate}%`;
                        }
                    }
                } else {
                    alert(data.message);
                    buttons.forEach(btn => btn.disabled = false);
                }
            })
            .catch(error => {
                console.error('Battle error:', error);
                alert('戦闘中にエラーが発生しました');
                buttons.forEach(btn => btn.disabled = false);
            });
        }
        
        function updateBattleDisplay(data) {
            const character = data.character;
            const monster = data.monster;
            
            // キャラクターHP更新
            const characterHpPercentage = (character.hp / character.max_hp) * 100;
            document.getElementById('character-hp').style.width = characterHpPercentage + '%';
            document.getElementById('character-hp-text').textContent = `${character.hp}/${character.max_hp}`;
            
            // キャラクターMP更新
            const characterMpPercentage = (character.mp / character.max_mp) * 100;
            document.getElementById('character-mp').style.width = characterMpPercentage + '%';
            document.getElementById('character-mp-text').textContent = `${character.mp}/${character.max_mp}`;
            
            // モンスターHP更新
            const monsterHpPercentage = (monster.hp / monster.max_hp) * 100;
            document.getElementById('monster-hp').style.width = monsterHpPercentage + '%';
            document.getElementById('monster-hp-text').textContent = `${monster.hp}/${monster.max_hp}`;
            
            // 特技メニューを更新
            updateSkillMenu(character);
        }
        
        function updateBattleLog(battleLog) {
            const logContainer = document.getElementById('log-container');
            logContainer.innerHTML = '';
            
            battleLog.forEach(entry => {
                const logEntry = document.createElement('div');
                logEntry.className = 'log-entry';
                
                if (entry.action.includes('player')) {
                    logEntry.classList.add('player-action');
                } else if (entry.action.includes('monster')) {
                    logEntry.classList.add('monster-action');
                } else if (entry.action === 'battle_end') {
                    logEntry.classList.add('battle-end');
                }
                
                logEntry.textContent = entry.message;
                logContainer.appendChild(logEntry);
            });
            
            // 最新のログにスクロール
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        
        function endBattle(data) {
            battleEnded = true;
            
            // 戦闘アクションを非表示
            document.getElementById('battle-actions').classList.add('hidden');
            
            // 結果表示
            const resultDiv = document.getElementById('battle-result');
            const resultTitle = document.getElementById('result-title');
            const resultMessage = document.getElementById('result-message');
            const expDiv = document.getElementById('experience-gained');
            const expAmount = document.getElementById('exp-amount');
            
            resultDiv.classList.remove('hidden');
            
            if (data.result === 'victory') {
                resultDiv.className = 'battle-result victory';
                resultTitle.textContent = '勝利！';
                resultMessage.textContent = `${data.monster.name}を倒しました！`;
                if (data.experience_gained > 0) {
                    expDiv.classList.remove('hidden');
                    expAmount.textContent = data.experience_gained;
                }
            } else if (data.result === 'defeat') {
                resultDiv.className = 'battle-result defeat';
                resultTitle.textContent = '敗北...';
                resultMessage.textContent = '戦闘に敗れました。';
            } else if (data.result === 'escaped') {
                resultDiv.className = 'battle-result escaped';
                resultTitle.textContent = '逃走';
                resultMessage.textContent = '戦闘から逃げ出しました。';
            }
            
            // 続行ボタンを表示
            document.getElementById('continue-actions').classList.remove('hidden');
        }
        
        function returnToGame() {
            // 戦闘終了API呼び出し
            fetch('/battle/end', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(() => {
                // ゲーム画面に戻る
                window.location.href = '/game';
            })
            .catch(error => {
                console.error('End battle error:', error);
                window.location.href = '/game';
            });
        }
        
        function toggleSkillMenu() {
            const skillMenu = document.getElementById('skill-menu');
            if (skillMenu.style.display === 'none' || skillMenu.style.display === '') {
                skillMenu.style.display = 'block';
            } else {
                skillMenu.style.display = 'none';
            }
        }

        function updateSkillMenu(character) {
            // 装備から使用可能な特技を取得（仮実装）
            updateAvailableSkills(character);
            const skillMenu = document.getElementById('skill-menu');
            skillMenu.innerHTML = '';

            if (availableSkills.length === 0) {
                const noSkillItem = document.createElement('div');
                noSkillItem.className = 'skill-item disabled';
                noSkillItem.textContent = '使用可能な特技がありません';
                skillMenu.appendChild(noSkillItem);
                return;
            }

            availableSkills.forEach(skill => {
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
                    skillItem.onclick = function() {
                        document.getElementById('skill-menu').style.display = 'none';
                        performAction('skill', skill.skill_id);
                    };
                }

                skillMenu.appendChild(skillItem);
            });
        }

        function updateAvailableSkills(character) {
            // 実際の実装では装備から特技を取得
            // 仮実装として基本的な特技を設定
            availableSkills = [
                {
                    skill_id: 'fire_magic',
                    name: 'ファイヤー',
                    mp_cost: 5
                },
                {
                    skill_id: 'heal',
                    name: 'ヒール',
                    mp_cost: 4
                }
            ];
        }

        function updateAttackButtonText(isMagicalWeapon) {
            const attackButton = document.getElementById('attack-button');
            attackButton.textContent = isMagicalWeapon ? '魔法攻撃' : '攻撃';
        }

        // スキルメニューの外側をクリックで閉じる
        document.addEventListener('click', function(event) {
            const skillMenu = document.getElementById('skill-menu');
            const skillButton = document.getElementById('skill-button');
            
            if (!skillButton.contains(event.target) && !skillMenu.contains(event.target)) {
                skillMenu.style.display = 'none';
            }
        });

        // 初期化時に逃走率を設定
        document.addEventListener('DOMContentLoaded', function() {
            const character = battleData.character;
            const monster = battleData.monster;
            
            // 逃走率を計算して表示
            const baseEscapeRate = 50;
            const speedDifference = character.agility - monster.agility;
            const escapeRate = Math.max(10, Math.min(90, baseEscapeRate + (speedDifference * 3)));
            
            document.getElementById('escape-rate').textContent = `成功率: ${escapeRate}%`;
            
            // 特技メニューを初期化
            updateSkillMenu(character);
        });
    </script>
</body>
</html>