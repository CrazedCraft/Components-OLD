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

use core\entity\text\FloatingText;
use core\language\LanguageManager;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\CommandStepPacket;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CoreListener implements Listener {

	/** @var Main */
	private $plugin;

	/* Array of commands that a player can execute at any time */
	public static $whitelistedCommands = [
		"login",
		"authenticate",
		"l",
		"register",
		"claim",
		"r",
		"help",
		"h"
	];

	/* Array of banned commands */
	public static $bannedCommands = [
		"me",
		"op",
		"effect",
		"tp",
		"ban-ip",
		"pardon",
		"pardon-ip",
		"deop",
		"give",
		"plugins",
		"reload",
		"seed",
		"spawnpoint",
		"setworldspawn",
		"stop",
		"whitelist",
		"version",
		"time",
		"status",
		"gamemode",
		"particles",
		"say"
	];

	/**
	 * CoreListener constructor.
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * @return mixed
	 */
	public function getPlugin() {
		return $this->getPlugin();
	}

	/**
	 * Make sure all worlds don't save and the time is locked
	 *
	 * @param LevelLoadEvent $event
	 */
	public function onLevelLoad(LevelLoadEvent $event) {
		$level = $event->getLevel();
		$level->setAutoSave(false);
		$level->setTime(6000);
		$level->stopTime();
	}

	/**
	 * Sets all players to a core player on creation
	 *
	 * @param PlayerCreationEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onPlayerCreation(PlayerCreationEvent $event) {
		$event->setPlayerClass(CorePlayer::class);
	}

	public function onPreLogin(PlayerPreLoginEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$ips = 0;
		$player->setDataProperty(Entity::DATA_FLAG_INVISIBLE, Entity::DATA_TYPE_BYTE, 1);
		/** @var CorePlayer $p */
		foreach($this->plugin->getServer()->getOnlinePlayers() as $p) {
			$p->hidePlayer($player);
			if(!$p->isAuthenticated())
				$player->hidePlayer($p);
			if($p->getName() === $player->getName()) {
				if($p->getAddress() === $player->getAddress()) {
					$event->setKickMessage(LanguageManager::getInstance()->translate("LOGIN_FROM_ANOTHER_LOCATION", "en"));
					$event->setCancelled(true);
				} else {
					$event->setKickMessage(LanguageManager::getInstance()->translate("ALREADY_ONLINE", "en"));
					$event->setCancelled(true);
				}
				return;
			}
			if($p->getAddress() === $player->getAddress()) $ips++;
		}
		if($ips >= 5) {
			$event->setKickMessage(LanguageManager::getInstance()->translate("MAX_CONNECTIONS", "en"));
			$event->setCancelled(true);
			return;
		}
		$this->plugin->getDatabaseManager()->getAuthDatabase()->login($player->getName());
		$this->plugin->getDatabaseManager()->getBanDatabase()->check($player->getName(), $player->getAddress(), $player->getClientId(), true);
		$player->setChatMuted(true);
	}

	public function onJoin(PlayerJoinEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$player->sendCommandData();
		$player->setNameTag(Utils::translateColors("&e" . $player->getName()));
		foreach($this->plugin->floatingText as $text) {
			if($text instanceof FloatingText) $text->spawnTo($player);
		}
	}

	/**
	 * Handle player chatting
	 *
	 * @param PlayerChatEvent $event
	 */
	public function onChat(PlayerChatEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onChat($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Handles unauthenticated command execution
	 *
	 * @param PlayerCommandPreprocessEvent $event
	 */
	public function onCommandPreProcess(PlayerCommandPreprocessEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$message = $event->getMessage();
		if(substr($message, 0, 1) === "/") {
			$command = substr($message, 1);
			$args = explode(" ", $command);

			if(!$player->isAuthenticated()) {
				if(in_array($args[0], self::$whitelistedCommands)) {
					// let the command do it's thing ;p
					return;
				} else {
					$event->setCancelled(true);
					$player->sendTranslatedMessage("MUST_AUTHENTICATE_FIRST", [], true);
					return;
				}
			}
			if(in_array(strtolower($args[0]), self::$bannedCommands)) {
				$event->setCancelled(true);
				$player->sendTranslatedMessage("COMMAND_BANNED");
				return;
			}
		}
	}

	/**
	 * Handle players breaking blocks
	 *
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onBreak($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Handle players placing blocks
	 *
	 * @param BlockPlaceEvent $event
	 */
	public function onPlace(BlockPlaceEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onPlace($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Despawn arrows when they land
	 *
	 * @param ProjectileHitEvent $event
	 */
	public function onArrowHit(ProjectileHitEvent $event) {
		$event->getEntity()->kill();
	}

	/**
	 * Handle player movement
	 *
	 * @param PlayerMoveEvent $event
	 */
	public function onMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onMove($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Handle player item dropping
	 *
	 * @param PlayerDropItemEvent $event
	 */
	public function onItemDrop(PlayerDropItemEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onDrop($event);
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * Handle player interaction
	 *
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof CorePlayer) {
			$player->onInteract($event);
		} else {
			$event->setCancelled();
		}
	}

	public function onQuit(PlayerQuitEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$event->setQuitMessage("");
		$this->plugin->getDatabaseManager()->getAuthDatabase()->update($player->getName(), $player->getAuthData());
	}

	public function onKick(PlayerKickEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$event->setQuitMessage("");
		$this->plugin->getDatabaseManager()->getAuthDatabase()->update($player->getName(), $player->getAuthData());
	}

	/**
	 * Intercept incoming data packet before they're handled by the server
	 *
	 * @param DataPacketReceiveEvent $event
	 *
	 * @priority MONITOR
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) {
		/** @var CorePlayer $source */
		$source = $event->getPlayer();
		$pk = $event->getPacket();
		if($pk instanceof LoginPacket) { // Intercept the client OS info
			$source->setDeviceOs($pk->osType);
		} elseif($pk instanceof CommandStepPacket) { // Handle commands
			$event->setCancelled(true);
			$name = $pk->name;
			$params = json_decode(json_encode($pk->overload), true);
			$command = "/" . $name;
			if(is_array($params)) {
				foreach($params as $param => $data) {
					if(is_array($data)) { // Target argument type
						if(isset($data["selector"])) {
							$selector = $data["selector"];
							switch($selector) {
								case "nearestPlayer":
									if(isset($data["rules"])) { // Player has been specified
										$player = $data["rules"][0]["value"]; // Player name
										break;
									}
									$nearest = null;
									$distance = PHP_INT_MAX;
									foreach($source->getViewers() as $p) {
										if($p instanceof Player) {
											$dist = $source->distance($p->getPosition());
											if($dist < $distance) {
												$nearest = $p;
												$distance = $dist;
											}
										}
									}
									if($nearest instanceof Player) {
										$player = $nearest->getName();
									} else {
										$player = "@p";
									}
									break;
								case "allPlayers":
									// no handling here yet
									$player = "@a";
									break;
								case "randomPlayer":
									$players = $this->plugin->getServer()->getOnlinePlayers();
									$player = $players[array_rand($players)]->getName();
									break;
								case "allEntities":
									// no handling here yet
									$player = "@e";
									break;
								default:
									$this->plugin->getServer()->getLogger()->warning("Unhandled selector for target argument!");
									var_dump($selector);
									$player = " ";
									break;
							}
							$command .= " " . $player;
						} else { // Another argument type?
							$this->plugin->getServer()->getLogger()->warning("No selector set for target argument!");
							var_dump($data);
						}
					} elseif(is_string($data)) { // Normal string argument
						$command .= " " . $data;
					} else { // Unhandled argument type
						$this->plugin->getServer()->getLogger()->warning("Unhandled command data type!");
						var_dump($data);
					}
				}
			}
			$ev = new PlayerCommandPreprocessEvent($source, $command);
			$this->plugin->getServer()->getPluginManager()->callEvent($ev);
			if($ev->isCancelled()) {
				return;
			}
			$this->plugin->getServer()->dispatchCommand($source, substr($ev->getMessage(), 1));
		}
	}

}