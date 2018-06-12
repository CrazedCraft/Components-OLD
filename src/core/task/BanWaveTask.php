<?php

/**
 * BanWaveTask.php – Components
 *
 * Copyright (C) 2015-2018 Jack Noordhuis
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Jack Noordhuis
 *
 */

declare(strict_types=1);

namespace core\task;

use core\ban\BanEntry;
use core\CorePlayer;
use core\Main;
use core\util\traits\CorePluginReference;
use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\Task;

class BanWaveTask extends Task {

	use CorePluginReference;

	/** @var int[] */
	public $flyKicks = []; // username => # of kicks

	/** @var BanEntry[] */
	private $banQueue = [];

	public function __construct(Main $plugin) {
		$this->setCore($plugin);
		$plugin->getScheduler()->scheduleRepeatingTask($this, 9600); // flush the bans every 8 minutes
	}

	public function onRun(int $currentTick) {
		$this->flush();
	}

	/**
	 * Check if a player has been added to the ban wave
	 *
	 * @param CorePlayer $player
	 *
	 * @return bool
	 */
	public function isQueued(CorePlayer $player) {
		return isset($this->banQueue[$player->getName()]);
	}

	/**
	 * Add a ban to the ban wave queue
	 *
	 * @param BanEntry $ban
	 */
	public function queue(BanEntry $ban) {
		$this->banQueue[$ban->getUsername()] = $ban;
	}

	/**
	 * Flush the ban wave queue
	 */
	public function flush() {
		foreach($this->banQueue as $ban) {
			$ban->save();
		}
	}

}