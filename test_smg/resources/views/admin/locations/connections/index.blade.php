@extends('admin.layouts.app')

@section('title', '接続関係管理')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">接続関係管理</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">ロケーション管理</a></li>
                    <li class="breadcrumb-item active">接続関係管理</li>
                </ol>
            </nav>
            <p class="mb-0 text-muted">ロケーション間の接続状況を確認・管理</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-info" onclick="toggleViewMode()">
                <i class="fas fa-exchange-alt"></i> 表示切替
            </button>
            @if($canManageGameData ?? false)
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#validateModal">
                <i class="fas fa-check-circle"></i> 接続検証
            </button>
            @endif
        </div>
    </div>

    <!-- 表示モード切替タブ -->
    <ul class="nav nav-tabs mb-4" id="connectionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="visual-tab" data-bs-toggle="tab" data-bs-target="#visual" 
                    type="button" role="tab">
                <i class="fas fa-project-diagram"></i> ビジュアル表示
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="table-tab" data-bs-toggle="tab" data-bs-target="#table" 
                    type="button" role="tab">
                <i class="fas fa-table"></i> テーブル表示
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="matrix-tab" data-bs-toggle="tab" data-bs-target="#matrix" 
                    type="button" role="tab">
                <i class="fas fa-th"></i> マトリックス表示
            </button>
        </li>
    </ul>

    <div class="tab-content" id="connectionTabContent">
        <!-- ビジュアル表示 -->
        <div class="tab-pane fade show active" id="visual" role="tabpanel">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">接続図</h6>
                </div>
                <div class="card-body">
                    <div id="connectionGraph" style="height: 600px; border: 1px solid #e3e6f0; border-radius: 0.35rem;">
                        <!-- D3.js またはCytoscape.js でグラフを描画 -->
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-project-diagram fa-3x text-gray-300 mb-3"></i>
                                <h6 class="text-muted">接続図を読み込み中...</h6>
                                <small class="text-muted">JavaScriptが有効になっていることを確認してください</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- テーブル表示 -->
        <div class="tab-pane fade" id="table" role="tabpanel">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">接続一覧</h6>
                </div>
                <div class="card-body">
                    @if(isset($connections) && count($connections) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered" id="connectionsTable">
                            <thead>
                                <tr>
                                    <th>ロケーション</th>
                                    <th>タイプ</th>
                                    <th>接続先</th>
                                    <th>接続数</th>
                                    <th>詳細</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($connections as $locationId => $locationData)
                                <tr>
                                    <td class="font-weight-bold">
                                        {{ $locationData['name'] }}
                                        <br><small class="text-muted"><code>{{ $locationId }}</code></small>
                                    </td>
                                    <td>
                                        @php
                                            $typeClass = match($locationData['type']) {
                                                'road' => 'primary',
                                                'town' => 'success',
                                                'dungeon' => 'info',
                                                default => 'secondary'
                                            };
                                            $typeText = match($locationData['type']) {
                                                'road' => '道路',
                                                'town' => '町',
                                                'dungeon' => 'ダンジョン',
                                                default => $locationData['type']
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $typeClass }}">{{ $typeText }}</span>
                                    </td>
                                    <td>
                                        @if(isset($locationData['connections']) && count($locationData['connections']) > 0)
                                            @foreach($locationData['connections'] as $direction => $connection)
                                                <div class="mb-1">
                                                    <small class="text-muted">{{ $direction }}:</small>
                                                    <strong>{{ $connection['name'] ?? $connection['id'] }}</strong>
                                                    <span class="badge bg-light text-dark">{{ $connection['type'] }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">接続なし</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ count($locationData['connections'] ?? []) }}</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-info btn-sm" 
                                                onclick="showConnectionDetails('{{ $locationId }}')">
                                            <i class="fas fa-eye"></i> 詳細
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-unlink fa-3x text-gray-300 mb-3"></i>
                        <h6 class="text-muted">接続情報がありません</h6>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- マトリックス表示 -->
        <div class="tab-pane fade" id="matrix" role="tabpanel">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">接続マトリックス</h6>
                </div>
                <div class="card-body">
                    <div id="connectionMatrix">
                        <!-- JavaScript で動的に生成 -->
                        <div class="text-center py-5">
                            <i class="fas fa-th fa-3x text-gray-300 mb-3"></i>
                            <h6 class="text-muted">マトリックスを生成中...</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 接続詳細モーダル -->
<div class="modal fade" id="connectionDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">接続詳細情報</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="connectionDetailContent">
                <!-- Ajax で内容が読み込まれます -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 接続検証モーダル -->
<div class="modal fade" id="validateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">接続検証</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="validationResults">
                    <div class="text-center py-3">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                        <p>接続の整合性を検証中...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <button type="button" class="btn btn-primary" onclick="runValidation()">再検証</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* 接続グラフ用スタイル */
.connection-node {
    cursor: pointer;
    stroke: #fff;
    stroke-width: 2px;
}

.connection-node.town {
    fill: #28a745;
}

.connection-node.road {
    fill: #007bff;
}

.connection-node.dungeon {
    fill: #17a2b8;
}

.connection-link {
    stroke: #999;
    stroke-opacity: 0.6;
    stroke-width: 2px;
}

.connection-link.bidirectional {
    stroke: #28a745;
    stroke-width: 3px;
}

.node-label {
    font-family: Arial, sans-serif;
    font-size: 12px;
    text-anchor: middle;
    pointer-events: none;
}

/* マトリックス用スタイル */
.matrix-cell {
    border: 1px solid #dee2e6;
    padding: 8px;
    text-align: center;
    font-size: 12px;
}

.matrix-cell.connected {
    background-color: #d4edda;
    color: #155724;
}

.matrix-cell.bidirectional {
    background-color: #d1ecf1;
    color: #0c5460;
}

.matrix-header {
    background-color: #e9ecef;
    font-weight: bold;
    writing-mode: vertical-rl;
    text-orientation: mixed;
}
</style>
@endpush

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
// グローバル変数
let connectionData = @json($connections ?? []);
let allRoads = @json($roads ?? []);
let allTowns = @json($towns ?? []);
let allDungeons = @json($dungeons ?? []);

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    initializeConnectionGraph();
    initializeConnectionMatrix();
    
    // タブ切り替え時の処理
    document.getElementById('connectionTabs').addEventListener('shown.bs.tab', function (event) {
        const target = event.target.getAttribute('data-bs-target');
        if (target === '#visual') {
            initializeConnectionGraph();
        } else if (target === '#matrix') {
            initializeConnectionMatrix();
        }
    });
});

