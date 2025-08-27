@extends('admin.layouts.app')

@section('title', 'アイテム詳細')
@section('subtitle', (is_object($item) ? $item->name : $item['name']) . ' の詳細情報')

@section('content')
<div class="admin-content-container">
    
    <!-- アイテム基本情報 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">基本情報</h3>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                @if(isset($item->is_standard) && $item->is_standard)
                    <span class="admin-badge admin-badge-warning">標準アイテム</span>
                @else
                    <span class="admin-badge admin-badge-success">カスタムアイテム</span>
                @endif
                
                <!-- 編集ボタン（常に表示） -->
                <a href="{{ route('admin.items.edit', $item->id) }}" class="admin-btn admin-btn-primary">
                    ✏️ 編集
                </a>
                
                <!-- 削除ボタン（すべてのアイテム） -->
                <form method="POST" action="{{ route('admin.items.destroy', $item->id) }}" style="display: inline;" onsubmit="return confirm('このアイテム「{{ $item->name }}」を削除してもよろしいですか？\\n\\n※この操作は元に戻せません。')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-btn admin-btn-danger">
                        🗑️ 削除
                    </button>
                </form>
            </div>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: auto 1fr; gap: 2rem;">
                <!-- アイテムアイコン -->
                <div style="text-align: center;">
                    <div style="width: 80px; height: 80px; border-radius: 12px; background: var(--admin-primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem; margin-bottom: 1rem;">
                        {{ (is_object($item) ? $item->emoji : $item['emoji'] ?? null) ?? '📦' }}
                    </div>
                    <span class="admin-badge admin-badge-info">
                        @if(isset($item->is_standard) && $item->is_standard)
                            {{ $item->category_name ?? 'カテゴリ不明' }}
                        @else
                            {{ $item->category->getDisplayName() ?? $item->category }}
                        @endif
                    </span>
                </div>

                <!-- 詳細情報 -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">アイテム情報</h4>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>名前:</strong> {{ is_object($item) ? $item->name : $item['name'] }}
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>説明:</strong> {{ (is_object($item) ? $item->description : $item['description']) ?? '説明なし' }}
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>カテゴリ:</strong> 
                            @if(isset($item->is_standard) && $item->is_standard)
                                {{ $item->category_name ?? 'カテゴリ不明' }}
                            @else
                                {{ $item->category->getDisplayName() ?? $item->category }}
                            @endif
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>アイテムID:</strong> {{ is_object($item) ? $item->id : $item['id'] }}
                        </div>
                        @php
                            $battleSkillId = is_object($item) ? $item->battle_skill_id : ($item['battle_skill_id'] ?? null);
                        @endphp
                        @if($battleSkillId)
                        <div style="margin-bottom: 0.75rem;">
                            <strong>バトルスキル:</strong> 
                            <span class="admin-badge admin-badge-warning">{{ $battleSkillId }}</span>
                        </div>
                        @endif
                    </div>

                    <div>
                        <h4 style="margin-bottom: 1rem; color: #374151;">ゲーム設定</h4>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>価格:</strong> {{ number_format(is_object($item) ? $item->value : $item['value']) }}G
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <strong>売却価格:</strong> 
                            @if(isset($item->is_standard) && $item->is_standard)
                                {{ number_format($item->sell_price ?? 0) }}G
                            @else
                                {{ $item->sell_price ? number_format($item->sell_price) . 'G' : number_format($item->getSellPrice()) . 'G (自動算出)' }}
                            @endif
                        </div>
                        @php
                            $stackLimit = is_object($item) ? $item->stack_limit : ($item['stack_limit'] ?? null);
                            $maxDurability = is_object($item) ? $item->max_durability : ($item['max_durability'] ?? null);
                            $weaponType = is_object($item) ? $item->weapon_type : ($item['weapon_type'] ?? null);
                        @endphp
                        @if($stackLimit)
                        <div style="margin-bottom: 0.75rem;">
                            <strong>スタック制限:</strong> {{ $stackLimit }}個
                        </div>
                        @endif
                        @if($maxDurability)
                        <div style="margin-bottom: 0.75rem;">
                            <strong>最大耐久度:</strong> {{ $maxDurability }}
                        </div>
                        @endif
                        @if($weaponType)
                        <div style="margin-bottom: 0.75rem;">
                            <strong>武器タイプ:</strong> 
                            <span class="admin-badge admin-badge-secondary">
                                {{ $weaponType === 'physical' ? '物理武器' : '魔法武器' }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- エフェクト情報 -->
    @php
        $effects = is_object($item) ? $item->effects : ($item['effects'] ?? []);
        $effects = is_array($effects) ? $effects : [];
    @endphp
    @if(count($effects) > 0)
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">エフェクト</h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                @foreach($effects as $effect => $value)
                <div style="padding: 1rem; background: #f0f9ff; border-radius: 8px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                        +{{ $value }}
                    </div>
                    <div style="color: #374151; font-weight: 500;">
                        @switch($effect)
                            @case('attack')
                                攻撃力
                                @break
                            @case('defense')
                                防御力
                                @break
                            @case('agility')
                                素早さ
                                @break
                            @case('magic_attack')
                                魔法攻撃力
                                @break
                            @case('heal_hp')
                                HP回復
                                @break
                            @case('heal_sp')
                                SP回復
                                @break
                            @case('heal_mp')
                                MP回復
                                @break
                            @case('accuracy')
                                命中率
                                @break
                            @case('evasion')
                                回避率
                                @break
                            @case('inventory_slots')
                                インベントリ枠
                                @break
                            @case('extra_dice')
                                移動サイコロ
                                @break
                            @default
                                {{ $effect }}
                        @endswitch
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

    <!-- 施設販売状況 -->
    @if($facilityItems->count() > 0)
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">施設販売状況</h3>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>施設名</th>
                            <th>販売価格</th>
                            <th>在庫</th>
                            <th>差額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($facilityItems as $facilityItem)
                        <tr>
                            <td>{{ $facilityItem->facility_name }}</td>
                            <td>{{ number_format($facilityItem->price) }}G</td>
                            <td>
                                @if($facilityItem->stock === -1)
                                    <span class="admin-badge admin-badge-success">無制限</span>
                                @else
                                    {{ $facilityItem->stock }}個
                                @endif
                            </td>
                            <td>
                                @php $diff = $facilityItem->price - (is_object($item) ? $item->value : $item['value']) @endphp
                                <span style="color: {{ $diff > 0 ? '#10b981' : ($diff < 0 ? '#ef4444' : '#6b7280') }};">
                                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff) }}G
                                    ({{ round(($diff / (is_object($item) ? $item->value : $item['value'])) * 100, 1) }}%)
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