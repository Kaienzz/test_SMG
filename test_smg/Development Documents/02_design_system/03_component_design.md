# コンポーネント設計書

## プロジェクト情報
- **プロジェクト名**: test_smg（Simple Management Game）
- **作成日**: 2025年7月26日
- **バージョン**: 1.0
- **作成者**: Claude（AI開発アシスタント）
- **想定開発時間**: 4時間

## 目次
1. [コンポーネント設計概要](#コンポーネント設計概要)
2. [アトミックデザイン階層](#アトミックデザイン階層)
3. [基本UIコンポーネント](#基本uiコンポーネント)
4. [ゲーム専用コンポーネント](#ゲーム専用コンポーネント)
5. [レイアウトコンポーネント](#レイアウトコンポーネント)
6. [データ表示コンポーネント](#データ表示コンポーネント)
7. [フォームコンポーネント](#フォームコンポーネント)
8. [状態管理とイベント](#状態管理とイベント)
9. [アクセシビリティ実装](#アクセシビリティ実装)
10. [テスト戦略](#テスト戦略)
11. [実装ガイドライン](#実装ガイドライン)

## コンポーネント設計概要

### 設計原則
test_smgのコンポーネント設計は以下の原則に基づいています：

```typescript
// コンポーネント設計の核心原則
interface ComponentPrinciples {
  reusability: boolean;        // 再利用可能性
  composability: boolean;      // 組み合わせ可能性
  accessibility: boolean;      // アクセシビリティ
  maintainability: boolean;    // メンテナンス性
  performance: boolean;        // パフォーマンス
  testability: boolean;        // テスト可能性
}
```

### 技術スタック
- **フレームワーク**: Vanilla JavaScript + Web Components
- **スタイリング**: TailwindCSS + CSS Custom Properties
- **状態管理**: カスタムState管理システム
- **型安全性**: TypeScript（開発時）+ JSDoc（実行時）

### ファイル構成
```
resources/
├── js/
│   ├── components/
│   │   ├── atoms/           # 最小単位コンポーネント
│   │   ├── molecules/       # 複合コンポーネント
│   │   ├── organisms/       # 機能ブロック
│   │   ├── templates/       # ページテンプレート
│   │   └── pages/          # 完全なページ
│   ├── styles/
│   │   ├── components/      # コンポーネント専用スタイル
│   │   └── utilities/       # ユーティリティクラス
│   └── utils/
│       ├── component-base.js    # ベースコンポーネントクラス
│       ├── state-manager.js     # 状態管理
│       └── event-emitter.js     # イベントシステム
```

## アトミックデザイン階層

### Atoms（原子）- 最小単位コンポーネント

#### 1. Button（ボタン）
```typescript
interface ButtonProps {
  variant: 'primary' | 'secondary' | 'danger' | 'ghost';
  size: 'sm' | 'md' | 'lg';
  disabled?: boolean;
  loading?: boolean;
  icon?: string;
  onClick: (event: MouseEvent) => void;
  children: string | HTMLElement;
  ariaLabel?: string;
}
```

**実装例**：
```javascript
class GameButton extends HTMLElement {
  static get observedAttributes() {
    return ['variant', 'size', 'disabled', 'loading'];
  }

  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.render();
  }

  render() {
    const variant = this.getAttribute('variant') || 'primary';
    const size = this.getAttribute('size') || 'md';
    const disabled = this.hasAttribute('disabled');
    const loading = this.hasAttribute('loading');

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: inline-block;
        }
        
        .btn {
          font-family: var(--font-family-base);
          font-weight: var(--font-weight-medium);
          border-radius: var(--border-radius-md);
          transition: all var(--transition-duration-normal);
          cursor: pointer;
          border: none;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          gap: var(--spacing-2);
        }
        
        .btn:hover:not(:disabled) {
          transform: translateY(-1px);
          box-shadow: var(--shadow-sm);
        }
        
        .btn:active:not(:disabled) {
          transform: translateY(0);
        }
        
        .btn:disabled {
          opacity: 0.6;
          cursor: not-allowed;
        }
        
        /* Variants */
        .btn--primary {
          background: var(--color-primary-600);
          color: white;
        }
        
        .btn--secondary {
          background: var(--color-neutral-200);
          color: var(--color-neutral-900);
        }
        
        .btn--danger {
          background: var(--color-danger-600);
          color: white;
        }
        
        .btn--ghost {
          background: transparent;
          color: var(--color-primary-600);
          border: 1px solid var(--color-primary-200);
        }
        
        /* Sizes */
        .btn--sm {
          padding: var(--spacing-2) var(--spacing-3);
          font-size: var(--font-size-sm);
        }
        
        .btn--md {
          padding: var(--spacing-3) var(--spacing-4);
          font-size: var(--font-size-base);
        }
        
        .btn--lg {
          padding: var(--spacing-4) var(--spacing-6);
          font-size: var(--font-size-lg);
        }
        
        .loading-spinner {
          width: 1em;
          height: 1em;
          border: 2px solid transparent;
          border-top: 2px solid currentColor;
          border-radius: 50%;
          animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
      </style>
      
      <button 
        class="btn btn--${variant} btn--${size}"
        ${disabled ? 'disabled' : ''}
        aria-label="${this.getAttribute('aria-label') || this.textContent}"
      >
        ${loading ? '<div class="loading-spinner"></div>' : ''}
        <slot></slot>
      </button>
    `;

    this.shadowRoot.querySelector('button').addEventListener('click', (e) => {
      if (!disabled && !loading) {
        this.dispatchEvent(new CustomEvent('click', { bubbles: true, detail: e }));
      }
    });
  }
}

customElements.define('game-button', GameButton);
```

#### 2. Icon（アイコン）
```javascript
class GameIcon extends HTMLElement {
  static get observedAttributes() {
    return ['name', 'size', 'color'];
  }

  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
  }

  render() {
    const name = this.getAttribute('name');
    const size = this.getAttribute('size') || '24';
    const color = this.getAttribute('color') || 'currentColor';

    // SVGスプライトシステムを使用
    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: inline-block;
          width: ${size}px;
          height: ${size}px;
        }
        
        svg {
          width: 100%;
          height: 100%;
          fill: ${color};
        }
      </style>
      
      <svg aria-hidden="true">
        <use href="#icon-${name}"></use>
      </svg>
    `;
  }
}

customElements.define('game-icon', GameIcon);
```

#### 3. Progress Bar（プログレスバー）
```javascript
class GameProgressBar extends HTMLElement {
  static get observedAttributes() {
    return ['value', 'max', 'variant', 'show-text'];
  }

  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
  }

  render() {
    const value = parseFloat(this.getAttribute('value')) || 0;
    const max = parseFloat(this.getAttribute('max')) || 100;
    const variant = this.getAttribute('variant') || 'primary';
    const showText = this.hasAttribute('show-text');
    
    const percentage = Math.min((value / max) * 100, 100);

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          width: 100%;
        }
        
        .progress-container {
          position: relative;
          background: var(--color-neutral-200);
          border-radius: var(--border-radius-full);
          overflow: hidden;
          height: var(--spacing-4);
        }
        
        .progress-bar {
          height: 100%;
          transition: width var(--transition-duration-normal);
          border-radius: inherit;
        }
        
        .progress-bar--hp {
          background: linear-gradient(90deg, var(--color-hp-400), var(--color-hp-600));
        }
        
        .progress-bar--sp {
          background: linear-gradient(90deg, var(--color-sp-400), var(--color-sp-600));
        }
        
        .progress-bar--exp {
          background: linear-gradient(90deg, var(--color-exp-400), var(--color-exp-600));
        }
        
        .progress-text {
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          font-size: var(--font-size-xs);
          font-weight: var(--font-weight-medium);
          color: var(--color-neutral-700);
          text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }
        
        /* 低い値での警告色 */
        .progress-bar--hp[style*="width: 0%"],
        .progress-bar--hp[style*="width: 1%"],
        .progress-bar--hp[style*="width: 2%"] {
          background: var(--color-danger-600);
          animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.7; }
        }
      </style>
      
      <div class="progress-container" role="progressbar" aria-valuenow="${value}" aria-valuemax="${max}">
        <div class="progress-bar progress-bar--${variant}" style="width: ${percentage}%"></div>
        ${showText ? `<div class="progress-text">${value}/${max}</div>` : ''}
      </div>
    `;
  }
}

customElements.define('game-progress-bar', GameProgressBar);
```

### Molecules（分子）- 複合コンポーネント

#### 1. Status Display（ステータス表示）
```javascript
class StatusDisplay extends HTMLElement {
  static get observedAttributes() {
    return ['hp', 'max-hp', 'sp', 'max-sp', 'exp', 'max-exp', 'level'];
  }

  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
  }

  render() {
    const hp = parseInt(this.getAttribute('hp')) || 0;
    const maxHp = parseInt(this.getAttribute('max-hp')) || 100;
    const sp = parseInt(this.getAttribute('sp')) || 0;
    const maxSp = parseInt(this.getAttribute('max-sp')) || 100;
    const exp = parseInt(this.getAttribute('exp')) || 0;
    const maxExp = parseInt(this.getAttribute('max-exp')) || 100;
    const level = parseInt(this.getAttribute('level')) || 1;

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          background: var(--color-surface);
          border-radius: var(--border-radius-lg);
          padding: var(--spacing-4);
          box-shadow: var(--shadow-sm);
        }
        
        .status-grid {
          display: grid;
          gap: var(--spacing-3);
        }
        
        .status-row {
          display: flex;
          align-items: center;
          gap: var(--spacing-3);
        }
        
        .status-label {
          font-weight: var(--font-weight-medium);
          min-width: 32px;
          display: flex;
          align-items: center;
          gap: var(--spacing-1);
        }
        
        .status-value {
          flex: 1;
        }
        
        .level-display {
          text-align: center;
          font-size: var(--font-size-lg);
          font-weight: var(--font-weight-bold);
          color: var(--color-primary-700);
          margin-bottom: var(--spacing-2);
        }
        
        .hp-label { color: var(--color-hp-600); }
        .sp-label { color: var(--color-sp-600); }
        .exp-label { color: var(--color-exp-600); }
      </style>
      
      <div class="status-grid">
        <div class="level-display">Level ${level}</div>
        
        <div class="status-row">
          <div class="status-label hp-label">
            <game-icon name="heart" size="16"></game-icon>
            HP
          </div>
          <div class="status-value">
            <game-progress-bar 
              value="${hp}" 
              max="${maxHp}" 
              variant="hp" 
              show-text>
            </game-progress-bar>
          </div>
        </div>
        
        <div class="status-row">
          <div class="status-label sp-label">
            <game-icon name="zap" size="16"></game-icon>
            SP
          </div>
          <div class="status-value">
            <game-progress-bar 
              value="${sp}" 
              max="${maxSp}" 
              variant="sp" 
              show-text>
            </game-progress-bar>
          </div>
        </div>
        
        <div class="status-row">
          <div class="status-label exp-label">
            <game-icon name="star" size="16"></game-icon>
            EXP
          </div>
          <div class="status-value">
            <game-progress-bar 
              value="${exp}" 
              max="${maxExp}" 
              variant="exp" 
              show-text>
            </game-progress-bar>
          </div>
        </div>
      </div>
    `;
  }
}

customElements.define('status-display', StatusDisplay);
```

#### 2. Item Card（アイテムカード）
```javascript
class ItemCard extends HTMLElement {
  static get observedAttributes() {
    return ['item-id', 'name', 'description', 'rarity', 'quantity', 'icon'];
  }

  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.addEventListener('click', this.handleClick.bind(this));
  }

  connectedCallback() {
    this.render();
  }

  render() {
    const itemId = this.getAttribute('item-id');
    const name = this.getAttribute('name') || 'Unknown Item';
    const description = this.getAttribute('description') || '';
    const rarity = this.getAttribute('rarity') || 'common';
    const quantity = this.getAttribute('quantity') || '1';
    const icon = this.getAttribute('icon') || 'package';

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          cursor: pointer;
        }
        
        .item-card {
          background: var(--color-surface);
          border-radius: var(--border-radius-md);
          padding: var(--spacing-3);
          border: 2px solid transparent;
          transition: all var(--transition-duration-normal);
          position: relative;
          overflow: hidden;
        }
        
        .item-card:hover {
          transform: translateY(-2px);
          box-shadow: var(--shadow-md);
        }
        
        .item-card:focus {
          outline: none;
          border-color: var(--color-primary-500);
        }
        
        /* Rarity borders */
        .item-card--common { border-color: var(--color-neutral-300); }
        .item-card--uncommon { border-color: var(--color-item-uncommon); }
        .item-card--rare { border-color: var(--color-item-rare); }
        .item-card--epic { border-color: var(--color-item-epic); }
        .item-card--legendary { border-color: var(--color-item-legendary); }
        
        .item-header {
          display: flex;
          align-items: center;
          gap: var(--spacing-2);
          margin-bottom: var(--spacing-2);
        }
        
        .item-icon {
          width: 32px;
          height: 32px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: var(--color-neutral-100);
          border-radius: var(--border-radius-sm);
        }
        
        .item-name {
          font-weight: var(--font-weight-medium);
          flex: 1;
          font-size: var(--font-size-sm);
        }
        
        .item-quantity {
          background: var(--color-primary-100);
          color: var(--color-primary-700);
          padding: var(--spacing-1) var(--spacing-2);
          border-radius: var(--border-radius-full);
          font-size: var(--font-size-xs);
          font-weight: var(--font-weight-medium);
        }
        
        .item-description {
          font-size: var(--font-size-xs);
          color: var(--color-text-secondary);
          line-height: 1.4;
        }
        
        .rarity-indicator {
          position: absolute;
          top: 0;
          right: 0;
          width: 0;
          height: 0;
          border-style: solid;
          border-width: 0 20px 20px 0;
        }
        
        .rarity-indicator--uncommon { border-color: transparent var(--color-item-uncommon) transparent transparent; }
        .rarity-indicator--rare { border-color: transparent var(--color-item-rare) transparent transparent; }
        .rarity-indicator--epic { border-color: transparent var(--color-item-epic) transparent transparent; }
        .rarity-indicator--legendary { border-color: transparent var(--color-item-legendary) transparent transparent; }
      </style>
      
      <div 
        class="item-card item-card--${rarity}" 
        tabindex="0" 
        role="button"
        aria-label="${name} ${description ? ': ' + description : ''}"
      >
        ${rarity !== 'common' ? `<div class="rarity-indicator rarity-indicator--${rarity}"></div>` : ''}
        
        <div class="item-header">
          <div class="item-icon">
            <game-icon name="${icon}" size="20"></game-icon>
          </div>
          <div class="item-name">${name}</div>
          ${quantity > 1 ? `<div class="item-quantity">${quantity}</div>` : ''}
        </div>
        
        ${description ? `<div class="item-description">${description}</div>` : ''}
      </div>
    `;
  }

  handleClick(event) {
    const itemId = this.getAttribute('item-id');
    this.dispatchEvent(new CustomEvent('item-selected', {
      bubbles: true,
      detail: { itemId, element: this }
    }));
  }
}

customElements.define('item-card', ItemCard);
```

### Organisms（組織）- 機能ブロック

#### 1. Character Panel（キャラクターパネル）
```javascript
class CharacterPanel extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.characterData = null;
  }

  connectedCallback() {
    this.render();
    this.setupEventListeners();
  }

  setCharacterData(data) {
    this.characterData = data;
    this.render();
  }

  render() {
    if (!this.characterData) {
      this.shadowRoot.innerHTML = '<div>Loading...</div>';
      return;
    }

    const { name, level, hp, maxHp, sp, maxSp, exp, maxExp, gold, location } = this.characterData;

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          background: var(--color-surface);
          border-radius: var(--border-radius-lg);
          padding: var(--spacing-4);
          box-shadow: var(--shadow-md);
        }
        
        .character-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: var(--spacing-4);
          padding-bottom: var(--spacing-3);
          border-bottom: 1px solid var(--color-border);
        }
        
        .character-name {
          font-size: var(--font-size-xl);
          font-weight: var(--font-weight-bold);
          color: var(--color-text-primary);
        }
        
        .location-info {
          display: flex;
          align-items: center;
          gap: var(--spacing-2);
          color: var(--color-text-secondary);
          font-size: var(--font-size-sm);
        }
        
        .gold-display {
          display: flex;
          align-items: center;
          gap: var(--spacing-2);
          background: var(--color-gold-50);
          color: var(--color-gold-700);
          padding: var(--spacing-2) var(--spacing-3);
          border-radius: var(--border-radius-md);
          font-weight: var(--font-weight-medium);
          margin-top: var(--spacing-3);
        }
        
        .actions {
          display: flex;
          gap: var(--spacing-2);
          margin-top: var(--spacing-4);
        }
        
        @media (max-width: 768px) {
          .character-header {
            flex-direction: column;
            gap: var(--spacing-2);
            align-items: flex-start;
          }
          
          .actions {
            flex-direction: column;
          }
        }
      </style>
      
      <div class="character-header">
        <div>
          <div class="character-name">${name}</div>
          <div class="location-info">
            <game-icon name="map-pin" size="16"></game-icon>
            ${location}
          </div>
        </div>
      </div>
      
      <status-display
        hp="${hp}"
        max-hp="${maxHp}"
        sp="${sp}"
        max-sp="${maxSp}"
        exp="${exp}"
        max-exp="${maxExp}"
        level="${level}">
      </status-display>
      
      <div class="gold-display">
        <game-icon name="coins" size="20"></game-icon>
        ${gold.toLocaleString()} G
      </div>
      
      <div class="actions">
        <game-button variant="primary" size="sm">
          <game-icon name="user" size="16"></game-icon>
          ステータス
        </game-button>
        <game-button variant="secondary" size="sm">
          <game-icon name="package" size="16"></game-icon>
          インベントリ
        </game-button>
        <game-button variant="secondary" size="sm">
          <game-icon name="settings" size="16"></game-icon>
          設定
        </game-button>
      </div>
    `;
  }

  setupEventListeners() {
    this.shadowRoot.addEventListener('click', (event) => {
      const button = event.target.closest('game-button');
      if (button) {
        const action = button.textContent.trim();
        this.dispatchEvent(new CustomEvent('character-action', {
          bubbles: true,
          detail: { action }
        }));
      }
    });
  }
}

customElements.define('character-panel', CharacterPanel);
```

#### 2. Inventory Grid（インベントリグリッド）
```javascript
class InventoryGrid extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.items = [];
    this.selectedItems = new Set();
  }

  connectedCallback() {
    this.render();
    this.setupEventListeners();
  }

  setItems(items) {
    this.items = items;
    this.render();
  }

  render() {
    const gridSize = 20; // 5x4 grid
    const emptySlots = Math.max(0, gridSize - this.items.length);

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
        }
        
        .inventory-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: var(--spacing-4);
        }
        
        .inventory-title {
          font-size: var(--font-size-lg);
          font-weight: var(--font-weight-bold);
        }
        
        .inventory-info {
          color: var(--color-text-secondary);
          font-size: var(--font-size-sm);
        }
        
        .inventory-grid {
          display: grid;
          grid-template-columns: repeat(5, 1fr);
          gap: var(--spacing-2);
          max-width: 400px;
        }
        
        .empty-slot {
          aspect-ratio: 1;
          border: 2px dashed var(--color-border);
          border-radius: var(--border-radius-md);
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--color-text-disabled);
          background: var(--color-surface-secondary);
          transition: all var(--transition-duration-normal);
        }
        
        .empty-slot:hover {
          border-color: var(--color-primary-300);
          background: var(--color-primary-50);
        }
        
        .inventory-actions {
          display: flex;
          gap: var(--spacing-2);
          margin-top: var(--spacing-4);
          flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
          .inventory-grid {
            grid-template-columns: repeat(4, 1fr);
            max-width: 320px;
          }
        }
      </style>
      
      <div class="inventory-header">
        <div class="inventory-title">インベントリ</div>
        <div class="inventory-info">${this.items.length}/${gridSize}</div>
      </div>
      
      <div class="inventory-grid">
        ${this.items.map(item => `
          <item-card
            item-id="${item.id}"
            name="${item.name}"
            description="${item.description || ''}"
            rarity="${item.rarity || 'common'}"
            quantity="${item.quantity || 1}"
            icon="${item.icon || 'package'}">
          </item-card>
        `).join('')}
        
        ${Array(emptySlots).fill().map(() => `
          <div class="empty-slot">
            <game-icon name="plus" size="16"></game-icon>
          </div>
        `).join('')}
      </div>
      
      <div class="inventory-actions">
        <game-button variant="secondary" size="sm">
          <game-icon name="sort-asc" size="16"></game-icon>
          ソート
        </game-button>
        <game-button variant="secondary" size="sm">
          <game-icon name="filter" size="16"></game-icon>
          フィルター
        </game-button>
        <game-button variant="danger" size="sm" id="delete-btn" disabled>
          <game-icon name="trash" size="16"></game-icon>
          削除
        </game-button>
      </div>
    `;
  }

  setupEventListeners() {
    this.shadowRoot.addEventListener('item-selected', (event) => {
      const { itemId } = event.detail;
      
      if (this.selectedItems.has(itemId)) {
        this.selectedItems.delete(itemId);
      } else {
        this.selectedItems.add(itemId);
      }
      
      this.updateSelectionUI();
      
      this.dispatchEvent(new CustomEvent('selection-changed', {
        bubbles: true,
        detail: { selectedItems: Array.from(this.selectedItems) }
      }));
    });

    // Action buttons
    this.shadowRoot.addEventListener('click', (event) => {
      const button = event.target.closest('game-button');
      if (button) {
        const action = button.id || button.textContent.trim();
        this.dispatchEvent(new CustomEvent('inventory-action', {
          bubbles: true,
          detail: { action, selectedItems: Array.from(this.selectedItems) }
        }));
      }
    });
  }

  updateSelectionUI() {
    const deleteBtn = this.shadowRoot.getElementById('delete-btn');
    deleteBtn.disabled = this.selectedItems.size === 0;
    
    // Update selected item styling
    const itemCards = this.shadowRoot.querySelectorAll('item-card');
    itemCards.forEach(card => {
      const itemId = card.getAttribute('item-id');
      card.style.opacity = this.selectedItems.has(itemId) ? '0.7' : '1';
    });
  }
}

customElements.define('inventory-grid', InventoryGrid);
```

#### 3. Battle Interface（バトルインターフェース）
```javascript
class BattleInterface extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.battleState = null;
  }

  connectedCallback() {
    this.render();
    this.setupEventListeners();
  }

  setBattleState(state) {
    this.battleState = state;
    this.render();
  }

  render() {
    if (!this.battleState) {
      this.shadowRoot.innerHTML = '<div>バトル準備中...</div>';
      return;
    }

    const { player, enemy, turn, actions, battleLog } = this.battleState;

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          background: linear-gradient(135deg, var(--color-surface) 0%, var(--color-surface-secondary) 100%);
          border-radius: var(--border-radius-lg);
          padding: var(--spacing-4);
          box-shadow: var(--shadow-lg);
        }
        
        .battle-field {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: var(--spacing-6);
          margin-bottom: var(--spacing-6);
        }
        
        .combatant {
          text-align: center;
        }
        
        .combatant-avatar {
          width: 120px;
          height: 120px;
          margin: 0 auto var(--spacing-4);
          background: var(--color-neutral-200);
          border-radius: var(--border-radius-lg);
          display: flex;
          align-items: center;
          justify-content: center;
          position: relative;
          overflow: hidden;
        }
        
        .player-avatar {
          background: linear-gradient(135deg, var(--color-primary-100), var(--color-primary-200));
          border: 3px solid var(--color-primary-400);
        }
        
        .enemy-avatar {
          background: linear-gradient(135deg, var(--color-danger-100), var(--color-danger-200));
          border: 3px solid var(--color-danger-400);
        }
        
        .combatant-name {
          font-size: var(--font-size-lg);
          font-weight: var(--font-weight-bold);
          margin-bottom: var(--spacing-2);
        }
        
        .combatant-hp {
          margin-bottom: var(--spacing-1);
        }
        
        .turn-indicator {
          position: absolute;
          top: -10px;
          right: -10px;
          background: var(--color-primary-600);
          color: white;
          border-radius: 50%;
          width: 24px;
          height: 24px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: var(--font-size-xs);
          font-weight: var(--font-weight-bold);
          animation: pulse 1s infinite;
        }
        
        .battle-actions {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
          gap: var(--spacing-2);
          margin-bottom: var(--spacing-4);
        }
        
        .battle-log {
          background: var(--color-surface);
          border-radius: var(--border-radius-md);
          padding: var(--spacing-3);
          max-height: 200px;
          overflow-y: auto;
          border: 1px solid var(--color-border);
        }
        
        .battle-log-title {
          font-weight: var(--font-weight-medium);
          margin-bottom: var(--spacing-2);
          color: var(--color-text-secondary);
        }
        
        .log-entry {
          padding: var(--spacing-1) 0;
          font-size: var(--font-size-sm);
          border-bottom: 1px solid var(--color-border-light);
        }
        
        .log-entry:last-child {
          border-bottom: none;
        }
        
        .log-entry--damage {
          color: var(--color-danger-600);
        }
        
        .log-entry--heal {
          color: var(--color-success-600);
        }
        
        .log-entry--action {
          color: var(--color-primary-600);
        }
        
        @media (max-width: 768px) {
          .battle-field {
            grid-template-columns: 1fr;
            gap: var(--spacing-4);
          }
          
          .combatant-avatar {
            width: 80px;
            height: 80px;
          }
          
          .battle-actions {
            grid-template-columns: 1fr 1fr;
          }
        }
      </style>
      
      <div class="battle-field">
        <div class="combatant">
          <div class="combatant-avatar player-avatar">
            ${turn === 'player' ? '<div class="turn-indicator">●</div>' : ''}
            <game-icon name="user" size="48"></game-icon>
          </div>
          <div class="combatant-name">${player.name}</div>
          <div class="combatant-hp">
            <game-progress-bar 
              value="${player.hp}" 
              max="${player.maxHp}" 
              variant="hp" 
              show-text>
            </game-progress-bar>
          </div>
          <div class="combatant-sp">
            <game-progress-bar 
              value="${player.sp}" 
              max="${player.maxSp}" 
              variant="sp" 
              show-text>
            </game-progress-bar>
          </div>
        </div>
        
        <div class="combatant">
          <div class="combatant-avatar enemy-avatar">
            ${turn === 'enemy' ? '<div class="turn-indicator">●</div>' : ''}
            <game-icon name="skull" size="48"></game-icon>
          </div>
          <div class="combatant-name">${enemy.name}</div>
          <div class="combatant-hp">
            <game-progress-bar 
              value="${enemy.hp}" 
              max="${enemy.maxHp}" 
              variant="hp" 
              show-text>
            </game-progress-bar>
          </div>
        </div>
      </div>
      
      <div class="battle-actions">
        ${actions.map(action => `
          <game-button 
            variant="${action.type === 'attack' ? 'danger' : action.type === 'defend' ? 'secondary' : 'primary'}"
            ${!action.available ? 'disabled' : ''}
            data-action="${action.id}">
            <game-icon name="${action.icon}" size="16"></game-icon>
            ${action.name}
            ${action.spCost ? `(-${action.spCost} SP)` : ''}
          </game-button>
        `).join('')}
      </div>
      
      <div class="battle-log">
        <div class="battle-log-title">バトルログ</div>
        ${battleLog.map(entry => `
          <div class="log-entry log-entry--${entry.type}">
            ${entry.message}
          </div>
        `).join('')}
      </div>
    `;
  }

  setupEventListeners() {
    this.shadowRoot.addEventListener('click', (event) => {
      const button = event.target.closest('game-button[data-action]');
      if (button && !button.disabled) {
        const actionId = button.getAttribute('data-action');
        this.dispatchEvent(new CustomEvent('battle-action', {
          bubbles: true,
          detail: { actionId }
        }));
      }
    });
  }
}

customElements.define('battle-interface', BattleInterface);
```

