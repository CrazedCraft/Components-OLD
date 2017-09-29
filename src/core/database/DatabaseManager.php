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
 * Created on 14/07/2016 at 12:39 AM
 *
 */

namespace core\database;

use core\Main;

abstract class DatabaseManager {

	/** @var Main */
	private $plugin;

	/** @var bool */
	private $closed = false;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->init();
	}

	/**
	 * @return Main
	 */
	public function getPlugin() {
		return $this->plugin;
	}

	/**
	 * Called when the class is constructed
	 */
	protected abstract function init();

	/**
	 * @return bool  Returns true if the manager was closed successfully
	 */
	public function close() : bool {
		if(!$this->closed) {
			$this->closed = true;
			unset($this->plugin);
			return true;
		}
		return false;
	}

	public function isClosed() : bool {
		return $this->closed;
	}

}