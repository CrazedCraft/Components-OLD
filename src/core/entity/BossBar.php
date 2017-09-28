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
 * Created on 03/08/2017 at 9:55 AM
 *
 */

namespace core\entity;

use core\CorePlayer;
use core\Main;
use core\Utils;
use pocketmine\entity\Entity;
use pocketmine\entity\Squid;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\BossEventPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\scheduler\PluginTask;

class BossBar {

	/** @var string[] */
	public $subscribed = [];

	/** @var string */
	private $text = "";

	/** @var int */
	private $progress = 1;

	/** @var int */
	public $eid = -1;

	/** @var PluginTask */
	private $updateTask = null;

	public function __construct() {
		$this->eid = Entity::$entityCount++;
		$this->updateTask = new BossBarUpdateTask(Main::getInstance(), $this);
	}

	public function spawnTo(CorePlayer $player) {
		if(isset($this->subscribed[$player->getId()])) {
			return false;
		}
		$this->subscribed[$player->getId()] = $player->getUniqueId()->toString();
		$pk = new AddEntityPacket();
		$pk->eid = $this->eid;
		$pk->type = 52;
		$pk->x = $player->getX();
		$pk->y = $player->getY() - 15;
		$pk->z = $player->getZ();
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$pk->yaw = 0;
		$pk->pitch = 0;
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, [Entity::DATA_FLAG_INVISIBLE, Entity::DATA_FLAG_SILENT]],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, "\n\n\n" . $this->text],
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
			Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.1],
			Entity::DATA_BOUNDING_BOX_WIDTH => [Entity::DATA_TYPE_FLOAT, 0],
			Entity::DATA_BOUNDING_BOX_HEIGHT => [Entity::DATA_TYPE_FLOAT, 0]
		];
		$player->dataPacket($pk);
		$bpk = new BossEventPacket();
		$bpk->eid = $this->eid;
		$bpk->eventType = BossEventPacket::TYPE_SHOW;
		$bpk->title = $this->text;
		$bpk->healthPercent = $this->progress;
		$player->dataPacket($bpk);
		return true;
	}

	public function despawnFrom(CorePlayer $player) {
		if(!isset($this->subscribed[$player->getId()])) {
			return false;
		}
		unset($this->subscribed[$player->getId()]);
		$pk = new RemoveEntityPacket();
		$pk->eid = $this->eid;
		$player->dataPacket($pk);
		return true;
	}

	public function setText(string $text) {
		$this->text = $text;
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->eid;
		$pk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, "\n\n\n" . $text]];
		//$pk->encode(Info::PROTOCOL_110);
		//$pk->isEncoded = true;
		$bpk = new BossEventPacket();
		$bpk->eid = $this->eid;
		$bpk->eventType = BossEventPacket::TYPE_SHOW;
		$bpk->title = $text;
		$bpk->healthPercent = $this->progress;
		//$bpk->encode(Info::PROTOCOL_110);
		//$bpk->isEncoded = true;
		foreach($this->subscribed as $id => $uuid) {
			$p = Utils::getPlayerByUUID($uuid);
			if($p instanceof CorePlayer) {
				$p->dataPacket($pk);
				$p->dataPacket($bpk);
			}
		}
	}

	public function setProgress(int $progress = 100) {
		$this->progress = $progress;
		$pk = new UpdateAttributesPacket();
		$pk->entityId = $this->eid;
		$pk->minValue = 0;
		$pk->maxValue = 100;
		$pk->value = $progress;
		$pk->defaultValue = $pk->maxValue;
		$pk->name = UpdateAttributesPacket::HEALTH;
		$bpk = new BossEventPacket();
		$bpk->eid = $this->eid;
		$bpk->eventType = BossEventPacket::TYPE_SHOW;
		$bpk->title = $this->text;
		$bpk->healthPercent = $progress;
		foreach($this->subscribed as $id => $uuid) {
			$p = Utils::getPlayerByUUID($uuid);
			if($p instanceof CorePlayer) {
				$p->dataPacket($pk);
				$p->dataPacket($bpk);
			}
		}
	}

	public function moveFor(CorePlayer $player) {
		$pk = new MoveEntityPacket();
		$pk->entities = [[$this->eid, $player->getX(), $player->getY() - 15, $player->getZ(), 0, 0]];
		$player->dataPacket($pk);
	}

}