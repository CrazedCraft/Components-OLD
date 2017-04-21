<?php

/**
 * CrazedCraft Network Components
 *
 * Copyright (C) 2016 CrazedCraft Network
 *
 * This is private software, you cannot redistribute it and/or modify any way
 * unless otherwise given permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author JackNoordhuis
 *
 * Created on 20/04/2017 at 6:10 PM
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
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\ShortTag;

abstract class GUIItem extends Item {

	/** The unique ID for the GUI Item (allows individual cooldown times for every item) */
	const GUI_ITEM_ID = "invalid";

	/** Time in which a user has to double click the item */
	const DOUBLE_CLICK_TIME = 20;

	private static $cooldownTick = [];
	protected $clickCount = 0;
	protected $lastClick = 0;

	/** @var ContainerGUI */
	private $parent;

	/**
	 * GUIItem constructor
	 *
	 * @param Item $item
	 * @param ContainerGUI|null $parent
	 */
	public function __construct(Item $item, ContainerGUI $parent = null) {
		parent::__construct($item->getId(), $item->getDamage(), $item->getCount(), $item->getName());
		$this->parent = $parent;
	}

	/**
	 * @param CorePlayer $player
	 * @param bool $force
	 */
	final public function handleClick(CorePlayer $player, bool $force = false) {
		$this->tickCooldowns();
		$ticks = $player->getServer()->getTick();
		$lang = $player->getCore()->getLanguageManager();
		if($this->clickCount == 0 and !$force) {
			$player->sendPopup($this->getPreview($player));
			$this->clickCount++;
		} else {
			$cooldownTick = $this->getCooldownTick($player);
			if($this->getCooldown() > 5 and floor($ticks - $cooldownTick / 20) >= floor($this->getCooldown() / 20)) {
				$this->clickCount = 0;
				$this->lastClick = 0;
				self::$cooldownTick[$player->getUniqueId()->toString()][self::GUI_ITEM_ID] = $ticks;
				$this->onClick($player);
			} else {
				$player->sendPopup($lang->translateForPlayer($player, "GUI_ITEM_COOLDOWN", [Utils::getTimeString($this->getCooldown() - ($ticks - $cooldownTick))]));
			}
		}
		$this->lastClick = $ticks;
	}

	public function onClick(CorePlayer $player) {
		return true;
	}

	public abstract function getCooldown() : int;

	public function getPreview(CorePlayer $player) : string {
		return LanguageUtils::centerPrecise($player->getCore()->getLanguageManager()->translateForPlayer($player, "GUI_ITEM_PREVIEW", [$this->getPreviewName($player), $this->getPreviewDescription($player)]), null);
	}

	public function getPreviewName(CorePlayer $player) : string {
		return $this->getName();
	}

	public function getPreviewDescription(CorePlayer $player) : string {
		return $player->getCore()->getLanguageManager()->translateForPlayer($player, "GUI_ITEM_TAP_GROUND");
	}

	final private function getCooldownTick(CorePlayer $player) {
		if(isset(self::$cooldownTick[$player->getUniqueId()->toString()][self::GUI_ITEM_ID])) {
			return self::$cooldownTick[$player->getUniqueId()->toString()][self::GUI_ITEM_ID];
		}
		return 0;
	}

	final private function tickCooldowns() {
		foreach(self::$cooldownTick as $plId => $cooldown) {
			if($cooldown == 0 or Utils::getPlayerByUUID($plId) == null) {
				unset(self::$cooldownTick[$plId]);
			}
		}
	}

	public function giveEnchantmentEffect() {
		$tag = $this->getNamedTag();
		$tag->ench = new Enum("ench", [
			0 => new Compound("", [
				"id" => new ShortTag("id", -1),
				"lvl" => new ShortTag("lvl", 1)
			])
		]);
		$tag->ench->setTagType(NBT::TAG_Compound);
		$this->setNamedTag($tag);
	}

	public function removeEnchantmentEffect() {
		$tag = $this->getNamedTag();
		unset($tag->ench);
		$this->setNamedTag($tag);
	}

}