// 接続グラフの初期化
function initializeConnectionGraph() {
    const container = document.getElementById('connectionGraph');
    container.innerHTML = '';
    
    // SVGの作成
    const svg = d3.select('#connectionGraph')
        .append('svg')
        .attr('width', '100%')
        .attr('height', '100%');
    
    const width = container.clientWidth;
    const height = container.clientHeight;
    
    // ノードとリンクのデータ準備
    const nodes = [];
    const links = [];
    
    // ノードの追加
    Object.keys(connectionData).forEach(locationId => {
        const location = connectionData[locationId];
        nodes.push({
            id: locationId,
            name: location.name,
            type: location.type,
            connections: location.connections || {}
        });
    });
    
    // リンクの追加
    Object.keys(connectionData).forEach(locationId => {
        const location = connectionData[locationId];
        if (location.connections) {
            Object.values(location.connections).forEach(connection => {
                links.push({
                    source: locationId,
                    target: connection.id,
                    type: connection.type
                });
            });
        }
    });
    
    // シミュレーションの設定
    const simulation = d3.forceSimulation(nodes)
        .force('link', d3.forceLink(links).id(d => d.id).distance(100))
        .force('charge', d3.forceManyBody().strength(-300))
        .force('center', d3.forceCenter(width / 2, height / 2));
    
    // リンクの描画
    const link = svg.append('g')
        .selectAll('line')
        .data(links)
        .join('line')
        .attr('class', 'connection-link')
        .attr('marker-end', 'url(#arrowhead)');
    
    // 矢印マーカーの定義
    svg.append('defs').append('marker')
        .attr('id', 'arrowhead')
        .attr('viewBox', '-0 -5 10 10')
        .attr('refX', 25)
        .attr('refY', 0)
        .attr('orient', 'auto')
        .attr('markerWidth', 6)
        .attr('markerHeight', 6)
        .append('path')
        .attr('d', 'M 0,-5 L 10 ,0 L 0,5')
        .attr('fill', '#999');
    
    // ノードの描画
    const node = svg.append('g')
        .selectAll('circle')
        .data(nodes)
        .join('circle')
        .attr('class', d => `connection-node ${d.type}`)
        .attr('r', 20)
        .call(d3.drag()
            .on('start', dragstarted)
            .on('drag', dragged)
            .on('end', dragended));
    
    // ノードラベルの描画
    const label = svg.append('g')
        .selectAll('text')
        .data(nodes)
        .join('text')
        .attr('class', 'node-label')
        .attr('dy', 4)
        .text(d => d.name);
    
    // シミュレーション更新
    simulation.on('tick', () => {
        link
            .attr('x1', d => d.source.x)
            .attr('y1', d => d.source.y)
            .attr('x2', d => d.target.x)
            .attr('y2', d => d.target.y);
        
        node
            .attr('cx', d => d.x)
            .attr('cy', d => d.y);
        
        label
            .attr('x', d => d.x)
            .attr('y', d => d.y);
    });
    
    // ドラッグ関数
    function dragstarted(event) {
        if (!event.active) simulation.alphaTarget(0.3).restart();
        event.subject.fx = event.subject.x;
        event.subject.fy = event.subject.y;
    }
    
    function dragged(event) {
        event.subject.fx = event.x;
        event.subject.fy = event.y;
    }
    
    function dragended(event) {
        if (!event.active) simulation.alphaTarget(0);
        event.subject.fx = null;
        event.subject.fy = null;
    }
    
    // ノードクリック時の詳細表示
    node.on('click', function(event, d) {
        showConnectionDetails(d.id);
    });
}

