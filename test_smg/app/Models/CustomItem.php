<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomItem extends Model
{
    protected $fillable = [
        'base_item_id', 
        'creator_id', 
        'custom_stats', 
        'base_stats', 
        'material_bonuses', 
        'base_durability', 
        'max_durability', 
        'is_masterwork'
    ];
    
    protected $casts = [
        'custom_stats' => 'array',
        'base_stats' => 'array', 
        'material_bonuses' => 'array',
        'base_durability' => 'integer',
        'max_durability' => 'integer',
        'is_masterwork' => 'boolean'
    ];

    /**
     * ベースアイテムの情報取得
     */
    public function baseItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'base_item_id');
    }

    /**
     * 生産者（錬金実施者）
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'creator_id');
    }

    /**
     * ベースアイテムの情報取得
     */
    public function getBaseItem(): Item
    {
        return $this->baseItem;
    }

    /**
     * 最終ステータス計算（ベース + カスタム効果）
     */
    public function getFinalStats(): array
    {
        $baseStats = $this->base_stats;
        $customStats = $this->custom_stats;
        
        $finalStats = [];
        
        // ベースステータスを基準として、カスタムステータスで上書き
        foreach ($baseStats as $stat => $value) {
            $finalStats[$stat] = $customStats[$stat] ?? $value;
        }
        
        // カスタムステータスに追加のステータスがある場合も追加
        foreach ($customStats as $stat => $value) {
            if (!isset($finalStats[$stat])) {
                $finalStats[$stat] = $value;
            }
        }
        
        return $finalStats;
    }

    /**
     * 耐久度マスター情報取得（個別耐久度はインベントリで管理）
     */
    public function getDurabilityInfo(): array
    {
        return [
            'base_durability' => $this->base_durability,
            'max_durability' => $this->max_durability,
            'durability_note' => '個別の耐久度はインベントリシステムで管理されます',
        ];
    }

    /**
     * アイテム情報取得（既存システム互換）
     */
    public function getItemInfo(): array
    {
        $baseItem = $this->getBaseItem();
        $finalStats = $this->getFinalStats();
        $durabilityInfo = $this->getDurabilityInfo();
        
        return [
            'id' => $this->id,
            'name' => $baseItem->name,
            'description' => $baseItem->description,
            'category' => $baseItem->category->value,
            'category_name' => $baseItem->getCategoryName(),
            'is_custom' => true,
            'custom_item_id' => $this->id,
            'base_item_id' => $this->base_item_id,
            'creator_id' => $this->creator_id,
            'creator_name' => $this->creator->name ?? 'Unknown',
            'effects' => $finalStats,
            'custom_stats' => $this->custom_stats,
            'base_stats' => $this->base_stats,
            'material_bonuses' => $this->material_bonuses,
            'max_durability' => $this->max_durability,
            'base_durability' => $this->base_durability,
            'durability_info' => $durabilityInfo,
            'is_masterwork' => $this->is_masterwork,
            'is_equippable' => $this->isEquippable(),
            'is_usable' => false, // カスタムアイテムは基本的に使用不可
            'has_durability' => true,
            'has_stack_limit' => false, // カスタムアイテムはスタック不可
        ];
    }

    /**
     * 装備可能かチェック
     */
    public function isEquippable(): bool
    {
        return $this->getBaseItem()->isEquippable();
    }

    /**
     * 錬金可能かチェック（常にfalse：カスタムアイテムは再錬金不可）
     */
    public function canBeAlchemized(): bool
    {
        return false;
    }

    /**
     * 耐久度管理はインベントリシステムで実行
     * マスターデータとしては耐久度操作は行わない
     */
    public function consumeDurability(int $amount): bool
    {
        // マスターデータでは個別耐久度を管理しないため、
        // 実際の耐久度操作はインベントリシステムで実行
        return false;
    }

    /**
     * 耐久度修理もインベントリシステムで実行
     */
    public function repairDurability(int $amount): void
    {
        // マスターデータでは個別耐久度を管理しないため、
        // 実際の耐久度操作はインベントリシステムで実行
    }

    /**
     * 使用可能性はインベントリ内の個別アイテムで判定
     */
    public function isUsable(): bool
    {
        // マスターデータとしては基本的に使用不可（装備のみ）
        return false;
    }

    /**
     * 破損状態もインベントリ内の個別アイテムで判定
     */
    public function isBroken(): bool
    {
        // マスターデータでは破損状態を持たない
        return false;
    }
}
