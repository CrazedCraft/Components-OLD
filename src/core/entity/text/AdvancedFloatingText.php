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
 * Created on 12/07/2016 at 9:11 PM
 *
 */

namespace core\entity\text;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\Player;
use pocketmine\utils\UUID;

/**
 * An advanced floating text implementation that allows the text to be unique for each player
 */
class AdvancedFloatingText extends Entity {

	/** @var Callable */
	private $callable = null;

	/** @var UUID */
	protected $uuid = null;

	public function getText(Player $player) : string {
		return call_user_func($this->callable, $player);
	}

	public function setCallable(Callable $callable) {
		$this->callable = $callable;

		$this->updateNameTag();
	}

	/**
	 * Send the updated nametag to all viewers
	 */
	protected function updateNameTag() {
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->id;

		foreach($this->hasSpawned as $viewer) {
			if($viewer->isOnline()) {
				$pk->metadata = [Entity::DATA_TYPE_STRING, $this->getText($viewer)];
				$viewer->dataPacket($pk);
			}
		}
	}

	public function initEntity() {
		parent::initEntity();

		if(isset($this->namedtag->uuid) and $this->namedtag->uuid instanceof StringTag) {
			$this->uuid = UUID::fromString($this->namedtag["uuid"]);
		} else {
			$this->uuid = UUID::fromRandom();
		}
	}

	/**
	 * Don't save the floating text
	 */
	public function saveNBT() {

	}

	public function onUpdate($currentTick) {
		return false; // don't tick floating text
	}

	/**
	 * Spawn the floating text to a player
	 *
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		if($player !== $this and !isset($this->hasSpawned[$player->getId()])) {
			$this->hasSpawned[$player->getId()] = $player;

			$pk = new AddPlayerPacket();
			$pk->eid = $this->id;
			$pk->uuid = $this->uuid;
			$pk->x = $this->x;
			$pk->y = $this->y + 0.15;
			$pk->z = $this->z;
			$pk->speedX = 0;
			$pk->speedY = 0;
			$pk->speedZ = 0;
			$pk->yaw = 0;
			$pk->pitch = 0;
			$pk->item = Item::get(0);
			$pk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, (1 << Entity::DATA_FLAG_SHOW_NAMETAG) | (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG)],
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->getText($player)],
				Entity::DATA_LEAD_HOLDER => [Entity::DATA_TYPE_LONG, -1],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.01],
			];
			$player->dataPacket($pk);
		}
	}

}