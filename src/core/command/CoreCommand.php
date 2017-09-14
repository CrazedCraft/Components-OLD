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

use core\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;

abstract class CoreCommand extends Command implements PluginIdentifiableCommand {

	/** @var Main */
	private $plugin;

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
		parent::__construct($name, $description, $usage, $aliases);
		$this->plugin = $plugin;
	}

	/**
	 * @return Main
	 */
	public function getPlugin() {
		return $this->plugin;
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