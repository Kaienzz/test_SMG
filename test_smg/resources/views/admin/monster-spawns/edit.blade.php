@extends('admin.layouts.app')

@section('title', 'スポーン編集')
@section('subtitle', $spawn->gameLocation->name . ' のモンスタースポーン編集')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary);">ダッシュボード</a></li>
            <li>/</li>
            <li><a href="{{ route('admin.monster-spawns.index') }}" style="color: var(--admin-primary);">モンスタースポーン管理</a></li>
            <li>/</li>
            <li><a href="{{ route('admin.monster-spawns.show', $spawn->location_id) }}" style="color: var(--admin-primary);">{{ $spawn->gameLocation->name }}</a></li>
            <li>/</li>
            <li>編集</li>
        </ol>
    </nav>

    <!-- 現在の設定表示カード -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">編集中のスポーン設定</h3>
        </div>
        <div class="admin-card-body">
            <div style="display: flex; align-items: center; gap: 2rem;">
                <!-- 現在のモンスター情報 -->
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 3rem;">{{ $spawn->monster->emoji ?? '👹' }}</div>
                    <div>
                        <div style="font-weight: bold; font-size: 1.2rem;">{{ $spawn->monster->name }}</div>
                        <div style="color: var(--admin-secondary); font-size: 0.875rem;">
                            Lv.{{ $spawn->monster->level }} | HP: {{ number_format($spawn->monster->max_hp) }} | EXP: {{ number_format($spawn->monster->experience_reward) }}
                        </div>
                    </div>
                </div>

                <!-- 現在の設定値 -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; flex: 1;">
                    <div>
                        <div style="font-size: 0.875rem; color: var(--admin-secondary);">出現率</div>
                        <div style="font-weight: bold; color: var(--admin-info);">{{ round($spawn->spawn_rate * 100, 1) }}%</div>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; color: var(--admin-secondary);">優先度</div>
                        <div style="font-weight: bold;">{{ $spawn->priority }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; color: var(--admin-secondary);">ステータス</div>
                        <span class="admin-badge admin-badge-{{ $spawn->is_active ? 'success' : 'secondary' }}">
                            {{ $spawn->is_active ? '有効' : '無効' }}
                        </span>
                    </div>
                </div>

                <!-- Location情報 -->
                <div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">Location</div>
                    <div style="font-weight: 500;">{{ $spawn->gameLocation->name }}</div>
                    <span class="admin-badge admin-badge-{{ $spawn->gameLocation->category === 'road' ? 'primary' : 'info' }}">
                        {{ $spawn->gameLocation->category === 'road' ? '道路' : 'ダンジョン' }}
                    </span>
                </div>
            </div>

            @if($spawn->min_level || $spawn->max_level)
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">レベル制限</div>
                <div>
                    @if($spawn->min_level)
                        Lv.{{ $spawn->min_level }}以上
                    @endif
                    @if($spawn->min_level && $spawn->max_level)
                        、
                    @endif
                    @if($spawn->max_level)
                        Lv.{{ $spawn->max_level }}以下
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- 編集フォーム -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">設定変更</h3>
            <a href="{{ route('admin.monster-spawns.show', $spawn->location_id) }}" class="admin-btn admin-btn-secondary">
                ← 戻る
            </a>
        </div>
        <div class="admin-card-body">
            <form method="POST" action="{{ route('admin.monster-spawns.update', $spawn->id) }}" id="spawn-edit-form">
                @csrf
                @method('PUT')

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <!-- 左側: 基本設定 -->
                    <div>
                        <h4 style="margin-bottom: 1.5rem; color: var(--admin-primary);">基本設定</h4>

                        <!-- モンスター選択 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                モンスター <span style="color: var(--admin-danger);">*</span>
                            </label>
                            <select name="monster_id" class="admin-select" required onchange="updateMonsterPreview(this.value)">
                                @foreach($availableMonsters as $monster)
                                <option value="{{ $monster->id }}" 
                                        {{ (old('monster_id', $spawn->monster_id) === $monster->id) ? 'selected' : '' }}
                                        data-level="{{ $monster->level }}"
                                        data-hp="{{ $monster->max_hp }}"
                                        data-attack="{{ $monster->attack }}"
                                        data-defense="{{ $monster->defense }}"
                                        data-exp="{{ $monster->experience_reward }}"
                                        data-emoji="{{ $monster->emoji }}">
                                    {{ $monster->emoji ?? '👹' }} {{ $monster->name }} (Lv.{{ $monster->level }})
                                </option>
                                @endforeach
                            </select>
                            @error('monster_id')
                            <div style="color: var(--admin-danger); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 出現率 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                出現率 <span style="color: var(--admin-danger);">*</span>
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="number" name="spawn_rate" 
                                       value="{{ old('spawn_rate', $spawn->spawn_rate) }}" 
                                       class="admin-input" 
                                       min="0.001" max="1.0" 
                                       step="0.001" 
                                       style="width: 120px;"
                                       oninput="updateRateDisplay(this.value)"
                                       required>
                                <span id="rate-percentage" style="font-size: 1.1rem; font-weight: bold; color: var(--admin-info);">
                                    {{ round((old('spawn_rate', $spawn->spawn_rate)) * 100, 1) }}%
                                </span>
                            </div>
                            @php
                                $otherSpawnsTotalRate = $spawn->gameLocation->monsterSpawns
                                    ->where('id', '!=', $spawn->id)
                                    ->sum('spawn_rate');
                                $maxAllowedRate = 1.0 - $otherSpawnsTotalRate;
                            @endphp
                            <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                0.001 (0.1%) から {{ round($maxAllowedRate, 3) }} ({{ round($maxAllowedRate * 100, 1) }}%) まで
                                @if($otherSpawnsTotalRate > 0)
                                <br><span style="color: var(--admin-info);">他のスポーン総計: {{ round($otherSpawnsTotalRate * 100, 1) }}%</span>
                                @endif
                            </div>
                            @error('spawn_rate')
                            <div style="color: var(--admin-danger); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 優先度 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                優先度 <span style="color: var(--admin-danger);">*</span>
                            </label>
                            <input type="number" name="priority" 
                                   value="{{ old('priority', $spawn->priority) }}" 
                                   class="admin-input" 
                                   min="0" max="999" 
                                   style="width: 120px;"
                                   required>
                            <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                数字が小さいほど優先度が高い
                            </div>
                            @error('priority')
                            <div style="color: var(--admin-danger); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ステータス -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">ステータス</label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="is_active" value="1" 
                                       {{ old('is_active', $spawn->is_active) ? 'checked' : '' }}>
                                <span>有効</span>
                            </label>
                            <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                無効にすると、このスポーン設定は実際のゲーム内で使用されません
                            </div>
                        </div>
                    </div>

                    <!-- 右側: 高度な設定 -->
                    <div>
                        <h4 style="margin-bottom: 1.5rem; color: var(--admin-primary);">高度な設定（オプション）</h4>

                        <!-- レベル制限 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">プレイヤーレベル制限</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: var(--admin-secondary);">最小レベル</label>
                                    <input type="number" name="min_level" 
                                           value="{{ old('min_level', $spawn->min_level) }}" 
                                           class="admin-input" 
                                           min="1" max="999"
                                           placeholder="制限なし">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: var(--admin-secondary);">最大レベル</label>
                                    <input type="number" name="max_level" 
                                           value="{{ old('max_level', $spawn->max_level) }}" 
                                           class="admin-input" 
                                           min="1" max="999"
                                           placeholder="制限なし">
                                </div>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.5rem;">
                                指定したレベル範囲のプレイヤーにのみ、このモンスターが出現します
                            </div>
                            @error('min_level')
                            <div style="color: var(--admin-danger); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</div>
                            @enderror
                            @error('max_level')
                            <div style="color: var(--admin-danger); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 変更プレビュー -->
                        <div id="change-preview" style="padding: 1.5rem; background: #fef3c7; border-radius: 8px; border-left: 4px solid var(--admin-warning);">
                            <h5 style="margin-bottom: 1rem; color: var(--admin-warning);">⚠️ 変更内容</h5>
                            <div id="changes-list" style="font-size: 0.875rem;">
                                <!-- JavaScriptで動的に更新 -->
                            </div>
                        </div>

                        <!-- 選択したモンスタープレビュー -->
                        <div id="monster-preview" style="margin-top: 1rem; padding: 1.5rem; background: #f9fafb; border-radius: 8px;">
                            <h5 style="margin-bottom: 1rem; color: var(--admin-primary);">選択中のモンスター</h5>
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <div id="monster-emoji" style="font-size: 3rem;">👹</div>
                                <div>
                                    <div id="monster-name" style="font-weight: bold; font-size: 1.1rem;"></div>
                                    <div id="monster-level" style="color: var(--admin-secondary); font-size: 0.875rem;"></div>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; font-size: 0.875rem;">
                                <div><strong>HP:</strong> <span id="monster-hp"></span></div>
                                <div><strong>攻撃:</strong> <span id="monster-attack"></span></div>
                                <div><strong>防御:</strong> <span id="monster-defense"></span></div>
                                <div><strong>経験値:</strong> <span id="monster-exp"></span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 送信ボタン -->
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
                    <a href="{{ route('admin.monster-spawns.show', $spawn->location_id) }}" class="admin-btn admin-btn-secondary">
                        キャンセル
                    </a>
                    <button type="submit" class="admin-btn admin-btn-warning">
                        💾 変更を保存
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 危険な操作 -->
    @if(auth()->user()->can('monsters.delete'))
    <div class="admin-card" style="border-left: 4px solid var(--admin-danger);">
        <div class="admin-card-header" style="background-color: #fef2f2;">
            <h3 class="admin-card-title" style="color: var(--admin-danger);">🗑️ 危険な操作</h3>
        </div>
        <div class="admin-card-body">
            <p style="color: var(--admin-danger); margin-bottom: 1rem;">
                このスポーン設定を完全に削除します。この操作は元に戻せません。
            </p>
            <form method="POST" action="{{ route('admin.monster-spawns.destroy', $spawn->id) }}" 
                  style="display: inline;" 
                  onsubmit="return confirm('このスポーン設定を削除しますか？\n\nモンスター: {{ $spawn->monster->name }}\n出現率: {{ round($spawn->spawn_rate * 100, 1) }}%\n\n※この操作は元に戻せません。')">
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn admin-btn-danger">
                    🗑️ このスポーンを削除
                </button>
            </form>
        </div>
    </div>
    @endif
</div>

<script>
// オリジナルの設定値を保存
const originalSettings = {
    monster_id: '{{ $spawn->monster_id }}',
    spawn_rate: {{ $spawn->spawn_rate }},
    priority: {{ $spawn->priority }},
    min_level: {{ $spawn->min_level ?? 'null' }},
    max_level: {{ $spawn->max_level ?? 'null' }},
    is_active: {{ $spawn->is_active ? 'true' : 'false' }}
};

// 出現率表示の更新
function updateRateDisplay(rate) {
    const percentage = Math.round(parseFloat(rate || 0) * 100 * 10) / 10;
    document.getElementById('rate-percentage').textContent = percentage + '%';
    updateChangePreview();
}

// モンスタープレビューの更新
function updateMonsterPreview(monsterId) {
    const select = document.querySelector('select[name="monster_id"]');
    const option = select.querySelector(`option[value="${monsterId}"]`);
    
    if (option && monsterId) {
        document.getElementById('monster-emoji').textContent = option.dataset.emoji || '👹';
        document.getElementById('monster-name').textContent = option.textContent.replace(/^[^\s]+\s/, '');
        document.getElementById('monster-level').textContent = `Level ${option.dataset.level}`;
        document.getElementById('monster-hp').textContent = parseInt(option.dataset.hp).toLocaleString();
        document.getElementById('monster-attack').textContent = parseInt(option.dataset.attack).toLocaleString();
        document.getElementById('monster-defense').textContent = parseInt(option.dataset.defense).toLocaleString();
        document.getElementById('monster-exp').textContent = parseInt(option.dataset.exp).toLocaleString();
    }
    updateChangePreview();
}

// 変更プレビューの更新
function updateChangePreview() {
    const form = document.getElementById('spawn-edit-form');
    const changes = [];
    
    // モンスター変更チェック
    const currentMonsterId = form.querySelector('select[name="monster_id"]').value;
    if (currentMonsterId !== originalSettings.monster_id) {
        const option = form.querySelector(`option[value="${currentMonsterId}"]`);
        changes.push(`モンスター → ${option.textContent}`);
    }
    
    // 出現率変更チェック
    const currentRate = parseFloat(form.querySelector('input[name="spawn_rate"]').value);
    if (Math.abs(currentRate - originalSettings.spawn_rate) > 0.0001) {
        changes.push(`出現率 → ${Math.round(currentRate * 100 * 10) / 10}%`);
    }
    
    // 優先度変更チェック
    const currentPriority = parseInt(form.querySelector('input[name="priority"]').value);
    if (currentPriority !== originalSettings.priority) {
        changes.push(`優先度 → ${currentPriority}`);
    }
    
    // レベル制限変更チェック
    const currentMinLevel = form.querySelector('input[name="min_level"]').value;
    const currentMaxLevel = form.querySelector('input[name="max_level"]').value;
    const minChanged = (currentMinLevel || null) != originalSettings.min_level;
    const maxChanged = (currentMaxLevel || null) != originalSettings.max_level;
    
    if (minChanged || maxChanged) {
        let levelText = 'レベル制限 → ';
        if (currentMinLevel && currentMaxLevel) {
            levelText += `Lv.${currentMinLevel}-${currentMaxLevel}`;
        } else if (currentMinLevel) {
            levelText += `Lv.${currentMinLevel}以上`;
        } else if (currentMaxLevel) {
            levelText += `Lv.${currentMaxLevel}以下`;
        } else {
            levelText += '制限なし';
        }
        changes.push(levelText);
    }
    
    // ステータス変更チェック
    const currentActive = form.querySelector('input[name="is_active"]').checked;
    if (currentActive !== originalSettings.is_active) {
        changes.push(`ステータス → ${currentActive ? '有効' : '無効'}`);
    }
    
    // 変更リストの表示
    const changesList = document.getElementById('changes-list');
    if (changes.length > 0) {
        changesList.innerHTML = changes.map(change => `• ${change}`).join('<br>');
        document.getElementById('change-preview').style.display = 'block';
    } else {
        changesList.innerHTML = '変更はありません';
    }
}

// フォーム送信時の確認
document.getElementById('spawn-edit-form').addEventListener('submit', function(e) {
    const confirmation = confirm('スポーン設定を変更しますか？');
    if (!confirmation) {
        e.preventDefault();
    }
});

// フォームの変更を監視
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('spawn-edit-form');
    const inputs = form.querySelectorAll('input, select');
    
    // 初期プレビューを表示
    updateMonsterPreview(form.querySelector('select[name="monster_id"]').value);
    updateChangePreview();
    
    // 変更監視
    inputs.forEach(input => {
        input.addEventListener('change', updateChangePreview);
        input.addEventListener('input', updateChangePreview);
    });
});
</script>

<style>
/* 管理画面固有のスタイル */
.admin-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.admin-badge-primary { background-color: #dbeafe; color: #1d4ed8; }
.admin-badge-secondary { background-color: #f1f5f9; color: #475569; }
.admin-badge-success { background-color: #dcfce7; color: #166534; }
.admin-badge-warning { background-color: #fef3c7; color: #d97706; }
.admin-badge-danger { background-color: #fee2e2; color: #dc2626; }
.admin-badge-info { background-color: #e0f2fe; color: #0369a1; }
</style>
@endsection