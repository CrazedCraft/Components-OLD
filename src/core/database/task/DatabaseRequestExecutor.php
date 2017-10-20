<?php

/**
 * DatabaseRequestExecutor.php – Components
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

namespace core\database\task;

use core\database\exception\DatabaseException;
use core\database\MySQLCredentials;
use core\database\request\MySQLDatabaseRequest;
use core\database\result\MysqlDatabaseErrorResult;
use core\database\result\MysqlDatabaseResult;
use core\Main;
use pocketmine\Server;

/**
 * Class used to execute a batch of requests
 */
class DatabaseRequestExecutor {

	/** @var string */
	private $credentials;

	/** @var MySQLDatabaseRequest[] */
	private $requests = [];

	/** @var array */
	private $results = [];

	public function __construct(MySQLCredentials $credentials, array $requests) {
		$this->credentials = $credentials;
		$this->requests = $requests;
	}

	public function run() {
		$mysqli = $this->getMysqli();

		foreach($this->requests as $request) {
			try {
				$this->results[] = [$request, $request->execute($mysqli)];
			} catch(DatabaseException $e) {
				$this->results[] = [$request, new MysqlDatabaseErrorResult($e)];
			}
		}
	}

	public function onCompletion(Server $server) {
		$plugin = $server->getPluginManager()->getPlugin("Components");
		if($plugin instanceof Main) {
			/** @var MySQLDatabaseRequest $request */
			/** @var MysqlDatabaseResult $result */
			foreach($this->results as list($request, $result)) {
				$request->complete($plugin, $result);
			}
		}
	}

	/**
	 * Fetches a new {@link \mysqli} instance from the credentials.
	 *
	 * @return \mysqli
	 */
	public function getMysqli() : \mysqli{
		return $this->credentials->newMysqli();
	}

	public function getCredentials() : MysqlCredentials {
		return $this->credentials;
	}

}