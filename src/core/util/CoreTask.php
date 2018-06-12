<?php

/**
 * CoreTask.php – Components
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
 */

namespace core\util;

use core\Main;
use pocketmine\scheduler\Task;

abstract class CoreTask extends Task {

	/** @var Main */
	private $plugin;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @return Main
	 */
	public function getOwner() {
		return $this->plugin;
	}

	/**
	 * Helper to quickly cancel a task
	 */
	public function cancel() : void {
		$this->plugin->getScheduler()->cancelTask($this->getTaskId());
	}

}