<?php

namespace core\database\request\network;

use core\database\request\MySQLDatabaseRequest;
use core\database\result\MysqlDatabaseErrorResult;
use core\database\result\MysqlDatabaseResult;
use core\database\result\MysqlDatabaseSuccessResult;
use core\Main;
use core\network\NetworkServer;

/**
 * Class for handling the updating of a network servers information
 */
class UpdateNetworkServerDatabaseRequest extends MySQLDatabaseRequest {

	/**
	 * Serialized copy of the network server
	 *
	 * @var string
	 */
	private $networkServer;

	public function __construct(NetworkServer $server) {
		$this->networkServer = serialize($server);
	}

	/**
	 * Execute the request to update the network servers data
	 *
	 * @param \mysqli $mysqli
	 *
	 * @return MysqlDatabaseResult
	 */
	public function execute(\mysqli $mysqli) : MysqlDatabaseResult {
		/** @var NetworkServer $server */
		$server = unserialize($this->networkServer);

		if($server->getNetworkId() === -1) { // this only happens when the server first starts or the network server is modified
			$exists = $server->fetchNetworkId($mysqli); // try and fetch the servers network id
		}

		$result = $server->doUpdateRequest($mysqli);

		if(isset($exists) and !$exists) { // if the server was inserted into the database and assigned a network id
			$server->fetchNetworkId($mysqli); // fetch the servers network id
		}

		$this->networkServer = serialize($server);

		return $result;
	}

	/**
	 * Finish the request back on the main thread by handling the result
	 *
	 * @param Main $plugin
	 * @param MysqlDatabaseResult $result
	 */
	public function complete(Main $plugin, MysqlDatabaseResult $result) {
		if($result instanceof MysqlDatabaseSuccessResult) { // map the database data to the player and let them know they can login
			if($result->affectedRows <= 0) { // user wasn't updated
				$plugin->getLogger()->debug("No rows were effected whilst executing network server update request!");
			} else { // user was updated
				$plugin->getNetworkManager()->getMap()->setServer(unserialize($this->networkServer));
				$plugin->getLogger()->debug("Successfully completed network server update request!");
			}
		} elseif($result instanceof MysqlDatabaseErrorResult) { // log error to the console and let the user know something went wrong
			$plugin->getLogger()->debug("Encountered error while executing network server update request!");
			$plugin->getLogger()->logException($result->getException());
		}
	}

}