<?php

/**
 * BanCommand.php – Components
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
 * Last modified on 20/10/2017 at 5:50 PM
 *
 */

namespace core\command\commands;

use core\ban\BanEntry;
use core\command\CoreStaffCommand;
use core\CorePlayer;
use core\database\request\ban\BanUpdateRequest;
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
			$target = $this->getCore()->getServer()->getPlayer($name = array_shift($args));
			if($target instanceof CorePlayer) {
				if(count($target->getBanList()->search(null, null, null, null, true, false)) > 0) {
					$target->getBanList()->add(new BanEntry(-1, strtolower($target->getName()), $target->getAddress(), $target->getClientId(), 0, time(), true, implode(" ", $args), $player->getName()));
					$player->sendTranslatedMessage("BAN_SUCCESS", [$name, $player->getCore()->getLanguageManager()->translateForPlayer($player, "BAN_DURATION_FOREVER")]);
				} else {
					$target->getBanList()->add(new BanEntry(-1, strtolower($target->getName()), $target->getAddress(), $target->getClientId(), strtotime("+7 days"), time(), true, implode(" ", $args), $player->getName()));
					$player->sendTranslatedMessage("BAN_SUCCESS", [$name, $player->getCore()->getLanguageManager()->translateForPlayer($player, "BAN_DURATION_DAYS", ["7"])]);
				}
			} else {
				$this->getCore()->getDatabaseManager()->pushToPool(new BanUpdateRequest(-1, strtolower($name), null, null, strtotime("+7 days"), time(), implode(" ", $args), $player->getName(), true));
				$player->sendTranslatedMessage("BAN_SUCCESS", [$name, $player->getCore()->getLanguageManager()->translateForPlayer($player, "BAN_DURATION_DAYS", ["7"])]);
			}
		} else {
			$player->sendTranslatedMessage("COMMAND_USAGE", [$this->getUsage()], true);
		}
	}

}