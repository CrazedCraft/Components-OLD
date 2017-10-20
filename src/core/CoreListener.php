<?php

/**
 * CoreListener.php – Components
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

use core\database\request\auth\AuthLoginDatabaseRequest;
use core\database\request\ban\BanCheckDatabaseRequest;
use core\entity\text\FloatingText;
use core\gui\container\ContainerGUI;
use core\gui\item\GUIItem;
use core\language\LanguageManager;
use core\util\traits\CorePluginReference;
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
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\CommandStepPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\Player;

class CoreListener implements Listener {

	use CorePluginReference;

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
		$this->setCore($plugin);
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * Set the global player counts
	 *
	 * @param QueryRegenerateEvent $event
	 */
	public function onQueryRegenerate(QueryRegenerateEvent $event) {
		$event->setPlayerCount($this->getCore()->getNetworkManager()->getOnlinePlayers()); // set the servers online players to the network value
		$event->setMaxPlayerCount($this->getCore()->getNetworkManager()->getMaxPlayers()); // set the available slots to the network value
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

		$player->setDataProperty(Entity::DATA_FLAG_INVISIBLE, Entity::DATA_TYPE_BYTE, 1); // make players invisible until they'tr authenticated

		/** @var CorePlayer $p */
		foreach($this->getCore()->getServer()->getOnlinePlayers() as $p) {
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

		$this->getCore()->getDatabaseManager()->pushToPool(new AuthLoginDatabaseRequest($player->getName()));
		$this->getCore()->getDatabaseManager()->pushToPool(new BanCheckDatabaseRequest(strtolower($player->getName()), $player->getAddress(), $player->getClientId(), $player->getXUID()));

		$player->setChatMuted(true);
	}

	public function onLogin(PlayerLoginEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$player->onLogin($event);
	}

	public function onJoin(PlayerJoinEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$player->sendCommandData();
		$player->setNameTag(Utils::translateColors("&e" . $player->getName()));
		foreach($this->getCore()->floatingText as $text) {
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
		if($player->isAuthenticated())
			$player->doGeneralUpdate();
	}

	/**
	 * @param PlayerItemHeldEvent $event
	 *
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onItemHeld(PlayerItemHeldEvent $event) {
		/** @var CorePlayer $player */
		$player = $event->getPlayer();
		$item = $event->getItem();
		if($item instanceof GUIItem) {
			$item->sendPreview($player);
		}
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
			if(!isset($source->commandData[$name])) {
				foreach($source->commandData as $n => $d) { // loop over all commands to find an alias that matches the given command name
					if(isset($d["versions"][0]["aliases"]) and in_array($name, $d["versions"][0]["aliases"])) {
						$name = $n;
						break;
					}
				}
			}
			if(!isset($source->commandData[$name])) {
				$source->sendMessage("Unknown command!");
				return;
			}
			$params = json_decode($pk->outputFormat, true);
			$command = "/" . $name;
			$data = $source->commandData[$name];
			$expected = $data["versions"][0]["overloads"][$pk->overload]["input"]["parameters"];
			foreach($expected as $expectedArgument) {
				if((!isset($params[$expectedArgument["name"]]) and (isset($expectedArgument["name"]["optional"])) and $expectedArgument["name"]["optional"] !== true)) {
					$source->sendMessage("Incorrect arguments given for {$name} command!");
					return;
				}
				if(isset($params[$expectedArgument["name"]])) {
					$data = $params[$expectedArgument["name"]];
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
									$players = $this->getCore()->getServer()->getOnlinePlayers();
									$player = $players[array_rand($players)]->getName();
									break;
								case "allEntities":
									// no handling here yet
									$player = "@e";
									break;
								default:
									$this->getCore()->getServer()->getLogger()->warning("Unhandled selector for target argument!");
									var_dump($selector);
									$player = " ";
									break;
							}
							$command .= " " . $player;
						} else { // Another argument type?
							$this->getCore()->getServer()->getLogger()->warning("No selector set for target argument!");
							var_dump($data);
						}
					} elseif(is_string($data)) { // Normal string argument
						$command .= " " . $data;
					} else { // Unhandled argument type
						$this->getCore()->getServer()->getLogger()->warning("Unhandled command data type!");
						var_dump($data);
					}
				}
			}
			$ev = new PlayerCommandPreprocessEvent($source, $command);
			$this->getCore()->getServer()->getPluginManager()->callEvent($ev);
			if($ev->isCancelled()) {
				return;
			}
			$this->getCore()->getServer()->dispatchCommand($source, substr($ev->getMessage(), 1));
		} elseif($pk instanceof AdventureSettingsPacket) {
			if(($source->isSurvival() or $source->isAdventure()) and ($pk->flags >> 9) & 0x01 === 1 or !$source->isSpectator() && ($pk->flags >> 7) & 0x01 === 1) {
				$event->setCancelled(true);
				$source->kick($this->getCore()->getLanguageManager()->translateForPlayer($source, "KICK_BANNED_MOD", ["Fly"]));
			}
		} elseif($pk instanceof ContainerSetSlotPacket) {
			$inv = $source->getWindowById($pk->windowid);
			if($inv instanceof ContainerGUI) {
				$item = $inv->getItem($pk->slot);
				if($item instanceof GUIItem) {
					$inv->onSelect($pk->slot, $item, $source);
					$event->setCancelled(true);
				}
			}
		}
	}

}