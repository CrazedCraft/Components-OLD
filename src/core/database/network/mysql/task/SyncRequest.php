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
 * Created on 16/04/2017 at 12:09 AM
 *
 */

namespace core\database\network\mysql\task;

use core\database\network\mysql\MySQLNetworkDatabase;
use core\database\network\mysql\MySQLNetworkRequest;
use core\network\NetworkMap;
use core\network\NetworkNode;
use core\network\NetworkServer;
use core\Main;
use pocketmine\Server;
use pocketmine\utils\PluginException;

class SyncRequest extends MySQLNetworkRequest {

	/** @var string */
	private $map;

	public function __construct(MySQLNetworkDatabase $database, NetworkMap $map) {
		parent::__construct($database->getCredentials());
		$this->map = serialize($map);
	}

	public function onRun() {
		try {
			$mysqli = $this->getMysqli();
			$map = unserialize($this->map);
			if(!$this->updateServer($mysqli, $map)) {
				$this->setResult(self::CONNECTION_ERROR);
				return;
			}
			$this->fetchServers($mysqli, $map);
			$map->recalculateSlots();
			$this->setResult([self::SUCCESS, $map]);
		} catch(\Exception $e) {
			$this->getLogger()->warning("Encountered error whilst trying to perform network sync");
			$this->getLogger()->logException($e);
		}
	}

	public function updateServer(\mysqli $mysqli, NetworkMap $map) {
		try {
			$result = $map->getServer()->doUpdateQuery($mysqli);
			// TODO: Check for mysqli errors
		} catch(\Exception $e) {
			$this->getLogger()->warning("Could not update server for network sync");
			$this->getLogger()->logException($e);
		}
		return true;
	}

	public function fetchServers(\mysqli $mysqli, NetworkMap $map) {
		try {
			$result = $map->doFetchRequest($mysqli);
			// TODO: Check for mysqli errors
			if($result instanceof \mysqli_stmt) {
				$result = $result->get_result();
				while(is_array($row = $result->fetch_assoc())) {
					$node = $map->findNode($row["node"]);
					if($node instanceof NetworkNode) {
						$server = $node->findServer($row["node_id"]);
						if($server instanceof NetworkServer) {
							if($server->isOnline()) {
								$server->setPlayerStatus($row["online_players"], $row["max_players"]);
								$server->setOnline($row["online_players"]);
							} else { // address, motd or port could be different from last time it was online
								// TODO: Fix this hack and add ability to fully update the status of a server
								$node->removeServer($server);
								$node->addServer(new NetworkServer($row["node_id"], $row["server_motd"], $row["node"], $row["address"], $row["server_port"], $row["max_players"], $row["online_players"], json_decode($row["player_list"], true), $row["last_sync"], (bool) $row["online"]));
							}
						} else {
							$node->addServer(new NetworkServer($row["node_id"], $row["server_motd"], $row["node"], $row["address"], $row["server_port"], $row["max_players"], $row["online_players"], json_decode($row["player_list"], true), $row["last_sync"], (bool) $row["online"]));
						}
					} else {
						// TODO: Add support for new nodes added on the fly
						$this->getLogger()->warning("Could not find network node! Node name: {$row["node"]}");
					}
				}
				$result->free();
			}
		} catch(\Exception $e) {
			$this->getLogger()->warning("Could not fetch servers for network sync");
			$this->getLogger()->logException($e);
		}
		return $map;
	}

	public function onCompletion(Server $server) {
		$plugin = $this->getCore($server);
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$result = $this->getResult();
			if($result[1] instanceof NetworkMap) {
				switch($result[0]) {
					case self::SUCCESS:
						$plugin->getNetworkManager()->setMap($result[1]);
						$server->getLogger()->debug("Successfully completed SyncRequest!");
						return;
				}
			} else {
				$server->getLogger()->debug("Network map has disappeared while trying to complete sync request!!");
				throw new PluginException("Network map has disappeared!");
			}
		} else {
			$server->getLogger()->debug("Attempted to complete SyncRequest while Components plugin isn't enabled!");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}