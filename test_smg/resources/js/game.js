import { renderCharacterUI } from './ui.js';
import { getDiceSpecFromEquipments, rollDice } from './dice.js';
import { character } from './character.js';
import { towns, roads, path, gameState, resetGameState } from './gameState.js';

const root = document.getElementById('game-root');

function render() {
    root.innerHTML = '';
    // キャラクター・装備UI
    root.appendChild(renderCharacterUI(render));
    // 現在地表示
    const sectionName = path[gameState.currentSection];
    const sectionDiv = document.createElement('div');
    sectionDiv.className = 'mb-4 text-lg font-bold';
    sectionDiv.textContent = `現在地: ${sectionName}`;
    root.appendChild(sectionDiv);

    // 道路なら進捗バーと操作
    if (sectionName.startsWith('道路')) {
        const bar = document.createElement('div');
        bar.className = 'w-full bg-gray-200 rounded h-6 mb-2';
        const fill = document.createElement('div');
        fill.className = 'bg-blue-500 h-6 rounded';
        fill.style.width = `${gameState.progress}%`;
        bar.appendChild(fill);
        root.appendChild(bar);

        const progressText = document.createElement('div');
        progressText.className = 'mb-2';
        progressText.textContent = `進捗: ${gameState.progress}/100`;
        root.appendChild(progressText);

        // サイコロ表示
        const diceSpec = getDiceSpecFromEquipments();
        const diceDiv = document.createElement('div');
        diceDiv.className = 'mb-2';
        diceDiv.textContent = `サイコロ: [${gameState.dice.join('] [')}] 合計: ${gameState.dice.reduce((a,b)=>a+b,0)}（${diceSpec.diceCount}個${diceSpec.diceFace}面）`;
        root.appendChild(diceDiv);

        // 左右ボタン
        const btnLeft = document.createElement('button');
        btnLeft.textContent = '左へ';
        btnLeft.className = 'px-4 py-2 bg-gray-400 text-white rounded mr-2';
        btnLeft.onclick = () => move(-1);
        root.appendChild(btnLeft);
        const btnRight = document.createElement('button');
        btnRight.textContent = '右へ';
        btnRight.className = 'px-4 py-2 bg-blue-500 text-white rounded';
        btnRight.onclick = () => move(1);
        root.appendChild(btnRight);

        // 0か100に到達したら次へ
        if (gameState.progress <= 0 || gameState.progress >= 100) {
            const nextBtn = document.createElement('button');
            nextBtn.textContent = gameState.currentSection === path.length-2 && gameState.progress >= 100 ? '町Bに入る' : '次へ';
            nextBtn.className = 'block mt-4 px-4 py-2 bg-green-500 text-white rounded';
            nextBtn.onclick = () => goNext();
            root.appendChild(nextBtn);
        }
    } else {
        // 町なら進むボタン
        if (gameState.currentSection === 0) {
            const btn = document.createElement('button');
            btn.textContent = '道路1へ進む';
            btn.className = 'px-4 py-2 bg-blue-500 text-white rounded';
            btn.onclick = () => goNext();
            root.appendChild(btn);
        } else if (gameState.currentSection === path.length-1) {
            const clearDiv = document.createElement('div');
            clearDiv.className = 'mt-4 text-xl text-green-700 font-bold';
            clearDiv.textContent = 'ゴール！町Bに到着しました！';
            root.appendChild(clearDiv);
            const retryBtn = document.createElement('button');
            retryBtn.textContent = '最初からやり直す';
            retryBtn.className = 'mt-4 px-4 py-2 bg-gray-500 text-white rounded';
            retryBtn.onclick = () => resetGame();
            root.appendChild(retryBtn);
        }
    }
}

function move(dir) {
    if (dir !== 1 && dir !== -1) return;
    // サイコロ仕様を取得
    const diceSpec = getDiceSpecFromEquipments();
    // サイコロを振る
    for (let i = 0; i < diceSpec.diceCount; i++) {
        gameState.dice[i] = rollDice(diceSpec.diceFace);
    }
    const moveAmount = gameState.dice.reduce((a,b)=>a+b,0) * dir;
    gameState.progress += moveAmount;
    if (gameState.progress < 0) gameState.progress = 0;
    if (gameState.progress > 100) gameState.progress = 100;
    render();
}

function goNext() {
    if (path[gameState.currentSection].startsWith('道路')) {
        if (gameState.progress <= 0) {
            gameState.currentSection--;
        } else if (gameState.progress >= 100) {
            gameState.currentSection++;
        }
    } else {
        gameState.currentSection++;
    }
    if (path[gameState.currentSection].startsWith('道路')) {
        gameState.progress = 0;
    }
    render();
}

function resetGame() {
    resetGameState();
    render();
}

document.addEventListener('DOMContentLoaded', render);