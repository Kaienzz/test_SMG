import { character } from './character.js';

export function getDiceSpecFromEquipments() {
    let diceCount = 2; // デフォルト2個
    let diceFace = 6;  // デフォルト6面
    // 装備効果を走査
    Object.values(character.equipments).forEach(eq => {
        if (!eq) return;
        if (eq.effect === 'dice_face_plus3') diceFace += 3;
        if (eq.effect === 'dice_count_plus1') diceCount += 1;
    });
    return { diceCount, diceFace };
}

export function rollDice(face = 6) {
    return Math.floor(Math.random() * face) + 1;
}