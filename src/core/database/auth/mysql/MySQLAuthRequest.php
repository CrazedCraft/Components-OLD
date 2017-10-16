<?php

/**
 * MySQLAuthRequest.php – Components
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

namespace core\database\auth\mysql;

use core\database\mysql\MySQLRequest;

abstract class MySQLAuthRequest extends MySQLRequest {

	/* The key used to store a mysqli instance onto the thread */
	const AUTH_KEY = "mysqli.auth";

	/**
	 * @return mixed|\mysqli
	 */
	public function getMysqli() {
		$mysqli = $this->getFromThreadStore(self::AUTH_KEY);
		if($mysqli !== null){
			return $mysqli;
		}
		$mysqli = parent::getMysqli();
		$this->saveToThreadStore(self::AUTH_KEY, $mysqli);
		return $mysqli;
	}

}