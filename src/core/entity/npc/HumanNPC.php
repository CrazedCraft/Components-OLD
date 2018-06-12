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
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\Player;
use pocketmine\plugin\PluginException;

abstract class HumanNPC extends Human implements BaseNPC, ChunkLoader {

	/** @var Main */
	private $core;

	/** @var string */
	protected $name;

	public $active = true;

	/**
	 * @return Main
	 */
	public function getCore() {
		return $this->core;
	}

	/**
	 * Spawn the NPC to a player
	 *
	 * @param Player $player
	 */
	public function spawnTo(Player $player) : void {
		if(!isset($this->hasSpawned[$player->getLoaderId()]) and $this->chunk !== \null and isset($player->usedChunks[((($this->chunk->getX()) & 0xFFFFFFFF) << 32) | (( $this->chunk->getZ()) & 0xFFFFFFFF)])) {
			$this->hasSpawned[$player->getId()] = $player;

			$pk = new PlayerListPacket();
			$pk->type = PlayerListPacket::TYPE_ADD;
			$pk->entries[] = PlayerListEntry::createAdditionEntry($this->getUniqueId(), $this->getId(), "", "", 0, $this->skin, "", "");
			$player->dataPacket($pk);

			$this->sendSpawnPacket($player);

			$this->armorInventory->sendContents($player);
		}
	}

	/**
	 * Ensure the NPC doesn't take damage
	 *
	 * @param EntityDamageEvent $source
	 */
	public function attack(EntityDamageEvent $source) : void {
		$source->setCancelled(true);
	}

	/**
	 * Make sure the npc doesn't get saved
	 */
	public function saveNBT() : void {
		return;
	}

	/**
	 * Same save characteristics as a player
	 */
	public function getSaveId() : string {
		return "Human";
	}

	/**
	 * Set the NPC's real name to the one given when the entity is spawned
	 */
	public function initEntity() : void {
		parent::initEntity();
		$plugin = $this->server->getPluginManager()->getPlugin("Components");
		if($plugin instanceof Main and $plugin->isEnabled()){
			$this->core = $plugin;
		} else {
			throw new PluginException("Core plugin isn't loaded!");
		}
		$this->name = $this->getNameTag();
		$this->getLevel()->registerChunkLoader($this, $this->chunk->getX(), $this->chunk->getZ());
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
	public function getName() : string {
		return $this->name;
	}

	/**
	 * Make sure nothing drops in case the NPC dies
	 *
	 * @return array
	 */
	public function getDrops() : array {
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
	 * @param CompoundTag $nbt
	 *
	 * @return HumanNPC|null
	 */
	public static function spawn($shortName, Location $pos, $name, $skin, $skinName, CompoundTag $nbt) {
		$entity = Entity::createEntity($shortName, $pos->getLevel(), $nbt);
		if($entity instanceof HumanNPC) {
			$entity->setSkin(new Skin($skinName, $skin));
			$entity->setName($name);
			$entity->setNameTag($entity->getName());
			$entity->setPositionAndRotation($pos, $pos->yaw, $pos->pitch);
			return $entity;
		} else {
			$entity->kill();
		}
		return null;
	}

	public function kill() : void {
		$this->active = false;
		parent::kill();
	}

	public function getLoaderId(): int {
		return $this->getId();
	}

	public function isLoaderActive(): bool {
		return $this->active;
	}

	public function onChunkChanged(Chunk $chunk) {
		// TODO: Implement onChunkChanged() method.
	}

	public function onChunkLoaded(Chunk $chunk) {
		// TODO: Implement onChunkLoaded() method.
	}

	public function onBlockChanged(Vector3 $block) {
		// TODO: Implement onBlockChanged() method.
	}

	public function onChunkPopulated(Chunk $chunk) {
		// TODO: Implement onChunkPopulated() method.
	}

	public function onChunkUnloaded(Chunk $chunk) {
		// TODO: Implement onChunkUnloaded() method.
	}

	//
	// The below overrides were for optimization purposes, should the server lag too much they should be re-enabled/updated.
	//
	///**
	// * Update the entity without calling all the functions with extra overhead
	// *
	// * ** If you want the entity to do normal entity things you'll have to override this and call the methods yourself **
	// *
	// * @param $currentTick
	// *
	// * @return bool
	// */
	//public function onUpdate(int $currentTick) : bool {
	//	if($this->closed){
	//		return false;
	//	}
	//
	//	$tickDiff = max(1, $currentTick - $this->lastUpdate);
	//	$this->lastUpdate = $currentTick;
	//
	//	$hasUpdate = $this->entityBaseTick($tickDiff);
	//
	//	return $hasUpdate;
	//}
	//
	///**
	// * Update the entity without calling all the functions with extra overhead
	// *
	// * ** If you want the entity to do normal entity things you'll have to override this and call the methods yourself **
	// *
	// * @param int $tickDiff
	// *
	// * @return bool
	// */
	//public function entityBaseTick($tickDiff = 1) : bool {
	//	if(count($this->changedDataProperties) > 0){
	//		$this->sendData($this->hasSpawned, $this->changedDataProperties);
	//		$this->changedDataProperties = [];
	//	}
	//
	//	if($this->dead === true) {
	//		$this->despawnFromAll();
	//		$this->close();
	//		return true;
	//	}
	//
	//	return false;
	//}
	//
	///**
	// * Make sure updated data properties are send to players
	// *
	// * @param string $name
	// * @param int $type
	// * @param mixed $value
	// * @param bool $send
	// *
	// * @return bool
	// */
	//public function setDataProperty(string $name, int $type, $value, bool $send = true) : bool {
	//	$this->scheduleUpdate();
	//	return parent::setDataProperty($name, $type, $value, $send);
	//}

}