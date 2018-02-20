<?php

/**
 * Team.php – Components
 *
 * Copyright (C) 2015-2018 Jack Noordhuis
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Jack Noordhuis
 *
 */

declare(strict_types=1);

namespace core\game\team;

use core\CorePlayer;
use core\game\Match;
use core\Utils;
use pocketmine\utils\TextFormat;

class Team {

	/** @var Match */
	private $battle;

	/** @var int */
	private $playerCount = 0;

	/** @var string[] */
	private $players = [];

	/** @var string */
	private $name = "";

	/** @var int */
	private $color;

	/** @var string */
	private $chatColor = TextFormat::RESET;

	/** @var bool */
	private $closed = false;

	public function __construct(Match $battle, string $name, int $color, string $chatColor) {
		$this->battle = $battle;
		$this->name = $name;
		$this->color = $color;
		$this->chatColor = $chatColor;
	}

	/**
	 * Get all players on the team
	 *
	 * @return CorePlayer[]
	 */
	public function getPlayers() : array {
		$players = $this->players;
		foreach($players as $name => $uuid) {
			$players[$name] = Utils::getPlayerByUUID($uuid);
		}

		return $players;
	}

	/**
	 * Check if a player is on this team
	 *
	 * @param CorePlayer $player
	 *
	 * @return bool
	 */
	public function playerOnTeam(CorePlayer $player) : bool {
		return isset($this->players[$player->getName()]);
	}

	/**
	 * Add a player to the team
	 *
	 * @param CorePlayer $player
	 */
	public function addPlayer(CorePlayer $player) : void {
		$this->players[$player->getName()] = $player->getUniqueId()->toString();
		$this->playerCount++;
	}

	/**
	 * Get the number of players on the team
	 *
	 * @return int
	 */
	public function getPlayerCount() : int {
		return $this->playerCount;
	}

	/**
	 * Get this teams name
	 *
	 * @return string
	 */
	public function getName() : string {
		return $this->name;
	}

	/**
	 * Get this teams color id
	 *
	 * @return int
	 */
	public function getColor() : int {
		return $this->color;
	}

	/**
	 * Get this teams chat color
	 *
	 * @return string
	 */
	public function getChatColor() : string {
		return $this->chatColor;
	}

	/**
	 * Broadcast a message to all players on the team
	 *
	 * @param string $message
	 */
	public function broadcastMessage(string $message) : void {
		foreach($this->players as $name => $uuid) {
			Utils::getPlayerByUUID($uuid)->sendMessage($message);
		}
	}

	/**
	 * Check if the team still has players
	 *
	 * @return bool
	 */
	public function hasPlayers() : bool {
		return $this->playerCount <= 0;
	}

	/**
	 * Check if the team is closed
	 *
	 * @return bool
	 */
	public function closed() : bool {
		return $this->closed;
	}

	/**
	 * Safely close the team
	 */
	public function close() : void {
		if(!$this->closed) {
			$this->closed = true;

			foreach($this->players as $name => $uuid) {
				unset($this->players[$name]);
			}

			$this->players = [];

			unset($this->battle);
		}
	}

}