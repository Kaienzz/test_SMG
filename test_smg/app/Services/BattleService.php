<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Monster;
use App\Models\Route;
use Illuminate\Support\Facades\Auth;

class BattleService
{
    /**
     * モンスターとのエンカウント判定（SQLite対応）
     */
    public static function checkEncounter(string $roadId): ?array
    {
        try {
            // SQLiteからロケーションデータを取得
            $location = Route::find($roadId);
            
            // パスウェイのエンカウント率を取得（デフォルト: 10%）
            $encounterRate = 0.1; // デフォルト値
            
            if ($location && $location->encounter_rate !== null) {
                $encounterRate = (float) $location->encounter_rate;
            } else {
                \Log::warning('Encounter rate not found in location or location not found, using default', [
                    'location_id' => $roadId,
                    'default_rate' => $encounterRate
                ]);
            }
            
            $random = mt_rand() / mt_getrandmax();
            
            \Log::debug('Encounter check with SQLite data', [
                'location_id' => $roadId,
                'encounter_rate' => $encounterRate,
                'encounter_percentage' => $encounterRate * 100,
                'random' => $random,
                'will_encounter' => $random <= $encounterRate
            ]);
            
            if ($random <= $encounterRate) {
                $monster = Monster::getRandomMonsterForRoad($roadId);
                
                if ($monster) {
                    \Log::info('Monster encounter occurred', [
                        'location_id' => $roadId,
                        'monster_name' => $monster['name'],
                        'monster_level' => $monster['level'],
                        'encounter_rate_used' => $encounterRate
                    ]);
                }
                
                return $monster;
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Failed to check encounter', [
                'location_id' => $roadId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 戦闘開始
     */
    public static function startBattle(array $character, array $monster): array
    {
        return [
            'battle_id' => uniqid('battle_'),
            'character' => $character,
            'monster' => $monster,
            'turn' => 1,
            'phase' => 'player_turn', // player_turn, monster_turn, battle_end
            'battle_log' => [],
            'result' => null, // victory, defeat, escaped
        ];
    }

    /**
     * 行動順序の決定
     */
    public static function determineActionOrder(array $character, array $monster): array
    {
        // 素早さで行動順序を決定
        $characterSpeed = $character['agility'] ?? 10;
        $monsterSpeed = $monster['agility'] ?? 10;
        
        // 同じ素早さの場合はランダム
        if ($characterSpeed == $monsterSpeed) {
            return mt_rand(0, 1) ? ['character', 'monster'] : ['monster', 'character'];
        }
        
        return $characterSpeed > $monsterSpeed ? ['character', 'monster'] : ['monster', 'character'];
    }

    /**
     * 攻撃処理
     */
    public static function calculateAttack(array $attacker, array $defender, array $equipment = null): array
    {
        // 装備情報から武器タイプを判定
        $weaponType = null;
        $isMagicalAttack = false;
        
        if ($equipment && isset($equipment['weapon']) && $equipment['weapon']) {
            $weaponType = $equipment['weapon']['weapon_type'] ?? 'physical';
            $isMagicalAttack = $weaponType === 'magical';
        }
        
        // 攻撃力の選択（物理武器は攻撃力、魔法武器は魔法攻撃力）
        if ($isMagicalAttack) {
            $baseAttack = $attacker['magic_attack'] ?? 8;
        } else {
            $baseAttack = $attacker['attack'] ?? 10;
        }
        
        $defense = $defender['defense'] ?? 5;
        $attackerAccuracy = $attacker['accuracy'] ?? 80;
        $defenderEvasion = $defender['evasion'] ?? 10;
        
        // 命中判定
        $hitChance = max(10, $attackerAccuracy - $defenderEvasion);
        $hitRoll = mt_rand(1, 100);
        
        if ($hitRoll > $hitChance) {
            $attackName = $isMagicalAttack ? '魔法攻撃' : '攻撃';
            return [
                'hit' => false,
                'damage' => 0,
                'critical' => false,
                'message' => $attackName . 'は外れた！',
                'attack_type' => $isMagicalAttack ? 'magical' : 'physical'
            ];
        }
        
        // ダメージ計算
        $baseDamage = max(1, $baseAttack - $defense);
        $randomMultiplier = mt_rand(80, 120) / 100; // 80%-120%のランダム要素
        $damage = (int) round($baseDamage * $randomMultiplier);
        
        // クリティカル判定 (5%の確率)
        $critical = mt_rand(1, 100) <= 5;
        if ($critical) {
            $damage = (int) round($damage * 1.5);
        }
        
        return [
            'hit' => true,
            'damage' => $damage,
            'critical' => $critical,
            'message' => $critical ? 'クリティカルヒット！' : '',
            'attack_type' => $isMagicalAttack ? 'magical' : 'physical'
        ];
    }

    /**
     * 逃走判定
     */
    public static function calculateEscape(array $character, array $monster): array
    {
        $characterSpeed = $character['agility'] ?? 10;
        $monsterSpeed = $monster['agility'] ?? 10;
        
        // 基本逃走率は50%
        $baseEscapeRate = 50;
        
        // 素早さの差による補正
        $speedDifference = $characterSpeed - $monsterSpeed;
        $escapeRate = $baseEscapeRate + ($speedDifference * 3);
        
        // 最低10%、最高90%に制限
        $escapeRate = max(10, min(90, $escapeRate));
        
        $escapeRoll = mt_rand(1, 100);
        $success = $escapeRoll <= $escapeRate;
        
        return [
            'success' => $success,
            'escape_rate' => $escapeRate,
            'message' => $success ? '逃走に成功した！' : '逃走に失敗した...'
        ];
    }

    /**
     * 防御処理
     */
    public static function calculateDefense(array $character): array
    {
        return [
            'defense_bonus' => 0.5, // 50%ダメージ軽減
            'message' => '防御の構えを取った！'
        ];
    }

    /**
     * 戦闘結果の処理
     */
    public static function processBattleResult(array $character, array $monster, string $result): array
    {
        if ($result === 'victory') {
            // 経験値計算: 基本経験値 + レベル差ボーナス
            $baseExp = $monster['experience_reward'] ?? 0;
            $playerLevel = $character['level'] ?? 1;
            $monsterLevel = $monster['level'] ?? 1;
            $levelDiff = max(0, $monsterLevel - $playerLevel);
            $levelBonus = $levelDiff * 5; // レベル差1につき+5exp
            $experienceGained = $baseExp + $levelBonus;
            
            // ゴールド報酬計算: モンスターレベル × 10-20G (ランダム)
            $baseGold = $monsterLevel * mt_rand(10, 20);
            $goldBonus = $levelDiff * 3; // レベル差1につき+3G
            $goldGained = $baseGold + $goldBonus;
            
            return [
                'result' => 'victory',
                'message' => "{$monster['name']}を倒した！",
                'experience_gained' => $experienceGained,
                'gold_gained' => $goldGained
            ];
        } elseif ($result === 'defeat') {
            // 戦闘敗北時の処理
            $defeatResult = self::processDefeat($character);
            
            return [
                'result' => 'defeat',
                'message' => '敗北した...',
                'teleport_location' => $defeatResult['teleport_location'],
                'gold_lost' => $defeatResult['gold_lost'],
                'remaining_gold' => $defeatResult['remaining_gold'],
                'teleport_message' => $defeatResult['teleport_message']
            ];
        } elseif ($result === 'escaped') {
            return [
                'result' => 'escaped',
                'message' => '戦闘から逃走した'
            ];
        }
        
        return [
            'result' => 'unknown',
            'message' => '不明な結果'
        ];
    }

    /**
     * 戦闘敗北時の処理
     */
    private static function processDefeat(array $character): array
    {
        // 直近に入った町を取得（Player モデルから取得、デフォルトはtown_a）
        $lastTown = 'town_a'; // デフォルト値
        
        // Player モデルから last_visited_town を取得
        if (Auth::check()) {
            $player = \App\Models\Player::where('user_id', Auth::id())->first();
            if ($player && $player->last_visited_town) {
                $lastTown = $player->last_visited_town;
            }
        }
        
        // セッションからも確認（フォールバック）
        $sessionTown = session('last_visited_town');
        if ($sessionTown) {
            $lastTown = $sessionTown;
        }
        
        // 所持金ペナルティの計算（20%-30%をランダムで失う）
        $currentGold = $character['gold'] ?? 0;
        $penaltyPercent = mt_rand(20, 30);
        $goldLost = (int) round($currentGold * ($penaltyPercent / 100));
        $remainingGold = $currentGold - $goldLost;
        
        // キャラクターデータを更新
        $updatedCharacter = $character;
        $updatedCharacter['gold'] = $remainingGold;
        $updatedCharacter['hp'] = 1; // HPを1に回復
        
        // 戦闘敗北時のセッション状態をクリア（DBが正となるため）
        // 古いセッションデータでPlayerデータが上書きされないようにする
        session()->forget([
            'location_type',
            'location_id', 
            'game_position',
            'character_gold',
            'player_gold',
            'player_sp'
        ]);
        
        // 町の名前を取得
        $townNames = [
            'town_a' => 'A町',
            'town_b' => 'B町'
        ];
        $townName = $townNames[$lastTown] ?? '不明な町';
        
        return [
            'teleport_location' => $lastTown,
            'gold_lost' => $goldLost,
            'remaining_gold' => $remainingGold,
            'teleport_message' => "{$townName}にテレポートしました",
            'penalty_percent' => $penaltyPercent,
            'updated_character' => $updatedCharacter
        ];
    }

    /**
     * ダメージ適用
     */
    public static function applyDamage(array $target, int $damage, string $damageType = 'physical', array $equipment = null): array
    {
        $finalDamage = $damage;
        
        // 装備による軽減効果を適用
        if ($equipment) {
            $reductionPercent = 0;
            
            // 装備の効果から軽減率を計算
            if ($damageType === 'physical' && isset($equipment['effects']['physical_damage_reduction'])) {
                $reductionPercent = $equipment['effects']['physical_damage_reduction'];
            } elseif ($damageType === 'magical' && isset($equipment['effects']['magical_damage_reduction'])) {
                $reductionPercent = $equipment['effects']['magical_damage_reduction'];
            }
            
            if ($reductionPercent > 0) {
                $finalDamage = (int) round($damage * (1 - $reductionPercent / 100));
            }
        }
        
        $target['hp'] = max(0, $target['hp'] - $finalDamage);
        return $target;
    }

    /**
     * 戦闘終了判定
     */
    public static function isBattleEnd(array $character, array $monster): bool
    {
        return $character['hp'] <= 0 || $monster['hp'] <= 0;
    }

    /**
     * AI行動選択（モンスター用）
     */
    public static function getMonsterAction(array $monster, array $character): string
    {
        $actions = ['attack'];
        
        // 安全なHP比率計算
        $currentHp = $monster['hp'] ?? 0;
        $maxHp = $monster['max_hp'] ?? 1;
        
        // ゼロ除算防止とHP比率計算
        $hpRatio = $maxHp > 0 ? ($currentHp / $maxHp) : 0;
        
        // モンスターのHPが30%以下の場合、たまに防御を選択
        if ($hpRatio < 0.3 && $currentHp > 0) {
            $actions[] = 'defend';
            
            // さらにHPが低い場合は逃走も考慮（10%以下）
            if ($hpRatio < 0.1) {
                $actions[] = 'defend'; // 防御の確率を上げる
            }
        }
        
        return $actions[array_rand($actions)];
    }

    /**
     * スキル使用処理
     * 
     * @param array $character
     * @param array $monster
     * @param string $skillId
     * @return array
     */
    public static function useSkill(array $character, array $monster, string $skillId): array
    {
        try {
            // スキル情報の取得
            $skills = $character['skills'] ?? [];
            $skill = null;
            
            foreach ($skills as $s) {
                if (($s['id'] ?? '') == $skillId || ($s['skill_name'] ?? '') == $skillId) {
                    $skill = $s;
                    break;
                }
            }
            
            if (!$skill) {
                return [
                    'success' => false,
                    'message' => 'スキルが見つかりません',
                    'character' => $character,
                    'monster' => $monster
                ];
            }
            
            // SP消費チェック
            $spCost = $skill['sp_cost'] ?? 10;
            if (($character['sp'] ?? 0) < $spCost) {
                return [
                    'success' => false,
                    'message' => 'SPが足りません',
                    'character' => $character,
                    'monster' => $monster
                ];
            }
            
            // SP消費
            $character['sp'] = max(0, ($character['sp'] ?? 0) - $spCost);
            
            // スキル効果の適用
            $skillType = $skill['skill_type'] ?? 'combat';
            $skillLevel = $skill['level'] ?? 1;
            
            switch ($skillType) {
                case 'combat':
                    return self::applyCombatSkill($character, $monster, $skill);
                    
                case 'magic':
                    return self::applyMagicSkill($character, $monster, $skill);
                    
                case 'defense':
                    return self::applyDefenseSkill($character, $monster, $skill);
                    
                default:
                    return self::applyGenericSkill($character, $monster, $skill);
            }
            
        } catch (\Exception $e) {
            \Log::error('Skill use failed', [
                'skill_id' => $skillId,
                'character' => $character['name'] ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'スキル使用に失敗しました',
                'character' => $character,
                'monster' => $monster
            ];
        }
    }
    
    /**
     * 戦闘スキル効果の適用
     */
    private static function applyCombatSkill(array $character, array $monster, array $skill): array
    {
        $skillLevel = $skill['level'] ?? 1;
        $skillName = $skill['skill_name'] ?? 'スキル';
        
        // 攻撃力ボーナス
        $baseAttack = $character['attack'] ?? 10;
        $bonusAttack = $baseAttack + ($skillLevel * 5);
        
        // 強化された攻撃を実行
        $enhancedCharacter = $character;
        $enhancedCharacter['attack'] = $bonusAttack;
        
        $attackResult = self::calculateAttack($enhancedCharacter, $monster);
        $monster = self::applyDamage($monster, $attackResult['damage']);
        
        $message = "{$character['name']}は{$skillName}を使った！ ";
        $message .= $attackResult['hit'] ? 
            "{$monster['name']}に{$attackResult['damage']}のダメージ！" . 
            ($attackResult['critical'] ? ' ' . $attackResult['message'] : '') :
            $attackResult['message'];
        
        return [
            'success' => true,
            'message' => $message,
            'character' => $character,
            'monster' => $monster
        ];
    }
    
    /**
     * 魔法スキル効果の適用
     */
    private static function applyMagicSkill(array $character, array $monster, array $skill): array
    {
        $skillLevel = $skill['level'] ?? 1;
        $skillName = $skill['skill_name'] ?? '魔法';
        
        // 魔法攻撃力ボーナス
        $baseMagicAttack = $character['magic_attack'] ?? 8;
        $bonusMagicAttack = $baseMagicAttack + ($skillLevel * 3);
        
        // 魔法攻撃を実行
        $enhancedCharacter = $character;
        $enhancedCharacter['magic_attack'] = $bonusMagicAttack;
        
        $attackResult = self::calculateAttack($enhancedCharacter, $monster, null);
        $monster = self::applyDamage($monster, $attackResult['damage'], 'magical');
        
        $message = "{$character['name']}は{$skillName}を唱えた！ ";
        $message .= $attackResult['hit'] ? 
            "{$monster['name']}に{$attackResult['damage']}の魔法ダメージ！" :
            $attackResult['message'];
        
        return [
            'success' => true,
            'message' => $message,
            'character' => $character,
            'monster' => $monster
        ];
    }
    
    /**
     * 防御スキル効果の適用
     */
    private static function applyDefenseSkill(array $character, array $monster, array $skill): array
    {
        $skillLevel = $skill['level'] ?? 1;
        $skillName = $skill['skill_name'] ?? '守りのスキル';
        
        // 防御力一時的増加
        $character['defense'] = ($character['defense'] ?? 5) + ($skillLevel * 3);
        
        return [
            'success' => true,
            'message' => "{$character['name']}は{$skillName}を使い、防御力が上がった！",
            'character' => $character,
            'monster' => $monster
        ];
    }
    
    /**
     * 汎用スキル効果の適用
     */
    private static function applyGenericSkill(array $character, array $monster, array $skill): array
    {
        $skillName = $skill['skill_name'] ?? 'スキル';
        
        // HP回復効果
        if (isset($skill['effects']['heal'])) {
            $healAmount = $skill['effects']['heal'];
            $character['hp'] = min($character['max_hp'] ?? 100, ($character['hp'] ?? 0) + $healAmount);
            
            return [
                'success' => true,
                'message' => "{$character['name']}は{$skillName}を使い、{$healAmount}HP回復した！",
                'character' => $character,
                'monster' => $monster
            ];
        }
        
        return [
            'success' => true,
            'message' => "{$character['name']}は{$skillName}を使った！",
            'character' => $character,
            'monster' => $monster
        ];
    }
}