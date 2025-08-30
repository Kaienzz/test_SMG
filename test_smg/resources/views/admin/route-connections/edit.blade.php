@extends('admin.layouts.app')

@section('title', 'ロケーション接続編集')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">ロケーション接続編集</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.route-connections.show', $connection->id) }}" class="btn btn-outline-info me-2">
                <i class="fas fa-eye"></i> 詳細表示
            </a>
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
                    <form action="{{ route('admin.route-connections.update', $connection->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="source_location_id" class="form-label">出発ロケーション <span class="text-danger">*</span></label>
                            <select class="form-select @error('source_location_id') is-invalid @enderror" 
                                    id="source_location_id" name="source_location_id" required>
                                <option value="">選択してください</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" 
                                            {{ old('source_location_id', $connection->source_location_id) == $location->id ? 'selected' : '' }}>
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
                                            {{ old('target_location_id', $connection->target_location_id) == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }} ({{ $location->category }})
                                    </option>
                                @endforeach
                            </select>
                            @error('target_location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="source_position" class="form-label">出発位置</label>
                                    <input type="number" class="form-control @error('source_position') is-invalid @enderror" 
                                           id="source_position" name="source_position" 
                                           value="{{ old('source_position', $connection->source_position) }}" 
                                           min="0" max="100" placeholder="0-100">
                                    <div class="form-text">道路・ダンジョンの場合は必須 (0-100)</div>
                                    @error('source_position')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="target_position" class="form-label">到着位置</label>
                                    <input type="number" class="form-control @error('target_position') is-invalid @enderror" 
                                           id="target_position" name="target_position" 
                                           value="{{ old('target_position', $connection->target_position) }}" 
                                           min="0" max="100" placeholder="0-100">
                                    <div class="form-text">道路・ダンジョンの場合は必須 (0-100)</div>
                                    @error('target_position')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edge_type" class="form-label">エッジタイプ</label>
                            <select class="form-select @error('edge_type') is-invalid @enderror" 
                                    id="edge_type" name="edge_type">
                                <option value="">選択してください</option>
                                <option value="normal" {{ old('edge_type', $connection->edge_type) == 'normal' ? 'selected' : '' }}>通常</option>
                                <option value="branch" {{ old('edge_type', $connection->edge_type) == 'branch' ? 'selected' : '' }}>分岐</option>
                                <option value="portal" {{ old('edge_type', $connection->edge_type) == 'portal' ? 'selected' : '' }}>ポータル</option>
                                <option value="exit" {{ old('edge_type', $connection->edge_type) == 'exit' ? 'selected' : '' }}>出口</option>
                                <option value="enter" {{ old('edge_type', $connection->edge_type) == 'enter' ? 'selected' : '' }}>入口</option>
                            </select>
                            @error('edge_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="action_label" class="form-label">アクションラベル</label>
                            <select class="form-select @error('action_label') is-invalid @enderror" 
                                    id="action_label" name="action_label">
                                <option value="">自動設定</option>
                                @foreach(\App\Helpers\ActionLabel::getAllActionLabels() as $key => $label)
                                    <option value="{{ $key }}" {{ old('action_label', $connection->action_label) == $key ? 'selected' : '' }}>
                                        {{ $label }} ({{ $key }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">プレイヤーに表示されるアクション名</div>
                            @error('action_label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="keyboard_shortcut" class="form-label">キーボードショートカット</label>
                            <select class="form-select @error('keyboard_shortcut') is-invalid @enderror" 
                                    id="keyboard_shortcut" name="keyboard_shortcut">
                                <option value="">なし</option>
                                @foreach(\App\Helpers\ActionLabel::getAllKeyboardShortcuts() as $key => $display)
                                    <option value="{{ $key }}" {{ old('keyboard_shortcut', $connection->keyboard_shortcut) == $key ? 'selected' : '' }}>
                                        {{ $display }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">矢印キーでの移動に対応</div>
                            @error('keyboard_shortcut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input @error('is_enabled') is-invalid @enderror" 
                                       type="checkbox" id="is_enabled" name="is_enabled" value="1" 
                                       {{ old('is_enabled', $connection->is_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_enabled">
                                    この接続を有効にする
                                </label>
                                @error('is_enabled')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Legacy fields for backward compatibility -->
                        <div class="mb-3">
                            <label for="direction" class="form-label">方向 (レガシー)</label>
                            <input type="text" class="form-control @error('direction') is-invalid @enderror" 
                                   id="direction" name="direction" value="{{ old('direction', $connection->direction) }}" placeholder="例: 北、南東">
                            <div class="form-text small text-muted">互換性のために残されています</div>
                            @error('direction')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.route-connections.show', $connection->id) }}" class="btn btn-secondary me-2">キャンセル</a>
                            <button type="submit" class="btn btn-primary">更新</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">現在の接続</h6>
                </div>
                <div class="card-body">
                    <div class="connection-visual">
                        <div class="location-box mb-2">
                            <strong>{{ $connection->sourceLocation?->name ?? 'Unknown' }}</strong>
                            <br>
                            <small class="text-muted">{{ $connection->sourceLocation?->category ?? 'N/A' }}</small>
                            @if($connection->source_position !== null)
                                <br><span class="badge bg-info">位置: {{ $connection->source_position }}</span>
                            @endif
                        </div>
                        
                        <div class="text-center my-2">
                            @if($connection->edge_type)
                                <div class="small text-muted">{{ $connection->edge_type }}</div>
                            @endif
                            <i class="fas fa-arrow-down"></i>
                            @if($connection->action_label)
                                <div class="small text-primary">
                                    {{ \App\Helpers\ActionLabel::getActionLabelText($connection->action_label) }}
                                </div>
                            @endif
                            @if($connection->keyboard_shortcut)
                                <span class="badge bg-dark">
                                    {{ \App\Helpers\ActionLabel::getKeyboardShortcutDisplay($connection->keyboard_shortcut) }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="location-box">
                            <strong>{{ $connection->targetLocation?->name ?? 'Unknown' }}</strong>
                            <br>
                            <small class="text-muted">{{ $connection->targetLocation?->category ?? 'N/A' }}</small>
                            @if($connection->target_position !== null)
                                <br><span class="badge bg-info">位置: {{ $connection->target_position }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <hr>
                    
                    <dl class="row small">
                        <dt class="col-sm-5">接続ID</dt>
                        <dd class="col-sm-7">{{ $connection->id }}</dd>
                        
                        <dt class="col-sm-5">状態</dt>
                        <dd class="col-sm-7">
                            @if($connection->is_enabled ?? true)
                                <span class="badge bg-success">有効</span>
                            @else
                                <span class="badge bg-warning">無効</span>
                            @endif
                        </dd>
                        
                        <dt class="col-sm-5">エッジタイプ</dt>
                        <dd class="col-sm-7">{{ $connection->edge_type ?: '未設定' }}</dd>
                        
                        <dt class="col-sm-5">アクション</dt>
                        <dd class="col-sm-7">
                            {{ $connection->action_label ? \App\Helpers\ActionLabel::getActionLabelText($connection->action_label) : '自動' }}
                        </dd>
                        
                        <dt class="col-sm-5">ショートカット</dt>
                        <dd class="col-sm-7">
                            {{ $connection->keyboard_shortcut ? \App\Helpers\ActionLabel::getKeyboardShortcutDisplay($connection->keyboard_shortcut) : 'なし' }}
                        </dd>
                        
                        <dt class="col-sm-5">作成日時</dt>
                        <dd class="col-sm-7">{{ $connection->created_at->format('Y-m-d H:i:s') }}</dd>
                        
                        <dt class="col-sm-5">更新日時</dt>
                        <dd class="col-sm-7">{{ $connection->updated_at->format('Y-m-d H:i:s') }}</dd>
                    </dl>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">設定ガイド</h6>
                </div>
                <div class="card-body">
                    <h6 class="h6">位置設定</h6>
                    <ul class="small">
                        <li><strong>町</strong>: 位置設定は不要</li>
                        <li><strong>道路・ダンジョン</strong>: 0-100の範囲で必須</li>
                        <li><strong>0/100</strong>: 端点位置 (≤, ≥ 比較)</li>
                        <li><strong>中間値</strong>: 完全一致比較</li>
                    </ul>
                    
                    <h6 class="h6">エッジタイプ</h6>
                    <dl class="small">
                        <dt>normal</dt>
                        <dd>通常の移動</dd>
                        <dt>branch</dt>
                        <dd>分岐点</dd>
                        <dt>portal</dt>
                        <dd>瞬間移動</dd>
                        <dt>exit/enter</dt>
                        <dd>出入口</dd>
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
    const currentConnectionId = {{ $connection->id }};
    
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