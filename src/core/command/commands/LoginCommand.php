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
 * Created on 12/07/2016 at 9:13 PM
 *
 */

namespace core\command\commands;

use core\command\CoreUnauthenticatedUserCommand;
use core\CorePlayer;
use core\Main;
use core\Utils;

class LoginCommand extends CoreUnauthenticatedUserCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "login", "Login to your account", "/login <password>", ["l", "authenticate", "auth"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(!$player->isAuthenticated()) {
			if(isset($args[0])) {
				$message = implode(" ", $args);
				if(hash_equals($player->getHash(), Utils::hash(strtolower($player->getName()), $message))) {
					$player->setChatMuted(false);
					$player->setAuthenticated();
					$player->setLoginTime();
					$player->sendTranslatedMessage("LOGIN_SUCCESS");
				} else {
					$player->addLoginAttempt();
					if($player->getLoginAttempts() >= 3) {
						$player->kick($this->getPlugin()->getLanguageManager()->translateForPlayer($player, "TOO_MANY_LOGIN_ATTEMPTS"), false);
						return;
					}
					$player->sendTranslatedMessage("INCORRECT_PASSWORD", [], true);
				}
			} else {
				$player->sendTranslatedMessage("COMMAND_USAGE", [$this->getUsage()], true);
			}
		} else {
			$player->sendTranslatedMessage("ALREADY_AUTHENTICATED");
		}
	}

}