### Templates（テンプレート）- ページレイアウト

#### 1. Game Layout（ゲームレイアウト）
```javascript
class GameLayout extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
    this.setupEventListeners();
  }

  render() {
    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          min-height: 100vh;
          background: var(--color-background);
        }
        
        .game-container {
          display: grid;
          grid-template-areas: 
            "header header"
            "sidebar main"
            "footer footer";
          grid-template-columns: 300px 1fr;
          grid-template-rows: auto 1fr auto;
          min-height: 100vh;
          max-width: 1400px;
          margin: 0 auto;
          gap: var(--spacing-4);
          padding: var(--spacing-4);
        }
        
        .game-header {
          grid-area: header;
          background: var(--color-surface);
          border-radius: var(--border-radius-lg);
          padding: var(--spacing-4);
          box-shadow: var(--shadow-sm);
        }
        
        .game-sidebar {
          grid-area: sidebar;
          background: var(--color-surface);
          border-radius: var(--border-radius-lg);
          padding: var(--spacing-4);
          box-shadow: var(--shadow-sm);
          overflow-y: auto;
          max-height: calc(100vh - 200px);
        }
        
        .game-main {
          grid-area: main;
          background: var(--color-surface);
          border-radius: var(--border-radius-lg);
          padding: var(--spacing-4);
          box-shadow: var(--shadow-sm);
          overflow-y: auto;
          max-height: calc(100vh - 200px);
        }
        
        .game-footer {
          grid-area: footer;
          background: var(--color-surface);
          border-radius: var(--border-radius-lg);
          padding: var(--spacing-3);
          box-shadow: var(--shadow-sm);
          text-align: center;
          color: var(--color-text-secondary);
          font-size: var(--font-size-sm);
        }
        
        @media (max-width: 1024px) {
          .game-container {
            grid-template-areas: 
              "header"
              "sidebar"
              "main"
              "footer";
            grid-template-columns: 1fr;
            grid-template-rows: auto auto 1fr auto;
          }
          
          .game-sidebar,
          .game-main {
            max-height: none;
          }
        }
        
        @media (max-width: 768px) {
          .game-container {
            padding: var(--spacing-2);
            gap: var(--spacing-2);
          }
          
          .game-header,
          .game-sidebar,
          .game-main,
          .game-footer {
            padding: var(--spacing-3);
          }
        }
      </style>
      
      <div class="game-container">
        <header class="game-header">
          <slot name="header">
            <div>test_smg - Simple Management Game</div>
          </slot>
        </header>
        
        <aside class="game-sidebar">
          <slot name="sidebar">
            <div>サイドバーコンテンツ</div>
          </slot>
        </aside>
        
        <main class="game-main">
          <slot name="main">
            <div>メインコンテンツ</div>
          </slot>
        </main>
        
        <footer class="game-footer">
          <slot name="footer">
            <div>&copy; 2025 test_smg Project</div>
          </slot>
        </footer>
      </div>
    `;
  }

  setupEventListeners() {
    // レスポンシブ対応のためのリスナー
    window.addEventListener('resize', this.handleResize.bind(this));
  }

  handleResize() {
    // 必要に応じてレイアウト調整
    this.dispatchEvent(new CustomEvent('layout-changed', {
      bubbles: true,
      detail: { width: window.innerWidth, height: window.innerHeight }
    }));
  }
}

