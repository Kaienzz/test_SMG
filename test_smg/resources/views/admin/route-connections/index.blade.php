@extends('admin.layouts.app')

@section('title', 'ロケーション接続管理')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">ロケーション接続管理</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.route-connections.create') }}" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> 新規接続作成
            </a>
            <a href="{{ route('admin.route-connections.validate') }}" class="btn btn-outline-warning me-2">
                <i class="fas fa-check"></i> 接続検証
            </a>
            <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> ロケーション管理に戻る
            </a>
        </div>
    </div>

    @if(isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <!-- フィルター -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.route-connections.index') }}" id="filter-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="connection_type" class="form-label">接続タイプ</label>
                        <select class="form-select" id="connection_type" name="connection_type">
                            <option value="">すべて</option>
                            <option value="start" {{ ($filters['connection_type'] ?? '') === 'start' ? 'selected' : '' }}>Start</option>
                            <option value="end" {{ ($filters['connection_type'] ?? '') === 'end' ? 'selected' : '' }}>End</option>
                            <option value="bidirectional" {{ ($filters['connection_type'] ?? '') === 'bidirectional' ? 'selected' : '' }}>Bidirectional</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="source_location" class="form-label">出発ロケーション</label>
                        <select class="form-select" id="source_location" name="source_location">
                            <option value="">すべて</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" 
                                        {{ ($filters['source_location'] ?? '') === $location->id ? 'selected' : '' }}>
                                    {{ $location->name }} ({{ $location->category }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sort_by" class="form-label">並び順</label>
                        <select class="form-select" id="sort_by" name="sort_by">
                            <option value="source_location_id" {{ ($filters['sort_by'] ?? '') === 'source_location_id' ? 'selected' : '' }}>出発地</option>
                            <option value="target_location_id" {{ ($filters['sort_by'] ?? '') === 'target_location_id' ? 'selected' : '' }}>到達地</option>
                            <option value="connection_type" {{ ($filters['sort_by'] ?? '') === 'connection_type' ? 'selected' : '' }}>接続タイプ</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-primary">フィルター</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- タブ切り替え -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary active" id="list-tab-btn" data-tab="list">
                    <i class="fas fa-list"></i> リスト表示
                </button>
                <button type="button" class="btn btn-outline-primary" id="graph-tab-btn" data-tab="graph">
                    <i class="fas fa-project-diagram"></i> グラフ表示
                </button>
            </div>
            <div>
                <a href="{{ route('admin.route-connections.debug') }}" class="btn btn-sm btn-outline-warning" target="_blank">
                    <i class="fas fa-bug"></i> Debug
                </a>
                <a href="{{ route('admin.route-connections.test-graph') }}" class="btn btn-sm btn-outline-info" target="_blank">
                    <i class="fas fa-bug"></i> API Test
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- リスト表示 -->
            <div id="list-content" class="tab-content" style="display: block;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">接続一覧 ({{ count($connections) }}件)</h5>
                </div>
                
                @if(count($connections) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>出発ロケーション</th>
                                    <th>到達ロケーション</th>
                                    <th>位置情報</th>
                                    <th>タイプ・ラベル</th>
                                    <th>キーボード</th>
                                    <th>状態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($connections as $connection)
                                <tr>
                                    <td>{{ $connection['id'] }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $connection['source_name'] ?? 'Unknown' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                <code>{{ $connection['source_location_id'] }}</code>
                                                <span class="badge bg-info">{{ $connection['source_category'] ?? 'N/A' }}</span>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $connection['target_name'] ?? 'Unknown' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                <code>{{ $connection['target_location_id'] }}</code>
                                                <span class="badge bg-info">{{ $connection['target_category'] ?? 'N/A' }}</span>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            @if(!empty($connection['source_position']))
                                                <div><strong>出発:</strong> {{ $connection['source_position'] }}</div>
                                            @endif
                                            @if(!empty($connection['target_position']))
                                                <div><strong>到着:</strong> {{ $connection['target_position'] }}</div>
                                            @endif
                                            @if(empty($connection['source_position']) && empty($connection['target_position']))
                                                <span class="text-muted">位置設定なし</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $edgeType = $connection['edge_type'] ?? $connection['connection_type'] ?? '';
                                            $actionLabel = $connection['action_label'] ?? '';
                                            $actionText = $actionLabel ? \App\Helpers\ActionLabel::getActionLabelText($actionLabel) : '';
                                        @endphp
                                        
                                        @if($actionLabel)
                                            <div class="small">
                                                <span class="badge bg-primary">{{ $actionText ?: $actionLabel }}</span>
                                                @if($edgeType && $edgeType !== $actionLabel)
                                                    <br><span class="badge bg-secondary mt-1">{{ $edgeType }}</span>
                                                @endif
                                            </div>
                                        @elseif($edgeType)
                                            <span class="badge bg-secondary">{{ $edgeType }}</span>
                                        @else
                                            <span class="text-muted small">未設定</span>
                                        @endif
                                        
                                        @if(!empty($connection['direction']))
                                            <div class="mt-1">
                                                <small class="text-muted">旧: {{ $connection['direction'] }}</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($connection['keyboard_shortcut']))
                                            @php
                                                $keyDisplay = \App\Helpers\ActionLabel::getKeyboardShortcutDisplay($connection['keyboard_shortcut']);
                                            @endphp
                                            <span class="badge bg-dark">{{ $keyDisplay ?? $connection['keyboard_shortcut'] }}</span>
                                        @else
                                            <span class="text-muted small">なし</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($connection['is_enabled']))
                                            @if($connection['is_enabled'])
                                                <span class="badge bg-success">有効</span>
                                            @else
                                                <span class="badge bg-warning">無効</span>
                                            @endif
                                        @else
                                            <span class="text-muted small">未設定</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.route-connections.show', $connection['id']) }}" 
                                               class="btn btn-outline-info" title="詳細">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.route-connections.edit', $connection['id']) }}" 
                                               class="btn btn-outline-primary" title="編集">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-connection-btn" 
                                                    data-connection-id="{{ $connection['id'] }}"
                                                    data-source-name="{{ $connection['source_name'] ?? 'Unknown' }}"
                                                    data-target-name="{{ $connection['target_name'] ?? 'Unknown' }}"
                                                    title="削除">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-link fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">接続が見つかりません</h5>
                        <p class="text-muted">新しい接続を作成するか、フィルター条件を変更してください。</p>
                        <a href="{{ route('admin.route-connections.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 新規接続作成
                        </a>
                    </div>
                @endif
            </div>
            
            <!-- グラフ表示 -->
            <div id="graph-content" class="tab-content" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">接続グラフ</h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" id="refresh-graph-btn">
                            <i class="fas fa-sync-alt"></i> 更新
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="fit-graph-btn">
                            <i class="fas fa-expand-arrows-alt"></i> 全体表示
                        </button>
                    </div>
                </div>
                
                <!-- グラフコンテナ -->
                <div id="graph-container" style="height: 600px; border: 1px solid #dee2e6; border-radius: 0.375rem; position: relative;">
                    <div id="graph-loading" class="text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">読み込み中...</span>
                        </div>
                        <div class="mt-2">グラフを読み込んでいます...</div>
                    </div>
                    <div id="cytoscape-graph" style="width: 100%; height: 100%;"></div>
                </div>
                
                <!-- グラフ統計情報 -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-info" id="graph-stats" style="display: none;">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>ノード数:</strong> <span id="nodes-count">-</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>エッジ数:</strong> <span id="edges-count">-</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>カテゴリ:</strong> <span id="categories-breakdown">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 0.5rem; width: 90%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div style="padding: 1.5rem; border-bottom: 1px solid #dee2e6;">
            <h5 style="margin: 0;">削除確認</h5>
        </div>
        <div style="padding: 1.5rem;">
            <p id="delete-message">この接続を削除しますか？</p>
            <div class="alert alert-warning" id="delete-details"></div>
            <p style="color: #dc3545; font-weight: 600;">この操作は取り消せません。</p>
        </div>
        <div style="padding: 1rem 1.5rem; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 0.5rem;">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">キャンセル</button>
            <form id="delete-form" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">削除実行</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* タブコンテンツの強制スタイル */
.tab-content {
    width: 100% !important;
    min-height: 200px !important;
}

.tab-content.hidden {
    display: none !important;
    visibility: hidden !important;
}

.tab-content.visible {
    display: block !important;
    visibility: visible !important;
}

/* ボタンの状態 */
.btn.active {
    background-color: #007bff !important;
    border-color: #007bff !important;
    color: white !important;
}
</style>
@endpush

@push('scripts')
<!-- Cytoscape.js CDN -->
<script src="https://unpkg.com/cytoscape@3.26.0/dist/cytoscape.min.js"></script>

<script>
let cy = null;
let currentFilters = {};

// シンプルなタブ切り替え機能
function showTab(tabName) {
    console.log('🔄 Switching to tab:', tabName);
    
    try {
        // 要素を取得
        const listContent = document.getElementById('list-content');
        const graphContent = document.getElementById('graph-content');
        const listTabBtn = document.getElementById('list-tab-btn');
        const graphTabBtn = document.getElementById('graph-tab-btn');
        
        // 要素の存在確認
        if (!listContent || !graphContent || !listTabBtn || !graphTabBtn) {
            console.error('❌ Required elements not found:', {
                listContent: !!listContent,
                graphContent: !!graphContent,
                listTabBtn: !!listTabBtn,
                graphTabBtn: !!graphTabBtn
            });
            alert('エラー: 必要な要素が見つかりません');
            return;
        }
        
        console.log('✅ All elements found, switching tabs');
        
        // すべてのタブコンテンツを非表示（クラスベース + inline style）
        listContent.className = 'tab-content hidden';
        listContent.style.display = 'none';
        graphContent.className = 'tab-content hidden';
        graphContent.style.display = 'none';
        
        // すべてのタブボタンからactiveクラスを削除
        listTabBtn.classList.remove('active');
        graphTabBtn.classList.remove('active');
        
        // 選択されたタブを表示
        if (tabName === 'list') {
            console.log('📋 Showing list tab');
            listContent.className = 'tab-content visible';
            listContent.style.display = 'block';
            listContent.style.visibility = 'visible';
            listTabBtn.classList.add('active');
        } else if (tabName === 'graph') {
            console.log('📊 Showing graph tab');
            graphContent.className = 'tab-content visible';
            graphContent.style.display = 'block';
            graphContent.style.visibility = 'visible';
            graphTabBtn.classList.add('active');
            
            // グラフデータを読み込み
            setTimeout(() => {
                if (typeof loadGraphData === 'function') {
                    loadGraphData();
                } else {
                    console.error('loadGraphData function not found');
                }
            }, 100);
        }
        
        console.log('✅ Tab switch completed');
        
    } catch (error) {
        console.error('❌ Error in showTab:', error);
        alert('タブ切り替えエラー: ' + error.message);
    }
}

// デバッグ機能（簡易ログのみ）
window.debugLog = function(message) {
    console.log(`[DEBUG] ${message}`);
}

// 統合されたshowTab関数を使用（上記で定義済み）

// グラフデータの読み込み
window.loadGraphData = function() {
    debugLog('loadGraphData() 開始');
    
    const loadingEl = document.getElementById('graph-loading');
    const graphEl = document.getElementById('cytoscape-graph');
    const statsEl = document.getElementById('graph-stats');
    
    if (loadingEl) loadingEl.style.display = 'block';
    if (graphEl) graphEl.style.display = 'none';
    if (statsEl) statsEl.style.display = 'none';
    
    // クエリパラメータの構築
    const params = new URLSearchParams(currentFilters);
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = `{{ route('admin.route-connections.graph-data') }}?${params}`;
    
    debugLog(`Fetching: ${url}`);
    debugLog(`CSRF Token: ${token ? 'あり' : 'なし'}`);
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        debugLog(`Response status: ${response.status} ${response.statusText}`);
        if (!response.ok) {
            return response.text().then(text => {
                debugLog(`Error response body: ${text}`);
                throw new Error(`HTTP ${response.status}: ${response.statusText}. Body: ${text.substring(0, 200)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        debugLog(`データ取得成功: nodes=${data.stats?.nodes_count || 0}, edges=${data.stats?.edges_count || 0}`);
        renderGraph(data);
        updateStats(data.stats);
    })
    .catch(error => {
        debugLog(`ERROR: ${error.message}`);
        console.error('Graph load error:', error);
        if (loadingEl) {
            loadingEl.innerHTML = `
                <div class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="mt-2">グラフの読み込みに失敗しました</div>
                    <div class="mt-1"><small>${error.message}</small></div>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadGraphData()">
                        <i class="fas fa-retry"></i> 再試行
                    </button>
                </div>
            `;
        }
    });
}

// グラフの描画
window.renderGraph = function(data) {
    debugLog('renderGraph() 開始');
    
    if (!data || !data.elements) {
        debugLog('ERROR: Invalid data structure');
        return;
    }
    
    debugLog(`Data structure: nodes=${data.elements.nodes?.length || 0}, edges=${data.elements.edges?.length || 0}`);
    
    const container = document.getElementById('cytoscape-graph');
    if (!container) {
        debugLog('ERROR: cytoscape-graph container not found');
        return;
    }
    
    if (typeof cytoscape === 'undefined') {
        debugLog('ERROR: cytoscape is not defined');
        return;
    }
    
    try {
        if (cy) {
            debugLog('Destroying existing cytoscape instance');
            cy.destroy();
        }
        
        const elements = [...(data.elements.nodes || []), ...(data.elements.edges || [])];
        debugLog(`Total elements to render: ${elements.length}`);
        
        cy = cytoscape({
            container: container,
            elements: elements,
            style: [
                {
                    selector: 'node',
                    style: {
                        'background-color': '#007bff',
                        'label': 'data(label)',
                        'width': 60,
                        'height': 60,
                        'font-size': '12px',
                        'text-valign': 'center',
                        'color': '#fff',
                        'text-wrap': 'wrap',
                        'text-max-width': 80
                    }
                },
                {
                    selector: 'edge',
                    style: {
                        'width': 3,
                        'line-color': '#666',
                        'target-arrow-color': '#666',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier',
                        'label': 'data(label)',
                        'font-size': '10px',
                        'text-rotation': 'autorotate'
                    }
                },
                {
                    selector: 'edge[?is_enabled]',
                    style: {
                        'line-color': '#28a745',
                        'target-arrow-color': '#28a745'
                    }
                },
                {
                    selector: 'edge[!is_enabled]',
                    style: {
                        'line-color': '#dc3545',
                        'target-arrow-color': '#dc3545',
                        'line-style': 'dashed'
                    }
                },
                {
                    selector: 'edge[keyboard_shortcut]',
                    style: {
                        'width': 4
                    }
                }
            ],
            layout: {
                name: 'circle',
                fit: true,
                padding: 30
            }
        });
        
        debugLog('グラフ描画完了');
        const loadingEl = document.getElementById('graph-loading');
        const graphEl = document.getElementById('cytoscape-graph');
        
        if (loadingEl) loadingEl.style.display = 'none';
        if (graphEl) graphEl.style.display = 'block';
        
    } catch (error) {
        debugLog(`ERROR in renderGraph: ${error.message}`);
        console.error('renderGraph error:', error);
        
        const loadingEl = document.getElementById('graph-loading');
        if (loadingEl) {
            loadingEl.innerHTML = `
                <div class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="mt-2">グラフの描画に失敗しました</div>
                    <div class="mt-1"><small>${error.message}</small></div>
                </div>
            `;
        }
    }
}

// 統計情報の更新
window.updateStats = function(stats) {
    const nodesCount = document.getElementById('nodes-count');
    const edgesCount = document.getElementById('edges-count');
    const categoriesBreakdown = document.getElementById('categories-breakdown');
    const graphStats = document.getElementById('graph-stats');
    
    if (nodesCount) nodesCount.textContent = stats.nodes_count;
    if (edgesCount) edgesCount.textContent = stats.edges_count;
    if (categoriesBreakdown) {
        const categoriesHtml = Object.entries(stats.categories)
            .map(([category, count]) => `<span class="badge bg-info me-1">${category}: ${count}</span>`)
            .join('');
        categoriesBreakdown.innerHTML = categoriesHtml;
    }
    if (graphStats) graphStats.style.display = 'block';
}

// その他の関数
window.refreshGraph = function() {
    debugLog('refreshGraph() 呼び出し');
    loadGraphData();
}

window.fitGraph = function() {
    debugLog('fitGraph() 呼び出し');
    if (cy) cy.fit();
}

// 削除確認関数 - イベントリスナー用
function showDeleteConfirmation(connectionId, sourceName, targetName) {
    console.log(`Showing delete confirmation for: ${connectionId}, ${sourceName} -> ${targetName}`);
    
    if (!connectionId) {
        console.error('Connection ID not provided');
        alert('接続IDが見つかりません。');
        return;
    }
    
    document.getElementById('delete-details').innerHTML = `<strong>${sourceName}</strong> → <strong>${targetName}</strong>`;
    // より安全なURL生成方法
    const deleteUrl = '{!! route("admin.route-connections.destroy", ["route_connection" => "__PLACEHOLDER__"]) !!}'.replace('__PLACEHOLDER__', connectionId);
    document.getElementById('delete-form').action = deleteUrl;
    
    console.log(`Generated delete URL: ${deleteUrl}`);
    
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// 削除（上記のシンプル版を使用）

// フィルター情報の更新
window.updateFilters = function() {
    const formData = new FormData(document.getElementById('filter-form'));
    currentFilters = {};
    for (let [key, value] of formData.entries()) {
        if (value) currentFilters[key] = value;
    }
    debugLog(`フィルター更新: ${JSON.stringify(currentFilters)}`);
}

// シンプルな初期化
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing tab functionality...');
    
    try {
        // 要素の存在確認
        const listTabBtn = document.getElementById('list-tab-btn');
        const graphTabBtn = document.getElementById('graph-tab-btn');
        const listContent = document.getElementById('list-content');
        const graphContent = document.getElementById('graph-content');
        
        console.log('🔍 Element check:', {
            listTabBtn: !!listTabBtn,
            graphTabBtn: !!graphTabBtn,
            listContent: !!listContent,
            graphContent: !!graphContent
        });
        
        if (!listTabBtn || !graphTabBtn || !listContent || !graphContent) {
            console.error('❌ Critical elements missing!');
            alert('エラー: 必要な要素が見つかりません。ページをリロードしてください。');
            return;
        }
        
        // 初期状態を強制設定（リスト表示をデフォルト）
        console.log('🔧 Setting initial tab state...');
        listContent.className = 'tab-content visible';
        listContent.style.display = 'block';
        listContent.style.visibility = 'visible';
        graphContent.className = 'tab-content hidden';
        graphContent.style.display = 'none';
        graphContent.style.visibility = 'hidden';
        listTabBtn.classList.add('active');
        graphTabBtn.classList.remove('active');
        
        // タブボタンのイベント設定
        listTabBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖱️ List tab clicked');
            showTab('list');
        });
        
        graphTabBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖱️ Graph tab clicked');
            showTab('graph');
        });
        
        console.log('✅ Tab event handlers attached');
        
        // 削除ボタンの設定
        document.querySelectorAll('.delete-connection-btn').forEach(button => {
            button.addEventListener('click', function() {
                const connectionId = this.dataset.connectionId;
                const sourceName = this.dataset.sourceName;
                const targetName = this.dataset.targetName;
                showDeleteConfirmation(connectionId, sourceName, targetName);
            });
        });
        
        // その他のボタン設定
        const refreshBtn = document.getElementById('refresh-graph-btn');
        const fitBtn = document.getElementById('fit-graph-btn');
    // const toggleDebugBtn = document.getElementById('toggle-debug-btn');
        
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => loadGraphData());
        }
        if (fitBtn) {
            fitBtn.addEventListener('click', () => {
                if (cy) cy.fit();
            });
        }
    // デバッグトグルは削除
        
        // フィルター設定
        updateFilters();
        
        console.log('✅ Tab functionality initialized');
        
        // 手動テスト用関数をグローバルに設定
        window.testTab = function(tabName) {
            console.log(`🧪 Testing tab: ${tabName}`);
            showTab(tabName);
        };
        
        // 初期化完了後に1秒待ってからテスト実行可能をログ出力
        setTimeout(() => {
            console.log('💡 Manual test available: window.testTab("list") or window.testTab("graph")');
        }, 1000);
        
    } catch (error) {
        console.error('❌ Error during initialization:', error);
        alert('初期化エラー: ' + error.message);
    }
});
</script>
@endpush