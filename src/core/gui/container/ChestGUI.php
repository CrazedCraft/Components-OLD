<?php

/**
 * ChestGUI.php – Components
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

namespace core\gui\container;

use core\CorePlayer;
use core\gui\item\GUIItem;
use core\Main;
use core\util\traits\CorePluginReference;
use core\Utils;
use pocketmine\block\Block;
use pocketmine\inventory\ContainerInventory;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;
use pocketmine\tile\Tile;

abstract class ChestGUI extends ContainerInventory implements ContainerGUI {

	use CorePluginReference;

	/** @var Position[] */
	private $lastOpenPos = [];

	/** @var [][] */
	private $replacedBlockData = [];

	public function __construct(Main $plugin) {
		$this->setCore($plugin);

		parent::__construct(new Position(0, 0, 0));
	}

	public function getNetworkType() : int {
		return WindowTypes::CONTAINER;
	}

	public function getName() : string {
		return "Chest";
	}

	public function getDefaultSize() : int {
		return 27;
	}

	public function remove(Item $item) : void {
		return; // Stop items being removed
	}

	public function removeItem(Item ...$slots) : array {
		return [];  // Stop items being removed
	}

	/**
	 * @param Player|CorePlayer $who
	 */
	public function onOpen(Player $who) : void {
		$this->lastOpenPos[$hash = spl_object_hash($who)] = $pos = $who->getPosition()->subtract(0.5, 4, 0.5);
		$pos->setComponents(intval($pos->x), intval($pos->y), intval($pos->z));
		$this->getHolder()->setComponents($pos->x, $pos->y, $pos->z);

		$block = $who->getLevel()->getBlock($pos);
		$this->replacedBlockData[$hash] = [$block->getId(), $block->getDamage()];

		Utils::sendBlock($who, $pos, Block::CHEST, 0);
		Utils::sendTile($who,$pos, $this->getDefaultCompoundTag($pos));

		parent::onOpen($who);
	}

	/**
	 * @param Player|CorePlayer $who
	 */
	public function onClose(Player $who) : void {
		if(isset($this->lastOpenPos[$hash = spl_object_hash($who)]) and isset($this->replacedBlockData[$hash])) {
			Utils::sendBlock($who, $this->lastOpenPos[$hash], $this->replacedBlockData[$hash][0], $this->replacedBlockData[$hash][1]);
		}

		//parent::onClose($who);
	}

	/**
	 * Handle the slot change
	 *
	 * @param Player $player
	 * @param int $slot
	 *
	 * @return bool
	 */
	public function processSlotChange(Player $player, int $slot) : bool {
		$item = $this->getItem($slot);
		if($item instanceof GUIItem and $player instanceof CorePlayer) {
			return $this->onSelect($slot, $item, $player);
		}

		return true;
	}

	public function onSelect(int $slot, GUIItem $item, CorePlayer $player) : bool {
		return $item->onSelect($player);
	}

	/**
	 * Get the compound tag for a chest tile at the last opened position
	 *
	 * @param Vector3 $pos
	 *
	 * @return CompoundTag
	 */
	protected function getDefaultCompoundTag(Vector3 $pos) : CompoundTag {
		return new CompoundTag("", [
			new StringTag("id", Tile::CHEST),
			new IntTag("x", $pos->x),
			new IntTag("y", $pos->y),
			new IntTag("z", $pos->z),
		]);
	}

}