<?php

namespace App\Enums;

enum FacilityType: string
{
    case ITEM_SHOP = 'item_shop';
    case BLACKSMITH = 'blacksmith';
    case WEAPON_SHOP = 'weapon_shop';
    case ARMOR_SHOP = 'armor_shop';
    case MAGIC_SHOP = 'magic_shop';
    case INN = 'inn';
    case GUILD = 'guild';
    case TAVERN = 'tavern';
    case ALCHEMY_SHOP = 'alchemy_shop';
    case COMPOUNDING_SHOP = 'compounding_shop';
    case LIBRARY = 'library';

    public function getDisplayName(): string
    {
        return match($this) {
            self::ITEM_SHOP => '道具屋',
            self::BLACKSMITH => '鍛冶屋',
            self::WEAPON_SHOP => '武器屋',
            self::ARMOR_SHOP => '防具屋',
            self::MAGIC_SHOP => '魔法屋',
            self::INN => '宿屋',
            self::GUILD => 'ギルド',
            self::TAVERN => '酒屋',
            self::ALCHEMY_SHOP => '錬金屋',
            self::COMPOUNDING_SHOP => '調合店',
            self::LIBRARY => '図書館',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::ITEM_SHOP => '冒険に必要なアイテムを販売しています。',
            self::BLACKSMITH => '武器や防具の強化・修理を行います。',
            self::WEAPON_SHOP => '様々な武器を取り扱っています。',
            self::ARMOR_SHOP => '防具類を専門に販売しています。',
            self::MAGIC_SHOP => '魔法に関するアイテムを扱っています。',
            self::INN => '休息とセーブができます。',
            self::GUILD => 'クエストの受注や情報収集ができます。',
            self::TAVERN => 'HP、MP、SPを回復できます。',
            self::ALCHEMY_SHOP => '武器・防具を素材で強化できます。',
            self::COMPOUNDING_SHOP => '材料から消耗品などを調合できます。',
            self::LIBRARY => '知識を得ることができます。',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::ITEM_SHOP => '🏪',
            self::BLACKSMITH => '⚒️',
            self::WEAPON_SHOP => '⚔️',
            self::ARMOR_SHOP => '🛡️',
            self::MAGIC_SHOP => '🔮',
            self::INN => '🏨',
            self::GUILD => '🏛️',
            self::TAVERN => '🍺',
            self::ALCHEMY_SHOP => '⚗️',
            self::COMPOUNDING_SHOP => '⚗️',
            self::LIBRARY => '📚',
        };
    }

    public function getControllerClass(): string
    {
        return match($this) {
            self::ITEM_SHOP => 'ItemFacilityController',
            self::BLACKSMITH => 'BlacksmithFacilityController',
            self::WEAPON_SHOP => 'WeaponFacilityController',
            self::ARMOR_SHOP => 'ArmorFacilityController',
            self::MAGIC_SHOP => 'MagicFacilityController',
            self::INN => 'InnFacilityController',
            self::GUILD => 'GuildFacilityController',
            self::TAVERN => 'TavernFacilityController',
            self::ALCHEMY_SHOP => 'AlchemyFacilityController',
            self::COMPOUNDING_SHOP => 'CompoundingFacilityController',
            self::LIBRARY => 'LibraryFacilityController',
        };
    }

    public function getViewPrefix(): string
    {
        return match($this) {
            self::ITEM_SHOP => 'facilities.item',
            self::BLACKSMITH => 'facilities.blacksmith',
            self::WEAPON_SHOP => 'facilities.weapon',
            self::ARMOR_SHOP => 'facilities.armor',
            self::MAGIC_SHOP => 'facilities.magic',
            self::INN => 'facilities.inn',
            self::GUILD => 'facilities.guild',
            self::TAVERN => 'facilities.tavern',
            self::ALCHEMY_SHOP => 'facilities.alchemy',
            self::COMPOUNDING_SHOP => 'facilities.compounding',
            self::LIBRARY => 'facilities.library',
        };
    }

    public static function getAllTypes(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}