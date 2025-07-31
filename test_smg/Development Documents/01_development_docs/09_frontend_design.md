# フロントエンド設計書

## 文書の概要

- **作成日**: 2025年7月25日
- **対象システム**: test_smg（Laravel/PHPブラウザRPG）
- **作成者**: AI開発チーム
- **バージョン**: v1.0

## 目的

test_smgプロジェクトのフロントエンド設計を定義し、ユーザー体験とパフォーマンスを最適化した実装を実現する。

## 目次

1. [設計方針](#設計方針)
2. [技術スタック](#技術スタック)
3. [アーキテクチャ設計](#アーキテクチャ設計)
4. [UI/UXデザイン](#ui-uxデザイン)
5. [JavaScript設計](#javascript設計)
6. [CSS設計](#css設計)
7. [コンポーネント設計](#コンポーネント設計)
8. [状態管理](#状態管理)
9. [パフォーマンス最適化](#パフォーマンス最適化)
10. [アクセシビリティ](#アクセシビリティ)
11. [テスト戦略](#テスト戦略)

## 設計方針

### 1. 基本理念
- **シンプルさ**: CGIゲーム風のシンプルで親しみやすいUI
- **レスポンシブ**: モバイルファーストなレスポンシブデザイン
- **高速性**: 軽量でスムーズな操作感
- **アクセシビリティ**: 誰でも使いやすいインターフェース

### 2. ユーザー体験設計
```
┌──────────────────────────┐
│     ユーザー中心設計     │
├──────────────────────────┤
│ ・直感的な操作           │
│ ・明確なフィードバック   │
│ ・一貫したインタラクション│
│ ・エラー時の適切な誘導   │
└──────────────────────────┘
```

### 3. パフォーマンス目標
- **初回ロード**: 3秒以内
- **画面遷移**: 100ms以内
- **API応答**: 500ms以内
- **Lighthouse Score**: 90点以上

## 技術スタック

### 1. フロントエンド技術
```javascript
// 基本技術スタック
const techStack = {
    markup: 'HTML5',
    styling: 'TailwindCSS + Custom CSS',
    scripting: 'Vanilla JavaScript ES6+',
    bundler: 'Laravel Vite',
    icons: 'Heroicons',
    fonts: 'システムフォント',
    responsive: 'Mobile First Design'
};
```

### 2. ライブラリ選定基準
- **軽量性**: バンドルサイズの最小化
- **保守性**: 長期サポートとコミュニティ
- **学習コスト**: チーム習得の容易さ
- **パフォーマンス**: 実行速度の最適化

### 3. 採用技術詳細
```html
<!-- Laravel Blade + TailwindCSS -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>test_smg</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans">
    <!-- アプリケーションコンテンツ -->
</body>
</html>
```

## アーキテクチャ設計

### 1. レイヤー構造
```
┌─────────────────────────┐
│    Presentation Layer   │ ← Blade Templates + CSS
├─────────────────────────┤
│    Application Layer    │ ← JavaScript Modules
├─────────────────────────┤
│    Domain Layer         │ ← Game Logic
├─────────────────────────┤
│    Infrastructure       │ ← API Client + Utils
└─────────────────────────┘
```

### 2. ディレクトリ構造
```
resources/
├── views/
│   ├── layouts/
│   │   ├── app.blade.php
│   │   └── game.blade.php
│   ├── components/
│   │   ├── ui/
│   │   │   ├── button.blade.php
│   │   │   ├── card.blade.php
│   │   │   └── modal.blade.php
│   │   └── game/
│   │       ├── character-status.blade.php
│   │       ├── dice-roller.blade.php
│   │       └── movement-controls.blade.php
│   ├── auth/
│   │   ├── login.blade.php
│   │   └── register.blade.php
│   └── game/
│       ├── dashboard.blade.php
│       └── play.blade.php
├── css/
│   ├── app.css
│   ├── components/
│   │   ├── buttons.css
│   │   ├── cards.css
│   │   └── forms.css
│   └── layouts/
│       ├── header.css
│       └── footer.css
└── js/
    ├── app.js
    ├── bootstrap.js
    ├── game/
    │   ├── GameManager.js
    │   ├── DiceManager.js
    │   ├── MovementManager.js
    │   ├── BattleManager.js
    │   └── InventoryManager.js
    ├── components/
    │   ├── Modal.js
    │   ├── Toast.js
    │   └── LoadingSpinner.js
    └── utils/
        ├── ApiClient.js
        ├── EventEmitter.js
        └── LocalStorage.js
```

### 3. モジュール設計
```javascript
// resources/js/game/GameManager.js
export class GameManager {
    constructor(gameData) {
        this.gameData = gameData;
        this.currentSteps = 0;
        this.isMoving = false;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateDisplay();
    }
    
    bindEvents() {
        // イベントバインディング
    }
    
    updateDisplay() {
        // 画面更新
    }
}
```

## UI/UXデザイン

### 1. デザインシステム
```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* カスタムCSSプロパティ */
:root {
    /* カラーシステム */
    --color-primary: #3b82f6;
    --color-primary-hover: #2563eb;
    --color-secondary: #10b981;
    --color-danger: #ef4444;
    --color-warning: #f59e0b;
    --color-success: #10b981;
    
    /* スペーシング */
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
    
    /* フォント */
    --font-sans: system-ui, -apple-system, sans-serif;
    --font-mono: 'SF Mono', Monaco, 'Cascadia Code', monospace;
    
    /* 影 */
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}
```

### 2. コンポーネントスタイル
```css
/* ボタンコンポーネント */
.btn {
    @apply inline-flex items-center justify-center px-4 py-2 
           border border-transparent text-sm font-medium rounded-md 
           focus:outline-none focus:ring-2 focus:ring-offset-2 
           disabled:opacity-50 disabled:cursor-not-allowed
           transition-colors duration-200;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700 
           focus:ring-blue-500;
}

.btn-secondary {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300 
           focus:ring-gray-500;
}

.btn-danger {
    @apply bg-red-600 text-white hover:bg-red-700 
           focus:ring-red-500;
}

/* カードコンポーネント */
.card {
    @apply bg-white rounded-lg shadow-md border border-gray-200 
           overflow-hidden;
}

.card-header {
    @apply px-6 py-4 border-b border-gray-200 bg-gray-50;
}

.card-body {
    @apply px-6 py-4;
}

.card-footer {
    @apply px-6 py-4 border-t border-gray-200 bg-gray-50;
}

/* ゲーム特有のスタイル */
.game-container {
    @apply max-w-4xl mx-auto p-4 space-y-6;
}

.character-status {
    @apply bg-white rounded-lg p-4 shadow-sm border;
}

.dice-container {
    @apply flex items-center justify-center space-x-2 p-4 
           bg-gray-100 rounded-lg;
}

.dice {
    @apply w-12 h-12 bg-white rounded border-2 border-gray-300 
           flex items-center justify-center text-lg font-bold 
           shadow-sm;
}

.movement-controls {
    @apply grid grid-cols-2 gap-4 p-4;
}

.location-card {
    @apply relative overflow-hidden rounded-lg bg-gradient-to-br 
           from-green-400 to-blue-500 p-6 text-white shadow-lg;
}
```

### 3. レスポンシブデザイン
```css
/* モバイルファースト */
.game-layout {
    @apply grid grid-cols-1;
}

/* タブレット */
@media (min-width: 768px) {
    .game-layout {
        @apply grid-cols-2 gap-6;
    }
}

/* デスクトップ */
@media (min-width: 1024px) {
    .game-layout {
        @apply grid-cols-3 gap-8;
    }
}

/* 大画面 */
@media (min-width: 1280px) {
    .game-layout {
        @apply max-w-7xl mx-auto;
    }
}
```

## JavaScript設計

### 1. モジュール設計パターン
```javascript
// resources/js/game/GameManager.js
import { EventEmitter } from '../utils/EventEmitter.js';
import { ApiClient } from '../utils/ApiClient.js';

export class GameManager extends EventEmitter {
    constructor(initialData = {}) {
        super();
        
        this.state = {
            character: null,
            currentLocation: null,
            gamePosition: 0,
            isLoading: false,
            ...initialData
        };
        
        this.apiClient = new ApiClient();
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadGameState();
    }
    
    bindEvents() {
        // DOM イベントのバインディング
        document.addEventListener('DOMContentLoaded', () => {
            this.bindDOMEvents();
        });
        
        // カスタムイベントのリスニング
        this.on('stateChange', this.handleStateChange.bind(this));
        this.on('error', this.handleError.bind(this));
    }
    
    bindDOMEvents() {
        const rollDiceBtn = document.getElementById('roll-dice');
        if (rollDiceBtn) {
            rollDiceBtn.addEventListener('click', this.rollDice.bind(this));
        }
        
        const moveLeftBtn = document.getElementById('move-left');
        const moveRightBtn = document.getElementById('move-right');
        
        if (moveLeftBtn) {
            moveLeftBtn.addEventListener('click', () => this.move('left'));
        }
        
        if (moveRightBtn) {
            moveRightBtn.addEventListener('click', () => this.move('right'));
        }
    }
    
    async loadGameState() {
        try {
            this.setState({ isLoading: true });
            
            const response = await this.apiClient.get('/game/state');
            
            this.setState({
                ...response.data,
                isLoading: false
            });
            
            this.emit('stateLoaded', response.data);
        } catch (error) {
            this.emit('error', error);
            this.setState({ isLoading: false });
        }
    }
    
    async rollDice() {
        if (this.state.isLoading) return;
        
        try {
            this.setState({ isLoading: true });
            
            const response = await this.apiClient.post('/game/roll-dice');
            
            this.updateDiceDisplay(response.data);
            this.showMovementControls(response.data.final_movement);
            
            this.emit('diceRolled', response.data);
        } catch (error) {
            this.emit('error', error);
        } finally {
            this.setState({ isLoading: false });
        }
    }
    
    async move(direction) {
        if (this.state.isLoading || this.currentSteps === 0) return;
        
        try {
            this.setState({ isLoading: true });
            
            const response = await this.apiClient.post('/game/move', {
                direction,
                steps: this.currentSteps
            });
            
            this.updateGameDisplay(response.data);
            this.hideMovementControls();
            
            this.emit('moved', response.data);
        } catch (error) {
            this.emit('error', error);
        } finally {
            this.setState({ isLoading: false });
        }
    }
    
    setState(newState) {
        const prevState = { ...this.state };
        this.state = { ...this.state, ...newState };
        this.emit('stateChange', this.state, prevState);
    }
    
    handleStateChange(newState, prevState) {
        // 状態変更時の処理
        this.updateUI(newState);
    }
    
    handleError(error) {
        console.error('GameManager Error:', error);
        this.showErrorMessage(error.message || 'エラーが発生しました。');
    }
    
    updateUI(state) {
        // UI更新ロジック
        if (state.character) {
            this.updateCharacterDisplay(state.character);
        }
        
        if (state.currentLocation) {
            this.updateLocationDisplay(state.currentLocation);
        }
    }
    
    // UI更新メソッド群
    updateDiceDisplay(diceResult) {
        const diceContainer = document.getElementById('all-dice');
        if (diceContainer) {
            diceContainer.innerHTML = diceResult.dice_rolls
                .map(roll => `<div class="dice">${roll}</div>`)
                .join('');
        }
        
        this.updateElement('base-total', diceResult.base_total);
        this.updateElement('bonus', diceResult.bonus);
        this.updateElement('final-movement', diceResult.final_movement);
        
        this.currentSteps = diceResult.final_movement;
        this.showElement('dice-result');
    }
    
    updateCharacterDisplay(character) {
        this.updateElement('character-name', character.name);
        this.updateElement('character-hp', `${character.hp}/${character.max_hp}`);
        this.updateElement('character-level', character.level);
        this.updateElement('game-position', character.game_position);
    }
    
    updateLocationDisplay(location) {
        this.updateElement('current-location', location.name);
        this.updateElement('location-description', location.description || '');
    }
    
    showMovementControls(steps) {
        this.currentSteps = steps;
        this.showElement('movement-controls');
        
        const buttons = document.querySelectorAll('#movement-controls button');
        buttons.forEach(btn => {
            btn.disabled = false;
        });
    }
    
    hideMovementControls() {
        this.hideElement('movement-controls');
        this.currentSteps = 0;
    }
    
    // ユーティリティメソッド
    updateElement(id, content) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = content;
        }
    }
    
    showElement(id) {
        const element = document.getElementById(id);
        if (element) {
            element.classList.remove('hidden');
        }
    }
    
    hideElement(id) {
        const element = document.getElementById(id);
        if (element) {
            element.classList.add('hidden');
        }
    }
    
    showErrorMessage(message) {
        // エラーメッセージ表示（Toast使用）
        if (window.Toast) {
            window.Toast.error(message);
        } else {
            alert(message);
        }
    }
}
```

### 2. APIクライアント
```javascript
// resources/js/utils/ApiClient.js
export class ApiClient {
    constructor(baseURL = '') {
        this.baseURL = baseURL;
        this.headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        // CSRFトークンの設定
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            this.headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
        }
    }
    
    async request(method, url, data = null) {
        const config = {
            method: method.toUpperCase(),
            headers: this.headers
        };
        
        if (data) {
            config.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(this.baseURL + url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }
    
    get(url) {
        return this.request('GET', url);
    }
    
    post(url, data) {
        return this.request('POST', url, data);
    }
    
    put(url, data) {
        return this.request('PUT', url, data);
    }
    
    delete(url) {
        return this.request('DELETE', url);
    }
}
```

### 3. イベントエミッター
```javascript
// resources/js/utils/EventEmitter.js
export class EventEmitter {
    constructor() {
        this.events = {};
    }
    
    on(event, listener) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        this.events[event].push(listener);
        return this;
    }
    
    off(event, listenerToRemove) {
        if (!this.events[event]) return this;
        
        this.events[event] = this.events[event].filter(
            listener => listener !== listenerToRemove
        );
        return this;
    }
    
    emit(event, ...args) {
        if (!this.events[event]) return this;
        
        this.events[event].forEach(listener => {
            try {
                listener.apply(this, args);
            } catch (error) {
                console.error('Event listener error:', error);
            }
        });
        return this;
    }
    
    once(event, listener) {
        const onceListener = (...args) => {
            listener.apply(this, args);
            this.off(event, onceListener);
        };
        
        return this.on(event, onceListener);
    }
}
```

## CSS設計

### 1. BEM + ユーティリティファースト
```css
/* コンポーネントスタイル（BEM） */
.game-dashboard {
    @apply grid gap-6 p-6;
}

.game-dashboard__header {
    @apply flex items-center justify-between;
}

.game-dashboard__title {
    @apply text-2xl font-bold text-gray-900;
}

.game-dashboard__actions {
    @apply flex space-x-2;
}

/* 状態クラス */
.is-loading {
    @apply opacity-50 pointer-events-none;
}

.is-disabled {
    @apply opacity-50 cursor-not-allowed;
}

.is-active {
    @apply bg-blue-600 text-white;
}

.is-hidden {
    @apply hidden;
}

/* アニメーション */
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

.slide-up {
    animation: slideUp 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

### 2. カスタムプロパティの活用
```css
/* テーマシステム */
[data-theme="light"] {
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #1f2937;
    --text-secondary: #6b7280;
    --border-color: #e5e7eb;
}

[data-theme="dark"] {
    --bg-primary: #1f2937;
    --bg-secondary: #111827;
    --text-primary: #f9fafb;
    --text-secondary: #d1d5db;
    --border-color: #374151;
}

/* 使用例 */
.card {
    background-color: var(--bg-primary);
    color: var(--text-primary);
    border-color: var(--border-color);
}
```

## コンポーネント設計

### 1. Bladeコンポーネント
```php
{{-- resources/views/components/game/character-status.blade.php --}}
<div class="character-status card">
    <div class="card-header">
        <h3 class="text-lg font-semibold">{{ $character->name }}</h3>
        <span class="text-sm text-gray-500">Lv.{{ $character->getLevel() }}</span>
    </div>
    
    <div class="card-body space-y-4">
        <!-- HP表示 -->
        <div class="stat-bar">
            <div class="flex justify-between text-sm">
                <span>HP</span>
                <span>{{ $character->hp }}/{{ $character->max_hp }}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill bg-red-500" 
                     style="width: {{ ($character->hp / $character->max_hp) * 100 }}%"></div>
            </div>
        </div>
        
        <!-- SP表示 -->
        <div class="stat-bar">
            <div class="flex justify-between text-sm">
                <span>SP</span>
                <span>{{ $character->sp }}/{{ $character->max_sp }}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill bg-blue-500" 
                     style="width: {{ ($character->sp / $character->max_sp) * 100 }}%"></div>
            </div>
        </div>
        
        <!-- スキル表示 -->
        <div class="skills-grid">
            @foreach($character->getSkillSet()->toArray() as $skill => $level)
                <div class="skill-item">
                    <span class="skill-name">{{ ucfirst($skill) }}</span>
                    <span class="skill-level">{{ $level }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
.progress-bar {
    @apply w-full bg-gray-200 rounded-full h-2;
}

.progress-fill {
    @apply h-full rounded-full transition-all duration-300;
}

.skills-grid {
    @apply grid grid-cols-2 gap-2;
}

.skill-item {
    @apply flex justify-between text-sm p-2 bg-gray-50 rounded;
}
</style>
```

### 2. JavaScriptコンポーネント
```javascript
// resources/js/components/Toast.js
export class Toast {
    static show(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} fixed top-4 right-4 z-50 
                          px-4 py-2 rounded-lg shadow-lg text-white 
                          transition-all duration-300 transform translate-x-full`;
        
        toast.textContent = message;
        
        // スタイルを型に応じて設定
        const styles = {
            info: 'bg-blue-600',
            success: 'bg-green-600',
            warning: 'bg-yellow-600',
            error: 'bg-red-600'
        };
        
        toast.classList.add(styles[type] || styles.info);
        
        document.body.appendChild(toast);
        
        // アニメーション
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full');
        });
        
        // 自動削除
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
    }
    
    static info(message, duration) {
        this.show(message, 'info', duration);
    }
    
    static success(message, duration) {
        this.show(message, 'success', duration);
    }
    
    static warning(message, duration) {
        this.show(message, 'warning', duration);
    }
    
    static error(message, duration) {
        this.show(message, 'error', duration);
    }
}

// グローバルに公開
window.Toast = Toast;
```

### 3. モーダルコンポーネント
```javascript
// resources/js/components/Modal.js
export class Modal {
    constructor(options = {}) {
        this.options = {
            closable: true,
            backdrop: true,
            keyboard: true,
            ...options
        };
        
        this.isOpen = false;
        this.element = null;
    }
    
    create(content) {
        this.element = document.createElement('div');
        this.element.className = 'modal fixed inset-0 z-50 hidden';
        this.element.innerHTML = `
            <div class="modal-backdrop fixed inset-0 bg-black bg-opacity-50"></div>
            <div class="modal-container flex items-center justify-center min-h-screen p-4">
                <div class="modal-content bg-white rounded-lg shadow-xl max-w-lg w-full 
                          transform transition-all duration-300 scale-95 opacity-0">
                    ${this.options.closable ? '<button class="modal-close absolute top-2 right-2 text-gray-400 hover:text-gray-600">×</button>' : ''}
                    <div class="modal-body p-6">
                        ${content}
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.element);
        this.bindEvents();
    }
    
    bindEvents() {
        if (this.options.closable) {
            const closeBtn = this.element.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }
        }
        
        if (this.options.backdrop) {
            const backdrop = this.element.querySelector('.modal-backdrop');
            backdrop.addEventListener('click', () => this.close());
        }
        
        if (this.options.keyboard) {
            document.addEventListener('keydown', this.handleKeydown.bind(this));
        }
    }
    
    handleKeydown(event) {
        if (event.key === 'Escape' && this.isOpen) {
            this.close();
        }
    }
    
    open(content) {
        if (!this.element) {
            this.create(content);
        }
        
        this.element.classList.remove('hidden');
        this.isOpen = true;
        
        // アニメーション
        requestAnimationFrame(() => {
            const modalContent = this.element.querySelector('.modal-content');
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        });
        
        // ボディのスクロールを無効化
        document.body.style.overflow = 'hidden';
    }
    
    close() {
        if (!this.isOpen) return;
        
        const modalContent = this.element.querySelector('.modal-content');
        modalContent.classList.add('scale-95', 'opacity-0');
        modalContent.classList.remove('scale-100', 'opacity-100');
        
        setTimeout(() => {
            this.element.classList.add('hidden');
            this.isOpen = false;
            
            // ボディのスクロールを復元
            document.body.style.overflow = '';
        }, 300);
    }
    
    destroy() {
        this.close();
        if (this.element && this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }
        this.element = null;
    }
}
```

## 状態管理

### 1. 軽量状態管理システム
```javascript
// resources/js/utils/StateManager.js
export class StateManager {
    constructor(initialState = {}) {
        this.state = { ...initialState };
        this.listeners = [];
        this.middleware = [];
    }
    
    getState() {
        return { ...this.state };
    }
    
    setState(newState) {
        const prevState = { ...this.state };
        
        // ミドルウェアの実行
        let processedState = { ...this.state, ...newState };
        for (const middleware of this.middleware) {
            processedState = middleware(processedState, prevState) || processedState;
        }
        
        this.state = processedState;
        
        // リスナーに通知
        this.listeners.forEach(listener => {
            try {
                listener(this.state, prevState);
            } catch (error) {
                console.error('State listener error:', error);
            }
        });
    }
    
    subscribe(listener) {
        this.listeners.push(listener);
        
        // アンサブスクライブ関数を返す
        return () => {
            const index = this.listeners.indexOf(listener);
            if (index > -1) {
                this.listeners.splice(index, 1);
            }
        };
    }
    
    use(middleware) {
        this.middleware.push(middleware);
    }
}

// 使用例
const gameState = new StateManager({
    character: null,
    currentLocation: null,
    isLoading: false
});

// ローカルストレージミドルウェア
gameState.use((newState, prevState) => {
    if (newState.character !== prevState.character) {
        localStorage.setItem('gameCharacter', JSON.stringify(newState.character));
    }
    return newState;
});

// 状態変更の監視
gameState.subscribe((state, prevState) => {
    if (state.character !== prevState.character) {
        updateCharacterDisplay(state.character);
    }
});
```

### 2. ローカルストレージ管理
```javascript
// resources/js/utils/LocalStorage.js
export class LocalStorage {
    static get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('LocalStorage get error:', error);
            return defaultValue;
        }
    }
    
    static set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('LocalStorage set error:', error);
            return false;
        }
    }
    
    static remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('LocalStorage remove error:', error);
            return false;
        }
    }
    
    static clear() {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('LocalStorage clear error:', error);
            return false;
        }
    }
    
    // ゲーム専用メソッド
    static saveGameData(data) {
        return this.set('gameData', data);
    }
    
    static loadGameData() {
        return this.get('gameData', {});
    }
    
    static saveSettings(settings) {
        return this.set('gameSettings', settings);
    }
    
    static loadSettings() {
        return this.get('gameSettings', {
            theme: 'light',
            soundEnabled: true,
            animationEnabled: true
        });
    }
}
```

## パフォーマンス最適化

### 1. 遅延読み込み
```javascript
// resources/js/utils/LazyLoader.js
export class LazyLoader {
    static async loadModule(modulePath) {
        try {
            const module = await import(modulePath);
            return module.default || module;
        } catch (error) {
            console.error('Module loading error:', error);
            throw error;
        }
    }
    
    static async loadCSS(href) {
        return new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.onload = resolve;
            link.onerror = reject;
            document.head.appendChild(link);
        });
    }
    
    static observeIntersection(elements, callback, options = {}) {
        const defaultOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1,
            ...options
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    callback(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, defaultOptions);
        
        elements.forEach(element => observer.observe(element));
        
        return observer;
    }
}

