<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Monster;
use App\Services\BattleService;
use App\Services\DummyDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BattleController extends Controller
{
    public function index(): View
    {
        $battleData = session('battle_data');
        
        if (!$battleData) {
            return redirect()->route('game.index');
        }
        
        return view('battle.index', [
            'battle' => $battleData,
            'character' => $battleData['character'],
            'monster' => $battleData['monster'],
        ]);
    }

    public function startBattle(Request $request): JsonResponse
    {
        $monster = $request->input('monster');
        $character = DummyDataService::getCharacter(1);
        
        $battleData = BattleService::startBattle($character, $monster);
        session(['battle_data' => $battleData]);
        
        return response()->json([
            'success' => true,
            'battle_id' => $battleData['battle_id'],
            'character' => $battleData['character'],
            'monster' => $battleData['monster'],
            'message' => "{$monster['name']}が現れた！"
        ]);
    }

    public function attack(Request $request): JsonResponse
    {
        $battleData = session('battle_data');
        
        if (!$battleData) {
            return response()->json(['success' => false, 'message' => '戦闘データが見つかりません']);
        }
        
        $character = $battleData['character'];
        $monster = $battleData['monster'];
        $battleLog = $battleData['battle_log'];
        
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
            
            session()->forget('battle_data');
            
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
            
            session()->forget('battle_data');
            
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
        $battleData['character'] = $character;
        $battleData['monster'] = $monster;
        $battleData['battle_log'] = $battleLog;
        $battleData['turn']++;
        
        session(['battle_data' => $battleData]);
        
        return response()->json([
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $battleData['turn'],
        ]);
    }

    public function defend(Request $request): JsonResponse
    {
        $battleData = session('battle_data');
        
        if (!$battleData) {
            return response()->json(['success' => false, 'message' => '戦闘データが見つかりません']);
        }
        
        $character = $battleData['character'];
        $monster = $battleData['monster'];
        $battleLog = $battleData['battle_log'];
        
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
            
            session()->forget('battle_data');
            
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
        $battleData['character'] = $character;
        $battleData['monster'] = $monster;
        $battleData['battle_log'] = $battleLog;
        $battleData['turn']++;
        
        session(['battle_data' => $battleData]);
        
        return response()->json([
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $battleData['turn'],
        ]);
    }

    public function escape(Request $request): JsonResponse
    {
        $battleData = session('battle_data');
        
        if (!$battleData) {
            return response()->json(['success' => false, 'message' => '戦闘データが見つかりません']);
        }
        
        $character = $battleData['character'];
        $monster = $battleData['monster'];
        $battleLog = $battleData['battle_log'];
        
        // 逃走判定
        $escapeResult = BattleService::calculateEscape($character, $monster);
        $battleLog[] = [
            'action' => 'player_escape',
            'message' => $escapeResult['message']
        ];
        
        if ($escapeResult['success']) {
            // 逃走成功
            $battleResult = BattleService::processBattleResult($character, $monster, 'escaped');
            session()->forget('battle_data');
            
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
            
            session()->forget('battle_data');
            
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
        $battleData['character'] = $character;
        $battleData['monster'] = $monster;
        $battleData['battle_log'] = $battleLog;
        $battleData['turn']++;
        
        session(['battle_data' => $battleData]);
        
        return response()->json([
            'success' => true,
            'battle_end' => false,
            'character' => $character,
            'monster' => $monster,
            'battle_log' => $battleLog,
            'turn' => $battleData['turn'],
            'escape_rate' => $escapeResult['escape_rate'],
        ]);
    }

    public function endBattle(Request $request): JsonResponse
    {
        session()->forget('battle_data');
        
        return response()->json([
            'success' => true,
            'message' => '戦闘を終了しました'
        ]);
    }
}