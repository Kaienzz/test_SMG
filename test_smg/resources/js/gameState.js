export const towns = ['町A', '町B'];
export const roads = ['道路1', '道路2', '道路3'];
export const path = [towns[0], ...roads, towns[1]];

export const gameState = {
    currentSection: 0, // 0:町A, 1:道路1, 2:道路2, 3:道路3, 4:町B
    progress: 0, // 0-100
    dice: [1, 1],
    direction: 1, // 1:右, -1:左
};

export function resetGameState() {
    gameState.currentSection = 0;
    gameState.progress = 0;
    gameState.dice = [1, 1];
    gameState.direction = 1;
}