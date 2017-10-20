<?php

/**
 * MysqlDatabaseErrorResult.php – Components
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

use core\database\exception\DatabaseException;

class MysqlDatabaseErrorResult extends MysqlDatabaseResult {

	/** @var string $exception Serialized form of the {@link DatabaseException} object. */
	private $exception;

	public function __construct(DatabaseException $e) {
		$this->setException($e);
	}

	/**
	 * @param DatabaseException $exception
	 */
	public function setException(DatabaseException $exception) {
		$this->exception = serialize($exception);
	}

	/**
	 * @return DatabaseException
	 */
	public function getException() : DatabaseException {
		return unserialize($this->exception);
	}

}