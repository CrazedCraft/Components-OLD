<?php

/**
 * CrazedCraft Network Components
 *
 * Copyright (C) 2016 CrazedCraft Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author JackNoordhuis
 *
 * Created on 15/04/2017 at 12:41 AM
 *
 */

namespace core\network;

/**
 * A class that represents the current status of another server on the network
 */
class NetworkServer {

	/** @var int */
	private $networkId = -1;

	/** @var int */
	private $id = 0;

	/** @var string */
	private $name = "Hub-1";

	/** @var string */
	private $node;

	/** @var string */
	private $host = "";

	/** @var int */
	private $port = 19132;

	/** @var int */
	private $onlinePlayers = 0;

	/** @var int */
	private $maxPlayers = 100;

	/** @var bool */
	private $lastOnline = 0;

	/** @var bool */
	private $online = false;

	/** @var bool */
	private $closed = false;

	public function __construct(int $id, string $name, string $node, string $host, int $port, int $maxPlayers, int $onlinePlayers, array $playerList, int $lastSync, bool $online, int $networkId = -1) {
		$this->id = $id;
		$this->name = $name;
		$this->node = $node;
		$this->host = $host;
		$this->port = $port;
		$this->setPlayerStatus($onlinePlayers, $maxPlayers);
		$this->lastOnline = $lastSync;
		$this->online = $online;
		$this->networkId = -1;
	}

	/**
	 * @return int
	 */
	public function getNetworkId() : int {
		return $this->networkId;
	}

	/**
	 * @param int $value
	 */
	public function setNetworkId(int $value) {
		$this->networkId = $value;
	}

	/**
	 * Update the online and max max player count of the server
	 *
	 * @param int $online
	 * @param int $max
	 */
	public function setPlayerStatus(int $online, int $max) {
		$this->onlinePlayers = $online;
		$this->maxPlayers = $max;
	}

	/**
	 * Get the node ID of the server
	 *
	 * @return int
	 */
	public function getId() : int {
		return $this->id;
	}

	/**
	 * Get the MOTD of the server
	 *
	 * @return string
	 */
	public function getName() : string {
		return $this->name;
	}

	/**
	 * Get the node string of the server
	 *
	 * @return string
	 */
	public function getNode() : string {
		return $this->node;
	}

	/**
	 * Get the IP of the server
	 *
	 * @return string
	 */
	public function getHost() : string {
		return $this->host;
	}

	/**
	 * Get the port of the server
	 *
	 * @return int
	 */
	public function getPort() : int {
		return $this->port;
	}

	/**
	 * Check if the server is available to join
	 *
	 * @return bool
	 */
	public function isAvailable()  : bool {
		return $this->online and $this->onlinePlayers < $this->maxPlayers and time() - $this->lastOnline <= 15;
	}

	/**
	 * Get the online player count of the server
	 *
	 * @return int
	 */
	public function getOnlinePlayers() : int {
		return $this->onlinePlayers;
	}

	/**
	 * Get the max player count of the server
	 *
	 * @return int
	 */
	public function getMaxPlayers() : int {
		return $this->maxPlayers;
	}

	/**
	 * Get the timestamp of when the server was last synced
	 *
	 * @return mixed
	 */
	public function getLastSyncTime() : int {
		return $this->lastOnline;
	}

	/**
	 * Get the online status of the server
	 *
	 * @return bool
	 */
	public function isOnline() {
		return $this->online;
	}

	/**
	 * Set the online status of the server
	 *
	 * @param bool $value
	 */
	public function setOnline(bool $value = true) {
		$this->online = $value;
	}

	/**
	 * Get the query to update this server in the network database
	 *
	 * @param \mysqli $db
	 *
	 * @return \mysqli_stmt
	 */
	public function doUpdateQuery(\mysqli $db) : \mysqli_stmt {
		$stmt = $db->prepare("INSERT INTO network_servers (id, server_motd, node, node_id, address, server_port, online_players, max_players, player_list, last_sync, online) VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE id = ?, server_motd = ?, node = ?, node_id = ?, address = ?, server_port = ?, online_players = ?, max_players = ?, player_list = ?, last_sync = ?, online = ?");
		$params = [$this->networkId, $this->getName(), $this->getNode(), $this->getId(), $this->getHost(), $this->getPort(), $this->getOnlinePlayers(), $this->getMaxPlayers(), "{}", time(), $this->isOnline() ? 1 : 0];
		$stmt->bind_param(str_repeat("issisiiisii", 2), ...$params, ...$params);
		$stmt->execute();
		return $stmt;
	}

	/**
	 * Dump all data safely to prevent memory leaks and shutdown hold ups
	 */
	public function close() {
		if(!$this->closed) {
			$this->closed = true;
			unset($this->id, $this->name, $this->node, $this->host, $this->port, $this->onlinePlayers, $this->maxPlayers, $this->lastOnline);
		}
	}

}