<?php

/**
 * MysqlDatabaseSuccessResult.php – Components
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

/**
 * Represents a successful result of a MySQL query.
 */
class MysqlDatabaseSuccessResult extends MysqlDatabaseResult {

	/**
	 * The number of rows affected in this query. May return unexpected values.
	 *
	 * @see <a href="https://php.net/mysqli.affected-rows">mysqli::$affected_rows</a>
	 *
	 * @var int $affectedRows
	 */
	public $affectedRows;

	/**
	 * The last insert ID returned from the database. <b>May be irrelevant to the query of this result.</b>
	 *
	 * @see <a href="https://php.net/mysqli.insert-id">mysqli::$insert_id</a>
	 * @var int $insertId
	 */
	public $insertId;

	/**
	 * Creates a {@link MysqlDatabaseSelectResult} and copies own contents into it.
	 *
	 * @internal Only intended for internal use.
	 *
	 * @return MysqlDatabaseSelectResult
	 */
	public function asSelectResult() : MysqlDatabaseSelectResult {
		$result = new MysqlDatabaseSelectResult();
		$result->affectedRows = $this->affectedRows;
		$result->insertId = $this->insertId;
		return $result;
	}

}