@extends('admin.layouts.app')

@section('title', '新規フロア作成')
@section('subtitle', $dungeon->dungeon_name . ' に新しいフロアを追加')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; list-style: none; margin: 0; padding: 0; font-size: 0.875rem;">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary); text-decoration: none;">ダッシュボード</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.dungeons.index') }}" style="color: var(--admin-primary); text-decoration: none;">Dungeon管理</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.dungeons.show', $dungeon->id) }}" style="color: var(--admin-primary); text-decoration: none;">{{ $dungeon->dungeon_name }}</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li><a href="{{ route('admin.dungeons.floors', $dungeon->id) }}" style="color: var(--admin-primary); text-decoration: none;">フロア管理</a></li>
            <li style="margin: 0 0.5rem; color: var(--admin-secondary);">/</li>
            <li style="color: var(--admin-secondary);">フロア作成</li>
        </ol>
    </nav>

    <!-- ダンジョン情報ヘッダー -->
    <div class="admin-card" style="margin-bottom: 2rem; background: linear-gradient(135deg, #f0f9ff, #e0f7fa);">
        <div class="admin-card-body">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: var(--admin-primary);">
                        <i class="fas fa-dungeon"></i> {{ $dungeon->dungeon_name }}
                    </h2>
                    <p style="margin: 0.5rem 0 0 0; color: var(--admin-secondary);">
                        <code style="background: rgba(37, 99, 235, 0.1); padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                            {{ $dungeon->dungeon_id }}
                        </code>
                        <span style="margin-left: 1rem;">現在のフロア数: {{ $dungeon->floors->count() }}</span>
                    </p>
                </div>
                <div>
                    <span class="admin-badge admin-badge-info">
                        新規フロア: {{ $dungeon->floors->count() + 1 }}番目
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ヘッダー -->
    <div style="margin-bottom: 2rem;">
        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 600;">
            <i class="fas fa-plus-circle"></i> 新規フロア作成
        </h3>
        <p style="margin-top: 0.5rem; color: var(--admin-secondary);">
            新しいフロアの情報を入力してください。フロアIDは自動的に「{{ $dungeon->dungeon_id }}_」で始まります。
        </p>
    </div>

    <!-- エラーメッセージ -->
    @if ($errors->any())
        <div class="admin-alert admin-alert-danger" style="margin-bottom: 2rem;">
            <h4 style="margin: 0 0 1rem 0;">入力内容にエラーがあります</h4>
            <ul style="margin: 0; padding-left: 1.5rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- フォーム -->
    <form method="POST" action="{{ route('admin.dungeons.floors.store', $dungeon->id) }}">
        @csrf
        
        <div style="display: grid; gap: 2rem;">
            
            <!-- 基本情報 -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-layer-group"></i> フロア基本情報
                    </h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; gap: 1.5rem;">
                        
                        <!-- フロアID -->
                        <div>
                            <label for="id" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                フロアID <span style="color: var(--admin-danger);">*</span>
                            </label>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="padding: 0.75rem; background: var(--admin-bg); border: 1px solid var(--admin-border); border-radius: 0.5rem 0 0 0.5rem; font-family: monospace; color: var(--admin-secondary);">
                                    {{ $dungeon->dungeon_id }}_
                                </span>
                                <input type="text" 
                                       id="floor_suffix" 
                                       name="floor_suffix" 
                                       value="{{ old('floor_suffix') }}" 
                                       class="admin-input"
                                       style="border-radius: 0 0.5rem 0.5rem 0; flex: 1;"
                                       placeholder="1f"
                                       required>
                                <input type="hidden" 
                                       id="id" 
                                       name="id" 
                                       value="{{ old('id') }}">
                            </div>
                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                                フロアIDは「{{ $dungeon->dungeon_id }}_」の後に続く部分を入力してください。<br>
                                例: 1f, 2f, b1, boss_room など
                            </div>
                            @error('id')
                            <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <!-- フロア名 -->
                        <div>
                            <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                フロア名 <span style="color: var(--admin-danger);">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   class="admin-input"
                                   placeholder="例: ピラミッド1階"
                                   required>
                            @error('name')
                            <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <!-- フロア説明 -->
                        <div>
                            <label for="description" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                フロア説明
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      class="admin-textarea"
                                      rows="3"
                                      placeholder="このフロアの詳細説明を入力してください">{{ old('description') }}</textarea>
                            @error('description')
                            <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- ゲーム設定 -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-gamepad"></i> ゲーム設定
                    </h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        
                        <!-- 長さ -->
                        <div>
                            <label for="length" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                長さ <span style="color: var(--admin-danger);">*</span>
                            </label>
                            <input type="number" 
                                   id="length" 
                                   name="length" 
                                   value="{{ old('length', 50) }}" 
                                   class="admin-input"
                                   min="1" 
                                   max="1000"
                                   placeholder="50"
                                   required>
                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                                1-1000の範囲で入力
                            </div>
                            @error('length')
                            <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <!-- 難易度 -->
                        <div>
                            <label for="difficulty" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                難易度 <span style="color: var(--admin-danger);">*</span>
                            </label>
                            <select id="difficulty" name="difficulty" class="admin-select" required>
                                <option value="">選択してください</option>
                                <option value="easy" {{ old('difficulty') === 'easy' ? 'selected' : '' }}>
                                    簡単
                                </option>
                                <option value="normal" {{ old('difficulty', 'normal') === 'normal' ? 'selected' : '' }}>
                                    普通
                                </option>
                                <option value="hard" {{ old('difficulty') === 'hard' ? 'selected' : '' }}>
                                    困難
                                </option>
                            </select>
                            @error('difficulty')
                            <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <!-- エンカウント率 -->
                        <div>
                            <label for="encounter_rate" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                エンカウント率
                            </label>
                            <input type="number" 
                                   id="encounter_rate" 
                                   name="encounter_rate" 
                                   value="{{ old('encounter_rate', 0.4) }}" 
                                   class="admin-input"
                                   min="0" 
                                   max="1"
                                   step="0.01"
                                   placeholder="0.40">
                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                                0.00-1.00の範囲（例: 0.40 = 40%）
                            </div>
                            @error('encounter_rate')
                            <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 次のステップ案内 -->
            <div class="admin-card" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7);">
                <div class="admin-card-header">
                    <h3 class="admin-card-title" style="color: var(--admin-success);">
                        <i class="fas fa-info-circle"></i> フロア作成後の設定
                    </h3>
                </div>
                <div class="admin-card-body">
                    <div style="color: var(--admin-secondary);">
                        フロアを作成した後は、以下の設定を行ってください：
                    </div>
                    <ol style="margin: 1rem 0; padding-left: 1.5rem; color: var(--admin-secondary);">
                        <li>モンスタースポーンの設定</li>
                        <li>他のフロアとの接続設定</li>
                        <li>特殊アクション・イベントの設定（任意）</li>
                        <li>バランステストの実施</li>
                    </ol>
                </div>
            </div>

        </div>

        <!-- フォーム送信ボタン -->
        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
            <a href="{{ route('admin.dungeons.floors', $dungeon->id) }}" class="admin-btn admin-btn-secondary">
                <i class="fas fa-times"></i> キャンセル
            </a>
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="fas fa-save"></i> フロア作成
            </button>
        </div>
        
    </form>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dungeonId = '{{ $dungeon->dungeon_id }}';
    const floorSuffixInput = document.getElementById('floor_suffix');
    const idInput = document.getElementById('id');
    const nameInput = document.getElementById('name');
    
    // フロアIDを自動生成
    function updateFloorId() {
        const suffix = floorSuffixInput.value;
        if (suffix) {
            idInput.value = dungeonId + '_' + suffix;
        } else {
            idInput.value = '';
        }
    }
    
    // フロア名を自動生成
    function updateFloorName() {
        const suffix = floorSuffixInput.value;
        if (suffix && !nameInput.value) {
            let suggestedName = '{{ $dungeon->dungeon_name }}';
            
            // suffixからフロア名を推測
            if (suffix.match(/^\d+f?$/)) {
                suggestedName += suffix.replace('f', '') + '階';
            } else if (suffix.includes('boss')) {
                suggestedName += 'ボス部屋';
            } else if (suffix.includes('treasure')) {
                suggestedName += '宝物庫';
            } else {
                suggestedName += ' ' + suffix;
            }
            
            nameInput.value = suggestedName;
        }
    }
    
    floorSuffixInput.addEventListener('input', function() {
        updateFloorId();
        updateFloorName();
    });
    
    // 初期値設定
    if (floorSuffixInput.value) {
        updateFloorId();
    }

    // フォームバリデーション
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const floorSuffix = floorSuffixInput.value;
        const name = nameInput.value;
        const length = document.getElementById('length').value;
        const difficulty = document.getElementById('difficulty').value;
        
        // 必須項目チェック
        if (!floorSuffix || !name || !length || !difficulty) {
            e.preventDefault();
            alert('必須項目をすべて入力してください。');
            return;
        }
        
        // フロアID形式チェック
        if (!/^[a-zA-Z0-9_]+$/.test(floorSuffix)) {
            e.preventDefault();
            alert('フロアIDサフィックスは英数字とアンダースコアのみ使用してください。');
            return;
        }
        
        // 長さチェック
        if (length < 1 || length > 1000) {
            e.preventDefault();
            alert('長さは1-1000の範囲で入力してください。');
            return;
        }
        
        // エンカウント率チェック
        const encounterRate = document.getElementById('encounter_rate').value;
        if (encounterRate && (encounterRate < 0 || encounterRate > 1)) {
            e.preventDefault();
            alert('エンカウント率は0.00-1.00の範囲で入力してください。');
            return;
        }
    });

    // リアルタイムプレビュー
    function updatePreview() {
        const suffix = floorSuffixInput.value;
        const fullId = suffix ? dungeonId + '_' + suffix : '';
        
        // ID表示の更新
        let preview = document.querySelector('.id-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'id-preview';
            preview.style.marginTop = '0.5rem';
            preview.style.padding = '0.5rem';
            preview.style.background = 'var(--admin-bg)';
            preview.style.borderRadius = '0.25rem';
            preview.style.fontSize = '0.875rem';
            floorSuffixInput.parentNode.appendChild(preview);
        }
        
        if (fullId) {
            preview.innerHTML = `<strong>完全なフロアID:</strong> <code>${fullId}</code>`;
            preview.style.color = 'var(--admin-success)';
        } else {
            preview.innerHTML = '<strong>フロアIDプレビュー:</strong> <span style="color: var(--admin-secondary);">入力してください</span>';
        }
    }
    
    floorSuffixInput.addEventListener('input', updatePreview);
    updatePreview(); // 初期表示
});
</script>

<style>
.id-preview {
    transition: all 0.2s ease;
}

.id-preview code {
    background: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    border: 1px solid var(--admin-border);
}
</style>
@endsection