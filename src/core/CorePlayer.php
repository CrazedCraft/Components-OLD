<?php

/**
 * CorePlayer.php – Components
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
 */

namespace core;

use core\ban\BanEntry;
use core\ban\BanList;
use core\database\request\auth\AuthUpdateDatabaseRequest;
use core\entity\antihack\KillAuraDetector;
use core\gui\container\ContainerGUI;
use core\gui\item\GUIItem;
use core\language\LanguageUtils;
use core\task\CheckMessageTask;
use core\util\traits\CorePluginReference;
use pocketmine\block\Block;
use pocketmine\block\Slab;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\protocol\AvailableCommandsPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\utils\PluginException;
use pocketmine\utils\TextFormat;

class CorePlayer extends Player {

	use CorePluginReference;

	/**
	 * Array of block ids for the anti-fly to ignore
	 *
	 * @var int[]
	 */
	protected static $ignoredBlocks = [
		Block::STONE_SLAB,
		Block::WOODEN_SLAB,
		Block::STONE_SLAB2,
		Block::NETHER_BRICK_STAIRS,
		Block::DARK_OAK_STAIRS,
		Block::ACACIA_STAIRS,
		Block::JUNGLE_STAIRS,
		Block::BIRCH_STAIRS,
		Block::SPRUCE_STAIRS,
		Block::BRICK_STAIRS,
		Block::COBBLESTONE_STAIRS,
		Block::OAK_STAIRS,
		Block::PURPUR_STAIRS,
		Block::QUARTZ_STAIRS,
		Block::RED_SANDSTONE_STAIRS,
		Block::SANDSTONE_STAIRS,
		Block::STONE_BRICK_STAIRS,
		Block::STONE_STAIRS,
		Block::WOODEN_STAIRS
	];

	/** @var Main */
	private $core;

	/** @var bool */
	private $authCheckCompleted = false;

	/** @var bool */
	private $registered = false;

	/** @var bool */
	private $authenticated = false;

	/** @var string */
	private $lastIp = "0.0.0.0";

	/** @var BanList */
	private $banList;

	/** @var bool */
	private $locked = false;

	/** @var string */
	private $lockReason = "";

	/** @var string */
	private $hash = "";

	/** @var string */
	private $email = "";

	/** @var int */
	private $registeredTime = 0;

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
	private $lastMoveTime = 0;

	/** @var int */
	private $loginAttempts = 0;

	/** @var int */
	private $killAuraTriggers = 0;

	/** @var int */
	private $flyChances = 0;

	/** @var int */
	public $reachChances = 0;

	/** @var bool */
	private $showPlayers = true;

	/** @var string[] */
	private $guiCooldowns = [];

	/** @var int */
	private $pingChances = 0;

	/** @var bool */
	private $sentLoginTitle = false;

	/** @var bool */
	private $joined = false;

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

