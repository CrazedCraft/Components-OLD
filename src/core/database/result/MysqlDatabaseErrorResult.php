<?php

namespace core\database\result;

use core\database\exception\DatabaseException;

class MysqlDatabaseErrorResult extends MysqlDatabaseResult {

	/** @var string $exception Serialized form of the {@link DatabaseException} object. */
	private $exception;

	public function __construct(DatabaseException $ex) {
		$this->setException($ex);
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