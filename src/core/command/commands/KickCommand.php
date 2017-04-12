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
 * Created on 24/03/2017 at 11:24 PM
 *
 */

namespace core\command\commands;

use core\command\CoreStaffCommand;
use core\CorePlayer;
use core\Main;
use pocketmine\command\Command;

class KickCommand extends CoreStaffCommand {

	public function __construct(Main $plugin) {
		$map = $plugin->getServer()->getCommandMap();
		$oldCommand = $map->getCommand("kick");
		if($oldCommand instanceof Command) {
			$oldCommand->setLabel("kick_disabled");
			$oldCommand->unregister($map);
		}
		parent::__construct($plugin, "kick", "Kick a player from the current server", "/kick <player> [reason]", []);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(isset($args[1])) {
			$target = $this->getPlugin()->getServer()->getPlayer($name = array_shift($args));
			if($target instanceof CorePlayer) {
				$victim = $target->getName();
					$target->kick($this->getPlugin()->getLanguageManager()->translateForPlayer($target, "STAFF_KICK", [
					$player->getName(),
					implode(" ", $args),
				]));
				$player->sendTranslatedMessage("KICK_SUCCESS", [$victim]);
			} else {
				$player->sendTranslatedMessage("USER_NOT_ONLINE", [$name]);
			}
		} else {
			$player->sendTranslatedMessage("COMMAND_USAGE", [$this->getUsage()], true);
		}
	}

}