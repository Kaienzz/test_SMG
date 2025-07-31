<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ActiveBattle extends Model
{
    protected $fillable = [
        'user_id',
        'battle_id',
        'character_data',
        'monster_data',
        'battle_log',
        'turn',
        'location',
        'status',
    ];

    protected $casts = [
        'character_data' => 'array',
        'monster_data' => 'array',
        'battle_log' => 'array',
        'turn' => 'integer',
    ];

    // リレーション
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // スコープ
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // 戦闘開始
    public static function startBattle(int $userId, array $character, array $monster, string $location = null): self
    {
        // 既存のアクティブな戦闘を完了状態にする
        self::forUser($userId)->active()->update(['status' => 'completed']);

        return self::create([
            'user_id' => $userId,
            'battle_id' => Str::uuid(),
            'character_data' => $character,
            'monster_data' => $monster,
            'battle_log' => [],
            'turn' => 1,
            'location' => $location,
            'status' => 'active',
        ]);
    }


    // 戦闘ログ追加
    public function addBattleLog(string $action, string $message): void
    {
        $battleLog = $this->battle_log;
        $battleLog[] = [
            'action' => $action,
            'message' => $message,
            'turn' => $this->turn,
        ];

        $this->update(['battle_log' => $battleLog]);
    }

    // 戦闘終了
    public function endBattle(string $result): void
    {
        $this->update(['status' => 'completed']);
        
        // Note: BattleLog creation is handled by BattleStateManager::endBattleSequence()
        // to ensure all battle reward processing is completed before logging
    }

    // 経験値計算
    private function calculateExperienceGained(string $result): int
    {
        if ($result !== 'victory') {
            return 0;
        }

        $monsterLevel = $this->monster_data['level'] ?? 1;
        $baseExp = max(10, $monsterLevel * 15);
        
        // ターン数によるボーナス/ペナルティ
        $turnBonus = max(0.5, 2 - ($this->turn * 0.1));
        
        return (int) round($baseExp * $turnBonus);
    }

    // ゴールド損失計算
    private function calculateGoldLost(string $result): int
    {
        if ($result === 'victory') {
            return 0;
        }

        $characterGold = $this->character_data['gold'] ?? 0;
        
        return match($result) {
            'defeat' => (int) round($characterGold * 0.1), // 10%損失
            'escaped' => (int) round($characterGold * 0.05), // 5%損失
            default => 0,
        };
    }

    // 戦闘データを更新
    public function updateBattleData(array $battleData): void
    {
        $updates = [];
        
        if (isset($battleData['character_data'])) {
            $updates['character_data'] = $battleData['character_data'];
        }
        
        if (isset($battleData['monster_data'])) {
            $updates['monster_data'] = $battleData['monster_data'];
        }
        
        if (isset($battleData['battle_log'])) {
            $updates['battle_log'] = $battleData['battle_log'];
        }
        
        if (isset($battleData['turn'])) {
            $updates['turn'] = $battleData['turn'];
        }
        
        $this->update($updates);
    }

    // ユーザーのアクティブな戦闘を取得
    public static function getUserActiveBattle(int $userId): ?self
    {
        return self::forUser($userId)->active()->first();
    }

    // セッションから戦闘データを移行
    public static function migrateFromSession(int $userId, array $sessionBattleData): ?self
    {
        if (empty($sessionBattleData)) {
            return null;
        }

        // 既存のアクティブな戦闘を完了状態にする
        self::forUser($userId)->active()->update(['status' => 'completed']);

        return self::create([
            'user_id' => $userId,
            'battle_id' => $sessionBattleData['battle_id'] ?? Str::uuid(),
            'character_data' => $sessionBattleData['character'] ?? [],
            'monster_data' => $sessionBattleData['monster'] ?? [],
            'battle_log' => $sessionBattleData['battle_log'] ?? [],
            'turn' => $sessionBattleData['turn'] ?? 1,
            'location' => $sessionBattleData['location'] ?? null,
            'status' => 'active',
        ]);
    }
}
