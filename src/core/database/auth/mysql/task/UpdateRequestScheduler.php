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
 * Created on 14/07/2016 at 11:07 PM
 *
 */

namespace core\database\auth\mysql\task;

use core\CorePlayer;
use core\Main;
use pocketmine\scheduler\PluginTask;

class UpdateRequestScheduler extends PluginTask {

	/**
	 * UpdateRequestScheduler constructor
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		parent::__construct($plugin);
		$this->setHandler($plugin->getServer()->getScheduler()->scheduleRepeatingTask($this, 20 * 120));
	}

	/**
	 * @param $tick
	 */
	public function onRun($tick) {
		/** @var Main $plugin */
		$plugin = $this->getOwner();
		/** @var CorePlayer $p */
		foreach($this->getOwner()->getServer()->getOnlinePlayers() as $p) {
			if($p->isAuthenticated()) {
				$plugin->getDatabaseManager()->getAuthDatabase()->update($p->getName(), $p->getAuthData());
			}
		}
	}

}