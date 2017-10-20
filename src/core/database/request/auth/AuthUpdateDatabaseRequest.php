<?php

/**
 * AuthUpdateDatabaseRequest.php – Components
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

namespace core\database\request\auth;

use core\CorePlayer;
use core\database\request\MySQLDatabaseRequest;
use core\database\result\MysqlDatabaseErrorResult;
use core\database\result\MysqlDatabaseResult;
use core\database\result\MysqlDatabaseSuccessResult;
use core\language\LanguageUtils;
use core\Main;

/**
 * Class for inserting and updating users' auth information
 */
class AuthUpdateDatabaseRequest extends MySQLDatabaseRequest {

	/**
	 * Name of the user the request is being executed for
	 *
	 * @var string
	 */
	private $username;

	/**
	 * Hash of the user
	 *
	 * @var string
	 */
	private $hash;

	/**
	 * Email of the user
	 *
	 * @var string
	 */
	private $email;

	/**
	 * Language abbreviation of the user
	 *
	 * @var string
	 */
	private $lang;

	/**
	 * Last ip the user successfully logged in with
	 *
	 * @var
	 */
	private $lastIp;

	/**
	 * Total time in seconds the user has been online
	 *
	 * @var int
	 */
	private $timePlayed;

	/**
	 * Time stamp of when the user was last seen online
	 *
	 * @var int
	 */
	private $lastLogin;

	/**
	 * Time stamp of when the user registered
	 *
	 * @var
	 */
	private $registerDate;

	/**
	 * AuthUpdateDatabaseRequest constructor.
	 *
	 * @param string $name
	 * @param null|string $hash
	 * @param null|string $email
	 * @param null|string $lang
	 * @param null|string $lastIp
	 * @param int|null $timePlayed
	 * @param int|null $lastLogin
	 * @param int|null $registerDate
	 */
	public function __construct(string $name, $hash = null, $email = null, $lang = null, $lastIp = null, $timePlayed = null, $lastLogin = null, $registerDate = null) {
		$this->username = strtolower($name);

		$this->hash = $hash;
		$this->email = $email;
		$this->lang = $lang;
		$this->lastIp = $lastIp;
		$this->timePlayed = $timePlayed;
		$this->lastLogin = $lastLogin;
		$this->registerDate = $registerDate;
	}

	/**
	 * Execute the login request to fetch the users data
	 *
	 * @param \mysqli $mysqli
	 *
	 * @return MysqlDatabaseResult
	 */
	public function execute(\mysqli $mysqli) : MysqlDatabaseResult {
		return self::executeQuery(
			$mysqli,
			"INSERT INTO auth ({$this->getInsertKeys()}) VALUES ({$this->getInsertValuePlaceholder()}) ON DUPLICATE KEY UPDATE {$this->getUpdateKeys()}",
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
					$player->sendTranslatedMessage("DATABASE_CONNECTION_ERROR", [], true);
					$plugin->getLogger()->debug("No rows were effected whilst executing update request for {$this->username}!");
				} else { // user was updated
					if($this->registerDate !== null and $this->email !== null) { // hacky way to detect if user was registered
						$player->setRegistered(true);
						$player->setAuthenticated(true);
						$player->setRegisteredTime($this->registerDate);
						$player->sendTranslatedMessage("REGISTER_SUCCESS", [], true);
						$plugin->getLogger()->debug("Successfully completed update request (registered) for user {$this->username}!");
					} elseif($this->hash !== null) { // user updated password
						$player->setHash($this->hash);
						$player->sendTranslatedMessage("PASSWORD_CHANGE_SUCCESS", [], true);
						$plugin->getLogger()->debug("Successfully completed update request (change password) for user {$this->username}!");
					} else {
						$player->setLoginTime();
						$plugin->getLogger()->debug("Successfully completed update request (general update) for user {$this->username}!");
					}
				}
			} elseif($result instanceof MysqlDatabaseErrorResult) { // log error to the console and let the user know something went wrong
				$player->kick(LanguageUtils::translateColors("&cUh oh! &6Looks like something is wrong with our database :(&r\n&7Contact us on twitter to let us know what happened!&r"));
				$plugin->getLogger()->debug("Encountered error while executing update request for {$this->username}!");
				$plugin->getLogger()->logException($result->getException());
			}
		} else {
			$plugin->getLogger()->debug("User {$this->username} logged out before their update request could be completed!");
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
		$value = "username, ";

		if($this->hash !== null) {
			$value .= "hash, ";
		}

		if($this->email !== null) {
			$value .= "email, ";
		}

		if($this->lang !== null) {
			$value .= "lang, ";
		}

		if($this->lastIp !== null) {
			$value .= "lastip, ";
		}

		if($this->timePlayed !== null) {
			$value .= "timeplayed, ";
		}

		if($this->lastLogin !== null) {
			$value .= "lastlogin, ";
		}

		if($this->registerDate !== null) {
			$value .= "registerdate";
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
		$value = 1; // always insert username

		if($this->hash !== null) {
			$value++;
		}

		if($this->email !== null) {
			$value++;
		}

		if($this->lang !== null) {
			$value++;
		}

		if($this->lastIp !== null) {
			$value++;
		}

		if($this->timePlayed !== null) {
			$value++;
		}

		if($this->lastLogin !== null) {
			$value++;
		}

		if($this->registerDate !== null) {
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

		if($this->hash !== null) {
			$value .= "hash = ?, ";
		}

		if($this->email !== null) {
			$value .= "email = ?, ";
		}

		if($this->lang !== null) {
			$value .= "lang = ?, ";
		}

		if($this->lastIp !== null) {
			$value .= "lastip = ?, ";
		}

		if($this->timePlayed !== null) {
			$value .= "timeplayed = ?, ";
		}

		if($this->lastLogin !== null) {
			$value .= "lastlogin = ?, ";
		}

		if($this->registerDate !== null) {
			$value .= "registerdate = ?";
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

		if($this->hash !== null) {
			$values[] = ["s", $this->hash];
		}

		if($this->email !== null) {
			$values[] = ["s", $this->email];
		}

		if($this->lang !== null) {
			$values[] = ["s", $this->lang];
		}

		if($this->lastIp !== null) {
			$values[] = ["s", $this->lastIp];
		}

		if($this->timePlayed !== null) {
			$values[] = ["i", $this->timePlayed];
		}

		if($this->lastLogin !== null) {
			$values[] = ["i", $this->lastLogin];
		}

		if($this->registerDate !== null) {
			$values[] = ["i", $this->registerDate];
		}

		return array_merge([["s", $this->username]], $values, $values);
	}

}