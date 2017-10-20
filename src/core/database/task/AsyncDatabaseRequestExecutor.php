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
 * Created on 29/09/2017 at 9:50 PM
 *
 */

namespace core\database\task;

use core\database\exception\DatabaseException;
use core\database\exception\DatabaseRequestException;
use core\database\MySQLCredentials;
use core\database\request\MySQLDatabaseRequest;
use core\database\result\MysqlDatabaseErrorResult;
use core\database\result\MysqlDatabaseResult;
use core\Main;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncDatabaseRequestExecutor extends AsyncTask {

	/** @var string */
	private $credentials;

	/** @var string */
	private $requests;

	public function __construct(MySQLCredentials $credentials, array $requests) {
		$this->credentials = serialize($credentials);
		$this->requests = serialize($requests);
	}

	public function onRun() {
		/** @var MySQLDatabaseRequest[] $requests */
		$requests = unserialize($this->requests);

		$results = [];
		$mysqli = $this->getMysqli();

		foreach($requests as $request) {
			try {
				$results[] = [$request, $request->execute($mysqli)];
			} catch(DatabaseException $e) {
				$results[] = [$request, new MysqlDatabaseErrorResult($e)];
			}
		}

		$this->setResult($results);
	}

	public function onCompletion(Server $server) {
		$plugin = $server->getPluginManager()->getPlugin("Components");
		if($plugin instanceof Main and $plugin->isEnabled()) {
			/** @var MySQLDatabaseRequest[] $requests */
			$requests = $this->getResult();

			/** @var MySQLDatabaseRequest $request */
			/** @var MysqlDatabaseResult $result */
			foreach($requests as list($request, $result)) {
				$request->complete($plugin, $result);
			}
		}
	}

	/**
	 * Fetches the {@link \mysqli} instance used in this async worker thread.
	 *
	 * @return \mysqli
	 */
	public function getMysqli() : \mysqli{
		/** @var MysqlCredentials $credentials */
		$credentials = $this->getCredentials();
		$identifier = AsyncDatabaseRequestExecutor::getIdentifier($credentials);

		$mysqli = $this->getFromThreadStore($identifier);
		if(!($mysqli instanceof \mysqli)){
			$mysqli = $credentials->newMysqli();
			$this->saveToThreadStore($identifier, $mysqli);
		}
		return $mysqli;
	}

	public function getCredentials() : MysqlCredentials {
		return unserialize($this->credentials);
	}

	public static function getIdentifier(MysqlCredentials $credentials) : string {
		return "components.database.request.executor.mysql.pool.$credentials";
	}

}