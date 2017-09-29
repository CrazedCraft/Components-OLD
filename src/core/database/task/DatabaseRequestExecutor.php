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

use core\database\exception\DatabaseRequestException;
use core\database\MySQLCredentials;
use core\database\request\MySQLDatabaseRequest;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class DatabaseRequestExecutor extends AsyncTask {

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
		try {
			foreach($requests as $request) {
				$request->execute();
			}
		} catch(DatabaseRequestException $e) {

		} finally {
			$this->setResult($requests);
		}
	}

	public function onCompletion(Server $server) {
		/** @var MySQLDatabaseRequest[] $requests */
		$requests = $this->getResult();
		foreach($requests as $request) {
			$request->complete($server);
		}
	}

	/**
	 * Fetches the {@link \mysqli} instance used in this async worker thread.
	 *
	 * @return \mysqli
	 */
	protected function getMysqli() : \mysqli{
		/** @var MysqlCredentials $credentials */
		$credentials = $this->getCredentials();
		$identifier = DatabaseRequestExecutor::getIdentifier($credentials);

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