// 使用例
document.addEventListener('DOMContentLoaded', () => {
    const battleSection = document.getElementById('battle-section');
    
    if (battleSection) {
        LazyLoader.observeIntersection([battleSection], async (element) => {
            const BattleManager = await LazyLoader.loadModule('./game/BattleManager.js');
            new BattleManager(element);
        });
    }
});
```

### 2. イメージ最適化
```javascript
// resources/js/utils/ImageOptimizer.js
export class ImageOptimizer {
    static createResponsiveImage(src, alt, sizes = []) {
        const img = document.createElement('img');
        img.src = src;
        img.alt = alt;
        img.loading = 'lazy';
        
        if (sizes.length > 0) {
            const srcset = sizes.map(size => 
                `${src}?w=${size.width}&h=${size.height} ${size.width}w`
            ).join(', ');
            
            img.srcset = srcset;
            img.sizes = sizes.map(size => 
                `(max-width: ${size.maxWidth}px) ${size.width}px`
            ).join(', ');
        }
        
        return img;
    }
    
    static preloadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }
    
    static async preloadImages(sources) {
        try {
            const promises = sources.map(src => this.preloadImage(src));
            return await Promise.all(promises);
        } catch (error) {
            console.error('Image preloading error:', error);
            throw error;
        }
    }
}
```

### 3. パフォーマンス監視
```javascript
// resources/js/utils/PerformanceMonitor.js
export class PerformanceMonitor {
    static measureFunctionExecution(fn, name) {
        return function(...args) {
            const start = performance.now();
            const result = fn.apply(this, args);
            const end = performance.now();
            
            console.log(`${name} execution time: ${end - start}ms`);
            
            return result;
        };
    }
    
