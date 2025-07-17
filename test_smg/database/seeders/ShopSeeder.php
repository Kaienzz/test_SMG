<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\Item;
use App\Enums\ShopType;
use App\Enums\ItemCategory;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedItems();
        $this->seedTownAShops();
        $this->seedTownBShops();
    }

    private function seedItems(): void
    {
        Item::firstOrCreate(
            ['name' => 'ポーション'],
            [
                'description' => 'HPを50回復する',
                'category' => ItemCategory::POTION->value,
                'rarity' => 1,
                'effects' => ['heal_hp' => 50],
                'value' => 100,
            ]
        );

        Item::firstOrCreate(
            ['name' => 'エーテル'],
            [
                'description' => 'SPを30回復する',
                'category' => ItemCategory::POTION->value,
                'rarity' => 1,
                'effects' => ['heal_sp' => 30],
                'value' => 150,
            ]
        );
    }

    private function seedTownAShops(): void
    {
        $this->createItemShop('town_a', 'A町の道具屋', '旅の準備に必要なアイテムを取り揃えています。');
        $this->createBlacksmith('town_a', 'A町の鍛冶屋', '熟練の鍛冶職人が武器・防具の加工を承ります。');
    }

    private function seedTownBShops(): void
    {
        $this->createItemShop('town_b', 'B町の道具屋', '冒険者御用達の道具屋です。');
        $this->createBlacksmith('town_b', 'B町の鍛冶屋', '伝統の技で最高の装備を提供いたします。');
    }

    private function createItemShop(string $locationId, string $name, string $description): void
    {
        $shop = Shop::firstOrCreate(
            [
                'location_id' => $locationId,
                'location_type' => 'town',
                'shop_type' => ShopType::ITEM_SHOP->value,
            ],
            [
                'name' => $name,
                'description' => $description,
                'is_active' => true,
            ]
        );

        $potionItem = Item::where('name', 'ポーション')->first();
        $etherItem = Item::where('name', 'エーテル')->first();

        if ($potionItem) {
            ShopItem::firstOrCreate(
                [
                    'shop_id' => $shop->id,
                    'item_id' => $potionItem->id,
                ],
                [
                    'price' => 100,
                    'stock' => -1,
                    'is_available' => true,
                ]
            );
        }

        if ($etherItem) {
            ShopItem::firstOrCreate(
                [
                    'shop_id' => $shop->id,
                    'item_id' => $etherItem->id,
                ],
                [
                    'price' => 150,
                    'stock' => -1,
                    'is_available' => true,
                ]
            );
        }
    }

    private function createBlacksmith(string $locationId, string $name, string $description): void
    {
        Shop::firstOrCreate(
            [
                'location_id' => $locationId,
                'location_type' => 'town',
                'shop_type' => ShopType::BLACKSMITH->value,
            ],
            [
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'shop_config' => [
                    'repair_base_cost' => 50,
                    'enhance_base_cost' => 100,
                    'enhance_available' => true,
                    'dismantle_cost' => 20,
                    'dismantle_available' => false,
                ],
            ]
        );
    }
}
