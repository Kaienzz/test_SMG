@extends('admin.layouts.app')

@section('title', $isEdit ? '町編集' : '町作成')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $isEdit ? '町編集' : '町作成' }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">ロケーション管理</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.towns') }}">町管理</a></li>
                    <li class="breadcrumb-item active">{{ $isEdit ? '編集' : '作成' }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.locations.towns') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> 一覧に戻る
        </a>
    </div>

    <!-- メインフォーム -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ $isEdit ? '町情報編集' : '新しい町の作成' }}</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('admin.locations.towns.update', $townId) : route('admin.locations.towns.store') }}">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="row">
                    <!-- 基本情報 -->
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label"><span class="text-danger">*</span> 町名</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $town['name'] ?? '') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">説明</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $town['description'] ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="type" class="form-label"><span class="text-danger">*</span> 町の種類</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">選択してください</option>
                                <option value="capital" {{ old('type', $town['type'] ?? '') == 'capital' ? 'selected' : '' }}>首都</option>
                                <option value="commercial" {{ old('type', $town['type'] ?? '') == 'commercial' ? 'selected' : '' }}>商業都市</option>
                                <option value="residential" {{ old('type', $town['type'] ?? '') == 'residential' ? 'selected' : '' }}>住宅地</option>
                                <option value="industrial" {{ old('type', $town['type'] ?? '') == 'industrial' ? 'selected' : '' }}>工業都市</option>
                                <option value="port" {{ old('type', $town['type'] ?? '') == 'port' ? 'selected' : '' }}>港町</option>
                                <option value="frontier" {{ old('type', $town['type'] ?? '') == 'frontier' ? 'selected' : '' }}>辺境の町</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- 右側のコンテンツ（将来の拡張用） -->
                        <div class="form-group mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>ヒント:</strong> 町の詳細設定は将来的に追加予定です。
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 接続情報 -->
                <hr>
                <h5 class="mb-3">接続情報</h5>
                <p class="text-muted mb-3">この町から移動可能な場所を設定します。</p>

                <div id="connections-container">
                    @php
                        $connections = old('connections', $town['connections'] ?? []);
                        $directions = ['north' => '北', 'south' => '南', 'east' => '東', 'west' => '西', 'northeast' => '北東', 'northwest' => '北西', 'southeast' => '南東', 'southwest' => '南西'];
                    @endphp
                    
                    @foreach($directions as $directionKey => $directionLabel)
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">{{ $directionLabel }}</label>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="connections[{{ $directionKey }}][type]" 
                                    onchange="updateConnectionOptions(this, '{{ $directionKey }}')">
                                <option value="">接続なし</option>
                                <option value="road" {{ ($connections[$directionKey]['type'] ?? '') == 'road' ? 'selected' : '' }}>道路</option>
                                <option value="town" {{ ($connections[$directionKey]['type'] ?? '') == 'town' ? 'selected' : '' }}>町</option>
                                <option value="dungeon" {{ ($connections[$directionKey]['type'] ?? '') == 'dungeon' ? 'selected' : '' }}>ダンジョン</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" name="connections[{{ $directionKey }}][id]" 
                                    id="connection-{{ $directionKey }}-id">
                                <option value="">選択してください</option>
                                @if(isset($connections[$directionKey]['type']) && $connections[$directionKey]['type'])
                                    @foreach($availableLocations as $location)
                                        @if($location['type'] == $connections[$directionKey]['type'])
                                            <option value="{{ $location['id'] }}" 
                                                    {{ ($connections[$directionKey]['id'] ?? '') == $location['id'] ? 'selected' : '' }}>
                                                {{ $location['name'] }}
                                            </option>
                                        @endif
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- 送信ボタン -->
                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> {{ $isEdit ? '更新' : '作成' }}
                        </button>
                        <a href="{{ route('admin.locations.towns') }}" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> キャンセル
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// 利用可能なロケーション一覧
const availableLocations = @json($availableLocations);

function updateConnectionOptions(typeSelect, direction) {
    const type = typeSelect.value;
    const idSelect = document.getElementById(`connection-${direction}-id`);
    
    // 選択肢をクリア
    idSelect.innerHTML = '<option value="">選択してください</option>';
    
    if (type) {
        // 選択されたタイプに合致するロケーションを追加
        availableLocations.forEach(location => {
            if (location.type === type) {
                const option = document.createElement('option');
                option.value = location.id;
                option.textContent = location.name;
                idSelect.appendChild(option);
            }
        });
    }
}
</script>
@endpush