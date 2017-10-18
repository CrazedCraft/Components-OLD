<?php

/**
 * DatabaseManager.php – Components
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

namespace core\database;

use core\Main;
use core\util\traits\CorePluginReference;

abstract class DatabaseManager {

	use CorePluginReference;

	/** @var Main */
	private $plugin;

	public function __construct(Main $plugin) {
		$this->setCore($plugin);

		$this->init();
	}

	/**
	 * Called when the class is constructed
	 */
	protected abstract function init();

}