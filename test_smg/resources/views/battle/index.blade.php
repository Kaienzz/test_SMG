<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>戦闘 - ブラウザゲーム</title>
    <link rel="stylesheet" href="{{ asset('css/game-design-system.css') }}">
</head>
<body>
    <div class="game-container">
        <div class="game-card battle-card">
            <h1 class="game-card-title">戦闘</h1>
            
            <div class="game-status" id="turn-indicator">
                ターン 1
            </div>
        </div>
        
        <div class="battle-area">
            <div class="character-info">
                <div class="character-name">{{ $character['name'] ?? 'プレイヤー' }}</div>
                <div class="progress">
                    <div class="progress-fill hp" id="character-hp" style="width: {{ (($character['hp'] ?? 100) / ($character['max_hp'] ?? 100)) * 100 }}%"></div>
                    <div class="progress-text" id="character-hp-text">{{ $character['hp'] ?? 100 }}/{{ $character['max_hp'] ?? 100 }}</div>
                </div>
                <div class="progress">
                    <div class="progress-fill mp" id="character-mp" style="width: {{ (($character['mp'] ?? 50) / ($character['max_mp'] ?? 50)) * 100 }}%"></div>
                    <div class="progress-text" id="character-mp-text">{{ $character['mp'] ?? 50 }}/{{ $character['max_mp'] ?? 50 }}</div>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">攻撃力</div>
                        <div class="stat-value">{{ $character['attack'] ?? 15 }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">魔法攻撃力</div>
                        <div class="stat-value">{{ $character['magic_attack'] ?? 12 }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">防御力</div>
                        <div class="stat-value">{{ $character['defense'] ?? 12 }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">素早さ</div>
                        <div class="stat-value">{{ $character['agility'] ?? 18 }}</div>
                    </div>
                </div>
            </div>
            
            <div class="monster-info">
                <div class="monster-name">{{ $monster['name'] ?? 'Unknown Monster' }}</div>
                <div class="monster-emoji">{{ $monster['emoji'] ?? '👹' }}</div>
                <div class="progress">
                    <div class="progress-fill" id="monster-hp" style="width: {{ (($monster['stats']['hp'] ?? 100) / ($monster['stats']['max_hp'] ?? 100)) * 100 }}%; background: linear-gradient(90deg, var(--error), #ef4444);"></div>
                    <div class="progress-text" id="monster-hp-text">{{ $monster['stats']['hp'] ?? 100 }}/{{ $monster['stats']['max_hp'] ?? 100 }}</div>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">攻撃力</div>
                        <div class="stat-value">{{ $monster['stats']['attack'] ?? 15 }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">防御力</div>
                        <div class="stat-value">{{ $monster['stats']['defense'] ?? 10 }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">素早さ</div>
                        <div class="stat-value">{{ $monster['stats']['agility'] ?? 10 }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">回避率</div>
                        <div class="stat-value">{{ $monster['stats']['evasion'] ?? 10 }}</div>
                    </div>
                </div>
                <div style="margin-top: var(--space-3); font-size: var(--text-sm); color: var(--text-secondary);">
                    {{ $monster['description'] ?? 'モンスターの説明はありません' }}
                </div>
            </div>
        </div>
        
        <div class="game-card hidden" id="battle-result">
            <h2 class="game-card-title" id="result-title"></h2>
            <div class="game-card-content">
                <p id="result-message"></p>
                <div class="hidden" id="experience-gained">
                    <p>経験値を <span id="exp-amount">0</span> 獲得しました！</p>
                </div>
                <div class="hidden" id="defeat-penalty">
                    <p id="teleport-message"></p>
                    <p id="gold-penalty-message"></p>
                </div>
            </div>
        </div>
        
        <div class="button-group" id="battle-actions">
            <button class="btn btn-error" onclick="performAction('attack')" id="attack-button">攻撃</button>
            <button class="btn btn-primary" onclick="performAction('defend')">防御</button>
            <div class="skill-button-container">
                <button class="btn" onclick="toggleSkillMenu()" id="skill-button" 
                        style="background: var(--secondary-500); color: white;">特技</button>
                <div class="skill-menu" id="skill-menu">
                    <!-- 特技一覧がここに表示される -->
                </div>
            </div>
            <button class="btn btn-warning" onclick="performAction('escape')">
                逃げる
                <div style="font-size: var(--text-xs); color: var(--text-secondary); margin-top: var(--space-1);" id="escape-rate"></div>
            </button>
        </div>
        
        <div class="button-group hidden" id="continue-actions">
            <button class="btn btn-success btn-large" onclick="returnToGame()" type="button" id="return-to-game-btn">ゲームに戻る</button>
        </div>
        
        <div class="game-card">
            <h3 class="game-card-title">戦闘ログ</h3>
            <div class="game-card-content" id="log-container" style="max-height: 300px; overflow-y: auto;">
                <div class="log-entry">{{ $monster['name'] ?? 'Unknown Monster' }}が現れた！</div>
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
            const monsterHpPercentage = (monster.stats.hp / monster.stats.max_hp) * 100;
            document.getElementById('monster-hp').style.width = monsterHpPercentage + '%';
            document.getElementById('monster-hp-text').textContent = `${monster.stats.hp}/${monster.stats.max_hp}`;
            
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
                
                // 敗北ペナルティ情報を表示
                if (data.teleport_message || data.gold_lost !== undefined) {
                    const defeatPenaltyDiv = document.getElementById('defeat-penalty');
                    const teleportMsg = document.getElementById('teleport-message');
                    const goldPenaltyMsg = document.getElementById('gold-penalty-message');
                    
                    if (data.teleport_message) {
                        teleportMsg.textContent = data.teleport_message;
                    }
                    
                    if (data.gold_lost !== undefined) {
                        goldPenaltyMsg.textContent = `所持金を ${data.gold_lost}G 失いました（残り: ${data.remaining_gold}G）`;
                    }
                    
                    defeatPenaltyDiv.classList.remove('hidden');
                }
            } else if (data.result === 'escaped') {
                resultDiv.className = 'battle-result escaped';
                resultTitle.textContent = '逃走';
                resultMessage.textContent = '戦闘から逃げ出しました。';
            }
            
            // 続行ボタンを表示（すべてのケースで）
            const continueActions = document.getElementById('continue-actions');
            const returnBtn = document.getElementById('return-to-game-btn');
            
            continueActions.classList.remove('hidden');
            
            // ボタンを確実に有効化
            if (returnBtn) {
                returnBtn.disabled = false;
                returnBtn.style.pointerEvents = 'auto';
                returnBtn.style.cursor = 'pointer';
            }
            
            console.log('Battle ended, continue button should be visible and clickable');
        }
        
        function returnToGame() {
            console.log('returnToGame function called');
            
            // ボタンを無効化して二重クリックを防ぐ
            const continueBtn = document.getElementById('return-to-game-btn');
            if (continueBtn) {
                continueBtn.disabled = true;
                continueBtn.textContent = '戻り中...';
                continueBtn.style.cursor = 'not-allowed';
            }
            
            // 戦闘が自然に終了した場合（勝利/敗北/逃走）は、
            // 戦闘終了処理は既に完了しているため、直接ゲーム画面に戻る
            if (battleEnded) {
                console.log('Battle already ended naturally, redirecting directly to /game');
                window.location.href = '/game';
                return;
            }
            
            // 戦闘が継続中で強制終了する場合のみAPI呼び出し
            fetch('/battle/end', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('Received response:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Battle end response:', data);
                if (data.success) {
                    // ゲーム画面に戻る
                    console.log('Redirecting to /game');
                    window.location.href = '/game';
                } else {
                    throw new Error(data.message || '戦闘終了処理に失敗しました');
                }
            })
            .catch(error => {
                console.error('End battle error:', error);
                // エラーが発生してもゲーム画面に戻る
                console.log('Error occurred, redirecting to /game anyway');
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

        // プレイヤースキルメニューの外側をクリックで閉じる
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
            const speedDifference = character.agility - monster.stats.agility;
            const escapeRate = Math.max(10, Math.min(90, baseEscapeRate + (speedDifference * 3)));
            
            document.getElementById('escape-rate').textContent = `成功率: ${escapeRate}%`;
            
            // 特技メニューを初期化
            updateSkillMenu(character);
        });
    </script>
</body>
</html>