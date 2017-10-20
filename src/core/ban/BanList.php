<?php

/**
 * BanList.php – Components
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

namespace core\ban;

use core\CorePlayer;
use core\Utils;

/**
 * Represents a list of bans for a user
 */
class BanList {

	/** @var string */
	private $playerUuid;

	/** @var BanEntry[] */
	private $banPool = [];

	public function __construct(CorePlayer $player) {
		$this->playerUuid = $player->getUniqueId()->toString();
	}

	/**
	 * @return CorePlayer|null
	 */
	public function getPlayer() {
		return Utils::getPlayerByUUID($this->playerUuid);
	}

	/**
	 * Add a ban to the list
	 *
	 * @param BanEntry $ban
	 * @param bool $save
	 */
	public function add(BanEntry $ban, bool $save = true) {
		$this->banPool[] = $ban;

		if($save) {
			$ban->save();
		}
	}

	/**
	 * Search the bans for a match of username, cid, ip or issuer
	 *
	 * @param null $username
	 * @param null $cid
	 * @param null $ip
	 * @param null $issuer
	 * @param bool $onlyValid
	 * @param bool $notExpired
	 *
	 * @return BanEntry[]
	 */
	public function search($username = null, $cid = null, $ip = null, $issuer = null, bool $onlyValid = true, bool $notExpired = true) : array {
		$found = [];
		$now = time();

		foreach($this->banPool as $ban) {
			if(
			($username !== null and $ban->getUsername() === $username) or // check username
			($cid !== null and $ban->getClientId() === $cid) or // check cid
			($ip !== null and $ban->getIp() === $ip) or // check ip
			($issuer !== null and $ban->getIssuer() === $issuer)) { // not expired = false pass, not expired = true and ban isn't expired pass
				if((!$onlyValid or $ban->isValid()) and (!$notExpired or ($now - $ban->getExpiry() < 0 or $ban->getExpiry() === 0))) {
					$found[] = $ban;
				}
			}
		}

		return $found;
	}

}