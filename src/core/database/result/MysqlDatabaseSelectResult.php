<?php

namespace core\database\result;

class MysqlDatabaseSelectResult extends MysqlDatabaseSuccessResult {

	/**
	 * Used with {@link #fixTypes()} for string columns like (VAR)CHAR, (VAR)BINARY, BLOB
	 *
	 * @var int TYPE_STRING
	 */
	const TYPE_STRING = 1;

	/**
	 * Used with {@link #fixTypes()} for integer columns like TINYINT, SMALLINT, MEDIUMINT, INT, BIGINT
	 *
	 * @var int TYPE_INT
	 */
	const TYPE_INT = 2;

	/**
	 * Used with {@link #fixTypes()} for floating point columns like FLOAT, DOUBLE, DECIMAL
	 *
	 * @var int TYPE_FLOAT
	 */
	const TYPE_FLOAT = 3;

	/**
	 * Used with {@link #fixTypes()} for boolean columns like BIT(1)
	 *
	 * @var int TYPE_BOOL
	 */
	const TYPE_BOOL = 4;

	/**
	 * The rows returned in the query. Some columns might be strings or null; correct them with {@link #fixTypes()}.
	 *
	 * @var array[] $rows
	 */
	public $rows;

	/**
	 * Some data in the {@link #rows} may not be in the types intended, e.g. ints may be in string form. Use this method to fix the types.
	 *
	 * Example usage: {@code $result->fixTypes(["name" => MysqlSelectResult::TYPE_STRING, "id" => MysqlSelectResult::TYPE_INT, "banned" => MysqlSelectResult::TYPE_BOOL])}
	 *
	 * @param array $columns an array of column names to TYPE_* constants.
	 */
	public function fixTypes(array $columns) {
		foreach($this->rows as &$row) {
			foreach($columns as $column => $type) {
				if(isset($row[$column])) {
					switch($type) {
						case self::TYPE_STRING:
							$row[$column] = (string) $row[$column];
							break;
						case self::TYPE_INT:
							if(!is_numeric($row[$column])) {
								throw new \UnexpectedValueException("Value " . json_encode($row[$column]) . " cannot be converted to int");
							}
							$row[$column] = (int) $row[$column];
							break;
						case self::TYPE_FLOAT:
							if(!is_numeric($row[$column])) {
								throw new \UnexpectedValueException("Value " . json_encode($row[$column]) . " cannot be converted to float");
							}
							$row[$column] = (float) $row[$column];
							break;
						case self::TYPE_BOOL:
							$value = $row[$column];
							if(is_numeric($value)) {
								$row[$column] = (bool) (int) $value;
							} elseif($value === "\0" or $value === "\1") {
								$row[$column] = (bool) ord($value);
							} else {
								throw new \UnexpectedValueException("Value " . json_encode($value) . " cannot be converted to boolean");
							}
							break;
					}
				} else {
					$row[$column] = null; // this should have been set to null already, but just to make sure
				}
			}
		}
	}

}