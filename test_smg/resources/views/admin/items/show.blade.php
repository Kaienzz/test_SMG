@extends('admin.layouts.app')

@section('title', 'アイテム詳細')
@section('subtitle', $item->name . ' の詳細情報')

@section('content')
<div class="admin-content-container">
    
    <!-- アイテム基本情報 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">基本情報</h3>
            <div style="display: flex; gap: 0.5rem;">
                @if(auth()->user()->can('items.edit'))
                <a href="{{ route('admin.items.edit', $item) }}" class="admin-btn admin-btn-primary">
                    ✏️ 編集
                </a>
                @endif
                @if(auth()->user()->can('items.delete'))
                <form method="POST" action="{{ route('admin.items.destroy', $item) }}" style="display: inline;" onsubmit="return confirm('このアイテムを削除してもよろしいですか？')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-btn admin-btn-danger">
                        🗑️ 削除
                    </button>
                </form>
                @endif
            </div>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: auto 1fr; gap: 2rem;">
                <!-- アイテムアイコン -->
                <div style="text-align: center;">
                    <div style="width: 80px; height: 80px; border-radius: 12px; background: var(--admin-primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem; margin-bottom: 1rem;">
                        {{ $item->emoji ?? '📦' }}
                    </div>
                    <span class="admin-badge admin-badge-info">
                        {{ $item->category->name ?? $item->category }}
                    </span>
                </div>

                <!-- 詳細情報 -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">アイテム情報</h4>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>名前:</strong> {{ $item->name }}
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>説明:</strong> {{ $item->description ?? '説明なし' }}
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>カテゴリ:</strong> {{ $item->category->name ?? $item->category }}
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>アイテムID:</strong> {{ $item->id }}
                        </div>
                        @if($item->battle_skill_id)
                        <div style="margin-bottom: 0.75rem;">
                            <strong>バトルスキル:</strong> 
                            <span class="admin-badge admin-badge-warning">{{ $item->battle_skill_id }}</span>
                        </div>
                        @endif
                    </div>

                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">ゲーム設定</h4>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>価格:</strong> {{ number_format($item->value) }}G
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>売却価格:</strong> 
                            {{ $item->sell_price ? number_format($item->sell_price) . 'G' : number_format($item->getSellPrice()) . 'G (自動算出)' }}
                        </div>
                        @if($item->stack_limit)
                        <div style="margin-bottom: 0.75rem;">
                            <strong>スタック制限:</strong> {{ $item->stack_limit }}個
                        </div>
                        @endif
                        @if($item->max_durability)
                        <div style="margin-bottom: 0.75rem;">
                            <strong>最大耐久度:</strong> {{ $item->max_durability }}
                        </div>
                        @endif
                        @if($item->weapon_type)
                        <div style="margin-bottom: 0.75rem;">
                            <strong>武器タイプ:</strong> 
                            <span class="admin-badge admin-badge-secondary">
                                {{ $item->weapon_type === 'physical' ? '物理武器' : '魔法武器' }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- エフェクト情報 -->
    @if($item->effects && count($item->effects) > 0)
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">エフェクト</h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                @foreach($item->effects as $effect => $value)
                <div style="padding: 1rem; background: #f0f9ff; border-radius: 8px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                        +{{ $value }}
                    </div>
                    <div style="color: #374151; font-weight: 500;">
                        {{ $this->getEffectDisplayName($effect) }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- 使用統計 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">使用統計</h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                        {{ $usageStats['in_inventory_count'] ?? 0 }}
                    </div>
                    <div style="color: var(--admin-secondary);">プレイヤー所持数</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                        {{ $usageStats['sold_count'] ?? 0 }}
                    </div>
                    <div style="color: var(--admin-secondary);">販売数（推定）</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                        {{ $usageStats['total_usage'] ?? 0 }}
                    </div>
                    <div style="color: var(--admin-secondary);">総使用数</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ショップ販売状況 -->
    @if($shopItems->count() > 0)
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">ショップ販売状況</h3>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ショップ名</th>
                            <th>販売価格</th>
                            <th>在庫</th>
                            <th>差額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shopItems as $shopItem)
                        <tr>
                            <td>{{ $shopItem->shop_name }}</td>
                            <td>{{ number_format($shopItem->price) }}G</td>
                            <td>
                                @if($shopItem->stock === -1)
                                    <span class="admin-badge admin-badge-success">無制限</span>
                                @else
                                    {{ $shopItem->stock }}個
                                @endif
                            </td>
                            <td>
                                @php $diff = $shopItem->price - $item->value @endphp
                                <span style="color: {{ $diff > 0 ? '#10b981' : ($diff < 0 ? '#ef4444' : '#6b7280') }};">
                                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff) }}G
                                    ({{ round(($diff / $item->value) * 100, 1) }}%)
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- カスタムアイテム -->
    @if($customItems->count() > 0)
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">カスタムアイテム例</h3>
            <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                このアイテムをベースに作成されたカスタムアイテム
            </div>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>作成者</th>
                            <th>カスタム効果</th>
                            <th>耐久度</th>
                            <th>名匠品</th>
                            <th>作成日</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customItems as $customItem)
                        <tr>
                            <td>{{ $customItem->creator->name ?? 'Unknown' }}</td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    @if($customItem->custom_stats)
                                        @foreach(array_slice($customItem->custom_stats, 0, 2) as $stat => $value)
                                        <div>{{ $stat }}: {{ $value }}</div>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    {{ $customItem->durability }}/{{ $customItem->max_durability }}
                                </div>
                            </td>
                            <td>
                                @if($customItem->is_masterwork)
                                    <span class="admin-badge admin-badge-warning">⭐ 名匠品</span>
                                @else
                                    <span style="color: var(--admin-secondary);">-</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    {{ $customItem->created_at->format('Y/m/d') }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- アクションボタン -->
    <div style="display: flex; gap: 1rem; justify-content: end;">
        <a href="{{ route('admin.items.index') }}" class="admin-btn admin-btn-secondary">
            ← 一覧に戻る
        </a>
        @if(auth()->user()->can('items.create'))
        <a href="{{ route('admin.items.create') }}" class="admin-btn admin-btn-success">
            ➕ 新規作成
        </a>
        @endif
    </div>
</div>

@php
function getEffectDisplayName($effect) {
    $effectNames = [
        'attack' => '攻撃力',
        'defense' => '防御力',
        'agility' => '敏捷性',
        'magic_attack' => '魔法攻撃力',
        'accuracy' => '命中率',
        'evasion' => '回避率',
        'heal_hp' => 'HP回復',
        'heal_mp' => 'MP回復',
        'heal_sp' => 'SP回復',
        'inventory_slots' => 'インベントリ拡張',
    ];
    
    return $effectNames[$effect] ?? $effect;
}
@endphp
@endsection