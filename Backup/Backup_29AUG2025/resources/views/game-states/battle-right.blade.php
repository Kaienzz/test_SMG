{{-- Battle State - Right Area: Battle Commands and Actions --}}

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

