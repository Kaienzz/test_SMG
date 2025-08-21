{{-- モンスタースポーンモジュール --}}
<div class="module-monster-spawns">
    
    <!-- スポーン概要 -->
    <div style="margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="spawn-stat-card">
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                        {{ $data['total_spawns'] ?? 0 }}
                    </div>
                    <div style="color: var(--admin-secondary); font-size: 0.875rem;">総スポーン数</div>
                </div>
            </div>

            <div class="spawn-stat-card">
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                        {{ $data['active_spawns'] ?? 0 }}
                    </div>
                    <div style="color: var(--admin-secondary); font-size: 0.875rem;">有効スポーン</div>
                </div>
            </div>

            <div class="spawn-stat-card">
                <div style="text-align: center; padding: 1.5rem;">
                    @php
                        $completionRate = ($data['completion_rate'] ?? 0) * 100;
                        $isComplete = $completionRate >= 99;
                        $rateColor = $isComplete ? 'var(--admin-success)' : ($completionRate > 70 ? 'var(--admin-warning)' : 'var(--admin-danger)');
                    @endphp
                    <div style="font-size: 2rem; font-weight: bold; color: {{ $rateColor }}; margin-bottom: 0.5rem;">
                        {{ number_format($completionRate, 1) }}%
                    </div>
                    <div style="color: var(--admin-secondary); font-size: 0.875rem;">
                        出現率合計
                        @if($isComplete)
                            <i class="fas fa-check-circle" style="color: var(--admin-success); margin-left: 0.25rem;"></i>
                        @endif
                    </div>
                </div>
            </div>

            <div class="spawn-stat-card">
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                        {{ $data['unique_monsters'] ?? 0 }}
                    </div>
                    <div style="color: var(--admin-secondary); font-size: 0.875rem;">モンスター種類</div>
                </div>
            </div>

            @if(isset($data['average_level']))
            <div class="spawn-stat-card">
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                        Lv.{{ number_format($data['average_level'], 1) }}
                    </div>
                    <div style="color: var(--admin-secondary); font-size: 0.875rem;">平均レベル</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- スポーン設定情報 -->
    @if(isset($data['spawn_tags']) && count($data['spawn_tags']) > 0 || isset($data['spawn_description']))
    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--admin-info);">
        <h6 style="margin-bottom: 1rem; color: var(--admin-info);">
            <i class="fas fa-tags"></i> スポーン設定
        </h6>
        
        @if(isset($data['spawn_description']) && $data['spawn_description'])
        <div style="margin-bottom: 1rem;">
            <div style="font-size: 0.875rem; font-weight: 500; color: var(--admin-secondary); margin-bottom: 0.25rem;">説明</div>
            <div style="color: var(--admin-text);">{{ $data['spawn_description'] }}</div>
        </div>
        @endif

        @if(isset($data['spawn_tags']) && count($data['spawn_tags']) > 0)
        <div>
            <div style="font-size: 0.875rem; font-weight: 500; color: var(--admin-secondary); margin-bottom: 0.5rem;">タグ</div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                @foreach($data['spawn_tags'] as $tag)
                <span class="admin-badge admin-badge-secondary">{{ $tag }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- モンスター一覧 -->
    @if(isset($data['monsters']) && count($data['monsters']) > 0)
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
            <h6 style="margin: 0; color: var(--admin-primary);">
                <i class="fas fa-dragon"></i> スポーンモンスター ({{ count($data['monsters']) }}体)
            </h6>
            @if(auth()->user()->can('monsters.create'))
            <a href="{{ route('admin.monster-spawns.create', request()->route('locationId')) }}" class="admin-btn admin-btn-success" style="font-size: 0.875rem;">
                <i class="fas fa-plus"></i> スポーン追加
            </a>
            @endif
        </div>

        <div class="monsters-grid" style="display: grid; gap: 1rem;">
            @foreach($data['monsters'] as $monster)
            <div class="monster-card" style="border: 1px solid var(--admin-border); border-radius: 8px; overflow: hidden; background: white;">
                <div style="padding: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <!-- モンスター画像・絵文字 -->
                        <div style="font-size: 3rem; flex-shrink: 0;">
                            {{ $monster['monster_emoji'] ?? '👹' }}
                        </div>
                        
                        <!-- モンスター基本情報 -->
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <h6 style="margin: 0; font-size: 1.1rem; font-weight: bold;">
                                    {{ $monster['monster_name'] ?? 'Unknown Monster' }}
                                </h6>
                                <span class="admin-badge admin-badge-{{ $monster['is_active'] ? 'success' : 'secondary' }}">
                                    {{ $monster['is_active'] ? '有効' : '無効' }}
                                </span>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 1rem; color: var(--admin-secondary); font-size: 0.875rem;">
                                <span><strong>Lv.{{ $monster['monster_level'] ?? 1 }}</strong></span>
                                <span>HP: {{ number_format($monster['monster_hp'] ?? 0) }}</span>
                                <span>攻撃: {{ number_format($monster['monster_attack'] ?? 0) }}</span>
                                <span>防御: {{ number_format($monster['monster_defense'] ?? 0) }}</span>
                                <span>EXP: {{ number_format($monster['monster_experience'] ?? 0) }}</span>
                            </div>
                        </div>

                        <!-- 操作ボタン -->
                        @if(auth()->user()->can('monsters.edit'))
                        <div style="flex-shrink: 0;">
                            <a href="{{ route('admin.monster-spawns.edit', $monster['spawn_id']) }}" 
                               class="admin-btn admin-btn-primary" 
                               style="padding: 0.5rem; font-size: 0.875rem;">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                        @endif
                    </div>

                    <!-- スポーン設定詳細 -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                        <div style="text-align: center;">
                            <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">出現率</div>
                            @php
                                $spawnRate = ($monster['spawn_rate'] ?? 0) * 100;
                                $rateClass = $spawnRate >= 30 ? 'danger' : ($spawnRate >= 15 ? 'warning' : 'info');
                            @endphp
                            <div class="admin-badge admin-badge-{{ $rateClass }}" style="font-weight: bold;">
                                {{ number_format($spawnRate, 1) }}%
                            </div>
                        </div>

                        <div style="text-align: center;">
                            <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">優先度</div>
                            <div style="font-weight: bold; color: var(--admin-text);">
                                {{ $monster['priority'] ?? 0 }}
                            </div>
                        </div>

                        @if($monster['min_level'] || $monster['max_level'])
                        <div style="text-align: center;">
                            <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">レベル制限</div>
                            <div style="font-size: 0.875rem;">
                                @if($monster['min_level'] && $monster['max_level'])
                                    <span class="admin-badge admin-badge-warning" style="font-size: 0.75rem;">
                                        Lv.{{ $monster['min_level'] }}-{{ $monster['max_level'] }}
                                    </span>
                                @elseif($monster['min_level'])
                                    <span class="admin-badge admin-badge-info" style="font-size: 0.75rem;">
                                        Lv.{{ $monster['min_level'] }}+
                                    </span>
                                @elseif($monster['max_level'])
                                    <span class="admin-badge admin-badge-info" style="font-size: 0.75rem;">
                                        ～Lv.{{ $monster['max_level'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @else
                        <div style="text-align: center;">
                            <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">レベル制限</div>
                            <div style="font-size: 0.875rem; color: var(--admin-secondary);">なし</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <!-- スポーン未設定の場合 -->
    <div style="text-align: center; padding: 3rem; color: var(--admin-secondary); background: #f8f9fa; border-radius: 8px;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🐉</div>
        <h4 style="margin-bottom: 1rem; color: var(--admin-secondary);">モンスタースポーンが未設定です</h4>
        <p style="margin-bottom: 2rem;">このLocationにはまだモンスターが設定されていません。</p>
        @if(auth()->user()->can('monsters.create'))
        <a href="{{ route('admin.monster-spawns.create', request()->route('locationId')) }}" class="admin-btn admin-btn-success">
            <i class="fas fa-plus"></i> 最初のスポーンを追加
        </a>
        @endif
    </div>
    @endif

    <!-- 管理アクション -->
    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--admin-border);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h6 style="margin: 0; color: var(--admin-primary);">
                    <i class="fas fa-tools"></i> 管理アクション
                </h6>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: var(--admin-secondary);">
                    スポーン設定の管理・編集を行います
                </p>
            </div>
            
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                @if(auth()->user()->can('monsters.view'))
                <a href="{{ route('admin.monster-spawns.show', request()->route('locationId')) }}" class="admin-btn admin-btn-primary">
                    <i class="fas fa-list"></i> スポーン詳細管理
                </a>
                @endif
                
                @if(auth()->user()->can('monsters.create'))
                <a href="{{ route('admin.monster-spawns.create', request()->route('locationId')) }}" class="admin-btn admin-btn-success">
                    <i class="fas fa-plus"></i> スポーン追加
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.spawn-stat-card {
    background: white;
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.spawn-stat-card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.monster-card {
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.monster-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.admin-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 500;
}

.admin-badge-primary { background-color: #dbeafe; color: #1d4ed8; }
.admin-badge-secondary { background-color: #f1f5f9; color: #475569; }
.admin-badge-success { background-color: #dcfce7; color: #166534; }
.admin-badge-warning { background-color: #fef3c7; color: #d97706; }
.admin-badge-danger { background-color: #fee2e2; color: #dc2626; }
.admin-badge-info { background-color: #e0f2fe; color: #0369a1; }

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .monsters-grid {
        grid-template-columns: 1fr !important;
    }
    
    .monster-card > div:first-child {
        padding: 1rem !important;
    }
    
    .monster-card .admin-btn {
        padding: 0.25rem !important;
        font-size: 0.75rem !important;
    }
}
</style>