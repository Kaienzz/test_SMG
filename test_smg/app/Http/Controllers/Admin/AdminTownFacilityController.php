<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminAuditService;
use App\Models\TownFacility;
use App\Models\FacilityItem;
use App\Models\Route;
use App\Enums\FacilityType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * 町施設管理コントローラー
 * 
 * 各町の施設管理機能を提供
 */
class AdminTownFacilityController extends AdminController
{
    public function __construct(AdminAuditService $auditService)
    {
        parent::__construct($auditService);
    }

    /**
     * 町施設管理ダッシュボード
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.view');
        $this->trackPageAccess('town_facilities.index');

        try {
            // 全施設リスト（ページネーション付き）
            $facilities = TownFacility::query()
                        ->when($request->get('location_id'), function($query, $locationId) {
                            return $query->where('location_id', $locationId);
                        })
                        ->when($request->get('facility_type'), function($query, $facilityType) {
                            return $query->where('facility_type', $facilityType);
                        })
                        ->when($request->get('search'), function($query, $search) {
                            return $query->where('name', 'like', "%{$search}%");
                        })
                        ->orderBy('location_id')
                        ->orderBy('facility_type')
                        ->paginate(20);

            // 全施設をロケーション別にグループ化（ビューで使用）
            $allFacilities = TownFacility::all();
            $facilitiesByLocation = $allFacilities->groupBy('location_id');

            // 各町の施設統計
            $locationStats = TownFacility::selectRaw('location_id, location_type, COUNT(*) as facility_count, 
                                              COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count')
                                   ->groupBy('location_id', 'location_type')
                                   ->orderBy('location_id')
                                   ->get();

            // 施設タイプ別統計
            $facilitiesByType = TownFacility::selectRaw('facility_type, COUNT(*) as count, 
                                          COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count')
                              ->groupBy('facility_type')
                              ->orderBy('facility_type')
                              ->get();

            // 最近作成された施設
            $recentFacilities = TownFacility::with([])
                              ->orderBy('created_at', 'desc')
                              ->limit(10)
                              ->get();

            $data = [
                'facilities' => $facilities,
                'facilitiesByLocation' => $facilitiesByLocation,
                'locationStats' => $locationStats,
                'facilitiesByType' => $facilitiesByType,
                'recentFacilities' => $recentFacilities,
                'facilityTypes' => FacilityType::cases(),
                'canManageFacilities' => true, // TODO: Implement proper permission check
                'filters' => [
                    'location_id' => $request->get('location_id'),
                    'facility_type' => $request->get('facility_type'),
                    'search' => $request->get('search'),
                ],
                'breadcrumb' => $this->buildBreadcrumb([
                    ['title' => '町施設管理', 'active' => true]
                ])
            ];

            $this->auditLog('town_facilities.index.viewed', [
                'total_facilities' => $facilities->total(),
                'filters_applied' => array_filter($data['filters'])
            ]);

            return view('admin.town-facilities.index', $data);

        } catch (\Exception $e) {
            Log::error('Admin town facilities index error: ' . $e->getMessage(), [
                'user_id' => $this->user?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withError('施設データの取得中にエラーが発生しました。');
        }
    }

    /**
     * 施設詳細表示
     */
    public function show(Request $request, TownFacility $facility)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.view');
        $this->trackPageAccess('town_facilities.show');

