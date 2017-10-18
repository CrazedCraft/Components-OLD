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
 * Created on 29/09/2017 at 9:33 PM
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