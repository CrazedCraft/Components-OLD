<?php

/**
 * MatchManager.php – Components
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

use core\Main;
use core\util\traits\CorePluginReference;

class MatchManager {

	use CorePluginReference;

	/** @var MatchHeartbeat */
	private $heartbeat;

	/** @var int */
	private $lastTick = 0;

	/** @var Match[] */
	private $matches = [];

	public function __construct(Main $plugin) {
		$this->setCore($plugin);
		$this->heartbeat = new MatchHeartbeat($this);
	}

	/**
	 * @return MatchHeartbeat
	 */
	public function getHeartbeat() {
		return $this->heartbeat;
	}

	/**
	 * @return int
	 */
	public function getLastTick() {
		return $this->lastTick;
	}

	/**
	 * @param $id
	 *
	 * @return Match|null
	 */
	public function getMatch($id) {
		return $this->matches[$id] ?? null;
	}

	/**
	 * @param Match $match
	 */
	public function addMatch(Match $match) {
		$this->matches[$match->getId()] = $match;
	}

	/**
	 * @param $id
	 */
	public function removeMatch($id) {
		$this->getMatch($id)->close();
		unset($this->matches[$id]);
	}

	/**
	 * Keep all matches moving and clean up inactive ones
	 *
	 * @param $currentTick
	 */
	public function tick($currentTick) {
		$tickDiff = $currentTick - $this->lastTick;
		foreach($this->matches as $key => $match) {
			if($match instanceof Match) {
				if($match->isActive()) {
					$match->tick($currentTick);
				} else {
					$this->removeMatch($key);
				}
			} else {
				unset($this->matches[$key]);
				throw new \RuntimeException("Tried to tick invalid match!");
			}
		}

		$this->getCore()->getLogger()->debug("Ticked MatchManager in " . round(($tickDiff) / 20) . " seconds ($tickDiff)!");
		$this->lastTick = $currentTick;
	}

}