<?php

namespace App\Domain\Character;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Characterクラスのスキル関連機能を分離したTrait
 * 
 * Phase 4: Character分割でのコード分離により単一責任原則を強化
 * スキルシステムの複雑性をCharacterクラスから切り離し
 */
trait CharacterSkills
{
    // キャッシュ用プロパティ
    private $_skillBonusesCache = null;

    /**
     * スキルとのリレーション
     *
     * @return HasMany
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * スキルを使用する
     *
     * @param string $skillName
     * @return array
     */
    public function useSkill(string $skillName): array
    {
        $skill = $this->getSkill($skillName);

        if (!$skill) {
            return ['success' => false, 'message' => "スキル「{$skillName}」を習得していません。"];
        }

        if (!$skill->is_active) {
            return ['success' => false, 'message' => "スキル「{$skillName}」は無効化されています。"];
        }

        $spCost = $skill->getSkillSpCost();
        if ($this->sp < $spCost) {
            return ['success' => false, 'message' => 'SPが不足しています。'];
        }

        $result = $skill->applySkillEffect($this->id);
        $this->decrement('sp', $spCost);

        if ($result['success']) {
            $expGain = $skill->calculateExperienceGain();
            $leveledUp = $skill->gainExperience($expGain);

            $result['skill_leveled_up'] = $leveledUp;
        }

        return $result;
    }

    /**
     * 指定されたスキルを取得する
     *
     * @param string $skillName
     * @return Skill|null
     */
    public function getSkill(string $skillName): ?Skill
    {
        return $this->skills()->where('skill_name', $skillName)->first();
    }

    /**
     * 指定されたスキルを習得しているかチェック
     *
     * @param string $skillName
     * @return bool
     */
    public function hasSkill(string $skillName): bool
    {
        return $this->skills()->where('skill_name', $skillName)->exists();
    }

    /**
     * アクティブなスキルを取得
     *
     * @return array
     */
    public function getActiveSkills(): array
    {
        return $this->skills()->where('is_active', true)->get()->toArray();
    }

    /**
     * 新しいスキルを習得する
     *
     * @param string $skillType
     * @param string $skillName
     * @param array $effects
     * @param int $spCost
     * @param int $duration
     * @return Skill
     */
    public function learnSkill(string $skillType, string $skillName, array $effects = [], int $spCost = 10, int $duration = 5): Skill
    {
        // 既に習得済みの場合は既存のスキルを返す
        $existingSkill = $this->getSkill($skillName);
        if ($existingSkill) {
            return $existingSkill;
        }

        // 新しいスキルを作成
        $skill = Skill::createForCharacter($this->id, $skillType, $skillName, $effects, $spCost, $duration);

        // スキルボーナスキャッシュをクリア
        $this->clearSkillBonusesCache();

        return $skill;
    }

    /**
     * スキルリストを取得する
     *
     * @return array
     */
    public function getSkillList(): array
    {
        return $this->skills()->get()->map(function($skill) {
            return [
                'id' => $skill->id,
                'skill_name' => $skill->skill_name,
                'skill_type' => $skill->skill_type,
                'level' => $skill->level,
                'experience' => $skill->experience,
                'required_exp' => $skill->getRequiredExperienceForNextLevel(),
                'sp_cost' => $skill->getSkillSpCost(),
                'is_active' => $skill->is_active,
                'effects' => $skill->effects,
            ];
        })->toArray();
    }

    /**
     * 総スキルレベルを取得する
     *
     * @return int
     */
    public function getTotalSkillLevel(): int
    {
        return $this->skills()->sum('level');
    }

    /**
     * スキルボーナスを計算する
     *
     * @return array
     */
    private function calculateSkillBonuses(): array
    {
        // キャッシュがある場合はキャッシュを返す
        $cacheKey = 'skill_bonuses_' . $this->id;
        if (isset($this->_skillBonusesCache)) {
            return $this->_skillBonusesCache;
        }

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

        // アクティブなスキルのボーナスを計算
        $skills = $this->relationLoaded('skills') 
            ? $this->skills->where('is_active', true)
            : $this->skills()->where('is_active', true)->get();

        foreach ($skills as $skill) {
            $skillLevel = $skill->level;
            $skillType = $skill->skill_type;

            // 全スキル共通ボーナス
            $bonuses['max_hp'] += $skillLevel * 2;
            $bonuses['max_sp'] += $skillLevel * 1;
            $bonuses['max_mp'] += $skillLevel * 1;

            // スキルタイプ別ボーナス
            switch ($skillType) {
                case 'combat':
                    $bonuses['attack'] += $skillLevel * 2;
                    $bonuses['defense'] += $skillLevel * 1;
                    $bonuses['accuracy'] += $skillLevel * 1;
                    break;

                case 'movement':
                    $bonuses['agility'] += $skillLevel * 2;
                    $bonuses['evasion'] += $skillLevel * 1;
                    break;

                case 'reconnaissance':
                    $bonuses['agility'] += $skillLevel * 1;
                    $bonuses['evasion'] += $skillLevel * 1;
                    break;

                case 'magic':
                    $bonuses['magic_attack'] += $skillLevel * 2;
                    $bonuses['accuracy'] += $skillLevel * 1;
                    break;

                case 'defense':
                    $bonuses['defense'] += $skillLevel * 1;
                    $bonuses['evasion'] += $skillLevel * 1;
                    break;
            }
        }

        // キャッシュに保存
        $this->_skillBonusesCache = $bonuses;

        return $bonuses;
    }

    /**
     * スキルボーナスキャッシュをクリアする
     *
     * @return void
     */
    public function clearSkillBonusesCache(): void
    {
        $this->_skillBonusesCache = null;
    }

    /**
     * ステータス計算時にスキルボーナスを取得する
     * 
     * このメソッドはCharacterクラスから呼び出される
     *
     * @return array
     */
    public function getSkillBonusesForStats(): array
    {
        return $this->calculateSkillBonuses();
    }
}