<?php

/**
 * CoreCommand.php – Components
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

namespace core\command;

use core\Main;
use core\util\traits\CorePluginReference;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;

abstract class CoreCommand extends Command {

	use CorePluginReference;

	/**
	 * DefaultCommand constructor.
	 *
	 * @param Main $plugin
	 * @param string $name
	 * @param null|string $description
	 * @param string $usage
	 * @param array ...$aliases
	 */
	public function __construct(Main $plugin, $name, $description, $usage, array $aliases = []) {
		$this->setCore($plugin);
		parent::__construct($name, $description, $usage, $aliases);
	}

	/**
	 * Initial command call
	 *
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $commandLabel, array $args) {
		if($this->testPermission($sender)) {
			return $this->run($sender, $args);
		} else {
			$sender->sendMessage($this->getPermissionMessage());
		}
		return false;
	}

	/**
	 * Internal command call
	 *
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return mixed
	 */
	protected abstract function run(CommandSender $sender, array $args);

}