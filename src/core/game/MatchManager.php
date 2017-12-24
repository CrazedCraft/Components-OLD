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

abstract class MatchManager {

	use CorePluginReference;

	/** @var MatchHeartbeat */
	private $heartbeat;

	/** @var int */
	private $lastTick = 0;

	/** @var Match[] */
	protected $matches = [];

	/** @var bool */
	private $justCreated = true;

	/** @var bool */
	private $closed = false;

	public function __construct(Main $plugin) {
		$this->setCore($plugin);

		$this->heartbeat = new MatchHeartbeat($this);
	}

	/**
	 * @return MatchHeartbeat
	 */
	public function getHeartbeat() : MatchHeartbeat {
		return $this->heartbeat;
	}

	/**
	 * @return int
	 */
	public function getLastTick() : int {
		return $this->lastTick;
	}

	/**
	 * @param $id
	 *
	 * @return Match|null
	 */
	public function getMatch(string $id) : ?Match {
		return $this->matches[$id] ?? null;
	}

	/**
	 * @param Match $match
	 */
	public function addMatch(Match $match) : void {
		$this->matches[$match->getId()] = $match;
	}

	/**
	 * @param $id
	 */
	public function removeMatch(string $id) : void {
		$this->getMatch($id)->close();
		unset($this->matches[$id]);
	}

	/**
	 * Keep all matches moving and clean up inactive ones
	 *
	 * @param $currentTick
	 *
	 * @return bool
	 */
	public function tick(int $currentTick) : bool {
		$tickDiff = $currentTick - $this->lastTick;

		if($tickDiff <= 0) {
			if(!$this->justCreated) {
				$this->getCore()->getLogger()->debug("Expected tick difference of at least 1, got {$tickDiff} for " . get_class($this));
			}

			return true;
		}

		$this->justCreated = false;

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

		$this->lastTick = $currentTick;

		return true;
	}

	/**
	 * Check if the match manager has been closed
	 *
	 * @return bool
	 */
	public function closed() : bool {
		return $this->closed;
	}

	/**
	 * Safely close the match manager
	 */
	public function close() : void {
		if(!$this->closed) {
			$this->closed = true;

			$this->heartbeat->cancel();

			foreach($this->matches as $id => $match) {
				$match->close();
				unset($this->matches[$id]);
			}

			$this->setCore(null);
		}
	}

	public function __destruct() {
		$this->close();
	}

}