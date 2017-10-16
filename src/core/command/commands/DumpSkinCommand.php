<?php

/**
 * DumpSkinCommand.php – Components
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
use core\Main;
use core\Utils;

class DumpSkinCommand extends CoreUserCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "dumpskin", "Dumps your current skin to a plugin readable format", "/dumpskin <name>", ["saveskin"]);
	}

	public function onRun(CorePlayer $player, array $args) {
		$dir = $this->getPlugin()->getDataFolder() . "skin_dumps" . DIRECTORY_SEPARATOR;
		if(!is_dir($dir)) mkdir($dir);
		$file = fopen($dir . strtolower(isset($args[0]) ? $args[0] : $player->getSkinName()) . ".skin", "w");
		fwrite($file, $player->getSkinData());
		fclose($file);
		$player->sendMessage(Utils::translateColors("&6- &aDumped skin successfully!"), true);
	}

}