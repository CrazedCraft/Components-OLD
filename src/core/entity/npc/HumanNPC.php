<?php

/**
 * HumanNPC.php – Components
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
		$this->setGenericFlag(Entity::DATA_FLAG_NO_AI, !$value);
	}

	/**
	 * @return bool
	 */
	public function isImmobile() : bool {
		return (bool) $this->getGenericFlag(Entity::DATA_FLAG_NO_AI);
	}

	/**
	 * @param bool $value
	 */
	public function setVisible($value = true) {
		$this->setGenericFlag(Entity::DATA_FLAG_INVISIBLE, !$value);
	}

	/**
	 * @return bool
	 */
	public function isVisible() {
		return $this->getGenericFlag(Entity::DATA_FLAG_INVISIBLE);
	}

	/**
	 * @return bool
	 */
	public function isNameTagVisible() {
		return $this->getGenericFlag(Entity::DATA_FLAG_CAN_SHOW_NAMETAG);
	}

	/**
	 * @return bool
	 */
	public function isNameTagAlwaysVisible() {
		return $this->getGenericFlag(Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG);
	}

	/**
	 * @param bool $value
	 */
	public function setNameTagVisible($value = true) {
		$this->setGenericFlag(Entity::DATA_FLAG_CAN_SHOW_NAMETAG, $value);
	}

	/**
	 * @param bool $value
	 */
	public function setNameTagAlwaysVisible($value = true) {
		$this->setGenericFlag(Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG, $value);
	}

	/**
	 * @return float
	 */
	public function getScale() : float {
		return $this->getDataProperty(Entity::DATA_SCALE) ?? 1.0;
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

	/**
	 * Update the entity without calling all the functions with extra overhead
	 *
	 * ** If you want the entity to do normal entity things you'll have to override this and call the methods yourself **
	 *
	 * @param $currentTick
	 *
	 * @return bool
	 */
	public function onUpdate($currentTick) {
		if($this->closed){
			return false;
		}

		$tickDiff = max(1, $currentTick - $this->lastUpdate);
		$this->lastUpdate = $currentTick;

		$hasUpdate = $this->entityBaseTick($tickDiff);

		return $hasUpdate;
	}

	/**
	 * Update the entity without calling all the functions with extra overhead
	 *
	 * ** If you want the entity to do normal entity things you'll have to override this and call the methods yourself **
	 *
	 * @param int $tickDiff
	 *
	 * @return bool
	 */
	public function entityBaseTick($tickDiff = 1) : bool {
		if(count($this->changedDataProperties) > 0){
			$this->sendData($this->hasSpawned, $this->changedDataProperties);
			$this->changedDataProperties = [];
		}

		if($this->dead === true) {
			$this->despawnFromAll();
			$this->close();
			return true;
		}

		return false;
	}

	/**
	 * Make sure updated data properties are send to players
	 *
	 * @param string $name
	 * @param int $type
	 * @param mixed $value
	 * @param bool $send
	 *
	 * @return bool
	 */
	public function setDataProperty(string $name, int $type, $value, bool $send = true) : bool {
		$this->scheduleUpdate();
		return parent::setDataProperty($name, $type, $value, $send);
	}

}