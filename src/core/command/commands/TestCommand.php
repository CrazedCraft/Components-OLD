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
 * Created on 05/09/2016 at 9:29 PM
 *
 */

namespace core\command\commands;

use core\command\CoreCommand;
use core\Main;
use core\task\ReportErrorTask;
use pocketmine\command\CommandSender;

class TestCommand extends CoreCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "test", "Command for testing stuff", "/test");
	}

	public function run(CommandSender $sender, array $args) {
		$this->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new ReportErrorTask("HLEFWFGE"));
	}

}