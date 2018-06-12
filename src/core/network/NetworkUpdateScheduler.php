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

use pocketmine\scheduler\Task;

class NetworkUpdateScheduler extends Task {

	/** @var NetworkManager */
	private $manager;

	public function __construct(NetworkManager $manager) {
		$this->manager = $manager;
		$manager->getCore()->getScheduler()->scheduleRepeatingTask($this, $manager->getCore()->getSettings()->getNested("settings.network.sync-interval", 20));
	}

	public function onRun($currentTick) {
		$this->manager->doNetworkSync();
	}

}