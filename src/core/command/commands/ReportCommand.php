<?php

/**
 * ReportCommand.php – Components
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

use core\command\CoreUserCommand;
use core\CorePlayer;
use core\language\LanguageUtils;
use core\Main;
use core\Utils;

class ReportCommand extends CoreUserCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "report", "Report a player for breaking the rules", "/report <player> <reason>");
	}

	public function onRun(CorePlayer $player, array $args) {
		if(isset($args[0])) {
			$target = $this->getCore()->getServer()->getPlayer($args[0]);
			if($target instanceof CorePlayer) {
				if(isset($args[1])) {
					Utils::broadcastStaffMessage("&a" . $player->getName() . " &ehas reported &c" . $target->getName() . "&e. &6Reason&7: " . $args[1]);
					$player->sendMessage(LanguageUtils::translateColors("&6- &a" . $target->getName() . " has been reported! Thanks!"));
				} else {
					$player->sendMessage(LanguageUtils::translateColors("&6- &cYou must specify a reason for the report!"));
				}
			} else {
				$player->sendMessage(LanguageUtils::translateColors("&6- &c" . $args[0] . " is not online!"));
			}
		} else {
			$player->sendMessage(LanguageUtils::translateColors("&6- &cYou must specify a player to report!"));
		}
	}

}