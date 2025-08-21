@extends('admin.layouts.app')

@section('title', '道路・ダンジョン管理')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">道路・ダンジョン管理</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">ロケーション管理</a></li>
                    <li class="breadcrumb-item active">道路・ダンジョン管理</li>
                </ol>
            </nav>
        </div>
        @if($canManageGameData ?? false)
        <a href="{{ route('admin.locations.pathways.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新しい道路・ダンジョンを追加
        </a>
        @endif
    </div>

    <!-- フィルター・検索フォーム -->
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h6 class="admin-card-title">フィルター・検索</h6>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.locations.pathways') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search" class="form-label">キーワード検索</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ $filters['search'] ?? '' }}" 
                                   placeholder="名前、説明、IDで検索">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="category" class="form-label">カテゴリー</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">全て</option>
                                <option value="road" {{ ($filters['category'] ?? '') == 'road' ? 'selected' : '' }}>道路</option>
                                <option value="dungeon" {{ ($filters['category'] ?? '') == 'dungeon' ? 'selected' : '' }}>ダンジョン</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="type" class="form-label">タイプ</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">全て</option>
                                <option value="cave" {{ ($filters['type'] ?? '') == 'cave' ? 'selected' : '' }}>洞窟</option>
                                <option value="ruins" {{ ($filters['type'] ?? '') == 'ruins' ? 'selected' : '' }}>遺跡</option>
                                <option value="tower" {{ ($filters['type'] ?? '') == 'tower' ? 'selected' : '' }}>塔</option>
                                <option value="underground" {{ ($filters['type'] ?? '') == 'underground' ? 'selected' : '' }}>地下</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="difficulty" class="form-label">難易度</label>
                            <select class="form-select" id="difficulty" name="difficulty">
                                <option value="">全て</option>
                                <option value="easy" {{ ($filters['difficulty'] ?? '') == 'easy' ? 'selected' : '' }}>簡単</option>
                                <option value="normal" {{ ($filters['difficulty'] ?? '') == 'normal' ? 'selected' : '' }}>普通</option>
                                <option value="hard" {{ ($filters['difficulty'] ?? '') == 'hard' ? 'selected' : '' }}>困難</option>
                                <option value="extreme" {{ ($filters['difficulty'] ?? '') == 'extreme' ? 'selected' : '' }}>極難</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="sort_by" class="form-label">ソート項目</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="name" {{ ($filters['sort_by'] ?? 'name') == 'name' ? 'selected' : '' }}>名前</option>
                                <option value="category" {{ ($filters['sort_by'] ?? '') == 'category' ? 'selected' : '' }}>カテゴリー</option>
                                <option value="difficulty" {{ ($filters['sort_by'] ?? '') == 'difficulty' ? 'selected' : '' }}>難易度</option>
                                <option value="length" {{ ($filters['sort_by'] ?? '') == 'length' ? 'selected' : '' }}>長さ</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label for="sort_direction" class="form-label">順序</label>
                            <select class="form-select" id="sort_direction" name="sort_direction">
                                <option value="asc" {{ ($filters['sort_direction'] ?? 'asc') == 'asc' ? 'selected' : '' }}>昇順</option>
                                <option value="desc" {{ ($filters['sort_direction'] ?? '') == 'desc' ? 'selected' : '' }}>降順</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> 検索
                        </button>
                        <a href="{{ route('admin.locations.pathways') }}" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-sync-alt"></i> リセット
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 結果表示 -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">道路・ダンジョン一覧</h6>
            <small class="text-muted">
                {{ $filtered_count ?? 0 }}件 / 全{{ $total_count ?? 0 }}件
            </small>
        </div>
        <div class="card-body">
            @if(isset($pathways) && count($pathways) > 0)
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名前</th>
                            <th>カテゴリー</th>
                            <th>説明</th>
                            <th>長さ</th>
                            <th>難易度</th>
                            <th>エンカウント率</th>
                            <th>詳細情報</th>
                            @if($canManageGameData ?? false)
                            <th width="200">操作</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pathways as $pathwayId => $pathway)
                        <tr>
                            <td><code>{{ $pathwayId }}</code></td>
                            <td class="font-weight-bold">{{ $pathway['name'] }}</td>
                            <td>
                                @php
                                    $categoryClass = match($pathway['category'] ?? 'road') {
                                        'road' => 'primary',
                                        'dungeon' => 'danger',
                                        default => 'secondary'
                                    };
                                    $categoryText = match($pathway['category'] ?? 'road') {
                                        'road' => '道路',
                                        'dungeon' => 'ダンジョン',
                                        default => '不明'
                                    };
                                @endphp
                                <span class="badge bg-{{ $categoryClass }}">{{ $categoryText }}</span>
                            </td>
                            <td class="text-muted">
                                @if(isset($pathway['description']) && $pathway['description'])
                                    {{ Str::limit($pathway['description'], 40) }}
                                @else
                                    <em>説明なし</em>
                                @endif
                            </td>
                            <td>{{ $pathway['length'] ?? 100 }}</td>
                            <td>
                                @php
                                    $difficultyClass = match($pathway['difficulty'] ?? 'normal') {
                                        'easy' => 'success',
                                        'hard' => 'warning',
                                        'extreme' => 'danger',
                                        default => 'info'
                                    };
                                    $difficultyText = match($pathway['difficulty'] ?? 'normal') {
                                        'easy' => '簡単',
                                        'hard' => '困難',
                                        'extreme' => '極難',
                                        default => '普通'
                                    };
                                @endphp
                                <span class="badge bg-{{ $difficultyClass }}">{{ $difficultyText }}</span>
                            </td>
                            <td>{{ number_format(($pathway['encounter_rate'] ?? 0) * 100, 1) }}%</td>
                            <td>
                                @if($pathway['category'] === 'dungeon')
                                    @if(isset($pathway['dungeon_type']))
                                        @php
                                            $typeClass = match($pathway['dungeon_type']) {
                                                'cave' => 'secondary',
                                                'ruins' => 'warning',
                                                'tower' => 'info',
                                                'underground' => 'dark',
                                                default => 'secondary'
                                            };
                                            $typeText = match($pathway['dungeon_type']) {
                                                'cave' => '洞窟',
                                                'ruins' => '遺跡',
                                                'tower' => '塔',
                                                'underground' => '地下',
                                                default => $pathway['dungeon_type']
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $typeClass }}">{{ $typeText }}</span>
                                    @endif
                                    @if(isset($pathway['floors']))
                                        <div><small>{{ $pathway['floors'] }}F</small></div>
                                    @endif
                                    @if(isset($pathway['boss']) && $pathway['boss'])
                                        <div><small class="text-danger">Boss: {{ $pathway['boss'] }}</small></div>
                                    @endif
                                @else
                                    @if(isset($pathway['connections']))
                                        @php $connectionCount = count($pathway['connections']); @endphp
                                        <small>接続: {{ $connectionCount }}個</small>
                                    @endif
                                    @if(isset($pathway['branches']))
                                        @php $branchCount = count($pathway['branches']); @endphp
                                        <div><small>分岐: {{ $branchCount }}個</small></div>
                                    @endif
                                @endif
                            </td>
                            @if($canManageGameData ?? false)
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.locations.pathways.edit', $pathwayId) }}" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i> 編集
                                    </a>
                                    <a href="{{ route('admin.locations.show', $pathwayId) }}" 
                                       class="btn btn-outline-info">
                                        <i class="fas fa-eye"></i> 詳細
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deletePathway('{{ $pathwayId }}', '{{ $pathway['name'] }}')">
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
                <i class="fas fa-route fa-3x text-gray-300 mb-3"></i>
                <h6 class="text-muted">該当する道路・ダンジョンが見つかりません</h6>
                @if($canManageGameData ?? false)
                    <a href="{{ route('admin.locations.pathways.create') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> 最初の道路・ダンジョンを作成
                    </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- 詳細表示は専用ページに移行したため、モーダルは削除 -->

<!-- 削除確認モーダル -->
<div class="modal fade" id="deletePathwayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">削除確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>「<span id="deletePathwayName"></span>」を削除しますか？</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>警告:</strong> この操作は元に戻せません。関連する接続情報も影響を受ける可能性があります。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeletePathway()">削除する</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除フォーム -->
<form id="deletePathwayForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
let deletePathwayId = null;

// 詳細表示は専用ページに遷移するため、JavaScriptは不要

function deletePathway(pathwayId, pathwayName) {
    deletePathwayId = pathwayId;
    document.getElementById('deletePathwayName').textContent = pathwayName;
    const modal = new bootstrap.Modal(document.getElementById('deletePathwayModal'));
    modal.show();
}

function confirmDeletePathway() {
    if (deletePathwayId) {
        const form = document.getElementById('deletePathwayForm');
        form.action = `/admin/locations/pathways/${deletePathwayId}`;
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