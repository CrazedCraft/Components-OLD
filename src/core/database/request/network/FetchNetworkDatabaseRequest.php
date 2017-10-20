<?php

namespace core\database\request\network;

use core\database\request\MySQLDatabaseRequest;
use core\database\result\MysqlDatabaseErrorResult;
use core\database\result\MysqlDatabaseResult;
use core\database\result\MysqlDatabaseSelectResult;
use core\Main;
use core\network\NetworkMap;
use core\network\NetworkNode;
use core\network\NetworkServer;
use pocketmine\utils\MainLogger;

/**
 * Class for handling the fetching of network information
 */
class FetchNetworkDatabaseRequest extends MySQLDatabaseRequest {

	/**
	 * Serialized copy of the network map
	 *
	 * @var string
	 */
	private $networkMap;

	public function __construct(NetworkMap $map) {
		$this->networkMap = serialize($map);
	}

	/**
	 * Execute the fetch request to fetch the network data
	 *
	 * @param \mysqli $mysqli
	 *
	 * @return MysqlDatabaseResult
	 */
	public function execute(\mysqli $mysqli) : MysqlDatabaseResult {
		/** @var NetworkMap $map */
		$map = unserialize($this->networkMap);
		$result = $map->doFetchRequest($mysqli);

		if($result instanceof MysqlDatabaseSelectResult) {
			$result->fixTypes([
				"id" => MysqlDatabaseSelectResult::TYPE_INT,
				"server_motd" => MysqlDatabaseSelectResult::TYPE_STRING,
				"node_id" => MysqlDatabaseSelectResult::TYPE_INT,
				"node" => MysqlDatabaseSelectResult::TYPE_STRING,
				"address" => MysqlDatabaseSelectResult::TYPE_STRING,
				"server_port" => MysqlDatabaseSelectResult::TYPE_INT,
				"online_players" => MysqlDatabaseSelectResult::TYPE_INT,
				"max_players" => MysqlDatabaseSelectResult::TYPE_INT,
				"player_list" => MysqlDatabaseSelectResult::TYPE_STRING,
				"online" => MysqlDatabaseSelectResult::TYPE_BOOL,
				"last_sync" => MysqlDatabaseSelectResult::TYPE_INT,
			]); // ensure the result has the correct types

			foreach($result->rows as $serverData) {
				try {
					$node = $map->findNode($serverData["node"]);
					if(!($node instanceof NetworkNode)) {
						$nodeResult = MySQLDatabaseRequest::executeQuery($mysqli, "SELECT `node_name` AS `name`, `node_display` AS `display` FROM `network_nodes` WHERE node_name = ?", [["s", $serverData["node"]]]);
						if($nodeResult instanceof MysqlDatabaseSelectResult) {
							$nodeResult->fixTypes([
								"name" => MysqlDatabaseSelectResult::TYPE_STRING,
								"display" => MysqlDatabaseSelectResult::TYPE_STRING,
							]); // ensure the result has the correct types
							$nodeData = $nodeResult->rows[0];
							$map->addNode($node = new NetworkNode($nodeData["name"], $nodeData["display"]));
						} else {
							MainLogger::getLogger()->debug("Error while fetching node for network server: {$serverData["node"]}-{$serverData["node_id"]}");
							continue;
						}
					}

					$server = $node->findServer($serverData["node_id"]);
					if($server instanceof NetworkServer) {
						$server->updateFromRow($serverData);
					} else {
						$node->addServer(NetworkServer::fromRow($serverData));
					}
				} catch(\Throwable $e) {
					MainLogger::getLogger()->debug("Error while fetching a network server!");
					MainLogger::getLogger()->logException($e);
				}
			}

			$map->recalculateSlots();
			$this->networkMap = serialize($map);
		}

		return $result;
	}

	/**
	 * Finish the request back on the main thread by handling the result
	 *
	 * @param Main $plugin
	 * @param MysqlDatabaseResult $result
	 */
	public function complete(Main $plugin, MysqlDatabaseResult $result) {
		$server = $plugin->getServer();
		$map = unserialize($this->networkMap);
		if($map instanceof NetworkMap) {
			if($result instanceof MysqlDatabaseSelectResult) { // map the database data to the player and let them know they can login
				$plugin->getNetworkManager()->setMap($map);
				$plugin->getNetworkManager()->unlockMap();

				$plugin->getLogger()->debug("Successfully completed fetch network request!");
			} elseif($result instanceof MysqlDatabaseErrorResult) { // log error to the console and let the user know something went wrong
				$plugin->getLogger()->debug("Encountered error while executing fetch network request!");
				$plugin->getLogger()->logException($result->getException());
			}
		} else {
			$server->getLogger()->debug("Network map has disappeared while trying to complete sync request!!");
		}
	}

}