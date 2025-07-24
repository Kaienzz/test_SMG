<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Skill;
use App\Models\ActiveEffect;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SkillController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        $skills = $character->getActiveSkills();
        $activeEffects = $character->activeEffects()
                                  ->where('is_active', true)
                                  ->where('remaining_duration', '>', 0)
                                  ->get();
        $sampleSkills = Skill::getSampleSkills();
        
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
            'skill_name' => 'required|string',
        ]);

        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        $skillName = $request->skill_name;
        
        // キャラクターがスキルを持っているか確認
        if (!$character->hasSkill($skillName)) {
            return response()->json([
                'success' => false,
                'message' => 'そのスキルを習得していません。',
            ], 400);
        }
        
        // スキル使用処理
        $result = $character->useSkill($skillName);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'character_sp' => $character->sp,
                'sp_consumed' => $result['sp_consumed'],
                'experience_gained' => $result['experience_gained'] ?? 0,
                'leveled_up' => $result['leveled_up'] ?? false,
                'skill_level' => $result['skill_level'] ?? 1,
                'effect_applied' => $result['effect_applied'] ?? null,
                'updated_skills' => $character->getActiveSkills(),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }
    }

    public function addSampleSkill(Request $request): JsonResponse
    {
        $request->validate([
            'skill_name' => 'required|string',
        ]);

        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        $skillName = $request->skill_name;
        
        if ($character->hasSkill($skillName)) {
            return response()->json([
                'success' => false,
                'message' => 'そのスキルは既に習得済みです。',
            ], 400);
        }
        
        $sampleSkills = Skill::getSampleSkills();
        $skillData = collect($sampleSkills)->firstWhere('skill_name', $skillName);

        if (!$skillData) {
            return response()->json([
                'success' => false,
                'message' => 'スキルが見つかりません。',
            ], 404);
        }

        $skill = Skill::createForCharacter(
            $character->id,
            $skillData['skill_type'],
            $skillData['skill_name'],
            $skillData['effects'],
            $skillData['sp_cost'],
            $skillData['duration']
        );

        return response()->json([
            'success' => true,
            'message' => "スキル「{$skillName}」を習得しました。",
            'skill' => [
                'id' => $skill->id,
                'name' => $skill->skill_name,
                'type' => $skill->skill_type,
                'level' => $skill->level,
                'sp_cost' => $skill->getSkillSpCost(),
            ],
            'updated_skills' => $character->getActiveSkills(),
        ]);
    }

    public function getActiveEffects(Request $request): JsonResponse
    {
        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        
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
        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        
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