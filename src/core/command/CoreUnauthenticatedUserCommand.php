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
 * Created on 12/07/2016 at 9:13 PM
 *
 */
namespace core\command;

use core\CorePlayer;
use core\language\LanguageManager;
use pocketmine\command\CommandSender;

abstract class CoreUnauthenticatedUserCommand extends CoreCommand {

	/**
	 * Internal command call
	 *
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	protected function run(CommandSender $sender, array $args) {
		if($sender instanceof CorePlayer) {
			return $this->onRun($sender, $args);
		} else {
			$sender->sendMessage(LanguageManager::getInstance()->translate("MUST_BE_PLAYER_FOR_COMMAND"));
		}
		return true;
	}

	/**
	 * Override this function to make the command do stuff
	 *
	 * @param CorePlayer $player
	 * @param array $args
	 *
	 * @return mixed
	 */
	public abstract function onRun(CorePlayer $player, array $args);

}