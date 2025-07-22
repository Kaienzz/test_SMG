<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Player;
use App\Models\GatheringTable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GatheringController extends Controller
{
    public function gather(Request $request): JsonResponse
    {
        $player = Player::first();
        if (!$player) {
            return response()->json(['error' => 'プレイヤーが見つかりません。'], 404);
        }

        $character = $player->getCharacter();
        
        if (!$player->isOnRoad()) {
            return response()->json(['error' => '道にいる時のみ採集できます。'], 400);
        }

        $gatheringSkill = $character->getSkill('採集');
        if (!$gatheringSkill) {
            return response()->json(['error' => '不正なコマンドです。'], 400);
        }

        $spCost = $gatheringSkill->getSkillSpCost();
        if (!$character->consumeSP($spCost)) {
            return response()->json(['error' => "SPが足りません。必要SP: {$spCost}"], 400);
        }

        $character->save();

        $roadId = $player->current_location_id;
        $gatheringTable = GatheringTable::getAvailableItems($roadId, $gatheringSkill->level);
        
        if (empty($gatheringTable)) {
            $experienceGained = $gatheringSkill->calculateExperienceGain();
            $leveledUp = $gatheringSkill->gainExperience($experienceGained);
            
            return response()->json([
                'success' => false,
                'message' => 'この道では何も採集できません。',
                'sp_consumed' => $spCost,
                'remaining_sp' => $character->sp,
                'experience_gained' => $experienceGained,
                'leveled_up' => $leveledUp,
                'skill_level' => $gatheringSkill->level,
            ]);
        }

        $selectedItem = $gatheringTable[array_rand($gatheringTable)];
        $result = GatheringTable::rollForItem($selectedItem);
        
        if (!$result['success']) {
            $experienceGained = $gatheringSkill->calculateExperienceGain();
            $leveledUp = $gatheringSkill->gainExperience($experienceGained);
            
            return response()->json([
                'success' => false,
                'message' => '採集に失敗しました。',
                'sp_consumed' => $spCost,
                'remaining_sp' => $character->sp,
                'experience_gained' => $experienceGained,
                'leveled_up' => $leveledUp,
                'skill_level' => $gatheringSkill->level,
            ]);
        }

        $inventory = $character->getInventory();
        $inventory->addItem($result['item'], $result['quantity']);
        
        $experienceGained = $gatheringSkill->calculateExperienceGain();
        $leveledUp = $gatheringSkill->gainExperience($experienceGained);
        
        return response()->json([
            'success' => true,
            'message' => "{$result['item']}を{$result['quantity']}個採集しました。",
            'item' => $result['item'],
            'quantity' => $result['quantity'],
            'rarity' => $result['rarity'],
            'sp_consumed' => $spCost,
            'remaining_sp' => $character->sp,
            'experience_gained' => $experienceGained,
            'leveled_up' => $leveledUp,
            'skill_level' => $gatheringSkill->level,
        ]);
    }

    public function getGatheringInfo(Request $request): JsonResponse
    {
        $player = Player::first();
        if (!$player) {
            return response()->json(['error' => 'プレイヤーが見つかりません。'], 404);
        }

        $character = $player->getCharacter();
        
        if (!$player->isOnRoad()) {
            return response()->json(['error' => '道にいる時のみ採集情報を確認できます。'], 400);
        }

        $gatheringSkill = $character->getSkill('採集');
        if (!$gatheringSkill) {
            return response()->json(['error' => '採集スキルがありません。'], 400);
        }

        $roadId = $player->current_location_id;
        $allItems = GatheringTable::getGatheringTableByRoad($roadId);
        $availableItems = GatheringTable::getAvailableItems($roadId, $gatheringSkill->level);
        
        $itemsWithStatus = array_map(function($item) use ($gatheringSkill) {
            $canGather = $item['required_skill_level'] <= $gatheringSkill->level;
            return [
                'item_name' => $item['item_name'],
                'required_skill_level' => $item['required_skill_level'],
                'success_rate' => $item['success_rate'],
                'can_gather' => $canGather,
                'rarity' => $item['rarity'],
            ];
        }, $allItems);

        return response()->json([
            'skill_level' => $gatheringSkill->level,
            'experience' => $gatheringSkill->experience,
            'required_exp_for_next_level' => $gatheringSkill->getRequiredExperienceForNextLevel(),
            'sp_cost' => $gatheringSkill->getSkillSpCost(),
            'current_sp' => $character->sp,
            'can_gather' => $character->sp >= $gatheringSkill->getSkillSpCost(),
            'road_name' => $player->getCurrentLocation()->name,
            'all_items' => $itemsWithStatus,
            'available_items_count' => count($availableItems),
        ]);
    }
}