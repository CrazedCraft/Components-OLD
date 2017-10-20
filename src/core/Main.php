<?php

/**
 * Main.php – Components
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
 * Last modified on 20/10/2017 at 5:50 PM
 *
 */

namespace core;

use core\command\CoreCommandMap;
use core\database\CoreDatabaseManager;
use core\database\request\network\UpdateNetworkServerDatabaseRequest;
use core\entity\antihack\KillAuraDetector;
use core\entity\text\FloatingText;
use core\language\LanguageManager;
use core\network\NetworkManager;
use core\network\NetworkNode;
use core\network\NetworkServer;
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

	public function onLoad() {
		$this->loadTime = microtime(true);
		self::$instance = $this;
		$this->getLogger()->info("Loading configs...");
		$this->loadConfigs();
	}

	public function onEnable() {
		Entity::registerEntity(KillAuraDetector::class, true);
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
		$map = $this->getNetworkManager()->getMap();
		$map->getServer()->setOnline(false); // disable this server

		$node = $map->findNode($map->getServer()->getNode());
		$server = null;
		$usedNodes = [$map->getServer()->getNode() => true]; // list of nodes used
		/** @var CorePlayer $p */
		foreach($this->getServer()->getOnlinePlayers() as $p) { // attempt to transfer players to an online server rather than kicking them
			if($server instanceof NetworkServer and $server->isAvailable()) { // try transferring player to server where other players have been transferred
				$p->transfer($server->getHost(), $server->getPort());
				continue;
			}

			if($node instanceof NetworkNode) { // try transfer player to same node as other players (starting with this servers node)
				$s = $node->getSuitableServer();
				if($s instanceof NetworkServer) {
					$server = $s;
					$p->transfer($s->getHost(), $s->getPort());
					continue;
				}
			}

			foreach($map->getNodes() as $n) { // loop over all nodes
				if(!isset($usedNodes[$name = $node->getName()])) { // make sure we haven't already looped over a node
					$node = $n;
					$usedNodes[$name] = true;

					$s = $node->getSuitableServer(); // try and find a server for the player to join
					if($s instanceof NetworkServer) {
						$server = $s;
						$p->transfer($s->getHost(), $s->getPort());
						continue;
					}
				}
			}

			$p->kick($this->getLanguageManager()->translateForPlayer($p, "SERVER_RESTART")); // no available servers
		}

		$this->getDatabaseManager()->pushToPool(new UpdateNetworkServerDatabaseRequest($map->getServer())); // push this servers status to the database and mark as offline

		$this->getDatabaseManager()->processEntirePool(); // execute all pending requests
	}

	/**
	 * Save all the configs and get them ready for use
	 */
	public function loadConfigs() {
		$this->saveResource(self::SETTINGS_FILE);
		$this->settings = new Config($this->getDataFolder() . self::SETTINGS_FILE, Config::YAML);
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