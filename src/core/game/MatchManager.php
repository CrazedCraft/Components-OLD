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
 * Last modified on 15/10/2017 at 2:04 AM
 *
 */

namespace core\game;

use pocketmine\event\TimingsHandler;
use pocketmine\plugin\Plugin;

class MatchManager {

	/** @var Plugin */
	private $plugin;

	///** @var TimingsHandler */
	//private $timings;

	/** @var MatchHeartbeat */
	private $heartbeat;

	/** @var int */
	private $lastTick = 0;

	/** @var Match[] */
	private $matches = [];

	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		//$this->timings = new TimingsHandler("Match Manager");
		$this->heartbeat = new MatchHeartbeat($this);
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin() {
		return $this->plugin;
	}

	///**
	// * @return TimingsHandler
	// */
	//public function getTimingsHandler() {
	//	return $this->timings;
	//}

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
		//$this->timings->startTiming();
		foreach($this->matches as $key => $match) {
			if($match instanceof Match) {
				if($match->isActive()) {
					$match->tick($currentTick);
				} else {
					$match->close();
					unset($this->matches[$key]);
				}
			} else {
				unset($this->matches[$key]);
			}
		}
		//$this->timings->stopTiming();
		$this->plugin->getLogger()->debug("Ticked MatchManager in " . round(($tickDiff) / 20) . " seconds ($tickDiff)!");
		$this->lastTick = $currentTick;
	}

}