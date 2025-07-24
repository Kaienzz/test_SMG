<?php

namespace App\Domain\Character;

use App\Models\Character;

/**
 * キャラクター統計計算サービス
 * 
 * Character クラスから統計計算関連のロジックを分離
 * レベル計算、ステータス計算、装備効果、スキルボーナスなどを統一管理
 */
class CharacterStatsService
{
    /**
     * キャラクターレベルを計算
     *
     * @param Character $character
     * @return int
     */
    public function calculateCharacterLevel(Character $character): int
    {
        // スキルレベルの合計を取得
        $totalSkillLevel = $character->skills()->sum('level');
        
        // スキルレベルが0の場合は1を返す
        if ($totalSkillLevel == 0) {
            return 1;
        }
        
        return max(1, floor($totalSkillLevel / 10) + 1);
    }

    /**
     * キャラクターレベルを更新
     *
     * @param Character $character
     * @return bool レベルが変更されたかどうか
     */
    public function updateCharacterLevel(Character $character): bool
    {
        $newLevel = $this->calculateCharacterLevel($character);
        $oldLevel = $character->level ?? 1;
        
        if ($newLevel !== $oldLevel) {
            $character->level = $newLevel;
            $this->updateStatsForLevel($character);
            return true;
        }
        
        return false;
    }

    /**
     * レベルに応じてステータスを更新
     *
     * @param Character $character
     */
    public function updateStatsForLevel(Character $character): void
    {
        $baseStats = $this->getBaseStats($character);
        $skillBonuses = $this->calculateSkillBonuses($character);
        
        // 基本ステータス + スキルボーナス
        $character->attack = $baseStats['attack'] + $skillBonuses['attack'];
        $character->defense = $baseStats['defense'] + $skillBonuses['defense'];
        $character->agility = $baseStats['agility'] + $skillBonuses['agility'];
        $character->evasion = $baseStats['evasion'] + $skillBonuses['evasion'];
        $character->magic_attack = $baseStats['magic_attack'] + $skillBonuses['magic_attack'];
        $character->accuracy = $baseStats['accuracy'] + $skillBonuses['accuracy'];
        
        // HP/SP/MP は現在値を保持しつつ最大値のみ更新
        $oldMaxHp = $character->max_hp;
        $oldMaxSp = $character->max_sp;
        $oldMaxMp = $character->max_mp;
        
        $character->max_hp = $baseStats['max_hp'] + $skillBonuses['max_hp'];
        $character->max_sp = $baseStats['max_sp'] + $skillBonuses['max_sp'];
        $character->max_mp = $baseStats['max_mp'] + $skillBonuses['max_mp'];
        
        // 最大値が増えた場合は現在値も比例して回復
        if ($character->max_hp > $oldMaxHp && $oldMaxHp > 0) {
            $character->hp = min($character->max_hp, $character->hp + ($character->max_hp - $oldMaxHp));
        }
        if ($character->max_sp > $oldMaxSp && $oldMaxSp > 0) {
            $character->sp = min($character->max_sp, $character->sp + ($character->max_sp - $oldMaxSp));
        }
        if ($character->max_mp > $oldMaxMp && $oldMaxMp > 0) {
            $character->mp = min($character->max_mp, $character->mp + ($character->max_mp - $oldMaxMp));
        }
    }

    /**
     * ベースステータスを取得
     *
     * @param Character $character
     * @return array
     */
    public function getBaseStats(Character $character): array
    {
        $level = $character->level ?? 1;
        
        return [
            'attack' => 10 + ($level - 1) * 2,
            'defense' => 8 + ($level - 1) * 2,
            'agility' => 12 + ($level - 1) * 2,
            'evasion' => 15 + ($level - 1) * 2,
            'magic_attack' => 8 + ($level - 1) * 2,
            'accuracy' => 85 + ($level - 1) * 1,
            'max_hp' => 100 + ($level - 1) * 10,
            'max_sp' => 50 + ($level - 1) * 5,
            'max_mp' => 60 + ($level - 1) * 8,
        ];
    }

    /**
     * スキルボーナスを計算
     *
     * @param Character $character
     * @return array
     */
    public function calculateSkillBonuses(Character $character): array
    {
        $bonuses = [
            'attack' => 0,
            'defense' => 0,
            'agility' => 0,
            'evasion' => 0,
            'magic_attack' => 0,
            'accuracy' => 0,
            'max_hp' => 0,
            'max_sp' => 0,
            'max_mp' => 0,
        ];
        
        $skills = $character->skills;
        
        foreach ($skills as $skill) {
            $skillLevel = $skill->level;
            
            // 全スキル共通ボーナス
            $bonuses['max_hp'] += $skillLevel * 2;
            $bonuses['max_sp'] += $skillLevel * 1;
            $bonuses['max_mp'] += $skillLevel * 1;
            
            // スキル別ボーナス
            switch ($skill->skill_name) {
                case '基本攻撃':
                    $bonuses['attack'] += $skillLevel * 2;
                    $bonuses['defense'] += $skillLevel * 1;
                    $bonuses['accuracy'] += $skillLevel * 1;
                    break;
                case '敏捷':
                    $bonuses['agility'] += $skillLevel * 2;
                    $bonuses['evasion'] += $skillLevel * 1;
                    break;
                case '回避':
                    $bonuses['agility'] += $skillLevel * 1;
                    $bonuses['evasion'] += $skillLevel * 1;
                    break;
                case '魔法攻撃':
                    $bonuses['magic_attack'] += $skillLevel * 2;
                    $bonuses['accuracy'] += $skillLevel * 1;
                    break;
                case '防御':
                    $bonuses['defense'] += $skillLevel * 1;
                    $bonuses['evasion'] += $skillLevel * 1;
                    break;
            }
        }
        
        return $bonuses;
    }

