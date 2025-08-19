@extends('admin.layouts.app')

@section('title', 'モンスタースポーン設定 - ' . $pathway['name'])

@section('content')
<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">{{ $pathway['name'] }} - モンスタースポーン設定</h1>
        <p class="admin-subtitle">{{ $pathwayId }} ({{ $pathway['category'] === 'dungeon' ? 'ダンジョン' : '道路' }})</p>
    </div>

    @if($errors->any())
        <div class="admin-alert admin-alert-error">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="admin-alert admin-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="admin-alert admin-alert-error">
            {{ session('error') }}
        </div>
    @endif

    <!-- エリア情報 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>エリア情報</h2>
        </div>
        <div class="admin-card-content">
            <div class="admin-info-grid">
                <div class="admin-info-item">
                    <label>名前:</label>
                    <span>{{ $pathway['name'] }}</span>
                </div>
                <div class="admin-info-item">
                    <label>カテゴリー:</label>
                    <span class="admin-badge admin-badge-{{ $pathway['category'] === 'dungeon' ? 'warning' : 'info' }}">
                        {{ $pathway['category'] === 'dungeon' ? 'ダンジョン' : '道路' }}
                    </span>
                </div>
                <div class="admin-info-item">
                    <label>難易度:</label>
                    <span class="admin-badge admin-badge-difficulty-{{ $pathway['difficulty'] ?? 'normal' }}">
                        {{ ucfirst($pathway['difficulty'] ?? 'normal') }}
                    </span>
                </div>
                <div class="admin-info-item">
                    <label>長さ:</label>
                    <span>{{ $pathway['length'] ?? 'N/A' }}</span>
                </div>
                <div class="admin-info-item">
                    <label>エンカウント率:</label>
                    <span>{{ number_format(($pathway['encounter_rate'] ?? 0) * 100, 1) }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- スポーン設定 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>モンスタースポーン設定</h2>
            <div class="admin-actions">
                <button type="button" id="addSpawnBtn" class="admin-btn admin-btn-primary">
                    モンスター追加
                </button>
                <a href="{{ route('admin.monsters.spawn-lists.test', $pathwayId) }}" 
                   class="admin-btn admin-btn-outline">スポーンテスト</a>
            </div>
        </div>

        <div class="admin-card-content">
            <form method="POST" action="{{ route('admin.monsters.spawn-lists.save', $pathwayId) }}" id="spawnForm">
                @csrf
                
                <div id="spawnContainer">
                    @foreach($spawns as $index => $spawn)
                        <div class="spawn-item" data-index="{{ $index }}">
                            <div class="spawn-item-header">
                                <h4>{{ $monsters[$spawn['monster_id']]['name'] ?? $spawn['monster_id'] }}</h4>
                                <button type="button" class="admin-btn admin-btn-sm admin-btn-danger remove-spawn">削除</button>
                            </div>
                            
                            <div class="spawn-item-content">
                                <input type="hidden" name="spawns[{{ $index }}][monster_id]" value="{{ $spawn['monster_id'] }}">
                                
                                <div class="admin-form-row">
                                    <div class="admin-form-group">
                                        <label>出現率 (%)</label>
                                        <input type="number" name="spawns[{{ $index }}][spawn_rate]" 
                                               value="{{ number_format($spawn['spawn_rate'] * 100, 1) }}" 
                                               min="0" max="100" step="0.1" class="admin-input spawn-rate">
                                    </div>
                                    
                                    <div class="admin-form-group">
                                        <label>優先度</label>
                                        <input type="number" name="spawns[{{ $index }}][priority]" 
                                               value="{{ $spawn['priority'] ?? 0 }}" 
                                               min="0" max="100" class="admin-input">
                                    </div>
                                    
                                    <div class="admin-form-group">
                                        <label>最小レベル</label>
                                        <input type="number" name="spawns[{{ $index }}][min_level]" 
                                               value="{{ $spawn['min_level'] ?? '' }}" 
                                               min="1" max="100" class="admin-input">
                                    </div>
                                    
                                    <div class="admin-form-group">
                                        <label>最大レベル</label>
                                        <input type="number" name="spawns[{{ $index }}][max_level]" 
                                               value="{{ $spawn['max_level'] ?? '' }}" 
                                               min="1" max="100" class="admin-input">
                                    </div>
                                </div>
                                
                                <div class="admin-form-row">
                                    <div class="admin-form-group">
                                        <label class="admin-checkbox">
                                            <input type="checkbox" name="spawns[{{ $index }}][is_active]" 
                                                   value="1" {{ ($spawn['is_active'] ?? true) ? 'checked' : '' }}>
                                            有効
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="admin-form-actions">
                    <div class="spawn-summary">
                        <span>出現率合計: <span id="totalRate">0.0</span>%</span>
                        <span id="rateWarning" class="admin-text-warning" style="display: none;">
                            出現率が100%を超えています
                        </span>
                    </div>
                    
                    <div class="admin-actions">
                        <a href="{{ route('admin.monsters.spawn-lists.index') }}" class="admin-btn admin-btn-secondary">戻る</a>
                        <button type="submit" class="admin-btn admin-btn-primary">保存</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- モンスター選択モーダル -->
<div id="monsterModal" class="admin-modal" style="display: none;">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h3>モンスター選択</h3>
            <button type="button" class="admin-modal-close">&times;</button>
        </div>
        
        <div class="admin-modal-body">
            <div class="monster-search">
                <input type="text" id="monsterSearch" placeholder="モンスター名で検索..." class="admin-input">
            </div>
            
            <div class="monster-list">
                @foreach($monsters as $monsterId => $monster)
                    <div class="monster-item" data-monster-id="{{ $monsterId }}" data-monster-name="{{ $monster['name'] }}">
                        <div class="monster-info">
                            <div class="monster-name">{{ $monster['name'] }}</div>
                            <div class="monster-details">
                                レベル: {{ $monster['level'] ?? 'N/A' }} | 
                                HP: {{ $monster['max_hp'] ?? 'N/A' }} | 
                                攻撃: {{ $monster['attack'] ?? 'N/A' }}
                            </div>
                        </div>
                        <button type="button" class="admin-btn admin-btn-sm admin-btn-primary select-monster">選択</button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let spawnIndex = {{ count($spawns) }};
    
    // 出現率合計計算
    function updateTotalRate() {
        let total = 0;
        document.querySelectorAll('.spawn-rate').forEach(function(input) {
            total += parseFloat(input.value) || 0;
        });
        
        document.getElementById('totalRate').textContent = total.toFixed(1);
        
        const warning = document.getElementById('rateWarning');
        if (total > 100) {
            warning.style.display = 'inline';
        } else {
            warning.style.display = 'none';
        }
    }
    
    // モンスター追加ボタン
    document.getElementById('addSpawnBtn').addEventListener('click', function() {
        document.getElementById('monsterModal').style.display = 'flex';
    });
    
    // モーダル閉じる
    document.querySelector('.admin-modal-close').addEventListener('click', function() {
        document.getElementById('monsterModal').style.display = 'none';
    });
    
    // モンスター検索
    document.getElementById('monsterSearch').addEventListener('input', function() {
        const search = this.value.toLowerCase();
        document.querySelectorAll('.monster-item').forEach(function(item) {
            const name = item.dataset.monsterName.toLowerCase();
            if (name.includes(search)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // モンスター選択
    document.querySelectorAll('.select-monster').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const item = this.closest('.monster-item');
            const monsterId = item.dataset.monsterId;
            const monsterName = item.dataset.monsterName;
            
            // 既に追加済みかチェック
            const existing = document.querySelector(`input[value="${monsterId}"]`);
            if (existing) {
                alert('このモンスターは既に追加されています。');
                return;
            }
            
            addSpawnItem(monsterId, monsterName);
            document.getElementById('monsterModal').style.display = 'none';
        });
    });
    
    // スポーンアイテム追加
    function addSpawnItem(monsterId, monsterName) {
        const container = document.getElementById('spawnContainer');
        const div = document.createElement('div');
        div.className = 'spawn-item';
        div.dataset.index = spawnIndex;
        
        div.innerHTML = `
            <div class="spawn-item-header">
                <h4>${monsterName}</h4>
                <button type="button" class="admin-btn admin-btn-sm admin-btn-danger remove-spawn">削除</button>
            </div>
            
            <div class="spawn-item-content">
                <input type="hidden" name="spawns[${spawnIndex}][monster_id]" value="${monsterId}">
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label>出現率 (%)</label>
                        <input type="number" name="spawns[${spawnIndex}][spawn_rate]" 
                               value="10.0" min="0" max="100" step="0.1" class="admin-input spawn-rate">
                    </div>
                    
                    <div class="admin-form-group">
                        <label>優先度</label>
                        <input type="number" name="spawns[${spawnIndex}][priority]" 
                               value="0" min="0" max="100" class="admin-input">
                    </div>
                    
                    <div class="admin-form-group">
                        <label>最小レベル</label>
                        <input type="number" name="spawns[${spawnIndex}][min_level]" 
                               min="1" max="100" class="admin-input">
                    </div>
                    
                    <div class="admin-form-group">
                        <label>最大レベル</label>
                        <input type="number" name="spawns[${spawnIndex}][max_level]" 
                               min="1" max="100" class="admin-input">
                    </div>
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label class="admin-checkbox">
                            <input type="checkbox" name="spawns[${spawnIndex}][is_active]" 
                                   value="1" checked>
                            有効
                        </label>
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(div);
        spawnIndex++;
        
        // イベントリスナー追加
        div.querySelector('.remove-spawn').addEventListener('click', function() {
            div.remove();
            updateTotalRate();
        });
        
        div.querySelector('.spawn-rate').addEventListener('input', updateTotalRate);
        
        updateTotalRate();
    }
    
    // 既存の削除ボタン
    document.querySelectorAll('.remove-spawn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.spawn-item').remove();
            updateTotalRate();
        });
    });
    
    // 出現率変更監視
    document.querySelectorAll('.spawn-rate').forEach(function(input) {
        input.addEventListener('input', updateTotalRate);
    });
    
    // 初期計算
    updateTotalRate();
});
</script>

<style>
.spawn-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 16px;
    background: #f9f9f9;
}

.spawn-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #f0f0f0;
    border-radius: 8px 8px 0 0;
    border-bottom: 1px solid #ddd;
}

.spawn-item-content {
    padding: 16px;
}

.spawn-summary {
    font-weight: bold;
    margin-right: 16px;
}

.monster-list {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 12px;
}

.monster-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.monster-item:last-child {
    border-bottom: none;
}

.monster-item:hover {
    background-color: #f5f5f5;
}

.monster-name {
    font-weight: bold;
    margin-bottom: 4px;
}

.monster-details {
    font-size: 0.9em;
    color: #666;
}

.admin-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.admin-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
}

.admin-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #ddd;
}

.admin-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}

.admin-modal-body {
    padding: 16px;
}
</style>
@endsection