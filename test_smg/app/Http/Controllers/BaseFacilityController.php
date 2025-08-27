<?php

namespace App\Http\Controllers;

use App\Models\TownFacility;
use App\Enums\FacilityType;
use App\Contracts\FacilityServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

abstract class BaseFacilityController extends Controller
{
    protected FacilityType $facilityType;
    protected FacilityServiceInterface $facilityService;

    public function __construct(FacilityType $facilityType, FacilityServiceInterface $facilityService)
    {
        $this->facilityType = $facilityType;
        $this->facilityService = $facilityService;
    }

    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        if (!$this->facilityService->canEnterFacility($player->location_id, $player->location_type)) {
            return $this->handleLocationError($request, $this->facilityType->getDisplayName() . 'は町にのみ存在します。');
        }

        $facility = TownFacility::findByLocationAndType(
            $player->location_id, 
            $player->location_type,
            $this->facilityType->value
        );
        
        if (!$facility) {
            return $this->handleFacilityNotFoundError($request, 'この町には' . $this->facilityType->getDisplayName() . 'がありません。');
        }

        $facilityData = $this->facilityService->getFacilityData($facility);
        
        // 現在の場所情報を取得
        $locationNames = [
            'town_prima' => 'プリマ',
            'town_a' => 'A町', // 下位互換性のため
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

        return view($this->facilityType->getViewPrefix() . '.index', [
            'facility' => $facility,
            'facilityData' => $facilityData,
            'player' => $player,
            'currentLocation' => $currentLocation,
            'facilityType' => $this->facilityType,
        ]);
    }

    public function processTransaction(Request $request): JsonResponse
    {
        $validationRules = $this->getValidationRules();
        $request->validate($validationRules);

        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        $facility = TownFacility::findByLocationAndType(
            $player->location_id, 
            $player->location_type,
            $this->facilityType->value
        );

        if (!$facility) {
            return response()->json([
                'success' => false,
                'message' => $this->facilityType->getDisplayName() . 'が見つかりません。'
            ], 404);
        }

        $result = $this->facilityService->processTransaction($facility, $player, $request->all());

        return response()->json($result, $result['success'] ? 200 : ($result['code'] ?? 400));
    }

    protected function handleLocationError(Request $request, string $message): View|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 400);
        }
        
        return redirect('/game')->with('error', $message);
    }

    protected function handleFacilityNotFoundError(Request $request, string $message): View|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 404);
        }
        
        return redirect('/game')->with('error', $message);
    }

    abstract protected function getValidationRules(): array;
}