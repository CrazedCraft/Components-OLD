<?php

/**
 * DisplayLoginTitleTask.php – Components
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

namespace core\task;

use core\CorePlayer;
use core\Main;
use core\util\traits\CorePluginReference;
use core\Utils;
use pocketmine\scheduler\Task;

/**
 * Simple class used to delay the display of the login title so slower device don't miss it
 */
class DisplayLoginTitleTask extends Task {

	use CorePluginReference;

	/** @var string */
	private $uuid;

	public function __construct(Main $plugin, CorePlayer $player) {
		$this->setCore($plugin);
		$this->uuid = $player->getUniqueId()->toString();

		$plugin->getScheduler()->scheduleDelayedTask($this, 60);
	}

	public function onRun($currentTick) {
		$player = Utils::getPlayerByUUID($this->uuid);

		if($player instanceof CorePlayer) {
			$player->sendLoginTitle();
		}
	}

}