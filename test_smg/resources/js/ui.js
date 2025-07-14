import { equipmentSlots, character, showEquipSelect } from './character.js';
import { getDiceSpecFromEquipments } from './dice.js';
import { towns, roads, path, currentSection, progress, dice } from './gameState.js';

export function renderCharacterUI(onEquipChanged) {
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
        const invEq = character.inventory.filter(i => i.type === 'equipment' && i.slot === slot.key);
        if (invEq.length > 0) {
            const btn = document.createElement('button');
            btn.textContent = '装備変更';
            btn.className = 'px-2 py-1 bg-blue-400 text-white rounded text-xs';
            btn.onclick = () => showEquipSelect(slot.key, onEquipChanged);
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