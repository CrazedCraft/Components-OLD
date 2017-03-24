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
 * Created on 24/03/2017 at 4:48 PM
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
			$sender->sendMessage($this->getPlugin()->getLanguageManager()->translate("MUST_BE_PLAYER_FOR_COMMAND"));
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