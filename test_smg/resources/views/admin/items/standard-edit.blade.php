@extends('admin.layouts.app')

@section('title', '標準アイテム編集: ' . $item['name'])
@section('subtitle', 'アイテムID: ' . $item['id'])

@section('content')
<div class="admin-content-container">
    
    <!-- ナビゲーション -->
    <div style="margin-bottom: 2rem;">
        <a href="{{ route('admin.items.standard') }}" class="admin-btn admin-btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> 一覧に戻る
        </a>
        <a href="{{ route('admin.items.standard.show', $item['id']) }}" class="admin-btn admin-btn-outline-info" style="margin-left: 0.5rem;">
            <i class="fas fa-eye"></i> 詳細表示
        </a>
    </div>

    <form action="{{ route('admin.items.standard.update', $item['id']) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            
            <!-- 基本情報 -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 style="margin: 0;">基本情報</h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; gap: 1.5rem;">
                        <div>
                            <label for="name" class="admin-label required">アイテム名</label>
                            <input type="text" id="name" name="name" 
                                   value="{{ old('name', $item['name']) }}" 
                                   class="admin-input @error('name') error @enderror" 
                                   required>
                            @error('name')
                                <div class="admin-error">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="description" class="admin-label required">説明</label>
                            <textarea id="description" name="description" rows="3" 
                                      class="admin-input @error('description') error @enderror" 
                                      required>{{ old('description', $item['description']) }}</textarea>
                            @error('description')
                                <div class="admin-error">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="category" class="admin-label required">カテゴリ</label>
                                <select id="category" name="category" 
                                        class="admin-input @error('category') error @enderror" 
                                        required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->value }}" 
                                                {{ old('category', $item['category']) === $category->value ? 'selected' : '' }}>
                                            {{ $category->getDisplayName() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <div class="admin-error">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="weapon_type" class="admin-label">武器タイプ</label>
                                <select id="weapon_type" name="weapon_type" class="admin-input">
                                    <option value="">武器以外</option>
                                    @foreach($weaponTypes as $value => $label)
                                        <option value="{{ $value }}" 
                                                {{ old('weapon_type', $item['weapon_type'] ?? '') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 属性 -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 style="margin: 0;">属性</h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; gap: 1rem;">
                        <div>
                            <label class="admin-label">属性</label>
                            <div style="display: grid; gap: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" name="is_usable" value="1" 
                                           {{ old('is_usable', $item['is_usable']) ? 'checked' : '' }}>
                                    使用可能
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" name="is_equippable" value="1" 
                                           {{ old('is_equippable', $item['is_equippable']) ? 'checked' : '' }}>
                                    装備可能
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
            
            <!-- 価格・数量設定 -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 style="margin: 0;">価格・数量設定</h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; gap: 1rem;">
                        <div>
                            <label for="value" class="admin-label required">価格</label>
                            <div style="position: relative;">
                                <input type="number" id="value" name="value" 
                                       value="{{ old('value', $item['value']) }}" 
                                       class="admin-input @error('value') error @enderror" 
                                       min="0" max="999999" required>
                                <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--admin-secondary);">G</span>
                            </div>
                            @error('value')
                                <div class="admin-error">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="sell_price" class="admin-label">売却価格</label>
                            <div style="position: relative;">
                                <input type="number" id="sell_price" name="sell_price" 
                                       value="{{ old('sell_price', $item['sell_price'] ?? '') }}" 
                                       class="admin-input" 
                                       min="0" max="999999">
                                <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--admin-secondary);">G</span>
                            </div>
                        </div>
                        
                        <div>
                            <label for="stack_limit" class="admin-label">スタック上限</label>
                            <input type="number" id="stack_limit" name="stack_limit" 
                                   value="{{ old('stack_limit', $item['stack_limit'] ?? '') }}" 
                                   class="admin-input" 
                                   min="1" max="999"
                                   placeholder="未設定の場合は空欄">
                        </div>
                        
                        <div>
                            <label for="max_durability" class="admin-label">最大耐久度</label>
                            <input type="number" id="max_durability" name="max_durability" 
                                   value="{{ old('max_durability', $item['max_durability'] ?? '') }}" 
                                   class="admin-input" 
                                   min="1" max="9999"
                                   placeholder="未設定の場合は空欄">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 効果設定 -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 style="margin: 0;">効果設定</h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; gap: 0.75rem;">
                        @php
                            $effectLabels = [
                                'attack' => '攻撃力',
                                'defense' => '防御力',
                                'agility' => '素早さ',
                                'magic_attack' => '魔法攻撃力',
                                'accuracy' => '命中率',
                                'evasion' => '回避率',
                                'heal_hp' => 'HP回復',
                                'heal_mp' => 'MP回復',
                                'heal_sp' => 'SP回復',
                                'inventory_slots' => 'インベントリ拡張',
                                'extra_dice' => '追加サイコロ'
                            ];
                        @endphp
                        
                        @foreach($effectLabels as $effect => $label)
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.5rem; align-items: center;">
                                <label for="effect_{{ $effect }}" style="font-weight: 500;">{{ $label }}</label>
                                <input type="number" id="effect_{{ $effect }}" name="effect_{{ $effect }}" 
                                       value="{{ old('effect_' . $effect, $item['effects'][$effect] ?? '') }}" 
                                       class="admin-input" style="width: 80px;"
                                       min="-999" max="999" step="1"
                                       placeholder="0">
                            </div>
                        @endforeach
                    </div>
                    
                    <div style="margin-top: 1rem; padding: 0.75rem; background-color: var(--admin-bg); border-radius: 4px; border: 1px solid var(--admin-border);">
                        <small style="color: var(--admin-secondary);">
                            効果値が0の場合は効果なしとして扱われます。<br>
                            負の値も設定可能です（デバフ効果）。
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 保存ボタン -->
        <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="admin-btn admin-btn-primary" style="min-width: 120px;">
                <i class="fas fa-save"></i> 保存
            </button>
            <a href="{{ route('admin.items.standard.show', $item['id']) }}" class="admin-btn admin-btn-outline-secondary" style="min-width: 120px;">
                <i class="fas fa-times"></i> キャンセル
            </a>
        </div>
    </form>
</div>
@endsection