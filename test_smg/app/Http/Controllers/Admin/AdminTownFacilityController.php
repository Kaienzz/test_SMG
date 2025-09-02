<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminAuditService;
use App\Models\TownFacility;
use App\Models\FacilityItem;
use App\Models\Route;
use App\Models\CompoundingRecipe;
use App\Models\CompoundingRecipeLocation;
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

            // 調合店の場合は調合レシピ情報も取得
            if ($facility->facility_type === 'compounding_shop') {
                // この施設で利用可能な調合レシピ取得
                $availableRecipes = CompoundingRecipe::whereHas('locations', function($query) use ($facility) {
                    $query->where('location_id', $facility->location_id)
                          ->where('is_active', true);
                })->with(['ingredients.item', 'productItem'])->get();

                // 全ての調合レシピも取得（管理用）
                $allRecipes = CompoundingRecipe::with(['ingredients.item', 'productItem'])
                    ->where('is_active', true)
                    ->get();

                $data['availableRecipes'] = $availableRecipes;
                $data['allRecipes'] = $allRecipes;
                $data['currentRecipeIds'] = $availableRecipes->pluck('id')->toArray();
            }

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

        // 調合店の場合、レシピ選択用のデータを追加
        if ($facility->facility_type === 'compounding_shop') {
            // 現在この施設で利用可能なレシピ
            $currentRecipes = CompoundingRecipe::whereHas('locations', function($query) use ($facility) {
                $query->where('location_id', $facility->location_id)
                      ->where('is_active', true);
            })->pluck('id')->toArray();

            // 全ての有効な調合レシピ
            $allRecipes = CompoundingRecipe::with(['ingredients.item', 'productItem'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $data['allRecipes'] = $allRecipes;
            $data['currentRecipeIds'] = $currentRecipes;
            $data['availableRecipes'] = $allRecipes->whereIn('id', $currentRecipes);
        }

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
     * 調合レシピ更新処理（調合店のみ）
     */
    public function updateRecipes(Request $request, TownFacility $facility)
    {
        $this->initializeForRequest();
        $this->checkPermission('town_facilities.edit');

        // 調合店以外は処理しない
        if ($facility->facility_type !== 'compounding_shop') {
            $errorMessage = 'この施設では調合レシピの管理はできません。';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage
                ], 400);
            }
            
            return back()->withError($errorMessage);
        }

        $validated = $request->validate([
            'recipes' => 'nullable|array',
            'recipes.*' => 'exists:compounding_recipes,id'
        ]);

        try {
            // 現在のレシピ割り当てを取得
            $currentRecipeIds = CompoundingRecipeLocation::where('location_id', $facility->location_id)
                ->where('is_active', true)
                ->pluck('recipe_id')
                ->toArray();

            $newRecipeIds = $validated['recipes'] ?? [];

            // 削除すべきレシピ（現在有効だが新しいリストにない）
            $recipesToRemove = array_diff($currentRecipeIds, $newRecipeIds);
            
            // 追加すべきレシピ（現在無効だが新しいリストにある）
            $recipesToAdd = array_diff($newRecipeIds, $currentRecipeIds);

            // 削除処理：is_activeをfalseに設定
            if (!empty($recipesToRemove)) {
                CompoundingRecipeLocation::where('location_id', $facility->location_id)
                    ->whereIn('recipe_id', $recipesToRemove)
                    ->update(['is_active' => false]);
            }

            // 追加処理：既存レコードがあればis_activeをtrueに、なければ新規作成
            foreach ($recipesToAdd as $recipeId) {
                $existing = CompoundingRecipeLocation::where('location_id', $facility->location_id)
                    ->where('recipe_id', $recipeId)
                    ->first();

                if ($existing) {
                    $existing->update(['is_active' => true]);
                } else {
                    CompoundingRecipeLocation::create([
                        'recipe_id' => $recipeId,
                        'location_id' => $facility->location_id,
                        'is_active' => true
                    ]);
                }
            }

            $this->auditLog('town_facilities.recipes_updated', [
                'facility_id' => $facility->id,
                'facility_name' => $facility->name,
                'location_id' => $facility->location_id,
                'recipes_added' => count($recipesToAdd),
                'recipes_removed' => count($recipesToRemove),
                'total_recipes' => count($newRecipeIds)
            ], 'medium');

            $message = '調合レシピの設定を更新しました。（有効: ' . count($newRecipeIds) . '件）';

            // AJAXリクエストの場合はJSONで返す
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'total_recipes' => count($newRecipeIds)
                ]);
            }

            return back()->withSuccess($message);

        } catch (\Exception $e) {
            Log::error('Admin facility recipes update error: ' . $e->getMessage(), [
                'facility_id' => $facility->id,
                'location_id' => $facility->location_id,
                'recipes' => $validated['recipes'] ?? [],
                'user_id' => $this->user?->id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = '調合レシピの更新中にエラーが発生しました。';

            // AJAXリクエストの場合はJSONで返す
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage
                ], 500);
            }

            return back()
                ->withInput()
                ->withError($errorMessage);
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
     * API: 施設管理用のアイテム一覧取得
     */
    public function getItemsApi()
    {
        $this->initializeForRequest();
        
        $categoryLabels = [
            'material' => '材料',
            'weapon' => '武器',
            'shield' => '盾',
            'head_equipment' => '頭装備',
            'body_equipment' => '胴装備',
            'foot_equipment' => '足装備',
            'accessory' => '装飾品',
            'bag' => '鞄',
            'potion' => 'ポーション',
        ];

        $items = \App\Models\Item::orderBy('category')->orderBy('name')
            ->get(['id', 'name', 'category', 'value'])
            ->map(function ($item) use ($categoryLabels) {
                $categoryLabel = $categoryLabels[$item->category->value] ?? $item->category->value;
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category->value,
                    'category_label' => $categoryLabel,
                    'value' => $item->value,
                    'display_name' => $item->name . ' (' . $categoryLabel . ' - ' . number_format($item->value) . 'G)',
                ];
            });

        return response()->json($items);
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