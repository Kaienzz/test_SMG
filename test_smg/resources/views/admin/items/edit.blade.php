@extends('admin.layouts.app')

@section('title', 'アイテム編集')
@section('subtitle', $item->name . ' の編集')

@section('content')
<div class="admin-content-container">
    
    <form method="POST" action="{{ route('admin.items.update', $item->id) }}" id="item-edit-form">
        @csrf
        @method('PUT')
        
        <!-- 基本情報 -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div class="admin-card-header">
                <h3 class="admin-card-title">基本情報</h3>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                    <div>
                        <!-- アイテムID -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="item_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                アイテムID <span style="color: #dc2626;">*</span>
                                @if(isset($item->is_standard) && $item->is_standard)
                                    <small style="color: var(--admin-warning);">(標準アイテム用: std_1, std_2など)</small>
                                @endif
                            </label>
                            <input type="text" id="item_id" name="item_id" value="{{ old('item_id', $item->id) }}" 
                                   class="admin-input @error('item_id') admin-input-error @enderror" 
                                   required pattern="[a-zA-Z][a-zA-Z0-9_-]*"
                                   placeholder="例: std_1, custom_sword_1">
                            @error('item_id')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- アイテム名 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                アイテム名 <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $item->name) }}" 
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
                                      placeholder="アイテムの説明を入力してください...">{{ old('description', $item->description) }}</textarea>
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
                                @foreach($categories as $key => $name)
                                <option value="{{ $key }}" {{ old('category', $item->category) === $key ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- カテゴリ名（標準アイテム用） -->
                        @if(isset($item->is_standard) && $item->is_standard)
                        <div style="margin-bottom: 1.5rem;">
                            <label for="category_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                カテゴリ名 <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="text" id="category_name" name="category_name" value="{{ old('category_name', $item->category_name) }}" 
                                   class="admin-input @error('category_name') admin-input-error @enderror" required>
                            @error('category_name')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 絵文字（標準アイテム用） -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="emoji" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                絵文字
                            </label>
                            <input type="text" id="emoji" name="emoji" value="{{ old('emoji', $item->emoji ?? '📦') }}" 
                                   class="admin-input @error('emoji') admin-input-error @enderror" 
                                   style="width: 80px; text-align: center; font-size: 1.5rem;" maxlength="4">
                            @error('emoji')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                    </div>

                    <div>
                        <!-- 価格 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label for="value" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                価格 <span style="color: #dc2626;">*</span>
                            </label>
                            <div style="position: relative;">
                                <input type="number" id="value" name="value" value="{{ old('value', $item->value) }}" 
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
                                @if(isset($item->is_standard) && $item->is_standard)
                                    売却価格 <small style="color: var(--admin-secondary);">(標準アイテムは直接設定)</small>
                                @else
                                    売却価格 <small style="color: var(--admin-secondary);">(空の場合は自動算出: {{ number_format($item->getSellPrice()) }}G)</small>
                                @endif
                            </label>
                            <div style="position: relative;">
                                <input type="number" id="sell_price" name="sell_price" value="{{ old('sell_price', $item->sell_price) }}" 
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
                            <input type="number" id="stack_limit" name="stack_limit" value="{{ old('stack_limit', $item->stack_limit) }}" 
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
                            <input type="number" id="max_durability" name="max_durability" value="{{ old('max_durability', $item->max_durability) }}" 
                                   class="admin-input @error('max_durability') admin-input-error @enderror" 
                                   min="1" max="9999" placeholder="例: 100">
                            @error('max_durability')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 作成日・更新日 -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">作成・更新情報</label>
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 4px; font-size: 0.875rem;">
                                <div>作成日: 
                                    @if(isset($item->is_standard) && $item->is_standard)
                                        {{ $item->created_at }}
                                    @else
                                        {{ $item->created_at->format('Y/m/d H:i') }}
                                    @endif
                                </div>
                                @if($item->updated_at != $item->created_at)
                                <div style="margin-top: 0.25rem;">更新日: 
                                    @if(isset($item->is_standard) && $item->is_standard)
                                        {{ $item->updated_at }}
                                    @else
                                        {{ $item->updated_at->format('Y/m/d H:i') }}
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 武器設定 -->
        <div class="admin-card" id="weapon-settings" style="margin-bottom: 2rem;">
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
                                <option value="{{ $value }}" {{ old('weapon_type', $item->weapon_type) === $value ? 'selected' : '' }}>
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
                            <input type="text" id="battle_skill_id" name="battle_skill_id" value="{{ old('battle_skill_id', $item->battle_skill_id) }}" 
                                   class="admin-input @error('battle_skill_id') admin-input-error @enderror" 
                                   placeholder="例: fire_magic, ice_magic">
                            @error('battle_skill_id')
                                <div class="admin-error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 標準アイテム用フィールド -->
                        @if(isset($item->is_standard) && $item->is_standard)
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                アイテム設定
                            </label>
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="hidden" name="is_equippable" value="0">
                                    <input type="checkbox" name="is_equippable" value="1" 
                                           {{ old('is_equippable', $item->is_equippable ?? false) ? 'checked' : '' }}
                                           style="margin-right: 0.5rem;">
                                    装備可能
                                </label>
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="hidden" name="is_usable" value="0">
                                    <input type="checkbox" name="is_usable" value="1" 
                                           {{ old('is_usable', $item->is_usable ?? false) ? 'checked' : '' }}
                                           style="margin-right: 0.5rem;">
                                    使用可能
                                </label>
                            </div>
                        </div>
                        @endif
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
                            <input type="number" id="effect_attack" name="effect_attack" value="{{ old('effect_attack', (isset($item->is_standard) && $item->is_standard) ? ($item->effects['attack'] ?? 0) : $item->getEffectValue('attack')) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_defense" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">防御力</label>
                            <input type="number" id="effect_defense" name="effect_defense" value="{{ old('effect_defense', (isset($item->is_standard) && $item->is_standard) ? ($item->effects['defense'] ?? 0) : $item->getEffectValue('defense')) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_agility" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">敏捷性</label>
                            <input type="number" id="effect_agility" name="effect_agility" value="{{ old('effect_agility', (isset($item->is_standard) && $item->is_standard) ? ($item->effects['agility'] ?? 0) : $item->getEffectValue('agility')) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                    </div>

                    <!-- 戦闘系 -->
                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">戦闘系</h4>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_magic_attack" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">魔法攻撃力</label>
                            <input type="number" id="effect_magic_attack" name="effect_magic_attack" value="{{ old('effect_magic_attack', (isset($item->is_standard) && $item->is_standard) ? ($item->effects['magic_attack'] ?? 0) : $item->getEffectValue('magic_attack')) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_accuracy" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">命中率</label>
                            <input type="number" id="effect_accuracy" name="effect_accuracy" value="{{ old('effect_accuracy', (isset($item->is_standard) && $item->is_standard) ? ($item->effects['accuracy'] ?? 0) : $item->getEffectValue('accuracy')) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_evasion" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">回避率</label>
                            <input type="number" id="effect_evasion" name="effect_evasion" value="{{ old('effect_evasion', (isset($item->is_standard) && $item->is_standard) ? ($item->effects['evasion'] ?? 0) : $item->getEffectValue('evasion')) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                    </div>

                    <!-- 回復・その他 -->
                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">回復・その他</h4>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_heal_hp" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">HP回復</label>
                            <input type="number" id="effect_heal_hp" name="effect_heal_hp" value="{{ old('effect_heal_hp', (isset($item->is_standard) && $item->is_standard) ? ($item->effects['heal_hp'] ?? 0) : $item->getEffectValue('heal_hp')) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_heal_mp" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">MP回復</label>
                            <input type="number" id="effect_heal_mp" name="effect_heal_mp" value="{{ old('effect_heal_mp', (isset($item->is_standard) && $item->is_standard) ? ($item->effects['heal_mp'] ?? 0) : $item->getEffectValue('heal_mp')) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="effect_inventory_slots" style="display: block; margin-bottom: 0.25rem; font-weight: 500;">インベントリ拡張</label>
                            <input type="number" id="effect_inventory_slots" name="effect_inventory_slots" value="{{ old('effect_inventory_slots', (isset($item->is_standard) && $item->is_standard) ? ($item->effects['inventory_slots'] ?? 0) : $item->getEffectValue('inventory_slots')) }}" 
                                   class="admin-input" placeholder="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 操作ボタン -->
        <div style="display: flex; gap: 1rem; justify-content: end;">
            <a href="{{ route('admin.items.show', $item->id) }}" class="admin-btn admin-btn-secondary">
                ← 詳細に戻る
            </a>
            <button type="button" onclick="resetForm()" class="admin-btn admin-btn-secondary">
                🔄 リセット
            </button>
            <button type="button" onclick="previewItem()" class="admin-btn admin-btn-info">
                👁️ プレビュー
            </button>
            <button type="submit" class="admin-btn admin-btn-primary">
                💾 更新
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
    }
}