customElements.define('game-layout', GameLayout);
```

## データ表示コンポーネント

### 1. Data Table（データテーブル）
```javascript
class DataTable extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.data = [];
    this.columns = [];
    this.sortColumn = null;
    this.sortDirection = 'asc';
  }

  static get observedAttributes() {
    return ['sortable', 'pagination'];
  }

  connectedCallback() {
    this.render();
    this.setupEventListeners();
  }

  setData(data, columns) {
    this.data = data;
    this.columns = columns;
    this.render();
  }

  render() {
    const sortable = this.hasAttribute('sortable');
    const pagination = this.hasAttribute('pagination');

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          background: var(--color-surface);
          border-radius: var(--border-radius-lg);
          overflow: hidden;
          box-shadow: var(--shadow-sm);
        }
        
        .table-container {
          overflow-x: auto;
        }
        
        .table {
          width: 100%;
          border-collapse: collapse;
        }
        
        .table th,
        .table td {
          padding: var(--spacing-3);
          text-align: left;
          border-bottom: 1px solid var(--color-border);
        }
        
        .table th {
          background: var(--color-surface-secondary);
          font-weight: var(--font-weight-medium);
          color: var(--color-text-secondary);
          position: sticky;
          top: 0;
          z-index: 1;
        }
        
        .table th.sortable {
          cursor: pointer;
          user-select: none;
          position: relative;
        }
        
        .table th.sortable:hover {
          background: var(--color-primary-50);
          color: var(--color-primary-700);
        }
        
        .sort-indicator {
          margin-left: var(--spacing-1);
          opacity: 0.5;
        }
        
        .sort-indicator.active {
          opacity: 1;
          color: var(--color-primary-600);
        }
        
        .table tr:hover {
          background: var(--color-surface-secondary);
        }
        
        .table tr:last-child td {
          border-bottom: none;
        }
        
        .pagination {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: var(--spacing-3) var(--spacing-4);
          background: var(--color-surface-secondary);
        }
        
        .pagination-info {
          color: var(--color-text-secondary);
          font-size: var(--font-size-sm);
        }
        
        .pagination-controls {
          display: flex;
          gap: var(--spacing-2);
        }
      </style>
      
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              ${this.columns.map(column => `
                <th class="${sortable ? 'sortable' : ''}" data-column="${column.key}">
                  ${column.label}
                  ${sortable ? `
                    <span class="sort-indicator ${this.sortColumn === column.key ? 'active' : ''}">
                      ${this.sortColumn === column.key && this.sortDirection === 'desc' ? '↓' : '↑'}
                    </span>
                  ` : ''}
                </th>
              `).join('')}
            </tr>
          </thead>
          <tbody>
            ${this.data.map(row => `
              <tr>
                ${this.columns.map(column => `
                  <td>${this.formatCellValue(row[column.key], column)}</td>
                `).join('')}
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
      
      ${pagination ? `
        <div class="pagination">
          <div class="pagination-info">
            ${this.data.length} 件中 1-${this.data.length} を表示
          </div>
          <div class="pagination-controls">
            <game-button variant="ghost" size="sm" disabled>前へ</game-button>
            <game-button variant="ghost" size="sm" disabled>次へ</game-button>
          </div>
        </div>
      ` : ''}
    `;
  }

  formatCellValue(value, column) {
    if (column.formatter) {
      return column.formatter(value);
    }
    
    if (column.type === 'number') {
      return typeof value === 'number' ? value.toLocaleString() : value;
    }
    
    if (column.type === 'date') {
      return value instanceof Date ? value.toLocaleDateString() : value;
    }
    
    return value;
  }

  setupEventListeners() {
    if (this.hasAttribute('sortable')) {
      this.shadowRoot.addEventListener('click', (event) => {
        const th = event.target.closest('th.sortable');
        if (th) {
          const column = th.getAttribute('data-column');
          this.handleSort(column);
        }
      });
    }
  }

  handleSort(column) {
    if (this.sortColumn === column) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortColumn = column;
      this.sortDirection = 'asc';
    }

    this.data.sort((a, b) => {
      const aVal = a[column];
      const bVal = b[column];
      
      if (typeof aVal === 'number' && typeof bVal === 'number') {
        return this.sortDirection === 'asc' ? aVal - bVal : bVal - aVal;
      }
      
      const aStr = String(aVal).toLowerCase();
      const bStr = String(bVal).toLowerCase();
      
      if (this.sortDirection === 'asc') {
        return aStr.localeCompare(bStr);
      } else {
        return bStr.localeCompare(aStr);
      }
    });

    this.render();
    
    this.dispatchEvent(new CustomEvent('table-sorted', {
      bubbles: true,
      detail: { column, direction: this.sortDirection }
    }));
  }
}

