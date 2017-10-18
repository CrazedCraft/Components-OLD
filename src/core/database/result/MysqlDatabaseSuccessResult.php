<?php

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