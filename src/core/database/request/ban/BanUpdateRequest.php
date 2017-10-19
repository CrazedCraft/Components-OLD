<?php

namespace core\database\request\ban;

use core\CorePlayer;
use core\database\request\MySQLDatabaseRequest;
use core\database\result\MysqlDatabaseErrorResult;
use core\database\result\MysqlDatabaseResult;
use core\database\result\MysqlDatabaseSuccessResult;
use core\database\task\DatabaseRequestExecutor;
use core\language\LanguageUtils;
use core\Main;

class BanUpdateRequest extends MySQLDatabaseRequest {

	/**
	 * Unique id of the ban
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Name of the banned user
	 *
	 * @var string
	 */
	private $username;

	/**
	 * IP of the banned user
	 *
	 * @var string
	 */
	private $ip;

	/**
	 * CID of the banned user
	 *
	 * @var string
	 */
	private $cid;

	/**
	 * XUID of the banned user
	 *
	 * @var string
	 */
	private $xuid;

	/**
	 * Expiry timestamp of the ban
	 *
	 * @var int
	 */
	private $expiry;

	/**
	 * Creation timestamp of the ban
	 *
	 * @var int
	 */
	private $created;

	/**
	 * Reason for the ban
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * Issuer of the ban
	 *
	 * @var string
	 */
	private $issuer;

	/**
	 * The validity of the ban
	 *
	 * @var bool
	 */
	private $valid;

	/**
	 * BanUpdateRequest constructor.
	 *
	 * @param int $id
	 * @param null|string $username
	 * @param null|string $ip
	 * @param null|string $cid
	 * @param int|null $expiry
	 * @param int|null $created
	 * @param null|string $reason
	 * @param null|string $issuer
	 * @param null|bool $valid
	 */
	public function __construct(int $id, $username = null, $ip = null, $cid = null, $expiry = null, $created = null, $reason = null, $issuer = null, $valid = null) {
		$this->id = $id;

		$this->username = $username;
		$this->ip = $ip;
		$this->cid = $cid;
		$this->expiry = $expiry;
		$this->created = $created;
		$this->reason = $reason;
		$this->issuer = $issuer;
		$this->valid = $valid;
	}

	/**
	 * Execute the ban update request to update the ban data
	 *
	 * @param DatabaseRequestExecutor $executor
	 *
	 * @return MysqlDatabaseResult
	 */
	public function execute(DatabaseRequestExecutor $executor) : MysqlDatabaseResult {
		return self::executeQuery(
			$executor->getMysqli(),
			"INSERT INTO bans ({$this->getInsertKeys()}) VALUES ({$this->getInsertValuePlaceholder()}) ON DUPLICATE KEY UPDATE {$this->getUpdateKeys()}" . ($this->id !== -1 ? " WHERE id = ?" : ""),
			$this->getValues());
	}

	/**
	 * Finish the request back on the main thread by handling the result
	 *
	 * @param Main $plugin
	 * @param MysqlDatabaseResult $result
	 */
	public function complete(Main $plugin, MysqlDatabaseResult $result) {
		$server = $plugin->getServer();
		$player = $server->getPlayerExact($this->username);
		if($player instanceof CorePlayer) {
			if($result instanceof MysqlDatabaseSuccessResult) { // map the database data to the player and let them know they can login
				if($result->affectedRows <= 0) { // user wasn't updated
					$plugin->getLogger()->debug("No rows were effected whilst executing ban update request for {$this->username}!");
				} else { // user was updated
					$player->checkBanState();
					$plugin->getLogger()->debug("Successfully completed ban update request for user {$this->username}!");
				}
			} elseif($result instanceof MysqlDatabaseErrorResult) { // log error to the console and let the user know something went wrong
				$player->kick(LanguageUtils::translateColors("&cUh oh! &6Looks like something is wrong with our database :(&r\n&7Contact us on twitter to let us know what happened!&r"));
				$plugin->getLogger()->debug("Encountered error while executing ban update request for {$this->username}!");
				$plugin->getLogger()->logException($result->getException());
			}
		} else {
			$plugin->getLogger()->debug("User {$this->username} logged out before their ban update request could be completed!");
		}
	}

