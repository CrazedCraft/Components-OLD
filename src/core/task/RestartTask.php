<?php

/**
 * RestartTask.php – Components
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
use core\language\LanguageManager;
use core\Main;
use core\util\traits\CorePluginReference;
use pocketmine\scheduler\Task;

class RestartTask extends Task {
	use CorePluginReference;

	/** @var int */
	private $time = 3600;

	/** @var bool */
	private $shutdown = false;

	/**
	 * RestartTask constructor
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		$this->setCore($plugin);

		$this->time = (int) $plugin->getSettings()->getNested("settings.restart-time");
		$plugin->getScheduler()->scheduleRepeatingTask($this, 20);
	}

	public function onRun($tick) {
		if($this->time >= 0) {
			if($this->time <= 10 and $this->time >= 1) {
				$this->getCore()->getServer()->broadcastTip(LanguageManager::getInstance()->translate("SECONDS_UNTIL_RESTART", "en", [$this->time]));
			} elseif($this->time === 60) {
				/** @var CorePlayer $p */
				foreach($this->getCore()->getServer()->getOnlinePlayers() as $p) {
					$p->sendTranslatedMessage("ONE_MINUTE_UNTIL_RESTART", [], true);
				}
			} elseif($this->time <= 0) {
				if(!$this->shutdown) {
					$this->shutdown = true;
					$this->getCore()->getServer()->forceShutdown();
				}
				return;
			}
			$this->time--;
		}
	}

}