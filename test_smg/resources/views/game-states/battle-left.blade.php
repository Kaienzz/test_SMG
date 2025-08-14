{{-- Battle State - Left Area: Player Status and Stats --}}

{{-- Player Battle Status --}}
<div class="player-battle-status">
    <div class="character-header">
        <h3>{{ $character['name'] ?? 'プレイヤー' }}</h3>
    </div>

    {{-- HP/MP Bars --}}
    <div class="resource-bars">
        <div class="resource-bar hp-bar">
            <div class="resource-label">
                <span class="resource-name">HP</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill hp" id="character-hp" style="width: {{ (($character['hp'] ?? 100) / ($character['max_hp'] ?? 100)) * 100 }}%"></div>
            </div>
        </div>

        <div class="resource-bar mp-bar">
            <div class="resource-label">
                <span class="resource-name">MP</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill mp" id="character-mp" style="width: {{ (($character['mp'] ?? 50) / ($character['max_mp'] ?? 50)) * 100 }}%"></div>
            </div>
        </div>

        <div class="resource-bar sp-bar">
            <div class="resource-label">
                <span class="resource-name">SP</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill sp" id="character-sp" style="width: {{ (($character['sp'] ?? 80) / ($character['max_sp'] ?? 80)) * 100 }}%"></div>
            </div>
        </div>
    </div>

    {{-- Battle Stats --}}
    <div class="battle-stats">
        <h4>戦闘ステータス</h4>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-icon">⚔️</span>
                <div class="stat-info">
                    <span class="stat-label">攻撃力</span>
                    <span class="stat-value">{{ $character['attack'] ?? 15 }}</span>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon">✨</span>
                <div class="stat-info">
                    <span class="stat-label">魔法攻撃</span>
                    <span class="stat-value">{{ $character['magic_attack'] ?? 12 }}</span>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon">🛡️</span>
                <div class="stat-info">
                    <span class="stat-label">防御力</span>
                    <span class="stat-value">{{ $character['defense'] ?? 12 }}</span>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon">💨</span>
                <div class="stat-info">
                    <span class="stat-label">素早さ</span>
                    <span class="stat-value">{{ $character['agility'] ?? 18 }}</span>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon">🎯</span>
                <div class="stat-info">
                    <span class="stat-label">命中率</span>
                    <span class="stat-value">{{ $character['accuracy'] ?? 85 }}%</span>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon">💫</span>
                <div class="stat-info">
                    <span class="stat-label">回避率</span>
                    <span class="stat-value">{{ $character['evasion'] ?? 15 }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Effects --}}
    @php
        $activeEffects = null;
        // プレイヤーがアクティブ効果を持っているかチェック
        if (is_object($character) && method_exists($character, 'activeEffects')) {
            $activeEffects = $character->activeEffects()->where('is_active', true)->get();
        }
    @endphp

    @if($activeEffects && $activeEffects->count() > 0)
        <div class="status-effects">
            <h4>状態効果</h4>
            <div class="effects-list" id="player-status-effects">
                @foreach($activeEffects as $effect)
                    <div class="effect-item {{ $effect->effect_type === 'buff' ? 'positive' : 'negative' }}">
                        <span class="effect-icon">{{ $effect->effect_type === 'buff' ? '💪' : '💔' }}</span>
                        <div class="effect-info">
                            <span class="effect-name">{{ $effect->effect_type }}</span>
                            <span class="effect-duration">{{ $effect->duration }}ターン</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="status-effects">
            <h4>状態効果</h4>
            <p class="no-effects">現在、状態効果はありません</p>
        </div>
    @endif

    {{-- Equipment Quick View --}}
    @php
        $equipment = null;
        if (is_object($character) && method_exists($character, 'equipment')) {
            $equipment = $character->equipment()->first();
        }
    @endphp

    <div class="equipment-quick">
        <h4>装備中</h4>
        <div class="equipment-list">
            @if($equipment)
                @if($equipment->weapon_id)
                    <div class="equipment-item">
                        <span class="equipment-icon">⚔️</span>
                        <span class="equipment-name">武器装備中</span>
                    </div>
                @endif
                @if($equipment->body_armor_id)
                    <div class="equipment-item">
                        <span class="equipment-icon">🛡️</span>
                        <span class="equipment-name">防具装備中</span>
                    </div>
                @endif
            @else
                <p class="no-equipment">装備なし</p>
            @endif
        </div>
    </div>
</div>