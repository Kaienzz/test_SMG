@extends('admin.layouts.app')

@section('title', '町管理')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">町管理</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">ロケーション管理</a></li>
                    <li class="breadcrumb-item active">町管理</li>
                </ol>
            </nav>
        </div>
        @if($canManageGameData ?? false)
        <a href="{{ route('admin.locations.towns.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新しい町を追加
        </a>
        @endif
    </div>

    <!-- フィルター・検索フォーム -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">フィルター・検索</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.locations.towns') }}">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="search" class="form-label">キーワード検索</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ $filters['search'] ?? '' }}" 
                               placeholder="町名、説明、IDで検索">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="type" class="form-label">町タイプ</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">全て</option>
                            @if(isset($town_types))
                                @foreach($town_types as $type)
                                    <option value="{{ $type }}" {{ ($filters['type'] ?? '') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="sort_by" class="form-label">ソート項目</label>
                        <select class="form-select" id="sort_by" name="sort_by">
                            <option value="name" {{ ($filters['sort_by'] ?? 'name') == 'name' ? 'selected' : '' }}>名前</option>
                            <option value="type" {{ ($filters['sort_by'] ?? '') == 'type' ? 'selected' : '' }}>タイプ</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="fas fa-search"></i> 検索
                        </button>
                        <a href="{{ route('admin.locations.towns') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-refresh"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 結果表示 -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">町一覧</h6>
            <small class="text-muted">
                {{ $filtered_count ?? 0 }}件 / 全{{ $total_count ?? 0 }}件
            </small>
        </div>
        <div class="card-body">
            @if(isset($towns) && count($towns) > 0)
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>町ID</th>
                            <th>名前</th>
                            <th>説明</th>
                            <th>タイプ</th>
                            <th>サービス</th>
                            <th>接続数</th>
                            @if($canManageGameData ?? false)
                            <th>操作</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($towns as $townId => $town)
                        <tr>
                            <td><code>{{ $townId }}</code></td>
                            <td class="font-weight-bold">{{ $town['name'] }}</td>
                            <td class="text-muted">
                                @if(isset($town['description']) && $town['description'])
                                    {{ Str::limit($town['description'], 50) }}
                                @else
                                    <em>説明なし</em>
                                @endif
                            </td>
                            <td>
                                @php
                                    $typeClass = match($town['type'] ?? 'small_town') {
                                        'starter_town' => 'success',
                                        'hub_town' => 'warning',
                                        'port_town' => 'info',
                                        'commercial_city' => 'primary',
                                        'elven_settlement' => 'secondary',
                                        default => 'light'
                                    };
                                    $typeText = match($town['type'] ?? 'small_town') {
                                        'starter_town' => '初期町',
                                        'hub_town' => 'ハブ町',
                                        'port_town' => '港町',
                                        'commercial_city' => '商業都市',
                                        'elven_settlement' => 'エルフ集落',
                                        'small_town' => '小さな町',
                                        default => ucfirst($town['type'] ?? 'unknown')
                                    };
                                @endphp
                                <span class="badge bg-{{ $typeClass }}">{{ $typeText }}</span>
                            </td>
                            <td>
                                @if(isset($town['services']) && is_array($town['services']))
                                    @foreach($town['services'] as $service)
                                        <span class="badge bg-light text-dark me-1">{{ $service }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">なし</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($town['connections']))
                                    @php $connectionCount = count($town['connections']); @endphp
                                    <span class="badge bg-info">{{ $connectionCount }}個</span>
                                    @if($connectionCount > 1)
                                        <span class="badge bg-warning ms-1">複数接続</span>
                                    @endif
                                @else
                                    <span class="text-muted">なし</span>
                                @endif
                            </td>
                            @if($canManageGameData ?? false)
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.locations.towns.edit', $townId) }}" 
                                       class="btn btn-outline-primary" title="編集">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-info" 
                                            onclick="showTownDetails('{{ $townId }}')" title="詳細">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteTown('{{ $townId }}', '{{ $town['name'] }}')" title="削除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-city fa-3x text-gray-300 mb-3"></i>
                <h6 class="text-muted">該当する町が見つかりません</h6>
                @if($canManageGameData ?? false)
                    <a href="{{ route('admin.locations.towns.create') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> 最初の町を作成
                    </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- 町詳細モーダル -->
<div class="modal fade" id="townDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">町詳細情報</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="townDetailContent">
                <!-- Ajax で内容が読み込まれます -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteTownModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">町削除確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>町「<span id="deleteTownName"></span>」を削除しますか？</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>警告:</strong> この操作は元に戻せません。この町に関連する接続情報も影響を受ける可能性があります。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteTown()">削除する</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除フォーム -->
<form id="deleteTownForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
let deleteTownId = null;

function showTownDetails(townId) {
    // Ajax で町詳細を取得して表示
    fetch(`/admin/locations/towns/${townId}/details`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('townDetailContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('townDetailModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('詳細情報の取得に失敗しました');
        });
}

function deleteTown(townId, townName) {
    deleteTownId = townId;
    document.getElementById('deleteTownName').textContent = townName;
    const modal = new bootstrap.Modal(document.getElementById('deleteTownModal'));
    modal.show();
}

function confirmDeleteTown() {
    if (deleteTownId) {
        const form = document.getElementById('deleteTownForm');
        form.action = `/admin/locations/towns/${deleteTownId}`;
        form.submit();
    }
}

// データテーブル初期化
$(document).ready(function() {
    $('#dataTable').DataTable({
        "pageLength": 25,
        "ordering": false,
        "searching": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ja.json"
        }
    });
});
</script>
@endpush