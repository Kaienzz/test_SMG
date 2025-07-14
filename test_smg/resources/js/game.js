// ゲーム進行状態
const towns = ['町A', '町B'];
const roads = ['道路1', '道路2', '道路3'];
const path = [towns[0], ...roads, towns[1]];

let currentSection = 0; // 0:町A, 1:道路1, 2:道路2, 3:道路3, 4:町B
let progress = 0; // 0-100
let dice = [1, 1];
let direction = 1; // 1:右, -1:左

// ===== 仮データ: キャラクター・装備・アイテム =====
const equipmentSlots = [
    { key: 'head', label: '頭' },
    { key: 'hand', label: '手' },
    { key: 'shield', label: '盾' },
    { key: 'body', label: '胴体' },
    { key: 'shoes', label: '靴' },
    { key: 'accessory', label: '装飾品' },
    { key: 'bag', label: '鞄' },
];

let character = {
    name: '勇者タロウ',
    hp: 120,
    mp: 30,
    attack: 15,
    defense: 12,
    speed: 10,
    evasion: 7,
    accuracy: 13,
    equipments: {
        head: { name: '鉄の兜', attack: 1, defense: 3, speed: 0, evasion: 0, hp: 5, mp: 0, accuracy: 0, effect: '' },
        hand: { name: '木の剣', attack: 5, defense: 0, speed: 1, evasion: 0, hp: 0, mp: 0, accuracy: 1, effect: '' },
        shield: null,
        body: null,
        shoes: null,
        accessory: null,
        bag: null,
    },
    inventory: [
        { type: 'equipment', slot: 'shield', name: '皮の盾', attack: 0, defense: 2, speed: 0, evasion: 1, hp: 2, mp: 0, accuracy: 0, effect: '' },
        { type: 'equipment', slot: 'body', name: '旅人の服', attack: 0, defense: 1, speed: 0, evasion: 0, hp: 3, mp: 0, accuracy: 0, effect: '' },
        { type: 'equipment', slot: 'shoes', name: '軽い靴', attack: 0, defense: 0, speed: 2, evasion: 2, hp: 0, mp: 0, accuracy: 0, effect: 'move_dice_plus1' },
        { type: 'item', name: '回復薬', effect: 'hp+20', quantity: 2 },
        { type: 'item', name: '魔法の水', effect: 'mp+10', quantity: 1 },
    ],
};

const root = document.getElementById('game-root');

function renderCharacterUI() {
    // キャラクター情報
    const charDiv = document.createElement('div');
    charDiv.className = 'mb-4 p-4 bg-gray-50 rounded shadow';
    charDiv.innerHTML = `<div class="font-bold text-lg mb-2">${character.name} のステータス</div>
        <div class="flex flex-wrap gap-4 mb-2">
            <span>HP: ${character.hp}</span>
            <span>MP: ${character.mp}</span>
            <span>攻撃: ${character.attack}</span>
            <span>防御: ${character.defense}</span>
            <span>素早さ: ${character.speed}</span>
            <span>回避: ${character.evasion}</span>
            <span>命中: ${character.accuracy}</span>
        </div>`;

    // 装備欄
    const equipDiv = document.createElement('div');
    equipDiv.className = 'mb-2';
    equipDiv.innerHTML = '<div class="font-semibold mb-1">装備</div>';
    const equipTable = document.createElement('table');
    equipTable.className = 'w-full text-sm';
    equipmentSlots.forEach(slot => {
        const tr = document.createElement('tr');
        const td1 = document.createElement('td');
        td1.textContent = slot.label;
        const td2 = document.createElement('td');
        const eq = character.equipments[slot.key];
        td2.textContent = eq ? eq.name : 'なし';
        const td3 = document.createElement('td');
        if (eq) {
            td3.innerHTML = `<span class='text-xs text-gray-500'>攻${eq.attack} 防${eq.defense} 速${eq.speed} 回${eq.evasion} HP${eq.hp} MP${eq.mp} 命${eq.accuracy}</span>`;
        } else {
            td3.innerHTML = '';
        }
        const td4 = document.createElement('td');
        // インベントリーに同じslotの装備があれば装備ボタン
        const invEq = character.inventory.filter(i => i.type === 'equipment' && i.slot === slot.key);
        if (invEq.length > 0) {
            const btn = document.createElement('button');
            btn.textContent = '装備変更';
            btn.className = 'px-2 py-1 bg-blue-400 text-white rounded text-xs';
            btn.onclick = () => showEquipSelect(slot.key);
            td4.appendChild(btn);
        }
        tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(td3); tr.appendChild(td4);
        equipTable.appendChild(tr);
    });
    equipDiv.appendChild(equipTable);
    charDiv.appendChild(equipDiv);

    // インベントリー
    const invDiv = document.createElement('div');
    invDiv.className = 'mt-2';
    invDiv.innerHTML = '<div class="font-semibold mb-1">インベントリー</div>';
    const invTable = document.createElement('table');
    invTable.className = 'w-full text-xs';
    character.inventory.forEach(item => {
        const tr = document.createElement('tr');
        const td1 = document.createElement('td');
        td1.textContent = item.type === 'equipment' ? `[装備]${item.name}` : `[アイテム]${item.name}`;
        const td2 = document.createElement('td');
        if (item.type === 'equipment') {
            td2.textContent = `部位:${equipmentSlots.find(s=>s.key===item.slot)?.label}`;
        } else {
            td2.textContent = `x${item.quantity}`;
        }
        const td3 = document.createElement('td');
        td3.textContent = item.effect ? `効果:${item.effect}` : '';
        tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(td3);
        invTable.appendChild(tr);
    });
    invDiv.appendChild(invTable);
    charDiv.appendChild(invDiv);

    return charDiv;
}

function showEquipSelect(slotKey) {
    // インベントリーから該当部位の装備を選択
    const options = character.inventory.filter(i => i.type === 'equipment' && i.slot === slotKey);
    if (options.length === 0) return;
    const select = window.prompt(`装備を選択してください: ${options.map((o,i)=>`[${i+1}]${o.name}`).join(' ')}`);
    const idx = parseInt(select) - 1;
    if (!isNaN(idx) && options[idx]) {
        // 現在の装備をインベントリーに戻す
        if (character.equipments[slotKey]) {
            character.inventory.push({ ...character.equipments[slotKey], type: 'equipment', slot: slotKey });
        }
        // 新しい装備を装備欄に
        character.equipments[slotKey] = options[idx];
        // インベントリーから外す
        character.inventory = character.inventory.filter((item, i) => !(item.type === 'equipment' && item.slot === slotKey && item.name === options[idx].name && i === character.inventory.indexOf(options[idx])));
        render();
    }
}

function render() {
    root.innerHTML = '';
    // キャラクター・装備UI
    root.appendChild(renderCharacterUI());
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