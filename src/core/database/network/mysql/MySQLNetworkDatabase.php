<?php

/**
 * MySQLNetworkDatabase.php – Components
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

namespace core\database\network\mysql;

use core\database\mysql\MySQLDatabase;
use core\database\network\mysql\task\CheckDatabaseRequest;
use core\database\network\mysql\task\FetchNodeListRequest;
use core\database\network\mysql\task\SyncRequest;
use core\database\network\NetworkDatabase;
use core\database\network\NetworkScheduler;

/**
 * MySQL implementation of the network database
 */
class MySQLNetworkDatabase extends MySQLDatabase implements NetworkDatabase {

	/** @var NetworkScheduler */
	protected $updateScheduler;

	/**
	 * Schedule an AsyncTask to check the database's status
	 */
	public function init() {
		//$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new CheckDatabaseRequest($this));
		$this->updateScheduler = new NetworkScheduler($this->getCore());
	}

	public function sync() {
		$this->getCore()->getNetworkManager()->doNetworkSync($this);
	}

	public function close() {
		if(parent::close()) {
			$this->updateScheduler->cancel();
			unset($this->updateScheduler);
		}
	}

}