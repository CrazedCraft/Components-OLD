<?php

/**
 * GUIItem.php – Components
 *
 * Copyright (C) 2015-2017 Jack Noordhuis
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Jack Noordhuis
 *
 */

namespace core\gui\item;

use core\CorePlayer;
use core\gui\container\ContainerGUI;
use core\language\LanguageManager;
use core\language\LanguageUtils;
use core\Utils;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;

abstract class GUIItem extends Item {

	/** The unique ID for the GUI Item (allows individual cooldown times for every item) */
	const GUI_ITEM_ID = "invalid";

	/** Time in which a user has to double click the item */
	const DOUBLE_CLICK_TIME = 20;

	/** @var ContainerGUI */
	private $parent;

	/** @var string */
	private $previewName = "";

	/** @var string */
	private $previewDescription = "";

	/**
	 * GUIItem constructor
	 *
	 * @param Item $item
	 * @param ContainerGUI|null $parent
	 */
	public function __construct(Item $item, ContainerGUI $parent = null) {
		parent::__construct($item->getId(), $item->getDamage(), $item->getCount());
		$this->parent = $parent;
		$this->setCustomName($item->getCustomName());
		$this->previewName = $this->getName(); // give the name a default value so we don't need to fetch it every time
		$this->previewDescription = LanguageManager::getInstance()->translate("GUI_ITEM_TAP_GROUND"); // give the description a default value so we don't need to fetch it every time
	}

	/**
	 * @param CorePlayer $player
	 * @param bool $force
	 */
	final public function handleClick(CorePlayer $player, bool $force = false) {
		$time = microtime(true);
		$lang = $player->getCore()->getLanguageManager();
		$cooldownTime = $player->getGuiCooldown($id = (new \ReflectionObject($this))->getShortName());
		$diff = floor($cooldownTime - $time);
		if($diff <= 0) {
			$player->setGuiCooldown($time + $this->getCooldown(), $id);
			$this->onClick($player);
		} else {
			$player->sendPopup($lang->translateForPlayer($player, "GUI_ITEM_COOLDOWN", [Utils::getTimeString($diff)]));
		}
	}

	/**
	 * Handles the clicking of a GUI item
	 *
	 * @param CorePlayer $player
	 *
	 * @return bool
	 */
	public function onClick(CorePlayer $player) {
		return true;
	}

	/**
	 * Handles the selection of an item in a GUI container
	 *
	 * @param CorePlayer $player
	 *
	 * @return bool   Whether the item can be moved
	 */
	public function onSelect(CorePlayer $player) : bool {
		return true;
	}

	/**
	 * Cooldown time in seconds
	 *
	 * @return int
	 */
	public function getCooldown() : int {
		return 0;
	}

	/**
	 * Display the preview to a player
	 *
	 * @param CorePlayer $player
	 * @param bool $popupOnly
	 */
	final public function sendPreview(CorePlayer $player, $popupOnly = false) {
		if($popupOnly) {
			$player->sendPopup(LanguageUtils::centerPrecise($player->getCore()->getLanguageManager()->translateForPlayer($player, "GUI_ITEM_PREVIEW", [$this->getPreviewName($player), $this->getPreviewDescription($player)]), null));
		} else {
			$player->sendTip($this->getPreviewName($player));
			$player->sendPopup($this->getPreviewDescription($player));
		}
	}

	/**
	 * Name of the item
	 *
	 * @param CorePlayer $player
	 *
	 * @return string
	 */
	public function getPreviewName(CorePlayer $player) : string {
		return $this->previewName;
	}

	/**
	 * @param string $value
	 */
	public function setPreviewName(string $value) {
		$this->previewName = $value;
	}

	/**
	 * Description of the item
	 *
	 * @param CorePlayer $player
	 *
	 * @return string
	 */
	public function getPreviewDescription(CorePlayer $player) : string {
		return $this->previewDescription;
	}

	/**
	 * @param string $value
	 */
	public function setPreviewDescription(string $value) {
		$this->previewDescription = $value;
	}

	/**
	 * Give the item an enchantment effect
	 */
	public function giveEnchantmentEffect() {
		$tag = $this->getNamedTagEntry(self::TAG_ENCH);
		if(!($tag instanceof ListTag)){
			$tag = new ListTag(self::TAG_ENCH, [], NBT::TAG_Compound);
		}
		$tag->set(0, new CompoundTag("",
			[
				"id" => new ShortTag("id", -1),
				"lvl" => new ShortTag("lvl", 1),
			])
		);

		$this->setNamedTagEntry($tag);
	}

	/**
	 * Remove an enchantment effect from the item
	 */
	public function removeEnchantmentEffect() {
		$ench = $this->getNamedTagEntry(self::TAG_ENCH);
		if(!($ench instanceof ListTag)) {
			return;
		}

		/** @var CompoundTag $entry */
		foreach($ench as $key => $entry) {
			if($entry->getShort("id") === -1) {
				$ench->remove($key);
				break;
			}
		}

		$this->setNamedTagEntry($ench);
	}

}