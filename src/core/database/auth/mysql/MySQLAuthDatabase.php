<?php

/**
 * MySQLAuthDatabase.php – Components
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

use core\database\auth\AuthDatabase;
use core\database\auth\mysql\task\CheckDatabaseRequest;
use core\database\auth\mysql\task\LoginRequest;
use core\database\auth\mysql\task\RegisterRequest;
use core\database\auth\mysql\task\UpdatePasswordRequest;
use core\database\auth\mysql\task\UpdateRequest;
use core\database\auth\mysql\task\UpdateRequestScheduler;
use core\database\mysql\MySQLDatabase;

/**
 * MySQL implementation of the Auth database
 */
class MySQLAuthDatabase extends MySQLDatabase implements AuthDatabase {

	/** @var UpdateRequestScheduler */
	protected $updateScheduler;

	/**
	 * Schedule an AsyncTask to check the database's status
	 */
	public function init() {
		//$this->updateScheduler = new UpdateRequestScheduler($this->getPlugin());
	}

	public function register($name, $hash, $email) {
		$this->getCore()->getServer()->getScheduler()->scheduleAsyncTask(new RegisterRequest($this, $name, $hash, $email));
	}

	public function login($name) {
		$this->getCore()->getServer()->getScheduler()->scheduleAsyncTask(new LoginRequest($this, $name));
	}

	public function update($name, array $args) {
		$this->getCore()->getServer()->getScheduler()->scheduleAsyncTask(new UpdateRequest($this, $name, $args));
	}

	public function changePassword($name, $hash) {
		$this->getCore()->getServer()->getScheduler()->scheduleAsyncTask(new UpdatePasswordRequest($this, $name, $hash));
	}

	public function unregister($name) {

	}

	public function close() {
	}

}