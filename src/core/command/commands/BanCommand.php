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
 * Created on 24/03/2017 at 4:44 PM
 *
 */

namespace core\command\commands;

use core\command\CoreStaffCommand;
use core\CorePlayer;
use core\Main;
use pocketmine\command\Command;

class BanCommand extends CoreStaffCommand {

	public function __construct(Main $plugin) {
		$map = $plugin->getServer()->getCommandMap();
		$oldCommand = $map->getCommand("ban");
		if($oldCommand instanceof Command) {
			$oldCommand->setLabel("ban_disabled");
			$oldCommand->unregister($map);
		}
		parent::__construct($plugin, "ban", "Ban a player from the network", "/ban <player> <reason>", []);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(isset($args[1])) {
			$target = $this->getPlugin()->getServer()->getPlayer($name = array_shift($args));
			if($target instanceof CorePlayer) {
				if($target->hasPreviousNetworkBan()) {
					$this->getPlugin()->getDatabaseManager()->getBanDatabase()->add($target->getName(), $target->getAddress(), $target->getClientId(), 0, implode(" ", $args), $player->getName());
					$player->sendTranslatedMessage("BAN_SUCCESS", [$name, $player->getCore()->getLanguageManager()->translateForPlayer($player, "BAN_DURATION_FOREVER")]);
				} else {
					$this->getPlugin()->getDatabaseManager()->getBanDatabase()->add($target->getName(), $target->getAddress(), $target->getClientId(), strtotime("+7 days"), implode(" ", $args), $player->getName());
					$player->sendTranslatedMessage("BAN_SUCCESS", [$name, $player->getCore()->getLanguageManager()->translateForPlayer($player, "BAN_DURATION_DAYS", ["7"])]);
				}
			} else {
				$this->getPlugin()->getDatabaseManager()->getBanDatabase()->add($name, "", "", strtotime("+7 days"), implode(" ", $args), $player->getName());
				$player->sendTranslatedMessage("BAN_SUCCESS", [$name, $player->getCore()->getLanguageManager()->translateForPlayer($player, "BAN_DURATION_DAYS", ["7"])]);
			}
		} else {
			$player->sendTranslatedMessage("COMMAND_USAGE", [$this->getUsage()], true);
		}
	}

}