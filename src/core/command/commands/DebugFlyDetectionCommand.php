<?php

/**
 * DebugFlyDetectionCommand.php – Components
 *
 * Copyright (C) 2015-2018 Jack Noordhuis
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Jack Noordhuis
 *
 */

declare(strict_types=1);

namespace core\command\commands;

use core\command\CoreStaffCommand;
use core\CorePlayer;
use core\language\LanguageUtils;
use core\Main;
class DebugFlyDetectionCommand extends CoreStaffCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "debugfly", "Toggle the display of fly detection debug information", "/debugfly", ["dfly"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		$player->setDebugFly(!$player->hasDebugFly());
		$player->sendMessage(LanguageUtils::translateColors("&6- &aToggled fly detection debug information!"));
	}

}