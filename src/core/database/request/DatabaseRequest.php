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
 * Created on 29/09/2017 at 9:11 PM
 *
 */

namespace core\database\request;

use core\database\task\DatabaseRequestExecutor;
use pocketmine\Server;

/**
 * Base class for all database requests
 */
abstract class DatabaseRequest {

	/**
	 * Actions to execute on when the request is run on the worker
	 *
	 * @param DatabaseRequestExecutor $executor
	 */
	abstract public function execute(DatabaseRequestExecutor $executor);
}