// フォームリセット
function resetForm() {
    if (confirm('入力内容をリセットしますか？未保存の変更は失われます。')) {
        document.getElementById('item-edit-form').reset();
        
        // オリジナルの値に戻す
        document.getElementById('name').value = '{{ $item->name }}';
        document.getElementById('description').value = '{{ $item->description }}';
        document.getElementById('category').value = '{{ $item->category->value ?? $item->category }}';
        document.getElementById('value').value = '{{ $item->value }}';
        document.getElementById('sell_price').value = '{{ $item->sell_price }}';
        document.getElementById('stack_limit').value = '{{ $item->stack_limit }}';
        document.getElementById('max_durability').value = '{{ $item->max_durability }}';
        document.getElementById('weapon_type').value = '{{ $item->weapon_type }}';
        document.getElementById('battle_skill_id').value = '{{ $item->battle_skill_id }}';
        
        // エフェクトのリセット
        @foreach(['attack', 'defense', 'agility', 'magic_attack', 'accuracy', 'evasion', 'heal_hp', 'heal_mp', 'inventory_slots'] as $effect)
        document.getElementById('effect_{{ $effect }}').value = '{{ (isset($item->is_standard) && $item->is_standard) ? ($item->effects[$effect] ?? 0) : $item->getEffectValue($effect) }}';
        @endforeach
        
        updateCategorySettings();
    }
}

// アイテムプレビュー（create.blade.phpと同じ関数）
function previewItem() {
    const formData = new FormData(document.getElementById('item-edit-form'));
    
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
document.getElementById('item-edit-form').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value;
    const category = document.getElementById('category').value;
    
    if (!name || !category) {
        alert('アイテム名とカテゴリは必須項目です。');
        e.preventDefault();
        return false;
    }
    
    if (!confirm('このアイテムの変更を保存しますか？')) {
        e.preventDefault();
        return false;
    }
});

// 売却価格の自動計算表示更新
document.getElementById('value').addEventListener('input', function() {
    const value = this.value;
    const sellPriceField = document.getElementById('sell_price');
    const label = sellPriceField.previousElementSibling;
    if (value) {
        const autoPrice = Math.floor(value * 0.5);
        label.innerHTML = '売却価格 <small style="color: var(--admin-secondary);">(空の場合は自動算出: ' + autoPrice.toLocaleString() + 'G)</small>';
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