<?php

/**
 * CoreDatabaseManager.php – Components
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

namespace core\database;

use core\database\auth\AuthDatabase;
use core\database\auth\mysql\MySQLAuthDatabase;
use core\database\ban\BanDatabase;
use core\database\ban\mysql\MySQLBanDatabase;
use core\database\mysql\MySQLCredentials;
use core\database\network\mysql\MySQLNetworkDatabase;
use core\database\network\NetworkDatabase;
use core\database\rank\mysql\MySQLRankDatabase;
use core\database\rank\RankDatabase;

class CoreDatabaseManager extends DatabaseManager {

	/** @var AuthDatabase */
	private $authDatabase;

	/** @var BanDatabase */
	private $banDatabase;

	/** @var RankDatabase */
	private $rankDatabase;

	/** @var NetworkDatabase */
	private $networkDatabase;

	/** @var bool */
	private $closed = false;

	/**
	 * Load up all the databases
	 */
	protected function init() {
		$this->setAuthDatabase();
		$this->setBanDatabase();
//		$this->setRankDatabase();
		$this->setNetworkDatabase();
	}

	/**
	 * Set the auth database
	 */
	public function setAuthDatabase() {
		$this->authDatabase = new MySQLAuthDatabase($this->getPlugin(), MySQLCredentials::fromArray($this->getPlugin()->getSettings()->getNested("settings.database")));
	}

	/**
	 * Set the bans database
	 */
	public function setBanDatabase() {
		$this->banDatabase = new MySQLBanDatabase($this->getPlugin(), MySQLCredentials::fromArray($this->getPlugin()->getSettings()->getNested("settings.database")));
	}

	/**
	 * Set the ranks database
	 */
	public function setRankDatabase() {
		$this->banDatabase = new MySQLRankDatabase($this->getPlugin(), MySQLCredentials::fromArray($this->getPlugin()->getSettings()->getNested("settings.database")));
	}

	/**
	 * Set the network database
	 */
	public function setNetworkDatabase() {
		$this->networkDatabase = new MySQLNetworkDatabase($this->getPlugin(), MySQLCredentials::fromArray($this->getPlugin()->getSettings()->getNested("settings.database")));
	}

	/**
	 * @return AuthDatabase
	 */
	public function getAuthDatabase() {
		return $this->authDatabase;
	}

	/**
	 * @return BanDatabase
	 */
	public function getBanDatabase() {
		return $this->banDatabase;
	}

	/**
	 * @return RankDatabase
	 */
	public function getRankDatabase() {
		return $this->rankDatabase;
	}

	/**
	 * @return NetworkDatabase|MySQLNetworkDatabase
	 */
	public function getNetworkDatabase() {
		return $this->networkDatabase;
	}

}