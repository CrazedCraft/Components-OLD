<?php

/**
 * NetworkManager.php – Components
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
 * Last modified on 20/10/2017 at 5:50 PM
 *
 */

namespace core\network;

use core\database\request\network\FetchNetworkDatabaseRequest;
use core\database\request\network\UpdateNetworkServerDatabaseRequest;
use core\Main;
use core\util\traits\CorePluginReference;

class NetworkManager {

	use CorePluginReference;

	/** @var NetworkMap */
	private $map;

	/** @var bool */
	private $mapLocked = false;

	/** @var bool */
	private $syncScheduler = false;

	/** @var bool */
	private $closed = false;

	public function __construct(Main $plugin) {
		$this->setCore($plugin);

		$settings = $plugin->getSettings();
		$server = $plugin->getServer();
		$this->map = new NetworkMap();
		$this->map->setServer(new NetworkServer($settings->getNested("settings.network.id"), "CrazedCraft: Server", $settings->getNested("settings.network.node"), $server->getIp(), $server->getPort(), count($server->getOnlinePlayers()), $server->getMaxPlayers(), [], time(), true));
		$this->syncScheduler = new NetworkUpdateScheduler($this);
	}

	/**
	 * @return NetworkServer
	 */
	public function getServer() {
		return $this->map->getServer();
	}

	/**
	 * @return NetworkMap
	 */
	public function getMap() : NetworkMap {
		return $this->map;
	}

	/**
	 * Check if the network map is locked
	 *
	 * @return bool
	 */
	public function isMapLocked() : bool {
		return $this->mapLocked;
	}

	/**
	 * @param NetworkMap $map
	 */
	public function setMap(NetworkMap $map) {
		$this->map = $map;
	}

	/**
	 * Lock the network map to prevent it being modified
	 */
	public function lockMap() {
		$this->mapLocked = true;
	}

	/**
	 * Unlock the network map to allow modifications
	 */
	public function unlockMap() {
		$this->mapLocked = false;
	}

	/**
	 * @return NetworkNode[]
	 */
	public function getNodes() {
		return $this->map->getNodes();
	}

	/**
	 * Get the total number of players on the network
	 *
	 * @return int
	 */
	public function getOnlinePlayers() : int {
		return $this->map->getOnlinePlayerCount();
	}

	/**
	 * Get the total number of slots for the network
	 *
	 * @return int
	 */
	public function getMaxPlayers() : int {
		return $this->map->getMaxPlayerCount();
	}

	/**
	 * Set the available nodes
	 *
	 * ** NOTE: This will remove all servers from the node lists until the next network sync **
	 *
	 * @param NetworkNode[] $nodes
	 */
	public function setNodes(array $nodes) {
		$this->map->setNodes($nodes);
	}

	public function doNetworkSync() {
		if(!$this->isMapLocked()) {
			$this->map->getServer()->setPlayerStatus(count($this->getCore()->getServer()->getOnlinePlayers()), $this->getCore()->getServer()->getMaxPlayers()); // set this servers player count before a network sync
			$this->lockMap();
			$this->getCore()->getDatabaseManager()->pushToPool(new UpdateNetworkServerDatabaseRequest($this->map->getServer()));
			$this->getCore()->getDatabaseManager()->pushToPool(new FetchNetworkDatabaseRequest($this->map));
		}
	}

	/**
	 * Recalculate the global slot counts for the network
	 */
	public function recalculateSlots() {
		$this->map->recalculateSlots();
	}

	/**
	 * Dump all data safely to prevent memory leaks and shutdown hold ups
	 */
	public function close() {
		if(!$this->closed) {
			$this->closed = true;
			unset($this->map);
		}
	}

}