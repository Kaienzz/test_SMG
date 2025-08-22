{{-- 接続情報モジュール --}}
<div class="module-connections">
    
    <!-- 接続概要 -->
    <div style="margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="connection-stat-card">
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                        {{ count($data['outgoing_connections'] ?? []) }}
                    </div>
                    <div style="color: var(--admin-secondary); font-size: 0.875rem;">出力接続</div>
                </div>
            </div>

            <div class="connection-stat-card">
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                        {{ count($data['incoming_connections'] ?? []) }}
                    </div>
                    <div style="color: var(--admin-secondary); font-size: 0.875rem;">入力接続</div>
                </div>
            </div>

            <div class="connection-stat-card">
                <div style="text-align: center; padding: 1.5rem;">
                    @php
                        $totalConnections = count($data['outgoing_connections'] ?? []) + count($data['incoming_connections'] ?? []);
                    @endphp
                    <div style="font-size: 2rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                        {{ $totalConnections }}
                    </div>
                    <div style="color: var(--admin-secondary); font-size: 0.875rem;">総接続数</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 出力接続（このLocationから他のLocationへ） -->
    @if(isset($data['outgoing_connections']) && count($data['outgoing_connections']) > 0)
    <div style="margin-bottom: 2rem;">
        <h6 style="margin-bottom: 1rem; color: var(--admin-primary); border-bottom: 2px solid var(--admin-primary); padding-bottom: 0.5rem;">
            <i class="fas fa-arrow-right"></i> 出力接続 ({{ count($data['outgoing_connections']) }}件)
        </h6>
        <p style="margin-bottom: 1rem; font-size: 0.875rem; color: var(--admin-secondary);">
            このLocationから移動できる接続先
        </p>
        
        <div class="connections-grid" style="display: grid; gap: 1rem;">
            @foreach($data['outgoing_connections'] as $connection)
            <div class="connection-card" style="border: 1px solid var(--admin-border); border-radius: 8px; padding: 1.5rem; background: white;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <h6 style="margin: 0; font-weight: bold;">
                                {{ $connection['target_name'] ?? $connection['target_id'] }}
                            </h6>
                            @php
                                $categoryData = match($connection['target_category'] ?? 'unknown') {
                                    'road' => ['badge' => 'primary', 'text' => '道路', 'icon' => 'fas fa-road'],
                                    'dungeon' => ['badge' => 'danger', 'text' => 'ダンジョン', 'icon' => 'fas fa-dungeon'],
                                    'town' => ['badge' => 'info', 'text' => '町', 'icon' => 'fas fa-city'],
                                    default => ['badge' => 'secondary', 'text' => '不明', 'icon' => 'fas fa-question']
                                };
                            @endphp
                            <span class="admin-badge admin-badge-{{ $categoryData['badge'] }}" style="font-size: 0.75rem;">
                                <i class="{{ $categoryData['icon'] }}"></i> {{ $categoryData['text'] }}
                            </span>
                        </div>
                        
                        <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                            ID: <code style="background: #f1f5f9; padding: 0.125rem 0.25rem; border-radius: 3px;">{{ $connection['target_id'] }}</code>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        @if(auth()->user()->can('locations.view'))
                        <a href="{{ route('admin.locations.show', $connection['target_id']) }}" 
                           class="admin-btn admin-btn-primary" 
                           style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                            <i class="fas fa-external-link-alt"></i> 詳細
                        </a>
                        @endif
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">接続タイプ</div>
                        @php
                            $typeData = match($connection['connection_type'] ?? 'road') {
                                'road' => ['badge' => 'primary', 'text' => '道路'],
                                'portal' => ['badge' => 'info', 'text' => 'ポータル'],
                                'stairs' => ['badge' => 'warning', 'text' => '階段'],
                                'door' => ['badge' => 'secondary', 'text' => 'ドア'],
                                default => ['badge' => 'secondary', 'text' => $connection['connection_type']]
                            };
                        @endphp
                        <div class="admin-badge admin-badge-{{ $typeData['badge'] }}" style="font-size: 0.75rem;">
                            {{ $typeData['text'] }}
                        </div>
                    </div>

                    @if(isset($connection['direction']))
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">方向</div>
                        @php
                            $directionData = match($connection['direction']) {
                                'north' => ['icon' => 'fas fa-arrow-up', 'text' => '北'],
                                'south' => ['icon' => 'fas fa-arrow-down', 'text' => '南'],
                                'east' => ['icon' => 'fas fa-arrow-right', 'text' => '東'],
                                'west' => ['icon' => 'fas fa-arrow-left', 'text' => '西'],
                                'up' => ['icon' => 'fas fa-arrow-up', 'text' => '上'],
                                'down' => ['icon' => 'fas fa-arrow-down', 'text' => '下'],
                                default => ['icon' => 'fas fa-question', 'text' => $connection['direction'] ?? '不明']
                            };
                        @endphp
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-size: 0.875rem;">
                            <i class="{{ $directionData['icon'] }}" style="color: var(--admin-info);"></i>
                            {{ $directionData['text'] }}
                        </div>
                    </div>
                    @endif

                    @if(isset($connection['position']))
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">位置</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ $connection['position'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- 入力接続（他のLocationからこのLocationへ） -->
    @if(isset($data['incoming_connections']) && count($data['incoming_connections']) > 0)
    <div style="margin-bottom: 2rem;">
        <h6 style="margin-bottom: 1rem; color: var(--admin-success); border-bottom: 2px solid var(--admin-success); padding-bottom: 0.5rem;">
            <i class="fas fa-arrow-left"></i> 入力接続 ({{ count($data['incoming_connections']) }}件)
        </h6>
        <p style="margin-bottom: 1rem; font-size: 0.875rem; color: var(--admin-secondary);">
            このLocationへ移動してくる接続元
        </p>
        
        <div class="connections-grid" style="display: grid; gap: 1rem;">
            @foreach($data['incoming_connections'] as $connection)
            <div class="connection-card" style="border: 1px solid var(--admin-border); border-radius: 8px; padding: 1.5rem; background: white;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <h6 style="margin: 0; font-weight: bold;">
                                {{ $connection['source_name'] ?? $connection['source_id'] }}
                            </h6>
                            @php
                                $categoryData = match($connection['source_category'] ?? 'unknown') {
                                    'road' => ['badge' => 'primary', 'text' => '道路', 'icon' => 'fas fa-road'],
                                    'dungeon' => ['badge' => 'danger', 'text' => 'ダンジョン', 'icon' => 'fas fa-dungeon'],
                                    'town' => ['badge' => 'info', 'text' => '町', 'icon' => 'fas fa-city'],
                                    default => ['badge' => 'secondary', 'text' => '不明', 'icon' => 'fas fa-question']
                                };
                            @endphp
                            <span class="admin-badge admin-badge-{{ $categoryData['badge'] }}" style="font-size: 0.75rem;">
                                <i class="{{ $categoryData['icon'] }}"></i> {{ $categoryData['text'] }}
                            </span>
                        </div>
                        
                        <div style="font-size: 0.875rem; color: var(--admin-secondary);">
                            ID: <code style="background: #f1f5f9; padding: 0.125rem 0.25rem; border-radius: 3px;">{{ $connection['source_id'] }}</code>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        @if(auth()->user()->can('locations.view'))
                        <a href="{{ route('admin.locations.show', $connection['source_id']) }}" 
                           class="admin-btn admin-btn-primary" 
                           style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                            <i class="fas fa-external-link-alt"></i> 詳細
                        </a>
                        @endif
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">接続タイプ</div>
                        @php
                            $typeData = match($connection['connection_type'] ?? 'road') {
                                'road' => ['badge' => 'primary', 'text' => '道路'],
                                'portal' => ['badge' => 'info', 'text' => 'ポータル'],
                                'stairs' => ['badge' => 'warning', 'text' => '階段'],
                                'door' => ['badge' => 'secondary', 'text' => 'ドア'],
                                default => ['badge' => 'secondary', 'text' => $connection['connection_type']]
                            };
                        @endphp
                        <div class="admin-badge admin-badge-{{ $typeData['badge'] }}" style="font-size: 0.75rem;">
                            {{ $typeData['text'] }}
                        </div>
                    </div>

                    @if(isset($connection['direction']))
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">方向</div>
                        @php
                            $directionData = match($connection['direction']) {
                                'north' => ['icon' => 'fas fa-arrow-up', 'text' => '北'],
                                'south' => ['icon' => 'fas fa-arrow-down', 'text' => '南'],
                                'east' => ['icon' => 'fas fa-arrow-right', 'text' => '東'],
                                'west' => ['icon' => 'fas fa-arrow-left', 'text' => '西'],
                                'up' => ['icon' => 'fas fa-arrow-up', 'text' => '上'],
                                'down' => ['icon' => 'fas fa-arrow-down', 'text' => '下'],
                                default => ['icon' => 'fas fa-question', 'text' => $connection['direction'] ?? '不明']
                            };
                        @endphp
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-size: 0.875rem;">
                            <i class="{{ $directionData['icon'] }}" style="color: var(--admin-info);"></i>
                            {{ $directionData['text'] }}
                        </div>
                    </div>
                    @endif

                    @if(isset($connection['position']))
                    <div style="text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--admin-secondary); margin-bottom: 0.25rem;">位置</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ $connection['position'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- 接続なしの場合 -->
    @if(count($data['outgoing_connections'] ?? []) === 0 && count($data['incoming_connections'] ?? []) === 0)
    <div style="text-align: center; padding: 3rem; color: var(--admin-secondary); background: #f8f9fa; border-radius: 8px;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🔗</div>
        <h4 style="margin-bottom: 1rem; color: var(--admin-secondary);">接続情報がありません</h4>
        <p style="margin-bottom: 2rem;">このLocationには他のLocationとの接続が設定されていません。</p>
        @if(auth()->user()->can('locations.edit'))
        <a href="{{ route('admin.locations.connections') }}" class="admin-btn admin-btn-primary">
            <i class="fas fa-route"></i> 接続管理
        </a>
        @endif
    </div>
    @endif

    <!-- 管理アクション -->
    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--admin-border);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h6 style="margin: 0; color: var(--admin-primary);">
                    <i class="fas fa-tools"></i> 接続管理
                </h6>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: var(--admin-secondary);">
                    Location間の接続の確認・管理を行います
                </p>
            </div>
            
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                @if(auth()->user()->can('locations.view'))
                <a href="{{ route('admin.locations.connections') }}" class="admin-btn admin-btn-primary">
                    <i class="fas fa-route"></i> 接続一覧
                </a>
                @endif
                
                @if(auth()->user()->can('locations.edit'))
                <a href="{{ route('admin.route-connections.index') }}" class="admin-btn admin-btn-warning">
                    <i class="fas fa-edit"></i> 接続管理
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.connection-stat-card {
    background: white;
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.connection-stat-card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.connection-card {
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.connection-card:hover {
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
    .connections-grid {
        grid-template-columns: 1fr !important;
    }
    
    .connection-card > div:first-child {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 1rem !important;
    }
    
    .connection-card .admin-btn {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.75rem !important;
    }
}
</style>