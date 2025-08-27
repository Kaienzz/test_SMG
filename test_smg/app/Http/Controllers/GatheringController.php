<?php

namespace App\Http\Controllers;

use App\Models\GatheringMapping;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GatheringController extends Controller
{
    public function gather(Request $request): JsonResponse
    {
        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        // 道またはダンジョンにいるかチェック
        if (!in_array($player->location_type, ['road', 'dungeon'])) {
            return response()->json(['error' => '道またはダンジョンにいる時のみ採集できます。'], 400);
        }

        // 現在位置のルート取得
        $currentRoute = Route::where('id', $player->location_id)
                             ->where('category', $player->location_type)
                             ->first();
        
        if (!$currentRoute) {
            return response()->json(['error' => '現在の位置情報が取得できません。'], 400);
        }

        // 採集スキルをチェック
        if (!$player->hasSkill('採集')) {
            return response()->json(['error' => '採集スキルがありません。'], 400);
        }
        
        $gatheringSkill = $player->getSkill('採集');
        
        // SP消費チェック
        $spCost = $gatheringSkill->getSkillSpCost();
        if ($player->sp < $spCost) {
            return response()->json(['error' => "SPが足りません。必要SP: {$spCost}"], 400);
        }

        // SP消費を反映
        $newSp = $player->sp - $spCost;
        $player->update(['sp' => $newSp]);

        // データベース駆動の採集処理
        $availableMappings = $currentRoute->getGatheringItemsForSkillLevel(
            $gatheringSkill->level, 
            $player->level
        );
        
        if ($availableMappings->isEmpty()) {
            $experienceGained = rand(5, 10);
            
            $environmentName = $currentRoute->category === 'dungeon' ? 'ダンジョン' : '道';
            return response()->json([
                'success' => false,
                'message' => "この{$environmentName}では何も採集できません。",
                'sp_consumed' => $spCost,
                'remaining_sp' => $newSp,
                'experience_gained' => $experienceGained,
                'leveled_up' => false,
                'skill_level' => $gatheringSkill->level,
                'environment' => $currentRoute->category,
                'location_name' => $currentRoute->name,
            ]);
        }

        // ランダムでアイテム選択
        $selectedMapping = $availableMappings->random();
        $actualSuccessRate = $selectedMapping->calculateSuccessRate($gatheringSkill->level);
        $rollResult = mt_rand(1, 100);
        $success = $rollResult <= $actualSuccessRate;
        
        $experienceGained = $success ? rand(15, 25) : rand(5, 15);
        
        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => '採集に失敗しました。',
                'sp_consumed' => $spCost,
                'remaining_sp' => $newSp,
                'experience_gained' => $experienceGained,
                'leveled_up' => false,
                'skill_level' => $gatheringSkill->level,
                'environment' => $currentRoute->category,
                'location_name' => $currentRoute->name,
                'attempted_item' => $selectedMapping->item->name,
                'success_rate' => $actualSuccessRate,
            ]);
        }

        // 採集成功 - アイテム獲得
        $quantity = $selectedMapping->generateRandomQuantity();
        $itemName = $selectedMapping->item->name;

        // TODO: インベントリにアイテム追加処理（後で実装）
        // $player->addItemToInventory($selectedMapping->item_id, $quantity);

        return response()->json([
            'success' => true,
            'message' => "{$itemName}を{$quantity}個採集しました。",
            'item' => $itemName,
            'item_id' => $selectedMapping->item_id,
            'quantity' => $quantity,
            'sp_consumed' => $spCost,
            'remaining_sp' => $newSp,
            'experience_gained' => $experienceGained,
            'leveled_up' => false,
            'skill_level' => $gatheringSkill->level,
            'environment' => $currentRoute->category,
            'location_name' => $currentRoute->name,
            'success_rate' => $actualSuccessRate,
        ]);
    }

    public function getGatheringInfo(Request $request): JsonResponse
    {
        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        // 道またはダンジョンにいるかチェック
        if (!in_array($player->location_type, ['road', 'dungeon'])) {
            return response()->json(['error' => '道またはダンジョンにいる時のみ採集情報を確認できます。'], 400);
        }

        // 現在位置のルート取得
        $currentRoute = Route::where('id', $player->location_id)
                             ->where('category', $player->location_type)
                             ->first();
        
        if (!$currentRoute) {
            return response()->json(['error' => '現在の位置情報が取得できません。'], 400);
        }

        // スキル情報を取得
        $gatheringSkill = $player->getSkill('採集');
        
        if (!$gatheringSkill) {
            return response()->json(['error' => '採集スキルがありません。'], 400);
        }

        // データベース駆動の採集情報取得
        $gatheringInfo = $currentRoute->getPlayerGatheringInfo($gatheringSkill->level, $player->level);
        
        // 全アイテム情報取得（可否判定含む）
        $allMappings = $currentRoute->allGatheringMappings()
                                   ->with('item')
                                   ->orderBy('required_skill_level')
                                   ->orderBy('success_rate', 'desc')
                                   ->get();

        $itemsWithStatus = $allMappings->map(function($mapping) use ($gatheringSkill, $player) {
            $actualSuccessRate = $mapping->calculateSuccessRate($gatheringSkill->level);
            $canGather = $mapping->canPlayerGather($gatheringSkill->level, $player->level);
            
            return [
                'item_id' => $mapping->item_id,
                'item_name' => $mapping->item->name,
                'item_category' => $mapping->item->getCategoryName(),
                'required_skill_level' => $mapping->required_skill_level,
                'base_success_rate' => $mapping->success_rate,
                'actual_success_rate' => $actualSuccessRate,
                'quantity_range' => $mapping->getQuantityRangeString(),
                'quantity_min' => $mapping->quantity_min,
                'quantity_max' => $mapping->quantity_max,
                'can_gather' => $canGather,
                'is_active' => $mapping->is_active,
                'skill_level_met' => $gatheringSkill->level >= $mapping->required_skill_level,
                'level_requirement_met' => $currentRoute->category !== 'dungeon' || 
                                         !$currentRoute->min_level || 
                                         $player->level >= $currentRoute->min_level,
            ];
        })->toArray();

        $availableItemsCount = collect($itemsWithStatus)->where('can_gather', true)->count();
        $spCost = $gatheringSkill->getSkillSpCost();

        return response()->json([
            'skill_level' => $gatheringSkill->level,
            'experience' => $gatheringSkill->experience,
            'required_exp_for_next_level' => 200, // TODO: 適切な計算に置き換え
            'sp_cost' => $spCost,
            'current_sp' => $player->sp,
            'can_gather' => $player->sp >= $spCost && $gatheringInfo['can_gather'],
            'location_name' => $currentRoute->name,
            'environment' => $currentRoute->category,
            'environment_name' => $currentRoute->category === 'dungeon' ? 'ダンジョン' : '道路',
            'min_level_requirement' => $currentRoute->min_level,
            'max_level_requirement' => $currentRoute->max_level,
            'player_level' => $player->level,
            'level_requirements_met' => $currentRoute->category !== 'dungeon' || 
                                       !$currentRoute->min_level || 
                                       $player->level >= $currentRoute->min_level,
            'all_items' => $itemsWithStatus,
            'available_items_count' => $availableItemsCount,
            'gathering_status' => $gatheringInfo,
        ]);
    }
}