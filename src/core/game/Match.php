<?php

/**
 * Match.php – Components
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

namespace core\game;

use core\CorePlayer;
use core\Utils;

class Match {

	/** @var MatchManager */
	private $manager;

	/** @var string */
	private $id = null;

	/** @var bool */
	private $active = true;

	/** @var int */
	protected $lastTick = 0;

	/** @var CorePlayer[] */
	private $players = [];

	/** @var CorePlayer[] */
	private $spectators = [];

	public function __construct(MatchManager $manager) {
		$this->manager = $manager;
		$this->id = md5(spl_object_hash($this));
	}

	/**
	 * @return MatchManager
	 */
	public function getManager() {
		return $this->manager;
	}

	/**
	 * @return string
	 */
	public function getId() : string {
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isActive() {
		return $this->active;
	}

	/**
	 * @return int
	 */
	public function getLastTick() {
		return $this->lastTick;
	}

	/**
	 * Check if a player is in the match using a player
	 *
	 * @param CorePlayer $player
	 *
	 * @return bool
	 */
	public function inMatchAsPlayerByPlayer(CorePlayer $player) {
		return isset($this->players[$player->getName()]) and $player->getUniqueId()->toString() === $this->players[$player->getName()];
	}

	/**
	 * Check if a player is in the match by using their name
	 *
	 * @param string $name
	 * @param bool $findPlayer
	 *
	 * @return bool
	 */
	public function inMatchAsPlayerByName(string $name, bool $findPlayer = false) {
		if($findPlayer) {
			$target = $this->manager->getCore()->getServer()->getPlayerExact($name);
			if($target instanceof CorePlayer and $target->isOnline()) {
				return isset($this->players[$target->getName()]) and $target->getUniqueId()->toString() === $this->players[$target->getName()];
			}
		}
		return isset($this->players[$name]);
	}

	/**
	 * Add a player to the match
	 *
	 * @param CorePlayer $player
	 */
	public function addPlayer(CorePlayer $player) {
		$name = $player->getName();
		if(!isset($this->players[$name])) {
			$this->players[$name] = $player->getUniqueId()->toString();
		}
	}

	/**
	 * Remove a player from the match using a player
	 *
	 * @param CorePlayer $player
	 */
	public function removePlayerByPlayer(CorePlayer $player) {
		if($this->inMatchAsPlayerByPlayer($player)) {
			unset($this->players[$player->getName()]);
		}
	}

	/**
	 * Remove a player from the match using their name
	 *
	 * @param string $name
	 */
	public function removePlayerByName(string $name) {
		if($this->inMatchAsPlayerByName($name, false)) {
			unset($this->players[$name]);
		}
	}

	/**
	 * Check if a player is spectating the match using a player
	 *
	 * @param CorePlayer $player
	 *
	 * @return bool
	 */
	public function inMatchAsSpectatorByPlayer(CorePlayer $player) {
		return isset($this->spectators[$player->getName()]) and $player->getUniqueId()->toString() === $this->spectators[$player->getName()];
	}

	/**
	 * Check if a player is spectating the match by using their name
	 *
	 * @param string $name
	 * @param bool $findPlayer
	 *
	 * @return bool
	 */
	public function inMatchAsSpectatorByName(string $name, bool $findPlayer = false) {
		if($findPlayer) {
			$target = $this->manager->getCore()->getServer()->getPlayerExact($name);
			if($target instanceof CorePlayer and $target->isOnline()) {
				return isset($this->spectators[$target->getName()]) and $target->getUniqueId()->toString() === $this->spectators[$target->getName()];
			}
		}
		return isset($this->spectators[$name]);
	}

	/**
	 * Add a spectator to the match
	 *
	 * @param CorePlayer $player
	 */
	public function addSpectator(CorePlayer $player) {
		$name = $player->getName();
		if(!isset($this->spectators[$name])) {
			$this->spectators[$name] = $player->getUniqueId()->toString();
		}
	}

	/**
	 * Remove a spectating player from the match using a player
	 *
	 * @param CorePlayer $player
	 */
	public function removeSpectatorByPlayer(CorePlayer $player) {
		if($this->inMatchAsSpectatorByPlayer($player)) {
			unset($this->spectators[$player->getName()]);
		}
	}

	/**
	 * Remove a spectating player from the match using their name
	 *
	 * @param string $name
	 */
	public function removeSpectatorByName(string $name) {
		if($this->inMatchAsSpectatorByName($name, false)) {
			unset($this->spectators[$name]);
		}
	}

	/**
	 * Broadcast a message to all players and spectators in the match
	 *
	 * @param string $message
	 */
	public function broadcastMessage(string $message) {
		foreach(array_merge($this->players, $this->spectators) as $name => $uuid) {
			$player = Utils::getPlayerByUUID($uuid);
			if($player instanceof CorePlayer and $player->isOnline()) {
				$player->sendMessage($message);
			}
		}
	}

	/**
	 * Broadcast a popup to all players and spectators in the match
	 *
	 * @param string $message
	 */
	public function broadcastPopup(string $message) {
		foreach(array_merge($this->players, $this->spectators) as $name => $uuid) {
			$player = Utils::getPlayerByUUID($uuid);
			if($player instanceof CorePlayer and $player->isOnline()) {
				$player->sendPopup($message);
			}
		}
	}

	/**
	 * Broadcast a tip to all players and spectators in the match
	 *
	 * @param string $message
	 */
	public function broadcastTip(string $message) {
		foreach(array_merge($this->players, $this->spectators) as $name => $uuid) {
			$player = Utils::getPlayerByUUID($uuid);
			if($player instanceof CorePlayer and $player->isOnline()) {
				$player->sendTip($message);
			}
		}
	}

	/**
	 * Broadcast a title to all players and spectators in the duel
	 *
	 * @param string $title
	 * @param string $subtitle
	 * @param int $fadeIn
	 * @param int $stay
	 * @param int $fadeOut
	 */
	public function broadcastTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1) {
		foreach(array_merge($this->players, $this->spectators) as $name => $uuid) {
			$player = Utils::getPlayerByUUID($uuid);
			if($player instanceof CorePlayer and $player->isOnline()) {
				$player->addTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
			}
		}
	}

	/**
	 * @param int $currentTick
	 */
	public function tick($currentTick) {
		$this->checkPlayers();
		$this->lastTick = $currentTick;
	}

	public function checkPlayers() {
		foreach($this->players as $name => $player) {
			if($player instanceof CorePlayer and $player->isOnline()) {

			} else {

			}
		}
	}

	/**
	 * Safely close the match instance
	 */
	public function close() {
		if($this->active) {
			foreach($this->players as $player) $this->removePlayer($player);
			foreach($this->spectators as $spectator) $this->removeSpectator($spectator);
		}
	}

	public function __destruct() {
		$this->close();
	}

}