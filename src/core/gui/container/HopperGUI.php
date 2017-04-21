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

namespace core\gui\container;

use core\CorePlayer;
use core\gui\item\GUIItem;
use core\Utils;
use pocketmine\inventory\HopperInventory;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Hopper;
use pocketmine\tile\Tile;

abstract class HopperGUI extends HopperInventory  implements ContainerGUI {

	/** @var null|Hopper */
	private $fakeHopper = null;

	/** @var Position */
	private $lastOpenPos = null;

	/** @var array */
	private $replacedBlockData = [];

	public function __construct(CorePlayer $owner) {
		$this->fakeHopper = new Hopper($owner->getLevel()->getChunk($owner->x >> 4, $owner->z >> 4), new CompoundTag("", [
			new ListTag("Items", []),
			new StringTag("id", Tile::HOPPER),
			new IntTag("x", $owner->x),
			new IntTag("y", $owner->y),
			new IntTag("z", $owner->z)
		]));
		parent::__construct($this->fakeHopper);
	}

	public function onOpen(Player $who) {
		$this->lastOpenPos = $who->getPosition()->subtract(0.5, 4, 0.5);
		$this->fakeHopper->setComponents($this->lastOpenPos->x, $this->lastOpenPos->y, $this->lastOpenPos->z);
		$this->fakeHopper->spawnTo($who);
		$block = $who->getLevel()->getBlock($this->lastOpenPos);
		$this->replacedBlockData = [$block->getId(), $block->getDamage()];
		Utils::sendBlock($who, $this->lastOpenPos, Item::HOPPER_BLOCK, 0);
		parent::onOpen($who);
	}

	public function onClose(Player $who) {
		Utils::sendBlock($who, $this->lastOpenPos, $this->replacedBlockData[0], $this->replacedBlockData[1]);
		parent::onClose($who);
	}

	public function onSelect($slot, GUIItem $item, CorePlayer $player) {
		return false;
	}

}