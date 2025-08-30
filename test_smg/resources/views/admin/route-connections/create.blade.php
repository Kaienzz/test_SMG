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

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="source_position" class="form-label">出発位置</label>
                                    <input type="number" class="form-control @error('source_position') is-invalid @enderror" 
                                           id="source_position" name="source_position" value="{{ old('source_position') }}" 
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
                                           id="target_position" name="target_position" value="{{ old('target_position') }}" 
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
                                <option value="normal" {{ old('edge_type') == 'normal' ? 'selected' : '' }}>通常</option>
                                <option value="branch" {{ old('edge_type') == 'branch' ? 'selected' : '' }}>分岐</option>
                                <option value="portal" {{ old('edge_type') == 'portal' ? 'selected' : '' }}>ポータル</option>
                                <option value="exit" {{ old('edge_type') == 'exit' ? 'selected' : '' }}>出口</option>
                                <option value="enter" {{ old('edge_type') == 'enter' ? 'selected' : '' }}>入口</option>
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
                                    <option value="{{ $key }}" {{ old('action_label') == $key ? 'selected' : '' }}>
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
                                    <option value="{{ $key }}" {{ old('keyboard_shortcut') == $key ? 'selected' : '' }}>
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
                                       {{ old('is_enabled', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_enabled">
                                    この接続を有効にする
                                </label>
                                @error('is_enabled')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="create_bidirectional" name="create_bidirectional" value="1">
                                <label class="form-check-label" for="create_bidirectional">
                                    <strong>反対方向の接続も同時に作成する</strong>
                                </label>
                                <div class="form-text">
                                    チェックすると、入力内容を反転させた接続も自動で作成されます（双方向接続）
                                </div>
                            </div>
                        </div>

                        <!-- 反対方向接続のプレビュー -->
                        <div class="mb-3" id="reverse-connection-preview" style="display: none;">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-eye"></i> 反対方向接続のプレビュー
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong>出発ロケーション:</strong>
                                                <span id="reverse-source-location" class="text-primary">-</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>到達ロケーション:</strong>
                                                <span id="reverse-target-location" class="text-primary">-</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>出発位置:</strong>
                                                <span id="reverse-source-position" class="text-info">-</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>到着位置:</strong>
                                                <span id="reverse-target-position" class="text-info">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong>エッジタイプ:</strong>
                                                <span id="reverse-edge-type" class="badge bg-secondary">-</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>アクションラベル:</strong>
                                                <div>
                                                    <code id="reverse-action-label-code">-</code>
                                                    <br><small id="reverse-action-label-text" class="text-muted">-</small>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <strong>キーボードショートカット:</strong>
                                                <span id="reverse-keyboard-shortcut" class="badge bg-dark">-</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>状態:</strong>
                                                <span id="reverse-is-enabled" class="badge bg-success">有効</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <div class="alert alert-info py-2 mb-0">
                                            <i class="fas fa-info-circle"></i>
                                            この反対方向接続は、元の接続作成と同時に自動で作成されます。
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legacy fields for backward compatibility -->
                        <div class="mb-3">
                            <label for="direction" class="form-label">方向 (レガシー)</label>
                            <input type="text" class="form-control @error('direction') is-invalid @enderror" 
                                   id="direction" name="direction" value="{{ old('direction') }}" placeholder="例: 北、南東">
                            <div class="form-text small text-muted">互換性のために残されています</div>
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
                    <h6 class="mb-0">接続設定ガイド</h6>
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
                    
                    <h6 class="h6">アクションラベル</h6>
                    <p class="small text-muted">
                        プレイヤーに表示される移動アクション名。空の場合は自動設定されます。
                    </p>
                    
                    <h6 class="h6">キーボードショートカット</h6>
                    <p class="small text-muted">
                        矢印キーでの移動に対応。同じ出発地点での重複は不可です。
                    </p>
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

    // 双方向接続機能
    const bidirectionalCheckbox = document.getElementById('create_bidirectional');
    const previewArea = document.getElementById('reverse-connection-preview');
    
    // 反対方向のマッピング
    const oppositeActionLabels = {
        'turn_right': 'turn_left',
        'turn_left': 'turn_right',
        'move_north': 'move_south',
        'move_south': 'move_north',
        'move_west': 'move_east',
        'move_east': 'move_west',
        'enter_dungeon': 'exit_dungeon',
        'exit_dungeon': 'enter_dungeon'
    };
    
    const oppositeKeyboardShortcuts = {
        'up': 'down',
        'down': 'up',
        'left': 'right',
        'right': 'left'
    };
    
    const oppositeEdgeTypes = {
        'exit': 'enter',
        'enter': 'exit',
        'normal': 'normal',
        'branch': 'branch',
        'portal': 'portal'
    };
    
    const actionLabelTexts = {
        'turn_right': '右折する',
        'turn_left': '左折する',
        'move_north': '北に移動する',
        'move_south': '南に移動する',
        'move_west': '西に移動する',
        'move_east': '東に移動する',
        'enter_dungeon': 'ダンジョンに入る',
        'exit_dungeon': 'ダンジョンから出る'
    };
    
    const keyboardShortcutDisplays = {
        'up': '↑',
        'down': '↓',
        'left': '←',
        'right': '→'
    };

    // チェックボックスの変更を監視
    bidirectionalCheckbox.addEventListener('change', function() {
        if (this.checked) {
            previewArea.style.display = 'block';
            updateReversePreview();
        } else {
            previewArea.style.display = 'none';
        }
    });

    // フォームフィールドの変更を監視
    const watchedFields = [
        'source_location_id', 'target_location_id', 
        'source_position', 'target_position',
        'edge_type', 'action_label', 'keyboard_shortcut', 'is_enabled'
    ];
    
    watchedFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', function() {
                if (bidirectionalCheckbox.checked) {
                    updateReversePreview();
                }
            });
        }
    });

    function updateReversePreview() {
        // ロケーション名の取得
        const sourceOption = sourceSelect.options[sourceSelect.selectedIndex];
        const targetOption = targetSelect.options[targetSelect.selectedIndex];
        
        const sourceName = sourceOption && sourceOption.value ? sourceOption.textContent : '-';
        const targetName = targetOption && targetOption.value ? targetOption.textContent : '-';
        
        // 反対方向では source ↔ target が入れ替わる
        document.getElementById('reverse-source-location').textContent = targetName;
        document.getElementById('reverse-target-location').textContent = sourceName;
        
        // 位置の入れ替え
        const sourcePosition = document.getElementById('source_position').value;
        const targetPosition = document.getElementById('target_position').value;
        
        document.getElementById('reverse-source-position').textContent = targetPosition || '-';
        document.getElementById('reverse-target-position').textContent = sourcePosition || '-';
        
        // エッジタイプの反対変換
        const edgeType = document.getElementById('edge_type').value;
        const reverseEdgeType = oppositeEdgeTypes[edgeType] || edgeType || '-';
        document.getElementById('reverse-edge-type').textContent = reverseEdgeType;
        
        // アクションラベルの反対変換
        const actionLabel = document.getElementById('action_label').value;
        const reverseActionLabel = oppositeActionLabels[actionLabel];
        
        if (reverseActionLabel) {
            document.getElementById('reverse-action-label-code').textContent = reverseActionLabel;
            document.getElementById('reverse-action-label-text').textContent = actionLabelTexts[reverseActionLabel];
        } else {
            document.getElementById('reverse-action-label-code').textContent = '-';
            document.getElementById('reverse-action-label-text').textContent = '自動設定';
        }
        
        // キーボードショートカットの反対変換
        const keyboardShortcut = document.getElementById('keyboard_shortcut').value;
        const reverseKeyboardShortcut = oppositeKeyboardShortcuts[keyboardShortcut];
        
        if (reverseKeyboardShortcut) {
            document.getElementById('reverse-keyboard-shortcut').textContent = keyboardShortcutDisplays[reverseKeyboardShortcut];
        } else {
            document.getElementById('reverse-keyboard-shortcut').textContent = '-';
        }
        
        // 有効性の同期
        const isEnabled = document.getElementById('is_enabled').checked;
        const enabledBadge = document.getElementById('reverse-is-enabled');
        
        if (isEnabled) {
            enabledBadge.textContent = '有効';
            enabledBadge.className = 'badge bg-success';
        } else {
            enabledBadge.textContent = '無効';
            enabledBadge.className = 'badge bg-warning';
        }
    }
});
</script>
@endsection