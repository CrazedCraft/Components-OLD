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
 * Created on 09/08/2017 at 9:39 PM
 *
 */

namespace core\command\commands;

use core\command\CoreUserCommand;
use core\CorePlayer;
use core\language\LanguageUtils;
use core\Main;

class InfoCommand extends CoreUserCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "info", "Get some basic info on a player", "/info <player>", ["device"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		if(isset($args[0])) {
			$target = $this->getPlugin()->getServer()->getPlayer($args[0]);
			if($target instanceof CorePlayer) {
				$player->sendMessage(LanguageUtils::translateColors("&a-=====- &e{$target->getName()}('s) Info &a-=====-\n&aPing&7: &6{$target->getPing()}ms\n&aDevice&7: &c{$target->getDeviceOSString()}"));
				$time = new \DateTime("NOW", new \DateTimeZone("GMT"));
				$time->setTimestamp($target->getTimePlayed());
				$weeks = floor($time->getTimestamp() / 604800); // get weeks by: timestamp / (60 * 60 * 24 * 7)
				$days = floor($time->getTimestamp() / 86400 -  ($weeks > 0 ? $weeks * 7 : 0)); // get days by: (timestamp / (60 * 60 * 24) - weeks * 7
				$hours = (int) $time->format("G");
				$minutes = (int) $time->format("i");
				$seconds = (int) $time->format("s");
				$player->sendMessage(LanguageUtils::translateColors(rtrim("&aTime played&7:&e" . ($weeks > 0 ? " {$weeks} week" . ($weeks == 1 ? "," : "s,") : "") . ($days > 0 ? " {$days} day" . ($days == 1 ? "," : "s,") : "") . ($hours > 0 ? " {$hours} hour" . ($hours == 1 ? "," : "s,") : "") . ($minutes > 0 ? " {$minutes} minute" . ($minutes == 1 ? "," : "s,") : "") . ($seconds > 0 ? " {$seconds} second" . ($seconds == 1 ? "," : "s,") : ""),","))); // time online
				$time->setTimestamp(time() - $target->getRegisteredTime());
				$weeks = floor($time->getTimestamp() / 604800); // get weeks by: timestamp / (60 * 60 * 24 * 7)
				$days = floor($time->getTimestamp() / 86400 -  ($weeks > 0 ? $weeks * 7 : 0)); // get days by: (timestamp / (60 * 60 * 24) - weeks * 7
				$hours = (int) $time->format("G");
				$minutes = (int) $time->format("i");
				$seconds = (int) $time->format("s");
				$player->sendMessage(LanguageUtils::translateColors(rtrim("&aRegistered for&7:&d" . ($weeks > 0 ? " {$weeks} week" . ($weeks == 1 ? "," : "s,") : "") . ($days > 0 ? " {$days} day" . ($days == 1 ? "," : "s,") : "") . ($hours > 0 ? " {$hours} hour" . ($hours == 1 ? "," : "s,") : "") . ($minutes > 0 ? " {$minutes} minute" . ($minutes == 1 ? "," : "s,") : "") . ($seconds > 0 ? " {$seconds} second" . ($seconds == 1 ? "," : "s,") : ""),","))); // registered time
			} else {
				$player->sendMessage(LanguageUtils::translateColors("&c" . $args[0] . " is not online!"));
			}
		} else {
			$player->sendMessage(LanguageUtils::translateColors("&a-=====- &eYour Info &a-=====-\n&aPing&7: &6{$player->getPing()}ms\n&aDevice&7: &c{$player->getDeviceOSString()}")); // ping + device type
			$time = new \DateTime("NOW", new \DateTimeZone("GMT"));
			$time->setTimestamp($player->getTimePlayed());
			$weeks = floor($time->getTimestamp() / 604800); // get weeks by: timestamp / (60 * 60 * 24 * 7)
			$days = floor($time->getTimestamp() / 86400 -  ($weeks > 0 ? $weeks * 7 : 0)); // get days by: (timestamp / (60 * 60 * 24) - weeks * 7
			$hours = (int) $time->format("G");
			$minutes = (int) $time->format("i");
			$seconds = (int) $time->format("s");
			$player->sendMessage(LanguageUtils::translateColors(rtrim("&aTime played&7:&e" . ($weeks > 0 ? " {$weeks} week" . ($weeks == 1 ? "," : "s,") : "") . ($days > 0 ? " {$days} day" . ($days == 1 ? "," : "s,") : "") . ($hours > 0 ? " {$hours} hour" . ($hours == 1 ? "," : "s,") : "") . ($minutes > 0 ? " {$minutes} minute" . ($minutes == 1 ? "," : "s,") : "") . ($seconds > 0 ? " {$seconds} second" . ($seconds == 1 ? "," : "s,") : ""),","))); // time online
			$time->setTimestamp(time() - $player->getRegisteredTime());
			$weeks = floor($time->getTimestamp() / 604800); // get weeks by: timestamp / (60 * 60 * 24 * 7)
			$days = floor($time->getTimestamp() / 86400 -  ($weeks > 0 ? $weeks * 7 : 0)); // get days by: (timestamp / (60 * 60 * 24) - weeks * 7
			$hours = (int) $time->format("G");
			$minutes = (int) $time->format("i");
			$seconds = (int) $time->format("s");
			$player->sendMessage(LanguageUtils::translateColors(rtrim("&aRegistered for&7:&d" . ($weeks > 0 ? " {$weeks} week" . ($weeks == 1 ? "," : "s,") : "") . ($days > 0 ? " {$days} day" . ($days == 1 ? "," : "s,") : "") . ($hours > 0 ? " {$hours} hour" . ($hours == 1 ? "," : "s,") : "") . ($minutes > 0 ? " {$minutes} minute" . ($minutes == 1 ? "," : "s,") : "") . ($seconds > 0 ? " {$seconds} second" . ($seconds == 1 ? "," : "s,") : ""),","))); // registered time
		}
	}

}