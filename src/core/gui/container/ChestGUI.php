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
use pocketmine\inventory\ChestInventory;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;

abstract class ChestGUI extends ChestInventory implements ContainerGUI {

	/** @var null|Chest */
	private $fakeChest = null;

	/** @var Position */
	private $lastOpenPos = null;

	/** @var array */
	private $replacedBlockData = [];

	public function __construct(CorePlayer $owner) {
		$this->fakeChest = new Chest($owner->getLevel()->getChunk($owner->x >> 4, $owner->z >> 4), new Compound("", [
			new Enum("Items", []),
			new StringTag("id", Tile::CHEST),
			new IntTag("x", $owner->x),
			new IntTag("y", $owner->y),
			new IntTag("z", $owner->z)
		]));
		parent::__construct($this->fakeChest);
	}

	public function onOpen(Player $who) {
		$this->lastOpenPos = $who->getPosition()->subtract(0.5, 4, 0.5);
		$this->fakeChest->setComponents($this->lastOpenPos->x, $this->lastOpenPos->y, $this->lastOpenPos->z);
		$this->fakeChest->spawnTo($who);
		$block = $who->getLevel()->getBlock($this->lastOpenPos);
		$this->replacedBlockData = [$block->getId(), $block->getDamage()];
		Utils::sendBlock($who, $this->lastOpenPos, Item::CHEST, 0);
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