customElements.define('data-table', DataTable);
```

## フォームコンポーネント

### 1. Form Field（フォームフィールド）
```javascript
class FormField extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  static get observedAttributes() {
    return ['label', 'type', 'required', 'error', 'help'];
  }

  connectedCallback() {
    this.render();
    this.setupEventListeners();
  }

  render() {
    const label = this.getAttribute('label') || '';
    const type = this.getAttribute('type') || 'text';
    const required = this.hasAttribute('required');
    const error = this.getAttribute('error') || '';
    const help = this.getAttribute('help') || '';
    const fieldId = `field-${Math.random().toString(36).substr(2, 9)}`;

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          margin-bottom: var(--spacing-4);
        }
        
        .field-group {
          display: flex;
          flex-direction: column;
          gap: var(--spacing-1);
        }
        
        .field-label {
          font-weight: var(--font-weight-medium);
          color: var(--color-text-primary);
          font-size: var(--font-size-sm);
        }
        
        .field-label .required {
          color: var(--color-danger-600);
          margin-left: var(--spacing-1);
        }
        
        .field-input {
          padding: var(--spacing-3);
          border: 1px solid var(--color-border);
          border-radius: var(--border-radius-md);
          font-size: var(--font-size-base);
          transition: all var(--transition-duration-normal);
          background: var(--color-surface);
        }
        
        .field-input:focus {
          outline: none;
          border-color: var(--color-primary-500);
          box-shadow: 0 0 0 3px var(--color-primary-100);
        }
        
        .field-input.error {
          border-color: var(--color-danger-500);
        }
        
        .field-input.error:focus {
          border-color: var(--color-danger-500);
          box-shadow: 0 0 0 3px var(--color-danger-100);
        }
        
        .field-help {
          font-size: var(--font-size-xs);
          color: var(--color-text-secondary);
        }
        
        .field-error {
          font-size: var(--font-size-xs);
          color: var(--color-danger-600);
          display: flex;
          align-items: center;
          gap: var(--spacing-1);
        }
        
        select.field-input {
          cursor: pointer;
        }
        
        textarea.field-input {
          resize: vertical;
          min-height: 80px;
        }
      </style>
      
      <div class="field-group">
        ${label ? `
          <label for="${fieldId}" class="field-label">
            ${label}
            ${required ? '<span class="required">*</span>' : ''}
          </label>
        ` : ''}
        
        <slot name="input">
          ${this.renderInput(type, fieldId, error)}
        </slot>
        
        ${help && !error ? `<div class="field-help">${help}</div>` : ''}
        ${error ? `
          <div class="field-error">
            <game-icon name="alert-circle" size="12"></game-icon>
            ${error}
          </div>
        ` : ''}
      </div>
    `;
  }

  renderInput(type, fieldId, error) {
    const commonAttrs = `
      id="${fieldId}"
      class="field-input ${error ? 'error' : ''}"
      ${this.hasAttribute('required') ? 'required' : ''}
      ${this.hasAttribute('disabled') ? 'disabled' : ''}
    `;

    switch (type) {
      case 'textarea':
        return `<textarea ${commonAttrs} placeholder="${this.getAttribute('placeholder') || ''}"></textarea>`;
      
      case 'select':
        return `
          <select ${commonAttrs}>
            <slot name="options"></slot>
          </select>
        `;
      
      default:
        return `
          <input 
            type="${type}"
            ${commonAttrs}
            placeholder="${this.getAttribute('placeholder') || ''}"
            value="${this.getAttribute('value') || ''}"
          />
        `;
    }
  }

  setupEventListeners() {
    const input = this.shadowRoot.querySelector('.field-input');
    if (input) {
      input.addEventListener('input', (event) => {
        this.dispatchEvent(new CustomEvent('field-change', {
          bubbles: true,
          detail: { value: event.target.value, name: this.getAttribute('name') }
        }));
      });

      input.addEventListener('blur', (event) => {
        this.dispatchEvent(new CustomEvent('field-blur', {
          bubbles: true,
          detail: { value: event.target.value, name: this.getAttribute('name') }
        }));
      });
    }
  }

  get value() {
    const input = this.shadowRoot.querySelector('.field-input');
    return input ? input.value : '';
  }

  set value(val) {
    const input = this.shadowRoot.querySelector('.field-input');
    if (input) {
      input.value = val;
    }
  }
}

customElements.define('form-field', FormField);
```

