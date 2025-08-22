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

    <!-- デバッグ情報 -->
    <div class="alert alert-info" id="debug-info" style="display: none;">
        <h6>デバッグ情報</h6>
        <div id="debug-log"></div>
    </div>

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
                <button type="button" class="btn btn-outline-primary active" id="list-tab-btn" onclick="debugShowTab('list')">
                    <i class="fas fa-list"></i> リスト表示
                </button>
                <button type="button" class="btn btn-outline-primary" id="graph-tab-btn" onclick="debugShowTab('graph')">
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
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleDebug()">
                    <i class="fas fa-eye"></i> デバッグ表示
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <!-- リスト表示 -->
            <div id="list-content" class="tab-content">
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
                                    <th>接続タイプ</th>
                                    <th>方向</th>
                                    <th>位置</th>
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
                                        <span class="badge bg-primary">{{ $connection['connection_type'] }}</span>
                                    </td>
                                    <td>{{ $connection['direction'] ?? 'N/A' }}</td>
                                    <td>{{ $connection['position'] ?? 'N/A' }}</td>
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
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="confirmDelete('{{ $connection['id'] }}', '{{ $connection['source_name'] ?? 'Unknown' }}', '{{ $connection['target_name'] ?? 'Unknown' }}')"
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
                        <button type="button" class="btn btn-outline-secondary" onclick="refreshGraph()">
                            <i class="fas fa-sync-alt"></i> 更新
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="fitGraph()">
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

@section('scripts')
<!-- Cytoscape.js CDN -->
<script src="https://unpkg.com/cytoscape@3.26.0/dist/cytoscape.min.js"></script>

<script>
let cy = null;
let currentFilters = {};

// デバッグ機能
window.debugLog = function(message) {
    const debugInfo = document.getElementById('debug-info');
    const debugLog = document.getElementById('debug-log');
    if (debugInfo && debugLog) {
        debugInfo.style.display = 'block';
        const timestamp = new Date().toLocaleTimeString();
        debugLog.innerHTML += `<div class="small">[${timestamp}] ${message}</div>`;
    }
    console.log(`[DEBUG] ${message}`);
}

window.toggleDebug = function() {
    const debugInfo = document.getElementById('debug-info');
    if (debugInfo) {
        debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
    }
}

// タブ切り替え（デバッグ版）
window.debugShowTab = function(tabName) {
    debugLog(`debugShowTab('${tabName}') が呼ばれました`);
    
    try {
        const listContent = document.getElementById('list-content');
        const graphContent = document.getElementById('graph-content');
        const listTabBtn = document.getElementById('list-tab-btn');
        const graphTabBtn = document.getElementById('graph-tab-btn');
        
        debugLog(`要素取得: list=${listContent ? 'OK' : 'NG'}, graph=${graphContent ? 'OK' : 'NG'}, listBtn=${listTabBtn ? 'OK' : 'NG'}, graphBtn=${graphTabBtn ? 'OK' : 'NG'}`);
        
        if (!listContent || !graphContent || !listTabBtn || !graphTabBtn) {
            debugLog('ERROR: 必要な要素が見つかりません');
            alert('エラー: 必要な要素が見つかりません');
            return;
        }
        
        // すべてのコンテンツを隠す
        listContent.style.display = 'none';
        graphContent.style.display = 'none';
        
        // すべてのボタンからactiveクラスを削除
        listTabBtn.classList.remove('active');
        graphTabBtn.classList.remove('active');
        
        // 選択されたタブを表示
        if (tabName === 'list') {
            listContent.style.display = 'block';
            listTabBtn.classList.add('active');
            debugLog('リストタブに切り替えました');
        } else if (tabName === 'graph') {
            graphContent.style.display = 'block';
            graphTabBtn.classList.add('active');
            debugLog('グラフタブに切り替えました - データ読み込み開始');
            loadGraphData();
        }
        
    } catch (error) {
        debugLog(`ERROR in debugShowTab: ${error.message}`);
        console.error('debugShowTab error:', error);
        alert(`エラーが発生しました: ${error.message}`);
    }
}

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
        debugLog(`Response status: ${response.status}`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
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
        if (loadingEl) {
            loadingEl.innerHTML = `
                <div class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="mt-2">グラフの読み込みに失敗しました: ${error.message}</div>
                </div>
            `;
        }
    });
}

// グラフの描画
window.renderGraph = function(data) {
    debugLog('renderGraph() 開始');
    
    const container = document.getElementById('cytoscape-graph');
    if (!container) {
        debugLog('ERROR: cytoscape-graph container not found');
        return;
    }
    
    try {
        if (cy) {
            cy.destroy();
        }
        
        cy = cytoscape({
            container: container,
            elements: [...data.elements.nodes, ...data.elements.edges],
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
                        'curve-style': 'bezier'
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
        document.getElementById('graph-loading').style.display = 'none';
        document.getElementById('cytoscape-graph').style.display = 'block';
        
    } catch (error) {
        debugLog(`ERROR in renderGraph: ${error.message}`);
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

window.confirmDelete = function(connectionId, sourceName, targetName) {
    debugLog(`confirmDelete(${connectionId}, ${sourceName}, ${targetName})`);
    document.getElementById('delete-details').innerHTML = `<strong>${sourceName}</strong> → <strong>${targetName}</strong>`;
    document.getElementById('delete-form').action = `{{ route('admin.route-connections.destroy', ':id') }}`.replace(':id', connectionId);
    document.getElementById('deleteModal').style.display = 'block';
}

window.closeDeleteModal = function() {
    document.getElementById('deleteModal').style.display = 'none';
}

// フィルター情報の更新
window.updateFilters = function() {
    const formData = new FormData(document.getElementById('filter-form'));
    currentFilters = {};
    for (let [key, value] of formData.entries()) {
        if (value) currentFilters[key] = value;
    }
    debugLog(`フィルター更新: ${JSON.stringify(currentFilters)}`);
}

// 初期化
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOMContentLoaded - 初期化開始');
    
    // フィルター変更時の処理
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('change', function() {
            updateFilters();
            if (document.getElementById('graph-tab-btn').classList.contains('active')) {
                loadGraphData();
            }
        });
    }
    
    // 初期フィルター設定
    updateFilters();
    
    debugLog('初期化完了');
});
</script>
@endsection