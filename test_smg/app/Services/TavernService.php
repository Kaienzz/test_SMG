<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\Player;
use App\Enums\ShopType;

class TavernService extends AbstractShopService
{
    // 回復料金の基本レート（後で調整可能）
    private const HP_RATE = 10; // 1HP = 10G
    private const MP_RATE = 15; // 1MP = 15G
    private const SP_RATE = 5;  // 1SP = 5G

    public function __construct()
    {
        parent::__construct(ShopType::TAVERN);
    }

    public function getAvailableServices(Shop $shop): array
    {
        return [
            'heal_hp' => [
                'name' => 'HP回復',
                'description' => 'HPを回復します',
                'rate' => self::HP_RATE,
                'unit' => 'G/HP',
            ],
            'heal_mp' => [
                'name' => 'MP回復',
                'description' => 'MPを回復します',
                'rate' => self::MP_RATE,
                'unit' => 'G/MP',
            ],
            'heal_sp' => [
                'name' => 'SP回復',
                'description' => 'SPを回復します',
                'rate' => self::SP_RATE,
                'unit' => 'G/SP',
            ],
            'heal_all' => [
                'name' => '全回復',
                'description' => 'HP、MP、SPを全回復します',
                'rate' => 'variable',
                'unit' => 'G',
            ],
        ];
    }

    public function processTransaction(Shop $shop, Player $player, array $data): array
    {
        if (!$this->validateTransactionData($data)) {
            return $this->createErrorResponse('無効なリクエストです。');
        }

        $serviceType = $data['service_type'];
        $amount = $data['amount'] ?? null;

        switch ($serviceType) {
            case 'heal_hp':
                return $this->healHP($player, $amount);
            case 'heal_mp':
                return $this->healMP($player, $amount);
            case 'heal_sp':
                return $this->healSP($player, $amount);
            case 'heal_all':
                return $this->healAll($player);
            default:
                return $this->createErrorResponse('不明なサービスです。');
        }
    }

    public function validateTransactionData(array $data): bool
    {
        return isset($data['service_type']) && 
               in_array($data['service_type'], ['heal_hp', 'heal_mp', 'heal_sp', 'heal_all']);
    }

    private function healHP(Player $player, ?int $amount): array
    {
        $maxHP = $player->max_hp;
        $currentHP = $player->hp;
        $missingHP = $maxHP - $currentHP;

        if ($missingHP <= 0) {
            return $this->createErrorResponse('HPは既に満タンです。');
        }

        $healAmount = $amount ? min($amount, $missingHP) : $missingHP;
        $cost = $healAmount * self::HP_RATE;

        if ($player->gold < $cost) {
            return $this->createErrorResponse("お金が足りません。必要金額: {$cost}G");
        }

        $player->hp += $healAmount;
        $player->gold -= $cost;
        $player->save();

        return $this->createSuccessResponse(
            "HPを{$healAmount}回復しました。",
            [
                'healed_hp' => $healAmount,
                'cost' => $cost,
                'current_hp' => $player->hp,
                'current_gold' => $player->gold,
            ]
        );
    }

    private function healMP(Player $player, ?int $amount): array
    {
        $maxMP = $player->max_mp;
        $currentMP = $player->mp;
        $missingMP = $maxMP - $currentMP;

        if ($missingMP <= 0) {
            return $this->createErrorResponse('MPは既に満タンです。');
        }

        $healAmount = $amount ? min($amount, $missingMP) : $missingMP;
        $cost = $healAmount * self::MP_RATE;

        if ($player->gold < $cost) {
            return $this->createErrorResponse("お金が足りません。必要金額: {$cost}G");
        }

        $player->mp += $healAmount;
        $player->gold -= $cost;
        $player->save();

        return $this->createSuccessResponse(
            "MPを{$healAmount}回復しました。",
            [
                'healed_mp' => $healAmount,
                'cost' => $cost,
                'current_mp' => $player->mp,
                'current_gold' => $player->gold,
            ]
        );
    }

    private function healSP(Player $player, ?int $amount): array
    {
        $maxSP = $player->max_sp;
        $currentSP = $player->sp;
        $missingSP = $maxSP - $currentSP;

        if ($missingSP <= 0) {
            return $this->createErrorResponse('SPは既に満タンです。');
        }

        $healAmount = $amount ? min($amount, $missingSP) : $missingSP;
        $cost = $healAmount * self::SP_RATE;

        if ($player->gold < $cost) {
            return $this->createErrorResponse("お金が足りません。必要金額: {$cost}G");
        }

        $player->sp += $healAmount;
        $player->gold -= $cost;
        $player->save();

        return $this->createSuccessResponse(
            "SPを{$healAmount}回復しました。",
            [
                'healed_sp' => $healAmount,
                'cost' => $cost,
                'current_sp' => $player->sp,
                'current_gold' => $player->gold,
            ]
        );
    }

    private function healAll(Player $player): array
    {
        $missingHP = $player->max_hp - $player->hp;
        $missingMP = $player->max_mp - $player->mp;
        $missingSP = $player->max_sp - $player->sp;

        if ($missingHP <= 0 && $missingMP <= 0 && $missingSP <= 0) {
            return $this->createErrorResponse('HP、MP、SPは既に満タンです。');
        }

        $totalCost = ($missingHP * self::HP_RATE) + 
                     ($missingMP * self::MP_RATE) + 
                     ($missingSP * self::SP_RATE);

        if ($player->gold < $totalCost) {
            return $this->createErrorResponse("お金が足りません。必要金額: {$totalCost}G");
        }

        $player->hp = $player->max_hp;
        $player->mp = $player->max_mp;
        $player->sp = $player->max_sp;
        $player->gold -= $totalCost;
        $player->save();

        return $this->createSuccessResponse(
            "HP、MP、SPを全回復しました。",
            [
                'healed_hp' => $missingHP,
                'healed_mp' => $missingMP,
                'healed_sp' => $missingSP,
                'cost' => $totalCost,
                'current_hp' => $player->hp,
                'current_mp' => $player->mp,
                'current_sp' => $player->sp,
                'current_gold' => $player->gold,
            ]
        );
    }
}