## 状態管理とイベント

### 1. Game State Manager（ゲーム状態管理）
```javascript
class GameStateManager {
  constructor() {
    this.state = {
      player: null,
      location: null,
      inventory: [],
      ui: {
        activeModal: null,
        loading: false,
        notifications: []
      },
      battle: null
    };
    
    this.listeners = new Map();
    this.history = [];
    this.maxHistorySize = 10;
  }

  // 状態の取得
  getState(path = null) {
    if (!path) return this.state;
    
    return path.split('.').reduce((obj, key) => {
      return obj && obj[key] !== undefined ? obj[key] : null;
    }, this.state);
  }

  // 状態の更新
  setState(path, value) {
    // 履歴保存
    this.saveToHistory();
    
    // 状態更新
    const keys = path.split('.');
    let current = this.state;
    
    for (let i = 0; i < keys.length - 1; i++) {
      const key = keys[i];
      if (!current[key] || typeof current[key] !== 'object') {
        current[key] = {};
      }
      current = current[key];
    }
    
    current[keys[keys.length - 1]] = value;
    
    // リスナーに通知
    this.notifyListeners(path, value);
  }

  // リスナー登録
  subscribe(path, callback) {
    if (!this.listeners.has(path)) {
      this.listeners.set(path, new Set());
    }
    
    this.listeners.get(path).add(callback);
    
    // 購読解除関数を返す
    return () => {
      const pathListeners = this.listeners.get(path);
      if (pathListeners) {
        pathListeners.delete(callback);
        if (pathListeners.size === 0) {
          this.listeners.delete(path);
        }
      }
    };
  }

  // リスナーに通知
  notifyListeners(path, value) {
    // 完全一致のリスナー
    const exactListeners = this.listeners.get(path);
    if (exactListeners) {
      exactListeners.forEach(callback => callback(value, path));
    }
    
    // 部分一致のリスナー（親パス）
    const pathParts = path.split('.');
    for (let i = pathParts.length - 1; i > 0; i--) {
      const parentPath = pathParts.slice(0, i).join('.');
      const parentListeners = this.listeners.get(parentPath);
      if (parentListeners) {
        parentListeners.forEach(callback => callback(this.getState(parentPath), parentPath));
      }
    }
    
    // 全体変更リスナー
    const globalListeners = this.listeners.get('*');
    if (globalListeners) {
      globalListeners.forEach(callback => callback(this.state, '*'));
    }
  }

  // 履歴保存
  saveToHistory() {
    this.history.push(JSON.parse(JSON.stringify(this.state)));
    if (this.history.length > this.maxHistorySize) {
      this.history.shift();
    }
  }

  // 状態の復元
  undo() {
    if (this.history.length > 0) {
      this.state = this.history.pop();
      this.notifyListeners('*', this.state);
    }
  }

  // アクション実行
  async dispatch(action) {
    try {
      const result = await this.executeAction(action);
      return result;
    } catch (error) {
      console.error('Action failed:', error);
      this.setState('ui.notifications', [
        ...this.getState('ui.notifications'),
        {
          id: Date.now(),
          type: 'error',
          message: error.message,
          timestamp: new Date()
        }
      ]);
      throw error;
    }
  }

  async executeAction(action) {
    switch (action.type) {
      case 'LOAD_PLAYER':
        this.setState('ui.loading', true);
        try {
          const player = await this.fetchPlayer(action.payload.playerId);
          this.setState('player', player);
          return player;
        } finally {
          this.setState('ui.loading', false);
        }

      case 'UPDATE_HP':
        const currentHp = this.getState('player.hp');
        const newHp = Math.max(0, currentHp + action.payload.delta);
        this.setState('player.hp', newHp);
        
        if (newHp === 0) {
          this.dispatch({ type: 'PLAYER_DEFEATED' });
        }
        return newHp;

      case 'ADD_ITEM':
        const inventory = this.getState('inventory') || [];
        const existingItem = inventory.find(item => item.id === action.payload.itemId);
        
        if (existingItem) {
          existingItem.quantity += action.payload.quantity || 1;
        } else {
          inventory.push({
            id: action.payload.itemId,
            ...action.payload.item,
            quantity: action.payload.quantity || 1
          });
        }
        
        this.setState('inventory', [...inventory]);
        return inventory;

      case 'BATTLE_START':
        this.setState('battle', {
          player: this.getState('player'),
          enemy: action.payload.enemy,
          turn: 'player',
          actions: action.payload.actions,
          battleLog: []
        });
        return this.getState('battle');

      default:
        console.warn('Unknown action type:', action.type);
        return null;
    }
  }

  async fetchPlayer(playerId) {
    // API呼び出しのシミュレーション
    const response = await fetch(`/api/players/${playerId}`);
    if (!response.ok) {
      throw new Error('Failed to load player data');
    }
    return response.json();
  }
}

// グローバルインスタンス
window.gameState = new GameStateManager();
```

