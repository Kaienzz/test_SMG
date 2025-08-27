<?php

namespace App\Http\Controllers;

use App\Models\TownFacility;
use App\Enums\FacilityType;
use App\Services\AlchemyFacilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AlchemyFacilityController extends BaseFacilityController
{
    public function __construct()
    {
        parent::__construct(FacilityType::ALCHEMY_SHOP, new AlchemyFacilityService());
    }

    protected function getValidationRules(): array
    {
        return [
            'base_item_slot' => 'required|integer|min:0',
            'material_slots' => 'required|array|min:1|max:5',
            'material_slots.*' => 'integer|min:0',
        ];
    }

    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        if (!$this->facilityService->canEnterFacility($player->location_id, $player->location_type)) {
            return $this->handleLocationError($request, '錬金屋は町にのみ存在します。');
        }

        $facility = TownFacility::findByLocationAndType(
            $player->location_id, 
            $player->location_type,
            FacilityType::ALCHEMY_SHOP->value
        );
        
        if (!$facility) {
            return $this->handleFacilityNotFoundError($request, 'この町には錬金屋がありません。');
        }

        // 錬金ショップデータを取得
        $facilityData = $this->facilityService->getFacilityData($facility);
        
        // プレイヤーの錬金可能アイテムと素材を取得
        $alchemizableItems = $this->facilityService->getAlchemizableItems($player);
        $materialItems = $this->facilityService->getMaterialItems($player);
        
        // 現在の場所情報を取得
        $locationNames = [
            'town_prima' => 'プリマ',
            'town_a' => 'A町',
            'town_b' => 'B町',
            'town_c' => 'C町',
            'elven_village' => 'エルフの村',
            'merchant_city' => '商業都市'
        ];
        
        $currentLocation = [
            'id' => $player->location_id,
            'type' => $player->location_type,
            'name' => $locationNames[$player->location_id] ?? '未知の場所'
        ];

        return view('facilities.alchemy.index', [
            'facility' => $facility,
            'facilityData' => $facilityData,
            'player' => $player,
            'currentLocation' => $currentLocation,
            'facilityType' => FacilityType::ALCHEMY_SHOP,
            'alchemizableItems' => $alchemizableItems,
            'materialItems' => $materialItems,
            'availableMaterials' => $facilityData['available_materials'],
        ]);
    }

    public function performAlchemy(Request $request): JsonResponse
    {
        $validationRules = [
            'base_item_slot' => 'required|integer|min:0',
            'material_slots' => 'required|array|min:1|max:5',
            'material_slots.*' => 'integer|min:0',
        ];
        
        $request->validate($validationRules);

        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        $facility = TownFacility::findByLocationAndType(
            $player->location_id, 
            $player->location_type,
            FacilityType::ALCHEMY_SHOP->value
        );

        if (!$facility) {
            return response()->json([
                'success' => false,
                'message' => '錬金屋が見つかりません。'
            ], 404);
        }

        $result = $this->facilityService->processTransaction($facility, $player, $request->all());

        return response()->json($result, $result['success'] ? 200 : ($result['code'] ?? 400));
    }

    /**
     * 錬金プレビュー（実際に錬金せずに結果を予測）
     */
    public function previewAlchemy(Request $request): JsonResponse
    {
        $validationRules = [
            'base_item_slot' => 'required|integer|min:0',
            'material_slots' => 'required|array|min:1|max:5',
            'material_slots.*' => 'integer|min:0',
        ];
        
        $request->validate($validationRules);

        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        $baseItemSlot = $request->input('base_item_slot');
        $materialSlots = $request->input('material_slots', []);

        // ベースアイテムの取得
        $inventory = $player->getInventory();
        $inventoryData = $inventory->getInventoryData();
        
        if (!isset($inventoryData[$baseItemSlot]) || isset($inventoryData[$baseItemSlot]['empty'])) {
            return response()->json([
                'success' => false,
                'message' => 'ベースアイテムが見つかりません。'
            ]);
        }

        $baseItemData = $inventoryData[$baseItemSlot];

        // 素材の検証と効果計算
        $materialNames = [];
        foreach ($materialSlots as $materialSlot) {
            if (!isset($inventoryData[$materialSlot]) || isset($inventoryData[$materialSlot]['empty'])) {
                return response()->json([
                    'success' => false,
                    'message' => '素材アイテムが見つかりません。'
                ]);
            }
            $materialData = $inventoryData[$materialSlot];
            $materialItem = $materialData['item_info'] ?? [];
            $materialNames[] = $materialItem['name'] ?? '';
        }

        if (empty($materialNames)) {
            return response()->json([
                'success' => false,
                'message' => '素材を選択してください。'
            ]);
        }

        // 素材効果を計算
        $materialEffects = \App\Models\AlchemyMaterial::calculateCombinedEffects($materialNames);
        
        return response()->json([
            'success' => true,
            'base_item' => $baseItemData['item_info'] ?? [],
            'material_effects' => $materialEffects,
            'estimated_stats' => $this->calculateEstimatedStats(
                $baseItemData['item_info'] ?? [], 
                $materialEffects
            ),
        ]);
    }

    /**
     * 錬金結果の推定値を計算
     */
    private function calculateEstimatedStats(array $baseItem, array $materialEffects): array
    {
        $baseStats = $baseItem['effects'] ?? [];
        $combinedStats = $materialEffects['combined_stats'];
        
        $estimatedMin = [];
        $estimatedMax = [];
        
        // 基本ステータス + 素材効果を基準値とする
        foreach ($baseStats as $stat => $value) {
            $materialBonus = $combinedStats[$stat] ?? 0;
            $baseWithMaterial = $value + $materialBonus;
            
            // 通常品の範囲（90-110%）
            $estimatedMin[$stat] = max(1, (int)round($baseWithMaterial * 0.9));
            $estimatedMax[$stat] = max(1, (int)round($baseWithMaterial * 1.1));
        }
        
        // 素材にのみ存在するステータスも追加
        foreach ($combinedStats as $stat => $value) {
            if (!isset($estimatedMin[$stat]) && $value > 0) {
                $estimatedMin[$stat] = max(1, (int)round($value * 0.9));
                $estimatedMax[$stat] = max(1, (int)round($value * 1.1));
            }
        }
        
        // 名匠品の場合の範囲（120-150%）
        $masterworkMin = [];
        $masterworkMax = [];
        
        foreach ($baseStats as $stat => $value) {
            $materialBonus = $combinedStats[$stat] ?? 0;
            $baseWithMaterial = $value + $materialBonus;
            
            $masterworkMin[$stat] = max(1, (int)round($baseWithMaterial * 1.2));
            $masterworkMax[$stat] = max(1, (int)round($baseWithMaterial * 1.5));
        }
        
        foreach ($combinedStats as $stat => $value) {
            if (!isset($masterworkMin[$stat]) && $value > 0) {
                $masterworkMin[$stat] = max(1, (int)round($value * 1.2));
                $masterworkMax[$stat] = max(1, (int)round($value * 1.5));
            }
        }
        
        return [
            'normal' => [
                'min' => $estimatedMin,
                'max' => $estimatedMax,
            ],
            'masterwork' => [
                'min' => $masterworkMin,
                'max' => $masterworkMax,
            ],
            'masterwork_chance' => $materialEffects['total_masterwork_chance'],
        ];
    }
}