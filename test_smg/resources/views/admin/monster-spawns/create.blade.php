@extends('admin.layouts.app')

@section('title', 'スポーン追加')
@section('subtitle', $location->name . ' に新しいモンスタースポーンを追加')

@section('content')
<div class="admin-content-container">
    
    <!-- パンくずリスト -->
    <nav style="margin-bottom: 2rem;">
        <ol style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
            <li><a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary);">ダッシュボード</a></li>
            <li>/</li>
            <li><a href="{{ route('admin.monster-spawns.index') }}" style="color: var(--admin-primary);">モンスタースポーン管理</a></li>
            <li>/</li>
            <li><a href="{{ route('admin.monster-spawns.show', $location->id) }}" style="color: var(--admin-primary);">{{ $location->name }}</a></li>
            <li>/</li>
            <li>新規追加</li>
        </ol>
    </nav>

    <!-- Location情報カード -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">対象Location</h3>
        </div>
        <div class="admin-card-body">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div>
                    <div style="font-weight: 500; font-size: 1.1rem;">{{ $location->name }}</div>
                    <div style="font-size: 0.875rem; color: var(--admin-secondary);">{{ $location->id }}</div>
                </div>
                <span class="admin-badge admin-badge-{{ $location->category === 'road' ? 'primary' : 'info' }}">
                    {{ $location->category === 'road' ? '道路' : ($location->category === 'dungeon' ? 'ダンジョン' : $location->category) }}
                </span>
            </div>
            
            @if($location->monsterSpawns->count() > 0)
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-bottom: 0.5rem;">
                    既存のスポーン ({{ $location->monsterSpawns->count() }}件)
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @foreach($location->monsterSpawns as $existingSpawn)
                    <span class="admin-badge admin-badge-secondary" style="font-size: 0.75rem;">
                        {{ $existingSpawn->monster->emoji ?? '👹' }} {{ $existingSpawn->monster->name }} ({{ round($existingSpawn->spawn_rate * 100, 1) }}%)
                    </span>
                    @endforeach
                </div>
                @php
                    $currentTotal = $location->monsterSpawns->sum('spawn_rate');
                    $remaining = 1.0 - $currentTotal;
                @endphp
                <div style="margin-top: 0.5rem; font-size: 0.875rem;">
                    <span style="color: var(--admin-secondary);">現在の総出現率: </span>
                    <span style="font-weight: bold; color: {{ $currentTotal >= 0.99 ? 'var(--admin-danger)' : 'var(--admin-info)' }};">
                        {{ round($currentTotal * 100, 1) }}%
                    </span>
                    @if($remaining > 0.01)
                    <span style="color: var(--admin-success); margin-left: 0.5rem;">
                        (残り: {{ round($remaining * 100, 1) }}%)
                    </span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- スポーン作成フォーム -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">新しいスポーン設定</h3>
            <a href="{{ route('admin.monster-spawns.show', $location->id) }}" class="admin-btn admin-btn-secondary">
                ← 戻る
            </a>
        </div>
        <div class="admin-card-body">
            @if($availableMonsters->count() === 0)
            <div style="text-align: center; padding: 2rem; color: var(--admin-secondary);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🚫</div>
                <h4 style="margin-bottom: 1rem;">追加可能なモンスターがありません</h4>
                <p>このLocationには既に全てのアクティブなモンスターが設定されています。</p>
                <a href="{{ route('admin.monster-spawns.show', $location->id) }}" class="admin-btn admin-btn-primary">
                    戻る
                </a>
            </div>
            @else
            <form method="POST" action="{{ route('admin.monster-spawns.store') }}" id="spawn-create-form">
                @csrf
                <input type="hidden" name="location_id" value="{{ $location->id }}">

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
                                <option value="">モンスターを選択してください</option>
                                @foreach($availableMonsters as $monster)
                                <option value="{{ $monster->id }}" {{ old('monster_id') === $monster->id ? 'selected' : '' }}
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
                                       value="{{ old('spawn_rate', '0.1') }}" 
                                       class="admin-input" 
                                       min="0.001" max="{{ $remaining > 0.01 ? round($remaining, 3) : '1.0' }}" 
                                       step="0.001" 
                                       style="width: 120px;"
                                       oninput="updateRateDisplay(this.value)"
                                       required>
                                <span id="rate-percentage" style="font-size: 1.1rem; font-weight: bold; color: var(--admin-info);">
                                    {{ round((old('spawn_rate', 0.1)) * 100, 1) }}%
                                </span>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                0.001 (0.1%) から {{ $remaining > 0.01 ? round($remaining, 3) : '1.0' }} ({{ $remaining > 0.01 ? round($remaining * 100, 1) : '100' }}%) まで
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
                                   value="{{ old('priority', $nextPriority) }}" 
                                   class="admin-input" 
                                   min="0" max="999" 
                                   style="width: 120px;"
                                   required>
                            <div style="font-size: 0.875rem; color: var(--admin-secondary); margin-top: 0.25rem;">
                                数字が小さいほど優先度が高い（推奨: {{ $nextPriority }}）
                            </div>
                            @error('priority')
                            <div style="color: var(--admin-danger); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ステータス -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">ステータス</label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
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
                                           value="{{ old('min_level') }}" 
                                           class="admin-input" 
                                           min="1" max="999"
                                           placeholder="制限なし">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: var(--admin-secondary);">最大レベル</label>
                                    <input type="number" name="max_level" 
                                           value="{{ old('max_level') }}" 
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

                        <!-- モンスタープレビュー -->
                        <div id="monster-preview" style="display: none; padding: 1.5rem; background: #f9fafb; border-radius: 8px;">
                            <h5 style="margin-bottom: 1rem; color: var(--admin-primary);">モンスター詳細</h5>
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
                    <a href="{{ route('admin.monster-spawns.show', $location->id) }}" class="admin-btn admin-btn-secondary">
                        キャンセル
                    </a>
                    <button type="submit" class="admin-btn admin-btn-success">
                        ➕ スポーンを追加
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>

