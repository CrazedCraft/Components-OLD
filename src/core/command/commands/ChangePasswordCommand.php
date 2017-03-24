<?php

namespace core\command\commands;

use core\command\CoreUserCommand;
use core\CorePlayer;
use core\Main;
use core\Utils;

class ChangePasswordCommand extends CoreUserCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "changepassword", "Change your accounts password", "/changepassword <password>", ["chgpassword", "chgpword", "chgpass", "changepword", "changepass"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(isset($args[0])) {
			$pass = Utils::hash($player->getName(), implode(" ", $args));
			$this->getPlugin()->getDatabaseManager()->getAuthDatabase()->changePassword($player->getName(), $pass);
		} else {
			$player->sendTranslatedMessage("COMMAND_USAGE", [$this->getUsage()], true);
		}
	}

}