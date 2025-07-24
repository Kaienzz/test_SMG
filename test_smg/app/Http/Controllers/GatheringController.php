<?php

namespace App\Http\Controllers;

use App\Models\GatheringTable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GatheringController extends Controller
{
    public function gather(Request $request): JsonResponse
    {
        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        
        // 道にいるかチェック
        if ($character->location_type !== 'road') {
            return response()->json(['error' => '道にいる時のみ採集できます。'], 400);
        }

        // 採集スキルをチェック
        if (!$character->hasSkill('採集')) {
            return response()->json(['error' => '採集スキルがありません。'], 400);
        }
        
        $gatheringSkill = $character->getSkill('採集');
        
        // SP消費チェック
        $spCost = $gatheringSkill->getSkillSpCost();
        if ($character->sp < $spCost) {
            return response()->json(['error' => "SPが足りません。必要SP: {$spCost}"], 400);
        }

        // SP消費を反映
        $character->update(['sp' => $character->sp - $spCost]);

        // 実装済みのGatheringTableを使用して採集処理
        $roadId = $character->location_id;
        $gatheringTable = GatheringTable::getAvailableItems($roadId, $gatheringSkill->level);
        
        if (empty($gatheringTable)) {
            $experienceGained = rand(5, 10);
            
            return response()->json([
                'success' => false,
                'message' => 'この道では何も採集できません。',
                'sp_consumed' => $spCost,
                'remaining_sp' => $newSp,
                'experience_gained' => $experienceGained,
                'leveled_up' => false,
                'skill_level' => $gatheringSkill['level'],
            ]);
        }

        $selectedItem = $gatheringTable[array_rand($gatheringTable)];
        $result = GatheringTable::rollForItem($selectedItem);
        
        $experienceGained = rand(10, 20);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => '採集に失敗しました。',
                'sp_consumed' => $spCost,
                'remaining_sp' => $newSp,
                'experience_gained' => $experienceGained,
                'leveled_up' => false,
                'skill_level' => $gatheringSkill['level'],
            ]);
        }

        // レアリティ名を取得
        $rarityNames = [1 => 'コモン', 2 => 'アンコモン', 3 => 'レア', 4 => 'スーパーレア', 5 => 'ウルトラレア', 6 => 'レジェンダリー'];
        $rarityName = $rarityNames[$result['rarity']] ?? 'コモン';

        return response()->json([
            'success' => true,
            'message' => "{$result['item']}を{$result['quantity']}個採集しました。",
            'item' => $result['item'],
            'quantity' => $result['quantity'],
            'rarity' => $rarityName,
            'sp_consumed' => $spCost,
            'remaining_sp' => $newSp,
            'experience_gained' => $experienceGained,
            'leveled_up' => false,
            'skill_level' => $gatheringSkill['level'],
        ]);
    }

    public function getGatheringInfo(Request $request): JsonResponse
    {
        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        
        // 道にいるかチェック
        if ($character->location_type !== 'road') {
            return response()->json(['error' => '道にいる時のみ採集情報を確認できます。'], 400);
        }

        // スキル情報を取得
        $gatheringSkill = $character->skills()->where('skill_type', 'gathering')->where('name', '採集')->first();
        
        if (!$gatheringSkill) {
            return response()->json(['error' => '採集スキルがありません。'], 400);
        }

        // 実装済みのGatheringTableを使用
        $roadId = $playerData['current_location_id'];
        $allItems = GatheringTable::getGatheringTableByRoad($roadId);
        $availableItems = GatheringTable::getAvailableItems($roadId, $gatheringSkill['level']);
        
        // レアリティ名マッピング
        $rarityNames = [1 => 'コモン', 2 => 'アンコモン', 3 => 'レア', 4 => 'スーパーレア', 5 => 'ウルトラレア', 6 => 'レジェンダリー'];
        
        $itemsWithStatus = array_map(function($item) use ($gatheringSkill, $rarityNames) {
            $canGather = $item['required_skill_level'] <= $gatheringSkill['level'];
            return [
                'item_name' => $item['item_name'],
                'required_skill_level' => $item['required_skill_level'],
                'success_rate' => $item['success_rate'],
                'quantity_range' => $item['quantity_min'] . '-' . $item['quantity_max'],
                'can_gather' => $canGather,
                'rarity' => $rarityNames[$item['rarity']] ?? 'コモン',
            ];
        }, $allItems);

        return response()->json([
            'skill_level' => $gatheringSkill['level'],
            'experience' => $gatheringSkill['experience'],
            'required_exp_for_next_level' => 200, // ダミー値
            'sp_cost' => $gatheringSkill['sp_cost'],
            'current_sp' => $characterData['sp'],
            'can_gather' => $characterData['sp'] >= $gatheringSkill['sp_cost'],
            'road_name' => $currentLocation['name'],
            'all_items' => $itemsWithStatus,
            'available_items_count' => count($availableItems),
        ]);
    }
}