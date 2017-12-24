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

abstract class Match {

	/** @var MatchManager */
	protected $manager;

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

	/** @var bool */
	private $closed = false;

	public function __construct(MatchManager $manager) {
		$this->manager = $manager;
		$this->id = md5(spl_object_hash($this));
	}

	/**
	 * @return MatchManager
	 */
	public function getManager() : MatchManager {
		return $this->manager;
	}

	/**
	 * @return string
	 */
	final public function getId() : string {
		return $this->id;
	}

	/**
	 * @return bool
	 */
	final public function isActive() : bool {
		return $this->active;
	}

	/**
	 * @return int
	 */
	final public function getLastTick() : int {
		return $this->lastTick;
	}

	/**
	 * Check if a player is in the match using a player
	 *
	 * @param CorePlayer $player
	 *
	 * @return bool
	 */
	final public function inMatchAsPlayerByPlayer(CorePlayer $player) : bool {
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
	final public function inMatchAsPlayerByName(string $name, bool $findPlayer = false) : bool {
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
	final public function addPlayer(CorePlayer $player) : void {
		$name = $player->getName();
		if(!isset($this->players[$name])) {
			$this->players[$name] = $player->getUniqueId()->toString();
		}
	}

	/**
	 * Get all players excluding the players in the array
	 *
	 * @param CorePlayer[] $exclude
	 *
	 * @return CorePlayer[]
	 */
	final public function getPlayersExcept(array $exclude) : array {
		$gamePlayers = $this->players; // players currently playing
		foreach($exclude as $p) {
			unset($gamePlayers[$p->getName()]); // remove player from players in game if in array to be excluded
		}

		foreach($gamePlayers as $name => $uuid) {
			$gamePlayers[$name] = Utils::lookupUuid($uuid) ?? $uuid; // fetch the actual player object from the uuid
		}

		return $gamePlayers;
	}

	/**
	 * Remove a player from the match using a player
	 *
	 * @param CorePlayer $player
	 */
	final public function removePlayerByPlayer(CorePlayer $player) : void {
		if($this->inMatchAsPlayerByPlayer($player)) {
			unset($this->players[$player->getName()]);
		}
	}

	/**
	 * Remove a player from the match using their name
	 *
	 * @param string $name
	 */
	final public function removePlayerByName(string $name) : void {
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
	final public function inMatchAsSpectatorByPlayer(CorePlayer $player) : bool {
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
	final public function inMatchAsSpectatorByName(string $name, bool $findPlayer = false) : bool {
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
	final public function addSpectator(CorePlayer $player) : void {
		$name = $player->getName();
		if(!isset($this->spectators[$name])) {
			$this->spectators[$name] = $player->getUniqueId()->toString();
		}
	}

	/**
	 * Get all spectators excluding the players in the array
	 *
	 * @param CorePlayer[] $exclude
	 *
	 * @return CorePlayer[]
	 */
	final public function getSpectatorsExcept(array $exclude) : array {
		$gameSpectators = $this->spectators; // spectators currently spectating
		foreach($exclude as $p) {
			unset($gameSpectators[$p->getName()]); // remove player from spectators in game if in array to be excluded
		}

		foreach($gameSpectators as $name => $uuid) {
			$gameSpectators[$name] = Utils::lookupUuid($uuid) ?? $uuid; // fetch the actual player object from the uuid
		}

		return $gameSpectators;
	}

	/**
	 * Remove a spectating player from the match using a player
	 *
	 * @param CorePlayer $player
	 */
	final public function removeSpectatorByPlayer(CorePlayer $player) : void {
		if($this->inMatchAsSpectatorByPlayer($player)) {
			unset($this->spectators[$player->getName()]);
		}
	}

	/**
	 * Remove a spectating player from the match using their name
	 *
	 * @param string $name
	 */
	final public function removeSpectatorByName(string $name) : void {
		if($this->inMatchAsSpectatorByName($name, false)) {
			unset($this->spectators[$name]);
		}
	}

	/**
	 * Broadcast a message to all players and spectators in the match
	 *
	 * @param string $message
	 */
	final public function broadcastMessage(string $message) : void {
		foreach(array_merge($this->players, $this->spectators) as $name => $uuid) {
			$player = Utils::lookupUuid($uuid);
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
	final public function broadcastPopup(string $message) : void {
		foreach(array_merge($this->players, $this->spectators) as $name => $uuid) {
			$player = Utils::lookupUuid($uuid);
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
	final public function broadcastTip(string $message) : void {
		foreach(array_merge($this->players, $this->spectators) as $name => $uuid) {
			$player = Utils::lookupUuid($uuid);
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
	final public function broadcastTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1) : void {
		foreach(array_merge($this->players, $this->spectators) as $name => $uuid) {
			$player = Utils::lookupUuid($uuid);
			if($player instanceof CorePlayer and $player->isOnline()) {
				$player->addTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
			}
		}
	}

	/**
	 * @param $currentTick
	 *
	 * @return bool
	 */
	public function tick($currentTick) : bool {
		if($this->closed or !$this->active) {
			return false;
		}

		$this->lastTick = $currentTick;

		return true;
	}

	/**
	 * Checks all players and spectators to make sure they're online
	 *
	 * @param string[] $removedPlayers     An array of player names who were removed
	 * @param string[] $removedSpectators  An array of spectator names who were removed
	 */
	final protected function checkPlayers(array &$removedPlayers = [], array &$removedSpectators = []) : void {
		foreach($this->players as $name => $uuid) {
			$player = Utils::lookupUuid($uuid);
			if(!($player instanceof CorePlayer) and !$player->isOnline()) {
				$this->removePlayerByName($name);
				$removedPlayers[] = $name;
			}
		}

		foreach($this->spectators as $name => $uuid) {
			$player = Utils::lookupUuid($uuid);
			if(!($player instanceof CorePlayer) and !$player->isOnline()) {
				$this->removeSpectatorByName($name);
				$removedSpectators[] = $name;
			}
		}
	}

	/**
	 * Check if the match has been closed
	 *
	 * @return bool
	 */
	final public function closed() : bool {
		return $this->closed;
	}

	/**
	 * Safely close the match instance
	 */
	public function close() : void {
		if(!$this->closed) {
			$this->closed = true;

			if($this->active) {
				foreach($this->players as $name => $uuid)
					$this->removePlayerByName($name);
				foreach($this->spectators as $name => $uuid)
					$this->removeSpectatorByName($name);
			}

			$this->players = [];
			$this->spectators = [];

			unset($this->manager);
		}
	}

	public function __destruct() {
		$this->close();
	}

}