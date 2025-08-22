@extends('admin.layouts.app')

@section('title', 'ロケーション接続作成')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">ロケーション接続作成</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.route-connections.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">接続情報</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.route-connections.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="source_location_id" class="form-label">出発ロケーション <span class="text-danger">*</span></label>
                            <select class="form-select @error('source_location_id') is-invalid @enderror" 
                                    id="source_location_id" name="source_location_id" required>
                                <option value="">選択してください</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" 
                                            {{ old('source_location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }} ({{ $location->category }})
                                    </option>
                                @endforeach
                            </select>
                            @error('source_location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="target_location_id" class="form-label">到達ロケーション <span class="text-danger">*</span></label>
                            <select class="form-select @error('target_location_id') is-invalid @enderror" 
                                    id="target_location_id" name="target_location_id" required>
                                <option value="">選択してください</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" 
                                            {{ old('target_location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }} ({{ $location->category }})
                                    </option>
                                @endforeach
                            </select>
                            @error('target_location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="connection_type" class="form-label">接続タイプ <span class="text-danger">*</span></label>
                            <select class="form-select @error('connection_type') is-invalid @enderror" 
                                    id="connection_type" name="connection_type" required>
                                <option value="">選択してください</option>
                                @foreach($connectionTypes as $type)
                                    <option value="{{ $type }}" 
                                            {{ old('connection_type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('connection_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="position" class="form-label">位置</label>
                            <input type="number" class="form-control @error('position') is-invalid @enderror" 
                                   id="position" name="position" value="{{ old('position') }}" min="0">
                            <div class="form-text">接続の並び順を指定します</div>
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="direction" class="form-label">方向</label>
                            <input type="text" class="form-control @error('direction') is-invalid @enderror" 
                                   id="direction" name="direction" value="{{ old('direction') }}" placeholder="例: 北、南東">
                            <div class="form-text">移動方向の説明</div>
                            @error('direction')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.route-connections.index') }}" class="btn btn-secondary me-2">キャンセル</a>
                            <button type="submit" class="btn btn-primary">作成</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">接続タイプについて</h6>
                </div>
                <div class="card-body">
                    <dl>
                        <dt>start</dt>
                        <dd>開始地点への接続</dd>
                        <dt>end</dt>
                        <dd>終了地点への接続</dd>
                        <dt>bidirectional</dt>
                        <dd>双方向接続</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 同じロケーションの選択を防ぐ
    const sourceSelect = document.getElementById('source_location_id');
    const targetSelect = document.getElementById('target_location_id');
    
    function updateOptions() {
        const sourceValue = sourceSelect.value;
        const targetValue = targetSelect.value;
        
        Array.from(targetSelect.options).forEach(option => {
            if (option.value === sourceValue && option.value !== '') {
                option.disabled = true;
            } else {
                option.disabled = false;
            }
        });
        
        Array.from(sourceSelect.options).forEach(option => {
            if (option.value === targetValue && option.value !== '') {
                option.disabled = true;
            } else {
                option.disabled = false;
            }
        });
    }
    
    sourceSelect.addEventListener('change', updateOptions);
    targetSelect.addEventListener('change', updateOptions);
    updateOptions();
});
</script>
@endsection