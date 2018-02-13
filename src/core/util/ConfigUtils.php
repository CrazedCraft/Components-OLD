<?php

/**
 * ConfigUtils.php – Components
 *
 * Copyright (C) 2015-2018 Jack Noordhuis
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Jack Noordhuis
 *
 */

declare(strict_types=1);

namespace core\util;

use core\exception\InvalidConfigException;
use core\language\LanguageUtils;
use pocketmine\entity\Effect;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;

class ConfigUtils {

	/**
	 * Construct an item from an array representation
	 *
	 * @param array $itemData
	 *
	 * @return null|Item
	 */
	public static function parseArrayItem(array $itemData) : ?Item {
		$item = (is_string($itemData["id"]) ? Item::fromString($itemData["id"]) : Item::get((int) $itemData["id"]));

		$item->setDamage((int) ($itemData["meta"] ?? 0));
		$item->setCount((int) ($itemData["count"] ?? 1));

		$item->setCustomName(LanguageUtils::translateColors($itemData["name"] ?? ""));
		foreach($itemData["enchantments"] ?? [] as $enchData) {
			$item->addEnchantment(self::parseArrayEnchantment($enchData));
		}

		return $item;
	}

	/**
	 * Construct an enchantment from an array representaion
	 *
	 * @param array $enchData
	 *
	 * @return Enchantment
	 */
	public static function parseArrayEnchantment(array $enchData) : Enchantment {
		$ench = (is_string($enchData["name"]) ? Enchantment::getEnchantmentByName($enchData["name"]) : Enchantment::getEnchantment((int) $enchData["name"]));
		if(!$ench instanceof Enchantment) throw new InvalidConfigException("Unknown enchantment name supplied for kit item! Value: " . $enchData["name"] ?? "NULL");

		$ench->setLevel($enchData["level"] ?? 1);

		return $ench;
	}

	/**
	 * Construct an effect from an array representation
	 *
	 * @param array $effectData
	 *
	 * @return Effect
	 */
	public static function parseArrayEffect(array $effectData) : Effect {
		$effect = (is_string($effectData["name"]) ? Effect::getEffectByName($effectData["name"]) : Effect::getEffect((int) $effectData["name"]));
		if(!$effect instanceof Effect) throw new InvalidConfigException("Unknown effect name supplied for kit effect! Value: " . $effectData["name"] ?? "NULL");

		$effect->setDuration((int) ($effectData["time"] ?? 100));
		$effect->setAmplifier((int) ($effectData["amplifier"] ?? 0));

		return $effect;
	}

}