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
 * Created on 27/08/2017 at 7:35 PM
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
			$target = $this->getPlugin()->getServer()->getPlayer($args[0]);
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