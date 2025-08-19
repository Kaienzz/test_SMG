@extends('admin.layouts.app')

@section('title', $isEdit ? 'パスウェイ編集' : 'パスウェイ作成')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $isEdit ? 'パスウェイ編集' : 'パスウェイ作成' }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">ロケーション管理</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.pathways') }}">道路・ダンジョン管理</a></li>
                    <li class="breadcrumb-item active">{{ $isEdit ? '編集' : '作成' }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.locations.pathways') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> 一覧に戻る
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.locations.pathways.update', $pathwayId) : route('admin.locations.pathways.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row">
            <!-- 基本情報カード -->
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">基本情報</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="pathway_id" class="form-label">ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('pathway_id') is-invalid @enderror" 
                                           id="pathway_id" name="pathway_id" value="{{ old('pathway_id', $pathwayId ?? '') }}" 
                                           required pattern="[a-zA-Z0-9_-]+" placeholder="半角英数字・アンダースコア・ハイフンのみ">
                                    @if($isEdit)
                                        <small class="form-text text-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            IDを変更すると関連データも自動更新されます
                                        </small>
                                    @endif
                                    @error('pathway_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $pathway['name'] ?? '') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="category" class="form-label">カテゴリー <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category') is-invalid @enderror" 
                                            id="category" name="category" required onchange="toggleDungeonFields()">
                                        <option value="">選択してください</option>
                                        @foreach($categories as $value => $label)
                                            <option value="{{ $value }}" 
                                                {{ old('category', $pathway['category'] ?? '') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">説明</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $pathway['description'] ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="length" class="form-label">長さ <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('length') is-invalid @enderror" 
                                           id="length" name="length" value="{{ old('length', $pathway['length'] ?? 100) }}" 
                                           min="1" max="1000" required>
                                    @error('length')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="difficulty" class="form-label">難易度 <span class="text-danger">*</span></label>
                                    <select class="form-select @error('difficulty') is-invalid @enderror" 
                                            id="difficulty" name="difficulty" required>
                                        @foreach($difficulties as $value => $label)
                                            <option value="{{ $value }}" 
                                                {{ old('difficulty', $pathway['difficulty'] ?? '') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('difficulty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="encounter_rate" class="form-label">エンカウント率 <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('encounter_rate') is-invalid @enderror" 
                                           id="encounter_rate" name="encounter_rate" 
                                           value="{{ old('encounter_rate', $pathway['encounter_rate'] ?? 0.1) }}" 
                                           min="0" max="1" step="0.01" required>
                                    <small class="form-text text-muted">0.0～1.0の範囲で入力</small>
                                    @error('encounter_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ダンジョン固有情報カード -->
                <div class="card shadow mb-4" id="dungeonFields" style="display: none;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-danger">ダンジョン固有情報</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="dungeon_type" class="form-label">ダンジョンタイプ</label>
                                    <select class="form-select @error('dungeon_type') is-invalid @enderror" 
                                            id="dungeon_type" name="dungeon_type">
                                        <option value="">選択してください</option>
                                        @foreach($dungeonTypes as $value => $label)
                                            <option value="{{ $value }}" 
                                                {{ old('dungeon_type', $pathway['dungeon_type'] ?? '') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('dungeon_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="floors" class="form-label">フロア数</label>
                                    <input type="number" class="form-control @error('floors') is-invalid @enderror" 
                                           id="floors" name="floors" value="{{ old('floors', $pathway['floors'] ?? 1) }}" 
                                           min="1" max="100">
                                    @error('floors')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="min_level" class="form-label">最小推奨レベル</label>
                                    <input type="number" class="form-control @error('min_level') is-invalid @enderror" 
                                           id="min_level" name="min_level" value="{{ old('min_level', $pathway['min_level'] ?? '') }}" 
                                           min="1">
                                    @error('min_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="max_level" class="form-label">最大推奨レベル</label>
                                    <input type="number" class="form-control @error('max_level') is-invalid @enderror" 
                                           id="max_level" name="max_level" value="{{ old('max_level', $pathway['max_level'] ?? '') }}" 
                                           min="1">
                                    @error('max_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="boss" class="form-label">ボス名</label>
                                    <input type="text" class="form-control @error('boss') is-invalid @enderror" 
                                           id="boss" name="boss" value="{{ old('boss', $pathway['boss'] ?? '') }}">
                                    @error('boss')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 接続情報カード -->
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">接続情報</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label class="form-label">開始地点</label>
                            <div class="row">
                                <div class="col-6">
                                    <select class="form-select @error('connections.start.type') is-invalid @enderror" 
                                            name="connections[start][type]">
                                        <option value="">タイプ</option>
                                        <option value="town" {{ old('connections.start.type', $pathway['connections']['start']['type'] ?? '') == 'town' ? 'selected' : '' }}>町</option>
                                        <option value="pathway" {{ old('connections.start.type', $pathway['connections']['start']['type'] ?? '') == 'pathway' ? 'selected' : '' }}>道路・ダンジョン</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control @error('connections.start.id') is-invalid @enderror" 
                                           name="connections[start][id]" value="{{ old('connections.start.id', $pathway['connections']['start']['id'] ?? '') }}" 
                                           placeholder="ID">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">終了地点</label>
                            <div class="row">
                                <div class="col-6">
                                    <select class="form-select @error('connections.end.type') is-invalid @enderror" 
                                            name="connections[end][type]">
                                        <option value="">タイプ</option>
                                        <option value="town" {{ old('connections.end.type', $pathway['connections']['end']['type'] ?? '') == 'town' ? 'selected' : '' }}>町</option>
                                        <option value="pathway" {{ old('connections.end.type', $pathway['connections']['end']['type'] ?? '') == 'pathway' ? 'selected' : '' }}>道路・ダンジョン</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control @error('connections.end.id') is-invalid @enderror" 
                                           name="connections[end][id]" value="{{ old('connections.end.id', $pathway['connections']['end']['id'] ?? '') }}" 
                                           placeholder="ID">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                接続先IDは既存のロケーションIDを入力してください。
                            </small>
                        </div>
                    </div>
                </div>

                <!-- 操作ボタン -->
                <div class="card shadow">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> {{ $isEdit ? '更新' : '作成' }}
                        </button>
                        <a href="{{ route('admin.locations.pathways') }}" class="btn btn-secondary btn-lg ms-2">
                            <i class="fas fa-times"></i> キャンセル
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleDungeonFields() {
    const category = document.getElementById('category').value;
    const dungeonFields = document.getElementById('dungeonFields');
    
    if (category === 'dungeon') {
        dungeonFields.style.display = 'block';
        // ダンジョン必須フィールドを有効化
        document.getElementById('dungeon_type').required = true;
    } else {
        dungeonFields.style.display = 'none';
        // ダンジョン必須フィールドを無効化
        document.getElementById('dungeon_type').required = false;
    }
}

// ページ読み込み時に実行
document.addEventListener('DOMContentLoaded', function() {
    toggleDungeonFields();
});
</script>
@endpush