    static measureAsyncFunction(fn, name) {
        return async function(...args) {
            const start = performance.now();
            const result = await fn.apply(this, args);
            const end = performance.now();
            
            console.log(`${name} execution time: ${end - start}ms`);
            
            return result;
        };
    }
    
    static trackPageMetrics() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const navigation = performance.getEntriesByType('navigation')[0];
                const paintEntries = performance.getEntriesByType('paint');
                
                const metrics = {
                    domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
                    loadComplete: navigation.loadEventEnd - navigation.loadEventStart,
                    firstPaint: paintEntries.find(entry => entry.name === 'first-paint')?.startTime || 0,
                    firstContentfulPaint: paintEntries.find(entry => entry.name === 'first-contentful-paint')?.startTime || 0
                };
                
                console.log('Page Performance Metrics:', metrics);
                
                // 分析サービスに送信
                this.sendMetrics(metrics);
            }, 0);
        });
    }
    
    static sendMetrics(metrics) {
        // 分析サービスへの送信ロジック
        if (navigator.sendBeacon) {
            navigator.sendBeacon('/api/metrics', JSON.stringify(metrics));
        }
    }
}
```

## アクセシビリティ

### 1. キーボード操作サポート
```javascript
// resources/js/utils/KeyboardNavigation.js
export class KeyboardNavigation {
    constructor(container) {
        this.container = container;
        this.focusableElements = [];
        this.currentIndex = -1;
        
        this.init();
    }
    
