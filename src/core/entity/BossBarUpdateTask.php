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
 * Created on 06/08/2017 at 8:07 PM
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