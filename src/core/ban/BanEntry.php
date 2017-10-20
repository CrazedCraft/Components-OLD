<?php

/**
 * BanEntry.php – Components
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
 * Last modified on 20/10/2017 at 5:50 PM
 *
 */

namespace core\ban;

use core\database\request\ban\BanUpdateRequest;
use core\Main;

/**
 * Represents a ban for a user
 */
class BanEntry {

	/**
	 * Create a ban entry from a database row
	 *
	 * @param array $row
	 *
	 * @return BanEntry
	 */
	public static function fromRow(array $row) {
		return new BanEntry($row["id"], $row["username"], $row["ip"], $row["cid"], $row["expires"], $row["created"], $row["valid"], $row["reason"], $row["issuer"]);
	}

	/** @var int */
	private $id;

	/** @var string */
	private $username;

	/** @var string */
	private $ip;

	/** @var string */
	private $cid;

	/** @var string */
	private $xuid;

	/** @var int */
	private $expiry;

	/** @var int */
	private $creation;

	/** @var bool */
	private $valid;

	/** @var string */
	private $reason;

	/** @var string */
	private $issuer;

	/**
	 * BanEntry constructor.
	 *
	 * @param int $id               ID of the ban
	 * @param string $name          Username of the user banned
	 * @param string $ip            IP of the user banned
	 * @param string $cid           CID of the user banned
	 * @param int $expiry           Expiry  timestamp of the ban
	 * @param int $creation         Creation timestamp of the ban
	 * @param bool $valid           If the ban is still valid
	 * @param string $reason        Reason for the ban
	 * @param string $issuer        Name of the ban issuer
	 */
	public function __construct(int $id, string $name, string $ip, string $cid, int $expiry, int $creation, bool $valid, string $reason, string $issuer) {
		$this->id = $id;
		$this->username = $name;
		$this->ip = $ip;
		$this->cid = $cid;
		$this->xuid = "";
		$this->expiry = $expiry;
		$this->creation = $creation;
		$this->valid = $valid;
		$this->reason = $reason;
		$this->issuer = $issuer;
	}

	/**
	 * Get the unique id of the ban
	 *
	 * @return int
	 */
	public function getId() : int {
		return $this->id;
	}

	/**
	 * Get the username of the ban
	 *
	 * @return string
	 */
	public function getUsername() : string {
		return $this->username;
	}

	/**
	 * Get the ip of the ban
	 *
	 * @return string
	 */
	public function getIp() : string {
		return $this->ip;
	}

	/**
	 * Get the cid of the ban
	 *
	 * @return string
	 */
	public function getClientId() : string {
		return $this->cid;
	}

	/**
	 * Get the xuid of the ban
	 *
	 * @return string
	 */
	public function getXuid() : string {
		return $this->xuid;
	}

	/**
	 * Get the expiry timestamp of the ban
	 *
	 * @return int
	 */
	public function getExpiry() : int {
		return $this->expiry;
	}

	/**
	 * Get the creation timestamp of the ban
	 *
	 * @return int
	 */
	public function getCreation() : int {
		return $this->creation;
	}

	/**
	 * Get the validity of the ban
	 *
	 * @return bool
	 */
	public function isValid() : bool {
		return $this->valid;
	}

	/**
	 * Get the ban reason
	 *
	 * @return string
	 */
	public function getReason() : string {
		return $this->reason;
	}

	/**
	 * Get the ban issuer
	 *
	 * @return string
	 */
	public function getIssuer() : string {
		return $this->issuer;
	}

	/**
	 * Set the bans username
	 *
	 * @param string $name
	 */
	public function setUsername(string $name) {
		$this->username = $name;
	}

	/**
	 * Set the bans cid
	 *
	 * @param string $cid
	 */
	public function setClientId(string $cid) {
		$this->cid = $cid;
	}

	/**
	 * Set the bans xuid
	 *
	 * @param string $xuid
	 */
	public function setXuid(string $xuid) {
		$this->xuid = $xuid;
	}

	/**
	 * Set the ban expiry
	 *
	 * @param int $timestamp
	 */
	public function setExpiry(int $timestamp) {
		$this->expiry = $timestamp;
	}

	/**
	 * Set the ban as invalid
	 */
	public function invalidate() {
		$this->valid = false;
	}

	/**
	 * Set the ban as valid
	 */
	public function validate() {
		$this->valid = true;
	}

	/**
	 * Update the bans reason
	 *
	 * @param string $reason
	 */
	public function setReason(string $reason) {
		$this->reason = $reason;
	}

	/**
	 * Update the bans issuer
	 *
	 * @param string $issuer
	 */
	public function setIssuer(string $issuer) {
		$this->issuer = $issuer;
	}

	/**
	 * Sync this ban with the database
	 */
	public function save() {
		Main::getInstance()->getDatabaseManager()->pushToPool(new BanUpdateRequest($this->id, $this->username, $this->ip, $this->cid, $this->expiry, $this->creation, $this->reason, $this->issuer, $this->valid));
	}

}