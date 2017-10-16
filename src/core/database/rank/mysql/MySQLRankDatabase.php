<?php

/**
 * MySQLRankDatabase.php – Components
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

namespace core\database\rank\mysql;

use core\database\MySQLDatabase;
use core\database\rank\mysql\task\CheckDatabaseRequest;
use core\database\rank\RankDatabase;

class MySQLRankDatabase extends MySQLDatabase implements RankDatabase {

	/**
	 * Schedule an AsyncTask to check the database's status
	 */
	public function init() {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new CheckDatabaseRequest($this));
	}

	public function load($player) {

	}

	public function add($player, $rank) {

	}

	public function remove($player, $rank) {

	}

}