### 2. Event System（イベントシステム）
```javascript
class GameEventSystem {
  constructor() {
    this.eventBus = document.createElement('div');
    this.middlewares = [];
  }

  // イベント発火
  emit(eventType, data = {}) {
    const event = new CustomEvent(eventType, {
      detail: { ...data, timestamp: Date.now() }
    });
    
    // ミドルウェア処理
    let processedData = data;
    for (const middleware of this.middlewares) {
      processedData = middleware(eventType, processedData) || processedData;
    }
    
    this.eventBus.dispatchEvent(event);
    
    // デバッグログ
    if (window.DEBUG_EVENTS) {
      console.log(`[Event] ${eventType}:`, processedData);
    }
  }

  // イベントリスナー登録
  on(eventType, callback) {
    this.eventBus.addEventListener(eventType, callback);
    
    return () => {
      this.eventBus.removeEventListener(eventType, callback);
    };
  }

  // 一度だけ実行されるリスナー
  once(eventType, callback) {
    const wrappedCallback = (event) => {
      callback(event);
      this.eventBus.removeEventListener(eventType, wrappedCallback);
    };
    
    this.eventBus.addEventListener(eventType, wrappedCallback);
    
    return () => {
      this.eventBus.removeEventListener(eventType, wrappedCallback);
    };
  }

  // ミドルウェア追加
  use(middleware) {
    this.middlewares.push(middleware);
  }

  // 非同期イベント処理
  async emitAsync(eventType, data = {}) {
    return new Promise((resolve) => {
      const handleResponse = (event) => {
        resolve(event.detail);
        this.eventBus.removeEventListener(`${eventType}:response`, handleResponse);
      };
      
      this.eventBus.addEventListener(`${eventType}:response`, handleResponse);
      this.emit(eventType, data);
    });
  }
}

// グローバルインスタンス
window.gameEvents = new GameEventSystem();

// デバッグ用ミドルウェア
window.gameEvents.use((eventType, data) => {
  if (eventType.startsWith('error:')) {
    console.error('[Game Error]', eventType, data);
  }
  return data;
});
```

