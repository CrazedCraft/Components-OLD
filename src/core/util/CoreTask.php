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

use pocketmine\scheduler\PluginTask;

abstract class CoreTask extends PluginTask {

	/**
	 * Helper to quickly cancel a task
	 */
	public function cancel() : void {
		$this->getOwner()->getServer()->getScheduler()->cancelTask($this->getTaskId());
	}

}