// 接続マトリックスの初期化
function initializeConnectionMatrix() {
    const container = document.getElementById('connectionMatrix');
    
    // 全ロケーションのリスト作成
    const allLocations = Object.keys(connectionData);
    
    if (allLocations.length === 0) {
        container.innerHTML = '<div class="text-center py-5"><h6 class="text-muted">表示するデータがありません</h6></div>';
        return;
    }
    
    // テーブルの作成
    let tableHtml = '<div class="table-responsive"><table class="table table-sm">';
    
    // ヘッダー行
    tableHtml += '<thead><tr><th class="matrix-header">From \\ To</th>';
    allLocations.forEach(locationId => {
        const location = connectionData[locationId];
        tableHtml += `<th class="matrix-header" title="${location.name}">${location.name.substr(0, 8)}</th>`;
    });
    tableHtml += '</tr></thead>';
    
    // データ行
    tableHtml += '<tbody>';
    allLocations.forEach(fromLocationId => {
        const fromLocation = connectionData[fromLocationId];
        tableHtml += `<tr><td class="matrix-header" title="${fromLocation.name}">${fromLocation.name}</td>`;
        
        allLocations.forEach(toLocationId => {
            let cellClass = 'matrix-cell';
            let cellContent = '';
            
            if (fromLocationId === toLocationId) {
                cellClass += ' bg-light';
                cellContent = '-';
            } else {
                // 接続チェック
                const hasConnection = fromLocation.connections && 
                    Object.values(fromLocation.connections).some(conn => conn.id === toLocationId);
                
                if (hasConnection) {
                    cellClass += ' connected';
                    cellContent = '●';
                } else {
                    cellContent = '';
                }
            }
            
            tableHtml += `<td class="${cellClass}">${cellContent}</td>`;
        });
        
        tableHtml += '</tr>';
    });
    
    tableHtml += '</tbody></table></div>';
    container.innerHTML = tableHtml;
}

// 接続詳細を表示
function showConnectionDetails(locationId) {
    fetch(`/admin/locations/connections/${locationId}/details`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('connectionDetailContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('connectionDetailModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('詳細情報の取得に失敗しました');
        });
}

// 接続検証を実行
function runValidation() {
    const resultsContainer = document.getElementById('validationResults');
    resultsContainer.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i><p>検証中...</p></div>';
    
    fetch('/admin/locations/connections/validate')
        .then(response => response.json())
        .then(data => {
            let html = '<div class="validation-results">';
            
            if (data.errors && data.errors.length > 0) {
                html += '<div class="alert alert-danger"><h6><i class="fas fa-exclamation-triangle"></i> 問題が見つかりました</h6><ul class="mb-0">';
                data.errors.forEach(error => {
                    html += `<li>${error}</li>`;
                });
                html += '</ul></div>';
            } else {
                html += '<div class="alert alert-success"><i class="fas fa-check-circle"></i> 接続に問題はありません</div>';
            }
            
            if (data.warnings && data.warnings.length > 0) {
                html += '<div class="alert alert-warning"><h6><i class="fas fa-exclamation-circle"></i> 警告</h6><ul class="mb-0">';
                data.warnings.forEach(warning => {
                    html += `<li>${warning}</li>`;
                });
                html += '</ul></div>';
            }
            
            html += '</div>';
            resultsContainer.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            resultsContainer.innerHTML = '<div class="alert alert-danger">検証に失敗しました</div>';
        });
}

// 表示モード切替
function toggleViewMode() {
    // 現在のアクティブタブを取得
    const activeTab = document.querySelector('#connectionTabs .nav-link.active');
    if (activeTab.id === 'visual-tab') {
        document.getElementById('table-tab').click();
    } else if (activeTab.id === 'table-tab') {
        document.getElementById('matrix-tab').click();
    } else {
        document.getElementById('visual-tab').click();
    }
}

// DataTable初期化
$(document).ready(function() {
    $('#connectionsTable').DataTable({
        "pageLength": 25,
        "ordering": true,
        "searching": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ja.json"
        }
    });
});
</script>
@endpush