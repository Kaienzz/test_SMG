<?php

namespace App\Http\Controllers\Admin;

use App\Models\Item;
use App\Models\CustomItem;
use App\Models\AlchemyMaterial;
use App\Enums\ItemCategory;
use App\Services\Admin\AdminAuditService;
use App\Services\StandardItem\StandardItemService;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdminItemController extends AdminController
{
    public function __construct(AdminAuditService $auditService)
    {
        parent::__construct($auditService);
        // Laravel 11では、コンストラクタ内でのmiddleware()は使用できません
        // 各メソッド内で権限チェックを実行します
    }

    /**
     * アイテム一覧表示
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.view');
        
        $filters = $request->only(['search', 'category', 'min_value', 'max_value', 'weapon_type', 'sort_by', 'sort_direction', 'item_type']);
        
        // アイテムタイプフィルタ (standard, custom, all)
        $itemType = $filters['item_type'] ?? 'all';
        
        // 標準アイテムの取得
        $standardItems = collect(\App\Services\DummyDataService::getStandardItems());
        
        // 標準アイテムにフィルタを適用
        if (!empty($filters['search'])) {
            $standardItems = $standardItems->filter(function($item) use ($filters) {
                return stripos($item['name'], $filters['search']) !== false ||
                       stripos($item['description'], $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['category'])) {
            $standardItems = $standardItems->filter(function($item) use ($filters) {
                return $item['category'] === $filters['category'];
            });
        }
        
        if (!empty($filters['min_value'])) {
            $standardItems = $standardItems->filter(function($item) use ($filters) {
                return $item['value'] >= $filters['min_value'];
            });
        }
        
        if (!empty($filters['max_value'])) {
            $standardItems = $standardItems->filter(function($item) use ($filters) {
                return $item['value'] <= $filters['max_value'];
            });
        }
        
        if (!empty($filters['weapon_type'])) {
            $standardItems = $standardItems->filter(function($item) use ($filters) {
                return $item['weapon_type'] === $filters['weapon_type'];
            });
        }
        
        // カスタムアイテムにフィルタを適用
        $query = Item::query();
        
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        if (!empty($filters['min_value'])) {
            $query->where('value', '>=', $filters['min_value']);
        }
        
        if (!empty($filters['max_value'])) {
            $query->where('value', '<=', $filters['max_value']);
        }
        
        if (!empty($filters['weapon_type'])) {
            $query->where('weapon_type', $filters['weapon_type']);
        }
        
        // ソート設定
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        
        // カスタムアイテムのソート
        $query->orderBy($sortBy, $sortDirection);
        $customItems = $query->get();
        
        // 標準アイテムのソート
        $standardItems = $standardItems->sortBy($sortBy, SORT_REGULAR, $sortDirection === 'desc');
        
        // アイテムタイプフィルタに基づいて結合
        $allItems = collect();
        
        if ($itemType === 'all' || $itemType === 'standard') {
            $allItems = $allItems->concat($standardItems->values());
        }
        
        if ($itemType === 'all' || $itemType === 'custom') {
            $customItemsArray = $customItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'category' => $item->category->value,
                    'category_name' => $item->category->getDisplayName(),
                    'effects' => $item->effects,
                    'value' => $item->value,
                    'sell_price' => $item->sell_price,
                    'stack_limit' => $item->stack_limit,
                    'max_durability' => $item->max_durability,
                    'weapon_type' => $item->weapon_type,
                    'is_standard' => false,
                ];
            });
            $allItems = $allItems->concat($customItemsArray);
        }
        
        // 統計データの計算
        $standardCount = \App\Services\DummyDataService::getStandardItems();
        $customCount = Item::count();
        
        $standardCategoryCounts = collect($standardCount)
            ->groupBy('category')
            ->map(function($items) { return count($items); });
            
        $customCategoryCounts = Item::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get()
            ->mapWithKeys(function ($item) {
                $categoryKey = $item->category instanceof \App\Enums\ItemCategory ? 
                              $item->category->value : 
                              $item->category;
                return [$categoryKey => $item->count];
            });
        
        // カテゴリ統計を統合
        $allCategories = $standardCategoryCounts->keys()->concat($customCategoryCounts->keys())->unique();
        $combinedCategoryCounts = $allCategories->mapWithKeys(function($category) use ($standardCategoryCounts, $customCategoryCounts) {
            $standardCount = $standardCategoryCounts->get($category, 0);
            $customCount = $customCategoryCounts->get($category, 0);
            return [$category => $standardCount + $customCount];
        });
        
        $stats = [
            'total_standard' => count($standardCount),
            'total_custom' => $customCount,
            'total_items' => count($standardCount) + $customCount,
            'by_category' => $combinedCategoryCounts,
            'avg_value_custom' => Item::avg('value') ?? 0,
            'total_value_custom' => Item::sum('value') ?? 0,
            'avg_value_standard' => collect($standardCount)->avg('value'),
            'total_value_standard' => collect($standardCount)->sum('value'),
        ];
        
        $this->auditLog('items.index.viewed', [
            'filters' => $filters,
            'item_type' => $itemType,
            'result_count' => $allItems->count()
        ]);
        
        return view('admin.items.index', [
            'items' => $allItems->forPage($request->get('page', 1), 20),
            'stats' => $stats,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'itemType' => $itemType,
            'pagination' => [
                'current_page' => $request->get('page', 1),
                'total' => $allItems->count(),
                'per_page' => 20,
                'last_page' => ceil($allItems->count() / 20),
            ]
        ]);
    }

    /**
     * アイテム詳細表示
     */
    public function show(Item $item)
    {
        $this->checkPermission('items.view');
        
        // 関連データの取得
        $customItems = CustomItem::where('base_item_id', $item->id)
                                ->with('creator')
                                ->limit(10)
                                ->get();
        
        // ショップでの販売状況
        $shopItems = DB::table('shop_items')
                      ->join('shops', 'shop_items.shop_id', '=', 'shops.id')
                      ->where('shop_items.item_id', $item->id)
                      ->select('shops.name as shop_name', 'shop_items.price', 'shop_items.stock')
                      ->get();
        
        // 使用統計（プレイヤーのインベントリから）
        $usageStats = $this->getItemUsageStats($item->id);
        
        $this->auditLog('items.show.viewed', [
            'item_id' => $item->id,
            'item_name' => $item->name
        ]);
        
        return view('admin.items.show', compact('item', 'customItems', 'shopItems', 'usageStats'));
    }

    /**
     * アイテム作成フォーム
     */
    public function create()
    {
        $this->checkPermission('items.create');
        
        $categories = ItemCategory::cases();
        $weaponTypes = [
            Item::WEAPON_TYPE_PHYSICAL => '物理武器',
            Item::WEAPON_TYPE_MAGICAL => '魔法武器',
        ];
        
        return view('admin.items.create', compact('categories', 'weaponTypes'));
    }

    /**
     * アイテム保存
     */
    public function store(Request $request)
    {
        $this->checkPermission('items.create');
        
        $validator = $this->validateItemData($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            DB::beginTransaction();
            
            $data = $request->only([
                'name', 'description', 'category', 'stack_limit', 'max_durability',
                'value', 'sell_price', 'battle_skill_id', 'weapon_type'
            ]);
            
            // エフェクトデータの処理
            $effects = $this->processEffectsData($request);
            if (!empty($effects)) {
                $data['effects'] = $effects;
            }
            
            $item = Item::create($data);
            
            DB::commit();
            
            $this->auditLog('items.created', [
                'item_id' => $item->id,
                'item_data' => $data
            ], 'high');
            
            return redirect()->route('admin.items.show', $item)
                           ->with('success', 'アイテムが作成されました。');
            
        } catch (\Exception $e) {
            DB::rollback();
            
            $this->auditLog('items.create.failed', [
                'error' => $e->getMessage(),
                'item_data' => $request->all()
            ], 'critical');
            
            return back()->withError('アイテムの作成に失敗しました: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * アイテム編集フォーム
     */
    public function edit(Item $item)
    {
        $this->checkPermission('items.edit');
        
        $categories = ItemCategory::cases();
        $weaponTypes = [
            Item::WEAPON_TYPE_PHYSICAL => '物理武器',
            Item::WEAPON_TYPE_MAGICAL => '魔法武器',
        ];
        
        return view('admin.items.edit', compact('item', 'categories', 'weaponTypes'));
    }

    /**
     * アイテム更新
     */
    public function update(Request $request, Item $item)
    {
        $this->checkPermission('items.edit');
        
        $validator = $this->validateItemData($request, $item);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            DB::beginTransaction();
            
            $originalData = $item->toArray();
            
            $data = $request->only([
                'name', 'description', 'category', 'stack_limit', 'max_durability',
                'value', 'sell_price', 'battle_skill_id', 'weapon_type'
            ]);
            
            // エフェクトデータの処理
            $effects = $this->processEffectsData($request);
            if (!empty($effects)) {
                $data['effects'] = $effects;
            }
            
            $item->update($data);
            
            DB::commit();
            
            $this->auditLog('items.updated', [
                'item_id' => $item->id,
                'original_data' => $originalData,
                'updated_data' => $data,
                'changes' => $item->getChanges()
            ], 'high');
            
            return redirect()->route('admin.items.show', $item)
                           ->with('success', 'アイテムが更新されました。');
            
        } catch (\Exception $e) {
            DB::rollback();
            
            $this->auditLog('items.update.failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()->withError('アイテムの更新に失敗しました: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * アイテム削除
     */
    public function destroy(Item $item)
    {
        $this->checkPermission('items.delete');
        
        try {
            DB::beginTransaction();
            
            // 関連データの確認
            $customItemsCount = CustomItem::where('base_item_id', $item->id)->count();
            $shopItemsCount = DB::table('shop_items')->where('item_id', $item->id)->count();
            
            if ($customItemsCount > 0 || $shopItemsCount > 0) {
                return back()->withError('このアイテムは使用されているため削除できません。');
            }
            
            $itemData = $item->toArray();
            $item->delete();
            
            DB::commit();
            
            $this->auditLog('items.deleted', [
                'deleted_item' => $itemData
            ], 'critical');
            
            return redirect()->route('admin.items.index')
                           ->with('success', 'アイテムが削除されました。');
            
        } catch (\Exception $e) {
            DB::rollback();
            
            $this->auditLog('items.delete.failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()->withError('アイテムの削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 一括操作
     */
    public function bulkAction(Request $request)
    {
        $this->checkPermission('items.edit');
        
        $request->validate([
            'action' => 'required|in:update_prices,toggle_availability,duplicate,delete',
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:items,id',
        ]);
        
        $action = $request->action;
        $itemIds = $request->item_ids;
        
        try {
            DB::beginTransaction();
            
            $result = match($action) {
                'update_prices' => $this->bulkUpdatePrices($itemIds, $request),
                'toggle_availability' => $this->bulkToggleAvailability($itemIds),
                'duplicate' => $this->bulkDuplicate($itemIds),
                'delete' => $this->bulkDelete($itemIds),
            };
            
            DB::commit();
            
            $this->auditLog('items.bulk_action', [
                'action' => $action,
                'item_ids' => $itemIds,
                'result' => $result
            ], 'high');
            
            return back()->with('success', $result['message']);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            $this->auditLog('items.bulk_action.failed', [
                'action' => $action,
                'item_ids' => $itemIds,
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()->withError('一括操作に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * アイテムデータのバリデーション
     */
    private function validateItemData(Request $request, ?Item $item = null)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string',
            'stack_limit' => 'nullable|integer|min:1|max:999',
            'max_durability' => 'nullable|integer|min:1|max:9999',
            'value' => 'required|integer|min:0|max:999999',
            'sell_price' => 'nullable|integer|min:0|max:999999',
            'battle_skill_id' => 'nullable|string|max:50',
            'weapon_type' => 'nullable|in:physical,magical',
        ];
        
        // 名前の重複チェック（編集時は自分以外）
        if ($item) {
            $rules['name'] .= '|unique:items,name,' . $item->id;
        } else {
            $rules['name'] .= '|unique:items,name';
        }
        
        return Validator::make($request->all(), $rules);
    }

    /**
     * エフェクトデータの処理
     */
    private function processEffectsData(Request $request): array
    {
        $effects = [];
        
        $effectFields = [
            'attack', 'defense', 'agility', 'magic_attack', 'accuracy', 'evasion',
            'heal_hp', 'heal_mp', 'heal_sp', 'inventory_slots'
        ];
        
        foreach ($effectFields as $field) {
            $value = $request->input("effect_$field");
            if (!empty($value) && is_numeric($value) && $value != 0) {
                $effects[$field] = (int) $value;
            }
        }
        
        return $effects;
    }

    /**
     * アイテム使用統計の取得
     */
    private function getItemUsageStats(int $itemId): array
    {
        // プレイヤーインベントリでの使用状況
        $inventoryCount = DB::table('players')
                           ->whereJsonContains('player_data->inventory', [['item_id' => $itemId]])
                           ->count();
        
        // ショップでの販売数（仮想データ）
        $soldCount = rand(0, 100);
        
        return [
            'in_inventory_count' => $inventoryCount,
            'sold_count' => $soldCount,
            'total_usage' => $inventoryCount + $soldCount,
        ];
    }

    /**
     * 一括価格更新
     */
    private function bulkUpdatePrices(array $itemIds, Request $request): array
    {
        $request->validate([
            'price_type' => 'required|in:multiply,add,set',
            'price_value' => 'required|numeric|min:0',
        ]);
        
        $priceType = $request->price_type;
        $priceValue = $request->price_value;
        
        $items = Item::whereIn('id', $itemIds)->get();
        $updated = 0;
        
        foreach ($items as $item) {
            $newValue = match($priceType) {
                'multiply' => $item->value * $priceValue,
                'add' => $item->value + $priceValue,
                'set' => $priceValue,
            };
            
            $item->update(['value' => (int) $newValue]);
            $updated++;
        }
        
        return ['message' => "{$updated}件のアイテム価格を更新しました。"];
    }

    /**
     * 一括複製
     */
    private function bulkDuplicate(array $itemIds): array
    {
        $items = Item::whereIn('id', $itemIds)->get();
        $duplicated = 0;
        
        foreach ($items as $item) {
            $data = $item->toArray();
            unset($data['id'], $data['created_at'], $data['updated_at']);
            $data['name'] = $data['name'] . ' (コピー)';
            
            Item::create($data);
            $duplicated++;
        }
        
        return ['message' => "{$duplicated}件のアイテムを複製しました。"];
    }

    /**
     * 一括削除
     */
    private function bulkDelete(array $itemIds): array
    {
        // 関連データの確認
        $usedItemIds = CustomItem::whereIn('base_item_id', $itemIds)
                                ->pluck('base_item_id')
                                ->unique()
                                ->toArray();
        
        if (!empty($usedItemIds)) {
            throw new \Exception('一部のアイテムは使用されているため削除できません。');
        }
        
        $deleted = Item::whereIn('id', $itemIds)->delete();
        
        return ['message' => "{$deleted}件のアイテムを削除しました。"];
    }

    // ========================================
    // 標準アイテム管理機能
    // ========================================

    /**
     * 標準アイテム一覧
     */
    public function standardItems(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.view');
        
        $standardItemService = new StandardItemService();
        $standardItems = collect($standardItemService->getStandardItems());
        
        // フィルタリング
        $filters = $request->only(['search', 'category']);
        
        if (!empty($filters['search'])) {
            $standardItems = $standardItems->filter(function($item) use ($filters) {
                return stripos($item['name'], $filters['search']) !== false ||
                       stripos($item['description'], $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['category'])) {
            $standardItems = $standardItems->filter(function($item) use ($filters) {
                return $item['category'] === $filters['category'];
            });
        }
        
        // 統計情報
        $stats = $standardItemService->getStatistics();
        
        $this->auditLog('standard_items.index.viewed', [
            'filters' => $filters,
            'items_count' => $standardItems->count()
        ]);
        
        return view('admin.items.standard', compact('standardItems', 'stats', 'filters'));
    }

    /**
     * 標準アイテム詳細表示
     */
    public function showStandardItem(string $itemId)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.view');
        
        $standardItemService = new StandardItemService();
        $item = $standardItemService->findById($itemId);
        
        if (!$item) {
            return redirect()->route('admin.items.standard')
                           ->with('error', '標準アイテムが見つかりません。');
        }
        
        $this->auditLog('standard_items.show.viewed', [
            'item_id' => $itemId,
            'item_name' => $item['name']
        ]);
        
        return view('admin.items.standard-show', compact('item'));
    }

    /**
     * 標準アイテム編集フォーム
     */
    public function editStandardItem(string $itemId)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.edit');
        
        $standardItemService = new StandardItemService();
        $item = $standardItemService->findById($itemId);
        
        if (!$item) {
            return redirect()->route('admin.items.standard')
                           ->with('error', '標準アイテムが見つかりません。');
        }
        
        $categories = ItemCategory::cases();
        $weaponTypes = [
            'physical' => '物理武器',
            'magical' => '魔法武器',
        ];
        
        return view('admin.items.standard-edit', compact('item', 'categories', 'weaponTypes'));
    }

    /**
     * 標準アイテム更新
     */
    public function updateStandardItem(Request $request, string $itemId)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.edit');
        
        // バリデーション
        $validator = $this->validateStandardItemData($request, $itemId);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            $standardItemService = new StandardItemService();
            
            // 現在のアイテムデータを取得
            $currentItem = $standardItemService->findById($itemId);
            if (!$currentItem) {
                return back()->withError('標準アイテムが見つかりません。')->withInput();
            }
            
            // 全ての標準アイテムデータを取得
            $fullData = $standardItemService->getFullData();
            
            // 更新データを作成
            $updatedItemData = [
                'id' => $itemId,
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'category_name' => ItemCategory::from($request->category)->getDisplayName(),
                'effects' => $this->processStandardItemEffects($request),
                'value' => (int) $request->value,
                'sell_price' => $request->sell_price ? (int) $request->sell_price : null,
                'stack_limit' => $request->stack_limit ? (int) $request->stack_limit : null,
                'max_durability' => $request->max_durability ? (int) $request->max_durability : null,
                'is_equippable' => (bool) $request->is_equippable,
                'is_usable' => (bool) $request->is_usable,
                'weapon_type' => $request->weapon_type ?: null,
                'is_standard' => true,
            ];
            
            // 該当アイテムを更新
            $items = $fullData['items'];
            foreach ($items as $index => $item) {
                if ($item['id'] === $itemId) {
                    $items[$index] = $updatedItemData;
                    break;
                }
            }
            
            // JSONファイルを更新
            $fullData['items'] = $items;
            $fullData['last_updated'] = now()->toDateString();
            
            $this->saveStandardItemsJson($fullData);
            
            // キャッシュクリア
            $standardItemService->clearCache();
            
            $this->auditLog('standard_items.updated', [
                'item_id' => $itemId,
                'original_data' => $currentItem,
                'updated_data' => $updatedItemData
            ], 'high');
            
            return redirect()->route('admin.items.standard.show', $itemId)
                           ->with('success', '標準アイテムが更新されました。');
            
        } catch (\Exception $e) {
            $this->auditLog('standard_items.update.failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()->withError('標準アイテムの更新に失敗しました: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * 標準アイテム作成フォーム
     */
    public function createStandardItem()
    {
        $this->initializeForRequest();
        $this->checkPermission('items.create');
        
        $categories = ItemCategory::cases();
        $weaponTypes = [
            'physical' => '物理武器',
            'magical' => '魔法武器',
        ];
        
        return view('admin.items.standard-create', compact('categories', 'weaponTypes'));
    }

    /**
     * 標準アイテム作成
     */
    public function storeStandardItem(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.create');
        
        // バリデーション
        $validator = $this->validateStandardItemData($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            $standardItemService = new StandardItemService();
            
            // 新しいIDを生成
            $fullData = $standardItemService->getFullData();
            $existingIds = collect($fullData['items'])->pluck('id')->toArray();
            $newId = $this->generateNewStandardItemId($existingIds);
            
            // 新アイテムデータを作成
            $newItemData = [
                'id' => $newId,
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'category_name' => ItemCategory::from($request->category)->getDisplayName(),
                'effects' => $this->processStandardItemEffects($request),
                'value' => (int) $request->value,
                'sell_price' => $request->sell_price ? (int) $request->sell_price : (int) floor($request->value * 0.5),
                'stack_limit' => $request->stack_limit ? (int) $request->stack_limit : null,
                'max_durability' => $request->max_durability ? (int) $request->max_durability : null,
                'is_equippable' => (bool) $request->is_equippable,
                'is_usable' => (bool) $request->is_usable,
                'weapon_type' => $request->weapon_type ?: null,
                'is_standard' => true,
            ];
            
            // アイテムを追加
            $fullData['items'][] = $newItemData;
            $fullData['last_updated'] = now()->toDateString();
            
            $this->saveStandardItemsJson($fullData);
            
            // キャッシュクリア
            $standardItemService->clearCache();
            
            $this->auditLog('standard_items.created', [
                'item_id' => $newId,
                'item_data' => $newItemData
            ], 'high');
            
            return redirect()->route('admin.items.standard.show', $newId)
                           ->with('success', '標準アイテムが作成されました。');
            
        } catch (\Exception $e) {
            $this->auditLog('standard_items.create.failed', [
                'error' => $e->getMessage(),
                'item_data' => $request->all()
            ], 'critical');
            
            return back()->withError('標準アイテムの作成に失敗しました: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * 標準アイテム削除
     */
    public function deleteStandardItem(string $itemId)
    {
        $this->checkPermission('items.delete');
        
        try {
            $standardItemService = new StandardItemService();
            
            // 現在のアイテムデータを取得
            $currentItem = $standardItemService->findById($itemId);
            if (!$currentItem) {
                return back()->withError('標準アイテムが見つかりません。');
            }
            
            // 全ての標準アイテムデータを取得
            $fullData = $standardItemService->getFullData();
            
            // 該当アイテムを削除
            $items = array_filter($fullData['items'], function($item) use ($itemId) {
                return $item['id'] !== $itemId;
            });
            
            $fullData['items'] = array_values($items); // インデックスを再構築
            $fullData['last_updated'] = now()->toDateString();
            
            $this->saveStandardItemsJson($fullData);
            
            // キャッシュクリア
            $standardItemService->clearCache();
            
            $this->auditLog('standard_items.deleted', [
                'deleted_item' => $currentItem
            ], 'critical');
            
            return redirect()->route('admin.items.standard')
                           ->with('success', '標準アイテムが削除されました。');
            
        } catch (\Exception $e) {
            $this->auditLog('standard_items.delete.failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()->withError('標準アイテムの削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * JSONファイルのバックアップ作成
     */
    public function backupStandardItems()
    {
        $this->checkPermission('items.edit');
        
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupPath = "data/backups/standard_items_backup_{$timestamp}.json";
            
            $currentJsonPath = config('items.json_file_path', 'data/standard_items.json');
            $currentContent = Storage::get($currentJsonPath);
            
            Storage::put($backupPath, $currentContent);
            
            $this->auditLog('standard_items.backup.created', [
                'backup_file' => $backupPath,
                'timestamp' => $timestamp
            ], 'medium');
            
            return back()->with('success', "バックアップファイルが作成されました: {$backupPath}");
            
        } catch (\Exception $e) {
            $this->auditLog('standard_items.backup.failed', [
                'error' => $e->getMessage()
            ], 'critical');
            
            return back()->withError('バックアップの作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 標準アイテムデータのバリデーション
     */
    private function validateStandardItemData(Request $request, ?string $itemId = null): \Illuminate\Validation\Validator
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'category' => 'required|string',
            'value' => 'required|integer|min:0|max:999999',
            'sell_price' => 'nullable|integer|min:0|max:999999',
            'stack_limit' => 'nullable|integer|min:1|max:999',
            'max_durability' => 'nullable|integer|min:1|max:9999',
            'weapon_type' => 'nullable|in:physical,magical',
            'is_equippable' => 'boolean',
            'is_usable' => 'boolean',
        ];
        
        // 名前の重複チェック（同じJSONファイル内で）
        $rules['name'] .= '|unique_standard_item_name' . ($itemId ? ':' . $itemId : '');
        
        Validator::extend('unique_standard_item_name', function ($attribute, $value, $parameters, $validator) {
            $excludeId = $parameters[0] ?? null;
            
            $standardItemService = new StandardItemService();
            $items = $standardItemService->getStandardItems();
            
            foreach ($items as $item) {
                if ($item['name'] === $value && $item['id'] !== $excludeId) {
                    return false;
                }
            }
            
            return true;
        });
        
        Validator::replacer('unique_standard_item_name', function ($message, $attribute, $rule, $parameters) {
            return 'この名前の標準アイテムは既に存在します。';
        });
        
        return Validator::make($request->all(), $rules);
    }

    /**
     * 標準アイテムのエフェクト処理
     */
    private function processStandardItemEffects(Request $request): array
    {
        $effects = [];
        
        $effectFields = [
            'attack', 'defense', 'agility', 'magic_attack', 'accuracy', 'evasion',
            'heal_hp', 'heal_mp', 'heal_sp', 'inventory_slots', 'extra_dice'
        ];
        
        foreach ($effectFields as $field) {
            $value = $request->input("effect_$field");
            if (!empty($value) && is_numeric($value) && $value != 0) {
                $effects[$field] = (int) $value;
            }
        }
        
        return $effects;
    }

    /**
     * 新しい標準アイテムIDを生成
     */
    private function generateNewStandardItemId(array $existingIds): string
    {
        $maxNumber = 0;
        foreach ($existingIds as $id) {
            if (preg_match('/std_(\d+)/', $id, $matches)) {
                $maxNumber = max($maxNumber, (int) $matches[1]);
            }
        }
        
        return 'std_' . ($maxNumber + 1);
    }

    /**
     * JSONファイルの保存
     */
    private function saveStandardItemsJson(array $data): void
    {
        $jsonPath = config('items.json_file_path', 'data/standard_items.json');
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSONエンコードエラー: ' . json_last_error_msg());
        }
        
        Storage::put($jsonPath, $jsonContent);
    }
}