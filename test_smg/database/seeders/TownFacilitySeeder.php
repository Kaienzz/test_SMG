<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TownFacility;
use App\Models\FacilityItem;
use App\Models\Item;
use App\Enums\FacilityType;
use App\Enums\ItemCategory;

class TownFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedItems();
        $this->seedTownAFacilities();
        $this->seedTownBFacilities();
        $this->seedTownPrimaFacilities();
    }

    private function seedItems(): void
    {
        Item::firstOrCreate(
            ['name' => 'ポーション'],
            [
                'description' => 'HPを50回復する',
                'category' => ItemCategory::POTION->value,
                'effects' => ['heal_hp' => 50],
                'value' => 100,
            ]
        );

        Item::firstOrCreate(
            ['name' => 'エーテル'],
            [
                'description' => 'SPを30回復する',
                'category' => ItemCategory::POTION->value,
                'effects' => ['heal_sp' => 30],
                'value' => 150,
            ]
        );
    }

    private function seedTownAFacilities(): void
    {
        $this->createItemShop('town_a', 'A町の道具屋', '旅の準備に必要なアイテムを取り揃えています。');
        $this->createBlacksmith('town_a', 'A町の鍛冶屋', '熟練の鍛冶職人が武器・防具の加工を承ります。');
        $this->createTavern('town_a', 'A町の酒場', '疲れた冒険者の憩いの場。HP、MP、SPを回復できます。');
        $this->createAlchemyShop('town_a', 'A町の錬金屋', '古い錬金術の秘伝で武器・防具を強化いたします。');
    }

    private function seedTownBFacilities(): void
    {
        $this->createItemShop('town_b', 'B町の道具屋', '冒険者御用達の道具屋です。');
        $this->createBlacksmith('town_b', 'B町の鍛冶屋', '伝統の技で最高の装備を提供いたします。');
        $this->createTavern('town_b', 'B町の酒場', '武闘の疲労を癒す最高の酒場です。');
        $this->createAlchemyShop('town_b', 'B町の錬金屋', '秘密の錬金術で装備を究極進化させます。');
    }

    private function seedTownPrimaFacilities(): void
    {
        $this->createItemShop('town_prima', 'プリマ町の道具屋', 'プリマ町で評判の道具屋です。冒険に必要なものが揃っています。');
        $this->createBlacksmith('town_prima', 'プリマ町の鍛冶屋', 'プリマ町で一番の腕を持つ鍛冶職人の店です。');
        $this->createTavern('town_prima', 'プリマ町の酒場', 'プリマ町の心温まる酒場で、疲れを癒しましょう。');
        $this->createAlchemyShop('town_prima', 'プリマ町の錬金屋', 'プリマ町の神秘的な錬金術師が装備を強化してくれます。');
    }

    private function createItemShop(string $locationId, string $name, string $description): void
    {
        $facility = TownFacility::firstOrCreate(
            [
                'location_id' => $locationId,
                'location_type' => 'town',
                'facility_type' => FacilityType::ITEM_SHOP->value,
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
            FacilityItem::firstOrCreate(
                [
                    'facility_id' => $facility->id,
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
            FacilityItem::firstOrCreate(
                [
                    'facility_id' => $facility->id,
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
        TownFacility::firstOrCreate(
            [
                'location_id' => $locationId,
                'location_type' => 'town',
                'facility_type' => FacilityType::BLACKSMITH->value,
            ],
            [
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'facility_config' => [
                    'repair_base_cost' => 50,
                    'enhance_base_cost' => 100,
                    'enhance_available' => true,
                    'dismantle_cost' => 20,
                    'dismantle_available' => false,
                ],
            ]
        );
    }

    private function createTavern(string $locationId, string $name, string $description): void
    {
        TownFacility::firstOrCreate(
            [
                'location_id' => $locationId,
                'location_type' => 'town',
                'facility_type' => FacilityType::TAVERN->value,
            ],
            [
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'facility_config' => [
                    'hp_rate' => 10,
                    'mp_rate' => 15,
                    'sp_rate' => 5,
                    'full_heal_discount' => 0.1,
                ],
            ]
        );
    }

    private function createAlchemyShop(string $locationId, string $name, string $description): void
    {
        TownFacility::firstOrCreate(
            [
                'location_id' => $locationId,
                'location_type' => 'town',
                'facility_type' => FacilityType::ALCHEMY_SHOP->value,
            ],
            [
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'facility_config' => [
                    'max_materials' => 5,
                    'base_success_rate' => 95.0,
                    'masterwork_base_chance' => 5.0,
                    'available_services' => ['alchemy'],
                ],
            ]
        );
    }
}