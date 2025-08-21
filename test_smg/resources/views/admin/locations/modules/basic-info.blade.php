{{-- 基本情報モジュール --}}
<div class="module-basic-info">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
        
        <!-- 基本設定 -->
        <div class="info-section">
            <h5 style="margin-bottom: 1rem; color: var(--admin-primary); border-bottom: 2px solid var(--admin-primary); padding-bottom: 0.5rem;">
                <i class="fas fa-info-circle"></i> 基本設定
            </h5>
            
            <div class="info-grid" style="display: grid; gap: 1rem;">
                <div class="info-item">
                    <label class="info-label">Location ID</label>
                    <div class="info-value">
                        <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-family: monospace;">
                            {{ $data['id'] }}
                        </code>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">名前</label>
                    <div class="info-value" style="font-weight: 500; font-size: 1.1rem;">
                        {{ $data['name'] }}
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">カテゴリー</label>
                    <div class="info-value">
                        @php
                            $categoryData = match($data['category'] ?? 'unknown') {
                                'road' => ['badge' => 'primary', 'text' => '道路', 'icon' => 'fas fa-road'],
                                'dungeon' => ['badge' => 'danger', 'text' => 'ダンジョン', 'icon' => 'fas fa-dungeon'],
                                'town' => ['badge' => 'info', 'text' => '町', 'icon' => 'fas fa-city'],
                                default => ['badge' => 'secondary', 'text' => '不明', 'icon' => 'fas fa-question']
                            };
                        @endphp
                        <span class="admin-badge admin-badge-{{ $categoryData['badge'] }}" style="font-size: 0.875rem;">
                            <i class="{{ $categoryData['icon'] }}"></i> {{ $categoryData['text'] }}
                        </span>
                    </div>
                </div>

                @if($data['description'])
                <div class="info-item">
                    <label class="info-label">説明</label>
                    <div class="info-value" style="color: var(--admin-secondary); line-height: 1.5;">
                        {{ $data['description'] }}
                    </div>
                </div>
                @endif

                <div class="info-item">
                    <label class="info-label">ステータス</label>
                    <div class="info-value">
                        <span class="admin-badge admin-badge-{{ ($data['is_active'] ?? true) ? 'success' : 'secondary' }}">
                            <i class="fas fa-{{ ($data['is_active'] ?? true) ? 'check-circle' : 'times-circle' }}"></i>
                            {{ ($data['is_active'] ?? true) ? '有効' : '無効' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 詳細設定 -->
        <div class="info-section">
            <h5 style="margin-bottom: 1rem; color: var(--admin-primary); border-bottom: 2px solid var(--admin-primary); padding-bottom: 0.5rem;">
                <i class="fas fa-cogs"></i> 詳細設定
            </h5>
            
            <div class="info-grid" style="display: grid; gap: 1rem;">
                @if(isset($data['length']))
                <div class="info-item">
                    <label class="info-label">長さ</label>
                    <div class="info-value">
                        <span style="font-weight: 500;">{{ number_format($data['length']) }}</span>
                        <small style="color: var(--admin-secondary); margin-left: 0.5rem;">単位</small>
                    </div>
                </div>
                @endif

                @if(isset($data['difficulty']))
                <div class="info-item">
                    <label class="info-label">難易度</label>
                    <div class="info-value">
                        @php
                            $difficultyData = match($data['difficulty'] ?? 'normal') {
                                'easy' => ['badge' => 'success', 'text' => '簡単', 'icon' => 'fas fa-smile'],
                                'normal' => ['badge' => 'info', 'text' => '普通', 'icon' => 'fas fa-meh'],
                                'hard' => ['badge' => 'warning', 'text' => '困難', 'icon' => 'fas fa-frown'],
                                'extreme' => ['badge' => 'danger', 'text' => '極難', 'icon' => 'fas fa-dizzy'],
                                default => ['badge' => 'secondary', 'text' => $data['difficulty'], 'icon' => 'fas fa-question']
                            };
                        @endphp
                        <span class="admin-badge admin-badge-{{ $difficultyData['badge'] }}">
                            <i class="{{ $difficultyData['icon'] }}"></i> {{ $difficultyData['text'] }}
                        </span>
                    </div>
                </div>
                @endif

                @if(isset($data['encounter_rate']))
                <div class="info-item">
                    <label class="info-label">エンカウント率</label>
                    <div class="info-value">
                        @php
                            $encounterRate = $data['encounter_rate'] * 100;
                            $encounterClass = $encounterRate >= 70 ? 'danger' : ($encounterRate >= 40 ? 'warning' : 'success');
                        @endphp
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="admin-badge admin-badge-{{ $encounterClass }}" style="font-weight: bold;">
                                {{ number_format($encounterRate, 1) }}%
                            </span>
                            <div style="flex: 1; background: #f1f5f9; border-radius: 4px; height: 8px; overflow: hidden;">
                                <div style="width: {{ $encounterRate }}%; height: 100%; background: var(--admin-{{ $encounterClass }}); transition: width 0.3s ease;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ダンジョン専用情報 -->
    @if(($data['category'] ?? '') === 'dungeon')
    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--admin-border);">
        <h5 style="margin-bottom: 1rem; color: var(--admin-danger); border-bottom: 2px solid var(--admin-danger); padding-bottom: 0.5rem;">
            <i class="fas fa-dungeon"></i> ダンジョン固有設定
        </h5>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            @if(isset($data['floors']))
            <div class="info-item">
                <label class="info-label">フロア数</label>
                <div class="info-value">
                    <span style="font-size: 1.25rem; font-weight: bold; color: var(--admin-danger);">
                        {{ $data['floors'] }}
                    </span>
                    <small style="color: var(--admin-secondary); margin-left: 0.25rem;">F</small>
                </div>
            </div>
            @endif

            @if(isset($data['min_level']) || isset($data['max_level']))
            <div class="info-item">
                <label class="info-label">推奨レベル</label>
                <div class="info-value">
                    @if(isset($data['min_level']) && isset($data['max_level']))
                        <span class="admin-badge admin-badge-warning">
                            Lv.{{ $data['min_level'] }} ～ Lv.{{ $data['max_level'] }}
                        </span>
                    @elseif(isset($data['min_level']))
                        <span class="admin-badge admin-badge-info">
                            Lv.{{ $data['min_level'] }}+
                        </span>
                    @elseif(isset($data['max_level']))
                        <span class="admin-badge admin-badge-info">
                            ～Lv.{{ $data['max_level'] }}
                        </span>
                    @endif
                </div>
            </div>
            @endif
        </div>

        @if((isset($data['min_level']) || isset($data['max_level'])) && $data['category'] === 'dungeon')
        <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-left: 4px solid var(--admin-warning); border-radius: 0 4px 4px 0;">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500; color: var(--admin-warning);">
                <i class="fas fa-exclamation-triangle"></i>
                レベル制限について
            </div>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: var(--admin-secondary);">
                このダンジョンには推奨レベルが設定されています。適正レベル外のプレイヤーには警告が表示される場合があります。
            </p>
        </div>
        @endif
    </div>
    @endif
</div>

<style>
.info-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--admin-secondary);
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 0.95rem;
    color: var(--admin-text);
}

.info-item {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    transition: border-color 0.2s ease;
}

.info-item:hover {
    border-color: var(--admin-primary);
}

.admin-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
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