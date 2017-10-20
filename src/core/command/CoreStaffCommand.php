<?php

/**
 * CoreStaffCommand.php – Components
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

namespace core\command;

use core\CorePlayer;
use pocketmine\command\CommandSender;

abstract class CoreStaffCommand extends CoreCommand {

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
			if($sender->isAuthenticated()) {
				if($sender->isStaff()) {
					return $this->onRun($sender, $args);
				} else {
					$sender->sendTranslatedMessage("STAFF_COMMAND_ONLY");
				}
			} else {
				$sender->sendTranslatedMessage("MUST_AUTHENTICATE_FIRST");
			}
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