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
 * Created on 23/09/2016 at 7:48 PM
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