@extends('admin.layouts.app')

@section('title', '町管理')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">町管理</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.towns.create') }}" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> 新規町作成
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
            <form method="GET" action="{{ route('admin.towns.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">検索</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ $filters['search'] ?? '' }}" placeholder="町名、IDで検索">
                    </div>
                    <div class="col-md-3">
                        <label for="sort_by" class="form-label">並び順</label>
                        <select class="form-select" id="sort_by" name="sort_by">
                            <option value="name" {{ ($filters['sort_by'] ?? '') === 'name' ? 'selected' : '' }}>名前順</option>
                            <option value="id" {{ ($filters['sort_by'] ?? '') === 'id' ? 'selected' : '' }}>ID順</option>
                            <option value="created_at" {{ ($filters['sort_by'] ?? '') === 'created_at' ? 'selected' : '' }}>作成日順</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sort_direction" class="form-label">順序</label>
                        <select class="form-select" id="sort_direction" name="sort_direction">
                            <option value="asc" {{ ($filters['sort_direction'] ?? '') === 'asc' ? 'selected' : '' }}>昇順</option>
                            <option value="desc" {{ ($filters['sort_direction'] ?? '') === 'desc' ? 'selected' : '' }}>降順</option>
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

    <!-- 町一覧 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">町一覧 ({{ count($towns) }}件)</h5>
        </div>
        <div class="card-body">
            @if(count($towns) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>町名</th>
                                <th>説明</th>
                                <th>ステータス</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($towns as $town)
                            <tr>
                                <td><code>{{ $town['id'] }}</code></td>
                                <td>
                                    <strong>{{ $town['name'] }}</strong>
                                </td>
                                <td>{{ $town['description'] ? Str::limit($town['description'], 50) : 'なし' }}</td>
                                
                                <td>
                                    @if($town['is_active'] ?? true)
                                        <span class="badge bg-success">有効</span>
                                    @else
                                        <span class="badge bg-secondary">無効</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="{{ route('admin.towns.show', $town['id']) }}" 
                                           class="admin-btn admin-btn-sm admin-btn-info" title="詳細">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.towns.edit', $town['id']) }}" 
                                           class="admin-btn admin-btn-sm admin-btn-warning" title="編集">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.towns.destroy', $town['id']) }}" 
                                              style="display: inline;" 
                                              onsubmit="return confirm('町「{{ $town['name'] }}」を削除してもよろしいですか？\n\nこの操作は取り消せません。')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" title="削除">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-city fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">町が見つかりません</h5>
                    <p class="text-muted">新しい町を作成するか、検索条件を変更してください。</p>
                    <a href="{{ route('admin.towns.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新規町作成
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- モーダルは使用せず、直接フォーム送信のconfirmで削除を行うため削除 --}}
@endsection