    init() {
        this.updateFocusableElements();
        this.bindEvents();
    }
    
    updateFocusableElements() {
        const selector = [
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            'a[href]',
            '[tabindex]:not([tabindex="-1"])'
        ].join(', ');
        
        this.focusableElements = Array.from(
            this.container.querySelectorAll(selector)
        );
    }
    
    bindEvents() {
        this.container.addEventListener('keydown', this.handleKeydown.bind(this));
    }
    
    handleKeydown(event) {
        switch (event.key) {
            case 'Tab':
                // デフォルトのTab動作を使用
                break;
                
            case 'ArrowDown':
            case 'ArrowRight':
                event.preventDefault();
                this.focusNext();
                break;
                
            case 'ArrowUp':
            case 'ArrowLeft':
                event.preventDefault();
                this.focusPrevious();
                break;
                
            case 'Home':
                event.preventDefault();
                this.focusFirst();
                break;
                
            case 'End':
                event.preventDefault();
                this.focusLast();
                break;
                
            case 'Enter':
            case ' ':
                if (event.target.tagName === 'BUTTON') {
                    event.target.click();
                }
                break;
        }
    }
    
    focusNext() {
        this.currentIndex = (this.currentIndex + 1) % this.focusableElements.length;
        this.focusCurrent();
    }
    
