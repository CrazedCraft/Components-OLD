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
 * Created on 18/09/2017 at 8:52 PM
 *
 */

namespace core\network;

use core\database\request\MySQLDatabaseRequest;
use core\database\result\MysqlDatabaseResult;

/**
 * Serializable map of the network to pass across threads
 */
class NetworkMap {

	/** @var NetworkServer */
	private $server;

	/** @var NetworkNode[] */
	private $nodes = [];

	/** @var int */
	private $onlinePlayerCount = 0;

	/** @var int */
	private $maxPlayerCount = 100;

	/**
	 * @return NetworkServer
	 */
	public function getServer() : NetworkServer {
		return $this->server;
	}

	/**
	 * @param NetworkServer $server
	 */
	public function setServer(NetworkServer $server) {
		$this->server = $server;
	}

	/**
	 * Add a node to the list
	 *
	 * @param NetworkNode $node
	 */
	public function addNode(NetworkNode $node) {
		$this->nodes[$node->getName()] = $node;
	}

	/**
	 * Try and find a network node
	 *
	 * @param string $name
	 *
	 * @return NetworkNode|null
	 */
	public function findNode(string $name) {
		if(isset($this->nodes[$name]) and $this->nodes[$name] instanceof NetworkNode) {
			return $this->nodes[$name];
		}

		return null;
	}

	/**
	 * @return NetworkNode[]
	 */
	public function getNodes() {
		return $this->nodes;
	}

	/**
	 * @param NetworkNode[] $nodes
	 */
	public function setNodes(array $nodes = []) {
		$this->nodes = $nodes;
		//foreach($nodes as $node) {
		//	$this->nodes[$node->getName()] = $node;
		//}
	}

	/**
	 * Get the total number of players on the network
	 *
	 * @return int
	 */
	public function getOnlinePlayerCount() : int {
		return $this->onlinePlayerCount;
	}

	/**
	 * Get the total number of slots for the network
	 *
	 * @return int
	 */
	public function getMaxPlayerCount() : int {
		return $this->maxPlayerCount;
	}

	/**
	 * Recalculate the global slot counts for the network
	 */
	public function recalculateSlots() {
		$online = $this->server->getOnlinePlayers();
		$max = $this->server->getMaxPlayers();
		foreach($this->nodes as $node) {
			$node->recalculateSlotCounts();
			$online += $node->getOnlinePlayers();
			$max += $node->getMaxPlayers();
		}
		$this->onlinePlayerCount = $online;
		$this->maxPlayerCount = $max;
	}

	/**
	 * Request to fetch all active servers in the network sync database
	 *
	 * @param \mysqli $db
	 *
	 * @return MysqlDatabaseResult
	 */
	public function doFetchRequest(\mysqli $db) : MysqlDatabaseResult {
		return MySQLDatabaseRequest::executeQuery($db, "SELECT id, node_id, server_motd, node, address, server_port, max_players, online_players, player_list, last_sync, online FROM network_servers WHERE NOT id = ?",
			[
				["i", $this->server->getNetworkId()],
			]
		);
	}

}