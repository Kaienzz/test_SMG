@extends('admin.layouts.app')

@section('title', $isEdit ? '道路編集' : '道路作成')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $isEdit ? '道路編集' : '道路作成' }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">ロケーション管理</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.roads') }}">道路管理</a></li>
                    <li class="breadcrumb-item active">{{ $isEdit ? '編集' : '作成' }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.locations.roads') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 一覧に戻る
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.locations.roads.update', $roadId) : route('admin.locations.roads.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row">
            <!-- 基本情報 -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">基本情報</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="name" class="form-label">道路名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $road['name'] ?? '') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="length" class="form-label">長さ <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('length') is-invalid @enderror" 
                                       id="length" name="length" value="{{ old('length', $road['length'] ?? 100) }}" 
                                       min="1" max="100" required>
                                <div class="form-text">1-100の範囲で設定してください</div>
                                @error('length')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">説明</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $road['description'] ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="difficulty" class="form-label">難易度 <span class="text-danger">*</span></label>
                                <select class="form-select @error('difficulty') is-invalid @enderror" 
                                        id="difficulty" name="difficulty" required>
                                    <option value="">選択してください</option>
                                    <option value="easy" {{ old('difficulty', $road['difficulty'] ?? '') == 'easy' ? 'selected' : '' }}>簡単</option>
                                    <option value="normal" {{ old('difficulty', $road['difficulty'] ?? '') == 'normal' ? 'selected' : '' }}>普通</option>
                                    <option value="hard" {{ old('difficulty', $road['difficulty'] ?? '') == 'hard' ? 'selected' : '' }}>困難</option>
                                </select>
                                @error('difficulty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="encounter_rate" class="form-label">エンカウント率 <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('encounter_rate') is-invalid @enderror" 
                                       id="encounter_rate" name="encounter_rate" 
                                       value="{{ old('encounter_rate', $road['encounter_rate'] ?? 0.1) }}" 
                                       min="0" max="1" step="0.01" required>
                                <div class="form-text">0.0～1.0の範囲で設定（例：0.15 = 15%）</div>
                                @error('encounter_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 接続情報 -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">接続情報</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">開始地点の接続</label>
                                <div class="row">
                                    <div class="col-6">
                                        <select class="form-select" name="connections[start][type]" 
                                                onchange="updateLocationOptions(this, 'start')">
                                            <option value="">タイプを選択</option>
                                            <option value="town" {{ old('connections.start.type', $road['connections']['start']['type'] ?? '') == 'town' ? 'selected' : '' }}>町</option>
                                            <option value="road" {{ old('connections.start.type', $road['connections']['start']['type'] ?? '') == 'road' ? 'selected' : '' }}>道路</option>
                                            <option value="dungeon" {{ old('connections.start.type', $road['connections']['start']['type'] ?? '') == 'dungeon' ? 'selected' : '' }}>ダンジョン</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select class="form-select" name="connections[start][id]" id="start_location_id">
                                            <option value="">接続先を選択</option>
                                            <!-- Ajax で動的に更新 -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">終了地点の接続</label>
                                <div class="row">
                                    <div class="col-6">
                                        <select class="form-select" name="connections[end][type]" 
                                                onchange="updateLocationOptions(this, 'end')">
                                            <option value="">タイプを選択</option>
                                            <option value="town" {{ old('connections.end.type', $road['connections']['end']['type'] ?? '') == 'town' ? 'selected' : '' }}>町</option>
                                            <option value="road" {{ old('connections.end.type', $road['connections']['end']['type'] ?? '') == 'road' ? 'selected' : '' }}>道路</option>
                                            <option value="dungeon" {{ old('connections.end.type', $road['connections']['end']['type'] ?? '') == 'dungeon' ? 'selected' : '' }}>ダンジョン</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select class="form-select" name="connections[end][id]" id="end_location_id">
                                            <option value="">接続先を選択</option>
                                            <!-- Ajax で動的に更新 -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 分岐情報 -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">分岐情報（T字路・交差点）</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addBranch()">
                            <i class="fas fa-plus"></i> 分岐を追加
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="branchesContainer">
                            @if(isset($road['branches']))
                                @foreach($road['branches'] as $position => $branchData)
                                    <!-- 既存分岐情報をここに表示 -->
                                @endforeach
                            @endif
                        </div>
                        <div id="noBranches" class="text-center text-muted py-3" 
                             style="{{ isset($road['branches']) && count($road['branches']) > 0 ? 'display: none;' : '' }}">
                            分岐はありません。「分岐を追加」ボタンで追加できます。
                        </div>
                    </div>
                </div>
            </div>

            <!-- サイドバー -->
            <div class="col-lg-4">
                <!-- 保存ボタン -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">操作</h6>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-save"></i> {{ $isEdit ? '更新' : '作成' }}
                        </button>
                        <a href="{{ route('admin.locations.roads') }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-times"></i> キャンセル
                        </a>
                    </div>
                </div>

                <!-- プレビュー -->
                @if($isEdit && isset($road))
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">現在の設定</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>道路ID</strong></td>
                                <td><code>{{ $roadId }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>名前</strong></td>
                                <td>{{ $road['name'] }}</td>
                            </tr>
                            <tr>
                                <td><strong>難易度</strong></td>
                                <td>
                                    @php
                                        $difficultyText = match($road['difficulty'] ?? 'normal') {
                                            'easy' => '簡単',
                                            'hard' => '困難',
                                            default => '普通'
                                        };
                                    @endphp
                                    {{ $difficultyText }}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>エンカウント</strong></td>
                                <td>{{ number_format(($road['encounter_rate'] ?? 0) * 100, 1) }}%</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @endif

                <!-- ヘルプ -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">ヘルプ</h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <p><strong>道路名:</strong> ゲーム内で表示される道路の名前です。</p>
                            <p><strong>長さ:</strong> 道路の移動可能範囲（通常は100）です。</p>
                            <p><strong>難易度:</strong> エンカウントする敵の強さに影響します。</p>
                            <p><strong>エンカウント率:</strong> 移動時に戦闘が発生する確率です。</p>
                            <p><strong>分岐:</strong> 道路の途中で分かれ道を作ることができます。位置50でT字路などを設定できます。</p>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
// 全ロケーション情報（PHP から渡される）
const allLocations = @json($allLocations ?? []);

// 接続先オプションを更新
function updateLocationOptions(typeSelect, position) {
    const type = typeSelect.value;
    const locationSelect = document.getElementById(position + '_location_id');
    
    // 選択肢をクリア
    locationSelect.innerHTML = '<option value="">接続先を選択</option>';
    
    if (type) {
        // 指定タイプのロケーションのみフィルタ
        const filteredLocations = allLocations.filter(location => location.type === type);
        
        filteredLocations.forEach(location => {
            const option = document.createElement('option');
            option.value = location.id;
            option.textContent = `${location.name} (${location.id})`;
            locationSelect.appendChild(option);
        });
    }
}

// 分岐を追加
function addBranch() {
    const container = document.getElementById('branchesContainer');
    const noBranches = document.getElementById('noBranches');
    
    const branchIndex = container.children.length;
    
    const branchHtml = `
        <div class="card mb-3 branch-item">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">分岐 ${branchIndex + 1}</h6>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeBranch(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">位置</label>
                        <input type="number" class="form-control" name="branches[${branchIndex}][position]" 
                               value="50" min="1" max="99" required>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">分岐方向</label>
                        <div class="row">
                            <div class="col-4">
                                <select class="form-select" name="branches[${branchIndex}][straight][type]">
                                    <option value="">直進先</option>
                                    <option value="town">町</option>
                                    <option value="road">道路</option>
                                    <option value="dungeon">ダンジョン</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select class="form-select" name="branches[${branchIndex}][left][type]">
                                    <option value="">左折先</option>
                                    <option value="town">町</option>
                                    <option value="road">道路</option>
                                    <option value="dungeon">ダンジョン</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select class="form-select" name="branches[${branchIndex}][right][type]">
                                    <option value="">右折先</option>
                                    <option value="town">町</option>
                                    <option value="road">道路</option>
                                    <option value="dungeon">ダンジョン</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', branchHtml);
    noBranches.style.display = 'none';
}

// 分岐を削除
function removeBranch(button) {
    const branchItem = button.closest('.branch-item');
    branchItem.remove();
    
    const container = document.getElementById('branchesContainer');
    const noBranches = document.getElementById('noBranches');
    
    if (container.children.length === 0) {
        noBranches.style.display = 'block';
    }
}

// ページ読み込み時に接続先を初期化
document.addEventListener('DOMContentLoaded', function() {
    // 既存の接続情報があれば初期化
    @if(isset($road['connections']['start']['type']))
        updateLocationOptions(document.querySelector('select[name="connections[start][type]"]'), 'start');
        setTimeout(() => {
            document.getElementById('start_location_id').value = '{{ $road['connections']['start']['id'] ?? '' }}';
        }, 100);
    @endif
    
    @if(isset($road['connections']['end']['type']))
        updateLocationOptions(document.querySelector('select[name="connections[end][type]"]'), 'end');
        setTimeout(() => {
            document.getElementById('end_location_id').value = '{{ $road['connections']['end']['id'] ?? '' }}';
        }, 100);
    @endif
});
</script>
@endpush