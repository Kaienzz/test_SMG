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
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="connection-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-view" 
                            type="button" role="tab" aria-controls="list-view" aria-selected="true">
                        <i class="fas fa-list"></i> リスト表示
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="graph-tab" data-bs-toggle="tab" data-bs-target="#graph-view" 
                            type="button" role="tab" aria-controls="graph-view" aria-selected="false">
                        <i class="fas fa-project-diagram"></i> グラフ表示
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="connection-tab-content">
                <!-- リスト表示タブ -->
                <div class="tab-pane fade show active" id="list-view" role="tabpanel" aria-labelledby="list-tab">
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
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal{{ $connection['id'] }}"
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
                
                <!-- グラフ表示タブ -->
                <div class="tab-pane fade" id="graph-view" role="tabpanel" aria-labelledby="graph-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">接続グラフ</h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" id="refresh-graph">
                                <i class="fas fa-sync-alt"></i> 更新
                            </button>
                            <div class="btn-group btn-group-sm" role="group">
                                <button id="layout-dropdown" type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-sitemap"></i> レイアウト
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item layout-option" href="#" data-layout="cose">標準レイアウト</a></li>
                                    <li><a class="dropdown-item layout-option" href="#" data-layout="circle">円形レイアウト</a></li>
                                    <li><a class="dropdown-item layout-option" href="#" data-layout="grid">グリッドレイアウト</a></li>
                                    <li><a class="dropdown-item layout-option" href="#" data-layout="breadthfirst">階層レイアウト</a></li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-outline-secondary" id="fit-graph">
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
                    
                    <!-- 操作説明 -->
                    <div class="mt-3">
                        <div class="alert alert-light">
                            <h6><i class="fas fa-info-circle"></i> 操作方法</h6>
                            <ul class="mb-0 small">
                                <li><strong>ズーム:</strong> マウスホイール</li>
                                <li><strong>パン:</strong> ドラッグ</li>
                                <li><strong>ノード移動:</strong> ノードをドラッグ</li>
                                <li><strong>詳細表示:</strong> ノードまたはエッジをクリック</li>
                                <li><strong>編集:</strong> エッジをダブルクリック</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(count($connections) > 0)
    @foreach($connections as $connection)
    <!-- 削除確認モーダル -->
    <div class="modal fade" id="deleteModal{{ $connection['id'] }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">削除確認</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>この接続を削除しますか？</p>
                    <div class="alert alert-warning">
                        <strong>{{ $connection['source_name'] ?? 'Unknown' }}</strong> → 
                        <strong>{{ $connection['target_name'] ?? 'Unknown' }}</strong>
                    </div>
                    <p class="text-danger"><strong>この操作は取り消せません。</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <form action="{{ route('admin.route-connections.destroy', $connection['id']) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">削除実行</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endif

<!-- ノード/エッジ詳細モーダル -->
<div class="modal fade" id="elementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="elementModalTitle">詳細情報</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="elementModalBody">
                <!-- 動的に生成される内容 -->
            </div>
            <div class="modal-footer" id="elementModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Cytoscape.js CDN -->
