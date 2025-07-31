# アニメーションシステム書

## プロジェクト情報
- **プロジェクト名**: test_smg（Simple Management Game）
- **作成日**: 2025年7月26日
- **バージョン**: 1.0
- **作成者**: Claude（AI開発アシスタント）
- **想定開発時間**: 3時間

## 目次
1. [アニメーションシステム概要](#アニメーションシステム概要)
2. [アニメーション設計原則](#アニメーション設計原則)
3. [基本アニメーションライブラリ](#基本アニメーションライブラリ)
4. [ゲーム専用アニメーション](#ゲーム専用アニメーション)
5. [パフォーマンス最適化](#パフォーマンス最適化)
6. [アクセシビリティ配慮](#アクセシビリティ配慮)
7. [状態変化アニメーション](#状態変化アニメーション)
8. [マイクロインタラクション](#マイクロインタラクション)
9. [レスポンシブアニメーション](#レスポンシブアニメーション)
10. [実装ガイドライン](#実装ガイドライン)

## アニメーションシステム概要

### 設計哲学
test_smgのアニメーションシステムは、CGI時代の素朴な魅力と現代的なユーザビリティを融合させることを目標とします。過度に派手な演出は避け、ユーザーの理解を助ける機能的なアニメーションを重視します。

### システム構成
```typescript
interface AnimationSystem {
  core: {
    engine: AnimationEngine;
    scheduler: AnimationScheduler;
    performance: PerformanceMonitor;
  };
  categories: {
    transitions: TransitionAnimations;
    feedback: FeedbackAnimations;
    gameEffects: GameEffectAnimations;
    ui: UIAnimations;
  };
  config: {
    durations: TimingConfig;
    easings: EasingConfig;
    reduced: ReducedMotionConfig;
  };
}
```

### 技術スタック
- **CSS Animations**: 基本的なトランジション・キーフレーム
- **Web Animations API**: 複雑なアニメーション制御
- **CSS Custom Properties**: 動的な値制御
- **Intersection Observer**: スクロール連動アニメーション
- **RequestAnimationFrame**: 高精度タイミング制御

## アニメーション設計原則

### 1. 機能性重視（Function over Form）
```css
/* Good: 機能的なアニメーション */
.notification-enter {
  animation: slideInFromTop 0.3s ease-out;
  /* ユーザーに新しい情報の出現を明確に伝える */
}

/* Avoid: 装飾的すぎるアニメーション */
.notification-enter-fancy {
  animation: spinFlipBounce 2s ease-in-out;
  /* 機能的価値がない */
}
```

### 2. 一貫性（Consistency）
```css
:root {
  /* 統一されたタイミング値 */
  --duration-instant: 0.1s;
  --duration-fast: 0.2s;
  --duration-normal: 0.3s;
  --duration-slow: 0.5s;
  --duration-deliberate: 0.8s;

  /* 統一されたイージング */
  --ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
  --ease-in-out: cubic-bezier(0.645, 0.045, 0.355, 1);
  --ease-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
  --ease-spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
```

### 3. 予測可能性（Predictability）
```javascript
// アニメーションの予測可能なパターン
const AnimationPatterns = {
  // 入場: 上から下、左から右、小さくから大きく
  enter: {
    fromTop: 'translateY(-20px) opacity(0) → translateY(0) opacity(1)',
    fromLeft: 'translateX(-20px) opacity(0) → translateX(0) opacity(1)',
    scale: 'scale(0.9) opacity(0) → scale(1) opacity(1)'
  },
  
  // 退場: 逆方向、フェードアウト
  exit: {
    toTop: 'translateY(0) opacity(1) → translateY(-20px) opacity(0)',
    toRight: 'translateX(0) opacity(1) → translateX(20px) opacity(0)',
    scale: 'scale(1) opacity(1) → scale(0.9) opacity(0)'
  }
};
```

### 4. パフォーマンス考慮
```css
/* GPU加速可能なプロパティを優先 */
.optimized-animation {
  /* Good: transform, opacity */
  transform: translateX(0);
  opacity: 1;
  transition: transform 0.3s ease-out, opacity 0.3s ease-out;
  
  /* Avoid: layout-triggering properties */
  /* width, height, margin, padding */
}

/* will-change で最適化ヒント */
.preparing-animation {
  will-change: transform, opacity;
}

.animation-complete {
  will-change: auto; /* メモリ解放 */
}
```

## 基本アニメーションライブラリ

### 1. Core Animation Engine
```javascript
class GameAnimationEngine {
  constructor() {
    this.activeAnimations = new Map();
    this.performance = new PerformanceMonitor();
    this.reducedMotion = this.checkReducedMotion();
  }

  // 基本アニメーション実行
  animate(element, keyframes, options = {}) {
    const config = {
      duration: this.reducedMotion ? 0 : (options.duration || 300),
      easing: options.easing || 'ease-out',
      fill: 'forwards',
      ...options
    };

    // Web Animations API使用
    const animation = element.animate(keyframes, config);
    
    // パフォーマンス監視
    this.performance.trackAnimation(animation);
    
    // アニメーション管理
    const id = this.generateId();
    this.activeAnimations.set(id, animation);
    
    animation.addEventListener('finish', () => {
      this.activeAnimations.delete(id);
      element.style.willChange = 'auto';
    });

    return animation;
  }

  // CSS クラスベースアニメーション
  addClass(element, className, options = {}) {
    return new Promise((resolve) => {
      element.style.willChange = 'transform, opacity';
      element.classList.add(className);
      
      const duration = this.reducedMotion ? 0 : (options.duration || 300);
      
      setTimeout(() => {
        element.style.willChange = 'auto';
        resolve();
      }, duration);
    });
  }

  // チェーン可能なアニメーション
  chain(animations) {
    return animations.reduce((chain, animation) => {
      return chain.then(() => animation());
    }, Promise.resolve());
  }

  // 並列アニメーション
  parallel(animations) {
    return Promise.all(animations.map(animation => animation()));
  }

  // 縮小モーション設定チェック
  checkReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  generateId() {
    return `anim_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
}

// グローバルインスタンス
window.gameAnimations = new GameAnimationEngine();
```

### 2. 基本アニメーション定義
```css
/* フェードイン・アウト */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes fadeOut {
  from { opacity: 1; }
  to { opacity: 0; }
}

/* スライドアニメーション */
@keyframes slideInFromTop {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes slideInFromLeft {
  from {
    transform: translateX(-20px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOutToRight {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(20px);
    opacity: 0;
  }
}

/* スケールアニメーション */
@keyframes scaleIn {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes scaleOut {
  from {
    transform: scale(1);
    opacity: 1;
  }
  to {
    transform: scale(0.9);
    opacity: 0;
  }
}

/* バウンスアニメーション */
@keyframes bounceIn {
  0% {
    transform: scale(0.3);
    opacity: 0;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.8;
  }
  70% {
    transform: scale(0.95);
    opacity: 1;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* シェイクアニメーション（エラー用） */
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
  20%, 40%, 60%, 80% { transform: translateX(4px); }
}

/* パルスアニメーション（注意喚起用） */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* ユーティリティクラス */
.anim-fade-in {
  animation: fadeIn var(--duration-normal) var(--ease-out);
}

.anim-slide-in-top {
  animation: slideInFromTop var(--duration-normal) var(--ease-out);
}

.anim-scale-in {
  animation: scaleIn var(--duration-normal) var(--ease-out);
}

.anim-bounce-in {
  animation: bounceIn var(--duration-slow) var(--ease-bounce);
}

.anim-shake {
  animation: shake var(--duration-fast);
}

.anim-pulse {
  animation: pulse var(--duration-deliberate) infinite;
}

/* 縮小モーション対応 */
@media (prefers-reduced-motion: reduce) {
  .anim-fade-in,
  .anim-slide-in-top,
  .anim-scale-in,
  .anim-bounce-in {
    animation: fadeIn 0.01s;
  }
  
  .anim-shake,
  .anim-pulse {
    animation: none;
  }
}
```

## ゲーム専用アニメーション

### 1. ステータス変化アニメーション
```javascript
class StatusAnimations {
  // HP変化アニメーション
  static animateHPChange(element, oldValue, newValue, maxValue) {
    const isHealing = newValue > oldValue;
    const isDamage = newValue < oldValue;
    const isCritical = newValue <= maxValue * 0.2;
    
    // プログレスバー更新
    const progressBar = element.querySelector('.progress-bar');
    const percentage = (newValue / maxValue) * 100;
    
    // 数値カウントアニメーション
    this.animateNumber(element.querySelector('.hp-value'), oldValue, newValue, {
      duration: 500,
      formatter: (value) => `${Math.round(value)}/${maxValue}`
    });
    
    // プログレスバーアニメーション
    progressBar.style.transition = 'width 0.5s ease-out';
    progressBar.style.width = `${percentage}%`;
    
    // フィードバックアニメーション
    if (isHealing) {
      this.showHealingEffect(element);
    } else if (isDamage) {
      this.showDamageEffect(element);
    }
    
    // 危険状態の表示
    if (isCritical && !element.classList.contains('critical')) {
      element.classList.add('critical');
      progressBar.classList.add('pulse-red');
    } else if (!isCritical && element.classList.contains('critical')) {
      element.classList.remove('critical');
      progressBar.classList.remove('pulse-red');
    }
  }

  // 数値カウントアニメーション
  static animateNumber(element, from, to, options = {}) {
    const duration = options.duration || 300;
    const formatter = options.formatter || (value => Math.round(value));
    const startTime = performance.now();
    
    const animate = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // イージング適用
      const easedProgress = this.easeOutCubic(progress);
      const currentValue = from + (to - from) * easedProgress;
      
      element.textContent = formatter(currentValue);
      
      if (progress < 1) {
        requestAnimationFrame(animate);
      }
    };
    
    requestAnimationFrame(animate);
  }

  // 回復エフェクト
  static showHealingEffect(element) {
    const effect = document.createElement('div');
    effect.className = 'healing-effect';
    effect.textContent = '+';
    
    element.style.position = 'relative';
    element.appendChild(effect);
    
    // CSS for healing effect
    effect.style.cssText = `
      position: absolute;
      top: -10px;
      right: 10px;
      color: var(--color-success-600);
      font-weight: bold;
      pointer-events: none;
      animation: healingFloat 1s ease-out forwards;
    `;
    
    setTimeout(() => effect.remove(), 1000);
  }

  // ダメージエフェクト
  static showDamageEffect(element) {
    element.classList.add('damage-flash');
    setTimeout(() => element.classList.remove('damage-flash'), 200);
  }

  // イージング関数
  static easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
  }
}

// CSS for status animations
const statusAnimationCSS = `
@keyframes healingFloat {
  0% {
    transform: translateY(0) scale(1);
    opacity: 1;
  }
  100% {
    transform: translateY(-20px) scale(1.2);
    opacity: 0;
  }
}

.damage-flash {
  animation: damageFlash 0.2s ease-out;
}

@keyframes damageFlash {
  0%, 100% { background-color: transparent; }
  50% { background-color: rgba(239, 68, 68, 0.2); }
}

.pulse-red {
  animation: pulseRed 1s infinite;
}

@keyframes pulseRed {
  0%, 100% { background-color: var(--color-danger-600); }
  50% { background-color: var(--color-danger-400); }
}
`;
```

### 2. バトルアニメーション
```javascript
class BattleAnimations {
  // 攻撃アニメーション
  static async attack(attackerElement, targetElement) {
    // 攻撃者の前進アニメーション
    await window.gameAnimations.animate(attackerElement, [
      { transform: 'translateX(0) scale(1)' },
      { transform: 'translateX(20px) scale(1.1)', offset: 0.5 },
      { transform: 'translateX(0) scale(1)' }
    ], { duration: 600 });
    
    // ターゲットの被ダメージアニメーション
    await this.takeDamage(targetElement);
  }

  // 被ダメージアニメーション
  static async takeDamage(element) {
    return window.gameAnimations.animate(element, [
      { transform: 'translateX(0)', filter: 'brightness(1)' },
      { transform: 'translateX(-5px)', filter: 'brightness(1.5)', offset: 0.2 },
      { transform: 'translateX(5px)', filter: 'brightness(1.5)', offset: 0.4 },
      { transform: 'translateX(-3px)', filter: 'brightness(1.2)', offset: 0.6 },
      { transform: 'translateX(3px)', filter: 'brightness(1.2)', offset: 0.8 },
      { transform: 'translateX(0)', filter: 'brightness(1)' }
    ], { duration: 500 });
  }

  // 防御アニメーション
  static async defend(element) {
    return window.gameAnimations.animate(element, [
      { transform: 'scale(1)', filter: 'brightness(1)' },
      { transform: 'scale(0.95)', filter: 'brightness(1.2)' },
      { transform: 'scale(1)', filter: 'brightness(1)' }
    ], { duration: 300 });
  }

  // スキル使用アニメーション
  static async useSkill(element, skillType) {
    const animations = {
      magic: [
        { transform: 'scale(1) rotate(0deg)', filter: 'hue-rotate(0deg)' },
        { transform: 'scale(1.1) rotate(5deg)', filter: 'hue-rotate(90deg)', offset: 0.5 },
        { transform: 'scale(1) rotate(0deg)', filter: 'hue-rotate(0deg)' }
      ],
      heal: [
        { transform: 'scale(1)', filter: 'brightness(1) saturate(1)' },
        { transform: 'scale(1.05)', filter: 'brightness(1.3) saturate(1.5)', offset: 0.5 },
        { transform: 'scale(1)', filter: 'brightness(1) saturate(1)' }
      ],
      special: [
        { transform: 'scale(1) rotateY(0deg)' },
        { transform: 'scale(1.2) rotateY(180deg)', offset: 0.5 },
        { transform: 'scale(1) rotateY(360deg)' }
      ]
    };

    return window.gameAnimations.animate(
      element, 
      animations[skillType] || animations.magic, 
      { duration: 800 }
    );
  }

  // 戦闘不能アニメーション
  static async defeated(element) {
    await window.gameAnimations.animate(element, [
      { transform: 'scale(1) rotate(0deg)', opacity: 1 },
      { transform: 'scale(0.8) rotate(-5deg)', opacity: 0.7, offset: 0.7 },
      { transform: 'scale(0.6) rotate(-10deg)', opacity: 0.3 }
    ], { duration: 1000, fill: 'forwards' });
    
    element.classList.add('defeated');
  }

  // 勝利アニメーション
  static async victory(element) {
    return window.gameAnimations.animate(element, [
      { transform: 'scale(1) translateY(0)', filter: 'brightness(1)' },
      { transform: 'scale(1.1) translateY(-10px)', filter: 'brightness(1.3)', offset: 0.3 },
      { transform: 'scale(1.05) translateY(-5px)', filter: 'brightness(1.2)', offset: 0.6 },
      { transform: 'scale(1) translateY(0)', filter: 'brightness(1)' }
    ], { duration: 1200 });
  }
}
```

### 3. アイテム・インベントリアニメーション
```javascript
class InventoryAnimations {
  // アイテム取得アニメーション
  static async itemObtained(itemElement, inventorySlot) {
    // アイテムの出現
    await window.gameAnimations.animate(itemElement, [
      { transform: 'scale(0) rotate(0deg)', opacity: 0 },
      { transform: 'scale(1.2) rotate(180deg)', opacity: 1, offset: 0.6 },
      { transform: 'scale(1) rotate(360deg)', opacity: 1 }
    ], { duration: 600 });

    // インベントリスロットへの移動（必要に応じて）
    if (inventorySlot) {
      const itemRect = itemElement.getBoundingClientRect();
      const slotRect = inventorySlot.getBoundingClientRect();
      
      const deltaX = slotRect.left - itemRect.left;
      const deltaY = slotRect.top - itemRect.top;
      
      await window.gameAnimations.animate(itemElement, [
        { transform: 'translate(0, 0) scale(1)' },
        { transform: `translate(${deltaX}px, ${deltaY}px) scale(0.8)` }
      ], { duration: 400 });
    }
  }

  // アイテム使用アニメーション
  static async itemUsed(itemElement) {
    await window.gameAnimations.animate(itemElement, [
      { transform: 'scale(1) rotate(0deg)', opacity: 1 },
      { transform: 'scale(1.3) rotate(20deg)', opacity: 0.8, offset: 0.3 },
      { transform: 'scale(0) rotate(40deg)', opacity: 0 }
    ], { duration: 500 });
  }

  // ドラッグ＆ドロップアニメーション
  static startDrag(element) {
    element.style.transform = 'scale(1.1) rotate(5deg)';
    element.style.zIndex = '1000';
    element.style.transition = 'transform 0.2s ease-out';
  }

  static endDrag(element, success) {
    if (success) {
      // 成功時のアニメーション
      window.gameAnimations.animate(element, [
        { transform: 'scale(1.1) rotate(5deg)' },
        { transform: 'scale(1) rotate(0deg)' }
      ], { duration: 200 });
    } else {
      // 失敗時のアニメーション（元の位置に戻る）
      window.gameAnimations.animate(element, [
        { transform: 'scale(1.1) rotate(5deg)' },
        { transform: 'scale(1.2) rotate(-5deg)', offset: 0.5 },
        { transform: 'scale(1) rotate(0deg)' }
      ], { duration: 300 });
    }
    
    element.style.zIndex = '';
  }

  // スロット強調アニメーション
  static highlightSlot(slotElement, highlight = true) {
    if (highlight) {
      slotElement.classList.add('slot-highlight');
    } else {
      slotElement.classList.remove('slot-highlight');
    }
  }
}

// CSS for inventory animations
const inventoryAnimationCSS = `
.slot-highlight {
  animation: slotPulse 1s infinite;
  border-color: var(--color-primary-400) !important;
}

@keyframes slotPulse {
  0%, 100% {
    background-color: var(--color-primary-50);
    transform: scale(1);
  }
  50% {
    background-color: var(--color-primary-100);
    transform: scale(1.05);
  }
}

.item-dragging {
  transform: scale(1.1) rotate(5deg);
  z-index: 1000;
  box-shadow: var(--shadow-lg);
}
`;
```

## パフォーマンス最適化

### 1. Animation Performance Monitor
```javascript
class AnimationPerformanceMonitor {
  constructor() {
    this.metrics = {
      totalAnimations: 0,
      activeAnimations: 0,
      droppedFrames: 0,
      averageFPS: 60
    };
    this.frameTimeHistory = [];
    this.lastFrameTime = performance.now();
  }

  trackAnimation(animation) {
    this.metrics.totalAnimations++;
    this.metrics.activeAnimations++;
    
    animation.addEventListener('finish', () => {
      this.metrics.activeAnimations--;
    });

    animation.addEventListener('cancel', () => {
      this.metrics.activeAnimations--;
    });
  }

  startFrameMonitoring() {
    const monitorFrame = (timestamp) => {
      const frameTime = timestamp - this.lastFrameTime;
      this.frameTimeHistory.push(frameTime);
      
      // 過去60フレームの平均を保持
      if (this.frameTimeHistory.length > 60) {
        this.frameTimeHistory.shift();
      }
      
      // FPS計算
      this.metrics.averageFPS = 1000 / (
        this.frameTimeHistory.reduce((sum, time) => sum + time, 0) / 
        this.frameTimeHistory.length
      );
      
      // フレームドロップ検出
      if (frameTime > 20) { // 50fps以下
        this.metrics.droppedFrames++;
      }
      
      this.lastFrameTime = timestamp;
      requestAnimationFrame(monitorFrame);
    };
    
    requestAnimationFrame(monitorFrame);
  }

  getPerformanceReport() {
    return {
      ...this.metrics,
      recommendation: this.generateRecommendation()
    };
  }

  generateRecommendation() {
    if (this.metrics.averageFPS < 30) {
      return 'critical: アニメーションを削減してください';
    } else if (this.metrics.averageFPS < 45) {
      return 'warning: パフォーマンスが低下しています';
    } else if (this.metrics.activeAnimations > 10) {
      return 'info: 同時実行アニメーション数が多めです';
    }
    return 'good: パフォーマンスは良好です';
  }
}
```

### 2. 最適化戦略
```javascript
class AnimationOptimizer {
  static optimizeForDevice() {
    const isLowEndDevice = this.detectLowEndDevice();
    const hasReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (isLowEndDevice || hasReducedMotion) {
      // 軽量モード設定
      document.documentElement.classList.add('reduced-animations');
      this.disableExpensiveAnimations();
    }
  }

  static detectLowEndDevice() {
    // CPU コア数チェック
    const cores = navigator.hardwareConcurrency || 2;
    
    // メモリチェック（利用可能な場合）
    const memory = navigator.deviceMemory || 2;
    
    // User Agentベースの簡易判定
    const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    return cores <= 2 || memory <= 2 || isMobile;
  }

  static disableExpensiveAnimations() {
    const style = document.createElement('style');
    style.textContent = `
      .reduced-animations * {
        animation-duration: 0.01s !important;
        animation-delay: 0s !important;
        transition-duration: 0.01s !important;
        transition-delay: 0s !important;
      }
      
      .reduced-animations .complex-animation {
        display: none !important;
      }
    `;
    document.head.appendChild(style);
  }

  // アニメーションの間引き
  static throttleAnimations(animations, maxConcurrent = 3) {
    const queue = [...animations];
    const active = [];
    
    const processNext = () => {
      if (queue.length > 0 && active.length < maxConcurrent) {
        const next = queue.shift();
        active.push(next);
        
        next().finally(() => {
          const index = active.indexOf(next);
          active.splice(index, 1);
          processNext();
        });
      }
    };
    
    // 初期実行
    for (let i = 0; i < maxConcurrent && queue.length > 0; i++) {
      processNext();
    }
  }
}
```

## アクセシビリティ配慮

### 1. 縮小モーション対応
```css
/* 基本的な縮小モーション対応 */
@media (prefers-reduced-motion: reduce) {
  /* すべてのアニメーションを無効化 */
  *, *::before, *::after {
    animation-duration: 0.01s !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01s !important;
    scroll-behavior: auto !important;
  }
  
  /* 重要な状態変化は残す */
  .critical-feedback {
    animation: none !important;
    transition: background-color 0.01s !important;
  }
  
  /* フォーカス表示は維持 */
  :focus {
    transition: outline 0.01s !important;
  }
}

/* より細かい制御 */
@media (prefers-reduced-motion: reduce) {
  .decorative-animation {
    animation: none !important;
  }
  
  .functional-animation {
    animation-duration: 0.2s !important;
  }
}
```

### 2. アニメーション制御API
```javascript
class AccessibleAnimationController {
  constructor() {
    this.prefersReducedMotion = this.checkReducedMotion();
    this.userOverride = null; // ユーザー設定による上書き
    
    // 設定変更の監視
    this.mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    this.mediaQuery.addListener(this.handleMotionPreferenceChange.bind(this));
  }

  checkReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  handleMotionPreferenceChange(e) {
    this.prefersReducedMotion = e.matches;
    this.updateAnimationSettings();
  }

  // ユーザー設定の保存
  setUserPreference(preference) {
    this.userOverride = preference;
    localStorage.setItem('animation-preference', preference);
    this.updateAnimationSettings();
  }

  getUserPreference() {
    if (this.userOverride !== null) return this.userOverride;
    
    const stored = localStorage.getItem('animation-preference');
    if (stored) return stored === 'true';
    
    return !this.prefersReducedMotion; // デフォルト
  }

  updateAnimationSettings() {
    const enableAnimations = this.getUserPreference();
    
    document.documentElement.classList.toggle('animations-disabled', !enableAnimations);
    
    // アニメーションエンジンに通知
    if (window.gameAnimations) {
      window.gameAnimations.reducedMotion = !enableAnimations;
    }
  }

  // 段階的なアニメーション制御
  getAnimationLevel() {
    if (!this.getUserPreference()) return 'none';
    if (this.prefersReducedMotion) return 'essential';
    return 'full';
  }

  shouldPlayAnimation(animationType) {
    const level = this.getAnimationLevel();
    
    switch (level) {
      case 'none':
        return false;
      case 'essential':
        return ['feedback', 'status-change', 'error'].includes(animationType);
      case 'full':
        return true;
      default:
        return true;
    }
  }
}

// グローバルコントローラー
window.animationController = new AccessibleAnimationController();
```

### 3. スクリーンリーダー対応
```javascript
class AnimationAccessibility {
  // アニメーション開始時の音声説明
  static announceAnimation(description, type = 'polite') {
    if (window.a11y) {
      window.a11y.announce(description, type);
    }
  }

  // アニメーション中の要素にaria属性追加
  static makeAnimationAccessible(element, description) {
    element.setAttribute('aria-live', 'polite');
    element.setAttribute('aria-description', description);
    
    // アニメーション完了後にクリーンアップ
    element.addEventListener('animationend', () => {
      element.removeAttribute('aria-live');
      element.removeAttribute('aria-description');
    }, { once: true });
  }

  // 重要な状態変化の代替表現
  static provideAlternativeFeedback(element, change) {
    // 視覚的アニメーションが無効でも状態は伝える
    const announcement = this.createStatusAnnouncement(change);
    this.announceAnimation(announcement);
    
    // 視覚的な代替表現（色や形の変化）
    element.classList.add(`status-${change.type}`);
    setTimeout(() => {
      element.classList.remove(`status-${change.type}`);
    }, 1000);
  }

  static createStatusAnnouncement(change) {
    switch (change.type) {
      case 'hp-decrease':
        return `HPが${change.amount}減少して${change.newValue}になりました`;
      case 'hp-increase':
        return `HPが${change.amount}回復して${change.newValue}になりました`;
      case 'level-up':
        return `レベルが${change.newLevel}に上がりました！`;
      case 'item-obtained':
        return `${change.itemName}を手に入れました`;
      default:
        return change.description || '状態が変化しました';
    }
  }
}
```

## 状態変化アニメーション

### 1. Page Transitions
```javascript
class PageTransitionManager {
  constructor() {
    this.currentPage = null;
    this.transitionDuration = 300;
  }

  async transitionTo(newPageElement, transitionType = 'slide') {
    const oldPage = this.currentPage;
    
    if (oldPage) {
      await this.exitAnimation(oldPage, transitionType);
    }
    
    await this.enterAnimation(newPageElement, transitionType);
    this.currentPage = newPageElement;
  }

  async exitAnimation(element, type) {
    const animations = {
      slide: [
        { transform: 'translateX(0)', opacity: 1 },
        { transform: 'translateX(-100%)', opacity: 0 }
      ],
      fade: [
        { opacity: 1 },
        { opacity: 0 }
      ],
      scale: [
        { transform: 'scale(1)', opacity: 1 },
        { transform: 'scale(0.9)', opacity: 0 }
      ]
    };

    return window.gameAnimations.animate(
      element, 
      animations[type] || animations.fade, 
      { duration: this.transitionDuration }
    );
  }

  async enterAnimation(element, type) {
    const animations = {
      slide: [
        { transform: 'translateX(100%)', opacity: 0 },
        { transform: 'translateX(0)', opacity: 1 }
      ],
      fade: [
        { opacity: 0 },
        { opacity: 1 }
      ],
      scale: [
        { transform: 'scale(1.1)', opacity: 0 },
        { transform: 'scale(1)', opacity: 1 }
      ]
    };

    return window.gameAnimations.animate(
      element, 
      animations[type] || animations.fade, 
      { duration: this.transitionDuration }
    );
  }
}
```

### 2. Modal Animations
```css
/* モーダルアニメーション */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  animation: modalOverlayIn var(--duration-normal) ease-out forwards;
}

.modal-overlay.closing {
  animation: modalOverlayOut var(--duration-normal) ease-in forwards;
}

.modal-content {
  background: var(--color-surface);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-6);
  max-width: 90vw;
  max-height: 90vh;
  overflow: auto;
  transform: scale(0.9) translateY(20px);
  animation: modalContentIn var(--duration-normal) ease-out forwards;
}

.modal-overlay.closing .modal-content {
  animation: modalContentOut var(--duration-normal) ease-in forwards;
}

@keyframes modalOverlayIn {
  to { opacity: 1; }
}

@keyframes modalOverlayOut {
  to { opacity: 0; }
}

@keyframes modalContentIn {
  to {
    transform: scale(1) translateY(0);
  }
}

@keyframes modalContentOut {
  to {
    transform: scale(0.9) translateY(20px);
  }
}
```

## マイクロインタラクション

### 1. Button Interactions
```css
/* ボタンのマイクロインタラクション */
.interactive-button {
  position: relative;
  overflow: hidden;
  transition: all var(--duration-fast) ease-out;
}

.interactive-button:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.interactive-button:active {
  transform: translateY(0);
  transition-duration: var(--duration-instant);
}

/* リップルエフェクト */
.interactive-button::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: translate(-50%, -50%);
  transition: width var(--duration-normal), height var(--duration-normal);
}

.interactive-button:active::after {
  width: 300px;
  height: 300px;
}

/* 読み込み状態 */
.interactive-button.loading {
  pointer-events: none;
  opacity: 0.7;
}

.interactive-button.loading::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 16px;
  height: 16px;
  margin: -8px 0 0 -8px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin var(--duration-deliberate) linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
```

### 2. Form Interactions
```css
/* フォームのマイクロインタラクション */
.form-field {
  position: relative;
}

.form-input {
  padding: var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius-md);
  transition: all var(--duration-fast) ease-out;
  background: var(--color-surface);
}

.form-input:focus {
  border-color: var(--color-primary-500);
  box-shadow: 0 0 0 3px var(--color-primary-100);
  outline: none;
}

.form-input:invalid:not(:focus) {
  border-color: var(--color-danger-500);
  animation: inputError var(--duration-fast) ease-out;
}

@keyframes inputError {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-2px); }
  75% { transform: translateX(2px); }
}

/* フローティングラベル */
.floating-label {
  position: absolute;
  top: var(--spacing-3);
  left: var(--spacing-3);
  pointer-events: none;
  transition: all var(--duration-fast) ease-out;
  color: var(--color-text-secondary);
}

.form-input:focus + .floating-label,
.form-input:not(:placeholder-shown) + .floating-label {
  top: -8px;
  left: var(--spacing-2);
  font-size: var(--font-size-xs);
  color: var(--color-primary-600);
  background: var(--color-surface);
  padding: 0 var(--spacing-1);
}
```

## レスポンシブアニメーション

### 1. Device-Specific Optimizations
```css
/* モバイル最適化 */
@media (max-width: 768px) {
  /* より短いアニメーション */
  :root {
    --duration-fast: 0.15s;
    --duration-normal: 0.2s;
    --duration-slow: 0.3s;
  }
  
  /* タッチフィードバック強化 */
  .touch-feedback:active {
    transform: scale(0.95);
    transition-duration: var(--duration-instant);
  }
}

/* 高解像度ディスプレイ */
@media (-webkit-min-device-pixel-ratio: 2) {
  /* より精細なアニメーション */
  .high-res-animation {
    animation-timing-function: cubic-bezier(0.25, 0.46, 0.45, 0.94);
  }
}

/* 低スペックデバイス対応 */
@media (max-width: 768px) and (max-height: 1024px) {
  .complex-animation {
    animation: none !important;
  }
  
  .essential-animation {
    animation-duration: 0.2s !important;
  }
}
```

### 2. Performance-Based Scaling
```javascript
class ResponsiveAnimationManager {
  constructor() {
    this.performanceLevel = this.detectPerformanceLevel();
    this.applyPerformanceOptimizations();
  }

  detectPerformanceLevel() {
    const cores = navigator.hardwareConcurrency || 2;
    const memory = navigator.deviceMemory || 2;
    const connection = navigator.connection?.effectiveType || '4g';
    
    if (cores >= 8 && memory >= 8 && connection === '4g') {
      return 'high';
    } else if (cores >= 4 && memory >= 4) {
      return 'medium';
    } else {
      return 'low';
    }
  }

  applyPerformanceOptimizations() {
    const level = this.performanceLevel;
    
    const styles = {
      high: {
        '--animation-quality': '1',
        '--max-concurrent-animations': '10',
        '--enable-complex-animations': '1'
      },
      medium: {
        '--animation-quality': '0.8',
        '--max-concurrent-animations': '5',
        '--enable-complex-animations': '0'
      },
      low: {
        '--animation-quality': '0.5',
        '--max-concurrent-animations': '3',
        '--enable-complex-animations': '0'
      }
    };

    Object.entries(styles[level]).forEach(([property, value]) => {
      document.documentElement.style.setProperty(property, value);
    });
  }
}
```

## 実装ガイドライン

### 1. 開発ワークフロー
```javascript
// アニメーション実装チェックリスト
const AnimationChecklist = {
  design: [
    '目的が明確か（装飾 vs 機能）',
    'タイミングが適切か',
    'ブランドガイドラインに準拠しているか'
  ],
  implementation: [
    'GPU加速プロパティを使用しているか',
    'will-changeを適切に使用・解除しているか',
    'メモリリークの可能性はないか'
  ],
  accessibility: [
    'prefers-reduced-motionに対応しているか',
    'キーボードナビゲーションは阻害されないか',
    'スクリーンリーダーへの配慮はあるか'
  ],
  performance: [
    '60fpsを維持できるか',
    '同時実行アニメーション数は適切か',
    'モバイルデバイスでも滑らかか'
  ]
};

// アニメーション品質テスト
class AnimationQualityTester {
  static async testAnimation(element, animation) {
    const results = {
      performance: await this.testPerformance(element, animation),
      accessibility: this.testAccessibility(element),
      usability: this.testUsability(animation)
    };
    
    return results;
  }

  static async testPerformance(element, animation) {
    const startTime = performance.now();
    let frameCount = 0;
    let droppedFrames = 0;
    
    const countFrames = () => {
      frameCount++;
      const now = performance.now();
      if (now - startTime < animation.duration) {
        requestAnimationFrame(countFrames);
      }
    };
    
    requestAnimationFrame(countFrames);
    await animation;
    
    const actualFrameRate = frameCount / (animation.duration / 1000);
    const expectedFrameRate = 60;
    
    return {
      actualFrameRate,
      droppedFrames: Math.max(0, expectedFrameRate - actualFrameRate),
      isSmooth: actualFrameRate >= 55
    };
  }

  static testAccessibility(element) {
    return {
      hasReducedMotionFallback: this.hasReducedMotionCSS(element),
      hasAriaLabels: element.hasAttribute('aria-label') || element.hasAttribute('aria-describedby'),
      focusNotBlocked: this.canReceiveFocus(element)
    };
  }

  static testUsability(animation) {
    return {
      durationAppropriate: animation.duration >= 200 && animation.duration <= 500,
      easingNatural: animation.easing !== 'linear',
      purposeClear: animation.purpose !== 'decoration'
    };
  }
}
```

### 2. デバッグツール
```javascript
// アニメーションデバッガー
class AnimationDebugger {
  constructor() {
    this.isEnabled = localStorage.getItem('debug-animations') === 'true';
    this.overlay = null;
    
    if (this.isEnabled) {
      this.createDebugOverlay();
    }
  }

  createDebugOverlay() {
    this.overlay = document.createElement('div');
    this.overlay.id = 'animation-debug-overlay';
    this.overlay.style.cssText = `
      position: fixed;
      top: 10px;
      right: 10px;
      background: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 10px;
      border-radius: 5px;
      font-family: monospace;
      font-size: 12px;
      z-index: 10000;
      pointer-events: none;
    `;
    document.body.appendChild(this.overlay);
    
    this.updateOverlay();
    setInterval(() => this.updateOverlay(), 100);
  }

  updateOverlay() {
    if (!this.overlay) return;
    
    const activeAnimations = document.getAnimations().length;
    const fps = window.gameAnimations?.performance?.metrics?.averageFPS || 'N/A';
    
    this.overlay.innerHTML = `
      Active Animations: ${activeAnimations}<br>
      Current FPS: ${Math.round(fps)}<br>
      Reduced Motion: ${window.animationController?.prefersReducedMotion ? 'ON' : 'OFF'}
    `;
  }

  logAnimation(name, element, details) {
    if (!this.isEnabled) return;
    
    console.group(`🎬 Animation: ${name}`);
    console.log('Element:', element);
    console.log('Details:', details);
    console.log('Performance Impact:', this.calculateImpact(details));
    console.groupEnd();
  }

  calculateImpact(details) {
    let score = 0;
    
    // GPU加速プロパティの使用
    if (details.properties?.includes('transform')) score += 2;
    if (details.properties?.includes('opacity')) score += 2;
    
    // レイアウトに影響するプロパティ
    if (details.properties?.some(p => ['width', 'height', 'margin', 'padding'].includes(p))) {
      score -= 3;
    }
    
    // 継続時間
    if (details.duration > 1000) score -= 1;
    if (details.duration < 100) score += 1;
    
    return score > 0 ? 'Low' : score > -2 ? 'Medium' : 'High';
  }
}

// デバッグ機能を有効化
window.animationDebugger = new AnimationDebugger();
```

---

このアニメーションシステム書は、test_smgプロジェクトで統一感のある、パフォーマンスに配慮した、アクセシブルなアニメーションを実現するための包括的なガイドラインです。CGI時代の親しみやすさと現代的なユーザビリティを両立させ、すべてのユーザーが快適にゲームを楽しめるよう設計されています。