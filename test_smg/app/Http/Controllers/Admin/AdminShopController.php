<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminAuditService;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Enums\ShopType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ショップ管理コントローラー
 * 
 * 各町のショップ施設管理機能を提供
 */
class AdminShopController extends AdminController
{
    public function __construct(AdminAuditService $auditService)
    {
        parent::__construct($auditService);
    }

    /**
     * ショップ管理ダッシュボード
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('shops.view');
        $this->trackPageAccess('shops.index');

        try {
            // 各町のショップ統計
            $shopsByLocation = Shop::selectRaw('location_id, location_type, COUNT(*) as shop_count, 
                                              COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count')
                                   ->groupBy('location_id', 'location_type')
                                   ->orderBy('location_id')
                                   ->get();

            // ショップタイプ別統計
            $shopsByType = Shop::selectRaw('shop_type, COUNT(*) as count, 
                                          COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count')
                              ->groupBy('shop_type')
                              ->orderBy('shop_type')
                              ->get();

            // 最近作成されたショップ
            $recentShops = Shop::with([])
                              ->orderBy('created_at', 'desc')
                              ->limit(10)
                              ->get();

            // 全ショップリスト（ページネーション付き）
            $shops = Shop::query()
                        ->when($request->get('location_id'), function($query, $locationId) {
                            return $query->where('location_id', $locationId);
                        })
                        ->when($request->get('shop_type'), function($query, $shopType) {
                            return $query->where('shop_type', $shopType);
                        })
                        ->when($request->get('search'), function($query, $search) {
                            return $query->where('name', 'like', "%{$search}%");
                        })
                        ->orderBy('location_id')
                        ->orderBy('shop_type')
                        ->paginate(20);

            $data = [
                'shops' => $shops,
                'shopsByLocation' => $shopsByLocation,
                'shopsByType' => $shopsByType,
                'recentShops' => $recentShops,
                'shopTypes' => ShopType::cases(),
                'filters' => [
                    'location_id' => $request->get('location_id'),
                    'shop_type' => $request->get('shop_type'),
                    'search' => $request->get('search'),
                ],
                'breadcrumb' => $this->buildBreadcrumb([
                    ['title' => 'ショップ管理', 'active' => true]
                ])
            ];

            $this->auditLog('shops.index.viewed', [
                'total_shops' => $shops->total(),
                'filters_applied' => array_filter($data['filters'])
            ]);

            return view('admin.shops.index', $data);

        } catch (\Exception $e) {
            Log::error('Admin shops index error: ' . $e->getMessage(), [
                'user_id' => $this->user?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withError('ショップデータの取得中にエラーが発生しました。');
        }
    }

    /**
     * ショップ詳細表示
     */
    public function show(Request $request, Shop $shop)
    {
        $this->initializeForRequest();
        $this->checkPermission('shops.view');
        $this->trackPageAccess('shops.show');

        try {
            // ショップアイテム取得
            $shopItems = $shop->shopItems()
                             ->with('item')
                             ->orderBy('price')
                             ->get();

            $data = [
                'shop' => $shop,
                'shopItems' => $shopItems,
                'shopType' => ShopType::from($shop->shop_type),
                'breadcrumb' => $this->buildBreadcrumb([
                    ['title' => 'ショップ管理', 'url' => route('admin.shops.index'), 'active' => false],
                    ['title' => $shop->name, 'active' => true]
                ])
            ];

            $this->auditLog('shops.show.viewed', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'location' => $shop->location_id
            ]);

            return view('admin.shops.show', $data);

        } catch (\Exception $e) {
            Log::error('Admin shop show error: ' . $e->getMessage(), [
                'shop_id' => $shop->id,
                'user_id' => $this->user?->id
            ]);

            return back()->withError('ショップ詳細の取得中にエラーが発生しました。');
        }
    }

    /**
     * ショップ作成フォーム表示
     */
    public function create(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('shops.create');

        $data = [
            'shopTypes' => ShopType::cases(),
            'locations' => $this->getAvailableLocations(),
            'breadcrumb' => $this->buildBreadcrumb([
                ['title' => 'ショップ管理', 'url' => route('admin.shops.index'), 'active' => false],
                ['title' => '新規ショップ作成', 'active' => true]
            ])
        ];

        return view('admin.shops.create', $data);
    }

    /**
     * ショップ作成処理
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('shops.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shop_type' => 'required|string|in:' . implode(',', array_column(ShopType::cases(), 'value')),
            'location_id' => 'required|string|max:100',
            'location_type' => 'required|string|in:town,road,dungeon',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'shop_config' => 'nullable|array'
        ]);

        try {
            $shop = Shop::create($validated);

            $this->auditLog('shops.created', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'location' => $shop->location_id,
                'shop_type' => $shop->shop_type
            ], 'medium');

            return redirect()
                ->route('admin.shops.show', $shop)
                ->withSuccess('ショップが正常に作成されました。');

        } catch (\Exception $e) {
            Log::error('Admin shop creation error: ' . $e->getMessage(), [
                'data' => $validated,
                'user_id' => $this->user?->id
            ]);

            return back()
                ->withInput()
                ->withError('ショップの作成中にエラーが発生しました。');
        }
    }

    /**
     * 利用可能なロケーション一覧取得
     */
    private function getAvailableLocations(): array
    {
        // 既存のロケーションデータから取得
        // 実際の実装では Location モデルから取得
        return [
            ['id' => 'town_prima', 'name' => 'プリマ町', 'type' => 'town'],
            ['id' => 'town_a', 'name' => 'A町', 'type' => 'town'],
            ['id' => 'town_b', 'name' => 'B町', 'type' => 'town'],
        ];
    }
}