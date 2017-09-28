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
 * Created on 12/07/2016 at 9:13 PM
 *
 */

namespace core\entity\text;

use core\Main;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\UUID;

/**
 * Basic floating text entity that will display the same text to all players
 */
class FloatingText extends Entity {

	/** @var string */
	protected $text = "";

	/** @var UUID */
	protected $uuid = null;

	public function getText() : string {
		return $this->text;
	}

	public function setText(string $value) {
		$this->text = $value;

		$this->updateNameTag();
	}

	/**
	 * Send the updated nametag to all viewers
	 */
	protected function updateNameTag() {
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->id;
		$pk->metadata = [Entity::DATA_TYPE_STRING, $this->getText()];

		foreach($this->hasSpawned as $viewer) {
			if($viewer->isOnline()) {
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
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->getText()],
				Entity::DATA_LEAD_HOLDER => [Entity::DATA_TYPE_LONG, -1],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.01],
			];
			$player->dataPacket($pk);
		}
	}

}