<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Skill;
use App\Models\ActiveEffect;
use App\Services\DummyDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function index(Request $request): View
    {
        $characterId = $request->query('character_id', 1);
        $character = (object) DummyDataService::getCharacter($characterId);
        $skills = DummyDataService::getSkills($characterId);
        $activeEffects = collect(DummyDataService::getActiveEffects($characterId))->map(function($effect) {
            return (object) $effect;
        });
        $sampleSkills = DummyDataService::getSampleSkills();
        
        return view('skills.index', compact(
            'character',
            'skills',
            'activeEffects',
            'sampleSkills'
        ));
    }

    public function useSkill(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|integer',
            'skill_name' => 'required|string',
        ]);

        $characterId = $request->character_id;
        $skillName = $request->skill_name;
        
        $character = DummyDataService::getCharacter($characterId);
        $currentSp = session('character_sp', $character['sp']);
        
        // ダミースキル使用処理
        if ($skillName === '飛脚術') {
            $spCost = 10;
            if ($currentSp >= $spCost) {
                $newSp = $currentSp - $spCost;
                session(['character_sp' => $newSp]);
                
                return response()->json([
                    'success' => true,
                    'message' => "スキル「{$skillName}」を使用しました。",
                    'character_sp' => $newSp,
                    'sp_consumed' => $spCost,
                    'experience_gained' => 21,
                    'leveled_up' => false,
                    'skill_level' => 3,
                    'effect_applied' => [
                        'success' => true,
                        'message' => '飛脚術効果が発動しました。',
                        'effects' => ['dice_bonus' => 3, 'extra_dice' => 1],
                        'duration' => 5
                    ],
                    'updated_skills' => DummyDataService::getSkills($characterId),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "SPが足りません。必要SP: {$spCost}",
                ], 400);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'そのスキルは実装されていません。',
        ], 400);
    }

    public function addSampleSkill(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|integer',
            'skill_name' => 'required|string',
        ]);

        $characterId = $request->character_id;
        $skillName = $request->skill_name;
        
        $sampleSkills = DummyDataService::getSampleSkills();
        $skillData = collect($sampleSkills)->firstWhere('skill_name', $skillName);

        if (!$skillData) {
            return response()->json([
                'success' => false,
                'message' => 'スキルが見つかりません。',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "スキル「{$skillName}」を習得しました。",
            'skill' => [
                'id' => rand(10, 99),
                'name' => $skillData['skill_name'],
                'type' => $skillData['skill_type'],
                'level' => 1,
                'sp_cost' => $skillData['sp_cost'],
            ],
            'updated_skills' => DummyDataService::getSkills($characterId),
        ]);
    }

    public function getActiveEffects(Request $request): JsonResponse
    {
        $characterId = $request->query('character_id', 1);
        $character = Character::findOrFail($characterId);
        
        $activeEffects = $character->activeEffects()
                                  ->where('is_active', true)
                                  ->where('remaining_duration', '>', 0)
                                  ->get()
                                  ->map(function($effect) {
                                      return [
                                          'id' => $effect->id,
                                          'name' => $effect->effect_name,
                                          'source_type' => $effect->source_type,
                                          'effects' => $effect->effects,
                                          'remaining_duration' => $effect->remaining_duration,
                                      ];
                                  });

        return response()->json([
            'success' => true,
            'active_effects' => $activeEffects,
        ]);
    }

    public function decreaseEffectDurations(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|integer',
        ]);

        $characterId = $request->character_id;
        $character = Character::findOrFail($characterId);
        
        $activeEffects = $character->activeEffects()
                                  ->where('is_active', true)
                                  ->where('remaining_duration', '>', 0)
                                  ->get();

        $expiredEffects = [];
        foreach ($activeEffects as $effect) {
            $stillActive = $effect->decreaseDuration(1);
            if (!$stillActive) {
                $expiredEffects[] = $effect->effect_name;
            }
        }

        return response()->json([
            'success' => true,
            'message' => '効果の持続時間を減少させました。',
            'expired_effects' => $expiredEffects,
            'remaining_effects' => $character->activeEffects()
                                           ->where('is_active', true)
                                           ->where('remaining_duration', '>', 0)
                                           ->get()
                                           ->map(function($effect) {
                                               return [
                                                   'name' => $effect->effect_name,
                                                   'remaining_duration' => $effect->remaining_duration,
                                               ];
                                           }),
        ]);
    }
}