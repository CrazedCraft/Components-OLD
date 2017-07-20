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
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
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
		$this->eid = bcadd("1095216660480", mt_rand(0, 0x7fffffff));
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
			$this->hasSpawned[$player->getId()] = $player;

			$pk = new AddPlayerPacket();
			$pk->eid = $this->eid;
			$pk->uuid = UUID::fromRandom();
			$pk->x = $this->pos->x;
			$pk->y = $this->pos->y + 0.15;
			$pk->z = $this->pos->z;
			$pk->speedX = 0;
			$pk->speedY = 0;
			$pk->speedZ = 0;
			$pk->yaw = 0;
			$pk->pitch = 0;
			$pk->item = Item::get(0);
			$pk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, (1 << Entity::DATA_FLAG_SHOW_NAMETAG) | (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG)],
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->text],
				Entity::DATA_LEAD_HOLDER => [Entity::DATA_TYPE_LONG, -1],
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