<script>
// 出現率表示の更新
function updateRateDisplay(rate) {
    const percentage = Math.round(parseFloat(rate || 0) * 100 * 10) / 10;
    document.getElementById('rate-percentage').textContent = percentage + '%';
}

// モンスタープレビューの更新
function updateMonsterPreview(monsterId) {
    const select = document.querySelector('select[name="monster_id"]');
    const option = select.querySelector(`option[value="${monsterId}"]`);
    const preview = document.getElementById('monster-preview');
    
    if (option && monsterId) {
        document.getElementById('monster-emoji').textContent = option.dataset.emoji || '👹';
        document.getElementById('monster-name').textContent = option.textContent.replace(/^[^\s]+\s/, '');
        document.getElementById('monster-level').textContent = `Level ${option.dataset.level}`;
        document.getElementById('monster-hp').textContent = parseInt(option.dataset.hp).toLocaleString();
        document.getElementById('monster-attack').textContent = parseInt(option.dataset.attack).toLocaleString();
        document.getElementById('monster-defense').textContent = parseInt(option.dataset.defense).toLocaleString();
        document.getElementById('monster-exp').textContent = parseInt(option.dataset.exp).toLocaleString();
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

// フォーム送信時の確認
document.getElementById('spawn-create-form').addEventListener('submit', function(e) {
    const monsterSelect = this.querySelector('select[name="monster_id"]');
    const rateInput = this.querySelector('input[name="spawn_rate"]');
    
    if (!monsterSelect.value) {
        alert('モンスターを選択してください。');
        e.preventDefault();
        return;
    }
    
    const rate = parseFloat(rateInput.value);
    if (rate <= 0 || rate > 1) {
        alert('出現率は0.001から1.0の間で入力してください。');
        e.preventDefault();
        return;
    }
    
    const monsterName = monsterSelect.selectedOptions[0].textContent;
    const confirmation = `以下の設定でスポーンを追加しますか？\n\nモンスター: ${monsterName}\n出現率: ${Math.round(rate * 100 * 10) / 10}%`;
    
    if (!confirm(confirmation)) {
        e.preventDefault();
    }
});

// ページロード時に既に選択されているモンスターのプレビューを表示
document.addEventListener('DOMContentLoaded', function() {
    const monsterSelect = document.querySelector('select[name="monster_id"]');
    if (monsterSelect.value) {
        updateMonsterPreview(monsterSelect.value);
    }
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