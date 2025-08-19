@extends('admin.layouts.app')

@section('title', '道路管理')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">道路管理</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">ロケーション管理</a></li>
                    <li class="breadcrumb-item active">道路管理</li>
                </ol>
            </nav>
        </div>
        @if($canManageGameData ?? false)
        <a href="{{ route('admin.locations.roads.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新しい道路を追加
        </a>
        @endif
    </div>

    <!-- フィルター・検索フォーム -->
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h6 class="admin-card-title">フィルター・検索</h6>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.locations.roads') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search" class="form-label">キーワード検索</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ $filters['search'] ?? '' }}" 
                                   placeholder="道路名、説明、IDで検索">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="sort_by" class="form-label">ソート項目</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="name" {{ ($filters['sort_by'] ?? 'name') == 'name' ? 'selected' : '' }}>名前</option>
                                <option value="difficulty" {{ ($filters['sort_by'] ?? '') == 'difficulty' ? 'selected' : '' }}>難易度</option>
                                <option value="encounter_rate" {{ ($filters['sort_by'] ?? '') == 'encounter_rate' ? 'selected' : '' }}>エンカウント率</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="sort_direction" class="form-label">ソート順</label>
                            <select class="form-select" id="sort_direction" name="sort_direction">
                                <option value="asc" {{ ($filters['sort_direction'] ?? 'asc') == 'asc' ? 'selected' : '' }}>昇順</option>
                                <option value="desc" {{ ($filters['sort_direction'] ?? '') == 'desc' ? 'selected' : '' }}>降順</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 検索
                                </button>
                                <a href="{{ route('admin.locations.roads') }}" class="btn btn-outline-secondary" title="リセット">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 結果表示 -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">道路一覧</h6>
            <small class="text-muted">
                {{ $filtered_count ?? 0 }}件 / 全{{ $total_count ?? 0 }}件
            </small>
        </div>
        <div class="card-body">
            @if(isset($roads) && count($roads) > 0)
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>道路ID</th>
                            <th>名前</th>
                            <th>説明</th>
                            <th>長さ</th>
                            <th>難易度</th>
                            <th>エンカウント率</th>
                            <th>接続</th>
                            <th>分岐</th>
                            @if($canManageGameData ?? false)
                            <th width="200">操作</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roads as $roadId => $road)
                        <tr>
                            <td><code>{{ $roadId }}</code></td>
                            <td class="font-weight-bold">{{ $road['name'] }}</td>
                            <td class="text-muted">
                                @if(isset($road['description']) && $road['description'])
                                    {{ Str::limit($road['description'], 50) }}
                                @else
                                    <em>説明なし</em>
                                @endif
                            </td>
                            <td>{{ $road['length'] ?? 100 }}</td>
                            <td>
                                @php
                                    $difficultyClass = match($road['difficulty'] ?? 'normal') {
                                        'easy' => 'success',
                                        'hard' => 'danger',
                                        default => 'warning'
                                    };
                                    $difficultyText = match($road['difficulty'] ?? 'normal') {
                                        'easy' => '簡単',
                                        'hard' => '困難',
                                        default => '普通'
                                    };
                                @endphp
                                <span class="badge bg-{{ $difficultyClass }}">{{ $difficultyText }}</span>
                            </td>
                            <td>{{ number_format(($road['encounter_rate'] ?? 0) * 100, 1) }}%</td>
                            <td>
                                @if(isset($road['connections']))
                                    @php $connectionCount = count($road['connections']); @endphp
                                    <span class="badge bg-info">{{ $connectionCount }}個</span>
                                @else
                                    <span class="text-muted">なし</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($road['branches']))
                                    @php $branchCount = count($road['branches']); @endphp
                                    <span class="badge bg-warning">{{ $branchCount }}個</span>
                                @else
                                    <span class="text-muted">なし</span>
                                @endif
                            </td>
                            @if($canManageGameData ?? false)
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.locations.roads.edit', $roadId) }}" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i> 編集
                                    </a>
                                    <button type="button" class="btn btn-outline-info" 
                                            onclick="showRoadDetails('{{ $roadId }}')">
                                        <i class="fas fa-eye"></i> 詳細
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteRoad('{{ $roadId }}', '{{ $road['name'] }}')">
                                        <i class="fas fa-trash"></i> 削除
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
                <i class="fas fa-road fa-3x text-gray-300 mb-3"></i>
                <h6 class="text-muted">該当する道路が見つかりません</h6>
                @if($canManageGameData ?? false)
                    <a href="{{ route('admin.locations.roads.create') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> 最初の道路を作成
                    </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- 道路詳細モーダル -->
<div class="modal fade" id="roadDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">道路詳細情報</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="roadDetailContent">
                <!-- Ajax で内容が読み込まれます -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteRoadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">道路削除確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>道路「<span id="deleteRoadName"></span>」を削除しますか？</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>警告:</strong> この操作は元に戻せません。この道路に関連する接続情報も影響を受ける可能性があります。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteRoad()">削除する</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除フォーム -->
<form id="deleteRoadForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
let deleteRoadId = null;

function showRoadDetails(roadId) {
    // Ajax で道路詳細を取得して表示
    fetch(`/admin/locations/roads/${roadId}/details`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('roadDetailContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('roadDetailModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('詳細情報の取得に失敗しました');
        });
}

function deleteRoad(roadId, roadName) {
    deleteRoadId = roadId;
    document.getElementById('deleteRoadName').textContent = roadName;
    const modal = new bootstrap.Modal(document.getElementById('deleteRoadModal'));
    modal.show();
}

function confirmDeleteRoad() {
    if (deleteRoadId) {
        const form = document.getElementById('deleteRoadForm');
        form.action = `/admin/locations/roads/${deleteRoadId}`;
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