    /**
     * レベル詳細ステータスを取得
     *
     * @param Character $character
     * @return array
     */
    public function getDetailedStatsWithLevel(Character $character): array
    {
        $baseStats = $character->getDetailedStats();
        $skillBonuses = $this->calculateSkillBonuses($character);
        $totalSkillLevel = $character->getTotalSkillLevel();
        
        return array_merge($baseStats, [
            'calculated_level' => $this->calculateCharacterLevel($character),
            'total_skill_level' => $totalSkillLevel,
            'skill_bonuses' => $skillBonuses,
            'base_stats' => $this->getBaseStats($character),
        ]);
    }

    /**
     * 装備効果を含む総合ステータスを取得
     *
     * @param Character $character
     * @return array
     */
    public function getTotalStatsWithEquipment(Character $character): array
    {
        $baseStats = $character->getDetailedStats();
        $equipment = $character->getOrCreateEquipment();
        $equipmentStats = $equipment->getTotalStats();
        
        return [
            'level' => $character->level,
            'attack' => ($baseStats['attack'] ?? 0) + ($equipmentStats['attack'] ?? 0),
            'defense' => ($baseStats['defense'] ?? 0) + ($equipmentStats['defense'] ?? 0),
            'agility' => ($baseStats['agility'] ?? 0) + ($equipmentStats['agility'] ?? 0),
            'evasion' => ($baseStats['evasion'] ?? 0) + ($equipmentStats['evasion'] ?? 0),
            'hp' => $baseStats['hp'] ?? 0,
            'max_hp' => ($baseStats['max_hp'] ?? 0) + ($equipmentStats['hp'] ?? 0),
            'mp' => $baseStats['mp'] ?? 0,
            'max_mp' => ($baseStats['max_mp'] ?? 0) + ($equipmentStats['mp'] ?? 0),
            'accuracy' => ($baseStats['accuracy'] ?? 0) + ($equipmentStats['accuracy'] ?? 0),
            'equipment_effects' => $equipmentStats['effects'] ?? [],
        ];
    }

    /**
     * 戦闘用ステータスを取得
     *
     * @param Character $character
     * @return array
     */
    public function getBattleStats(Character $character): array
    {
        // スキル・装備効果を含む総合ステータス
        $totalStats = $this->getTotalStatsWithEquipment($character);
        $skillBonuses = $this->calculateSkillBonuses($character);
        
        return [
            'level' => $character->level,
            'name' => $character->name,
            
            // 基本バイタル
            'hp' => $character->hp,
            'max_hp' => $totalStats['max_hp'],
            'mp' => $character->mp,
            'max_mp' => $totalStats['max_mp'],
            
            // 戦闘能力値（装備+スキル効果込み）
            'attack' => $totalStats['attack'] + ($skillBonuses['attack'] ?? 0),
            'defense' => $totalStats['defense'] + ($skillBonuses['defense'] ?? 0),
            'agility' => $totalStats['agility'] + ($skillBonuses['agility'] ?? 0),
            'evasion' => $totalStats['evasion'] + ($skillBonuses['evasion'] ?? 0),
            'accuracy' => $totalStats['accuracy'] + ($skillBonuses['accuracy'] ?? 0),
            'magic_attack' => $character->magic_attack + ($skillBonuses['magic_attack'] ?? 0),
            
            // 効果情報
            'equipment_effects' => $totalStats['equipment_effects'],
            'skill_bonuses' => $skillBonuses,
        ];
    }

    /**
     * レベルアップ時のステータス処理
     *
     * @param Character $character
     */
    public function processLevelUpStats(Character $character): void
    {
        // 新しいレベルに基づいてステータスを更新
        $this->updateStatsForLevel($character);
        
        // レベルアップ時の特別処理（HP/MP回復など）
        $character->hp = min($character->max_hp, $character->hp + 20);
        $character->sp = min($character->max_sp, $character->sp + 10);
        $character->mp = min($character->max_mp, $character->mp + 15);
    }

    /**
     * ステータス成長値を計算
     *
     * @param Character $character
     * @param int $fromLevel
     * @param int $toLevel
     * @return array
     */
    public function calculateStatGrowth(Character $character, int $fromLevel, int $toLevel): array
    {
        $fromStats = $this->getBaseStats($character);
        
        // 一時的にレベルを変更して計算
        $originalLevel = $character->level;
        $character->level = $toLevel;
        $toStats = $this->getBaseStats($character);
        $character->level = $originalLevel;
        
        $growth = [];
        foreach ($fromStats as $stat => $value) {
            $growth[$stat] = $toStats[$stat] - $value;
        }
        
        return $growth;
    }
}