    focusPrevious() {
        this.currentIndex = this.currentIndex <= 0 
            ? this.focusableElements.length - 1 
            : this.currentIndex - 1;
        this.focusCurrent();
    }
    
    focusFirst() {
        this.currentIndex = 0;
        this.focusCurrent();
    }
    
    focusLast() {
        this.currentIndex = this.focusableElements.length - 1;
        this.focusCurrent();
    }
    
    focusCurrent() {
        if (this.focusableElements[this.currentIndex]) {
            this.focusableElements[this.currentIndex].focus();
        }
    }
}
```

### 2. スクリーンリーダー対応
```javascript
// resources/js/utils/ScreenReader.js
export class ScreenReader {
    static announce(message, priority = 'polite') {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', priority);
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        
        document.body.appendChild(announcement);
        
        // 読み上げ後に要素を削除
        setTimeout(() => {
            if (announcement.parentNode) {
                announcement.parentNode.removeChild(announcement);
            }
        }, 1000);
    }
    
    static announceImportant(message) {
        this.announce(message, 'assertive');
    }
    
    static setLabel(element, label) {
        element.setAttribute('aria-label', label);
    }
    
    static setDescription(element, description) {
        const descId = `desc-${Math.random().toString(36).substr(2, 9)}`;
        const descElement = document.createElement('div');
        descElement.id = descId;
        descElement.className = 'sr-only';
        descElement.textContent = description;
        
        document.body.appendChild(descElement);
        element.setAttribute('aria-describedby', descId);
    }
    
