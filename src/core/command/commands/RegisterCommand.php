<?php

/**
 * RegisterCommand.php – Components
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

namespace core\command\commands;

use core\command\CoreUnauthenticatedUserCommand;
use core\CorePlayer;
use core\Main;

class RegisterCommand extends CoreUnauthenticatedUserCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "register", "Register an account", "/register <password>", ["r", "claim"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(!$player->isAuthenticated()) {
			if(isset($args[0])) {
				$message = implode(" ", $args);
				$player->handleAuth($message);
			} else {
				$player->sendTranslatedMessage("COMMAND_USAGE", [$this->getUsage()], true);
			}
		} else {
			$player->sendTranslatedMessage("ALREADY_AUTHENTICATED");
		}
	}

}