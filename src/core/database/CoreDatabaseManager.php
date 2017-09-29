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
 * Created on 14/07/2016 at 12:44 AM
 *
 */

namespace core\database;

class CoreDatabaseManager extends DatabaseManager {

	/** @var MySQLCredentials[] */
	private $credentialsPool = [];

	/** @var bool */
	private $closed = false;

	/**
	 * Load up all the databases
	 */
	protected function init() {

	}

	/**
	 * Add a database credentials instance into the pool
	 *
	 * @param MySQLCredentials $credentials
	 * @param string $key
	 */
	public function addCredentials(MySQLCredentials $credentials, string $key) {
		$this->credentialsPool[$key] = $credentials;
	}

	/**
	 * Get a database credentials instance from the pool
	 *
	 * @param string $key
	 *
	 * @return MySQLCredentials|null
	 */
	public function getCredentials(string $key) {
		return $this->credentialsPool[$key] ?? null;
	}

	/**
	 * Check if there is a credentials instance in the pool
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasCredentials(string $key) : bool {
		return isset($this->credentialsPool[$key]);
	}

	public function close() : bool {
		if(parent::close()) {
			unset($this->credentialsPool);
			return true;
		}
		return false;
	}

	public function isClosed() : bool {
		return $this->closed;
	}

}