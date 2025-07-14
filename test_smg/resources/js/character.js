// キャラクター・装備・インベントリー管理
export const equipmentSlots = [
    { key: 'head', label: '頭' },
    { key: 'hand', label: '手' },
    { key: 'shield', label: '盾' },
    { key: 'body', label: '胴体' },
    { key: 'shoes', label: '靴' },
    { key: 'accessory', label: '装飾品' },
    { key: 'bag', label: '鞄' },
];

export let character = {
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
        accessory: { name: 'サイコロの指輪', attack: 0, defense: 0, speed: 0, evasion: 0, hp: 0, mp: 0, accuracy: 0, effect: 'dice_face_plus3' },
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

export function showEquipSelect(slotKey, onEquipChanged) {
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
        if (onEquipChanged) onEquipChanged();
    }
}