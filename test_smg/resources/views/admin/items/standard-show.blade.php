@extends('admin.layouts.app')

@section('title', '標準アイテム詳細: ' . $item['name'])
@section('subtitle', 'アイテムID: ' . $item['id'])

@section('content')
<div class="admin-content-container">
    
    <!-- 操作ボタン -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <a href="{{ route('admin.items.standard') }}" class="admin-btn admin-btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
        
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.items.standard.edit', $item['id']) }}" class="admin-btn admin-btn-warning">
                <i class="fas fa-edit"></i> 編集
            </a>
            
            <form action="{{ route('admin.items.standard.delete', $item['id']) }}" method="POST" 
                  style="display: inline;" 
                  onsubmit="return confirm('このアイテムを削除してもよろしいですか？')">
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn admin-btn-danger">
                    <i class="fas fa-trash"></i> 削除
                </button>
            </form>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        
        <!-- 基本情報 -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 style="margin: 0;">基本情報</h3>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">アイテム名</label>
                        <div style="font-size: 1.25rem; font-weight: 600;">{{ $item['name'] }}</div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">アイテムID</label>
                        <span class="badge" style="background-color: var(--admin-info); color: white; font-size: 1rem;">
                            {{ $item['id'] }}
                        </span>
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">説明</label>
                        <div style="background-color: var(--admin-bg); padding: 1rem; border-radius: 4px; border: 1px solid var(--admin-border);">
                            {{ $item['description'] }}
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">カテゴリ</label>
                        <span class="badge" style="background-color: var(--admin-primary); color: white;">
                            {{ $item['category_name'] ?? $item['category'] }}
                        </span>
                    </div>
                    
                </div>
            </div>
        </div>

        <!-- 属性情報 -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 style="margin: 0;">属性情報</h3>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">使用可能</label>
                        <span class="badge" style="background-color: {{ $item['is_usable'] ? 'var(--admin-success)' : 'var(--admin-secondary)' }}; color: white;">
                            {{ $item['is_usable'] ? 'はい' : 'いいえ' }}
                        </span>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">装備可能</label>
                        <span class="badge" style="background-color: {{ $item['is_equippable'] ? 'var(--admin-success)' : 'var(--admin-secondary)' }}; color: white;">
                            {{ $item['is_equippable'] ? 'はい' : 'いいえ' }}
                        </span>
                    </div>
                    
                    @if(isset($item['weapon_type']))
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">武器タイプ</label>
                            <span class="badge" style="background-color: var(--admin-warning); color: white;">
                                {{ $item['weapon_type'] === 'physical' ? '物理武器' : '魔法武器' }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
        
        <!-- 価格・数量情報 -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 style="margin: 0;">価格・数量情報</h3>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">価格</label>
                        <div style="font-size: 1.25rem; font-weight: 600; color: var(--admin-success);">
                            {{ number_format($item['value']) }}G
                        </div>
                    </div>
                    
                    @if(isset($item['sell_price']))
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">売却価格</label>
                            <div style="font-size: 1.1rem; color: var(--admin-warning);">
                                {{ number_format($item['sell_price']) }}G
                            </div>
                        </div>
                    @endif
                    
                    @if(isset($item['stack_limit']))
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">スタック上限</label>
                            <div>{{ number_format($item['stack_limit']) }}個</div>
                        </div>
                    @endif
                    
                    @if(isset($item['max_durability']))
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--admin-secondary);">最大耐久度</label>
                            <div>{{ number_format($item['max_durability']) }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 効果情報 -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 style="margin: 0;">効果情報</h3>
            </div>
            <div class="admin-card-body">
                @if(!empty($item['effects']))
                    <div style="display: grid; gap: 0.75rem;">
                        @foreach($item['effects'] as $effect => $value)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background-color: var(--admin-bg); border-radius: 4px; border: 1px solid var(--admin-border);">
                                <span style="font-weight: 500;">
                                    @switch($effect)
                                        @case('attack') 攻撃力 @break
                                        @case('defense') 防御力 @break
                                        @case('agility') 素早さ @break
                                        @case('magic_attack') 魔法攻撃力 @break
                                        @case('accuracy') 命中率 @break
                                        @case('evasion') 回避率 @break
                                        @case('heal_hp') HP回復 @break
                                        @case('heal_mp') MP回復 @break
                                        @case('heal_sp') SP回復 @break
                                        @case('inventory_slots') インベントリ拡張 @break
                                        @case('extra_dice') 追加サイコロ @break
                                        @default {{ $effect }} @break
                                    @endswitch
                                </span>
                                <span class="badge" style="background-color: var(--admin-success); color: white;">
                                    +{{ $value }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align: center; padding: 2rem; color: var(--admin-secondary);">
                        <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        効果がありません
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection