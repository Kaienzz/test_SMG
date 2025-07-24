<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Monster;

class BattleService
{
    /**
     * モンスターとのエンカウント判定
     */
    public static function checkEncounter(string $roadId): ?array
    {
        $encounterRate = 0.1; // 10%の確率
        $random = mt_rand() / mt_getrandmax();
        
        if ($random <= $encounterRate) {
            return Monster::getRandomMonsterForRoad($roadId);
        }
        
        return null;
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
            return [
                'result' => 'victory',
                'message' => "{$monster['name']}を倒した！"
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
        // 直近に入った町を取得（デフォルトはtown_a）
        $lastTown = session('last_visited_town', 'town_a');
        
        // 所持金ペナルティの計算（20%-30%をランダムで失う）
        $currentGold = $character['gold'] ?? 0;
        $penaltyPercent = mt_rand(20, 30);
        $goldLost = (int) round($currentGold * ($penaltyPercent / 100));
        $remainingGold = $currentGold - $goldLost;
        
        // キャラクターデータを更新
        $updatedCharacter = $character;
        $updatedCharacter['gold'] = $remainingGold;
        $updatedCharacter['hp'] = 1; // HPを1に回復
        
        // セッションを更新（町にテレポート + 所持金更新）
        session([
            'location_type' => 'town',
            'location_id' => $lastTown,
            'game_position' => 0,
            'character_gold' => $remainingGold,
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
        
        // モンスターのHPが30%以下の場合、たまに防御を選択
        if (($monster['hp'] / $monster['max_hp']) < 0.3) {
            $actions[] = 'defend';
        }
        
        return $actions[array_rand($actions)];
    }
}