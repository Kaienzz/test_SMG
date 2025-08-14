@extends('admin.layouts.app')

@section('title', 'アイテム作成')
@section('subtitle', '新しいゲームアイテムを作成')

@section('content')
<div class="admin-content-container">
    
    <form method="POST" action="{{ route('admin.items.store') }}" id="item-create-form">
        @csrf
        
        <!-- 基本情報 -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div class="admin-card-header">
                <h3 class="admin-card-title">基本情報</h3>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                    <div>
                        <!-- アイテム名 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                アイテム名 <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" 
                                   class="admin-input @error('name') admin-input-error @enderror" required>
                            @error('name')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 説明 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="description" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                説明
                            </label>
                            <textarea id="description" name="description" rows="3" 
                                      class="admin-input @error('description') admin-input-error @enderror" 
                                      placeholder="アイテムの説明を入力してください...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- カテゴリ -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="category" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                カテゴリ <span style="color: #dc2626;">*</span>
                            </label>
                            <select id="category" name="category" 
                                    class="admin-select @error('category') admin-input-error @enderror" required
                                    onchange="updateCategorySettings()">
                                <option value="">カテゴリを選択...</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->value }}" {{ old('category') === $category->value ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <!-- 価格 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="value" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                価格 <span style="color: #dc2626;">*</span>
                            </label>
                            <div style="position: relative;">
                                <input type="number" id="value" name="value" value="{{ old('value') }}" 
                                       class="admin-input @error('value') admin-input-error @enderror" 
                                       min="0" max="999999" required>
                                <span style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--admin-secondary);">G</span>
                            </div>
                            @error('value')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 売却価格 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="sell_price" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                売却価格 <small style="color: var(--admin-secondary);">(空の場合は自動算出)</small>
                            </label>
                            <div style="position: relative;">
                                <input type="number" id="sell_price" name="sell_price" value="{{ old('sell_price') }}" 
                                       class="admin-input @error('sell_price') admin-input-error @enderror" 
                                       min="0" max="999999">
                                <span style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--admin-secondary);">G</span>
                            </div>
                            @error('sell_price')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- スタック制限 -->
                        <div style="margin-bottom: 1.5rem;" id="stack-limit-field">
                            <label for="stack_limit" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                スタック制限
                            </label>
                            <input type="number" id="stack_limit" name="stack_limit" value="{{ old('stack_limit') }}" 
                                   class="admin-input @error('stack_limit') admin-input-error @enderror" 
                                   min="1" max="999" placeholder="例: 50">
                            @error('stack_limit')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 最大耐久度 -->
                        <div style="margin-bottom: 1.5rem;" id="durability-field">
                            <label for="max_durability" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                最大耐久度
                            </label>
                            <input type="number" id="max_durability" name="max_durability" value="{{ old('max_durability') }}" 
                                   class="admin-input @error('max_durability') admin-input-error @enderror" 
                                   min="1" max="9999" placeholder="例: 100">
                            @error('max_durability')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 武器設定 -->
        <div class="admin-card" id="weapon-settings" style="margin-bottom: 2rem; display: none;">
            <div class="admin-card-header">
                <h3 class="admin-card-title">武器設定</h3>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                    <div>
                        <!-- 武器タイプ -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="weapon_type" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                武器タイプ
                            </label>
                            <select id="weapon_type" name="weapon_type" class="admin-select">
                                <option value="">選択してください</option>
                                @foreach($weaponTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('weapon_type') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <!-- バトルスキル -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="battle_skill_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                バトルスキルID <small style="color: var(--admin-secondary);">(オプション)</small>
                            </label>
                            <input type="text" id="battle_skill_id" name="battle_skill_id" value="{{ old('battle_skill_id') }}" 
                                   class="admin-input @error('battle_skill_id') admin-input-error @enderror" 
                                   placeholder="例: fire_magic, ice_magic">
                            @error('battle_skill_id')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- エフェクト設定 -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div class="admin-card-header">
                <h3 class="admin-card-title">エフェクト設定</h3>
                <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                    0以外の値を入力したエフェクトのみが適用されます
                </div>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                    <!-- ステータス系 -->
                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">ステータス系</h4>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_attack" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">攻撃力</label>
                            <input type="number" id="effect_attack" name="effect_attack" value="{{ old('effect_attack', 0) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_defense" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">防御力</label>
                            <input type="number" id="effect_defense" name="effect_defense" value="{{ old('effect_defense', 0) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_agility" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">敏捷性</label>
                            <input type="number" id="effect_agility" name="effect_agility" value="{{ old('effect_agility', 0) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                    </div>

                    <!-- 戦闘系 -->
                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">戦闘系</h4>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_magic_attack" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">魔法攻撃力</label>
                            <input type="number" id="effect_magic_attack" name="effect_magic_attack" value="{{ old('effect_magic_attack', 0) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_accuracy" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">命中率</label>
                            <input type="number" id="effect_accuracy" name="effect_accuracy" value="{{ old('effect_accuracy', 0) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_evasion" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">回避率</label>
                            <input type="number" id="effect_evasion" name="effect_evasion" value="{{ old('effect_evasion', 0) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                    </div>

                    <!-- 回復・その他 -->
                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">回復・その他</h4>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_heal_hp" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">HP回復</label>
                            <input type="number" id="effect_heal_hp" name="effect_heal_hp" value="{{ old('effect_heal_hp', 0) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_heal_mp" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">MP回復</label>
                            <input type="number" id="effect_heal_mp" name="effect_heal_mp" value="{{ old('effect_heal_mp', 0) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_inventory_slots" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">インベントリ拡張</label>
                            <input type="number" id="effect_inventory_slots" name="effect_inventory_slots" value="{{ old('effect_inventory_slots', 0) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 操作ボタン -->
        <div style="display: flex; gap: 1rem; justify-content: end;">
            <a href="{{ route('admin.items.index') }}" class="admin-btn admin-btn-secondary">
                ← キャンセル
            </a>
            <button type="button" onclick="previewItem()" class="admin-btn admin-btn-info">
                👁️ プレビュー
            </button>
            <button type="submit" class="admin-btn admin-btn-primary">
                💾 作成
            </button>
        </div>
    </form>
</div>

<!-- プレビューモーダル -->
<div id="preview-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 2rem; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-bottom: 1.5rem;">アイテムプレビュー</h3>
        <div id="preview-content">
            <!-- JavaScriptで動的に更新 -->
        </div>
        <div style="display: flex; gap: 1rem; justify-content: end; margin-top: 2rem;">
            <button type="button" onclick="hidePreview()" class="admin-btn admin-btn-secondary">
                閉じる
            </button>
        </div>
    </div>
</div>

<script>
// カテゴリ変更時の設定更新
function updateCategorySettings() {
    const category = document.getElementById('category').value;
    const weaponSettings = document.getElementById('weapon-settings');
    const stackLimitField = document.getElementById('stack-limit-field');
    const durabilityField = document.getElementById('durability-field');
    
    // 武器設定の表示制御
    if (category === 'weapon') {
        weaponSettings.style.display = 'block';
    } else {
        weaponSettings.style.display = 'none';
        document.getElementById('weapon_type').value = '';
        document.getElementById('battle_skill_id').value = '';
    }
    
    // フィールドの表示制御（カテゴリに応じて）
    const stackableCategories = ['potion', 'material', 'consumable'];
    const durableCategories = ['weapon', 'armor', 'equipment'];
    
    if (stackableCategories.includes(category)) {
        stackLimitField.style.display = 'block';
    } else {
        stackLimitField.style.display = 'none';
        document.getElementById('stack_limit').value = '';
    }
    
    if (durableCategories.includes(category)) {
        durabilityField.style.display = 'block';
    } else {
        durabilityField.style.display = 'none';
        document.getElementById('max_durability').value = '';
    }
}

// アイテムプレビュー
function previewItem() {
    const formData = new FormData(document.getElementById('item-create-form'));
    
    let previewHTML = '<div class="item-preview" style="border: 2px solid var(--admin-primary); border-radius: 8px; padding: 1.5rem;">';
    previewHTML += '<div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">';
    previewHTML += '<div style="width: 50px; height: 50px; border-radius: 8px; background: var(--admin-primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">📦</div>';
    previewHTML += '<div>';
    previewHTML += '<h4 style="margin: 0; font-size: 1.25rem;">' + (formData.get('name') || '未設定') + '</h4>';
    previewHTML += '<p style="margin: 0.25rem 0 0 0; color: var(--admin-secondary);">' + (formData.get('description') || '説明なし') + '</p>';
    previewHTML += '</div>';
    previewHTML += '</div>';
    
    // 基本情報
    previewHTML += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">';
    previewHTML += '<div><strong>カテゴリ:</strong> ' + (formData.get('category') || '未設定') + '</div>';
    previewHTML += '<div><strong>価格:</strong> ' + (formData.get('value') ? Number(formData.get('value')).toLocaleString() + 'G' : '0G') + '</div>';
    
    if (formData.get('stack_limit')) {
        previewHTML += '<div><strong>スタック制限:</strong> ' + formData.get('stack_limit') + '個</div>';
    }
    if (formData.get('max_durability')) {
        previewHTML += '<div><strong>最大耐久度:</strong> ' + formData.get('max_durability') + '</div>';
    }
    previewHTML += '</div>';
    
    // エフェクト
    const effects = [];
    const effectFields = ['attack', 'defense', 'agility', 'magic_attack', 'accuracy', 'evasion', 'heal_hp', 'heal_mp', 'inventory_slots'];
    const effectNames = {
        'attack': '攻撃力',
        'defense': '防御力', 
        'agility': '敏捷性',
        'magic_attack': '魔法攻撃力',
        'accuracy': '命中率',
        'evasion': '回避率',
        'heal_hp': 'HP回復',
        'heal_mp': 'MP回復',
        'inventory_slots': 'インベントリ拡張'
    };
    
    effectFields.forEach(field => {
        const value = formData.get('effect_' + field);
        if (value && Number(value) !== 0) {
            effects.push(effectNames[field] + ': +' + value);
        }
    });
    
    if (effects.length > 0) {
        previewHTML += '<div style="margin-top: 1rem;"><strong>エフェクト:</strong></div>';
        previewHTML += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-top: 0.5rem;">';
        effects.forEach(effect => {
            previewHTML += '<div style="padding: 0.5rem; background: #f0f9ff; border-radius: 4px; font-size: 0.875rem;">' + effect + '</div>';
        });
        previewHTML += '</div>';
    }
    
    previewHTML += '</div>';
    
    document.getElementById('preview-content').innerHTML = previewHTML;
    document.getElementById('preview-modal').style.display = 'block';
}

function hidePreview() {
    document.getElementById('preview-modal').style.display = 'none';
}

// モーダル外クリックで閉じる
document.getElementById('preview-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        hidePreview();
    }
});

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    updateCategorySettings();
});

// フォーム送信時の確認
document.getElementById('item-create-form').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value;
    const category = document.getElementById('category').value;
    
    if (!name || !category) {
        alert('アイテム名とカテゴリは必須項目です。');
        e.preventDefault();
        return false;
    }
    
    if (!confirm('このアイテムを作成しますか？')) {
        e.preventDefault();
        return false;
    }
});

// 売却価格の自動計算
document.getElementById('value').addEventListener('input', function() {
    const value = this.value;
    const sellPriceField = document.getElementById('sell_price');
    if (!sellPriceField.value && value) {
        sellPriceField.placeholder = '自動: ' + Math.floor(value * 0.5) + 'G';
    }
});
</script>

<style>
.admin-input-error {
    border-color: #dc2626;
}

.admin-error-message {
    color: #dc2626;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
</style>
@endsection