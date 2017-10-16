<?php

/**
 * MySQLCredentials.php – Components
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

namespace core\database\mysql;

class MySQLCredentials {

	/** @var string */
	public $host;

	/** @var string */
	public $user;

	/** @var string */
	public $password;

	/** @var string */
	public $name;

	/** @var int */
	public $port;

	/**
	 * Construct a new credentials class from an array
	 *
	 * @param array $array
	 *
	 * @return MySQLCredentials
	 */
	public static function fromArray(array $array) {
		$instance = new self;
		$instance->host = $array["host"];
		$instance->user = $array["user"];
		$instance->password = $array["password"];
		$instance->name = $array["name"];
		$instance->port = $array["port"];
		return $instance;
	}

	/**
	 * Get a new mysqli instance
	 *
	 * @return \mysqli
	 */
	public function getMysqli() {
		return new \mysqli($this->host, $this->user, $this->password, $this->name, $this->port/*, "/Applications/MAMP/tmp/mysql/mysql.sock"*/);
	}

}