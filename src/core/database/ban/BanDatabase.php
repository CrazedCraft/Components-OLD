<?php

/**
 * BanDatabase.php – Components
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

namespace core\database\ban;

/**
 * All classes that implement a ban database MUST implement this class
 */
interface BanDatabase {

	public function check($name, $ip, $cid, $doCallback);

	public function add($name, $ip, $cid, $expiry, $reason, $issuer);

	public function update($name, $ip, $cid);

	public function remove($name, $ip, $cid);

}