<script src="https://unpkg.com/cytoscape@3.26.0/dist/cytoscape.min.js"></script>
<script src="https://unpkg.com/cytoscape-cose-bilkent@4.1.0/cytoscape-cose-bilkent.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cy = null;
    let currentFilters = {};
    
    // タブ切り替え時の処理
    document.getElementById('graph-tab').addEventListener('shown.bs.tab', function (e) {
        loadGraphData();
    });
    
    // フィルター変更時の処理
    document.getElementById('filter-form').addEventListener('change', function() {
        updateFilters();
        if (document.getElementById('graph-tab').classList.contains('active')) {
            loadGraphData();
        }
    });
    
    // フィルター情報の更新
    function updateFilters() {
        const formData = new FormData(document.getElementById('filter-form'));
        currentFilters = {};
        for (let [key, value] of formData.entries()) {
            if (value) currentFilters[key] = value;
        }
    }
    
    // グラフデータの読み込み
    function loadGraphData() {
        console.log('Loading graph data...');
        
        const loadingEl = document.getElementById('graph-loading');
        const graphEl = document.getElementById('cytoscape-graph');
        const statsEl = document.getElementById('graph-stats');
        
        loadingEl.style.display = 'block';
        graphEl.style.display = 'none';
        statsEl.style.display = 'none';
        
        // クエリパラメータの構築
        const params = new URLSearchParams(currentFilters);
        console.log('Filters:', currentFilters);
        console.log('Params:', params.toString());
        
        // CSRFトークンの取得
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('CSRF Token:', token ? 'Found' : 'Not found');
        
        const url = `{{ route('admin.route-connections.graph-data') }}?${params}`;
        console.log('Fetching URL:', url);
        
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
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Response text:', text);
                        throw new Error(`HTTP error! status: ${response.status}, message: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.error) {
                    throw new Error(data.error);
                }
                renderGraph(data);
                updateStats(data.stats);
            })
            .catch(error => {
                console.error('Error loading graph data:', error);
                loadingEl.innerHTML = `
                    <div class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="mt-2">グラフの読み込みに失敗しました: ${error.message}</div>
                        <small class="text-muted mt-1 d-block">詳細はブラウザコンソールを確認してください。</small>
                    </div>
                `;
            });
    }
    
    // グラフの描画
    function renderGraph(data) {
        const container = document.getElementById('cytoscape-graph');
        
        // 既存のインスタンスがあれば破棄
        if (cy) {
            cy.destroy();
        }
        
        cy = cytoscape({
            container: container,
            elements: [...data.elements.nodes, ...data.elements.edges],
            
            style: [
                // ノードのスタイル
                {
                    selector: 'node',
                    style: {
                        'background-color': 'data(category)',
                        'label': 'data(label)',
                        'width': 60,
                        'height': 60,
                        'font-size': '12px',
                        'text-valign': 'bottom',
                        'text-margin-y': 5,
                        'color': '#333',
                        'text-wrap': 'wrap',
                        'text-max-width': 80,
                        'border-width': 2,
                        'border-color': '#fff'
                    }
                },
                // カテゴリ別ノード色
                {
                    selector: 'node[category = "town"]',
                    style: {
                        'background-color': '#28a745'
                    }
                },
                {
                    selector: 'node[category = "road"]',
                    style: {
                        'background-color': '#ffc107'
                    }
                },
                {
                    selector: 'node[category = "dungeon"]',
                    style: {
                        'background-color': '#dc3545'
                    }
                },
                // エッジのスタイル
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
                // 接続タイプ別エッジ色
                {
                    selector: 'edge[connection_type = "start"]',
                    style: {
                        'line-color': '#007bff'
                    }
                },
                {
                    selector: 'edge[connection_type = "end"]',
                    style: {
                        'line-color': '#dc3545'
                    }
                },
                {
                    selector: 'edge[connection_type = "bidirectional"]',
                    style: {
                        'line-color': '#28a745'
                    }
                },
                // 選択時のスタイル
                {
                    selector: ':selected',
                    style: {
                        'border-width': 4,
                        'border-color': '#fd7e14'
                    }
                }
            ],
            
            layout: {
                name: 'cose',
                idealEdgeLength: 100,
                nodeOverlap: 20,
                refresh: 20,
                fit: true,
                padding: 30,
                randomize: false,
                componentSpacing: 100,
                nodeRepulsion: 400000,
                edgeElasticity: 100,
                nestingFactor: 5,
                gravity: 80,
                numIter: 1000,
                initialTemp: 200,
                coolingFactor: 0.95,
                minTemp: 1.0
            },
            
            // インタラクション設定
            wheelSensitivity: 0.1,
            minZoom: 0.1,
            maxZoom: 3
        });
        
        // イベントリスナーの設定
        setupGraphEvents();
        
        // 表示切り替え
        document.getElementById('graph-loading').style.display = 'none';
        document.getElementById('cytoscape-graph').style.display = 'block';
        document.getElementById('graph-stats').style.display = 'block';
    }
    
    // グラフイベントの設定
    function setupGraphEvents() {
        // ノードクリック
        cy.on('tap', 'node', function(evt) {
            const node = evt.target;
            showElementModal(node, 'node');
        });
        
        // エッジクリック
        cy.on('tap', 'edge', function(evt) {
            const edge = evt.target;
            showElementModal(edge, 'edge');
        });
        
        // エッジダブルクリック（編集）
        cy.on('dbltap', 'edge', function(evt) {
            const edge = evt.target;
            const connectionId = edge.data('connection_id');
            window.open(`{{ route('admin.route-connections.edit', ':id') }}`.replace(':id', connectionId), '_blank');
        });
    }
    
    // 要素詳細モーダルの表示
    function showElementModal(element, type) {
        const modal = new bootstrap.Modal(document.getElementById('elementModal'));
        const title = document.getElementById('elementModalTitle');
        const body = document.getElementById('elementModalBody');
        const footer = document.getElementById('elementModalFooter');
        
        if (type === 'node') {
            const data = element.data();
            title.textContent = `ロケーション: ${data.label}`;
            body.innerHTML = `
                <dl class="row">
                    <dt class="col-sm-3">ID:</dt>
                    <dd class="col-sm-9"><code>${data.id}</code></dd>
                    <dt class="col-sm-3">名前:</dt>
                    <dd class="col-sm-9">${data.label}</dd>
                    <dt class="col-sm-3">カテゴリ:</dt>
                    <dd class="col-sm-9"><span class="badge bg-info">${data.category}</span></dd>
                </dl>
            `;
            footer.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <a href="{{ route('admin.locations.show', ':id') }}".replace(':id', '${data.id}') class="btn btn-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i> 詳細表示
                </a>
            `;
        } else if (type === 'edge') {
            const data = element.data();
            title.textContent = `接続: ${data.label}`;
            body.innerHTML = `
                <dl class="row">
                    <dt class="col-sm-3">接続ID:</dt>
                    <dd class="col-sm-9">${data.connection_id}</dd>
                    <dt class="col-sm-3">出発地:</dt>
                    <dd class="col-sm-9"><code>${data.source}</code></dd>
                    <dt class="col-sm-3">到達地:</dt>
                    <dd class="col-sm-9"><code>${data.target}</code></dd>
                    <dt class="col-sm-3">接続タイプ:</dt>
                    <dd class="col-sm-9"><span class="badge bg-primary">${data.connection_type}</span></dd>
                    <dt class="col-sm-3">方向:</dt>
                    <dd class="col-sm-9">${data.direction || 'N/A'}</dd>
                </dl>
            `;
            footer.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <a href="{{ route('admin.route-connections.edit', ':id') }}".replace(':id', '${data.connection_id}') class="btn btn-primary" target="_blank">
                    <i class="fas fa-edit"></i> 編集
                </a>
            `;
        }
        
        modal.show();
    }
    
    // 統計情報の更新
    function updateStats(stats) {
        document.getElementById('nodes-count').textContent = stats.nodes_count;
        document.getElementById('edges-count').textContent = stats.edges_count;
        
        const categoriesHtml = Object.entries(stats.categories)
            .map(([category, count]) => `<span class="badge bg-info me-1">${category}: ${count}</span>`)
            .join('');
        document.getElementById('categories-breakdown').innerHTML = categoriesHtml;
    }
    
    // ボタンイベント
    document.getElementById('refresh-graph').addEventListener('click', loadGraphData);
    
    document.getElementById('fit-graph').addEventListener('click', function() {
        if (cy) cy.fit();
    });
    
    // レイアウト変更
    document.querySelectorAll('.layout-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const layoutName = this.dataset.layout;
            if (cy) {
                const layout = cy.layout({
                    name: layoutName,
                    fit: true,
                    padding: 30
                });
                layout.run();
            }
        });
    });
    
    // 初期フィルター設定
    updateFilters();
});
</script>
@endsection