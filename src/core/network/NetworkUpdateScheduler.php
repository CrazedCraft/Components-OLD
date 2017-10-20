<?php

/**
 * NetworkUpdateScheduler.php – Components
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

namespace core\network;

use pocketmine\scheduler\PluginTask;

class NetworkUpdateScheduler extends PluginTask {

	/** @var NetworkManager */
	private $manager;

	public function __construct(NetworkManager $manager) {
		$this->manager = $manager;
		parent::__construct($plugin = $manager->getPlugin());
		$plugin->getServer()->getScheduler()->scheduleRepeatingTask($this, $plugin->getSettings()->getNested("settings.network.sync-interval", 20));
	}

	public function onRun($currentTick) {
		$this->manager->doNetworkSync();
	}

}