        try {
            // 施設アイテム取得
            $facilityItems = $facility->facilityItems()
                             ->with('item')
                             ->orderBy('price')
                             ->get();

            $data = [
                'facility' => $facility,
                'facilityItems' => $facilityItems,
                'facilityType' => FacilityType::from($facility->facility_type),
                'breadcrumb' => $this->buildBreadcrumb([
                    ['title' => '町施設管理', 'url' => route('admin.town-facilities.index'), 'active' => false],
                    ['title' => $facility->name, 'active' => true]
                ])
            ];

            $this->auditLog('town_facilities.show.viewed', [
                'facility_id' => $facility->id,
                'facility_name' => $facility->name,
                'location' => $facility->location_id
            ]);

            return view('admin.town-facilities.show', $data);

        } catch (\Exception $e) {
            Log::error('Admin town facility show error: ' . $e->getMessage(), [
                'facility_id' => $facility->id,
                'user_id' => $this->user?->id
            ]);

            return back()->withError('施設詳細の取得中にエラーが発生しました。');
        }
    }

    /**
     * 施設作成フォーム表示
     */
    public function create(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.create');

        $data = [
            'facilityTypes' => FacilityType::cases(),
            'locations' => $this->getAvailableLocations(),
            'breadcrumb' => $this->buildBreadcrumb([
                ['title' => '町施設管理', 'url' => route('admin.town-facilities.index'), 'active' => false],
                ['title' => '新規施設作成', 'active' => true]
            ])
        ];

        return view('admin.town-facilities.create', $data);
    }

    /**
     * 施設作成処理
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'facility_type' => [
                'required',
                'string',
                'in:' . implode(',', array_column(FacilityType::cases(), 'value')),
                Rule::unique('town_facilities')->where(function ($query) use ($request) {
                    return $query->where('location_id', $request->location_id)
                                 ->where('location_type', $request->location_type);
                })
            ],
            'location_id' => 'required|string|max:100',
            'location_type' => 'required|string|in:town,road,dungeon',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'facility_config' => 'nullable|array'
        ]);

        try {
            $facility = TownFacility::create($validated);

            $this->auditLog('town_facilities.created', [
                'facility_id' => $facility->id,
                'facility_name' => $facility->name,
                'location' => $facility->location_id,
                'facility_type' => $facility->facility_type
            ], 'medium');

            return redirect()
                ->route('admin.town-facilities.show', $facility)
                ->withSuccess('施設が正常に作成されました。');

        } catch (\Exception $e) {
            Log::error('Admin town facility creation error: ' . $e->getMessage(), [
                'data' => $validated,
                'user_id' => $this->user?->id
            ]);

            return back()
                ->withInput()
                ->withError('施設の作成中にエラーが発生しました。');
        }
    }

    /**
     * 施設編集フォーム表示
     */
    public function edit(Request $request, TownFacility $facility)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.edit');

        $data = [
            'facility' => $facility,
            'facilityTypes' => FacilityType::cases(),
            'locations' => $this->getAvailableLocations(),
            'breadcrumb' => $this->buildBreadcrumb([
                ['title' => '町施設管理', 'url' => route('admin.town-facilities.index'), 'active' => false],
                ['title' => $facility->name, 'url' => route('admin.town-facilities.show', $facility), 'active' => false],
                ['title' => '編集', 'active' => true]
            ])
        ];

        return view('admin.town-facilities.edit', $data);
    }

    /**
     * 施設更新処理
     */
    public function update(Request $request, TownFacility $facility)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'facility_type' => 'required|string|in:' . implode(',', array_column(FacilityType::cases(), 'value')),
            'location_id' => 'required|string|max:100',
            'location_type' => 'required|string|in:town,road,dungeon',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'facility_config' => 'nullable|array'
        ]);

        try {
            $originalData = $facility->toArray();
            $facility->update($validated);

            $this->auditLog('town_facilities.updated', [
                'facility_id' => $facility->id,
                'facility_name' => $facility->name,
                'changes' => array_diff_assoc($validated, $originalData)
            ], 'medium');

            return redirect()
                ->route('admin.town-facilities.show', $facility)
                ->withSuccess('施設が正常に更新されました。');

        } catch (\Exception $e) {
            Log::error('Admin town facility update error: ' . $e->getMessage(), [
                'facility_id' => $facility->id,
                'data' => $validated,
                'user_id' => $this->user?->id
            ]);

            return back()
                ->withInput()
                ->withError('施設の更新中にエラーが発生しました。');
        }
    }

    /**
     * 施設削除処理
     */
    public function destroy(Request $request, TownFacility $facility)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.delete');

        try {
            $facilityName = $facility->name;
            $facilityId = $facility->id;

            // 関連するFacilityItemsも削除
            $facility->facilityItems()->delete();
            $facility->delete();

            $this->auditLog('town_facilities.deleted', [
                'facility_id' => $facilityId,
                'facility_name' => $facilityName
            ], 'high');

            return redirect()
                ->route('admin.town-facilities.index')
                ->withSuccess("施設「{$facilityName}」を正常に削除しました。");

        } catch (\Exception $e) {
            Log::error('Admin town facility deletion error: ' . $e->getMessage(), [
                'facility_id' => $facility->id,
                'user_id' => $this->user?->id
            ]);

            return back()->withError('施設の削除中にエラーが発生しました。');
        }
    }

    /**
     * 施設重複チェック（Ajax用）
     */
    public function checkDuplicate(Request $request)
    {
        $this->initializeForRequest();
        
        $request->validate([
            'location_id' => 'required|string',
            'location_type' => 'required|string',
            'facility_type' => 'required|string'
        ]);

        $exists = TownFacility::where('location_id', $request->location_id)
                              ->where('location_type', $request->location_type)
                              ->where('facility_type', $request->facility_type)
                              ->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * 施設設定更新処理
     */
    public function updateConfig(Request $request, TownFacility $facility)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.edit');

        $validated = $request->validate([
            'config' => 'required|array'
        ]);

        try {
            $facility->update([
                'facility_config' => $validated['config']
            ]);

            $this->auditLog('town_facilities.config_updated', [
                'facility_id' => $facility->id,
                'facility_name' => $facility->name,
                'config_changes' => $validated['config']
            ], 'medium');

            return redirect()
                ->route('admin.town-facilities.edit', $facility)
                ->withSuccess('施設設定が正常に更新されました。');

        } catch (\Exception $e) {
            Log::error('Admin town facility config update error: ' . $e->getMessage(), [
                'facility_id' => $facility->id,
                'config' => $validated['config'],
                'user_id' => $this->user?->id
            ]);

            return back()
                ->withInput()
                ->withError('施設設定の更新中にエラーが発生しました。');
        }
    }

    /**
     * 施設アイテム追加処理
     */
    public function addItem(Request $request, TownFacility $facility)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.edit');

        $validated = $request->validate([
            'item_id' => 'required|string',
            'item_name' => 'required|string|max:255',
            'price' => 'required|integer|min:1',
            'stock' => 'required|integer|min:-1', // -1 for infinite
            'is_available' => 'boolean'
        ]);

        try {
            $facilityItem = $facility->facilityItems()->create($validated);

            $this->auditLog('facility_items.added', [
                'facility_id' => $facility->id,
                'facility_name' => $facility->name,
                'item_id' => $validated['item_id'],
                'item_name' => $validated['item_name'],
                'price' => $validated['price']
            ], 'medium');

            return response()->json([
                'success' => true,
                'message' => 'アイテムが正常に追加されました。',
                'item' => $facilityItem
            ]);

        } catch (\Exception $e) {
            Log::error('Facility item add error: ' . $e->getMessage(), [
                'facility_id' => $facility->id,
                'item_data' => $validated,
                'user_id' => $this->user?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'アイテムの追加中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * 施設アイテム編集処理
     */
    public function updateItem(Request $request, TownFacility $facility, FacilityItem $item)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.edit');

        // Check if item belongs to facility
        if ($item->town_facility_id !== $facility->id) {
            return response()->json([
                'success' => false,
                'message' => '指定されたアイテムはこの施設に属していません。'
            ], 403);
        }

        $validated = $request->validate([
            'price' => 'required|integer|min:1',
            'stock' => 'required|integer|min:-1',
            'is_available' => 'boolean'
        ]);

        try {
            $originalData = $item->toArray();
            $item->update($validated);

            $this->auditLog('facility_items.updated', [
                'facility_id' => $facility->id,
                'item_id' => $item->id,
                'changes' => array_diff_assoc($validated, $originalData)
            ], 'low');

            return response()->json([
                'success' => true,
                'message' => 'アイテム情報が正常に更新されました。',
                'item' => $item->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Facility item update error: ' . $e->getMessage(), [
                'facility_id' => $facility->id,
                'item_id' => $item->id,
                'user_id' => $this->user?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'アイテム情報の更新中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * 施設アイテム削除処理
     */
    public function deleteItem(Request $request, TownFacility $facility, FacilityItem $item)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.edit');

        // Check if item belongs to facility
        if ($item->town_facility_id !== $facility->id) {
            return response()->json([
                'success' => false,
                'message' => '指定されたアイテムはこの施設に属していません。'
            ], 403);
        }

        try {
            $itemName = $item->item_name;
            $itemId = $item->id;
            
            $item->delete();

            $this->auditLog('facility_items.deleted', [
                'facility_id' => $facility->id,
                'item_id' => $itemId,
                'item_name' => $itemName
            ], 'medium');

            return response()->json([
                'success' => true,
                'message' => "アイテム「{$itemName}」を削除しました。"
            ]);

        } catch (\Exception $e) {
            Log::error('Facility item delete error: ' . $e->getMessage(), [
                'facility_id' => $facility->id,
                'item_id' => $item->id,
                'user_id' => $this->user?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'アイテムの削除中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * 利用可能なロケーション一覧取得
     */
    private function getAvailableLocations(): array
    {
        return Route::where('category', 'town')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(function($town) {
                        return [
                            'id' => $town->id,
                            'name' => $town->name,
                            'type' => 'town'
                        ];
                    })
                    ->toArray();
    }
}