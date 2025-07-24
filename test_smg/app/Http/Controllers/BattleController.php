<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Monster;
use App\Models\BattleSkill;
use App\Models\ActiveBattle;
use App\Services\BattleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class BattleController extends Controller
{
    use \App\Http\Controllers\Traits\HasCharacter;
    public function index(): View|RedirectResponse
    {
        $userId = Auth::id();
        
        // セッションから戦闘データを移行（下位互換性のため）
        $this->migrateBattleSessionToDatabase($userId);
        
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return redirect()->route('game.index');
        }
        
        return view('battle.index', [
            'battle' => [
                'battle_id' => $activeBattle->battle_id,
                'character' => $activeBattle->character_data,
                'monster' => $activeBattle->monster_data,
                'battle_log' => $activeBattle->battle_log,
                'turn' => $activeBattle->turn,
            ],
            'character' => $activeBattle->character_data,
            'monster' => $activeBattle->monster_data,
        ]);
    }

    public function startBattle(Request $request): JsonResponse
    {
        $monster = $request->input('monster');
        $user = Auth::user();
        $character = $this->getOrCreateCharacter();
        
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
        
        return response()->json([
            'success' => true,
            'battle_id' => $activeBattle->battle_id,
            'character' => $battleData['character'],
            'monster' => $battleData['monster'],
            'message' => "{$monster['name']}が現れた！"
        ]);
    }

    public function attack(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return response()->json(['success' => false, 'message' => '戦闘データが見つかりません']);
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
            $result = $monster['hp'] <= 0 ? 'victory' : 'defeat';
            $battleResult = BattleService::processBattleResult($character, $monster, $result);
            
            $battleLog[] = [
                'action' => 'battle_end',
                'message' => $battleResult['message']
            ];
            
            $activeBattle->endBattle($result);
            $this->updateCharacterFromBattle($userId, $character);
            
            return response()->json([
                'success' => true,
                'battle_end' => true,
                'result' => $result,
                'character' => $character,
                'monster' => $monster,
                'battle_log' => $battleLog,
                'experience_gained' => $battleResult['experience_gained'] ?? 0,
            ]);
        }
        
        // モンスターの行動
        $monsterAction = BattleService::getMonsterAction($monster, $character);
        
        if ($monsterAction === 'attack') {
            $monsterAttack = BattleService::calculateAttack($monster, $character);
            $character = BattleService::applyDamage($character, $monsterAttack['damage']);
            
            $battleLog[] = [
                'action' => 'monster_attack',
                'message' => $monsterAttack['hit'] ? 
                    "{$monster['name']}の攻撃！ {$character['name']}に{$monsterAttack['damage']}のダメージ！" . 
                    ($monsterAttack['critical'] ? ' ' . $monsterAttack['message'] : '') :
                    $monsterAttack['message']
            ];
        }
        
        // 戦闘終了判定（モンスターの攻撃後）
        if (BattleService::isBattleEnd($character, $monster)) {
            $result = $character['hp'] <= 0 ? 'defeat' : 'victory';
            $battleResult = BattleService::processBattleResult($character, $monster, $result);
            
            $battleLog[] = [
                'action' => 'battle_end',
                'message' => $battleResult['message']
            ];
            
            $activeBattle->endBattle($result);
            $this->updateCharacterFromBattle($userId, $character);
            
            return response()->json([
                'success' => true,
                'battle_end' => true,
                'result' => $result,
                'character' => $character,
                'monster' => $monster,
                'battle_log' => $battleLog,
                'experience_gained' => $battleResult['experience_gained'] ?? 0,
            ]);
        }
        
        // 戦闘データをDB更新
        $activeBattle->updateBattleData($character, $monster, $battleLog);
        
        return response()->json([
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1,
        ]);
    }

    public function defend(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return response()->json(['success' => false, 'message' => '戦闘データが見つかりません']);
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
                "{$monster['name']}の攻撃！ {$character['name']}に{$reducedDamage}のダメージ！（防御により軽減）" :
                $monsterAttack['message']
        ];
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            $result = $character['hp'] <= 0 ? 'defeat' : 'victory';
            $battleResult = BattleService::processBattleResult($character, $monster, $result);
            
            $battleLog[] = [
                'action' => 'battle_end',
                'message' => $battleResult['message']
            ];
            
            $activeBattle->endBattle($result);
            $this->updateCharacterFromBattle($userId, $character);
            
            return response()->json([
                'success' => true,
                'battle_end' => true,
                'result' => $result,
                'character' => $character,
                'monster' => $monster,
                'battle_log' => $battleLog,
                'experience_gained' => $battleResult['experience_gained'] ?? 0,
            ]);
        }
        
        // 戦闘データをDB更新
        $activeBattle->updateBattleData($character, $monster, $battleLog);
        
        return response()->json([
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1,
        ]);
    }

    public function escape(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return response()->json(['success' => false, 'message' => '戦闘データが見つかりません']);
        }
        
        $character = $activeBattle->character_data;
        $monster = $activeBattle->monster_data;
        $battleLog = $activeBattle->battle_log;
        
        // 逃走判定
        $escapeResult = BattleService::calculateEscape($character, $monster);
        $battleLog[] = [
            'action' => 'player_escape',
            'message' => $escapeResult['message']
        ];
        
        if ($escapeResult['success']) {
            // 逃走成功
            $battleResult = BattleService::processBattleResult($character, $monster, 'escaped');
            $activeBattle->endBattle('escaped');
            $this->updateCharacterFromBattle($userId, $character);
            
            return response()->json([
                'success' => true,
                'battle_end' => true,
                'result' => 'escaped',
                'character' => $character,
                'monster' => $monster,
                'battle_log' => $battleLog,
                'message' => $battleResult['message'],
            ]);
        }
        
        // 逃走失敗、モンスターの攻撃
        $monsterAttack = BattleService::calculateAttack($monster, $character);
        $character = BattleService::applyDamage($character, $monsterAttack['damage']);
        
        $battleLog[] = [
            'action' => 'monster_attack',
            'message' => $monsterAttack['hit'] ? 
                "{$monster['name']}の攻撃！ {$character['name']}に{$monsterAttack['damage']}のダメージ！" :
                $monsterAttack['message']
        ];
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            $result = $character['hp'] <= 0 ? 'defeat' : 'victory';
            $battleResult = BattleService::processBattleResult($character, $monster, $result);
            
            $battleLog[] = [
                'action' => 'battle_end',
                'message' => $battleResult['message']
            ];
            
            $activeBattle->endBattle($result);
            $this->updateCharacterFromBattle($userId, $character);
            
            return response()->json([
                'success' => true,
                'battle_end' => true,
                'result' => $result,
                'character' => $character,
                'monster' => $monster,
                'battle_log' => $battleLog,
                'experience_gained' => $battleResult['experience_gained'] ?? 0,
            ]);
        }
        
        // 戦闘データを更新
        $activeBattle->updateBattleData([
            'character_data' => $character,
            'monster_data' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1,
        ]);
        
        return response()->json([
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn,
            'escape_rate' => $escapeResult['escape_rate'],
        ]);
    }

    public function endBattle(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if ($activeBattle) {
            $activeBattle->endBattle('abandoned');
        }
        
        return response()->json([
            'success' => true,
            'message' => '戦闘を終了しました'
        ]);
    }

    public function useSkill(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return response()->json(['success' => false, 'message' => '戦闘データが見つかりません']);
        }

        $skillId = $request->input('skill_id');
        $skill = BattleSkill::getSkillById($skillId);
        
        if (!$skill) {
            return response()->json(['success' => false, 'message' => 'スキルが見つかりません']);
        }

        $character = $activeBattle->character_data;
        $monster = $activeBattle->monster_data;
        $battleLog = $activeBattle->battle_log;

        // MP確認
        if ($character['mp'] < $skill->mp_cost) {
            return response()->json(['success' => false, 'message' => 'MPが足りません']);
        }

        // MP消費
        $character['mp'] -= $skill->mp_cost;

        // スキル効果の計算
        if ($skill->skill_type === BattleSkill::TYPE_SUPPORT) {
            // 回復系
            if (isset($skill->effects['heal_hp']) && $skill->effects['heal_hp']) {
                $healAmount = $skill->base_power;
                $character['hp'] = min($character['max_hp'], $character['hp'] + $healAmount);
                $battleLog[] = [
                    'action' => 'player_skill',
                    'message' => "{$character['name']}は{$skill->name}を使用した！HPを{$healAmount}回復した！"
                ];
            }
        } else {
            // 攻撃系
            $skillResult = $skill->calculateDamage($character, $monster);
            
            if ($skillResult['hit']) {
                $monster = BattleService::applyDamage($monster, $skillResult['damage']);
                $battleLog[] = [
                    'action' => 'player_skill',
                    'message' => "{$character['name']}の{$skill->name}！ {$monster['name']}に{$skillResult['damage']}のダメージ！" . 
                        ($skillResult['critical'] ? ' ' . $skillResult['message'] : '')
                ];
            } else {
                $battleLog[] = [
                    'action' => 'player_skill',
                    'message' => $skillResult['message']
                ];
            }
        }

        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            $result = $monster['hp'] <= 0 ? 'victory' : 'defeat';
            $battleResult = BattleService::processBattleResult($character, $monster, $result);
            
            $battleLog[] = [
                'action' => 'battle_end',
                'message' => $battleResult['message']
            ];
            
            $activeBattle->endBattle($result);
            $this->updateCharacterFromBattle($userId, $character);
            
            return response()->json([
                'success' => true,
                'battle_end' => true,
                'result' => $result,
                'character' => $character,
                'monster' => $monster,
                'battle_log' => $battleLog,
                'experience_gained' => $battleResult['experience_gained'] ?? 0,
            ]);
        }

        // モンスターの行動
        $monsterAction = BattleService::getMonsterAction($monster, $character);
        
        if ($monsterAction === 'attack') {
            $monsterAttack = BattleService::calculateAttack($monster, $character);
            $character = BattleService::applyDamage($character, $monsterAttack['damage']);
            
            $battleLog[] = [
                'action' => 'monster_attack',
                'message' => $monsterAttack['hit'] ? 
                    "{$monster['name']}の攻撃！ {$character['name']}に{$monsterAttack['damage']}のダメージ！" . 
                    ($monsterAttack['critical'] ? ' ' . $monsterAttack['message'] : '') :
                    $monsterAttack['message']
            ];
        }

        // 戦闘終了判定（モンスターの攻撃後）
        if (BattleService::isBattleEnd($character, $monster)) {
            $result = $character['hp'] <= 0 ? 'defeat' : 'victory';
            $battleResult = BattleService::processBattleResult($character, $monster, $result);
            
            $battleLog[] = [
                'action' => 'battle_end',
                'message' => $battleResult['message']
            ];
            
            $activeBattle->endBattle($result);
            $this->updateCharacterFromBattle($userId, $character);
            
            return response()->json([
                'success' => true,
                'battle_end' => true,
                'result' => $result,
                'character' => $character,
                'monster' => $monster,
                'battle_log' => $battleLog,
                'experience_gained' => $battleResult['experience_gained'] ?? 0,
            ]);
        }

        // 戦闘データを更新
        $activeBattle->updateBattleData([
            'character_data' => $character,
            'monster_data' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1,
        ]);
        
        return response()->json([
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn,
        ]);
    }

    // セッションから戦闘データをDBに移行するヘルパー（下位互換性のため）
    private function migrateBattleSessionToDatabase(int $userId): void
    {
        $sessionBattleData = session('battle_data');
        
        if ($sessionBattleData) {
            ActiveBattle::migrateFromSession($userId, $sessionBattleData);
            session()->forget('battle_data');
        }
    }

    // CharacterモデルからBattleService用の配列形式に変換（装備込み最適化）
    private function createPlayerFromCharacter(Character $character): array
    {
        return $character->getBattleStats();
    }

    // 戦闘結果をCharacterモデルに反映
    private function updateCharacterFromBattle(int $userId, array $characterData): void
    {
        $user = Auth::user();
        $character = $this->getOrCreateCharacter();
        
        // HP, MP, SPの更新
        $character->update([
            'hp' => max(0, $characterData['hp'] ?? $character->hp),
            'mp' => max(0, $characterData['mp'] ?? $character->mp),
            'sp' => max(0, $characterData['sp'] ?? $character->sp),
            'gold' => max(0, $characterData['gold'] ?? $character->gold),
        ]);
    }
}