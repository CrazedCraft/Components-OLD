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

use core\entity\antihack\KillAuraDetector;
use core\task\CheckMessageTask;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\protocol\AvailableCommandsPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\utils\PluginException;

class CorePlayer extends Player {

	/** @var Main */
	private $core;

	/** @var bool */
	private $registered = false;

	/** @var bool */
	private $authenticated = false;

	/** @var bool */
	private $networkBanned = false;

	/** @var array */
	private $networkBanData = [];

	/** @var bool */
	private $locked = false;

	/** @var string */
	private $lockReason = "";

	/** @var string */
	private $hash = "";

	/** @var string */
	private $email = "";

	/** @var int */
	private $loginTime = 0;

	/** @var int */
	private $timePlayed = 0;

	/** @var int */
	private $state = self::STATE_LOBBY;

	/** @var string */
	private $registrationStatus = self::AUTH_PASSWORD;

	/** @var bool */
	private $chatMuted = false;

	/** @var string */
	private $lang = "en";

	/** @var int */
	private $coins = 0;

	/** @var string */
	private $lastMessage = "";

	/** @var int */
	private $lastMessageTime = 0;

	/** @var int */
	private $lastDamagedTime = 0;

	/** @var int */
	private $loginAttempts = 0;

	/** @var int */
	private $killAuraTriggers = 0;

	/** @var int */
	private $flyChances = 0;

	/** @var bool */
	private $showPlayers = true;

	/** @var int */
	private $deviceOs = -1;

	/** Game statuses */
	const STATE_LOBBY = "state.lobby";
	const STATE_PLAYING = "state.playing";
	const STATE_SPECTATING = "state.spectating";

	/** Authentication statuses */
	const AUTH_PASSWORD = "auth.password";
	const AUTH_CONFIRM = "auth.confirm";
	const AUTH_EMAIL = "auth.email";

	/** Device operating systems */
	const OS_ANDROID = 1;
	const OS_IOS = 2;
	const OS_OSX = 3;
	const OS_FIREOS = 4;
	const OS_GEARVR = 5;
	const OS_HOLOLENS = 6;
	const OS_WIN10 = 7;
	const OS_WIN32 = 8;
	const OS_DEDICATED = 9;

	/**
	 * Make sure the core plugin is enabled before an instance is constructed
	 *
	 * @param SourceInterface $interface
	 * @param null $clientID
	 * @param string $ip
	 * @param int $port
	 */
	public function __construct(SourceInterface $interface, $clientID, $ip, $port) {
		parent::__construct($interface, $clientID, $ip, $port);
		if(($plugin = $this->getServer()->getPluginManager()->getPlugin("Components")) instanceof Main and $plugin->isEnabled()){
			$this->core = $plugin;
		} else {
			$this->kick("Error");
			throw new PluginException("Core plugin isn't loaded!");
		}
	}

	/**
	 * @return bool
	 */
	public function isRegistered() {
		return $this->registered;
	}

	/**
	 * @return bool
	 */
	public function isAuthenticated() {
		return $this->authenticated;
	}

	/**
	 * @return bool
	 */
	public function isNetworkBanned() {
		return $this->networkBanned;
	}

	/**
	 * @return array
	 */
	public function getNetworkBanData() {
		return $this->networkBanData;
	}

	/**
	 * @return mixed
	 */
	public function isLocked() {
		return $this->isLocked();
	}

