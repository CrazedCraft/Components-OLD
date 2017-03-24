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
 * Created on 14/07/2016 at 8:21 PM
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