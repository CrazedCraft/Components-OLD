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
 * Created on 29/09/2017 at 9:26 PM
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

		$manager->getPlugin()->getServer()->getScheduler()->scheduleRepeatingTask($this, 20); // process the batch pool every second
	}

	public function getManager() : DatabaseManager {
		return $this->manager;
	}

	public function onRun($currentTick) {
		$this->manager->processPool();
	}

}