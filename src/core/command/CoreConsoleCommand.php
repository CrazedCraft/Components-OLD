<?php

/**
 * CoreConsoleCommand.php – Components
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

namespace core\command;

use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;

abstract class CoreConsoleCommand extends CoreCommand {

	/**
	 * Internal command call
	 *
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	protected function run(CommandSender $sender, array $args) {
		if($sender instanceof ConsoleCommandSender) {
			return $this->onRun($sender, $args);
		} else {
			$sender->sendMessage($this->getCore()->getLanguageManager()->translate("MUST_BE_CONSOLE_FOR_COMMAND"));
		}
		return true;
	}

	/**
	 * Override this function to make the command do stuff
	 *
	 * @param ConsoleCommandSender $console
	 * @param array $args
	 *
	 * @return mixed
	 */
	public abstract function onRun(ConsoleCommandSender $console, array $args);

}