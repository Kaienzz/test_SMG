{{-- Battle State - Left Area: Merged Right + Left Content --}}

{{-- === RIGHT BAR CONTENT (First) === --}}

{{-- Battle Actions --}}
<div class="battle-actions-section">
    <h3>戦闘コマンド</h3>
    
    <div class="action-buttons" id="battle-actions">
        <button class="btn btn-danger action-btn" onclick="performAction('attack')" id="attack-button">
            <span class="btn-icon">⚔️</span>
            <span class="btn-text">攻撃</span>
        </button>
        
        <button class="btn btn-primary action-btn" onclick="performAction('defend')">
            <span class="btn-icon">🛡️</span>
            <span class="btn-text">防御</span>
        </button>
        
        <div class="skill-container">
            <button class="btn btn-secondary action-btn" onclick="toggleSkillMenu()" id="skill-button">
                <span class="btn-icon">✨</span>
                <span class="btn-text">特技</span>
            </button>
            <div class="skill-menu hidden" id="skill-menu">
                <!-- Dynamic skills -->
            </div>
        </div>
        
        <button class="btn btn-warning action-btn" onclick="performAction('escape')">
            <span class="btn-icon">🏃</span>
            <div class="btn-content">
                <span class="btn-text">逃げる</span>
                <div class="escape-rate" id="escape-rate">成功率: 50%</div>
            </div>
        </button>
    </div>

    {{-- Continue Actions --}}
    <div class="continue-actions hidden" id="continue-actions">
        <button class="btn btn-success btn-large" onclick="returnToGame()" id="return-to-game-btn">
            <span class="btn-icon">🚀</span>
            ゲームに戻る
        </button>
    </div>
</div>

{{-- Battle Strategy --}}
<div class="battle-strategy-section">
    <h4>戦略</h4>
    <div class="strategy-tips">
        <div class="tip-item">
            <span class="tip-icon">💡</span>
            <p>敵の攻撃パターンを観察しよう</p>
        </div>
        <div class="tip-item">
            <span class="tip-icon">⚡</span>
            <p>MPを効率的に使って特技で勝負</p>
        </div>
        <div class="tip-item">
            <span class="tip-icon">🛡️</span>
            <p>HPが低い時は防御で様子見</p>
        </div>
    </div>
</div>

{{-- Inventory Quick Access --}}
<div class="inventory-quick-section">
    <h4>アイテム使用</h4>
    <div class="inventory-note">
        <p>戦闘中にアイテムを使用したい場合は、インベントリから選択してください。</p>
        <a href="/inventory" class="btn btn-info btn-sm" target="_blank">
            <span class="btn-icon">🎒</span>
            インベントリを開く
        </a>
    </div>
</div>

{{-- Battle Information --}}
<div class="battle-info-section">
    <h4>戦闘情報</h4>
    <div class="battle-info">
        <div class="info-item">
            <span class="info-label">現在ターン:</span>
            <span class="info-value" id="current-turn">1</span>
        </div>
        <div class="info-item">
            <span class="info-label">戦闘状態:</span>
            <span class="info-value" id="battle-status">進行中</span>
        </div>
    </div>
</div>

{{-- Emergency Actions --}}
<div class="emergency-section">
    <h4>緊急時</h4>
    <div class="emergency-buttons">
        <button class="btn btn-danger btn-sm" onclick="forfeitBattle()" title="戦闘を放棄します">
            <span class="btn-icon">🏳️</span>
            戦闘放棄
        </button>
    </div>
</div>

{{-- === SEPARATOR === --}}
<hr class="content-separator">

{{-- === LEFT BAR CONTENT (Second) === --}}

{{-- Player Battle Status --}}
<div class="player-battle-status">
    <div class="character-header">
        <h3>{{ $character['name'] ?? 'プレイヤー' }}</h3>
        <div class="character-level">Lv.{{ $character['level'] ?? 5 }}</div>
    </div>

    {{-- HP/MP Bars --}}
    <div class="resource-bars">
        <div class="resource-bar hp-bar">
            <div class="resource-label">
                <span class="resource-name">HP</span>
                <span class="resource-text" id="character-hp-text">{{ $character['hp'] ?? 100 }}/{{ $character['max_hp'] ?? 100 }}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill hp" id="character-hp" style="width: {{ (($character['hp'] ?? 100) / ($character['max_hp'] ?? 100)) * 100 }}%"></div>
            </div>
        </div>

        <div class="resource-bar mp-bar">
            <div class="resource-label">
                <span class="resource-name">MP</span>
                <span class="resource-text" id="character-mp-text">{{ $character['mp'] ?? 50 }}/{{ $character['max_mp'] ?? 50 }}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill mp" id="character-mp" style="width: {{ (($character['mp'] ?? 50) / ($character['max_mp'] ?? 50)) * 100 }}%"></div>
            </div>
        </div>

        <div class="resource-bar sp-bar">
            <div class="resource-label">
                <span class="resource-name">SP</span>
                <span class="resource-text" id="character-sp-text">{{ $character['sp'] ?? 80 }}/{{ $character['max_sp'] ?? 80 }}</span>
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