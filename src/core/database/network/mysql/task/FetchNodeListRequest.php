<?php

/**
 * FetchNodeListRequest.php – Components
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
 * Last modified on 15/10/2017 at 2:04 AM
 *
 */

namespace core\database\network\mysql\task;

use core\database\network\mysql\MySQLNetworkDatabase;
use core\database\network\mysql\MySQLNetworkRequest;
use core\Main;
use core\network\NetworkMap;
use core\network\NetworkNode;
use core\network\NetworkServer;
use pocketmine\Server;
use pocketmine\utils\PluginException;

class FetchNodeListRequest extends MySQLNetworkRequest {

	/** @var string */
	private $map;

	public function __construct(MySQLNetworkDatabase $database, NetworkMap $map) {
		parent::__construct($database->getCredentials());
		$this->map = serialize($map);
	}

	public function onRun() {
		$mysqli = $this->getMysqli();
		$map = unserialize($this->map);
		/** @var NetworkServer $server */
		$server = $map->getServer();
		$result = $mysqli->query("SELECT id FROM network_servers WHERE node = '{$server->getNode()}' AND node_id = {$server->getId()}");
		if($result instanceof \mysqli_result) {
			$data = $result->fetch_assoc();
			$result->free();
			if(($id = $data["id"]) !== -1) {
				$server->setNetworkId($data["id"]);
			}
		}

		if($server->getNetworkId() === -1) { // if the server hasn't been added to the database before
			$server->doInsertQuery($mysqli);
		}

		$result = $mysqli->query("SELECT node_name, node_display FROM network_nodes WHERE max_servers > 0");
		if($result instanceof \mysqli_result) {
			$nodes = [];
			while(is_array($row = $result->fetch_assoc())) {
				$nodes[$row["node_name"]] = new NetworkNode($row["node_name"], $row["node_display"]);
			}
			$result->free();
			$map->setNodes($nodes);
			$this->setResult([self::SUCCESS, $map]);
			return;
		}
		$this->setResult([self::MYSQLI_ERROR, []]);
	}

	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server) {
		$plugin = $this->getCore($server);
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$result = $this->getResult();
			switch((is_array($result) ? $result[0] : $result)) {
				case self::SUCCESS:
					$plugin->getNetworkManager()->setMap($result[1]);
					$plugin->getNetworkManager()->hasNodes = true;
					$server->getLogger()->debug("Successfully completed FetchNodeListRequest!");
					return;
				case self::MYSQLI_ERROR:
					return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete FetchNodeListRequest while Components plugin isn't enabled!");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}


}