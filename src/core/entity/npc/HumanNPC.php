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

namespace core\entity\npc;

use core\Main;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\Player;
use pocketmine\utils\PluginException;

abstract class HumanNPC extends Human implements BaseNPC {

	/** @var Main */
	private $core;

	/** @var string */
	protected $name;

	/**
	 * @return Main
	 */
	public function getCore() {
		return $this->core;
	}

	/**
	 * @param bool $value
	 */
	public function setImmobile($value = true) {
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_NO_AI, !$value);
	}

	/**
	 * @return bool
	 */
	public function isImmobile() {
		return (bool) $this->getDataFlag(Entity::DATA_FLAG_NO_AI, Entity::DATA_FLAGS);
	}

	/**
	 * @param bool $value
	 */
	public function setVisible($value = true) {
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, !$value);
	}

	/**
	 * @return bool
	 */
	public function isVisible() {
		return (bool) $this->getDataFlag(Entity::DATA_FLAG_INVISIBLE, Entity::DATA_FLAGS);
	}

	/**
	 * @return bool
	 */
	public function isNameTagVisible() {
		return $this->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHOW_NAMETAG);
	}

	/**
	 * @return bool
	 */
	public function isNameTagAlwaysVisible() {
		return $this->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG);
	}

	/**
	 * @param bool $value
	 */
	public function setNameTagVisible($value = true) {
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHOW_NAMETAG, $value);
	}

	/**
	 * @param bool $value
	 */
	public function setNameTagAlwaysVisible($value = true) {
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG, $value);
	}

	/**
	 * @return float
	 */
	public function getScale() : float {
		return $this->getDataProperty(Entity::DATA_SCALE);
	}

	/**
	 * @param float $value
	 */
	public function setScale(float $value) {
		$multiplier = $value / $this->getScale();
		$this->width *= $multiplier;
		$this->height *= $multiplier;
		$halfWidth = $this->width / 2;
		$this->boundingBox->setBounds(
			$this->x - $halfWidth,
			$this->y,
			$this->z - $halfWidth,
			$this->x + $halfWidth,
			$this->y + $this->height,
			$this->z + $halfWidth
		);

		$this->setDataProperty(Entity::DATA_SCALE, Entity::DATA_TYPE_FLOAT, $value);
		$this->setDataProperty(Entity::DATA_BOUNDING_BOX_WIDTH, Entity::DATA_TYPE_FLOAT, $this->width);
		$this->setDataProperty(Entity::DATA_BOUNDING_BOX_HEIGHT, Entity::DATA_TYPE_FLOAT, $this->height);
	}

	/**
	 * Spawn the NPC to a player
	 *
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		if($player !== $this and !isset($this->hasSpawned[$player->getId()]) and isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;

			$pk = new PlayerListPacket();
			$pk->type = PlayerListPacket::TYPE_ADD;
			$pk->entries[] = [$this->getUniqueId(), $this->getId(), "", ($this->skinName !== "" ? $this->skinName : $player->skinName), ($this->skin !== "" ? $this->skin : $player->skin)];
			$player->dataPacket($pk);

			$pk = new AddPlayerPacket();
			$pk->uuid = $this->getUniqueId();
			$pk->username = $this->getName();
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);

			$this->inventory->sendArmorContents($player);
			$this->level->addPlayerHandItem($this, $player);
		}
	}

	/**
	 * Ensure the NPC doesn't take damage
	 *
	 * @param float $damage
	 * @param EntityDamageEvent $source
	 */
	public function attack($damage, EntityDamageEvent $source) {
		$source->setCancelled(true);
	}

	/**
	 * Make sure the npc doesn't get saved
	 */
	public function saveNBT() {
		return false;
	}

	/**
	 * Same save characteristics as a player
	 */
	public function getSaveId() {
		return "Human";
	}

	/**
	 * Set the NPC's real name to the one given when the entity is spawned
	 */
	public function initEntity() {
		parent::initEntity();
		$plugin = $this->server->getPluginManager()->getPlugin("Components");
		if($plugin instanceof Main and $plugin->isEnabled()){
			$this->core = $plugin;
		} else {
			throw new PluginException("Core plugin isn't loaded!");
		}
		$this->name = $this->getNameTag();
		$this->core->freezeLoadedChunks();
		$this->setImmobile();
		$this->setNameTagVisible();
		$this->setNameTagAlwaysVisible();
	}

	/**
	 * @param $string
	 */
	public function setName($string) {
		$this->name = $string;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Make sure nothing drops in case the NPC dies
	 *
	 * @return array
	 */
	public function getDrops() {
		return [];
	}

	/**
	 * Function to easily spawn an NPC
	 *
	 * @param string $shortName
	 * @param Location $pos
	 * @param string $name
	 * @param string $skin
	 * @param string $skinName
	 * @param Compound $nbt
	 *
	 * @return HumanNPC|null
	 */
	public static function spawn($shortName, Location $pos, $name, $skin, $skinName, Compound $nbt) {
		$entity = Entity::createEntity($shortName, $pos->getLevel()->getChunk($pos->x >> 4, $pos->z >> 4), $nbt);
		if($entity instanceof HumanNPC) {
			$entity->setSkin($skin, $skinName);
			$entity->setName($name);
			$entity->setNameTag($entity->getName());
			$entity->setSkin($skin, $skinName);
			$entity->setPositionAndRotation($pos, $pos->yaw, $pos->pitch);
			return $entity;
		} else {
			$entity->kill();
		}
		return null;
	}

}