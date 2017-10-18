<?php

/**
 * MySQLBanDatabase.php – Components
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

namespace core\database\ban\mysql;

use core\database\ban\BanDatabase;
use core\database\ban\mysql\task\AddBanRequest;
use core\database\ban\mysql\task\CheckBanRequest;
use core\database\ban\mysql\task\CheckDatabaseRequest;
use core\database\ban\mysql\task\UpdateBanRequest;
use core\database\mysql\MySQLDatabase;

class MySQLBanDatabase extends MySQLDatabase implements BanDatabase {

	/**
	 * Schedule an AsyncTask to check the database's status
	 */
	public function init() {
		//$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new CheckDatabaseRequest($this));
	}

	public function check($name, $ip, $cid, $doCallback) {
		$this->getCore()->getServer()->getScheduler()->scheduleAsyncTask(new CheckBanRequest($this, $name, $ip, $cid, $doCallback));
	}

	public function add($name, $ip, $cid, $expiry, $reason, $issuer) {
		$this->getCore()->getServer()->getScheduler()->scheduleAsyncTask(new AddBanRequest($this, $name, $ip, $cid, $expiry, $reason, $issuer));
	}

	public function update($name, $ip, $cid) {
		$this->getCore()->getServer()->getScheduler()->scheduleAsyncTask(new UpdateBanRequest($this, $name, $ip, $cid));
	}

	public function remove($name, $ip, $id) {

	}

}