    static setExpanded(element, expanded) {
        element.setAttribute('aria-expanded', expanded.toString());
    }
    
    static setPressed(element, pressed) {
        element.setAttribute('aria-pressed', pressed.toString());
    }
}
```

## テスト戦略

### 1. JavaScriptユニットテスト
```javascript
// tests/js/gameManager.test.js
import { GameManager } from '../../resources/js/game/GameManager.js';

describe('GameManager', () => {
    let gameManager;
    let mockApiClient;
    
    beforeEach(() => {
        mockApiClient = {
            get: jest.fn(),
            post: jest.fn()
        };
        
        gameManager = new GameManager();
        gameManager.apiClient = mockApiClient;
    });
    
    describe('rollDice', () => {
        it('should call API and update dice display', async () => {
            const mockResponse = {
                data: {
                    dice_rolls: [6, 4, 2],
                    base_total: 12,
                    bonus: 3,
                    final_movement: 15
                }
            };
            
            mockApiClient.post.mockResolvedValue(mockResponse);
            
            await gameManager.rollDice();
            
            expect(mockApiClient.post).toHaveBeenCalledWith('/game/roll-dice');
            expect(gameManager.currentSteps).toBe(15);
        });
        
        it('should handle API errors gracefully', async () => {
            const mockError = new Error('Network error');
            mockApiClient.post.mockRejectedValue(mockError);
            
            const consoleSpy = jest.spyOn(console, 'error').mockImplementation();
            
            await gameManager.rollDice();
            
            expect(consoleSpy).toHaveBeenCalledWith('GameManager Error:', mockError);
            
            consoleSpy.mockRestore();
        });
    });
    
    describe('setState', () => {
        it('should update state and emit stateChange event', () => {
            const listener = jest.fn();
            gameManager.on('stateChange', listener);
            
            const newState = { character: { name: 'Test' } };
            gameManager.setState(newState);
            
            expect(gameManager.state.character).toEqual({ name: 'Test' });
            expect(listener).toHaveBeenCalledWith(
                expect.objectContaining(newState),
                expect.any(Object)
            );
        });
    });
});
```

### 2. E2Eテスト
```javascript
// tests/e2e/game.spec.js
const { test, expect } = require('@playwright/test');

