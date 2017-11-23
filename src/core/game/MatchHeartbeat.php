<?php

/**
 * MatchHeartbeat.php – Components
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

namespace core\game;

use pocketmine\scheduler\PluginTask;

class MatchHeartbeat extends PluginTask {

	/** @var MatchManager */
	private $manager;

	public function __construct(MatchManager $manager, int $ticks = 20) {
		$this->manager = $manager;
		parent::__construct($manager->getCore());

		$manager->getCore()->getServer()->getScheduler()->scheduleRepeatingTask($this, $ticks);
	}

	/**
	 * @return MatchManager
	 */
	public function getManager() {
		return $this->manager;
	}

	/**
	 * Ticks all the match manager
	 *
	 * @param $currentTick
	 */
	public function onRun($currentTick) {
		$this->manager->tick($currentTick);
	}

}