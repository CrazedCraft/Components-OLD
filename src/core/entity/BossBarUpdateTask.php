<?php

/**
 * BossBarUpdateTask.php – Components
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

namespace core\entity;

use core\CorePlayer;
use core\Main;
use core\Utils;
use pocketmine\scheduler\PluginTask;

class BossBarUpdateTask extends PluginTask {

	/** @var BossBar */
	private $bossBar = null;

	public function __construct(Main $plugin, BossBar $bossBar) {
		$this->bossBar = $bossBar;
		parent::__construct($plugin);
		$plugin->getServer()->getScheduler()->scheduleRepeatingTask($this, 20 * 5);
	}

	public function onRun($currentTick) {
		foreach($this->bossBar->subscribed as $id => $uuid) {
			$p = Utils::getPlayerByUUID($uuid);
			if($p instanceof CorePlayer) {
				$this->bossBar->moveFor($p);
			}
		}
	}

}