<?php

/**
 * CrazedCraft Network Components
 *
 * Copyright (C) 2016 CrazedCraft Network
 *
 * This is private software, you cannot redistribute it and/or modify any way
 * unless otherwise given permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author JackNoordhuis
 *
 * Created on 29/09/2017 at 9:23 PM
 *
 */

namespace core\database\request\auth;

use core\CorePlayer;
use core\database\request\MySQLDatabaseRequest;
use core\database\result\MysqlDatabaseErrorResult;
use core\database\result\MysqlDatabaseResult;
use core\database\result\MysqlDatabaseSelectResult;
use core\database\task\DatabaseRequestExecutor;
use core\language\LanguageUtils;
use core\Main;

/**
 * Class for handling the fetching of users auth information
 */
class AuthLoginDatabaseRequest extends MySQLDatabaseRequest {

	/**
	 * Name of the user the request is being executed for
	 *
	 * @var string
	 */
	private $username;

	public function __construct(string $name) {
		$this->username = $name;
	}

	/**
	 * Execute the login request to fetch the users data
	 *
	 * @param DatabaseRequestExecutor $executor
	 *
	 * @return MysqlDatabaseResult
	 */
	public function execute(DatabaseRequestExecutor $executor) : MysqlDatabaseResult {
		return self::executeQuery(
			$executor->getMysqli(),
			"SELECT hash, email, lastip, lang, timeplayed, lastlogin, registerdate, id FROM auth WHERE username = ?",
			[
				["s", $this->username],
			]);
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
			if($result instanceof MysqlDatabaseSelectResult) { // map the database data to the player and let them know they can login
				$player->sendTranslatedMessage("WELCOME", [], true, false);
				if(count($result->rows) === 0) { // user isn't registered
					$player->sendTranslatedMessage("REGISTER_PROMPT", [], true);
				} else { // user is registered
					$result->fixTypes([
						"hash" => MysqlDatabaseSelectResult::TYPE_STRING,
						"email" => MysqlDatabaseSelectResult::TYPE_STRING,
						"lastip" => MysqlDatabaseSelectResult::TYPE_STRING,
						"lang" => MysqlDatabaseSelectResult::TYPE_STRING,
						"timeplayed" => MysqlDatabaseSelectResult::TYPE_INT,
						"lastlogin" => MysqlDatabaseSelectResult::TYPE_INT,
						"registerdate" => MysqlDatabaseSelectResult::TYPE_INT,
						"id" => MysqlDatabaseSelectResult::TYPE_INT,
					]); // ensure the result has the correct types
					$row = $result->rows[0];
					$player->setRegistered(true);
					$player->setLastIp($row["lastip"]);
					$player->setHash($row["hash"]);
					$player->setEmail($row["email"]);
					$player->setRegisteredTime($row["registerdate"]);
					$player->setLanguageAbbreviation($row["lang"]);
					$player->setTimePlayed($row["timeplayed"]);
					if($player->getAddress() === $player->getLastIp()) {
						$player->setAuthenticated(true);
						$player->sendTranslatedMessage("IP_REMEMBERED_LOGIN", [], true);
					} else {
						$player->sendTranslatedMessage("LOGIN_PROMPT", [], true);
					}
				}

				$plugin->getLogger()->debug("Successfully completed login request for user {$this->username}");
			} elseif($result instanceof MysqlDatabaseErrorResult) { // log error to the console and let the user know something went wrong
				$player->kick(LanguageUtils::translateColors("&cUh oh! &6Looks like something is wrong with our database :(&r\n&7Contact us on twitter to let us know what happened!&r"));
				$plugin->getLogger()->debug("Encountered error while executing login request for {$this->username}!");
				$plugin->getLogger()->logException($result->getException());
			}
		} else {
			$plugin->getLogger()->debug("User {$this->username} logged out before their login request could be completed!");
		}
	}

}