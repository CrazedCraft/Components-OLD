<?php

namespace core\database\request\ban;

use core\ban\BanEntry;
use core\CorePlayer;
use core\database\request\MySQLDatabaseRequest;
use core\database\result\MysqlDatabaseErrorResult;
use core\database\result\MysqlDatabaseResult;
use core\database\result\MysqlDatabaseSelectResult;
use core\database\task\DatabaseRequestExecutor;
use core\language\LanguageUtils;
use core\Main;

/**
 * Class for handling the fetching of users' ban information
 */
class BanCheckDatabaseRequest extends MySQLDatabaseRequest {

	/**
	 * Username of the user being checked for a ban
	 *
	 * @var string
	 */
	private $username;

	/**
	 * IP of the user being checked for a ban
	 *
	 * @var string
	 */
	private $ip;

	/**
	 * Client id of the user being checked for a ban
	 *
	 * @var string
	 */
	private $cid;

	/**
	 * XUID of the user being checked for a ban
	 *
	 * @var string
	 */
	private $xuid;

	public function __construct(string $username, string $ip, string $cid, string $xuid) {
		$this->username = $username;
		$this->ip = $ip;
		$this->cid = $cid;
		$this->xuid = $xuid;
	}

	/**
	 * Execute the ban request to fetch the users existing bans
	 *
	 * @param DatabaseRequestExecutor $executor
	 *
	 * @return MysqlDatabaseResult
	 */
	public function execute(DatabaseRequestExecutor $executor) : MysqlDatabaseResult {
		return self::executeQuery(
			$executor->getMysqli(),
			"SELECT id, username, ip, uid AS cid, expires, created, reason, issuer_name AS issuer, valid FROM bans WHERE username = ? OR ip = ? OR uid = ?",
			[
				["s", $this->username],
				["s", $this->ip],
				["s", $this->cid],
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
			if($result instanceof MysqlDatabaseSelectResult) { // check the bans listed to make sure they're valid
				if(count($result->rows) > 0) { // user has bans
					$result->fixTypes([
						"id" => MysqlDatabaseSelectResult::TYPE_INT,
						"username" => MysqlDatabaseSelectResult::TYPE_STRING,
						"ip" => MysqlDatabaseSelectResult::TYPE_STRING,
						"cid" => MysqlDatabaseSelectResult::TYPE_STRING,
						"expires" => MysqlDatabaseSelectResult::TYPE_INT,
						"created" => MysqlDatabaseSelectResult::TYPE_INT,
						"reason" => MysqlDatabaseSelectResult::TYPE_STRING,
						"issuer" => MysqlDatabaseSelectResult::TYPE_STRING,
						"valid" => MysqlDatabaseSelectResult::TYPE_BOOL,
					]); // ensure the result has the correct types

					$banList = $player->getBanList();
					foreach($result->rows as $banData) {
						$banList->add(BanEntry::fromRow($banData), false);
					}

					$player->checkBanState();
				}

				$plugin->getLogger()->debug("Successfully completed ban check request for user {$this->username}");
			} elseif($result instanceof MysqlDatabaseErrorResult) { // log error to the console and let the user know something went wrong
				$player->kick(LanguageUtils::translateColors("&cUh oh! &6Looks like something is wrong with our database :(&r\n&7Contact us on twitter to let us know what happened!&r"));
				$plugin->getLogger()->debug("Encountered error while executing ban check request for {$this->username}!");
				$plugin->getLogger()->logException($result->getException());
			}
		} else {
			$plugin->getLogger()->debug("User {$this->username} logged out before their ban check request could be completed!");
		}
	}

}