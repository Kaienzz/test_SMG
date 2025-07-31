# Tasks 27JUL2025 #4 - モンスターHP0時戦闘終了エラー修正

## 問題概要

モンスターのHPが0になる（勝利時）に「戦闘中にエラーが発生しました」が表示され、戦闘が正常終了しない重大なバグが発生。

## エラー詳細分析

### 発生エラー
```
[2025-07-27 04:07:43] local.ERROR: Undefined array key "hp" 
{"userId":1,"exception":"[object] (ErrorException(code: 0): Undefined array key \"hp\" 
at /workspaces/test_SMG/test_smg/app/Application/Services/BattleStateManager.php:731)
```

### 根本原因
Task 21でBattleMonsterData統合時に生じたデータ構造の不整合：

1. **BattleStateManager::endBattleSequence()** (Line 731)
   - `$monster['hp']` にアクセス（フラット構造を期待）
   - しかし実際にはUI表示用のネスト構造 `$monster['stats']['hp']` が渡される

2. **BattleService::isBattleEnd()** (Line 268)
   - `$monster['hp']` にアクセス（フラット構造を期待）
   - しかし戦闘処理中でネスト構造が混在

3. **BattleService::getMonsterAction()** (Line 279)
   - `$monster['hp'] / $monster['max_hp']` 計算（フラット構造を期待）

### データフロー問題
```
BattleStateManager::processAttack()
├─ BattleMonsterData::fromArray() → フラット構造生成
├─ BattleService::calculateAttack() → フラット構造で処理
├─ BattleService::isBattleEnd() → ❌ フラット構造期待だがネスト構造渡し
└─ endBattleSequence() → ❌ フラット構造期待だがネスト構造渡し
```

## 修正タスク

### Task 22: BattleService フラット構造対応 [高優先度]
**目的**: BattleServiceの全メソッドでフラット構造データを確実に受け取る

**修正対象**:
- `BattleStateManager::processAttack()` - `isBattleEnd()` 呼び出し部分
- `BattleStateManager::processDefense()` - `isBattleEnd()` 呼び出し部分  
- `BattleStateManager::processEscape()` - `isBattleEnd()` 呼び出し部分
- `BattleStateManager::processSkillUse()` - `isBattleEnd()` 呼び出し部分

**具体的修正**:
```php
// 修正前（現在の問題箇所）
if (BattleService::isBattleEnd($character, $updatedBattleMonsterData->toUIArray())) {

// 修正後
if (BattleService::isBattleEnd($character, $monster)) { // フラット構造を渡す
```

### Task 23: BattleStateManager::endBattleSequence データ構造統一 [高優先度]
**目的**: 戦闘終了シーケンスでの一貫したデータ処理

**修正内容**:
1. `endBattleSequence()` メソッドシグネチャ変更
2. フラット構造での戦闘結果判定実装
3. 呼び出し元でのデータ形式統一

**具体的修正**:
```php
// 現在の問題箇所 (Line 731)
$result = $monster['hp'] <= 0 ? 'victory' : ($character['hp'] <= 0 ? 'defeat' : 'draw');

// 修正後：BattleMonsterDataを受け取ってフラット構造で判定
private function endBattleSequence(ActiveBattle $activeBattle, array $character, BattleMonsterData $battleMonsterData, array $battleLog, int $userId): array
{
    $monster = $battleMonsterData->toBattleArray();
    $result = $monster['hp'] <= 0 ? 'victory' : ($character['hp'] <= 0 ? 'defeat' : 'draw');
    // ...
}
```

### Task 24: モンスター行動AI修正 [中優先度]
**目的**: BattleService::getMonsterAction()でのHP参照エラー防止

**修正内容**:
- AI判定でフラット構造データを確実に受け取る
- HP比率計算の安全性向上

### Task 25: 戦闘終了フロー統合テスト [中優先度]
**目的**: 修正後の戦闘終了が全シナリオで正常動作するか検証

**テストシナリオ**:
1. プレイヤー勝利（モンスターHP0）
2. プレイヤー敗北（プレイヤーHP0）
3. 逃走成功
4. 逃走失敗

### Task 26: データ構造ドキュメント整備 [低優先度]
**目的**: 今後同様の問題を防ぐためのガイドライン作成

**内容**:
- BattleService入力形式の明確化
- BattleStateManagerデータフロー図作成

## 緊急度評価

**Critical**: 戦闘システムの中核機能が完全に破綻
- ゲームプレイ不可能
- 勝利条件が満たせない
- ユーザー体験が著しく損なわれる

## 修正優先順位

1. **Task 22** - BattleService フラット構造対応（即時）
2. **Task 23** - endBattleSequence データ構造統一（即時）
3. **Task 24** - モンスター行動AI修正（即時）
4. **Task 25** - 統合テスト（修正後）
5. **Task 26** - ドキュメント整備（後日）

## 影響範囲

**直接影響**:
- 全ての戦闘終了処理
- モンスター勝利フロー
- 経験値獲得処理

**間接影響**:
- プレイヤー進行システム
- ゲームバランス
- ユーザー満足度

## 検証方法

修正後に以下を確認：
1. モンスターのHPを0にして勝利できるか
2. 戦闘ログが正常に表示されるか
3. 経験値が正常に獲得できるか
4. ゲーム画面に正常に戻れるか