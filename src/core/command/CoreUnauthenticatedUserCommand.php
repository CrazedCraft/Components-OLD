<?php

/**
 * CoreUnauthenticatedUserCommand.php – Components
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
 * Last modified on 20/10/2017 at 5:31 PM
 *
 */

namespace core\command;

use core\CorePlayer;
use core\language\LanguageManager;
use pocketmine\command\CommandSender;

abstract class CoreUnauthenticatedUserCommand extends CoreCommand {

	/**
	 * Internal command call
	 *
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	protected function run(CommandSender $sender, array $args) {
		if($sender instanceof CorePlayer) {
			return $this->onRun($sender, $args);
		} else {
			$sender->sendMessage($this->getCore()->getLanguageManager()->translate("MUST_BE_PLAYER_FOR_COMMAND"));
		}
		return true;
	}

	/**
	 * Override this function to make the command do stuff
	 *
	 * @param CorePlayer $player
	 * @param array $args
	 *
	 * @return mixed
	 */
	public abstract function onRun(CorePlayer $player, array $args);

}