## アクセシビリティ実装

### 1. Accessibility Helpers（アクセシビリティヘルパー）
```javascript
class AccessibilityManager {
  constructor() {
    this.announcer = this.createAnnouncer();
    this.focusHistory = [];
    this.shortcuts = new Map();
  }

  // スクリーンリーダー用アナウンサー作成
  createAnnouncer() {
    const announcer = document.createElement('div');
    announcer.setAttribute('aria-live', 'polite');
    announcer.setAttribute('aria-atomic', 'true');
    announcer.className = 'sr-only';
    announcer.style.cssText = `
      position: absolute !important;
      width: 1px !important;
      height: 1px !important;
      padding: 0 !important;
      margin: -1px !important;
      overflow: hidden !important;
      clip: rect(0, 0, 0, 0) !important;
      white-space: nowrap !important;
      border: 0 !important;
    `;
    document.body.appendChild(announcer);
    return announcer;
  }

  // メッセージの読み上げ
  announce(message, priority = 'polite') {
    this.announcer.setAttribute('aria-live', priority);
    this.announcer.textContent = message;
    
    setTimeout(() => {
      this.announcer.textContent = '';
    }, 1000);
  }

  // フォーカス管理
  saveFocus() {
    this.focusHistory.push(document.activeElement);
  }

  restoreFocus() {
    const previousFocus = this.focusHistory.pop();
    if (previousFocus && previousFocus.focus) {
      previousFocus.focus();
    }
  }

  // フォーカストラップ
  trapFocus(container) {
    const focusableElements = container.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    const handleTabKey = (event) => {
      if (event.key === 'Tab') {
        if (event.shiftKey) {
          if (document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
          }
        } else {
          if (document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
          }
        }
      }
    };

    container.addEventListener('keydown', handleTabKey);
    
    return () => {
      container.removeEventListener('keydown', handleTabKey);
    };
  }

  // キーボードショートカット
  registerShortcut(key, callback, description) {
    this.shortcuts.set(key, { callback, description });
    
    const handler = (event) => {
      if (event.key === key || event.code === key) {
        event.preventDefault();
        callback(event);
      }
    };
    
    document.addEventListener('keydown', handler);
    
    return () => {
      document.removeEventListener('keydown', handler);
      this.shortcuts.delete(key);
    };
  }

  // ARIAラベル管理
  setAriaLabel(element, label) {
    element.setAttribute('aria-label', label);
  }

  setAriaDescribedBy(element, descriptionId) {
    element.setAttribute('aria-describedby', descriptionId);
  }

  // 高コントラストモード検出
  isHighContrastMode() {
    return window.matchMedia('(prefers-contrast: high)').matches;
  }

  // 縮小モーション設定検出
  prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  // アクセシビリティチェック
  auditElement(element) {
    const issues = [];
    
    // 画像のalt属性チェック
    const images = element.querySelectorAll('img');
    images.forEach(img => {
      if (!img.getAttribute('alt')) {
        issues.push({
          element: img,
          issue: 'Missing alt attribute',
          severity: 'error'
        });
      }
    });
    
    // ボタンのラベルチェック
    const buttons = element.querySelectorAll('button');
    buttons.forEach(button => {
      if (!button.textContent.trim() && !button.getAttribute('aria-label')) {
        issues.push({
          element: button,
          issue: 'Button without accessible name',
          severity: 'error'
        });
      }
    });
    
    // カラーコントラストチェック（簡易版）
    const textElements = element.querySelectorAll('*');
    textElements.forEach(el => {
      const style = window.getComputedStyle(el);
      const color = style.color;
      const backgroundColor = style.backgroundColor;
      
      if (color && backgroundColor) {
        const contrast = this.calculateContrast(color, backgroundColor);
        if (contrast < 4.5) {
          issues.push({
            element: el,
            issue: `Low color contrast: ${contrast.toFixed(2)}`,
            severity: 'warning'
          });
        }
      }
    });
    
    return issues;
  }

  // コントラスト比計算（簡易版）
  calculateContrast(color1, color2) {
    // 実際の実装では、より正確な計算が必要
    // ここは簡易版として固定値を返す
    return 4.5;
  }
}

// グローバルインスタンス
window.a11y = new AccessibilityManager();

// 基本ショートカット登録
window.a11y.registerShortcut('?', () => {
  window.gameEvents.emit('show-help');
}, 'Show help');

window.a11y.registerShortcut('Escape', () => {
  window.gameEvents.emit('close-modal');
}, 'Close modal');
```

## テスト戦略

### 1. Component Testing（コンポーネントテスト）
```javascript
// テストユーティリティ
class ComponentTestUtils {
  static createTestElement(tagName, attributes = {}) {
    const element = document.createElement(tagName);
    Object.entries(attributes).forEach(([key, value]) => {
      element.setAttribute(key, value);
    });
    document.body.appendChild(element);
    return element;
  }

  static async waitForRender(element) {
    await new Promise(resolve => {
      if (element.shadowRoot) {
        resolve();
      } else {
        element.addEventListener('connected', resolve, { once: true });
      }
    });
  }

  static queryInShadow(element, selector) {
    return element.shadowRoot ? element.shadowRoot.querySelector(selector) : null;
  }

  static queryAllInShadow(element, selector) {
    return element.shadowRoot ? element.shadowRoot.querySelectorAll(selector) : [];
  }

  static simulateEvent(element, eventType, detail = {}) {
    const event = new CustomEvent(eventType, { detail });
    element.dispatchEvent(event);
  }

  static cleanup() {
    document.querySelectorAll('[data-test]').forEach(el => el.remove());
  }
}

// サンプルテスト
describe('GameButton Component', () => {
  let button;

  beforeEach(() => {
    button = ComponentTestUtils.createTestElement('game-button', {
      'data-test': 'game-button',
      variant: 'primary',
      size: 'md'
    });
  });

  afterEach(() => {
    ComponentTestUtils.cleanup();
  });

  it('should render with correct variant class', async () => {
    await ComponentTestUtils.waitForRender(button);
    
    const buttonElement = ComponentTestUtils.queryInShadow(button, '.btn');
    expect(buttonElement).toBeTruthy();
    expect(buttonElement.classList.contains('btn--primary')).toBe(true);
  });

  it('should handle click events', async () => {
    await ComponentTestUtils.waitForRender(button);
    
    let clickFired = false;
    button.addEventListener('click', () => {
      clickFired = true;
    });

    const buttonElement = ComponentTestUtils.queryInShadow(button, '.btn');
    buttonElement.click();

    expect(clickFired).toBe(true);
  });

  it('should show loading state', async () => {
    button.setAttribute('loading', '');
    await ComponentTestUtils.waitForRender(button);

    const spinner = ComponentTestUtils.queryInShadow(button, '.loading-spinner');
    expect(spinner).toBeTruthy();
  });
});

describe('StatusDisplay Component', () => {
  let statusDisplay;

  beforeEach(() => {
    statusDisplay = ComponentTestUtils.createTestElement('status-display', {
      'data-test': 'status-display',
      hp: '75',
      'max-hp': '100',
      sp: '30',
      'max-sp': '50',
      level: '5'
    });
  });

  afterEach(() => {
    ComponentTestUtils.cleanup();
  });

  it('should display correct level', async () => {
    await ComponentTestUtils.waitForRender(statusDisplay);
    
    const levelDisplay = ComponentTestUtils.queryInShadow(statusDisplay, '.level-display');
    expect(levelDisplay.textContent).toContain('Level 5');
  });

  it('should render progress bars with correct values', async () => {
    await ComponentTestUtils.waitForRender(statusDisplay);
    
    const hpBar = ComponentTestUtils.queryInShadow(statusDisplay, 'game-progress-bar[variant="hp"]');
    expect(hpBar.getAttribute('value')).toBe('75');
    expect(hpBar.getAttribute('max')).toBe('100');
  });
});
```

