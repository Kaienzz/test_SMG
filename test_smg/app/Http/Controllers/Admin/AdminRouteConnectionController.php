<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Route;
use App\Models\RouteConnection;
use App\Services\Admin\AdminRouteService;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminRouteConnectionController extends AdminController
{
    private AdminRouteService $adminRouteService;

    public function __construct(AdminAuditService $auditService, AdminRouteService $adminRouteService)
    {
        parent::__construct($auditService);
        $this->adminRouteService = $adminRouteService;
    }

    /**
     * Display a listing of route connections.
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('route-connections.index');

        $filters = $request->only(['connection_type', 'source_location', 'sort_by', 'sort_direction']);
        
        try {
            $connections = $this->adminRouteService->getConnections($filters);
            $locations = Route::active()->orderBy('name')->get();

            $this->auditLog('route_connections.index.viewed', [
                'filters' => $filters,
                'result_count' => count($connections)
            ]);

            return view('admin.route-connections.index', compact(
                'connections',
                'locations',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load connections data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.route-connections.index', [
                'error' => '接続データの読み込みに失敗しました: ' . $e->getMessage(),
                'connections' => [],
                'locations' => [],
                'filters' => $filters
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');
        $this->trackPageAccess('route-connections.create');

        try {
            $locations = Route::active()->orderBy('name')->get();
            $connectionTypes = ['start', 'end', 'bidirectional'];

            return view('admin.route-connections.create', compact('locations', 'connectionTypes'));

        } catch (\Exception $e) {
            Log::error('Failed to load create form data', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.route-connections.index')
                           ->with('error', '作成フォームの読み込みに失敗しました。');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $validated = $request->validate([
            'source_location_id' => [
                'required',
                'string',
                'exists:routes,id'
            ],
            'target_location_id' => [
                'required',
                'string',
                'exists:routes,id',
                'different:source_location_id'
            ],
            'connection_type' => 'required|in:start,end,bidirectional',
            'position' => 'nullable|integer|min:0',
            'direction' => 'nullable|string|max:255'
        ]);

        try {
            // Check for existing connection
            $existingConnection = RouteConnection::where(function($query) use ($validated) {
                $query->where('source_location_id', $validated['source_location_id'])
                      ->where('target_location_id', $validated['target_location_id']);
            })->orWhere(function($query) use ($validated) {
                $query->where('source_location_id', $validated['target_location_id'])
                      ->where('target_location_id', $validated['source_location_id']);
            })->first();

            if ($existingConnection) {
                return back()->withInput()->with('error', 'この接続は既に存在します。');
            }

            $connection = RouteConnection::create($validated);

            $this->auditLog('route_connections.created', [
                'connection_id' => $connection->id,
                'source_location' => $validated['source_location_id'],
                'target_location' => $validated['target_location_id'],
                'connection_type' => $validated['connection_type']
            ]);

            return redirect()->route('admin.route-connections.index')
                           ->with('success', 'ロケーション間の接続を作成しました。');

        } catch (\Exception $e) {
            Log::error('Failed to create route connection', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return back()->withInput()->with('error', '接続の作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('route-connections.show', ['connection_id' => $id]);

        try {
            $connection = RouteConnection::with(['sourceLocation', 'targetLocation'])->findOrFail($id);
            
            $this->auditLog('route_connections.viewed', ['connection_id' => $id]);

            return view('admin.route-connections.show', compact('connection'));

        } catch (\Exception $e) {
            Log::error('Failed to load connection details', [
                'connection_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.route-connections.index')
                           ->with('error', '接続の詳細の取得に失敗しました。');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');
        $this->trackPageAccess('route-connections.edit', ['connection_id' => $id]);

        try {
            $connection = RouteConnection::with(['sourceLocation', 'targetLocation'])->findOrFail($id);
            $locations = Route::active()->orderBy('name')->get();
            $connectionTypes = ['start', 'end', 'bidirectional'];

            return view('admin.route-connections.edit', compact('connection', 'locations', 'connectionTypes'));

        } catch (\Exception $e) {
            Log::error('Failed to load connection for editing', [
                'connection_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.route-connections.index')
                           ->with('error', '接続の編集画面の取得に失敗しました。');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $validated = $request->validate([
            'source_location_id' => [
                'required',
                'string',
                'exists:routes,id'
            ],
            'target_location_id' => [
                'required',
                'string',
                'exists:routes,id',
                'different:source_location_id'
            ],
            'connection_type' => 'required|in:start,end,bidirectional',
            'position' => 'nullable|integer|min:0',
            'direction' => 'nullable|string|max:255'
        ]);

        try {
            $connection = RouteConnection::findOrFail($id);
            
            // Check for existing connection (excluding current one)
            $existingConnection = RouteConnection::where('id', '!=', $id)
                ->where(function($query) use ($validated) {
                    $query->where('source_location_id', $validated['source_location_id'])
                          ->where('target_location_id', $validated['target_location_id']);
                })->orWhere(function($query) use ($validated) {
                    $query->where('source_location_id', $validated['target_location_id'])
                          ->where('target_location_id', $validated['source_location_id']);
                })->first();

            if ($existingConnection) {
                return back()->withInput()->with('error', 'この接続は既に存在します。');
            }

            $oldData = $connection->toArray();
            $connection->update($validated);

            $this->auditLog('route_connections.updated', [
                'connection_id' => $id,
                'old_data' => $oldData,
                'new_data' => $connection->fresh()->toArray()
            ]);

            return redirect()->route('admin.route-connections.index')
                           ->with('success', '接続を更新しました。');

        } catch (\Exception $e) {
            Log::error('Failed to update route connection', [
                'connection_id' => $id,
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return back()->withInput()->with('error', '接続の更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $connection = RouteConnection::with(['sourceLocation', 'targetLocation'])->findOrFail($id);
            $sourceName = $connection->sourceLocation?->name ?? 'Unknown';
            $targetName = $connection->targetLocation?->name ?? 'Unknown';
            
            $connection->delete();

            $this->auditLog('route_connections.deleted', [
                'connection_id' => $id,
                'source_location' => $sourceName,
                'target_location' => $targetName
            ]);

            return redirect()->route('admin.route-connections.index')
                           ->with('success', "{$sourceName} - {$targetName} 間の接続を削除しました。");

        } catch (\Exception $e) {
            Log::error('Failed to delete route connection', [
                'connection_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '接続の削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Validate all connections
     */
    public function validate(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $invalidConnections = [];
            
            // Check for invalid connections
            $connections = RouteConnection::with(['sourceLocation', 'targetLocation'])->get();
            
            foreach ($connections as $connection) {
                if (!$connection->sourceLocation || !$connection->targetLocation) {
                    $invalidConnections[] = [
                        'id' => $connection->id,
                        'source_id' => $connection->source_location_id,
                        'target_id' => $connection->target_location_id,
                        'issue' => 'Missing location reference'
                    ];
                }
            }

            $this->auditLog('route_connections.validation.performed', [
                'invalid_count' => count($invalidConnections)
            ]);

            return view('admin.route-connections.validation', compact('invalidConnections'));

        } catch (\Exception $e) {
            Log::error('Failed to validate connections', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '接続の検証に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Get graph data for visualization
     */
    public function graphData(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $filters = $request->only(['connection_type', 'source_location']);
            
            Log::info('GraphData request received', ['filters' => $filters]);
            
            // ノードデータ（ロケーション）を取得
            $locations = Route::active()->get(['id', 'name', 'category'])->toArray();
            Log::info('Locations retrieved', ['count' => count($locations)]);
            
            // エッジデータ（接続）を取得
            $connections = $this->adminRouteService->getConnections($filters);
            Log::info('Connections retrieved', ['count' => count($connections)]);
            
            // ノードをCytoscape形式に変換
            $nodes = collect($locations)->map(function($location) {
                return [
                    'data' => [
                        'id' => $location['id'],
                        'label' => $location['name'],
                        'category' => $location['category']
                    ]
                ];
            })->values()->toArray();
            
            // エッジをCytoscape形式に変換
            $edges = collect($connections)->map(function($connection, $index) {
                return [
                    'data' => [
                        'id' => 'edge-' . $connection['id'],
                        'source' => $connection['source_location_id'],
                        'target' => $connection['target_location_id'],
                        'label' => $connection['connection_type'],
                        'direction' => $connection['direction'] ?? '',
                        'connection_type' => $connection['connection_type'],
                        'connection_id' => $connection['id']
                    ]
                ];
            })->values()->toArray();
            
            $graphData = [
                'elements' => [
                    'nodes' => $nodes,
                    'edges' => $edges
                ],
                'stats' => [
                    'nodes_count' => count($nodes),
                    'edges_count' => count($edges),
                    'categories' => collect($locations)->groupBy('category')->map->count()->toArray()
                ]
            ];

            Log::info('Graph data prepared', [
                'nodes_count' => count($nodes),
                'edges_count' => count($edges)
            ]);

            $this->auditLog('route_connections.graph_data.viewed', [
                'filters' => $filters,
                'nodes_count' => count($nodes),
                'edges_count' => count($edges)
            ]);

            return response()->json($graphData);

        } catch (\Exception $e) {
            Log::error('Failed to load graph data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'グラフデータの読み込みに失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }
}
