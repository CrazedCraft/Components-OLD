<?php

/**
 * TestCommand.php – Components
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
 * Last modified on 15/10/2017 at 2:04 AM
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