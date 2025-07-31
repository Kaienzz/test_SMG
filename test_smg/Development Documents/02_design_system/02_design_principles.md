# デザイン原則書

## プロジェクト情報
- **プロジェクト名**: test_smg（Simple Management Game）
- **作成日**: 2025年7月26日
- **バージョン**: 1.0
- **作成者**: Claude（AI開発アシスタント）
- **想定開発時間**: 3時間

## 目次
1. [デザイン原則の概要](#デザイン原則の概要)
2. [核となる設計思想](#核となる設計思想)
3. [UI設計原則](#ui設計原則)
4. [UX設計原則](#ux設計原則)
5. [ビジュアル設計原則](#ビジュアル設計原則)
6. [技術的制約と配慮](#技術的制約と配慮)
7. [アクセシビリティ原則](#アクセシビリティ原則)
8. [ブランディング指針](#ブランディング指針)
9. [実装ガイドライン](#実装ガイドライン)

## デザイン原則の概要

### プロジェクトビジョン
test_smgは「現代的な技術で蘇る懐かしのCGIゲーム体験」をコンセプトとし、昔懐かしいブラウザRPGの温かみと親しみやすさを、現代のUI/UX標準で表現するゲームです。

### デザインの使命
- **懐かしさと新しさの融合**: CGI時代の素朴な魅力を現代的なデザインで表現
- **直感的な操作性**: ゲーム初心者でもすぐに理解できるシンプルなインターフェース
- **没入感の創出**: プレイヤーがゲーム世界に自然に没入できる一貫した体験
- **アクセシビリティの確保**: すべてのユーザーが楽しめるインクルーシブなデザイン

## 核となる設計思想

### 1. シンプルさの追求（Simplicity First）
```
原則: 複雑さを隠し、本質を際立たせる
実践: 
- 1画面1機能の徹底
- 3クリック以内でのアクション完了
- 視覚的ノイズの排除
```

**具体例**:
- ステータス表示: HP 50/100 （シンプルな分数表記）
- ボタン配置: 主要アクション3つ以下に制限
- 情報階層: 重要度による明確な視覚的差別化

### 2. 予測可能性（Predictability）
```
原則: ユーザーの期待に応える一貫した挙動
実践:
- 同じアクションは常に同じ結果を生む
- 状態変化は明確なフィードバックで通知
- 標準的なWebUIパターンの採用
```

**具体例**:
- 攻撃ボタン: 常に同じ位置、同じ色、同じ挙動
- ローディング状態: 統一されたスピナーとメッセージ
- エラー表示: 一貫したスタイルと配置

### 3. 段階的開示（Progressive Disclosure）
```
原則: 必要な情報を適切なタイミングで提示
実践:
- 基本情報の優先表示
- 詳細情報のオンデマンド表示
- 習熟度に応じた機能公開
```

**具体例**:
- キャラクター情報: 基本ステータス → 詳細ステータス → 装備詳細
- スキル画面: 習得済み → 習得可能 → 今後解放予定
- バトル: 基本操作 → 高度な戦術 → システム詳細

## UI設計原則

### 1. 視覚的階層（Visual Hierarchy）

#### 重要度レベル定義
```css
/* プライマリ（最重要）*/
.primary-element {
  color: var(--color-primary-600);
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-bold);
}

/* セカンダリ（重要）*/
.secondary-element {
  color: var(--color-neutral-700);
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
}

/* ターシャリ（補助）*/
.tertiary-element {
  color: var(--color-neutral-500);
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-normal);
}
```

#### 情報の重要度
1. **最重要**: プレイヤーの現在HP/SP、所持金
2. **重要**: 現在位置、実行可能アクション
3. **補助**: フレーバーテキスト、システムメッセージ

### 2. 色彩設計原則

#### カラーの意味付け
```scss
// 機能的色彩
$colors: (
  // 状態色
  'success': #22c55e,    // 成功、回復
  'warning': #f59e0b,    // 注意、消費
  'danger': #ef4444,     // 危険、損失
  'info': #3b82f6,       // 情報、中立
  
  // ゲーム専用色
  'hp': #dc2626,         // HP（赤系）
  'sp': #2563eb,         // SP（青系）
  'exp': #7c3aed,        // 経験値（紫系）
  'gold': #f59e0b,       // 所持金（金系）
);
```

#### 色彩の使用ルール
- **赤色系**: HP、危険、緊急性
- **青色系**: SP、情報、冷静さ
- **緑色系**: 成功、回復、成長
- **黄色系**: 注意、所持金、貴重品

### 3. タイポグラフィ原則

#### フォント選択基準
```css
/* メインフォント: 読みやすさ重視 */
font-family: 
  'Hiragino Kaku Gothic ProN', 
  'Hiragino Sans', 
  Meiryo, 
  sans-serif;

/* 数値フォント: 判読性重視 */
.numeric {
  font-family: 
    'SF Mono', 
    Monaco, 
    'Cascadia Code', 
    'Roboto Mono', 
    monospace;
}
```

#### 読みやすさの確保
- **行間**: 1.6倍以上
- **文字間**: デフォルト + 0.025em
- **コントラスト比**: 4.5:1以上（WCAG AA準拠）

## UX設計原則

### 1. 学習コストの最小化

#### オンボーディング戦略
```
段階1: 基本操作の習得（移動、アクション実行）
段階2: システム理解（ステータス、装備）
段階3: 戦略的思考（スキル選択、リソース管理）
```

#### ガイダンス設計
- **コンテキストヘルプ**: アクション時のヒント表示
- **プログレッシブガイド**: 段階的な機能解放
- **エラーリカバリ**: 間違いからの学習支援

### 2. フィードバックの充実

#### 即座のフィードバック
```javascript
// アクション実行時の即座の反応
const provideFeedback = (action, result) => {
  // 視覚的フィードバック
  showVisualEffect(action.type);
  // 数値変化の明示
  animateValueChange(result.changes);
  // 音響フィードバック（オプション）
  playActionSound(action.type);
};
```

#### 長期的な進捗表示
- **レベル進捗**: 経験値バーによる可視化
- **目標到達**: マイルストーンの明確な表示
- **成長実感**: 過去との比較データ

### 3. エラー予防と回復

#### エラー予防策
```typescript
interface ActionValidation {
  canExecute: boolean;
  requirements: string[];
  consequences: string[];
  alternatives: string[];
}

// 実行前の警告システム
const validateAction = (action: GameAction): ActionValidation => {
  return {
    canExecute: checkRequirements(action),
    requirements: getUnmetRequirements(action),
    consequences: getPredictedOutcomes(action),
    alternatives: getSuggestedAlternatives(action)
  };
};
```

#### 回復支援
- **取り消し機能**: 重要でない操作の巻き戻し
- **確認ダイアログ**: 不可逆操作の事前確認
- **状態復元**: セーブポイントからの復帰

## ビジュアル設計原則

### 1. 一貫性の維持

#### コンポーネント統一
```css
/* ボタンの基本スタイル */
.btn {
  padding: var(--spacing-3) var(--spacing-4);
  border-radius: var(--border-radius-md);
  font-weight: var(--font-weight-medium);
  transition: all var(--transition-duration-normal);
  
  &:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
  }
}

/* 状態別バリエーション */
.btn--primary { @apply bg-primary-600 text-white; }
.btn--secondary { @apply bg-neutral-200 text-neutral-900; }
.btn--danger { @apply bg-red-600 text-white; }
```

#### グリッドシステム
```css
/* 8px基準のグリッド */
.grid-container {
  display: grid;
  gap: var(--spacing-4); /* 16px */
  padding: var(--spacing-4);
}

/* レスポンシブ対応 */
@media (min-width: 768px) {
  .grid-container {
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-6); /* 24px */
  }
}
```

### 2. 空白の活用

#### 余白設計原則
```
密度レベル:
- Compact: 8px間隔（情報一覧）
- Normal: 16px間隔（標準レイアウト）
- Spacious: 24px間隔（重要エリア）
```

#### 呼吸できるデザイン
- **セクション間**: 32px以上の余白
- **関連要素**: 8-16pxの密接な配置
- **独立要素**: 24px以上の分離

### 3. アニメーションの効果的活用

#### アニメーション設計原則
```css
/* 基本タイミング関数 */
:root {
  --easing-ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
  --easing-ease-in-out: cubic-bezier(0.645, 0.045, 0.355, 1);
  --easing-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

/* 用途別アニメーション */
.fade-enter {
  animation: fadeIn 0.3s var(--easing-ease-out);
}

.slide-up {
  animation: slideUp 0.2s var(--easing-ease-out);
}

.bounce-in {
  animation: bounceIn 0.5s var(--easing-bounce);
}
```

#### アニメーション使用指針
- **フィードバック**: 0.1-0.2秒の素早い反応
- **遷移**: 0.3-0.5秒の自然な変化
- **演出**: 0.5-1.0秒の印象的な効果

## 技術的制約と配慮

### 1. パフォーマンス最適化

#### レンダリング効率
```javascript
// 仮想化による大量データ表示
const VirtualizedList = ({ items, itemHeight }) => {
  const [visibleRange, setVisibleRange] = useState({ start: 0, end: 10 });
  
  const updateVisibleRange = useCallback((scrollTop) => {
    const start = Math.floor(scrollTop / itemHeight);
    const end = start + Math.ceil(window.innerHeight / itemHeight);
    setVisibleRange({ start, end });
  }, [itemHeight]);
  
  return (
    <div className="virtual-list">
      {items.slice(visibleRange.start, visibleRange.end).map(renderItem)}
    </div>
  );
};
```

#### リソース管理
- **画像最適化**: WebP形式、適切なサイズ
- **CSS最適化**: クリティカルパスCSS、遅延読み込み
- **JavaScript最適化**: コード分割、Tree Shaking

### 2. レスポンシブ対応

#### ブレークポイント戦略
```css
/* モバイルファースト設計 */
.responsive-layout {
  /* Mobile: 320px以上 */
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
  
  /* Tablet: 768px以上 */
  @media (min-width: 768px) {
    flex-direction: row;
    gap: var(--spacing-6);
  }
  
  /* Desktop: 1024px以上 */
  @media (min-width: 1024px) {
    max-width: 1200px;
    margin: 0 auto;
  }
}
```

#### タッチインターフェース対応
- **タップターゲット**: 44px以上のサイズ確保
- **ジェスチャー**: スワイプ、ピンチズーム対応
- **ホバー状態**: タッチデバイスでの適切な処理

## アクセシビリティ原則

### 1. WCAG 2.1 準拠

#### レベルAA達成項目
```html
<!-- セマンティックHTML -->
<main role="main">
  <h1>ゲームメイン画面</h1>
  <section aria-labelledby="status-heading">
    <h2 id="status-heading">キャラクターステータス</h2>
    <div role="region" aria-live="polite">
      <p>HP: <span aria-label="50点中50点">50/50</span></p>
      <p>SP: <span aria-label="30点中30点">30/30</span></p>
    </div>
  </section>
</main>

<!-- キーボードナビゲーション -->
<button 
  type="button"
  aria-describedby="attack-help"
  tabindex="0">
  攻撃
</button>
<div id="attack-help" class="sr-only">
  敵に物理攻撃を行います。SPを2消費します。
</div>
```

#### 色覚対応
```css
/* 色以外の情報伝達手段 */
.status-critical {
  background-color: var(--color-danger-100);
  border-left: 4px solid var(--color-danger-600);
  position: relative;
}

.status-critical::before {
  content: "⚠️";
  position: absolute;
  left: var(--spacing-2);
  top: 50%;
  transform: translateY(-50%);
}
```

### 2. スクリーンリーダー対応

#### 適切なラベリング
```javascript
// 動的コンテンツの読み上げ対応
const announceToScreenReader = (message, priority = 'polite') => {
  const announcement = document.createElement('div');
  announcement.setAttribute('aria-live', priority);
  announcement.setAttribute('aria-atomic', 'true');
  announcement.className = 'sr-only';
  announcement.textContent = message;
  
  document.body.appendChild(announcement);
  
  setTimeout(() => {
    document.body.removeChild(announcement);
  }, 1000);
};
```

## ブランディング指針

### 1. ゲームトーン

#### 親しみやすさの演出
```
語調: 丁寧だが親しみやすい関西弁の要素
例: 「おつかれさまや！」「ええ感じやん！」
理由: CGI時代の個人製作ゲームの温かみを再現
```

#### メッセージライティング
- **成功**: 「やったね！」「すごいやん！」
- **失敗**: 「あー、惜しい！」「次はうまくいくで！」
- **情報**: 「そういえば...」「ちなみに...」

### 2. ビジュアルアイデンティティ

#### アイコンスタイル
```css
/* 手作り感のあるアイコン設計 */
.icon {
  /* 完璧すぎない、少し歪んだ形状 */
  border-radius: 6px 8px 6px 8px;
  /* 温かみのある影 */
  box-shadow: 2px 3px 4px rgba(0, 0, 0, 0.1);
  /* 手書き風のボーダー */
  border: 1px solid currentColor;
}
```

#### イラストスタイル
- **手描き風**: 完璧すぎない、人間味のある線
- **温かい色合い**: 彩度を抑えた親しみやすい色調
- **シンプルな形状**: 識別しやすい明確なシルエット

## 実装ガイドライン

### 1. CSS設計方針

#### BEM + Utility-First
```css
/* BEMでコンポーネント構造 */
.character-status {
  /* ベーススタイル */
}

.character-status__hp {
  /* HP表示部分 */
}

.character-status__hp--critical {
  /* HP危険状態 */
}

/* Utilityクラスで微調整 */
.character-status {
  @apply p-4 bg-white rounded-lg shadow-sm;
}
```

#### CSS Custom Propertiesの活用
```css
/* テーマ切り替え対応 */
.theme-light {
  --bg-primary: #ffffff;
  --text-primary: #1f2937;
  --accent: #3b82f6;
}

.theme-dark {
  --bg-primary: #1f2937;
  --text-primary: #f9fafb;
  --accent: #60a5fa;
}
```

### 2. JavaScript設計方針

#### 状態管理パターン
```javascript
// 単純なState管理
class GameState {
  constructor() {
    this.data = {
      player: null,
      location: null,
      ui: {
        loading: false,
        activeModal: null
      }
    };
    this.listeners = [];
  }
  
  update(path, value) {
    this.data = updateNestedProperty(this.data, path, value);
    this.notifyListeners();
  }
  
  subscribe(callback) {
    this.listeners.push(callback);
    return () => {
      this.listeners = this.listeners.filter(l => l !== callback);
    };
  }
}
```

#### エラーハンドリング
```javascript
// 統一されたエラー処理
const handleGameError = (error, context) => {
  console.error(`[${context}] ${error.message}`, error);
  
  // ユーザーフレンドリーなメッセージ
  const userMessage = translateErrorToUserMessage(error);
  
  // 画面上での通知
  showNotification({
    type: 'error',
    message: userMessage,
    duration: 5000
  });
  
  // 必要に応じて状態復旧
  if (error.recoverable) {
    attemateErrorRecovery(error, context);
  }
};
```

### 3. 品質保証

#### 自動テストの指針
```javascript
// ビジュアルリグレッションテスト
describe('Character Status Component', () => {
  it('renders correctly with full HP', () => {
    const component = render(<CharacterStatus hp={100} maxHp={100} />);
    expect(component).toMatchSnapshot();
  });
  
  it('shows critical state when HP is low', () => {
    const component = render(<CharacterStatus hp={5} maxHp={100} />);
    expect(component.getByText('危険')).toBeInTheDocument();
  });
});
```

#### デザインレビュー項目
- [ ] デザイン原則への準拠
- [ ] アクセシビリティ要件の満足
- [ ] レスポンシブ動作の確認
- [ ] パフォーマンス指標の達成
- [ ] ブラウザ間の互換性確認

---

このデザイン原則書は、test_smgプロジェクトの一貫したユーザー体験を実現するための指針です。実装時には必ずこの原則に立ち返り、ユーザーにとって最良の体験を提供することを心がけてください。