<?php

/**
 * MySQLBanRequest.php – Components
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

use core\database\mysql\MySQLRequest;

abstract class MySQLBanRequest extends MySQLRequest {

	/* The key used to store a mysqli instance onto the thread */
	const BANS_KEY = "mysqli.bans";

	/**
	 * @return mixed|\mysqli
	 */
	public function getMysqli() {
		$mysqli = $this->getFromThreadStore(self::BANS_KEY);
		if($mysqli !== null){
			return $mysqli;
		}
		$mysqli = parent::getMysqli();
		$this->saveToThreadStore(self::BANS_KEY, $mysqli);
		return $mysqli;
	}

}