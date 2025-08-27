<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Route;
use App\Models\RouteConnection;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminRoadController extends AdminController
{
    public function __construct(AdminAuditService $auditService)
    {
        parent::__construct($auditService);
    }

    /**
     * Road一覧表示
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('roads.index');

        try {
            $roads = Route::roads()
                                ->active()
                                ->orderBy('name')
                                ->paginate(20);

            $this->auditLog('roads.index.viewed', [
                'result_count' => $roads->count()
            ]);

            $canManageGameData = $this->hasPermission('locations.edit') || $this->hasPermission('locations.create');

            return view('admin.roads.index', compact('roads', 'canManageGameData'));

        } catch (\Exception $e) {
            Log::error('Failed to load roads data', [
                'error' => $e->getMessage()
            ]);
            
            $canManageGameData = $this->hasPermission('locations.edit') || $this->hasPermission('locations.create');
            
            return view('admin.roads.index', [
                'error' => 'Road データの読み込みに失敗しました: ' . $e->getMessage(),
                'roads' => collect(),
                'canManageGameData' => $canManageGameData
            ]);
        }
    }

    /**
     * Road詳細表示
     */
    public function show(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $road = Route::roads()->where('id', $id)->first();

            if (!$road) {
                return redirect()->route('admin.roads.index')
                               ->with('error', 'Road が見つかりませんでした。');
            }

            $this->auditLog('roads.show.viewed', [
                'road_id' => $id,
                'road_name' => $road->name
            ]);

            $canManageGameData = $this->hasPermission('locations.edit') || $this->hasPermission('locations.delete');

            return view('admin.roads.show', compact('road', 'canManageGameData'));

        } catch (\Exception $e) {
            Log::error('Failed to load road detail', [
                'road_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.roads.index')
                           ->with('error', 'Road 詳細の読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Road作成フォーム
     */
    public function create(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $canManageGameData = $this->hasPermission('locations.edit') || $this->hasPermission('locations.create');
        
        return view('admin.roads.create', compact('canManageGameData'));
    }

    /**
     * Road作成処理
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        Log::info('Road creation attempt started', [
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);

        try {
            $validated = $request->validate([
                'id' => 'required|string|unique:routes,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'length' => 'required|integer|min:1|max:1000',
                'difficulty' => 'required|in:easy,normal,hard',
                'encounter_rate' => 'nullable|numeric|between:0,1'
                // 接続関連のバリデーションは新規作成時には不要
            ]);

            Log::info('Road creation validation passed', [
                'validated_data' => $validated
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Road creation validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            throw $e;
        }

        try {
            DB::beginTransaction();
            
            $road = Route::create(array_merge($validated, [
                'category' => 'road',
                'dungeon_id' => null,
                'is_active' => true,
            ]));

            // 新規作成時は接続処理をスキップ（編集画面で後から設定可能）
            Log::info('Road created successfully, connections can be added later via edit', [
                'road_id' => $road->id
            ]);

            DB::commit();

            $this->auditLog('roads.created', [
                'road_id' => $road->id,
                'road_name' => $road->name,
                'connections_count' => 0  // 新規作成時は接続なし
            ]);

            return redirect()->route('admin.roads.show', $road->id)
                           ->with('success', 'Road が正常に作成されました。');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create road', [
                'data' => $validated,
                'error' => $e->getMessage()
            ]);
            
            return back()->withInput()
                        ->with('error', 'Road の作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Road編集フォーム
     */
    public function edit(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $road = Route::roads()->where('id', $id)->first();

            if (!$road) {
                return redirect()->route('admin.roads.index')
                               ->with('error', 'Road が見つかりませんでした。');
            }

            $canManageGameData = $this->hasPermission('locations.edit') || $this->hasPermission('locations.delete');

            return view('admin.roads.edit', compact('road', 'canManageGameData'));

        } catch (\Exception $e) {
            Log::error('Failed to load road for edit', [
                'road_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.roads.index')
                           ->with('error', 'Road の編集画面読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Road更新処理
     */
    public function update(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        Log::info('Road update attempt started', [
            'user_id' => auth()->id(),
            'road_id' => $id,
            'request_data' => $request->all()
        ]);

        try {
            $road = Route::roads()->where('id', $id)->first();

            if (!$road) {
                Log::warning('Road not found for update', ['road_id' => $id]);
                return redirect()->route('admin.roads.index')
                               ->with('error', 'Road が見つかりませんでした。');
            }

            try {
                // 基本情報のバリデーション
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'length' => 'required|integer|min:1|max:1000',
                    'difficulty' => 'required|in:easy,normal,hard',
                    'encounter_rate' => 'nullable|numeric|between:0,1',
                    'is_active' => 'sometimes|in:0,1',  // checkboxで送信される値に対応
                ]);

                // is_activeをbooleanに変換
                $validated['is_active'] = isset($validated['is_active']) ? (bool) $validated['is_active'] : false;

                // 接続データ（新規追加分）の取り込みと検証（任意）
                $connectionsInput = $request->input('connections', []);
                $newConnections = [];
                if (is_array($connectionsInput) && count($connectionsInput) > 0) {
                    // 空行を除外
                    $filtered = array_values(array_filter($connectionsInput, function ($row) use ($id) {
                        return is_array($row)
                            && !empty($row['target_location_id'])
                            && $row['target_location_id'] !== $id
                            && !empty($row['connection_type']);
                    }));

                    if (count($filtered) > 0) {
                        $validator = Validator::make(
                            ['connections' => $filtered],
                            [
                                'connections' => 'array',
                                'connections.*.target_location_id' => [
                                    'required',
                                    'string',
                                    'different:source_location_id',
                                    Rule::exists('routes', 'id'),
                                ],
                                'connections.*.connection_type' => 'required|in:start,end,bidirectional',
                                'connections.*.position' => 'nullable|integer|min:0',
                                'connections.*.direction' => 'nullable|string|max:50',
                            ],
                            [
                                'connections.*.target_location_id.different' => '接続先は自身と異なる場所を指定してください。',
                            ]
                        );

                        if ($validator->fails()) {
                            throw new \Illuminate\Validation\ValidationException($validator);
                        }

                        $newConnections = $validator->validated()['connections'];
                    }
                }

                Log::info('Road update validation passed', [
                    'road_id' => $id,
                    'validated_data' => $validated,
                    'new_connections_count' => count($newConnections)
                ]);

            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Road update validation failed', [
                    'road_id' => $id,
                    'errors' => $e->errors(),
                    'request_data' => $request->all()
                ]);
                throw $e;
            }

            DB::beginTransaction();
            
            $road->update($validated);

            // 新規接続データがあれば作成（既存は別画面で編集・削除）
            if (!empty($newConnections)) {
                $this->createConnections($road->id, $newConnections);
            }

            Log::info('Road basic data updated successfully', [
                'road_id' => $road->id,
                'road_name' => $road->name,
                'changes' => $road->getChanges(),
                'added_connections' => !empty($newConnections) ? count($newConnections) : 0,
            ]);

            DB::commit();

            $this->auditLog('roads.updated', [
                'road_id' => $road->id,
                'road_name' => $road->name,
                'changes' => $road->getChanges(),
                'added_connections' => !empty($newConnections) ? count($newConnections) : 0,
            ]);

            return redirect()->route('admin.roads.show', $road->id)
                           ->with('success', 'Road が正常に更新されました。');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update road', [
                'road_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withInput()
                        ->with('error', 'Road の更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Road削除処理
     */
    public function destroy(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.delete');

        try {
            $road = Route::roads()->where('id', $id)->first();

            if (!$road) {
                return redirect()->route('admin.roads.index')
                               ->with('error', 'Road が見つかりませんでした。');
            }

            $roadName = $road->name;
            $road->delete();

            $this->auditLog('roads.deleted', [
                'road_id' => $id,
                'road_name' => $roadName
            ]);

            return redirect()->route('admin.roads.index')
                           ->with('success', "Road '{$roadName}' が正常に削除されました。");

        } catch (\Exception $e) {
            Log::error('Failed to delete road', [
                'road_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.roads.index')
                           ->with('error', 'Road の削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 接続データを作成
     */
    private function createConnections(string $sourceLocationId, array $connections)
    {
        foreach ($connections as $connectionData) {
            // 重複チェック
            $existingConnection = RouteConnection::where(function($query) use ($sourceLocationId, $connectionData) {
                $query->where('source_location_id', $sourceLocationId)
                      ->where('target_location_id', $connectionData['target_location_id']);
            })->orWhere(function($query) use ($sourceLocationId, $connectionData) {
                $query->where('source_location_id', $connectionData['target_location_id'])
                      ->where('target_location_id', $sourceLocationId);
            })->first();

            if (!$existingConnection) {
                RouteConnection::create([
                    'source_location_id' => $sourceLocationId,
                    'target_location_id' => $connectionData['target_location_id'],
                    'connection_type' => $connectionData['connection_type'],
                    'position' => $connectionData['position'] ?? null,
                    'direction' => $connectionData['direction'] ?? null,
                ]);
            } else {
                // 既存があり、今回が双方向指定ならアップグレード
                if (($connectionData['connection_type'] ?? null) === 'bidirectional' && $existingConnection->connection_type !== 'bidirectional') {
                    $existingConnection->connection_type = 'bidirectional';
                    if (array_key_exists('position', $connectionData) && $connectionData['position'] !== null) {
                        $existingConnection->position = $connectionData['position'];
                    }
                    if (array_key_exists('direction', $connectionData) && $connectionData['direction'] !== null) {
                        $existingConnection->direction = $connectionData['direction'];
                    }
                    $existingConnection->save();
                }
            }
        }
    }
}