### 2. Integration Testing（統合テスト）
```javascript
describe('Game State Integration', () => {
  let gameState;

  beforeEach(() => {
    gameState = new GameStateManager();
  });

  it('should update UI when player HP changes', async () => {
    // プレイヤーデータをセット
    gameState.setState('player', {
      name: 'Test Player',
      hp: 100,
      maxHp: 100,
      sp: 50,
      maxSp: 50,
      level: 1
    });

    // ステータス表示コンポーネントを作成
    const statusDisplay = ComponentTestUtils.createTestElement('status-display');
    
    // 状態変更をリッスン
    const unsubscribe = gameState.subscribe('player.hp', (hp) => {
      statusDisplay.setAttribute('hp', hp);
    });

    // HPを変更
    gameState.setState('player.hp', 75);

    await ComponentTestUtils.waitForRender(statusDisplay);
    
    expect(statusDisplay.getAttribute('hp')).toBe('75');
    
    unsubscribe();
  });

  it('should handle battle actions correctly', async () => {
    // バトル状態をセット
    gameState.setState('battle', {
      player: { hp: 100, maxHp: 100, sp: 50, maxSp: 50 },
      enemy: { hp: 80, maxHp: 100 },
      turn: 'player',
      actions: [
        { id: 'attack', name: '攻撃', available: true, spCost: 0 }
      ],
      battleLog: []
    });

    // バトルインターフェースを作成
    const battleInterface = ComponentTestUtils.createTestElement('battle-interface');
    battleInterface.setBattleState(gameState.getState('battle'));

    await ComponentTestUtils.waitForRender(battleInterface);

    // 攻撃ボタンをクリック
    const attackButton = ComponentTestUtils.queryInShadow(
      battleInterface, 
      'game-button[data-action="attack"]'
    );
    
    let actionFired = false;
    battleInterface.addEventListener('battle-action', (event) => {
      actionFired = true;
      expect(event.detail.actionId).toBe('attack');
    });

    attackButton.click();
    expect(actionFired).toBe(true);
  });
});
```

## 実装ガイドライン

### 1. 開発環境セットアップ
```bash
# 必要なツールのインストール
npm install --save-dev @web/test-runner
npm install --save-dev @open-wc/testing
npm install --save-dev @web/dev-server

# ビルドツール
npm install --save-dev rollup
npm install --save-dev @rollup/plugin-terser

# リンター
npm install --save-dev eslint
npm install --save-dev @typescript-eslint/parser
```

### 2. コンポーネント開発フロー
```javascript
// 1. コンポーネント定義
class NewGameComponent extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  // 2. 属性監視
  static get observedAttributes() {
    return ['prop1', 'prop2'];
  }

  // 3. ライフサイクル
  connectedCallback() {
    this.render();
    this.setupEventListeners();
  }

  disconnectedCallback() {
    this.cleanup();
  }

  // 4. 描画
  render() {
    this.shadowRoot.innerHTML = `
      <style>/* スタイル */</style>
      <div>/* HTML */</div>
    `;
  }

  // 5. イベント処理
  setupEventListeners() {
    // イベントリスナーの設定
  }

  // 6. クリーンアップ
  cleanup() {
    // リソースの解放
  }
}

// 7. 登録
customElements.define('new-game-component', NewGameComponent);
```

### 3. パフォーマンス最適化
```javascript
// 仮想化による大量データ表示
class VirtualizedList extends HTMLElement {
  constructor() {
    super();
    this.items = [];
    this.itemHeight = 50;
    this.visibleRange = { start: 0, end: 10 };
    this.scrollTop = 0;
  }

  updateVisibleItems() {
    const containerHeight = this.clientHeight;
    const totalHeight = this.items.length * this.itemHeight;
    
    const start = Math.floor(this.scrollTop / this.itemHeight);
    const visibleCount = Math.ceil(containerHeight / this.itemHeight) + 2;
    const end = Math.min(start + visibleCount, this.items.length);
    
    if (start !== this.visibleRange.start || end !== this.visibleRange.end) {
      this.visibleRange = { start, end };
      this.render();
    }
  }

  render() {
    const visibleItems = this.items.slice(this.visibleRange.start, this.visibleRange.end);
    const offsetY = this.visibleRange.start * this.itemHeight;
    
    this.shadowRoot.innerHTML = `
      <div class="virtual-container" style="height: ${this.items.length * this.itemHeight}px;">
        <div class="visible-items" style="transform: translateY(${offsetY}px);">
          ${visibleItems.map(item => this.renderItem(item)).join('')}
        </div>
      </div>
    `;
  }
}
```

### 4. エラーハンドリング
```javascript
// グローバルエラーハンドラー
class ComponentErrorHandler {
  static handleError(error, component) {
    console.error(`[${component.tagName}] Error:`, error);
    
    // エラー通知
    window.gameEvents.emit('component-error', {
      component: component.tagName,
      error: error.message,
      stack: error.stack
    });
    
    // フォールバック表示
    component.showErrorState(error.message);
  }

  static wrapMethod(component, methodName) {
    const originalMethod = component[methodName];
    
    component[methodName] = function(...args) {
      try {
        return originalMethod.apply(this, args);
      } catch (error) {
        ComponentErrorHandler.handleError(error, this);
      }
    };
  }
}

// 使用例
class SafeGameComponent extends HTMLElement {
  constructor() {
    super();
    
    // メソッドをエラーハンドリングでラップ
    ComponentErrorHandler.wrapMethod(this, 'render');
    ComponentErrorHandler.wrapMethod(this, 'handleEvent');
  }

  showErrorState(message) {
    this.shadowRoot.innerHTML = `
      <div class="error-state">
        <p>エラーが発生しました: ${message}</p>
        <button onclick="location.reload()">再読み込み</button>
      </div>
    `;
  }
}
```

---

このコンポーネント設計書は、test_smgプロジェクトの一貫性のあるUI/UX実現のための包括的なガイドラインです。各コンポーネントは再利用可能性、アクセシビリティ、メンテナンス性を重視して設計されており、ゲーム特有の要件にも対応しています。実装時にはこの設計書を参考に、統一感のあるユーザーインターフェースを構築してください。