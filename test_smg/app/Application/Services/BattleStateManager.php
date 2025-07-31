<?php

namespace App\Application\Services;

use App\Models\Player;
use App\Models\ActiveBattle;
use App\Services\BattleService;
use App\Application\DTOs\BattleData;
use App\Application\DTOs\BattleResult;
use App\Application\DTOs\BattleMonsterData;
use App\Application\DTOs\EncounterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 戦闘状態管理サービス
 * 
 * BattleController からビジネスロジックを抽出し、戦闘状態の変更を統一管理
 * Phase 3: Controller純化でのサービス層統合
 */
class BattleStateManager
{
    /**
     * アクティブな戦闘データを取得
     *
     * @param int $userId
     * @return array|null
     */
    public function getActiveBattleData(int $userId): ?array
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return null;
        }
        
        // monster_dataを一貫性のあるBattleMonsterDataで処理
        $monsterRawData = $activeBattle->monster_data;
        $battleMonsterData = BattleMonsterData::fromArray($monsterRawData);
        $monsterUIData = $battleMonsterData->toUIArray();
        
        return [
            'battle' => [
                'battle_id' => $activeBattle->battle_id,
                'character' => $activeBattle->character_data,
                'monster' => $monsterUIData,
                'battle_log' => $activeBattle->battle_log,
                'turn' => $activeBattle->turn,
            ],
            'character' => $activeBattle->character_data,
            'monster' => $monsterUIData,
        ];
    }

    /**
     * 戦闘を開始する
     *
     * @param Player $player
     * @param array $monster
     * @return BattleResult
     */
    public function startBattle(Player $player, array $monster): BattleResult
    {
        try {
            $user = Auth::user();
            
            \Log::info('Battle start requested', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'monster_name' => $monster['name'] ?? 'Unknown Monster',
                'user_id' => $user->id
            ]);
            
            // モンスターデータをBattleMonsterDataに変換し、データ整合性を確保
            $battleMonsterData = BattleMonsterData::fromArray($monster);
            $validatedMonster = $battleMonsterData->toBattleArray();
            
            // プレイヤーデータ検証
            $playerValidation = $this->validatePlayerData($player);
            if (!$playerValidation['valid']) {
                \Log::warning('Player data validation failed', $playerValidation);
            }
            
            // PlayerをBattleService用の配列形式に変換
            $playerArray = $this->createPlayerFromPlayer($player);
            
            \Log::debug('Player data converted for battle', [
                'player_array' => $playerArray,
                'monster' => $validatedMonster
            ]);
            
            $battleData = BattleService::startBattle($playerArray, $validatedMonster);
            
            // ActiveBattleに保存（UI表示用のネスト構造で保存）
            $activeBattle = ActiveBattle::startBattle(
                $user->id,
                $battleData['character'],
                $battleMonsterData->toUIArray(),
                $player->location_type
            );
            
            \Log::info('Battle started successfully', [
                'battle_id' => $activeBattle->battle_id,
                'player_id' => $player->id,
                'monster_name' => $battleMonsterData->name
            ]);
            
            return BattleResult::battleStart(
                $activeBattle->battle_id,
                $battleData['character'],
                $battleMonsterData->toUIArray(),
                "{$battleMonsterData->name}が現れた！"
            );
        } catch (\Exception $e) {
            \Log::error('Battle start failed', [
                'player_id' => $player->id ?? null,
                'monster' => $monster,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return BattleResult::failure('戦闘開始に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 攻撃処理
     *
     * @param int $userId
     * @return BattleResult
     */
    public function processAttack(int $userId): BattleResult
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return BattleResult::failure('戦闘データが見つかりません');
        }
        
        $character = $activeBattle->character_data;
        
        // モンスターデータをBattleMonsterDataで統一処理
        $battleMonsterData = BattleMonsterData::fromArray($activeBattle->monster_data);
        $monster = $battleMonsterData->toBattleArray();
        $battleLog = $activeBattle->battle_log;
        
        // プレイヤーの攻撃
        $attackResult = BattleService::calculateAttack($character, $monster);
        $monster = BattleService::applyDamage($monster, $attackResult['damage']);
        
        // 更新されたモンスターデータでBattleMonsterDataを再作成
        $updatedBattleMonsterData = BattleMonsterData::fromArray($monster)->withUpdatedHp($monster['hp']);
        
        $battleLog[] = [
            'action' => 'player_attack',
            'message' => $attackResult['hit'] ? 
                "{$character['name']}の攻撃！ {$updatedBattleMonsterData->name}に{$attackResult['damage']}のダメージ！" . 
                ($attackResult['critical'] ? ' ' . $attackResult['message'] : '') :
                $attackResult['message']
        ];
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // モンスターの行動
        $monsterAction = BattleService::getMonsterAction($monster, $character);
        
        if ($monsterAction === 'attack') {
            $result = $this->processMonsterAttack($character, $monster, $battleLog);
            $character = $result['character'];
            $monster = $result['monster'];
            $battleLog = $result['battleLog'];
            $updatedBattleMonsterData = BattleMonsterData::fromArray($monster);
        }
        
        // 戦闘終了判定（モンスターの攻撃後）
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // 戦闘データをDB更新（UI表示用ネスト構造で保存）
        $activeBattle->updateBattleData([
            'character_data' => $character,
            'monster_data' => $updatedBattleMonsterData->toUIArray(),
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1
        ]);
        
        return BattleResult::success(
            $character,
            $updatedBattleMonsterData->toUIArray(),
            $battleLog,
            $activeBattle->turn + 1
        );
    }

    /**
     * 防御処理
     *
     * @param int $userId
     * @return BattleResult
     */
    public function processDefense(int $userId): BattleResult
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return BattleResult::failure('戦闘データが見つかりません');
        }
        
        $character = $activeBattle->character_data;
        
        // モンスターデータをBattleMonsterDataで統一処理
        $battleMonsterData = BattleMonsterData::fromArray($activeBattle->monster_data);
        $monster = $battleMonsterData->toBattleArray();
        $battleLog = $activeBattle->battle_log;
        
        // プレイヤーの防御
        $defenseResult = BattleService::calculateDefense($character);
        $battleLog[] = [
            'action' => 'player_defend',
            'message' => $defenseResult['message']
        ];
        
        // モンスターの攻撃（ダメージ軽減）
        $monsterAttack = BattleService::calculateAttack($monster, $character);
        $reducedDamage = (int) round($monsterAttack['damage'] * (1 - $defenseResult['defense_bonus']));
        $character = BattleService::applyDamage($character, $reducedDamage);
        
        $battleLog[] = [
            'action' => 'monster_attack',
            'message' => $monsterAttack['hit'] ? 
                "{$battleMonsterData->name}の攻撃！ {$character['name']}に{$reducedDamage}のダメージ！" . 
                ($reducedDamage < $monsterAttack['damage'] ? ' (防御によりダメージ軽減)' : '') :
                $monsterAttack['message']
        ];
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // 戦闘データをDB更新（UI表示用ネスト構造で保存）
        $activeBattle->updateBattleData([
            'character_data' => $character,
            'monster_data' => $battleMonsterData->toUIArray(),
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1
        ]);
        
        return BattleResult::success(
            $character,
            $battleMonsterData->toUIArray(),
            $battleLog,
            $activeBattle->turn + 1
        );
    }

    /**
     * 逃走処理
     *
     * @param int $userId
     * @return BattleResult
     */
    public function processEscape(int $userId): BattleResult
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return BattleResult::failure('戦闘データが見つかりません');
        }
        
        $character = $activeBattle->character_data;
        
        // モンスターデータをBattleMonsterDataで統一処理
        $battleMonsterData = BattleMonsterData::fromArray($activeBattle->monster_data);
        $monster = $battleMonsterData->toBattleArray();
        $battleLog = $activeBattle->battle_log;
        
        // 逃走判定
        $escapeResult = BattleService::calculateEscape($character, $monster);
        
        if ($escapeResult['success']) {
            $battleLog[] = [
                'action' => 'escape_success',
                'message' => $escapeResult['message']
            ];
            
            $activeBattle->endBattle('escape');
            $this->updatePlayerFromBattle($userId, $character);
            
            return BattleResult::success(
                $character,
                $battleMonsterData->toUIArray(),
                $battleLog,
                1,
                true,
                $escapeResult['message'],
                'escape'
            );
        }
        
        $battleLog[] = [
            'action' => 'escape_failed',
            'message' => $escapeResult['message']
        ];
        
        // 逃走失敗時はモンスターの攻撃
        $result = $this->processMonsterAttack($character, $monster, $battleLog);
        $character = $result['character'];
        $monster = $result['monster'];
        $battleLog = $result['battleLog'];
        
        // 更新されたモンスターデータでBattleMonsterDataを再作成
        $updatedBattleMonsterData = BattleMonsterData::fromArray($monster);
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // 戦闘データをDB更新（UI表示用ネスト構造で保存）
        $activeBattle->updateBattleData([
            'character_data' => $character,
            'monster_data' => $updatedBattleMonsterData->toUIArray(),
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1
        ]);
        
        return BattleResult::success(
            $character,
            $updatedBattleMonsterData->toUIArray(),
            $battleLog,
            $activeBattle->turn + 1
        );
    }

    /**
     * スキル使用処理
     *
     * @param int $userId
     * @param Request $request
     * @return BattleResult
     */
    public function processSkillUse(int $userId, Request $request): BattleResult
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            return BattleResult::failure('戦闘データが見つかりません');
        }
        
        $skillId = $request->input('skill_id');
        $character = $activeBattle->character_data;
        
        // モンスターデータをBattleMonsterDataで統一処理
        $battleMonsterData = BattleMonsterData::fromArray($activeBattle->monster_data);
        $monster = $battleMonsterData->toBattleArray();
        $battleLog = $activeBattle->battle_log;
        
        // スキル使用処理
        $skillResult = BattleService::useSkill($character, $monster, $skillId);
        
        if (!$skillResult['success']) {
            return BattleResult::failure($skillResult['message']);
        }
        
        $character = $skillResult['character'];
        $monster = $skillResult['monster'];
        
        // 更新されたモンスターデータでBattleMonsterDataを再作成
        $updatedBattleMonsterData = BattleMonsterData::fromArray($monster);
        
        $battleLog[] = [
            'action' => 'skill_use',
            'message' => $skillResult['message']
        ];
        
        // 戦闘終了判定
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // モンスターの行動
        $monsterAction = BattleService::getMonsterAction($monster, $character);
        
        if ($monsterAction === 'attack') {
            $result = $this->processMonsterAttack($character, $monster, $battleLog);
            $character = $result['character'];
            $monster = $result['monster'];
            $battleLog = $result['battleLog'];
            $updatedBattleMonsterData = BattleMonsterData::fromArray($monster);
        }
        
        // 戦闘終了判定（モンスターの攻撃後）
        if (BattleService::isBattleEnd($character, $monster)) {
            return $this->endBattleSequence($activeBattle, $character, $monster, $battleLog, $userId);
        }
        
        // 戦闘データをDB更新（UI表示用ネスト構造で保存）
        $activeBattle->updateBattleData([
            'character_data' => $character,
            'monster_data' => $updatedBattleMonsterData->toUIArray(),
            'battle_log' => $battleLog,
            'turn' => $activeBattle->turn + 1
        ]);
        
        return BattleResult::success(
            $character,
            $updatedBattleMonsterData->toUIArray(),
            $battleLog,
            $activeBattle->turn + 1
        );
    }

    /**
     * 戦闘強制終了処理
     *
     * @param int $userId
     * @return BattleResult
     */
    public function forceBattleEnd(int $userId): BattleResult
    {
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        
        if (!$activeBattle) {
            // 戦闘データが見つからない場合は、既に終了済みとして成功を返す
            \Log::info('Force battle end called but no active battle found', [
                'user_id' => $userId,
                'message' => 'Battle may have already ended naturally'
            ]);
            
            return BattleResult::success(
                [],
                [],
                [],
                0,
                true,
                '戦闘は既に終了しています'
            );
        }
        
        $activeBattle->endBattle('forced_end');
        
        return BattleResult::success(
            [],
            [],
            [],
            0,
            true,
            '戦闘を終了しました'
        );
    }

    /**
     * セッションデータをデータベースに移行
     *
     * @param int $userId
     * @return void
     */
    public function migrateBattleSessionToDatabase(int $userId): void
    {
        $sessionKey = "battle_data_{$userId}";
        
        if (session()->has($sessionKey)) {
            $sessionBattleData = session($sessionKey);
            
            // セッションデータをActiveBattleに移行する処理
            // （実装は必要に応じて）
            
            session()->forget($sessionKey);
        }
    }

    /**
     * Player を BattleService用の配列形式に変換
     *
     * @param Player $player
     * @return array
     */
    private function createPlayerFromPlayer(Player $player): array
    {
        try {
            // スキルリストを安全に取得
            $skills = [];
            try {
                // スキルリレーションを事前にロード
                if (!$player->relationLoaded('skills')) {
                    $player->load('skills');
                }
                $skills = $player->getSkillList();
            } catch (\Exception $e) {
                \Log::warning('Failed to get skill list for player', [
                    'player_id' => $player->id,
                    'error' => $e->getMessage()
                ]);
                
                // フォールバック: 直接SQLでスキル取得
                try {
                    $rawSkills = \DB::table('skills')
                        ->where('player_id', $player->id)
                        ->where('is_active', true)
                        ->select(['id', 'skill_name', 'skill_type', 'level', 'sp_cost', 'effects'])
                        ->get();
                    
                    $skills = $rawSkills->map(function($skill) {
                        return [
                            'id' => $skill->id,
                            'skill_name' => $skill->skill_name,
                            'skill_type' => $skill->skill_type,
                            'level' => $skill->level,
                            'sp_cost' => $skill->sp_cost,
                            'effects' => json_decode($skill->effects, true) ?? [],
                            'is_active' => true,
                        ];
                    })->toArray();
                } catch (\Exception $fallbackE) {
                    \Log::error('Fallback skill fetch also failed', [
                        'player_id' => $player->id,
                        'error' => $fallbackE->getMessage()
                    ]);
                    $skills = [];
                }
            }
            
            $playerData = [
                'name' => $player->name ?? 'プレイヤー',
                'hp' => $player->hp ?? 100,
                'max_hp' => $player->max_hp ?? 100,
                'sp' => $player->sp ?? 50,
                'max_sp' => $player->max_sp ?? 50,
                'mp' => $player->mp ?? 30,
                'max_mp' => $player->max_mp ?? 30,
                'attack' => $player->attack ?? 10,
                'defense' => $player->defense ?? 5,
                'agility' => $player->agility ?? 8,
                'evasion' => $player->evasion ?? 10,
                'accuracy' => $player->accuracy ?? 80,
                'magic_attack' => $player->magic_attack ?? 8,
                'gold' => $player->gold ?? 500,
                'level' => $player->level ?? 1,
                'experience' => $player->experience ?? 0,
                'skills' => $skills,
            ];
            
            \Log::debug('Player converted to battle array', [
                'player_id' => $player->id,
                'skills_count' => count($skills),
                'player_stats' => [
                    'hp' => $playerData['hp'],
                    'attack' => $playerData['attack'],
                    'defense' => $playerData['defense']
                ]
            ]);
            
            return $playerData;
        } catch (\Exception $e) {
            \Log::error('Failed to convert player to battle array', [
                'player_id' => $player->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // フォールバック用の最小限データ
            return [
                'name' => $player->name ?? 'プレイヤー',
                'hp' => $player->hp ?? 100,
                'max_hp' => $player->max_hp ?? 100,
                'sp' => $player->sp ?? 50,
                'max_sp' => $player->max_sp ?? 50,
                'mp' => $player->mp ?? 30,
                'max_mp' => $player->max_mp ?? 30,
                'attack' => $player->attack ?? 10,
                'defense' => $player->defense ?? 5,
                'agility' => $player->agility ?? 8,
                'evasion' => $player->evasion ?? 10,
                'accuracy' => $player->accuracy ?? 80,
                'magic_attack' => $player->magic_attack ?? 8,
                'gold' => $player->gold ?? 500,
                'level' => $player->level ?? 1,
                'experience' => $player->experience ?? 0,
                'skills' => [],
            ];
        }
    }

    /**
     * モンスターデータの検証と自動修正
     *
     * @param array $monster
     * @return array
     */
    private function validateMonsterData(array $monster): array
    {
        $requiredFields = [
            'name' => 'Unknown Monster',
            'emoji' => '👹',
            'level' => 1,
            'description' => '',
        ];

        $requiredStats = [
            'hp' => 100,
            'max_hp' => 100,
            'attack' => 15,
            'defense' => 10,
            'agility' => 10,
            'evasion' => 10,
            'accuracy' => 80,
            'experience_reward' => 0,
        ];

        $missingFields = [];

        // 基本フィールドチェック
        foreach ($requiredFields as $field => $defaultValue) {
            if (!isset($monster[$field]) || $monster[$field] === null) {
                $monster[$field] = $defaultValue;
                $missingFields[] = $field;
            }
        }

        // statsフィールドチェック
        if (!isset($monster['stats']) || !is_array($monster['stats'])) {
            $monster['stats'] = [];
        }

        foreach ($requiredStats as $stat => $defaultValue) {
            if (!isset($monster['stats'][$stat]) || $monster['stats'][$stat] === null) {
                $monster['stats'][$stat] = $defaultValue;
                $missingFields[] = "stats.{$stat}";
            }
        }

        // 特別なケース: descriptionが空の場合
        if (empty($monster['description'])) {
            $monster['description'] = "レベル{$monster['level']}の{$monster['name']}";
        }

        // 整合性チェック
        if ($monster['stats']['hp'] > $monster['stats']['max_hp']) {
            $monster['stats']['hp'] = $monster['stats']['max_hp'];
        }

        if (!empty($missingFields)) {
            \Log::warning('Monster data validation: Fixed missing fields', [
                'monster_name' => $monster['name'],
                'missing_fields' => $missingFields,
                'fixed_monster' => $monster
            ]);
        }

        return $monster;
    }

    /**
     * プレイヤーデータの検証
     *
     * @param Player $player
     * @return array
     */
    private function validatePlayerData(Player $player): array
    {
        $errors = [];
        $warnings = [];

        // 必須フィールドチェック
        if (empty($player->name)) {
            $errors[] = 'Player name is empty';
        }

        // 数値フィールドチェック
        $numericFields = [
            'hp' => ['min' => 0, 'max' => $player->max_hp ?? 9999],
            'max_hp' => ['min' => 1, 'max' => 9999],
            'sp' => ['min' => 0, 'max' => $player->max_sp ?? 9999],
            'max_sp' => ['min' => 1, 'max' => 9999],
            'mp' => ['min' => 0, 'max' => $player->max_mp ?? 9999],
            'max_mp' => ['min' => 1, 'max' => 9999],
            'attack' => ['min' => 1, 'max' => 9999],
            'defense' => ['min' => 1, 'max' => 9999],
            'agility' => ['min' => 1, 'max' => 9999],
            'level' => ['min' => 1, 'max' => 999],
        ];

        foreach ($numericFields as $field => $range) {
            $value = $player->$field ?? 0;
            if ($value < $range['min'] || $value > $range['max']) {
                $errors[] = "Player {$field} ({$value}) is out of valid range [{$range['min']}, {$range['max']}]";
            }
        }

        // 整合性チェック
        if (($player->hp ?? 0) > ($player->max_hp ?? 0)) {
            $warnings[] = 'Player HP exceeds max HP';
        }
        if (($player->sp ?? 0) > ($player->max_sp ?? 0)) {
            $warnings[] = 'Player SP exceeds max SP';
        }
        if (($player->mp ?? 0) > ($player->max_mp ?? 0)) {
            $warnings[] = 'Player MP exceeds max MP';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'player_id' => $player->id,
            'player_name' => $player->name,
        ];
    }

    /**
     * 戦闘結果をプレイヤーに反映
     *
     * @param int $userId
     * @param array $playerData
     * @return void
     */
    private function updatePlayerFromBattle(int $userId, array $playerData): void
    {
        $user = Auth::user();
        if ($user && $user->id === $userId) {
            $player = $user->player;
            
            if ($player) {
                $player->update([
                    'hp' => $playerData['hp'],
                    'sp' => $playerData['sp'],
                    'experience' => $playerData['experience'] ?? $player->experience,
                    'gold' => $playerData['gold'] ?? $player->gold,
                ]);
            }
        }
    }

    /**
     * モンスターの攻撃処理
     *
     * @param array $character
     * @param array $monster
     * @param array $battleLog
     * @return array
     */
    private function processMonsterAttack(array $character, array $monster, array $battleLog): array
    {
        $monsterAttack = BattleService::calculateAttack($monster, $character);
        $character = BattleService::applyDamage($character, $monsterAttack['damage']);
        
        $battleLog[] = [
            'action' => 'monster_attack',
            'message' => $monsterAttack['hit'] ? 
                "{$monster['name']}の攻撃！ {$character['name']}に{$monsterAttack['damage']}のダメージ！" . 
                ($monsterAttack['critical'] ? ' ' . $monsterAttack['message'] : '') :
                $monsterAttack['message']
        ];
        
        return [
            'character' => $character,
            'monster' => $monster,
            'battleLog' => $battleLog
        ];
    }

    /**
     * 戦闘終了シーケンス
     *
     * @param ActiveBattle $activeBattle
     * @param array $character
     * @param array $monster
     * @param array $battleLog
     * @param int $userId
     * @return BattleResult
     */
    private function endBattleSequence(ActiveBattle $activeBattle, array $character, array $monster, array $battleLog, int $userId): BattleResult
    {
        $result = $monster['hp'] <= 0 ? 'victory' : ($character['hp'] <= 0 ? 'defeat' : 'draw');
        $battleResult = BattleService::processBattleResult($character, $monster, $result);
        
        $battleLog[] = [
            'action' => 'battle_end',
            'message' => $battleResult['message']
        ];
        
        $activeBattle->endBattle($result);
        
        // 戦闘結果に応じたプレイヤー更新
        $updatedCharacter = $character;
        
        if ($result === 'defeat' && isset($battleResult['updated_character'])) {
            // 敗北時：processDefeat()で更新されたキャラクターデータを使用
            $updatedCharacter = $battleResult['updated_character'];
            
            // Player モデルを完全に更新（gold, hp, location）
            $player = \App\Models\Player::where('user_id', $userId)->first();
            if ($player) {
                // キャラクターデータの更新
                $player->hp = $updatedCharacter['hp']; // HP = 1
                $player->gold = $updatedCharacter['gold']; // ゴールド減少
                
                // 強制ワープの実行
                $teleportLocation = $battleResult['teleport_location'] ?? 'town_a';
                $player->location_type = 'town';
                $player->location_id = $teleportLocation;
                $player->game_position = 0; // 町の最初の位置
                $player->last_visited_town = $teleportLocation; // 最新訪問町を更新
                
                $player->save();
                
                \Log::info('Player defeated and teleported', [
                    'user_id' => $userId,
                    'old_location' => $player->getOriginal('location_type') . '_' . $player->getOriginal('location_id'),
                    'new_location' => $player->location_type . '_' . $player->location_id,
                    'gold_lost' => $battleResult['gold_lost'] ?? 0,
                    'remaining_gold' => $player->gold
                ]);
            }
        } else {
            // 勝利・逃走時：通常の更新
            $this->updatePlayerFromBattle($userId, $updatedCharacter);
        }
        
        // UI表示用にモンスターデータをネスト構造に変換
        $monsterUIData = BattleMonsterData::fromArray($monster)->toUIArray();
        
        // 戦闘結果に応じた処理
        $experienceGained = $battleResult['experience_gained'] ?? 0;
        $goldGained = $battleResult['gold_gained'] ?? 0;
        $finalMessage = $battleResult['message'];
        
        if ($result === 'victory') {
            // 勝利時の報酬処理
            $player = \App\Models\Player::where('user_id', $userId)->first();
            if ($player) {
                // 経験値付与
                if ($experienceGained > 0) {
                    $experienceResult = $player->gainExperience($experienceGained);
                    if ($experienceResult['leveled_up']) {
                        $finalMessage .= " 経験値を {$experienceGained} 獲得しました！レベルアップ！Lv.{$experienceResult['new_level']}になりました！";
                    } else {
                        $finalMessage .= " 経験値を {$experienceGained} 獲得しました！";
                    }
                }
                
                // ゴールド付与
                if ($goldGained > 0) {
                    $player->gold += $goldGained;
                    $player->save();
                    $finalMessage .= " {$goldGained}G を獲得しました！";
                }
            }
        } elseif ($result === 'defeat') {
            // 敗北時のメッセージ更新
            $goldLost = $battleResult['gold_lost'] ?? 0;
            $teleportMessage = $battleResult['teleport_message'] ?? '';
            if ($goldLost > 0) {
                $finalMessage .= " {$goldLost}G を失いました。{$teleportMessage}";
            } else {
                $finalMessage .= " {$teleportMessage}";
            }
        }
        
        // 戦闘ログの作成
        $player = \App\Models\Player::where('user_id', $userId)->first();
        $location = $player ? $player->location_type . '_' . $player->location_id : 'unknown';
        
        \App\Models\BattleLog::create([
            'user_id' => $userId,
            'monster_name' => $monster['name'] ?? 'Unknown Monster',
            'location' => $location,
            'result' => $result,
            'experience_gained' => $experienceGained,
            'gold_lost' => $result === 'defeat' ? ($battleResult['gold_lost'] ?? 0) : 0,
            'turns' => $activeBattle->turn ?? 1,
            'battle_data' => [
                'monster_level' => $monster['level'] ?? 1,
                'character_level' => $updatedCharacter['level'] ?? 1,
                'gold_gained' => $result === 'victory' ? $goldGained : 0,
                'gold_lost' => $result === 'defeat' ? ($battleResult['gold_lost'] ?? 0) : 0,
                'teleport_location' => $result === 'defeat' ? ($battleResult['teleport_location'] ?? null) : null,
                'penalty_percent' => $result === 'defeat' ? ($battleResult['penalty_percent'] ?? null) : null,
                'battle_log' => $battleLog,
                'ended_at' => now()->toDateTimeString()
            ]
        ]);
        
        // 戦闘終了時に位置関連のセッションデータをクリア
        // ゲーム画面に戻った時にDBから最新データを確実に取得するため
        session()->forget([
            'location_type',
            'location_id', 
            'game_position',
            'character_gold',
            'player_gold',
            'player_sp',
            'last_visited_town' // 敗北時のワープも含めて全てDBベースにする
        ]);
        
        return BattleResult::success(
            $updatedCharacter,
            $monsterUIData,
            $battleLog,
            1,
            true,
            $finalMessage,
            $result
        );
    }

    /**
     * 戦闘終了処理（戦闘後のクリーンアップ）
     *
     * @param int $userId
     * @return BattleResult
     */
    public function endBattle(int $userId): BattleResult
    {
        // アクティブバトルの削除
        $activeBattle = ActiveBattle::getUserActiveBattle($userId);
        if ($activeBattle) {
            $activeBattle->delete();
        }
        
        // プレイヤーの最新状態を取得
        $player = \App\Models\Player::where('user_id', $userId)->first();
        
        if (!$player) {
            return BattleResult::failure('プレイヤーデータが見つかりません');
        }
        
        \Log::info('Battle cleanup completed', [
            'user_id' => $userId,
            'player_location' => $player->location_type . '_' . $player->location_id,
            'player_hp' => $player->hp,
            'player_gold' => $player->gold
        ]);
        
        return BattleResult::success(
            [],
            [],
            [],
            0,
            true,
            '戦闘が終了しました',
            'ended'
        );
    }
}