test.describe('Game Functionality', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.fill('#email', 'test@example.com');
        await page.fill('#password', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('/game');
    });
    
    test('should allow dice rolling and movement', async ({ page }) => {
        // サイコロを振る
        await page.click('#roll-dice');
        
        // 結果が表示されるまで待機
        await page.waitForSelector('#dice-result:not(.hidden)');
        
        // 移動距離が表示されることを確認
        const finalMovement = await page.textContent('#final-movement');
        expect(parseInt(finalMovement)).toBeGreaterThan(0);
        
        // 移動ボタンが表示されることを確認
        await page.waitForSelector('#movement-controls:not(.hidden)');
        
        // 右に移動
        await page.click('#move-right');
        
        // 移動後の位置が更新されることを確認
        await page.waitForFunction(() => {
            const controls = document.getElementById('movement-controls');
            return controls && controls.classList.contains('hidden');
        });
    });
    
    test('should be responsive on mobile devices', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });
        
        // モバイルレイアウトが正しく表示されることを確認
        const gameContainer = page.locator('.game-container');
        await expect(gameContainer).toBeVisible();
        
        // ボタンがタップしやすいサイズであることを確認
        const rollButton = page.locator('#roll-dice');
        const buttonBox = await rollButton.boundingBox();
        expect(buttonBox.height).toBeGreaterThanOrEqual(44); // 44px以上
    });
});
```

## まとめ

### 開発ガイドライン
1. **コード品質**: ESLint + Prettier でコード統一
2. **パフォーマンス**: Core Web Vitals 基準の達成
3. **アクセシビリティ**: WCAG 2.1 AA レベル準拠
4. **テスト**: 80%以上のコードカバレッジ

### 実装優先順位
1. 基本UIコンポーネント実装
2. ゲームロジック統合
3. レスポンシブ対応
4. パフォーマンス最適化
5. アクセシビリティ向上

このフロントエンド設計により、test_smgの優れたユーザー体験を実現できます。