	/**
	 * @return string
	 */
	public function getLockReason() {
		return $this->lockReason;
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @return int
	 */
	public function getLoginTime() {
		return $this->loginTime;
	}

	/**
	 * @return int
	 */
	public function getTimePlayed() {
		return $this->timePlayed;
	}

	/**
	 * @return int
	 */
	public function getTotalTimePlayed() {
		return (time() - $this->loginTime) + $this->timePlayed;
	}

	/**
	 * @return int
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @return string
	 */
	public function getRegistrationState() {
		return $this->registrationStatus;
	}

	/**
	 * @return bool
	 */
	public function hasChatMuted() {
		return $this->hasChatMuted();
	}

	/**
	 * @return string
	 */
	public function getLanguageAbbreviation() {
		return $this->lang;
	}

	/**
	 * @return int
	 */
	public function getCoins() {
		return $this->coins;
	}

	/**
	 * @return string
	 */
	public function getLastMessage() {
		return $this->lastMessage;
	}

	/**
	 * @return int
	 */
	public function getLastMessageTime() {
		return $this->lastMessageTime;
	}

	/**
	 * @return int
	 */
	public function getLoginAttempts() {
		return $this->loginAttempts;
	}

	/**
	 * @return int
	 */
	public function getKillAuraTriggers() {
		return $this->killAuraTriggers;
	}

	/**
	 * @return bool
	 */
	public function hasPlayersVisible() {
		return $this->showPlayers;
	}

	/**
	 * @return int
	 */
	public function getDeviceOs() {
		return $this->deviceOs;
	}

	/**
	 * @return Main
	 */
	public function getCore() {
		return $this->core;
	}

	/**
	 * @param $value
	 */
	public function setRegistered($value = true) {
		$this->registered = $value;
	}

	/**
	 * @param bool $authenticated
	 */
	public function setAuthenticated($authenticated = true) {
		$this->authenticated = $authenticated;
	}

	/**
	 * @param bool $value
	 */
	public function setNetworkBanned($value = true) {
		$this->networkBanned = $value;
	}

	/**
	 * @param array $data
	 */
	public function setNetworkBanData($data = []) {
		$this->networkBanData = $data;
	}

	/**
	 * @param bool $value
	 */
	public function setLocked($value = true) {
		$this->locked = $value;
	}

	/**
	 * @param string $reason
	 */
	public function setLockReason($reason = "") {
		$this->lockReason = $reason;
	}

	/**
	 * @param string $hash
	 */
	public function setHash($hash) {
		$this->hash = $hash;
	}

	/**
	 * @param string $email
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * Set the last time a players login date was updated
	 */
	public function setLoginTime() {
		$this->loginTime = time();
	}

	/**
	 * @param $time
	 */
	public function setTimePlayed($time) {
		$this->timePlayed = $time;
	}

	/**
	 * @param int $state
	 */
	public function setStatus($state) {
		$this->state = $state;
	}

	/** @param string $status */
	public function setRegistrationStatus($status) {
		$this->registrationStatus = $status;
	}

	/**
	 * @param $value
	 */
	public function setChatMuted($value) {
		$this->chatMuted = $value;
	}

	/**
	 * @param string $abbreviation
	 */
	public function setLanguageAbbreviation($abbreviation) {
		$this->lang = $abbreviation;
	}

	/**
	 * @param $value
	 */
	public function addCoins($value) {
		$this->coins += $value;
	}

	/**
	 * @param $message
	 */
	public function setLastMessage($message) {
		$this->lastMessage = $message;
		$this->lastMessageTime = floor(microtime(true));
	}

	/**
	 * Add a failed login attempt for the player
	 */
	public function addLoginAttempt() {
		$this->loginAttempts++;
	}

	/**
	 * @param bool $value
	 */
	public function setPlayersVisible($value = true) {
		$this->showPlayers = $value;
		foreach($this->server->getOnlinePlayers() as $p) {
			if($value) {
				if($p->distance($this) <= 20) $p->spawnTo($this);
			} else {
				$p->despawnFrom($this);
			}
		}
	}

	/**
	 * @param int $os
	 */
	public function setDeviceOs(int $os) {
		$this->deviceOs = $os;
	}

	/**
	 * Increases the amount of times a player has been detected for having kill aura
	 */
	public function addKillAuraTrigger() {
		$this->killAuraTriggers++;
		$this->checkKillAuraTriggers();
	}

	/**
	 * Checks the amount of times a player has triggered a kill aura detector and handles the result accordingly
	 */
	public function checkKillAuraTriggers() {
		if($this->killAuraTriggers >= 8) $this->kick($this->getCore()->getLanguageManager()->translateForPlayer($this, "KICK_BANNED_MOD", ["Kill Aura"]));
	}

	/**
	 * Spawn the kill aura detection entities
	 */
	public function spawnKillAuraDetectors() {
		$nbt = new Compound("", [
			"Pos" => new Enum("Pos", [
				new DoubleTag("", $this->x),
				new DoubleTag("", $this->y),
				new DoubleTag("", $this->z)
			]),
			"Motion" =>new Enum("Motion", [
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
			]),
			"Rotation" => new Enum("Rotation", [
				new FloatTag("", 180),
				new FloatTag("", 0)
			]),
		]);
		$entity = Entity::createEntity("KillAuraDetector", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), clone $nbt);
		if($entity instanceof KillAuraDetector) {
			$entity->setTarget($this);
			$entity->setOffset(new Vector3(0, 2.5, 0));
		} else {
			$entity->kill();
		}
		$entity = Entity::createEntity("KillAuraDetector", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), clone $nbt);
		if($entity instanceof KillAuraDetector) {
			$entity->setTarget($this);
			$entity->setOffset(new Vector3(0, -2.5, 0));
		} else {
			$entity->kill();
		}
	}

	public function handleAuth(string $message) {
		if($this->isRegistered()) {
			if(hash_equals($this->getHash(), Utils::hash(strtolower($this->getName()), $message))) {
				$this->chatMuted = false;
				$this->authenticated = true;
				$this->setLoginTime();
				/** @var CorePlayer $p */
				foreach($this->getServer()->getOnlinePlayers() as $p) {
					$p->showPlayer($this);
				}
				$this->spawnKillAuraDetectors();
				$this->sendTranslatedMessage("LOGIN_SUCCESS", [], true);
			} else {
				$this->addLoginAttempt();
				if($this->loginAttempts >= 3) {
					$this->kick($this->core->getLanguageManager()->translateForPlayer($this, "TOO_MANY_LOGIN_ATTEMPTS"));
				}
				$this->sendTranslatedMessage("INCORRECT_PASSWORD", [], true);
			}
		} else {
			switch($this->registrationStatus) {
				// password
				default:
					$this->hash = Utils::hash(strtolower($this->getName()), $message);
					$this->registrationStatus = self::AUTH_CONFIRM;
					$this->sendTranslatedMessage("CONFIRM_PASSWORD_PROMPT", [], true);
					break;
				// password confirmation
				case self::AUTH_CONFIRM:
					if(hash_equals($this->getHash(), Utils::hash(strtolower($this->getName()), $message))) {
						$this->registrationStatus = self::AUTH_EMAIL;
						$this->sendTranslatedMessage("EMAIL_PROMPT", [], true);
						break;
					}
					$this->sendTranslatedMessage("PASSWORDS_NO_MATCH", [], true);
					$this->registrationStatus = self::AUTH_PASSWORD;
					$this->sendTranslatedMessage("REGISTER_PROMPT", [], true);
					break;
				// email
				case self::AUTH_EMAIL:
					if(filter_var($message, FILTER_VALIDATE_EMAIL)) {
						$this->email = strtolower($message);
						$this->chatMuted = false;
						$this->core->getDatabaseManager()->getAuthDatabase()->register($this->getName(), $this->getHash(), $this->getEmail());
						break;
					}
					$this->sendTranslatedMessage("INVALID_EMAIL", [], true);
					break;
			}
		}
	}

	/**
	 * Checks a players network ban status after querying the database and handles the results accordingly
	 */
	public function checkNetworkBan() {
		if($this->networkBanned and is_array($this->networkBanData)) {
			// $message = explode("\n", $this->networkBanData["reason"]); //Todo – Format message properly
			$this->kick($this->getCore()->getLanguageManager()->translateForPlayer($this, "BANNED_KICK", [
				$this->networkBanData["issuer_name"],
				$this->networkBanData["reason"],
				date("j-n-Y g:i a T", $this->networkBanData["expires"]),
			]));
		}
	}

	/**
	 * Returns an array of data to be saved to the database
	 *
	 * @return array
	 */
	public function getAuthData() {
		return [
			"ip" => $this->getAddress(),
			"lang" => $this->lang,
			"coins" => $this->coins,
			"timePlayed" => time() - $this->loginTime,
			"lastLogin" => $this->loginTime
		];
	}

	/**
	 * @param $key
	 * @param array $args
	 * @param bool $isImportant
	 */
	public function sendTranslatedMessage($key, array $args = [], $isImportant = false) {
		$this->sendMessage($this->core->getLanguageManager()->translateForPlayer($this, $key, $args), $isImportant);
	}

	/**
	 * @param \pocketmine\event\TextContainer|string $message
	 * @param bool $isImportant
	 *
	 * @return bool
	 */
	public function sendMessage($message, $isImportant = false) {
		if(!$isImportant and $this->chatMuted) {
			return false;
		}
		parent::sendMessage($message);
		return true;
	}

	/**
	 * @param float $damage
	 * @param EntityDamageEvent $source
	 *
	 * @return bool
	 */
	public function attack($damage, EntityDamageEvent $source) {
		if($this->state === self::STATE_PLAYING) {
			parent::attack($damage, $source);
			if($source->setCancelled()) $this->lastDamagedTime = microtime(true);
			return true;
		}
		$source->setCancelled(true);
		return $source->isCancelled();
	}

	/**
	 * Ensures players don't actually die
	 *
	 * @param bool $forReal
	 * @return bool
	 */
	public function kill($forReal = false) {
		if($forReal) return parent::kill();
		$this->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
		$this->setHealth($this->getMaxHealth());
		$inv = $this->getInventory();
		if($inv instanceof PlayerInventory) $this->getInventory()->clearAll();
		return true;
	}

	public function sendCommandData() {
		$default = $this->getCore()->getCommandMap()->getDefaultCommandData();

		if($this->isStaff()) {
			$default = array_merge($default, $this->getCore()->getCommandMap()->getCommandData("staff"));
		}

		$pk = new AvailableCommandsPacket();
		$pk->commands = json_encode($default);
		$this->dataPacket($pk);
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		if($player instanceof CorePlayer and $player->showPlayers) parent::spawnTo($player);
	}

	/**
	 * @return bool
	 */
	public function isStaff() {
		return Main::isStaff($this);
	}

	/**
	 * @param PlayerChatEvent $event
	 */
	public function onChat(PlayerChatEvent $event) {
		$message = $event->getMessage();
		$event->setCancelled();
		if($this->authenticated) {
			if(Main::$debug) $start = microtime(true);
			if(($key = $this->getCore()->getLanguageManager()->check($message)) !== false) {
				$this->sendTranslatedMessage(( $key === "" ? "BLOCKED_MESSAGE" : $key), [], true);
			} else {
				$this->getServer()->getScheduler()->scheduleAsyncTask(new CheckMessageTask($this->getName(), $this->hash, $this->lastMessage, $this->lastMessageTime, $message, $this->chatMuted));
			}
			if(Main::$debug) {
				$end = microtime(true);
				echo "<----------- MESSAGE CHECK ----------->" . PHP_EOL;
				echo "TIME: " . round($end - $start, 3) . "s " . PHP_EOL;
			}
		} else {
			$this->handleAuth($message);
		}
	}

	/**
	 * @param PlayerMoveEvent $event
	 */
	public function onMove(PlayerMoveEvent $event) {
		$y = $event->getTo()->getY();
		if($y <= 0 or $y >= 112) {
			$this->kill();
		} else {
			$block = $this->getLevel()->getBlock(new Vector3($this->getFloorX(),$this->getFloorY()-1,$this->getFloorZ()));
			if($block->getId() === Block::AIR and (microtime(true) - $this->lastDamagedTime) >= 5 and round($event->getTo()->getY() - $event->getFrom()->getY(), 3) >= 0.375) {
				$this->flyChances++;
			} else {
				$this->flyChances = 0;
			}

			if($this->flyChances >= 5) {
				$this->kick($this->getCore()->getLanguageManager()->translateForPlayer($this, "KICK_BANNED_MOD", ["Fly"]));
			}
		}
	}

	/**
	 * @param PlayerDropItemEvent $event
	 */
	public function onDrop(PlayerDropItemEvent $event) {
		$event->setCancelled(true);
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event) {}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event) {
		$event->setCancelled();
	}

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onPlace(BlockPlaceEvent $event) {
		$event->setCancelled();
	}

}