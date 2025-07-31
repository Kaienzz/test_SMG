<?php

namespace App\Enums;

enum ShopType: string
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
        };
    }

    public function getControllerClass(): string
    {
        return match($this) {
            self::ITEM_SHOP => 'ItemShopController',
            self::BLACKSMITH => 'BlacksmithController',
            self::WEAPON_SHOP => 'WeaponShopController',
            self::ARMOR_SHOP => 'ArmorShopController',
            self::MAGIC_SHOP => 'MagicShopController',
            self::INN => 'InnController',
            self::GUILD => 'GuildController',
            self::TAVERN => 'TavernController',
            self::ALCHEMY_SHOP => 'AlchemyShopController',
        };
    }

    public function getViewPrefix(): string
    {
        return match($this) {
            self::ITEM_SHOP => 'shops.item',
            self::BLACKSMITH => 'shops.blacksmith',
            self::WEAPON_SHOP => 'shops.weapon',
            self::ARMOR_SHOP => 'shops.armor',
            self::MAGIC_SHOP => 'shops.magic',
            self::INN => 'shops.inn',
            self::GUILD => 'shops.guild',
            self::TAVERN => 'shops.tavern',
            self::ALCHEMY_SHOP => 'shops.alchemy',
        };
    }

    public static function getAllTypes(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}