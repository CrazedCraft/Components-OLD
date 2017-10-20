<?php

/**
 * CorePluginReference.php – Components
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
 * Last modified on 18/10/2017 at 6:31 PM
 *
 */

namespace core\util\traits;

use core\Main;

/**
 * Simple trait for providing a reference to the cores main class
 */
trait CorePluginReference {

	/** @var Main */
	private $core;

	protected function setCore(Main $plugin) {
		$this->core = $plugin;
	}

	/**
	 * @return Main
	 */
	public function getCore() {
		return $this->core;
	}
}