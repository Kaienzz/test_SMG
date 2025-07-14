// ゲーム進行状態
const towns = ['町A', '町B'];
const roads = ['道路1', '道路2', '道路3'];
const path = [towns[0], ...roads, towns[1]];

let currentSection = 0; // 0:町A, 1:道路1, 2:道路2, 3:道路3, 4:町B
let progress = 0; // 0-100
let dice = [1, 1];
let direction = 1; // 1:右, -1:左

const root = document.getElementById('game-root');

function render() {
    root.innerHTML = '';
    // 現在地表示
    const sectionName = path[currentSection];
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
        fill.style.width = `${progress}%`;
        bar.appendChild(fill);
        root.appendChild(bar);

        const progressText = document.createElement('div');
        progressText.className = 'mb-2';
        progressText.textContent = `進捗: ${progress}/100`;
        root.appendChild(progressText);

        // サイコロ表示
        const diceDiv = document.createElement('div');
        diceDiv.className = 'mb-2';
        diceDiv.textContent = `サイコロ: [${dice[0]}] [${dice[1]}] 合計: ${dice[0]+dice[1]}`;
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
        if (progress <= 0 || progress >= 100) {
            const nextBtn = document.createElement('button');
            nextBtn.textContent = currentSection === path.length-2 && progress >= 100 ? '町Bに入る' : '次へ';
            nextBtn.className = 'block mt-4 px-4 py-2 bg-green-500 text-white rounded';
            nextBtn.onclick = () => goNext();
            root.appendChild(nextBtn);
        }
    } else {
        // 町なら進むボタン
        if (currentSection === 0) {
            const btn = document.createElement('button');
            btn.textContent = '道路1へ進む';
            btn.className = 'px-4 py-2 bg-blue-500 text-white rounded';
            btn.onclick = () => goNext();
            root.appendChild(btn);
        } else if (currentSection === path.length-1) {
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
    direction = dir;
    // サイコロを振る
    dice = [rollDice(), rollDice()];
    const moveAmount = (dice[0] + dice[1]) * dir;
    progress += moveAmount;
    if (progress < 0) progress = 0;
    if (progress > 100) progress = 100;
    render();
}

function goNext() {
    if (path[currentSection].startsWith('道路')) {
        if (progress <= 0) {
            currentSection--;
        } else if (progress >= 100) {
            currentSection++;
        }
    } else {
        currentSection++;
    }
    if (path[currentSection].startsWith('道路')) {
        progress = 0;
    }
    render();
}

function resetGame() {
    currentSection = 0;
    progress = 0;
    dice = [1, 1];
    direction = 1;
    render();
}

function rollDice() {
    return Math.floor(Math.random() * 6) + 1;
}

document.addEventListener('DOMContentLoaded', render);