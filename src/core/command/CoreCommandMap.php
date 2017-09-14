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

use core\command\commands\BanCommand;
use core\command\commands\ChangePasswordCommand;
use core\command\commands\DumpSkinCommand;
use core\command\commands\InfoCommand;
use core\command\commands\KickCommand;
use core\command\commands\LoginCommand;
use core\command\commands\RegisterCommand;
use core\command\commands\TestCommand;
use core\Main;

/**
 * Manages all commands
 */
class CoreCommandMap {

	/** @var CoreCommand[] */
	protected $commands = [];

	/** @var $plugin */
	private $plugin;

	/** @var array */
	private $commandData = [];

	/** @var array */
	private $defaultCommandData;

	const COMMAND_DATA_FOLDER = "command_data" . DIRECTORY_SEPARATOR;
	const COMMAND_DATA_FILES = [
		"default" => "default.json",
		"staff" => "staff.json",
		"duels" => "duels.json",
		"cpvp" => "cpvp.json",
		"cprison" => "cprison.json",
	];

	/**
	 * CommandMap constructor
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->loadCommandData();
		$this->setDefaultCommands();
	}

	protected function loadCommandData() {
		if(!is_dir($this->plugin->getDataFolder() . self::COMMAND_DATA_FOLDER)) mkdir($this->plugin->getDataFolder() . self::COMMAND_DATA_FOLDER);
		foreach(self::COMMAND_DATA_FILES as $name => $filename) {
			$this->plugin->saveResource(self::COMMAND_DATA_FOLDER . $filename);
			$this->commandData[$name] = json_decode(file_get_contents($this->plugin->getDataFolder() . self::COMMAND_DATA_FOLDER . $filename), true);
		}

		$this->generateDefaultCommandData();
	}

	private function generateDefaultCommandData() {
		$defaults = $this->plugin->getSettings()->getNested("settings.command-data", ["default"]);
		$data = [];
		foreach($defaults as $name) {
			if(isset($this->commandData[$name])) {
				$data = array_merge($data, $this->commandData[$name]);
			}
		}
		$this->defaultCommandData = $data;
	}

	/**
	 * @param string $key
	 *
	 * @return array
	 */
	public function getCommandData($key = "") {
		return $this->commandData[$key];
	}

	/**
	 * @return array
	 */
	public function getDefaultCommandData() {
		return $this->defaultCommandData;
	}

	/**
	 * Set the default commands
	 */
	public function setDefaultCommands() {
		$this->registerAll([
			new BanCommand($this->plugin),
			new ChangePasswordCommand($this->plugin),
			new DumpSkinCommand($this->plugin),
			new InfoCommand($this->plugin),
			new KickCommand(($this->plugin)),
			new LoginCommand($this->plugin),
			new RegisterCommand($this->plugin),
			new TestCommand($this->plugin),
		]);
	}

	/**
	 * @return Main
	 */
	public function getPlugin() {
		return $this->plugin;
	}

	/**
	 * Register an array of commands
	 *
	 * @param array $commands
	 */
	public function registerAll(array $commands) {
		foreach($commands as $command) {
			$this->register($command);
		}
	}

	/**
	 * Register a command
	 *
	 * @param CoreCommand $command
	 * @param string $fallbackPrefix
	 *
	 * @return bool
	 */
	public function register(CoreCommand $command, $fallbackPrefix = "cc") {
		if($command instanceof CoreCommand) {
			$this->plugin->getServer()->getCommandMap()->register($fallbackPrefix, $command);
			$this->commands[strtolower($command->getName())] = $command;
		}
		return false;
	}

	/**
	 * Unregisters all commands
	 */
	public function clearCommands() {
		foreach($this->commands as $command) {
			$this->unregister($command);
		}
		$this->commands = [];
		$this->setDefaultCommands();
	}

	/**
	 * Unregister a command
	 *
	 * @param CoreCommand $command
	 */
	public function unregister(CoreCommand $command) {
		$this->plugin->getServer()->getCommandMap()->unregister($command);
		unset($this->commands[strtolower($command->getName())]);
	}

	/**
	 * Get a command
	 *
	 * @param $name
	 *
	 * @return CoreCommand|null
	 */
	public function getCommand($name) {
		if(isset($this->commands[$name])) {
			return $this->commands[$name];
		}
		return null;
	}

	/**
	 * @return CoreCommand[]
	 */
	public function getCommands() {
		return $this->commands;
	}

	public function __destruct() {
		$this->close();
	}

	public function close() {
		foreach($this->commands as $command) {
			$this->unregister($command);
		}
		unset($this->commands, $this->plugin);
	}

}