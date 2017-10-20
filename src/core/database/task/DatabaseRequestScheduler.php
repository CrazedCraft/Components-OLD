<?php

/**
 * DatabaseRequestScheduler.php – Components
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
 * Last modified on 20/10/2017 at 5:50 PM
 *
 */

namespace core\database\task;

use core\database\CoreDatabaseManager;
use core\database\DatabaseManager;
use pocketmine\scheduler\PluginTask;

class DatabaseRequestScheduler extends PluginTask {

	/** @var CoreDatabaseManager */
	private $manager;

	public function __construct(DatabaseManager $manager) {
		$this->manager = $manager;
		parent::__construct($manager->getPlugin());

		$manager->getCore()->getServer()->getScheduler()->scheduleRepeatingTask($this, 20); // process the batch pool every second
	}

	public function getManager() : DatabaseManager {
		return $this->manager;
	}

	public function onRun($currentTick) {
		$this->manager->processPool();
	}

}