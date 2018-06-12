<?php

/**
 * FloatingText.php – Components
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

namespace core\entity\text;

use core\Main;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\UUID;

class FloatingText {

	/** @var Position */
	protected $pos;

	/** @var string */
	protected $text = "";

	/** @var int */
	protected $eid;

	/** @var array */
	protected $hasSpawned = [];

	public function __construct(Position $pos, $text) {
		$this->pos = $pos;
		$this->text = $text;
		$this->eid = Entity::$entityCount++;
		$this->spawnToAll();
		Main::getInstance()->floatingText[] = $this;
	}

	/**
	 * Spawn the floating text to an array of players
	 *
	 * @param array $players
	 */
	public function spawnToAll(array $players = []) {
		if(empty($players)) {
			$players = Server::getInstance()->getOnlinePlayers();
		}
		foreach($players as $p) {
			$this->spawnTo($p);
		}
	}

	/**
	 * Despawn the floating text from an array of players
	 *
	 * @param array $players
	 */
	public function despawnFromAll(array $players = []) {
		if(empty($players)) {
			$players = Server::getInstance()->getOnlinePlayers();
		}
		foreach($players as $p) {
			$this->despawnFrom($p);
		}
	}

	/**
	 * Spawn the floating text to a player
	 *
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		if($player !== $this and !isset($this->hasSpawned[$player->getId()])) {
			$this->hasSpawned[$player->getId()] = true;

			$pk = new AddPlayerPacket();
			$pk->entityRuntimeId = $this->eid;
			$pk->entityUniqueId = $this->eid;
			$pk->uuid = UUID::fromRandom();
			$pk->position = $this->pos->add(0, 0.15);
			$pk->yaw = 0;
			$pk->pitch = 0;
			$flags = Entity::DATA_FLAG_CAN_SHOW_NAMETAG & Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG & Entity::DATA_FLAG_IMMOBILE;
			$pk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->text],
				Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.01],
			];
			$player->dataPacket($pk);
		}
	}

	/**
	 * Despawn the floating text from a player
	 *
	 * @param Player $player
	 */
	public function despawnFrom(Player $player) {
		if(isset($this->hasSpawned[$player->getId()])) {
			unset($this->hasSpawned[$player->getId()]);

			$pk = new RemoveEntityPacket();
			$pk->eid = $this->eid;
			$player->dataPacket($pk);
		}
	}

}