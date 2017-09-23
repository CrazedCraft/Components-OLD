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

namespace core;

use core\command\CoreCommandMap;
use core\database\CoreDatabaseManager;
use core\entity\antihack\KillAuraDetector;
use core\entity\text\FloatingText;
use core\language\LanguageManager;
use core\network\NetworkManager;
use core\task\ReportErrorTask;
use core\task\RestartTask;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

	/** @var int */
	protected $loadTime;

	/** @var Main */
	private static $instance;

	/** @var Config */
	protected $errorLog;

	/** @var Config */
	private $settings;

	/** @var CoreCommandMap */
	private $commandMap;

	/** @var CoreDatabaseManager */
	private $databaseManager;

	/** @var CoreListener */
	private $listener;

	/** @var LanguageManager */
	private $languageManager;

	/** @var NetworkManager */
	private $networkManager;

	/** @var FloatingText */
	public $floatingText = [];

	/** @var RestartTask */
	private $restartTask;

	/** @var bool */
	public static $testing = false;

	/** @var bool */
	public static $debug = false;

	/** @var array */
	private static $staff = [
		"jacknoordhuis",
		"littlehorsey",
		"jakeisthebest1",
		"xxdoomrealxx",
		"rustymcpe",
		"tabbott",
	]; // List of accounts with access to staff commands

	/** Resource files & paths */
	const SETTINGS_FILE = "Settings.yml";
	const ERROR_REPORT_LOG = "error_log.json";

	public function onLoad() {
		$this->loadTime = microtime(true);
		self::$instance = $this;
		$this->getLogger()->info("Loading configs...");
		$this->loadConfigs();
	}

	public function onEnable() {
		Entity::registerEntity(KillAuraDetector::class, true);
		set_error_handler([$this, "errorHandler"], E_ALL);
		$this->getLogger()->info("Enabling command map...");
		$this->setCommandMap();
		$this->getLogger()->info("Initializing database manager...");
		$this->setDatabaseManager();
		$this->getLogger()->info("Enabling network manager...");
		$this->setNetworkManager();
		$this->getLogger()->info("Setting event listener...");
		$this->setListener();
		$this->getLogger()->info("Enabling language manager...");
		$this->setLanguageManager();
		$this->getLogger()->info("Applying finishing touches...");
		$this->getServer()->getNetwork()->setName($this->languageManager->translate("SERVER_NAME", "en"));
		$this->restartTask = new RestartTask($this);
		$server = $this->getServer();
		$this->getLogger()->info("Enabled components! (" . round(microtime(true) - $this->loadTime, 3) . "s)! Components enabled on {$server->getIp()}:{$server->getPort()} with {$server->getMaxPlayers()} slots!");
	}

	/**
	 * @return Main
	 */
	public static function getInstance() {
		return self::$instance;
	}

	/**
	 * @return array
	 */
	public static function getStaffNames() {
		return self::$staff;
	}

	/**
	 * Check to see if a player is staff
	 *
	 * @param Player|string $player
	 *
	 * @return bool
	 */
	public static function isStaff($player) {
		if($player instanceof Player) $player = $player->getName();
		return in_array(strtolower($player), self::$staff);
	}

	/**
	 * Safely shutdown the plugin
	 */
	public function onDisable() {
		/** @var CorePlayer $p */
		foreach($this->getServer()->getOnlinePlayers() as $p) $p->kick($this->getLanguageManager()->translateForPlayer($p, "SERVER_RESTART"));
		$this->errorLog->save(false);
	}

	/**
	 * Save all the configs and get them ready for use
	 */
	public function loadConfigs() {
		$this->saveResource(self::SETTINGS_FILE);
		$this->settings = new Config($this->getDataFolder() . self::SETTINGS_FILE, Config::YAML);
		$this->saveResource(self::ERROR_REPORT_LOG);
		$this->errorLog = new Config($this->getDataFolder() . self::ERROR_REPORT_LOG, Config::JSON);
	}

	/**
	 * @return Config
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * @return CoreCommandMap
	 */
	public function getCommandMap() {
		return $this->commandMap;
	}

	/**
	 * @return CoreDatabaseManager
	 */
	public function getDatabaseManager() {
		return $this->databaseManager;
	}

	/**
	 * @return CoreListener
	 */
	public function getListener() {
		return $this->listener;
	}

	/**
	 * @return LanguageManager
	 */
	public function getLanguageManager() {
		return $this->languageManager;
	}

	/**
	 * @return NetworkManager
	 */
	public function getNetworkManager() : NetworkManager {
		return $this->networkManager;
	}

	/**
	 * Set the command map
	 */
	public function setCommandMap() {
		$this->commandMap = new CoreCommandMap($this);
	}

	/**
	 * Set the event listener
	 */
	public function setListener() {
		$this->listener = new CoreListener($this);
	}

	/**
	 * Set the database manager
	 */
	public function setDatabaseManager() {
		$this->databaseManager = new CoreDatabaseManager($this);
	}

	/**
	 * Set the language manager
	 */
	public function setLanguageManager() {
		$this->languageManager = new LanguageManager($this);
	}

	/**
	 * Set the network manager
	 */
	public function setNetworkManager() {
		$this->networkManager = new NetworkManager($this);
	}

	/**
	 * Stop loaded chunks from being unloaded
	 */
	public function freezeLoadedChunks() {
		$chunks = $this->getServer()->getDefaultLevel()->getProvider()->getLoadedChunks();
		foreach($chunks as $chunk) {
			$chunk->allowUnload = false;
		}
	}

	/**
	 * Our custom error handler
	 *
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 * @param $context
	 * @param null $trace
	 *
	 * @return bool
	 */
	public function errorHandler($errno, $errstr, $errfile, $errline, $context, $trace = null) {
		$errorConversion = [E_ERROR => "E_ERROR", E_WARNING => "E_WARNING", E_PARSE => "E_PARSE", E_NOTICE => "E_NOTICE", E_CORE_ERROR => "E_CORE_ERROR", E_CORE_WARNING => "E_CORE_WARNING", E_COMPILE_ERROR => "E_COMPILE_ERROR", E_COMPILE_WARNING => "E_COMPILE_WARNING", E_USER_ERROR => "E_USER_ERROR", E_USER_WARNING => "E_USER_WARNING", E_USER_NOTICE => "E_USER_NOTICE", E_STRICT => "E_STRICT", E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR", E_DEPRECATED => "E_DEPRECATED", E_USER_DEPRECATED => "E_USER_DEPRECATED",];
		$errno = isset($errorConversion[$errno]) ? $errorConversion[$errno] : $errno;
		if(($pos = strpos($errstr, "\n")) !== false) {
			$errstr = substr($errstr, 0, $pos);
		}

		$error = "An $errno error happened: '$errstr' in '$errfile' at line $errline" . PHP_EOL;

		$encoded = base64_encode($error);
		if(!$this->errorLog->exists($encoded)) {
			$this->getLogger()->debug("Logging error to log Error: {$error}");

			$backtrace = "";
			foreach(($trace = \core\Utils::getTrace($trace === null ? 3 : 0, $trace)) as $i => $line) {
				$backtrace .= $line . PHP_EOL;
			}

			//$this->getServer()->getScheduler()->scheduleAsyncTask(new ReportErrorTask($error . PHP_EOL . $backtrace . PHP_EOL .  "Server: {$this->getServer()->getIp()}:{$this->getServer()->getPort()}"));

			$this->errorLog->set($encoded, base64_encode($backtrace));
			$this->errorLog->save(true);
		}

		return true;
	}

	/**
	 * Uses SHA-512 [http://en.wikipedia.org/wiki/SHA-2] and Whirlpool
	 * [http://en.wikipedia.org/wiki/Whirlpool_(cryptography)]
	 *
	 * Both of them have an output of 512 bits. Even if one of them is broken in the future, you have to break both
	 * of them at the same time due to being hashed separately and then XORed to mix their results equally.
	 *
	 * @param string $salt
	 * @param string $password
	 *
	 * @return string[128] hex 512-bit hash
	 */
	public static function hash($salt, $password) {
		$salt = strtolower($salt); // temp fix for password in chat check :p
		return bin2hex(hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true));
	}

}