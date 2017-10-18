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
 * Created on 29/09/2017 at 9:20 PM
 *
 */

namespace core\database\request;

use core\database\exception\DatabaseException;
use core\database\exception\DatabaseRequestException;
use core\database\result\MysqlDatabaseErrorResult;
use core\database\result\MysqlDatabaseResult;
use core\database\result\MysqlDatabaseSuccessResult;
use core\database\task\DatabaseRequestExecutor;
use core\Main;

abstract class MySQLDatabaseRequest {

	protected static function executeQuery(\mysqli $mysqli, string $query, array $args) : MysqlDatabaseResult {
		$start = microtime(true);
		try {
			$stmt = $mysqli->prepare($query);
			if($stmt === false) {
				throw new DatabaseRequestException($mysqli->error);
			}

			if(count($args) > 0) {
				$types = "";
				$params = [];
				foreach($args as list($type, $arg)) {
					assert(strlen($type) === 1);
					$types .= $type;
					$params[] = $arg;
				}

				$successBind = $stmt->bind_param($types, ...$params);
				if($successBind === false) {
					throw new DatabaseRequestException($stmt->error);
				}
			}

			if($stmt->execute() === false) {
				throw new DatabaseRequestException($stmt->error);
			}

			$requestResult = new MysqlDatabaseSuccessResult();
			$requestResult->affectedRows = $stmt->affected_rows;

			$result = $stmt->get_result();
			if($result instanceof \mysqli_result) {
				$requestResult = $requestResult->asSelectResult();
				$requestResult->rows = [];
				while(is_array($row = $result->fetch_assoc())) {
					$requestResult->rows[] = $row;
				}
			} else {
				$requestResult->insertId = $stmt->insert_id;
			}

			$end = microtime(true);

			return $requestResult->setTiming($end - $start);
		} catch(DatabaseException $e) {
			$end = microtime(true);
			return (new MysqlDatabaseErrorResult($e))->setTiming($end - $start);
		} finally {
			if(isset($stmt) and $stmt instanceof \mysqli_stmt) {
				$stmt->close();
			}

			if(isset($result) and $result instanceof \mysqli_result) {
				$result->close();
			}
		}
	}

	/**
	 * Actions to execute on when the request is run on the worker
	 *
	 * @param DatabaseRequestExecutor $executor
	 *
	 * @return MysqlDatabaseResult
	 */
	abstract public function execute(DatabaseRequestExecutor $executor) : MysqlDatabaseResult;

	/**
	 * Actions to execute once back on the main thread
	 *
	 * @param Main $plugin
	 * @param MysqlDatabaseResult $result
	 */
	public function complete(Main $plugin, MysqlDatabaseResult $result) {

	}
}