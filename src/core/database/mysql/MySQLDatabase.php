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
 * Created on 12/07/2016 at 9:13 PM
 *
 */

namespace core\database\mysql;

use core\database\Database;
use core\Main;

abstract class MySQLDatabase implements Database {

	/** @var MySQLCredentials */
	private $credentials;

	/** @var Main */
	private $plugin;

	/**
	 * MySQLDatabase constructor
	 *
	 * @param Main $plugin
	 * @param MySQLCredentials $credentials
	 */
	public function __construct(Main $plugin, MySQLCredentials $credentials) {
		$this->plugin = $plugin;
		$this->credentials = $credentials;
		$this->init();
	}
	
	protected abstract function init();

	/**
	 * @return Main
	 */
	public function getPlugin() {
		return $this->plugin;
	}
	
	/**
	 * @return MySQLCredentials
	 */
	public function getCredentials() {
		return $this->credentials;
	}

}