	/**
	 * Get the list of keys to insert
	 *
	 * TODO: Improve this (maybe some sort of loop?)
	 *
	 * @return string
	 */
	protected function getInsertKeys() : string {
		$value = "";

		if($this->id !== -1) {
			$value .= "id, ";
		}

		if($this->username !== null) {
			$value .= "username, ";
		}

		if($this->ip !== null) {
			$value .= "ip, ";
		}

		if($this->cid !== null) {
			$value .= "uid, ";
		}

		if($this->expiry !== null) {
			$value .= "expires, ";
		}

		if($this->created !== null) {
			$value .= "created, ";
		}

		if($this->reason !== null) {
			$value .= "reason, ";
		}

		if($this->issuer !== null) {
			$value .= "issuer_name, ";
		}

		if($this->valid !== null) {
			$value .= "valid";
		}

		return rtrim($value, ", ");
	}

	/**
	 * Get a the prepared statement placeholders for the insert query
	 *
	 * TODO: Improve this (maybe some sort of loop?)
	 *
	 * @return string
	 */
	protected function getInsertValuePlaceholder() : string {
		$value = 0;

		if($this->id !== -1) {
			$value++;
		}

		if($this->username !== null) {
			$value++;
		}

		if($this->ip !== null) {
			$value++;
		}

		if($this->cid !== null) {
			$value++;
		}

		if($this->expiry !== null) {
			$value++;
		}

		if($this->created !== null) {
			$value++;
		}

		if($this->reason !== null) {
			$value++;
		}

		if($this->issuer !== null) {
			$value++;
		}

		if($this->valid !== null) {
			$value++;
		}

		return rtrim(str_repeat("?, ", $value), ", ");
	}

	/**
	 * Get a list of keys to update
	 *
	 * TODO: Improve this (maybe some sort of loop?)
	 *
	 * @return string
	 */
	protected function getUpdateKeys() : string {
		$value = "";

		if($this->username !== null) {
			$value .= "username = ?, ";
		}

		if($this->ip !== null) {
			$value .= "ip = ?, ";
		}

		if($this->cid !== null) {
			$value .= "uid = ?, ";
		}

		if($this->expiry !== null) {
			$value .= "expires = ?, ";
		}

		if($this->created !== null) {
			$value .= "created = ?, ";
		}

		if($this->reason !== null) {
			$value .= "reason = ?, ";
		}

		if($this->issuer !== null) {
			$value .= "issuer_name = ?, ";
		}

		if($this->valid !== null) {
			$value .= "valid = ?";
		}

		return rtrim($value, ", ");
	}

	/**
	 * Get the list of values to insert
	 *
	 * TODO: Improve this (maybe some sort of loop?)
	 *
	 * @return array
	 */
	protected function getValues() : array {
		$values = [];

		if($this->username !== null) {
			$values[] = ["s", $this->username];
		}

		if($this->ip !== null) {
			$values[] = ["s", $this->ip];
		}

		if($this->cid !== null) {
			$values[] = ["s", $this->cid];
		}

		if($this->expiry !== null) {
			$values[] = ["i", $this->expiry];
		}

		if($this->created !== null) {
			$values[] = ["i", $this->created];
		}

		if($this->reason !== null) {
			$values[] = ["s", $this->reason];
		}

		if($this->issuer !== null) {
			$values[] = ["s", $this->issuer];
		}

		if($this->valid !== null) {
			$values[] = ["i", $this->valid ? 1 : 0];
		}

		if($this->id !== -1) {
			$id = [["i", $this->id]];
			return array_merge($id, $values, $values, $id);
		}
		return array_merge($values, $values);
	}

}