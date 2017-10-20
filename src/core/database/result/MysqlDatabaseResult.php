<?php

/**
 * MysqlDatabaseResult.php – Components
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

namespace core\database\result;

abstract class MysqlDatabaseResult {

	/** @var float */
	private $timing;

	public function setTiming(float $timing) : MysqlDatabaseResult {
		$this->timing = $timing;
		return $this;
	}

	public function getTiming() : float {
		return $this->timing;
	}

}