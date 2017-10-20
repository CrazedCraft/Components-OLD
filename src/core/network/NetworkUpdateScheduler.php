<?php

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