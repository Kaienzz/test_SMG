<?php

namespace App\Application\Services;

use App\Models\Character;
use App\Models\ActiveBattle;
use App\Services\BattleService;
use App\Application\DTOs\BattleData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 戦闘状態管理サービス
 * 
 * BattleController からビジネスロジックを抽出し、戦闘状態の変更を統一管理
 * Phase 3: Controller純化でのサービス層統合
 */
class BattleStateManager
{
    /**
     * アクティブな戦闘データを取得
     *
     * @param int $userId
     * @return array|null
     */
    public function getActiveBattleData(int $userId): ?array
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return null;
        }
        
        return [
            'battle' => [
                'battle_id' => $activeBattle->battle_id,
                'character' => $activeBattle->character_data,
                'monster' => $activeBattle->monster_data,
                'battle_log' => $activeBattle->battle_log,
                'turn' => $activeBattle->turn,
            ],
            'character' => $activeBattle->character_data,
            'monster' => $activeBattle->monster_data,
        ];
    }

    /**
     * 戦闘を開始する
     *
     * @param Character $character
     * @param array $monster
     * @return array
     */
    public function startBattle(Character $character, array $monster): array
    {
        $user = Auth::user();
        
        // CharacterをBattleService用の配列形式に変換
        $characterArray = $this->createPlayerFromCharacter($character);
        
        $battleData = BattleService::startBattle($characterArray, $monster);
        
        // ActiveBattleに保存
        $activeBattle = ActiveBattle::startBattle(
            $user->id,
            $battleData['character'],
            $battleData['monster'],
            $character->location_type
        );
        
        return [
            'success' => true,
            'battle_id' => $activeBattle->battle_id,
            'character' => $battleData['character'],
            'monster' => $battleData['monster'],
            'message' => "{$monster['name']}が現れた！"
        ];
    }

    /**
     * 攻撃処理
     *
     * @param int $userId
     * @return array
     */
    public function processAttack(int $userId): array
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return ['success' => false, 'message' => '戦闘データが見つかりません'];
        }
        
        $character = $activeBattle->character_data;
        $monster = $activeBattle->monster_data;
        $battleLog = $activeBattle->battle_log;
        
        // プレイヤーの攻撃
        $attackResult = BattleService::calculateAttack($character, $monster);
        $monster = BattleService::applyDamage($monster, $attackResult['damage']);
        
        $battleLog[] = [
            'action' => 'player_attack',
            'message' => $attackResult['hit'] ? 
                "{$character['name']}の攻撃！ {$monster['name']}に{$attackResult['damage']}のダメージ！" . 
                ($attackResult['critical'] ? ' ' . $attackResult['message'] : '') :
                $attackResult['message']
        ];
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // モンスターの行動
        $monsterAction = BattleService::getMonsterAction($monster, $character);
        
        if ($monsterAction === 'attack') {
            $result = $this->processMonsterAttack($character, $monster, $battleLog);
            $character = $result['character'];
            $monster = $result['monster'];
            $battleLog = $result['battleLog'];
        }
        
        // 戦闘終了判定（モンスターの攻撃後）
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // 戦闘データをDB更新
        $activeBattle->updateBattleData($character, $monster, $battleLog);
        
        return [
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1,
        ];
    }

    /**
     * 防御処理
     *
     * @param int $userId
     * @return array
     */
    public function processDefense(int $userId): array
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return ['success' => false, 'message' => '戦闘データが見つかりません'];
        }
        
        $character = $activeBattle->character_data;
        $monster = $activeBattle->monster_data;
        $battleLog = $activeBattle->battle_log;
        
        // プレイヤーの防御
        $defenseResult = BattleService::calculateDefense($character);
        $battleLog[] = [
            'action' => 'player_defend',
            'message' => $defenseResult['message']
        ];
        
        // モンスターの攻撃（ダメージ軽減）
        $monsterAttack = BattleService::calculateAttack($monster, $character);
        $reducedDamage = (int) round($monsterAttack['damage'] * (1 - $defenseResult['defense_bonus']));
        $character = BattleService::applyDamage($character, $reducedDamage);
        
        $battleLog[] = [
            'action' => 'monster_attack',
            'message' => $monsterAttack['hit'] ? 
                "{$monster['name']}の攻撃！ {$character['name']}に{$reducedDamage}のダメージ！" . 
                ($reducedDamage < $monsterAttack['damage'] ? ' (防御によりダメージ軽減)' : '') :
                $monsterAttack['message']
        ];
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // 戦闘データをDB更新
        $activeBattle->updateBattleData($character, $monster, $battleLog);
        
        return [
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1,
        ];
    }

    /**
     * 逃走処理
     *
     * @param int $userId
     * @return array
     */
    public function processEscape(int $userId): array
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return ['success' => false, 'message' => '戦闘データが見つかりません'];
        }
        
        $character = $activeBattle->character_data;
        $monster = $activeBattle->monster_data;
        $battleLog = $activeBattle->battle_log;
        
        // 逃走判定
        $escapeResult = BattleService::calculateEscape($character, $monster);
        
        if ($escapeResult['success']) {
            $battleLog[] = [
                'action' => 'escape_success',
                'message' => $escapeResult['message']
            ];
            
            $activeBattle->endBattle('escape');
            $this->updateCharacterFromBattle($userId, $character);
            
            return [
                'success' => true,
                'battle_end' => true,
                'result' => 'escape',
                'character' => $character,
                'monster' => $monster,
                'battle_log' => $battleLog,
                'message' => $escapeResult['message']
            ];
        }
        
        $battleLog[] = [
            'action' => 'escape_failed',
            'message' => $escapeResult['message']
        ];
        
        // 逃走失敗時はモンスターの攻撃
        $result = $this->processMonsterAttack($character, $monster, $battleLog);
        $character = $result['character'];
        $monster = $result['monster'];
        $battleLog = $result['battleLog'];
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // 戦闘データをDB更新
        $activeBattle->updateBattleData($character, $monster, $battleLog);
        
        return [
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1,
        ];
    }

    /**
     * スキル使用処理
     *
     * @param int $userId
     * @param Request $request
     * @return array
     */
    public function processSkillUse(int $userId, Request $request): array
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return ['success' => false, 'message' => '戦闘データが見つかりません'];
        }
        
        $skillId = $request->input('skill_id');
        $character = $activeBattle->character_data;
        $monster = $activeBattle->monster_data;
        $battleLog = $activeBattle->battle_log;
        
        // スキル使用処理
        $skillResult = BattleService::useSkill($character, $monster, $skillId);
        
        if (!$skillResult['success']) {
            return [
                'success' => false,
                'message' => $skillResult['message']
            ];
        }
        
        $character = $skillResult['character'];
        $monster = $skillResult['monster'];
        
        $battleLog[] = [
            'action' => 'skill_use',
            'message' => $skillResult['message']
        ];
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // モンスターの行動
        $monsterAction = BattleService::getMonsterAction($monster, $character);
        
        if ($monsterAction === 'attack') {
            $result = $this->processMonsterAttack($character, $monster, $battleLog);
            $character = $result['character'];
            $monster = $result['monster'];
            $battleLog = $result['battleLog'];
        }
        
        // 戦闘終了判定（モンスターの攻撃後）
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // 戦闘データをDB更新
        $activeBattle->updateBattleData($character, $monster, $battleLog);
        
        return [
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1,
        ];
    }

    /**
     * 戦闘終了処理
     *
     * @param int $userId
     * @return array
     */
    public function endBattle(int $userId): array
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return ['success' => false, 'message' => '戦闘データが見つかりません'];
        }
        
        $activeBattle->endBattle('forced_end');
        
        return [
            'success' => true,
            'message' => '戦闘を終了しました'
        ];
    }

    /**
     * セッションデータをデータベースに移行
     *
     * @param int $userId
     * @return void
     */
    public function migrateBattleSessionToDatabase(int $userId): void
    {
        $sessionKey = "battle_data_{$userId}";
        
        if (session()->has($sessionKey)) {
            $sessionBattleData = session($sessionKey);
            
            // セッションデータをActiveBattleに移行する処理
            // （実装は必要に応じて）
            
            session()->forget($sessionKey);
        }
    }

    /**
     * Character を BattleService用の配列形式に変換
     *
     * @param Character $character
     * @return array
     */
    private function createPlayerFromCharacter(Character $character): array
    {
        return [
            'name' => $character->name,
            'hp' => $character->hp,
            'max_hp' => $character->max_hp,
            'sp' => $character->sp,
            'max_sp' => $character->max_sp,
            'attack' => $character->attack ?? 10,
            'defense' => $character->defense ?? 5,
            'speed' => $character->speed ?? 8,
            'skills' => $character->getSkillList() ?? [],
        ];
    }

    /**
     * 戦闘結果をキャラクターに反映
     *
     * @param int $userId
     * @param array $characterData
     * @return void
     */
    private function updateCharacterFromBattle(int $userId, array $characterData): void
    {
        $user = Auth::user();
        if ($user && $user->id === $userId) {
            $character = $user->getOrCreateCharacter();
            
            $character->update([
                'hp' => $characterData['hp'],
                'sp' => $characterData['sp'],
                'experience' => $characterData['experience'] ?? $character->experience,
                'gold' => $characterData['gold'] ?? $character->gold,
            ]);
        }
    }

    /**
     * モンスターの攻撃処理
     *
     * @param array $character
     * @param array $monster
     * @param array $battleLog
     * @return array
     */
    private function processMonsterAttack(array $character, array $monster, array $battleLog): array
    {
        $monsterAttack = BattleService::calculateAttack($monster, $character);
        $character = BattleService::applyDamage($character, $monsterAttack['damage']);
        
        $battleLog[] = [
            'action' => 'monster_attack',
            'message' => $monsterAttack['hit'] ? 
                "{$monster['name']}の攻撃！ {$character['name']}に{$monsterAttack['damage']}のダメージ！" . 
                ($monsterAttack['critical'] ? ' ' . $monsterAttack['message'] : '') :
                $monsterAttack['message']
        ];
        
        return [
            'character' => $character,
            'monster' => $monster,
            'battleLog' => $battleLog
        ];
    }

    /**
     * 戦闘終了シーケンス
     *
     * @param ActiveBattle $activeBattle
     * @param array $character
     * @param array $monster
     * @param array $battleLog
     * @param int $userId
     * @return array
     */
    private function endBattleSequence(ActiveBattle $activeBattle, array $character, array $monster, array $battleLog, int $userId): array
    {
        $result = $monster['hp'] <= 0 ? 'victory' : ($character['hp'] <= 0 ? 'defeat' : 'draw');
        $battleResult = BattleService::processBattleResult($character, $monster, $result);
        
        $battleLog[] = [
            'action' => 'battle_end',
            'message' => $battleResult['message']
        ];
        
        $activeBattle->endBattle($result);
        $this->updateCharacterFromBattle($userId, $character);
        
        return [
            'success' => true,
            'battle_end' => true,
            'result' => $result,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'experience_gained' => $battleResult['experience_gained'] ?? 0,
        ];
    }
}