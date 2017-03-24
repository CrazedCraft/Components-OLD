<?php


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