		$plugin = $this->getServer()->getPluginManager()->getPlugin("Components");
		if($plugin instanceof Main and $plugin->isEnabled()) {
			$this->setCore($plugin);
		} else {
			$this->kick("Error");
			throw new PluginException("Core plugin isn't loaded!");
		}
	}

	public function initEntity() {
		parent::initEntity();

		$this->banList = new BanList($this);
	}

	/**
	 * @return bool
	 */
	public function hasAuthCheckCompleted() : bool {
		return $this->authCheckCompleted;
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
	 * @return string
	 */
	public function getLastIp() {
		return $this->lastIp;
	}

	/**
	 * @return BanList
	 */
	public function getBanList() {
		return $this->banList;
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
	public function getRegisteredTime() {
		return $this->registeredTime;
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
		return $this->chatMuted;
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
	public function getLastMoveTime() : int {
		return $this->lastMoveTime;
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
	 * @param string $id
	 *
	 * @return int|string
	 */
	public function getGuiCooldown(string $id = GUIItem::GUI_ITEM_ID) {
		if($this->hasGuiCooldown($id)) {
			return $this->guiCooldowns[$id];
		}
		return 0;
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function hasGuiCooldown(string $id = GUIItem::GUI_ITEM_ID) {
		return isset($this->guiCooldowns[$id]);
	}

	/**
	 * @return bool
	 */
	public function hasPlayersVisible() {
		return $this->showPlayers;
	}

	/**
	 * @return bool
	 */
	public function hasSentLoginTitle() : bool {
		return $this->sentLoginTitle;
	}

	/**
	 * @return bool
	 */
	public function hasJoined() : bool {
		return $this->joined;
	}

	/**
	 * Get a string representation of the players device operating system
	 *
	 * @return string
	 */
	public function getDeviceOSString() {
		switch($this->getDeviceOs()) {
			case self::OS_ANDROID:
				return "Android";
			case self::OS_IOS:
				return "iOS";
			case self::OS_FIREOS:
				return "FireOS";
			case self::OS_GEARVR:
				return "Gear VR";
			case self::OS_HOLOLENS:
				return "Holo-lens";
			case self::OS_WIN10:
				return "Windows 10";
			case self::OS_WIN32:
				return "Windows 32";
			case self::OS_DEDICATED:
				return "Dedicated";
			default:
				return "Unknown";
		}
	}

	public function getInAirTicks() {
		return $this->inAirTicks;
	}

	/**
	 * @param bool $value
	 */
	public function setAuthCheckCompleted(bool $value = true) {
		$this->authCheckCompleted = $value;

		if($value) {
			$this->afterAuthCheck();
		}
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
		$this->chatMuted = false;
		$this->setLoginTime();
		/** @var CorePlayer $p */
		foreach($this->getServer()->getOnlinePlayers() as $p) {
			$p->showPlayer($this);
		}
		$this->spawnKillAuraDetectors();
		$this->doGeneralUpdate();
	}

	/**
	 * @param string $ip
	 */
	public function setLastIp(string $ip) {
		$this->lastIp = $ip;
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
	 * @param int $value
	 */
	public function setRegisteredTime(int $value = 0) {
		$this->registeredTime = $value;
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
	 * @param int $time
	 * @param string $id
	 */
	public function setGuiCooldown(int $time, string $id = GUIItem::GUI_ITEM_ID) {
		$this->guiCooldowns[$id] = $time;
	}

	/**
	 * @param ContainerGUI $gui
	 *
	 * @throws \ErrorException
	 */
	public function openGuiContainer(ContainerGUI $gui) {
		$this->addWindow($gui);
	}

	/**
	 * @param bool $value
	 */
	public function setHasSentLoginTitle(bool $value = true) {
		$this->sentLoginTitle = $value;
	}

	/**
	 * @param bool $value
	 */
	public function setHasJoined(bool $value = true) {
		$this->joined = $value;
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
		if($this->killAuraTriggers >= 12) {
			Utils::broadcastStaffMessage("&a" . $this->getName() . " &ehas been kicked for suspected kill-aura!");
			$this->kick($this->getCore()->getLanguageManager()->translateForPlayer($this, "KICK_BANNED_MOD"));
			Utils::broadcastStaffMessage("&a" . $this->getName() . " &ehas been kicked for kill-aura!");
		}
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
			$entity->setOffset(new Vector3(0, 3, 0));
		} else {
			$entity->kill();
		}
		$entity = Entity::createEntity("KillAuraDetector", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), clone $nbt);
		if($entity instanceof KillAuraDetector) {
			$entity->setTarget($this);
			$entity->setOffset(new Vector3(0, -1, 0));
		} else {
			$entity->kill();
		}
	}

	/**
	 * Checks the amount of times a player has triggered the reach detection and handles the result accordingly
	 */
	public function checkReachTriggers() {
		if($this->reachChances >= 14) {
			Utils::broadcastStaffMessage("&a" . $this->getName() . " &ehas been kicked for suspected reach!");
			$this->kick($this->getCore()->getLanguageManager()->translateForPlayer($this, "KICK_BANNED_MOD"));
			Utils::broadcastStaffMessage("&a" . $this->getName() . " &ehas been kicked for reach!");
		}
	}

	/**
	 * Increases or decreases the amount of reach triggers based on distance and ping
	 *
	 * @param int $distance
	 */
	public function updateReachTriggers(int $distance) {
		if($distance >= 6.5 and $this->getPing() <= 200) {
			$this->reachChances += 1;
		} elseif($distance >= 8 and $this->getPing() <= 600) {
			$this->reachChances += 2;
		} elseif($distance >= 12) {
			$this->reachChances += 4;
		} else {
			$this->reachChances--;
			return;
		}

		$this->checkReachTriggers();
	}

	/**
	 * Checks the amount of times a player has triggered the fly detection and handles the result accordingly
	 */
	public function checkFlyTriggers() {
		// be more harsh on android players due to it being the easiest platform to 'hack' on
		if(($this->getDeviceOs() === self::OS_ANDROID and $this->flyChances >= 24) or (($this->getDeviceOs() === self::OS_IOS or $this->getDeviceOs() === self::OS_WIN10) and $this->flyChances >= 32) or $this->flyChances >= 48) {
			if($this->isAuthenticated()) {
				$banWaveTask = $this->getCore()->getBanWaveTask();
				if(!isset($banWaveTask->flyKicks[$this->getName()])) {
					$banWaveTask->flyKicks[$this->getName()] = 0;
				}
				$banWaveTask->flyKicks[$this->getName()]++; // increment number of times kicked for fly
				if($banWaveTask->flyKicks[$this->getName()] >= 5) {
					$banWaveTask->queue(new BanEntry(-1, $this->getName(), $this->getAddress(), $this->getClientId(), strtotime("+4 days"), time(), true, "You were banned automatically ¯\_(ツ)_/¯", "MAGIC I"));
				}
			}

			$this->kick($this->getCore()->getLanguageManager()->translateForPlayer($this, "KICK_BANNED_MOD"));
			Utils::broadcastStaffMessage("&a" . $this->getName() . " &ehas been kicked for flying!");
		}
	}

	/**
	 * @param Vector3 $to
	 * @param int $yDistance
	 */
	public function updateFlyTriggers(Vector3 $to, int $yDistance) {
		if(!$this->getAllowFlight()) { // make sure the player isn't allowed to fly
			$blockInId = $this->getLevel()->getBlockIdAt($to->getFloorX(), ceil($to->getY() + 1.5), $to->getFloorZ()); // block at players head height (used to make sure player isn't in a transparent block (cobwebs, water, etc)
			$blockOnId = $this->getLevel()->getBlockIdAt($to->getFloorX(), $to->getY(), $to->getFloorZ()); // block the player is on (use this for checking slabs, stairs, etc)
			$blockBelowId = $this->getLevel()->getBlockIdAt($to->getFloorX(), ceil($to->getY() - 0.5), $to->getFloorZ()); // block beneath the player
			$inAir = !in_array($blockOnId, self::$ignoredBlocks) and $blockBelowId === Block::AIR and $blockInId === Block::AIR;

			if(microtime(true) - $this->lastDamagedTime >= 5) { // player hasn't taken damage for five seconds
				// check fly upwards
				if(($yDistance >= 0.05 or ($this->getInAirTicks() >= 100 and $yDistance >= 0)) // TODO: Improve this so detection isn't triggered when players are moving horizontally
					and $this->lastMoveTime - $this->lastJumpTime >= 2) { // if the movement wasn't downwards and the player hasn't jumped for 2 seconds
					if($inAir) { // make sure the player isn't standing on a slab or stairs and the block directly below them is air
						$secondBlockBelowId = $this->getLevel()->getBlockIdAt($to->getFloorX(), ceil($to->getY() - 2), $to->getFloorZ());
						if($secondBlockBelowId === Block::AIR) { // if two blocks directly below them is air
							$thirdBlockBelowId = $this->getLevel()->getBlockIdAt($to->getFloorX(), $to->getFloorY() - 3, $to->getFloorZ());
							if($thirdBlockBelowId === Block::AIR) { // if three blocks directly below them is air
								$this->flyChances += 2;
							} else {
								$this->flyChances += 1;
							}
						}

						if($yDistance >= 0.6) {
							$this->flyChances += 4;
						} elseif($yDistance >= 0.45) {
							$this->flyChances += 2;
						} elseif($yDistance >= 0.38) {
							$this->flyChances += 1;
						}
					} else {
						if($this->flyChances > 0) { // player isn't in the air
							$this->flyChances -= 1;
						}
					}
				} else {
					if($this->flyChances > 0) { // player just likes to jump
						$this->flyChances -= 2;
					}
				}
			}

			if($this->getInAirTicks() >= 400) { // been in air for more than 60 seconds
				if($inAir) {
					$this->flyChances += 2;
				}
			} elseif($this->flyChances > 0) {
				$this->flyChances -= 1;
			}

			$this->checkFlyTriggers();
		}
	}

	/**
	 * Actions to execute after auth check
	 */
	public function afterAuthCheck() {
	}

	/**
	 * Send the join title to the player
	 */
	public function sendLoginTitle() {
		$this->setHasSentLoginTitle(true);

		$subtitleKey = ($this->isAuthenticated() ? "WELCOME_SUBTITLE" : ($this->isRegistered() ? "WELCOME_LOGIN_SUBTITLE" : "WELCOME_REGISTER_SUBTITLE"));

		$this->addTitle($this->getCore()->getLanguageManager()->translateForPlayer($this, "WELCOME_TITLE", [], false), $this->getCore()->getLanguageManager()->translateForPlayer($this, $subtitleKey, [], false), 10, 100, 10);

		$pk = new LevelEventPacket();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->evid = LevelEventPacket::EVENT_GUARDIAN_CURSE;
		$pk->data = 0;
		$this->dataPacket($pk);
	}

	public function handleAuth(string $message) {
		if($this->isRegistered()) {
			if(hash_equals($this->getHash(), Utils::hash(strtolower($this->getName()), $message))) {
				$this->chatMuted = false;
				$this->setAuthenticated(true);
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
					if(strlen($message) > 3) {
						$this->hash = Utils::hash(strtolower($this->getName()), $message);
						$this->registrationStatus = self::AUTH_CONFIRM;
						$this->sendTranslatedMessage("CONFIRM_PASSWORD_PROMPT", [], true);
					} else {
						$this->sendMessage(LanguageUtils::translateColors("&c- &6Your password must be at least 3 characters long!"));
					}
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
						$this->getCore()->getDatabaseManager()->pushToPool(new AuthUpdateDatabaseRequest($this->getName(), $this->getHash(), $this->getEmail(), $this->getLanguageAbbreviation(), $this->getAddress(), 0, $now = time(), $now));
						break;
					}
					$this->sendTranslatedMessage("INVALID_EMAIL", [], true, false);
					break;
			}
		}
	}

	/**
	 * Checks a players network ban status after querying the database and handles the results accordingly
	 */
	public function checkBanState() {
		if(count(($bans = $this->getBanList()->search(strtolower($this->getName()), $this->getClientId(), $this->getAddress()))) > 0) {
			foreach($bans as $ban) {
				$this->kick($this->getCore()->getLanguageManager()->translateForPlayer($this, "BANNED_KICK", [
					$ban->getIssuer(),
					$ban->getReason(),
					$ban->getExpiry() > 0 ? date("j-n-Y g:i a T", $ban->getExpiry()) : "Never",
				]));
				break;
			}

			if(isset($ban)) {
				// TODO: Cascade update sub-bans to the main ban so only the initial ban has to be updated
				if(count($this->getBanList()->search(null, $this->getClientId())) < 1) { // add new ban if user is banned but logged in with different cid
					$this->getBanList()->add(new BanEntry(-1, $this->getName(), $this->getAddress(), $this->getClientId(), $ban->getExpiry(), $ban->getCreation(), true, $ban->getReason(), $ban->getIssuer()));
					return; // new ban has already been added with this players data
				}

				if(count($this->getBanList()->search(null, null, $this->getAddress())) < 1) { // add new ban if user is banned but logged in with different ip
					$this->getBanList()->add(new BanEntry(-1, $this->getName(), $this->getAddress(), $this->getClientId(), $ban->getExpiry(), $ban->getCreation(), true, $ban->getReason(), $ban->getIssuer()));
					return; // not needed but for consistencies sake :p
				}
			}
		}
	}

	/**
	 * Execute a general database update request for this player
	 */
	public function doGeneralUpdate() {
		$this->getCore()->getDatabaseManager()->pushToPool(new AuthUpdateDatabaseRequest($this->getName(), null, null, $this->getLanguageAbbreviation(), $this->getAddress(), $this->getTimePlayed() + (($now = time()) - $this->getLoginTime()), $now));
	}

	/**
	 * @param $key
	 * @param array $args
	 * @param bool $isImportant
	 * @param bool $center
	 */
	public function sendTranslatedMessage($key, array $args = [], $isImportant = false, $center = true) {
		$this->sendMessage($this->core->getLanguageManager()->translateForPlayer($this, $key, $args, $center), $isImportant);
	}

	/**
	 * @param \pocketmine\event\TextContainer|string $message
	 * @param bool $isImportant
	 *
	 * @return bool
	 */
	public function sendMessage($message, $isImportant = false) {
		if(!$isImportant and $this->hasChatMuted()) {
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
			if(!$source->isCancelled()) {
				$this->lastDamagedTime = microtime(true);
				if($source instanceof EntityDamageByEntityEvent and $source->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
					$attacker = $source->getDamager();
					if($attacker instanceof CorePlayer) {
						$attacker->updateReachTriggers($distance = $this->distance($attacker));
					}
				}
			}
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
		return true;
	}

	public function onUpdate($currentTick) {
		if($currentTick % 100 == 0) { // only check ping every 5 seconds (100 ticks)
			if($this->getPing() >= 1000) {
				if($this->getPing() >= 2000) {
					if($this->pingChances <= 0) {
						$this->sendMessage(LanguageUtils::translateColors("&c[&6WARNING&c] &rYour ping is above 2000ms, you will be kicked soon if it does not improve."));
					}
					$this->pingChances += 2;
				} else {
					if($this->pingChances <= 0) {
						$this->sendMessage(LanguageUtils::translateColors("&c[&6WARNING&c] &rYour ping is above 1000ms, you will be kicked soon if it does not improve."));
					}
					$this->pingChances += 1;
				}
				if($this->pingChances >= 12) {
					Utils::broadcastStaffMessage("&a" . $this->getName() . " &ehas been kicked for high ping ({$this->getPing()}ms)!");
					$this->kick("You have been kicked due to your ping ({$this->getPing()}ms)");
				}
			} else {
				if($this->pingChances > 0) {
					$this->pingChances--;
				}
			}
		}

		if(($currentTick % 1200) == 0) { // every minute
			if($this->state === self::STATE_LOBBY) {
				if($this->killAuraTriggers > 0) {
					$this->killAuraTriggers--;
				}
			}
		}

		return parent::onUpdate($currentTick);
	}

	public function sendCommandData() {
		$default = $this->getCore()->getCommandMap()->getDefaultCommandData();

		if($this->isStaff()) {
			$default = array_merge($default, $this->getCore()->getCommandMap()->getCommandData("staff"));
		}

		$this->commandData = $default;

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
	 * Do the callback for the message check task
	 *
	 * @param string $message
	 */
	public function messageCheckCallback(string $message) {
		$message = TextFormat::LIGHT_PURPLE . $this->getName() . TextFormat::GOLD . ": " . TextFormat::GRAY . TextFormat::clean($message);
		foreach($this->getServer()->getOnlinePlayers() as $p) {
			$p->sendMessage($message);
		}
	}

	/**
	 * @param PlayerChatEvent $event
	 */
	public function onChat(PlayerChatEvent $event) {
		$message = $event->getMessage();
		$event->setCancelled();
		if($this->isOnline() and $this->isAuthenticated()) {
			//if(Main::$debug) $start = microtime(true);
			if(!$this->hasChatMuted()) {
				if(($key = $this->getCore()->getLanguageManager()->check($message)) !== false) {
					$this->sendTranslatedMessage(($key === "" ? "BLOCKED_MESSAGE" : $key), [], true);
				} else {
					$this->getServer()->getScheduler()->scheduleAsyncTask(new CheckMessageTask($this->getName(), $this->hash, $this->lastMessage, $this->lastMessageTime, $message));
				}
			} else {
				$this->sendTranslatedMessage("CANNOT_CHAT_WHILE_MUTED", [], true);
			}
			//if(Main::$debug and isset($start)) {
			//	$end = microtime(true);
			//	echo "<----------- MESSAGE CHECK ----------->" . PHP_EOL;
			//	echo "TIME: " . round($end - $start, 3) . "s " . PHP_EOL;
			//}
		} else {
			$this->handleAuth($message);
		}
	}

	/**
	 * @param PlayerMoveEvent $event
	 */
	public function onMove(PlayerMoveEvent $event) {
		$this->lastMoveTime = $time = microtime(true);

		$this->updateFlyTriggers($event->getTo(), round($event->getTo()->getY() - $event->getFrom()->getY(), 3));
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
	public function onInteract(PlayerInteractEvent $event) {
		if($this->isAuthenticated()) {
			$item = $this->getInventory()->getItemInHand();
			if($item instanceof GUIItem) {
				$item->handleClick($this, true);
			}
		}
	}

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

	/**
	 * @param PlayerLoginEvent $event
	 */
	public function onLogin(PlayerLoginEvent $event) {
		$this->banList = new BanList($this);
	}

}