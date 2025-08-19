@extends('admin.layouts.app')

@section('title', 'ダンジョン管理')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">ダンジョン管理</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">ロケーション管理</a></li>
                    <li class="breadcrumb-item active">ダンジョン管理</li>
                </ol>
            </nav>
        </div>
        @if($canManageGameData ?? false)
        <a href="{{ route('admin.locations.dungeons.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新しいダンジョンを追加
        </a>
        @endif
    </div>

    <!-- フィルター・検索フォーム -->
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h6 class="admin-card-title">フィルター・検索</h6>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.locations.dungeons') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search" class="form-label">キーワード検索</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ $filters['search'] ?? '' }}" 
                                   placeholder="ダンジョン名、説明、IDで検索">
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
                                <option value="type" {{ ($filters['sort_by'] ?? '') == 'type' ? 'selected' : '' }}>タイプ</option>
                                <option value="difficulty" {{ ($filters['sort_by'] ?? '') == 'difficulty' ? 'selected' : '' }}>難易度</option>
                                <option value="floors" {{ ($filters['sort_by'] ?? '') == 'floors' ? 'selected' : '' }}>フロア数</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="sort_direction" class="form-label">ソート順</label>
                            <select class="form-select" id="sort_direction" name="sort_direction">
                                <option value="asc" {{ ($filters['sort_direction'] ?? 'asc') == 'asc' ? 'selected' : '' }}>昇順</option>
                                <option value="desc" {{ ($filters['sort_direction'] ?? '') == 'desc' ? 'selected' : '' }}>降順</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 検索
                                </button>
                                <a href="{{ route('admin.locations.dungeons') }}" class="btn btn-outline-secondary" title="リセット">
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
            <h6 class="m-0 font-weight-bold text-primary">ダンジョン一覧</h6>
            <small class="text-muted">
                {{ $filtered_count ?? 0 }}件 / 全{{ $total_count ?? 0 }}件
            </small>
        </div>
        <div class="card-body">
            @if(isset($dungeons) && count($dungeons) > 0)
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ダンジョンID</th>
                            <th>名前</th>
                            <th>説明</th>
                            <th>タイプ</th>
                            <th>難易度</th>
                            <th>フロア数</th>
                            <th>レベル制限</th>
                            <th>ボス</th>
                            @if($canManageGameData ?? false)
                            <th width="200">操作</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dungeons as $dungeonId => $dungeon)
                        <tr>
                            <td><code>{{ $dungeonId }}</code></td>
                            <td class="font-weight-bold">{{ $dungeon['name'] }}</td>
                            <td class="text-muted">
                                @if(isset($dungeon['description']) && $dungeon['description'])
                                    {{ Str::limit($dungeon['description'], 50) }}
                                @else
                                    <em>説明なし</em>
                                @endif
                            </td>
                            <td>
                                @php
                                    $typeClass = match($dungeon['type'] ?? 'cave') {
                                        'cave' => 'secondary',
                                        'ruins' => 'info',
                                        'tower' => 'warning',
                                        'underground' => 'dark',
                                        default => 'secondary'
                                    };
                                    $typeText = match($dungeon['type'] ?? 'cave') {
                                        'cave' => '洞窟',
                                        'ruins' => '遺跡',
                                        'tower' => '塔',
                                        'underground' => '地下',
                                        default => '洞窟'
                                    };
                                @endphp
                                <span class="badge bg-{{ $typeClass }}">{{ $typeText }}</span>
                            </td>
                            <td>
                                @php
                                    $difficultyClass = match($dungeon['difficulty'] ?? 'normal') {
                                        'easy' => 'success',
                                        'hard' => 'danger',
                                        'extreme' => 'dark',
                                        default => 'warning'
                                    };
                                    $difficultyText = match($dungeon['difficulty'] ?? 'normal') {
                                        'easy' => '簡単',
                                        'hard' => '困難',
                                        'extreme' => '極難',
                                        default => '普通'
                                    };
                                @endphp
                                <span class="badge bg-{{ $difficultyClass }}">{{ $difficultyText }}</span>
                            </td>
                            <td>{{ $dungeon['floors'] ?? 1 }}F</td>
                            <td>
                                @if(isset($dungeon['min_level']) || isset($dungeon['max_level']))
                                    @if(isset($dungeon['min_level']) && isset($dungeon['max_level']))
                                        Lv.{{ $dungeon['min_level'] }}-{{ $dungeon['max_level'] }}
                                    @elseif(isset($dungeon['min_level']))
                                        Lv.{{ $dungeon['min_level'] }}+
                                    @else
                                        ～Lv.{{ $dungeon['max_level'] }}
                                    @endif
                                @else
                                    <span class="text-muted">制限なし</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($dungeon['boss']) && $dungeon['boss'])
                                    <span class="text-danger">{{ $dungeon['boss'] }}</span>
                                @else
                                    <span class="text-muted">なし</span>
                                @endif
                            </td>
                            @if($canManageGameData ?? false)
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.locations.dungeons.edit', $dungeonId) }}" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i> 編集
                                    </a>
                                    <button type="button" class="btn btn-outline-info" 
                                            onclick="showDungeonDetails('{{ $dungeonId }}')">
                                        <i class="fas fa-eye"></i> 詳細
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteDungeon('{{ $dungeonId }}', '{{ $dungeon['name'] }}')">
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
                <i class="fas fa-dragon fa-3x text-gray-300 mb-3"></i>
                <h6 class="text-muted">該当するダンジョンが見つかりません</h6>
                @if($canManageGameData ?? false)
                    <a href="{{ route('admin.locations.dungeons.create') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> 最初のダンジョンを作成
                    </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- ダンジョン詳細モーダル -->
<div class="modal fade" id="dungeonDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ダンジョン詳細情報</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dungeonDetailContent">
                <!-- Ajax で内容が読み込まれます -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteDungeonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ダンジョン削除確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>ダンジョン「<span id="deleteDungeonName"></span>」を削除しますか？</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>警告:</strong> この操作は元に戻せません。このダンジョンに関連する接続情報も影響を受ける可能性があります。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteDungeon()">削除する</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除フォーム -->
<form id="deleteDungeonForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
let deleteDungeonId = null;

function showDungeonDetails(dungeonId) {
    // Ajax でダンジョン詳細を取得して表示
    fetch(`/admin/locations/dungeons/${dungeonId}/details`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('dungeonDetailContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('dungeonDetailModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('詳細情報の取得に失敗しました');
        });
}

function deleteDungeon(dungeonId, dungeonName) {
    deleteDungeonId = dungeonId;
    document.getElementById('deleteDungeonName').textContent = dungeonName;
    const modal = new bootstrap.Modal(document.getElementById('deleteDungeonModal'));
    modal.show();
}

function confirmDeleteDungeon() {
    if (deleteDungeonId) {
        const form = document.getElementById('deleteDungeonForm');
        form.action = `/admin/locations/dungeons/${deleteDungeonId}`;
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